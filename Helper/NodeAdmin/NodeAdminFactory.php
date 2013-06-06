<?php

namespace Kunstmaan\NodeBundle\Helper\NodeAdmin;
 
use Doctrine\ORM\EntityManager;

use Kunstmaan\AdminBundle\Helper\CloneHelper;
use Kunstmaan\AdminBundle\Helper\Creators\ACLPermissionCreator;
use Kunstmaan\AdminBundle\Helper\Security\Acl\AclHelper;
use Kunstmaan\NodeBundle\Helper\Creators\PageCreator;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class NodeAdminFactory
{

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
     * @param EntityManager            $em
     * @param EngineInterface          $renderer
     * @param FormFactoryInterface     $formFactory
     * @param SecurityContextInterface $securityContext
     * @param AclHelper                $aclHelper
     * @param EventDispatcherInterface $eventDispatcher
     * @param CloneHelper              $cloneHelper
     * @param PageCreator              $pageCreator
     * @param integer                  $nodeVersionTimeout
     */
    public function __construct(
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
        $this->em = $em;
        $this->renderer = $renderer;
        $this->formFactory = $formFactory;
        $this->securityContext = $securityContext;
        $this->aclHelper = $aclHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->cloneHelper = $cloneHelper;
        $this->pageCreator = $pageCreator;
        $this->nodeVersionTimeout = $nodeVersionTimeout;
    }

    /**
     * @param NodeAdminConfiguratorInterface $configurator
     *
     * @return NodeAdmin
     */
    public function createNodeAdmin(NodeAdminConfiguratorInterface $configurator)
    {
        return new NodeAdmin($configurator, $this->em, $this->renderer, $this->formFactory, $this->securityContext, $this->aclHelper, $this->eventDispatcher, $this->cloneHelper, $this->pageCreator, $this->nodeVersionTimeout);
    }

}
