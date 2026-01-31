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

class PrepareStores implements PrepareDataInterface
{
    /**
     * @inheritDoc
     */
    #[\Override]
    public function execute(array &$data): void
    {
        if (isset($data[SliderInterface::STORE_IDS])
            && is_string($data[SliderInterface::STORE_IDS])) {
            $data[SliderInterface::STORE_IDS] = explode(',', $data[SliderInterface::STORE_IDS]);
        }
    }
}
