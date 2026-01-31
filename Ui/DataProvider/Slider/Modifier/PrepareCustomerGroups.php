<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Slider\Modifier;

use Hryvinskyi\BannerSliderAdminUi\Api\DataProvider\PrepareDataInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;

class PrepareCustomerGroups implements PrepareDataInterface
{
    /**
     * @inheritDoc
     */
    #[\Override]
    public function execute(array &$data): void
    {
        if (isset($data[SliderInterface::CUSTOMER_GROUP_IDS])
            && is_string($data[SliderInterface::CUSTOMER_GROUP_IDS])) {
            $data[SliderInterface::CUSTOMER_GROUP_IDS] = explode(',', $data[SliderInterface::CUSTOMER_GROUP_IDS]);
        }
    }
}
