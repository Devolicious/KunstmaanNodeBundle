<?php

namespace Kunstmaan\NodeBundle\Helper\NodeAdmin;
 
use Kunstmaan\NodeBundle\Entity\HasNodeInterface;
use Kunstmaan\NodeBundle\Entity\Node;
use Kunstmaan\NodeBundle\Entity\NodeTranslation;
use Kunstmaan\NodeBundle\Entity\NodeVersion;
use Kunstmaan\NodeBundle\Helper\Tabs\TabPane;

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
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node             $node
     *   @param NodeTranslation  $nodeTranslation
     *   @param NodeVersion      $nodeVersion
     * )
     */
    public function getEditUrlGenerator();

    /**
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node             $node
     *   @param NodeTranslation  $nodeTranslation
     *   @param NodeVersion      $nodeVersion
     * )
     */
    public function getAddUrlGenerator();

    /**
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node             $node
     *   @param NodeTranslation  $nodeTranslation
     *   @param NodeVersion      $nodeVersion
     * )
     */
    public function getDeleteUrlGenerator();

    /**
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node             $node
     *   @param NodeTranslation  $nodeTranslation
     *   @param NodeVersion      $nodeVersion
     * )
     */
    public function getUnPublishUrlGenerator();

    /**
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node             $node
     *   @param NodeTranslation  $nodeTranslation
     *   @param NodeVersion      $nodeVersion
     * )
     */
    public function getPublishUrlGenerator();

    /**
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node             $node
     *   @param NodeTranslation  $nodeTranslation
     *   @param NodeVersion      $currentNodeVersion
     *   @param NodeVersion      $revertToNodeVersion
     * )
     */
    public function getRevertUrlGenerator();

    /**
     * @return Callable(
     *   @param Node   $node
     *   @param string $locale
     * )
     */
    public function getCopyFromOtherLanguageUrlGenerator();

    /**
     * @return Callable(
     *   @param Node $node
     * )
     */
    public function getCreateEmptyPageUrlGenerator();

    /**
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node             $node
     *   @param NodeTranslation  $nodeTranslation
     *   @param NodeVersion      $nodeVersion
     *   @param TabPane          $tabPane
     *   @param boolean          $draft
     * )
     */
    public function getSuccessfulEditUrlGenerator();

    /**
     * @return Callable(
     *   @param HasNodeInterface $page
     *   @param Node             $node
     *   @param NodeTranslation  $nodeTranslation
     *   @param NodeVersion      $nodeVersion
     * )
     */
    public function getAfterDeleteUrlGenerator();

    /**
     * @return null|Callable(
     *   @param HasNodeInterface $page
     *   @param string           $locale
     * )
     */
    public function getNewPageInitializer();

}
