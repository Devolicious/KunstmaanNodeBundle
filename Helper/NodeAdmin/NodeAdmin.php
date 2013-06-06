<?php

namespace Kunstmaan\NodeBundle\Helper\NodeAdmin;

use DateTime;
use InvalidArgumentException;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Kunstmaan\AdminBundle\Entity\User;
use Kunstmaan\AdminBundle\Helper\CloneHelper;
use Kunstmaan\AdminBundle\Helper\Creators\ACLPermissionCreator;
use Kunstmaan\AdminBundle\Helper\Security\Acl\AclHelper;
use Kunstmaan\AdminBundle\Helper\Security\Acl\Permission\PermissionMap;
use Kunstmaan\NodeBundle\Entity\HasNodeInterface;
use Kunstmaan\NodeBundle\Entity\Node;
use Kunstmaan\NodeBundle\Entity\NodeTranslation;
use Kunstmaan\NodeBundle\Entity\NodeVersion;
use Kunstmaan\NodeBundle\Event\AdaptFormEvent;
use Kunstmaan\NodeBundle\Event\CopyPageTranslationNodeEvent;
use Kunstmaan\NodeBundle\Event\Events;
use Kunstmaan\NodeBundle\Event\NodeEvent;
use Kunstmaan\NodeBundle\Event\RevertNodeAction;
use Kunstmaan\NodeBundle\Form\NodeMenuTabAdminType;
use Kunstmaan\NodeBundle\Form\NodeMenuTabTranslationAdminType;
use Kunstmaan\NodeBundle\Helper\Creators\PageCreator;
use Kunstmaan\NodeBundle\Helper\NodeMenu;
use Kunstmaan\NodeBundle\Helper\Tabs\Tab;
use Kunstmaan\NodeBundle\Helper\Tabs\TabPane;
use Kunstmaan\NodeBundle\Repository\NodeRepository;
use Kunstmaan\NodeBundle\Repository\NodeTranslationRepository;
use Kunstmaan\NodeBundle\Repository\NodeVersionRepository;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\SecurityContextInterface;

class NodeAdmin
{

    /**
     * @var NodeAdminConfiguratorInterface
     */
    private $configurator;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EngineInterface
     */
    private $renderer;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    /**
     * @var AclHelper
     */
    private $aclHelper;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var CloneHelper
     */
    private $cloneHelper;

    /**
     * @var PageCreator
     */
    private $pageCreator;

    /**
     * @var integer
     */
    private $nodeVersionTimeout;


    /**
     * @var User $user
     */
    private $user;


    /**
     * @param NodeAdminConfiguratorInterface $configurator
     * @param EntityManager                  $em
     * @param EngineInterface                $renderer
     * @param FormFactoryInterface           $formFactory
     * @param SecurityContextInterface       $securityContext
     * @param AclHelper                      $aclHelper
     * @param EventDispatcherInterface       $eventDispatcher
     * @param CloneHelper                    $cloneHelper
     * @param PageCreator                    $pageCreator
     * @param integer                        $nodeVersionTimeout
     */
    public function __construct(
        NodeAdminConfiguratorInterface $configurator,
        EntityManager $em,
        EngineInterface $renderer,
        FormFactoryInterface $formFactory,
        SecurityContextInterface $securityContext,
        AclHelper $aclHelper,
        EventDispatcherInterface $eventDispatcher,
        CloneHelper $cloneHelper,
        PageCreator $pageCreator,
        $nodeVersionTimeout
    )
    {
        $this->configurator = $configurator;
        $this->em = $em;
        $this->renderer = $renderer;
        $this->formFactory = $formFactory;
        $this->securityContext = $securityContext;
        $this->aclHelper = $aclHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->cloneHelper = $cloneHelper;
        $this->pageCreator = $pageCreator;
        $this->nodeVersionTimeout = $nodeVersionTimeout; // Maybe this should be part of the configuration

        $this->user = $securityContext->getToken()->getUser();
    }

