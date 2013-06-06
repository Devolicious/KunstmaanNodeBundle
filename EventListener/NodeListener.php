<?php

namespace Kunstmaan\NodeBundle\EventListener;

use Kunstmaan\AdminBundle\Helper\Creators\ACLPermissionCreator;
use Kunstmaan\NodeBundle\Event\NodeEvent;
use Kunstmaan\NodeBundle\Helper\Tabs\PermissionTab;
use Kunstmaan\NodeBundle\Event\AdaptFormEvent;
use Kunstmaan\AdminBundle\Helper\Security\Acl\Permission\PermissionAdmin;
use Kunstmaan\AdminBundle\Helper\Security\Acl\Permission\PermissionMapInterface;

use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * NodeListener
 */
class NodeListener
{

    /**
     * SecurityContextInterface
     */
    private $securityContext;

    /**
     * @var PermissionAdmin
     */
    protected $permissionAdmin;

    /**
     * @var PermissionMapInterface
     */
    protected $permissionMap;

    /**
     * @var ACLPermissionCreator
     */
    protected $permissionCreator;

    /**
     * @param SecurityContextInterface $securityContext The security context
     * @param PermissionAdmin          $permissionAdmin The permission admin
     * @param PermissionMapInterface   $permissionMap   The permission map
     * @param ACLPermissionCreator     $permissionCreator
     */
    public function __construct(SecurityContextInterface $securityContext, PermissionAdmin $permissionAdmin, PermissionMapInterface $permissionMap, ACLPermissionCreator $permissionCreator)
    {
        $this->permissionAdmin = $permissionAdmin;
        $this->permissionMap = $permissionMap;
        $this->securityContext = $securityContext;
        $this->permissionCreator = $permissionCreator;
    }

    /**
     * @param AdaptFormEvent $event
     */
    public function onAdaptForm(AdaptFormEvent $event)
    {
        if ($this->securityContext->isGranted('ROLE_PERMISSIONMANAGER')) {
            $tabPane = $event->getTabPane();
            $tabPane->addTab(new PermissionTab('Permissions', $event->getPage(), $event->getNode(), $this->permissionAdmin, $this->permissionMap));
        }
    }

    /**
     * @param NodeEvent $event
     */
    public function onAddNode(NodeEvent $event)
    {
        $node = $event->getNode();
        $parentNode = $node->getParent();
        if (!is_null($parentNode)) {
            $this->permissionCreator->initByExample($node, $parentNode, false);
        }
    }

}
