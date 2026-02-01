<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Api;

/**
 * Interface for saving responsive crops data with banner
 *
 * @api
 */
interface ResponsiveCropsSaverInterface
{
    /**
     * Save responsive crops data for a banner
     *
     * @param int $bannerId
     * @param array<int, array<string, mixed>> $cropsData
     * @return void
     */
    public function save(int $bannerId, array $cropsData): void;
}