    /**
     * @param Request          $request
     * @param HasNodeInterface $pageInstance
     * @param Node             $parentNode
     *
     * @return Response
     */
    public function doAdd(Request $request, HasNodeInterface $pageInstance, Node $parentNode)
    {
        $locale = $request->getLocale();

        $parentNodeTranslation = $parentNode->getNodeTranslation($locale, true);
        $parentNodeVersion = $parentNodeTranslation->getPublicNodeVersion();
        $parentPage = $parentNodeVersion->getRef($this->em);

        $currentTitle = $pageInstance->getTitle();
        if (is_null($currentTitle) || empty($currentTitle)) {
            $pageInstance->setTitle($this->configurator->getDefaultTitle());
        }

        /**
         * @var HasNodeInterface $page
         * @var Node             $node
         * @var NodeTranslation  $nodeTranslation
         */
        list ($page, $node, $nodeTranslation, $nodeVersion) = $this->pageCreator->createPage(
            $locale, $pageInstance,
            array_merge($this->configurator->getPageCreatorOptions(), array(
                'owner' => $this->user,
                'parent' => $parentPage,
            )
        ));

        return new RedirectResponse($this->configurator->generateEditUrl($request, $page, $node, $nodeTranslation, $nodeVersion));
    }

    /**
     * @param Request $request
     * @param Node    $node
     * @param boolean $draft
     *
     * @return Response
     */
    public function doEdit(Request $request, Node $node, $draft)
    {
        $locale = $request->getLocale();
        $tabPane = new TabPane('todo', $request, $this->formFactory);

        $nodeTranslation = $node->getNodeTranslation($locale, true);
        if (!$nodeTranslation) {
            // if no translation is available, show message
            $nodeMenu = new NodeMenu($this->em, $this->securityContext, $this->aclHelper, $locale, $node, PermissionMap::PERMISSION_EDIT, true, true);
            return new Response(
                $this->renderer->render(
                    'KunstmaanNodeBundle:NodeAdmin:pagenottranslated.html.twig',
                    array(
                        'node' => $node,
                        'nodeTranslations' => $node->getNodeTranslations(true),
                        'nodemenu' => $nodeMenu,
                        'copyfromotherlanguages' => $this->doParentsHaveTranslationForLanguage($node, $locale),
                        'nodeAdminConfigurator' => $this->configurator
                    )
                )
            );
        }

        $nodeVersion = $nodeTranslation->getPublicNodeVersion();
        $draftNodeVersion = $nodeTranslation->getNodeVersion('draft');

        /* @var HasNodeInterface $page */
        $page = null;
        $saveAsDraft = $request->get('saveasdraft');
        if ((!$draft && !empty($saveAsDraft)) || ($draft && is_null($draftNodeVersion))) {
            // Create a new draft version
            $draft = true;
            $page = $nodeVersion->getRef($this->em);
            $nodeVersion = $this->createDraftVersion($page, $nodeTranslation, $nodeVersion);
            $draftNodeVersion = $nodeVersion;
        } elseif ($draft) {
            $nodeVersion = $draftNodeVersion;
            $page = $nodeVersion->getRef($this->em);
        } else {
            $page = $nodeVersion->getRef($this->em);
        }

        $this->configurator->initActionsMenu($page, $node, $nodeTranslation, $nodeVersion);
        $this->buildTabs($tabPane, $page, $node, $nodeTranslation);

        $this->eventDispatcher->dispatch(Events::ADAPT_FORM, new AdaptFormEvent($request, $tabPane, $page, $node, $nodeTranslation, $nodeVersion));

        $tabPane->buildForm();
        if ($request->getMethod() == 'POST') {
            $tabPane->bindRequest($request);
            if ($tabPane->isValid()) {

                $nodeVersion = $this->createNewVersionIfOutdated($nodeVersion, $nodeTranslation, $page);

                $this->dispatchNodeEvent(Events::PRE_PERSIST, $node, $nodeTranslation, $nodeVersion, $page);

                $nodeTranslation->setTitle($page->getTitle());
                if ($page->isStructureNode()) {
                    $nodeTranslation->setSlug('');
                }

                $this->em->persist($nodeTranslation);
                $nodeVersion->setUpdated(new DateTime());
                $this->em->persist($nodeVersion);
                $tabPane->persist($this->em);
                $this->em->flush();

                $saveAndPublish = $request->get('saveandpublish');
                if (is_string($saveAndPublish) && !empty($saveAndPublish)) {
                    $draft = false;
                    $nodeVersion = $this->createPublicVersion($page, $nodeTranslation, $nodeVersion);
                }

                $this->dispatchNodeEvent(Events::POST_PERSIST, $node, $nodeTranslation, $nodeVersion, $page);

                $session = $request->getSession();
                if ($session instanceof Session) {
                    // @todo Why are there two types of session objects one in the container and one in the request???
                    $session->getFlashBag()->add('success', 'Page has been edited!');
                }

                return new RedirectResponse($this->configurator->generateAfterSuccessfulEditUrl($request, $page, $node, $nodeTranslation, $nodeVersion, $tabPane, $draft));
            }
        }

        $nodeMenu = new NodeMenu($this->em, $this->securityContext, $this->aclHelper, $locale, $node, PermissionMap::PERMISSION_EDIT, true, true);
        $topNodes = $this->getNodeRepository()->getTopNodes($locale, PermissionMap::PERMISSION_EDIT, $this->aclHelper);
        $nodeVersions = $nodeTranslation->getNodeVersions();

        return new Response(
            $this->renderer->render(
                'KunstmaanNodeBundle:NodeAdmin:edit.html.twig',
                array(
                    'topnodes' => $topNodes, // this should be done by a listener?
                    'page' => $page,
                    'nodeVersions' => $nodeVersions,
                    'nodemenu' => $nodeMenu, // should be done by a listener?
                    'node' => $node,
                    'nodeTranslation' => $nodeTranslation,
                    'nodeVersion' => $nodeVersion,
                    'draft' => $draft,
                    'draftNodeVersion' => $draftNodeVersion,
                    'subaction' => $draft ? 'draft' : 'public',
                    'tabPane' => $tabPane,
                    'editmode' => true, // where is this used?
                    'nodeAdminConfigurator' => $this->configurator
                )
            )
        );
    }

