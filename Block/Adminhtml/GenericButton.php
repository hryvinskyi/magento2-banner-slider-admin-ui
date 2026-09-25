<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml;

use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * What every form and grid button of the module needs: the current request's entity ids, admin URLs and the ACL
 * check that hides a button the admin may not use.
 */
abstract class GenericButton implements ButtonProviderInterface
{
    /**
     * @param Context $context
     * @param AuthorizationInterface $authorization
     * @param EntityIdReader $idReader
     */
    public function __construct(
        private readonly Context $context,
        private readonly AuthorizationInterface $authorization,
        private readonly EntityIdReader $idReader
    ) {
    }

    /**
     * Whether the admin has an ACL resource
     *
     * @param string $resource
     * @return bool
     */
    protected function isAllowed(string $resource): bool
    {
        return $this->authorization->isAllowed($resource);
    }

    /**
     * An entity id from the request, or null
     *
     * @param string $name
     * @return int|null
     */
    protected function getRequestedId(string $name): ?int
    {
        return $this->idReader->read($this->context->getRequest(), $name);
    }

    /**
     * An admin URL
     *
     * @param string $route
     * @param array<string,mixed> $params
     * @return string
     */
    protected function getUrl(string $route, array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }

    /**
     * Data of a button that navigates to an admin URL
     *
     * @param Phrase $label
     * @param string $url
     * @param string $class
     * @param int $sortOrder
     * @return array<string, mixed>
     */
    protected function linkButton(Phrase $label, string $url, string $class, int $sortOrder): array
    {
        return [
            'label' => $label,
            'on_click' => sprintf("location.href = '%s';", $url),
            'class' => $class,
            'sort_order' => $sortOrder,
        ];
    }

    /**
     * Data of a delete button: after the admin confirms `$message`, it posts to `$url` with the form key
     *
     * Both the URL and the message travel as JSON data attributes, so no text is ever spliced into script code.
     *
     * @param string $url
     * @param Phrase $message
     * @return array<string, mixed>
     */
    protected function deleteButton(string $url, Phrase $message): array
    {
        return [
            'label' => __('Delete'),
            'class' => 'delete',
            'on_click' => '',
            'data_attribute' => [
                'post' => [
                    'action' => $url,
                    'data' => ['confirmation' => true, 'confirmationMessage' => (string)$message],
                ],
                'mage-init' => ['mage/dataPost' => new \stdClass()],
            ],
            'sort_order' => 20,
        ];
    }
}
