<?php

namespace Kunstmaan\NodeBundle\Helper\NodeAdmin;
 
use Kunstmaan\NodeBundle\Entity\HasNodeInterface;
use Kunstmaan\NodeBundle\Entity\Node;
use Kunstmaan\NodeBundle\Entity\NodeTranslation;
use Kunstmaan\NodeBundle\Entity\NodeVersion;
use Kunstmaan\NodeBundle\Helper\Menu\ActionsMenuBuilder;
use Kunstmaan\NodeBundle\Helper\Tabs\TabPane;

use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class DefaultNodeAdminConfigurator implements NodeAdminConfiguratorInterface {

    /**
     * @var ActionsMenuBuilder
     */
    private $menuBuilder;

    private $router;

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
     * @param Node $node
     * @param NodeTranslation $nodeTranslation
     * @param NodeVersion $nodeVersion
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
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node $node
     *   @param NodeTranslation $nodeTranslation
     *   @param NodeVersion $nodeVersion
     * )
     */
    public function getEditUrlGenerator()
    {
        $router = $this->router;
        return function(HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion) use ($router) {
            return $router->generate('KunstmaanNodeBundle_nodes_edit', array(
                'id' => $node->getId(),
                'subaction' => $nodeVersion != $nodeTranslation->getPublicNodeVersion() ? 'draft' : 'public'
            ));
        };
    }

    /**
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node $node
     *   @param NodeTranslation $nodeTranslation
     *   @param NodeVersion $nodeVersion
     * )
     */
    public function getAddUrlGenerator()
    {
        $router = $this->router;
        return function(HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion) use ($router) {
            return $router->generate('KunstmaanNodeBundle_nodes_add', array(
                'id' => $node->getId()
            ));
        };
    }

    /**
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node $node
     *   @param NodeTranslation $nodeTranslation
     *   @param NodeVersion $nodeVersion
     * )
     */
    public function getDeleteUrlGenerator()
    {
        $router = $this->router;
        return function(HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion) use ($router) {
            return $router->generate('KunstmaanNodeBundle_nodes_add', array(
                'id' => $node->getId()
            ));
        };
    }

    /**
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node $node
     *   @param NodeTranslation $nodeTranslation
     *   @param NodeVersion $nodeVersion
     * )
     */
    public function getUnPublishUrlGenerator()
    {
        $router = $this->router;
        return function(HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion) use ($router) {
            return $router->generate('KunstmaanNodeBundle_nodes_unpublish', array(
                'id' => $node->getId()
            ));
        };
    }

    /**
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node $node
     *   @param NodeTranslation $nodeTranslation
     *   @param NodeVersion $nodeVersion
     * )
     */
    public function getPublishUrlGenerator()
    {
        $router = $this->router;
        return function(HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion) use ($router) {
            return $router->generate('KunstmaanNodeBundle_nodes_publish', array(
                'id' => $node->getId()
            ));
        };
    }

    /**
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node $node
     *   @param NodeTranslation $nodeTranslation
     *   @param NodeVersion $currentNodeVersion
     *   @param NodeVersion $revertToNodeVersion
     * )
     */
    public function getRevertUrlGenerator()
    {
        $router = $this->router;
        return function(HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $currentNodeVersion, NodeVersion $revertToNodeVersion) use ($router) {
            return $router->generate('KunstmaanNodeBundle_nodes_revert', array(
                'id' => $node->getId(),
                'version' => $revertToNodeVersion->getId()
            ));
        };
    }

    /**
     * @return Callable(
     *   @param Node $node
     *   @param string $locale
     * )
     */
    public function getCopyFromOtherLanguageUrlGenerator()
    {
        $router = $this->router;
        return function(Node $node, $locale) use ($router) {
            return $router->generate('KunstmaanNodeBundle_nodes_revert', array(
                'id' => $node->getId(),
                'originallanguage' => $locale
            ));
        };
    }

    /**
     * @return Callable(
     *   @param Node $node
     * )
     */
    public function getCreateEmptyPageUrlGenerator()
    {
        $router = $this->router;
        return function(Node $node) use ($router) {
            return $router->generate('KunstmaanNodeBundle_nodes_createemptypage', array(
                'id' => $node->getId()
            ));
        };
    }

    /**
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node $node
     *   @param NodeTranslation $nodeTranslation
     *   @param NodeVersion $nodeVersion
     *   @param TabPane $tabPane
     * )
     */
    public function getSuccessfulEditUrlGenerator()
    {
        $router = $this->router;
        return function(HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion, TabPane $tabPane) use ($router) {
            return $router->generate('KunstmaanNodeBundle_nodes_edit', array_merge(array(
                'id' => $node->getId(),
                'subaction' => $nodeVersion != $nodeTranslation->getPublicNodeVersion() ? 'draft' : 'public',
                'currenttab' => $tabPane->getActiveTab()
            ), $tabPane->getExtraParams($request)));
        };
    }

    /**
     * @return Callable(
     * @param HasNodeInterface $page
     * @param Node $node
     * @param NodeTranslation $nodeTranslation
     * @param NodeVersion $nodeVersion
     * )
     */
    public function getAfterDeleteUrlGenerator()
    {
        $router = $this->router;
        return function(HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion) use ($router) {
            return $router->generate('KunstmaanNodeBundle_nodes_publish', array(
                'id' => $node->getParent()->getId()
            ));
        };
    }

    /**
     * @return null|Callable(
     *   @param HasNodeInterface $page
     *   @param string $locale
     * )
     */
    public function getNewPageInitializer()
    {
        return null;
    }
}