    /**
     * @param Request $request
     * @param Node    $node
     *
     * @return Response
     */
    public function doCopyFromOtherLanguage(Request $request, Node $node)
    {
        $locale = $request->getLocale();
        $otherLocale = $request->get('originallanguage');

        list ($otherLanguagePage, $otherLanguageNodeTranslation, $otherLanguageNodeNodeVersion) = $this->getRelatedEntitiesFor($node, $otherLocale);

        $myLanguagePage = $this->cloneHelper->deepCloneAndSave($otherLanguagePage);
        /* @var NodeTranslation $nodeTranslation */
        $nodeTranslation = $this->getNodeTranslationRepository()->createNodeTranslationFor($myLanguagePage, $locale, $node, $this->user);
        $nodeVersion = $nodeTranslation->getPublicNodeVersion();

        $this->eventDispatcher->dispatch(Events::COPY_PAGE_TRANSLATION, new CopyPageTranslationNodeEvent($node, $nodeTranslation, $nodeVersion, $myLanguagePage, $otherLanguageNodeTranslation, $otherLanguageNodeNodeVersion, $otherLanguagePage, $otherLocale));

        return new RedirectResponse($this->configurator->generateEditUrl($request, $myLanguagePage, $node, $nodeTranslation, $nodeVersion));
    }

    /**
     * @param Request $request
     * @param Node    $node
     *
     * @return Response
     */
    public function doCreateEmptyPage(Request $request, Node $node)
    {
        $locale = $request->getLocale();

        $entityName = $node->getRefEntityName();
        /* @var HasNodeInterface $myLanguagePage */
        $myLanguagePage = new $entityName();
        $myLanguagePage->setTitle($this->configurator->getDefaultTitle());

        /**
         * @var HasNodeInterface $page
         * @var Node             $node
         * @var NodeTranslation  $nodeTranslation
         */
        list ($page, $node, $nodeTranslation, $nodeVersion) = $this->pageCreator->createPage(
            $locale, $myLanguagePage,
            array_merge($this->configurator->getPageCreatorOptions(), array(
                'owner' => $this->user,
                'node' => $node
            )
        ));

        return new RedirectResponse($this->configurator->generateEditUrl($request, $page, $node, $nodeTranslation, $nodeVersion));
    }

