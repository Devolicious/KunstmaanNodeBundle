<?php

namespace Kunstmaan\NodeBundle\Helper\NodeAdmin;
 
use Kunstmaan\NodeBundle\Entity\HasNodeInterface;
use Kunstmaan\NodeBundle\Entity\Node;
use Kunstmaan\NodeBundle\Entity\NodeTranslation;
use Kunstmaan\NodeBundle\Entity\NodeVersion;
use Kunstmaan\NodeBundle\Helper\Tabs\TabPane;
use Symfony\Component\HttpFoundation\Request;

interface NodeAdminConfiguratorInterface
{

    /**
     * @return string
     */
    public function getDefaultTitle();

    /**
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     */
    public function initActionsMenu(HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion);

    /**
     * @return string
     */
    public function getSubActionsMenuAlias();

    /**
     * @return string
     */
    public function getTopActionsMenuAlias();

    /**
     * @return string
     */
    public function getActionsMenuAlias();

    /**
     * @param Request          $request
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     *
     * @return string
     */
    public function generateEditUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion);

    /**
     * @param Request          $request
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     *
     * @return string
     */
    public function generateAddUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion);

    /**
     * @param Request          $request
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     *
     * @return string
     */
    public function generateDeleteUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion);

    /**
     * @param Request          $request
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     *
     * @return string
     */
    public function generateUnPublishUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion);

    /**
     * @param Request          $request
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     *
     * @return string
     */
    public function generatePublishUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion);

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
    public function generateRevertUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $currentNodeVersion, NodeVersion $revertToNodeVersion);

    /**
     * @param Request $request
     * @param Node    $node
     * @param string  $locale
     *
     * @return string
     */
    public function generateCopyFromOtherLanguageUrl(Request $request, Node $node, $locale);

    /**
     * @param Request $request
     * @param Node    $node
     *
     * @return string
     */
    public function generateCreateEmptyPageUrl(Request $request, Node $node);

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
    public function generateAfterSuccessfulEditUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion, TabPane $tabPane);

    /**
     * @param Request          $request
     * @param HasNodeInterface $page
     * @param Node             $node
     * @param NodeTranslation  $nodeTranslation
     * @param NodeVersion      $nodeVersion
     *
     * @return string
     */
    public function generateAfterSuccessfulDeleteUrl(Request $request, HasNodeInterface $page, Node $node, NodeTranslation $nodeTranslation, NodeVersion $nodeVersion);

    /**
     * @return array
     */
    public function getPageCreatorOptions();

}
