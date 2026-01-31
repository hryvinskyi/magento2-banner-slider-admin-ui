<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Slider;

use Hryvinskyi\BannerSlider\Model\ResourceModel\Slider\CollectionFactory;
use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Slider form data provider
 */
class FormDataProvider extends AbstractDataProvider
{
    /**
     * @var array|null
     */
    private ?array $loadedData = null;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param BreakpointRepositoryInterface $breakpointRepository
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly BreakpointRepositoryInterface $breakpointRepository,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        $items = $this->collection->getItems();

        /** @var SliderInterface $slider */
        foreach ($items as $slider) {
            $sliderData = $slider->getData();
            $sliderData['breakpoints']['breakpoints_container'] = $this->getBreakpointsData((int)$slider->getSliderId());
            $this->loadedData[$slider->getSliderId()] = $sliderData;
        }

        $data = $this->dataPersistor->get('hryvinskyi_banner_slider_slider');
        if (!empty($data)) {
            $slider = $this->collection->getNewEmptyItem();
            $slider->setData($data);
            $this->loadedData[$slider->getSliderId()] = $slider->getData();
            $this->dataPersistor->clear('hryvinskyi_banner_slider_slider');
        }

        return $this->loadedData;
    }

    /**
     * Get breakpoints data for slider
     *
     * @param int $sliderId
     * @return array<int, array<string, mixed>>
     */
    private function getBreakpointsData(int $sliderId): array
    {
        $breakpoints = $this->breakpointRepository->getBySliderId($sliderId);
        $result = [];

        foreach ($breakpoints as $breakpoint) {
            $result[] = $breakpoint->getData();
        }

        return $result;
    }
}
