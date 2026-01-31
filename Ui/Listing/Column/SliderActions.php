<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Slider grid actions column
 */
class SliderActions extends Column
{
    private const URL_PATH_EDIT = 'banner_slider/slider/edit';
    private const URL_PATH_DELETE = 'banner_slider/slider/delete';
    private const URL_PATH_MANAGE_BANNERS = 'banner_slider/banner/index';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['slider_id'])) {
                continue;
            }

            $item[$this->getData('name')] = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl(
                        self::URL_PATH_EDIT,
                        ['slider_id' => $item['slider_id']]
                    ),
                    'label' => __('Edit'),
                ],
                'manage_banners' => [
                    'href' => $this->urlBuilder->getUrl(
                        self::URL_PATH_MANAGE_BANNERS,
                        ['slider_id' => $item['slider_id']]
                    ),
                    'label' => __('Manage Banners'),
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl(
                        self::URL_PATH_DELETE,
                        ['slider_id' => $item['slider_id']]
                    ),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete Slider'),
                        'message' => __('Are you sure you want to delete this slider?'),
                    ],
                    'post' => true,
                ],
            ];
        }

        return $dataSource;
    }
}
