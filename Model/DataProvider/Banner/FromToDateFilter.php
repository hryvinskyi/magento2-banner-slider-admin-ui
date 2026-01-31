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
use Magento\Framework\Stdlib\DateTime\Filter\DateTime;

/**
 * Filters from_date and to_date fields through DateTime filter.
 */
class FromToDateFilter implements PrepareDataInterface
{
    /**
     * @param DateTime $dateFilter
     */
    public function __construct(
        private readonly DateTime $dateFilter
    ) {
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function execute(array &$data): void
    {
        if (isset($data[BannerInterface::FROM_DATE])) {
            $data[BannerInterface::FROM_DATE] = $data[BannerInterface::FROM_DATE] !== ''
                ? $this->dateFilter->filter($data[BannerInterface::FROM_DATE])
                : null;
        }

        if (isset($data[BannerInterface::TO_DATE])) {
            $data[BannerInterface::TO_DATE] = $data[BannerInterface::TO_DATE] !== ''
                ? $this->dateFilter->filter($data[BannerInterface::TO_DATE])
                : null;
        }
    }
}
