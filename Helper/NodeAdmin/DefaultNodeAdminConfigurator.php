<?php

namespace Kunstmaan\NodeBundle\Helper\NodeAdmin;
 
use Kunstmaan\NodeBundle\Entity\HasNodeInterface;
use Kunstmaan\NodeBundle\Entity\Node;
use Kunstmaan\NodeBundle\Entity\NodeTranslation;
use Kunstmaan\NodeBundle\Entity\NodeVersion;
use Kunstmaan\NodeBundle\Helper\Menu\ActionsMenuBuilder;
use Kunstmaan\NodeBundle\Helper\Tabs\TabPane;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class DefaultNodeAdminConfigurator implements NodeAdminConfiguratorInterface {

    /**
     * @var ActionsMenuBuilder
     */
    private $menuBuilder;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param ActionsMenuBuilder $menuBuilder
     * @param RouterInterface    $router
     */
    public function __construct(ActionsMenuBuilder $menuBuilder, RouterInterface $router)
    {
        $this->menuBuilder = $menuBuilder;
        $this->router = $router;
    }

    /**
     * @return string
     */
    public function getDefaultTitle()
    {
        return "New page";
    }

    /**
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     */
    public function initActionsMenu(HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion)
    {
        $this->menuBuilder->setActiveNodeVersion($nodeVersion);
        $this->menuBuilder->setIsEditableNode(!$page->isStructureNode());
    }

    /**
     * @return string
     */
    public function getSubActionsMenuAlias()
    {
        return 'sub_actions';
    }

    /**
     * @return string
     */
    public function getTopActionsMenuAlias()
    {
        return 'top_actions';
    }

    /**
     * @return string
     */
    public function getActionsMenuAlias()
    {
        return 'actions';
    }

    /**
     * @param Request          $request
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     *
     * @return string
     */
    public function generateEditUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion)
    {
        return $this->router->generate('KunstmaanNodeBundle_nodes_edit', array(
            'id' => $node->getId(),
            'subaction' => $nodeVersion != $nodeTranslation->getPublicNodeVersion() ? 'draft' : 'public'
        ));
    }

    /**
     * @param Request          $request
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     *
     * @return string
     */
    public function generateAddUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion)
    {
        return $this->router->generate('KunstmaanNodeBundle_nodes_add', array(
            'id' => $node->getId()
        ));
    }

    /**
     * @param Request          $request
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     *
     * @return string
     */
    public function generateDeleteUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion)
    {
        return $this->router->generate('KunstmaanNodeBundle_nodes_delete', array(
            'id' => $node->getId()
        ));
    }

    /**
     * @param Request          $request
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     *
     * @return string
     */
    public function generateUnPublishUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion)
    {
        return $this->router->generate('KunstmaanNodeBundle_nodes_unpublish', array(
            'id' => $node->getId()
        ));
    }

    /**
     * @param Request          $request
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     *
     * @return string
     */
    public function generatePublishUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion)
    {
        return $this->router->generate('KunstmaanNodeBundle_nodes_publish', array(
            'id' => $node->getId()
        ));
    }

    /**
     * @param Request          $request
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $currentNodeVersion
     * @param NodeVersion      $revertToNodeVersion
     *
     * @return string
     */
    public function generateRevertUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $currentNodeVersion, NodeVersion $revertToNodeVersion)
    {
        return $this->router->generate('KunstmaanNodeBundle_nodes_revert', array(
            'id' => $node->getId(),
            'version' => $revertToNodeVersion->getId()
        ));
    }

    /**
     * @param Request $request
     * @param Node    $node
     * @param string  $locale
     *
     * @return string
     */
    public function generateCopyFromOtherLanguageUrl(Request $request, Node $node, $locale)
    {
        return $this->router->generate('KunstmaanNodeBundle_nodes_copyfromotherlanguage', array(
            'id' => $node->getId(),
            'originallanguage' => $locale
        ));
    }

    /**
     * @param Request $request
     * @param Node    $node
     *
     * @return string
     */
    public function generateCreateEmptyPageUrl(Request $request, Node $node)
    {
        return $this->router->generate('KunstmaanNodeBundle_nodes_createemptypage', array(
            'id' => $node->getId()
        ));
    }

    /**
     * @param Request          $request
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     * @param TabPane          $tabPane
     *
     * @return string
     */
    public function generateAfterSuccessfulEditUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion, TabPane $tabPane)
    {
        return $this->router->generate('KunstmaanNodeBundle_nodes_edit', array_merge(array(
            'id' => $node->getId(),
            'subaction' => $nodeVersion != $nodeTranslation->getPublicNodeVersion() ? 'draft' : 'public',
            'currenttab' => $tabPane->getActiveTab()
        ), $tabPane->getExtraParams($request)));
    }

    /**
     * @param Request          $request
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     *
     * @return string
     */
    public function generateAfterSuccessfulDeleteUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion)
    {
        return $this->router->generate('KunstmaanNodeBundle_nodes_publish', array(
            'id' => $node->getParent()->getId()
        ));
    }

    /**
     * @return array
     */
    public function getPageCreatorOptions()
    {
        return array();
    }
}
