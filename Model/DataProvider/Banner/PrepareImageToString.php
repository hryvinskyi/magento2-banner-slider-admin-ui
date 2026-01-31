<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\DataProvider\Banner;

use Hryvinskyi\BannerSliderAdminUi\Api\DataProvider\PrepareDataInterface;
use Hryvinskyi\MediaUploader\Api\ImageDataPreparerInterface;

/**
 * Adapter for ImageDataPreparerInterface to PrepareDataInterface.
 */
class PrepareImageToString implements PrepareDataInterface
{
    /**
     * @param ImageDataPreparerInterface $imagePreparer
     */
    public function __construct(
        private readonly ImageDataPreparerInterface $imagePreparer
    ) {
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function execute(array &$data): void
    {
        $this->imagePreparer->execute($data);
    }
}