    /**
     * @param Request $request
     * @param Node    $node
     *
     * @return Response
     */
    public function doPublish(Request $request, Node $node)
    {
        return $this->changeOnlineValue($request, $node, true, Events::PRE_PUBLISH, Events::POST_PUBLISH, 'Page has been published!');
    }

    /**
     * @param Request $request
     * @param Node    $node
     *
     * @return Response
     */
    public function doUnPublish(Request $request, Node $node)
    {
        return $this->changeOnlineValue($request, $node, false, Events::PRE_UNPUBLISH, Events::POST_UNPUBLISH, 'Page has been unpublished!');
    }

    /**
     * @param Request $request
     * @param Node    $node
     *
     * @return Response
     */
    public function doDelete(Request $request, Node $node)
    {
        $locale = $request->getLocale();

        list ($page, $nodeTranslation, $nodeVersion) = $this->getRelatedEntitiesFor($node, $locale);

        $this->dispatchNodeEvent(Events::PRE_DELETE, $node, $nodeTranslation, $nodeVersion, $page);

        $node->setDeleted(true);
        $this->em->persist($node);

        $children = $node->getChildren();
        $this->deleteNodeChildren($locale, $children);
        $this->em->flush();

        $this->dispatchNodeEvent(Events::POST_DELETE, $node, $nodeTranslation, $nodeVersion, $page);

        $session = $request->getSession();
        if ($session instanceof Session) {
            // @todo Why are there two types of session objects one in the container and one in the request???
            $session->getFlashBag()->add('success', 'Page has been deleted!');
        }

        return new RedirectResponse($this->configurator->generateAfterSuccessfulDeleteUrl($request, $page, $node, $nodeTranslation, $nodeVersion));
    }

    /**
     * @param Request $request
     * @param Node    $node
     *
     * @return Response
     * @throws InvalidArgumentException
     */
    public function doRevert(Request $request, Node $node)
    {
        $locale = $request->getLocale();
        $version = $request->get('version');
        if (empty($version) || !is_numeric($version)) {
            throw new InvalidArgumentException('No version specified!');
        }

        /* @var NodeVersion $nodeVersion */
        $nodeVersion = $this->getNodeVersionRepository()->find($version);
        if (is_null($nodeVersion)) {
            throw new InvalidArgumentException('Version does not exist!');
        }

        /* @var NodeTranslation $nodeTranslation */
        $nodeTranslation = $node->getNodeTranslation($locale, true);
        $page = $nodeVersion->getRef($this->em);

        /* @var HasNodeInterface $clonedPage */
        $clonedPage = $this->cloneHelper->deepCloneAndSave($page);
        $newNodeVersion = $this->getNodeVersionRepository()->createNodeVersionFor($clonedPage, $nodeTranslation, $this->user, $nodeVersion, 'draft');
        $nodeTranslation->setTitle($clonedPage->getTitle());
        $this->em->persist($nodeTranslation);
        $this->em->flush();

        $this->eventDispatcher->dispatch(Events::REVERT, new RevertNodeAction($node, $nodeTranslation, $newNodeVersion, $clonedPage, $nodeVersion, $page));

        $session = $request->getSession();
        if ($session instanceof Session) {
            // @todo Why are there two types of session objects one in the container and one in the request???
            $session->getFlashBag()->add('success', 'Page has been reverted!');
        }

        return new RedirectResponse($this->configurator->generateEditUrl($request, $clonedPage, $node, $nodeTranslation, $newNodeVersion));
    }

    /**
     * @param string          $locale   The locale that was used
     * @param ArrayCollection $children The children array
     */
    private function deleteNodeChildren($locale, ArrayCollection $children)
    {
        /* @var Node $childNode */
        foreach ($children as $childNode) {
            $childNodeTranslation = $childNode->getNodeTranslation($locale, true);
            $childNodeVersion = $childNodeTranslation->getPublicNodeVersion();
            $childNodePage = $childNodeVersion->getRef($this->em);

            $this->dispatchNodeEvent(Events::PRE_DELETE, $childNode, $childNodeTranslation, $childNodeVersion, $childNodePage);

            $childNode->setDeleted(true);
            $this->em->persist($childNode);

            $children2 = $childNode->getChildren();
            $this->deleteNodeChildren($locale, $children2);

            $this->dispatchNodeEvent(Events::POST_DELETE, $childNode, $childNodeTranslation, $childNodeVersion, $childNodePage);
        }
    }

