<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\Component\MassAction;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Action;

/**
 * A grid mass action that is left out of the actions menu when the admin lacks its ACL resource.
 *
 * The resource comes from the action's `aclResource` data item in the listing XML. The controller behind the action
 * checks the same resource, so this only keeps the menu from offering what would be refused.
 */
class AuthorizedAction extends Action
{
    /**
     * @param ContextInterface $context
     * @param AuthorizationInterface $authorization
     * @param array<mixed> $components
     * @param array<mixed> $data
     * @param array<mixed>|null $actions
     */
    public function __construct(
        ContextInterface $context,
        private readonly AuthorizationInterface $authorization,
        array $components = [],
        array $data = [],
        $actions = null
    ) {
        parent::__construct($context, $components, $data, $actions);
    }

    /**
     * Disable the action when its ACL resource is not allowed
     *
     * @return void
     */
    public function prepare(): void
    {
        parent::prepare();

        $resource = $this->getData('aclResource');
        if (is_string($resource) && $resource !== '' && !$this->authorization->isAllowed($resource)) {
            $config = $this->getConfiguration();
            $config['actionDisable'] = true;
            $this->setData('config', $config);
        }
    }
}
