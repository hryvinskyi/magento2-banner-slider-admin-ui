<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Source;

use Hryvinskyi\BannerSlider\Model\ResourceModel\Slider\CollectionFactory;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Slider source model for select options
 */
class Slider implements OptionSourceInterface
{
    /**
     * @var array|null
     */
    private ?array $options = null;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $this->options = [['value' => '', 'label' => __('-- Please Select --')]];

        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect([SliderInterface::SLIDER_ID, SliderInterface::NAME]);
        $collection->setOrder(SliderInterface::NAME, 'ASC');

        foreach ($collection as $slider) {
            $this->options[] = [
                'value' => $slider->getSliderId(),
                'label' => $slider->getName(),
            ];
        }

        return $this->options;
    }
}
