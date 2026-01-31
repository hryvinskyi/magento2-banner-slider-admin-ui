<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\DataProvider\Banner;

use Hryvinskyi\BannerSliderAdminUi\Api\DataProvider\PrepareDataInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Magento\Framework\Api\DataObjectHelper;

class PopulateEntity implements PrepareDataInterface
{
    public function __construct(
        private readonly DataObjectHelper $dataObjectHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function execute(array &$data): void
    {
        $this->dataObjectHelper->populateWithArray(
            $data['object_entity'],
            $data,
            BannerInterface::class,
        );
    }
}
