<?php

namespace Kunstmaan\NodeBundle\Controller;

use InvalidArgumentException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Kunstmaan\AdminBundle\Helper\Security\Acl\Permission\PermissionAdmin;
use Kunstmaan\AdminBundle\Helper\Security\Acl\Permission\PermissionMap;
use Kunstmaan\AdminListBundle\AdminList\AdminList;
use Kunstmaan\NodeBundle\AdminList\NodeAdminListConfigurator;
use Kunstmaan\NodeBundle\Entity\Node;
use Kunstmaan\NodeBundle\Entity\HasNodeInterface;
use Kunstmaan\NodeBundle\Helper\Menu\ActionsMenuBuilder;
use Kunstmaan\NodeBundle\Helper\NodeMenu;
use Kunstmaan\NodeBundle\Helper\NodeAdmin\DefaultNodeAdminConfigurator;
use Kunstmaan\NodeBundle\Helper\NodeAdmin\NodeAdmin;
use Kunstmaan\NodeBundle\Helper\NodeAdmin\NodeAdminFactory;
use Kunstmaan\NodeBundle\Repository\NodeRepository;

/**
 * NodeAdminController
 */
class NodeAdminController extends Controller
{

    /**
     * @Route("/", name="KunstmaanNodeBundle_nodes")
     * @Template("KunstmaanNodeBundle:Admin:list.html.twig")
     *
     * @return array
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $locale = $this->getRequest()->getLocale();
        $securityContext = $this->get('security.context');
        $aclHelper = $this->get('kunstmaan_admin.acl.helper');
        $adminListFactory = $this->get('kunstmaan_adminlist.factory');

        /* @var Node[] $topNodes */
        $topNodes = $this->getNodeRepository()->getTopNodes($locale, PermissionMap::PERMISSION_EDIT, $aclHelper, true);
        $nodeMenu = new NodeMenu($em, $securityContext, $aclHelper, $locale, null, PermissionMap::PERMISSION_EDIT, true, true);

        /* @var AdminList $adminlist */
        $adminlist = $adminListFactory->createList(new NodeAdminListConfigurator($em, $aclHelper, $locale, PermissionMap::PERMISSION_EDIT));
        $adminlist->bindRequest($this->getRequest());

