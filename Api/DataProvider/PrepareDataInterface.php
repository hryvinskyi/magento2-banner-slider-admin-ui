<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Api\DataProvider;

/**
 * Interface for data transformation in data providers and save controllers.
 *
 * @api
 */
interface PrepareDataInterface
{
    /**
     * Transform data within the provided array.
     *
     * @param array<string, mixed> $data Reference to data array
     * @return void
     */
    public function execute(array &$data): void;
}
