<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\Listing\Column;

use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Row actions of the banner grid: Edit, and Delete for admins allowed to delete banners.
 */
class BannerActions extends Column
{
    private const URL_PATH_EDIT = 'banner_slider/banner/edit';
    private const URL_PATH_DELETE = 'banner_slider/banner/delete';
    private const DELETE_RESOURCE = 'Hryvinskyi_BannerSlider::banner_delete';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param AuthorizationInterface $authorization
     * @param array<mixed> $components
     * @param array<mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Add the row actions
     *
     * @param array<mixed> $dataSource
     * @return array<mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        $data = $dataSource['data'] ?? null;
        $items = is_array($data) ? $data['items'] ?? null : null;
        $fieldName = $this->getData('name');
        if (!is_array($data) || !is_array($items) || !is_string($fieldName)) {
            return $dataSource;
        }

        $canDelete = $this->authorization->isAllowed(self::DELETE_RESOURCE);
        foreach ($items as $index => $item) {
            $bannerId = is_array($item) ? $item[BannerInterface::BANNER_ID] ?? null : null;
            if (!is_array($item) || !is_scalar($bannerId)) {
                continue;
            }
            $actions = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['banner_id' => $bannerId]),
                    'label' => __('Edit'),
                ],
            ];
            if ($canDelete) {
                $actions['delete'] = [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['banner_id' => $bannerId]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete Banner'),
                        'message' => __('Are you sure you want to delete this banner?'),
                    ],
                    'post' => true,
                ];
            }
            $item[$fieldName] = $actions;
            $items[$index] = $item;
        }
        $data['items'] = $items;
        $dataSource['data'] = $data;

        return $dataSource;
    }
}