        return array(
            'topnodes' => $topNodes,
            'nodemenu' => $nodeMenu,
            'adminlist' => $adminlist,
        );
    }

    /**
     * @var NodeAdmin
     */
    private $nodeAdmin;

    /**
     * @return NodeAdmin
     */
    private function getNodeAdmin()
    {
        if (is_null($this->nodeAdmin)) {
            /** @var NodeAdminFactory $nodeAdminFactory */
            $nodeAdminFactory = $this->get('kunstmaan_node.node_admin.factory');
            /** @var ActionsMenuBuilder $menuBuilder */
            $menuBuilder = $this->get('kunstmaan_node.actions_menu_builder');
            /** @var RouterInterface $router */
            $router = $this->get('router');
            $this->nodeAdmin = $nodeAdminFactory->createNodeAdmin(new DefaultNodeAdminConfigurator($menuBuilder, $router));
        }

        return $this->nodeAdmin;
    }

    /**
     * @param Node $node
     *
     * @throws AccessDeniedException
     * @Route("/{id}/copyfromotherlanguage", requirements={"_method" = "GET", "id" = "\d+"}, name="KunstmaanNodeBundle_nodes_copyfromotherlanguage")
     *
     * @return Response
     */
    public function copyFromOtherLanguageAction(Node $node)
    {
        $this->checkPermission($node, PermissionMap::PERMISSION_EDIT);
        return $this->getNodeAdmin()->doCopyFromOtherLanguage($this->getRequest(), $node);
    }

    /**
     * @param Node $node
     *
     * @throws AccessDeniedException
     * @Route("/{id}/createemptypage", requirements={"_method" = "GET", "id" = "\d+"}, name="KunstmaanNodeBundle_nodes_createemptypage")
     *
     * @return Response
     */
    public function createEmptyPageAction(Node $node)
    {
        $this->checkPermission($node, PermissionMap::PERMISSION_EDIT);
        return $this->getNodeAdmin()->doCreateEmptyPage($this->getRequest(), $node);
    }

    /**
     * @param Node $node
     *
     * @throws AccessDeniedException
     * @Route("/{id}/publish", requirements={"_method" = "GET|POST", "id" = "\d+"}, name="KunstmaanNodeBundle_nodes_publish")
     *
     * @return Response
     */
    public function publishAction(Node $node)
    {
        $this->checkPermission($node, PermissionMap::PERMISSION_PUBLISH);
        return $this->getNodeAdmin()->doPublish($this->getRequest(), $node);
    }

    /**
     * @param Node $node
     *
     * @throws AccessDeniedException
     * @Route("/{id}/unpublish", requirements={"_method" = "GET|POST", "id" = "\d+"}, name="KunstmaanNodeBundle_nodes_unpublish")
     *
     * @return Response
     */
    public function unPublishAction(Node $node)
    {
        $this->checkPermission($node, PermissionMap::PERMISSION_UNPUBLISH);
        return $this->getNodeAdmin()->doUnPublish($this->getRequest(), $node);
    }

    /**
     * @param Node $node
     *
     * @throws AccessDeniedException
     * @Route("/{id}/delete", requirements={"_method" = "POST", "id" = "\d+"}, name="KunstmaanNodeBundle_nodes_delete")
     *
     * @return Response
     */
    public function deleteAction(Node $node)
    {
        $this->checkPermission($node, PermissionMap::PERMISSION_DELETE);
        return $this->getNodeAdmin()->doDelete($this->getRequest(), $node);
    }

    /**
     * @param Node $node
     *
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @Route("/{id}/revert", requirements={"_method" = "GET", "id" = "\d+"}, defaults={"subaction" = "public"}, name="KunstmaanNodeBundle_nodes_revert")
     *
     * @return Response
     */
    public function revertAction(Node $node)
    {
        $this->checkPermission($node, PermissionMap::PERMISSION_EDIT);
        return $this->getNodeAdmin()->doRevert($this->getRequest(), $node);
    }

    /**
     * @param Node $parentNode
     *
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @Route("/{id}/add", requirements={"_method" = "POST", "id" = "\d+"}, name="KunstmaanNodeBundle_nodes_add")
     *
     * @return Response
     */
    public function addAction(Node $parentNode)
    {
        $this->checkPermission($parentNode, PermissionMap::PERMISSION_EDIT);

        $request = $this->getRequest();
        $type = $request->get('type');

        if (empty($type)) {
            throw new InvalidArgumentException('Please specify a type of page to create!');
        }

        /** @var HasNodeInterface $pageInstance */
        $pageInstance = new $type();

        if (!($pageInstance instanceof HasNodeInterface)) {
            throw new InvalidArgumentException('Please specify a type which implements the HasNodeInterface!');
        }

        $title = $request->get('title');
        if (is_string($title) && !empty($title)) {
            $pageInstance->setTitle($title);
        }

        return $this->getNodeAdmin()->doAdd($request, $pageInstance, $parentNode);
    }

    /**
     * @param Node   $node
     * @param string $subaction The subaction (draft|public)
     *
     * @throws AccessDeniedException
     * @Route("/{id}/{subaction}", requirements={"_method" = "GET|POST", "id" = "\d+"}, defaults={"subaction" = "public"}, name="KunstmaanNodeBundle_nodes_edit")
     *
     * @return Response
     */
    public function editAction(Node $node, $subaction)
    {
       $this->checkPermission($node, PermissionMap::PERMISSION_EDIT);
       return $this->getNodeAdmin()->doEdit($this->getRequest(), $node, ($subaction == 'draft'));
    }

    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    /**
     * @param Node   $node       The node
     * @param string $permission The permission to check for
     *
     * @throws AccessDeniedException
     */
    private function checkPermission(Node $node, $permission)
    {
        if (is_null($this->securityContext)) {
            $this->securityContext = $this->get('security.context');
        }

        if (false === $this->securityContext->isGranted($permission, $node)) {
            throw new AccessDeniedException();
        }
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
            $em = $this->getDoctrine()->getManager();
            $this->node = $em->getRepository('KunstmaanNodeBundle:Node');
        }

        return $this->node;
    }

}
