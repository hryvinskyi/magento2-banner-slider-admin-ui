<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\Listing\Column;

use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Row actions of the slider grid: Edit, Manage Banners for admins allowed to see banners, and Delete for admins
 * allowed to delete sliders.
 */
class SliderActions extends Column
{
    private const URL_PATH_EDIT = 'banner_slider/slider/edit';
    private const URL_PATH_DELETE = 'banner_slider/slider/delete';
    private const URL_PATH_MANAGE_BANNERS = 'banner_slider/banner/index';
    private const DELETE_RESOURCE = 'Hryvinskyi_BannerSlider::slider_delete';
    private const BANNERS_RESOURCE = 'Hryvinskyi_BannerSlider::banner';

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
        $canSeeBanners = $this->authorization->isAllowed(self::BANNERS_RESOURCE);
        foreach ($items as $index => $item) {
            $sliderId = is_array($item) ? $item[SliderInterface::SLIDER_ID] ?? null : null;
            if (!is_array($item) || !is_scalar($sliderId)) {
                continue;
            }
            $actions = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['slider_id' => $sliderId]),
                    'label' => __('Edit'),
                ],
            ];
            if ($canSeeBanners) {
                $actions['manage_banners'] = [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_MANAGE_BANNERS, ['slider_id' => $sliderId]),
                    'label' => __('Manage Banners'),
                ];
            }
            if ($canDelete) {
                $actions['delete'] = [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['slider_id' => $sliderId]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete Slider'),
                        'message' => __('Are you sure you want to delete this slider?'),
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