    /**
     * @param string           $event
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     * @param HasNodeInterface $page
     */
    private function dispatchNodeEvent($event, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion, HasNodeInterface $page)
    {
        $this->eventDispatcher->dispatch($event, new NodeEvent($node, $nodeTranslation, $nodeVersion, $page));
    }

    /**
     * @param Node   $node
     * @param string $locale
     *
     * @return array
     */
    private function getRelatedEntitiesFor(Node $node, $locale)
    {
        $nodeTranslation = $node->getNodeTranslation($locale, true);
        $nodeVersion = $nodeTranslation->getPublicNodeVersion();
        $page = $nodeVersion->getRef($this->em);

        return array($page, $nodeTranslation, $nodeVersion);
    }

    /**
     * @param Request $request
     * @param Node    $node
     * @param string  $value
     * @param string  $preChangeEvent
     * @param string  $postChangeEvent
     * @param string  $flashMessage
     *
     * @return RedirectResponse
     */
    private function changeOnlineValue(Request $request, Node $node, $value, $preChangeEvent, $postChangeEvent, $flashMessage)
    {
        $locale = $request->getLocale();

        $nodeTranslation = $node->getNodeTranslation($locale, true);
        $nodeVersion = $nodeTranslation->getPublicNodeVersion();
        $page = $nodeVersion->getRef($this->em);

        $this->dispatchNodeEvent($preChangeEvent, $node, $nodeTranslation, $nodeVersion, $page);

        $nodeTranslation->setOnline($value);

        $this->em->persist($nodeTranslation);
        $this->em->flush();

        $this->dispatchNodeEvent($postChangeEvent, $node, $nodeTranslation, $nodeVersion, $page);

        $session = $request->getSession();
        if ($session instanceof Session) {
            // @todo Why are there two types of session objects one in the container and one in the request???
            $session->getFlashBag()->add('success', $flashMessage);
        }

        return new RedirectResponse($this->configurator->generateEditUrl($request, $page, $node, $nodeTranslation, $nodeVersion));
    }

    /**
     * @param HasNodeInterface $page            The page
     * @param NodeTranslation  $nodeTranslation The node translation
     * @param NodeVersion      $nodeVersion     The node version
     * @param boolean          $publish         Publish node
     *
     * @return mixed
     */
    private function createPublicVersion(HasNodeInterface $page, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion, $publish = true)
    {
        $newPublicPage = $this->cloneHelper->deepCloneAndSave($page);
        $nodeVersion = $this->getNodeVersionRepository()->createNodeVersionFor($newPublicPage, $nodeTranslation, $this->user, $nodeVersion);
        $nodeTranslation->setPublicNodeVersion($nodeVersion);
        $nodeTranslation->setTitle($newPublicPage->getTitle());
        if ($publish) {
            $nodeTranslation->setOnline(true);
        }

        $this->em->persist($nodeTranslation);
        $this->em->flush();

        $this->eventDispatcher->dispatch(Events::CREATE_PUBLIC_VERSION, new NodeEvent($nodeTranslation->getNode(), $nodeTranslation, $nodeVersion, $newPublicPage));

        return $nodeVersion;
    }

    /**
     * @param HasNodeInterface $page            The page
     * @param NodeTranslation  $nodeTranslation The node translation
     * @param NodeVersion      $nodeVersion     The node version
     *
     * @return NodeVersion
     */
    private function createDraftVersion(HasNodeInterface $page, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion)
    {
        $publicPage = $this->cloneHelper->deepCloneAndSave($page);
        /* @var NodeVersion $publicNodeVersion */
        $publicNodeVersion = $this->getNodeVersionRepository()->createNodeVersionFor($publicPage, $nodeTranslation, $this->user, $nodeVersion->getOrigin(), 'public', $nodeVersion->getCreated());
        $nodeTranslation->setPublicNodeVersion($publicNodeVersion);
        $nodeVersion->setType('draft');
        $nodeVersion->setOrigin($publicNodeVersion);
        $nodeVersion->setCreated(new DateTime());

        $this->em->persist($nodeTranslation);
        $this->em->persist($nodeVersion);
        $this->em->flush();

        $this->eventDispatcher->dispatch(Events::CREATE_DRAFT_VERSION, new NodeEvent($nodeTranslation->getNode(), $nodeTranslation, $nodeVersion, $page));

        return $nodeVersion;
    }

