<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Api\DataProvider;

/**
 * Interface for composite data processor that executes multiple data transformations.
 *
 * @api
 */
interface PrepareDataProcessorInterface
{
    /**
     * Execute all registered data processors on the provided data.
     *
     * @param array<string, mixed> $data Reference to data array
     * @return void
     */
    public function execute(array &$data): void;
}