    /**
     * Creates a new NodeVersion if the given one is outdated.
     *
     * @param NodeVersion $nodeVersion
     * @param NodeTranslation $nodeTranslation
     * @param HasNodeInterface $page
     *
     * @return NodeVersion
     */
    private function createNewVersionIfOutdated(NodeVersion $nodeVersion, NodeTranslation $nodeTranslation, HasNodeInterface $page)
    {
        if ($this->isCurrentVersionOutdated($nodeVersion)) {
            if ($nodeVersion == $nodeTranslation->getPublicNodeVersion()) {
                return $this->createPublicVersion($page, $nodeTranslation, $nodeVersion, false);
            } else {
                return $this->createDraftVersion($page, $nodeTranslation, $nodeVersion);
            }
        }

        return $nodeVersion;
    }

    /**
     * Checks if the given NodeVersion is outdated.
     *
     * @param NodeVersion $nodeVersion
     *
     * @return bool
     */
    private function isCurrentVersionOutdated(NodeVersion $nodeVersion)
    {
        $thresholdDate = date("Y-m-d H:i:s", time() - $this->nodeVersionTimeout);
        $updatedDate = date("Y-m-d H:i:s", strtotime($nodeVersion->getUpdated()->format("Y-m-d H:i:s")));

        return $thresholdDate >= $updatedDate;
    }

    /**
     * @param TabPane          $tabPane
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     */
    private function buildTabs(TabPane $tabPane, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation)
    {
        $propertiesTab = new Tab('Properties');
        $propertiesTab->addType('main', $page->getDefaultAdminType(), $page);
        $propertiesTab->addType('node', $node->getDefaultAdminType(), $node);
        $tabPane->addTab($propertiesTab);

        // Menu tab
        if (!$page->isStructureNode()) {
            $menuTab = new Tab('Menu');
            $menuTab->addType('menunodetranslation', new NodeMenuTabTranslationAdminType(), $nodeTranslation);
            $menuTab->addType('menunode', new NodeMenuTabAdminType(), $node);
            $tabPane->addTab($menuTab);
        }
    }

    /**
     * @param Node   $node
     * @param string $locale
     *
     * @return boolean
     */
    private function doParentsHaveTranslationForLanguage(Node $node, $locale)
    {
        $parentNode = $node->getParent();
        if ($parentNode) {
            $parentNodeTranslation = $parentNode->getNodeTranslation($locale, true);

            if ($parentNodeTranslation) {
                return $this->getNodeTranslationRepository()->hasParentNodeTranslationsForLanguage($parentNode->getNodeTranslation($locale, true), $locale);
            }

            return false;
        }

        return true;
    }

    /**
     * @return NodeVersionRepository
     */
    private $nodeVersionRepository;

    /**
     * @return NodeVersionRepository
     */
    private function getNodeVersionRepository()
    {
        if (is_null($this->nodeVersionRepository)) {
            $this->nodeVersionRepository = $this->em->getRepository('KunstmaanNodeBundle:NodeVersion');
        }

        return $this->nodeVersionRepository;
    }

    /**
     * @return NodeTranslationRepository
     */
    private $nodeTranslationRepository;

    /**
     * @return NodeTranslationRepository
     */
    private function getNodeTranslationRepository()
    {
        if (is_null($this->nodeTranslationRepository)) {
            $this->nodeTranslationRepository = $this->em->getRepository('KunstmaanNodeBundle:NodeTranslation');
        }

        return $this->nodeTranslationRepository;
    }

    /**
     * @return NodeRepository
     */
    private $node;

    /**
     * @return NodeRepository
     */
    private function getNodeRepository()
    {
        if (is_null($this->node)) {
            $this->node = $this->em->getRepository('KunstmaanNodeBundle:Node');
        }

        return $this->node;
    }

}