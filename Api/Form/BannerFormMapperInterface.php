<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Api\Form;

use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;

/**
 * Maps one concern of the banner form (a group of fields) between the posted form and the banner, both ways.
 *
 * Mappers are registered in the banner form hydrator's `mappers` pool (`etc/adminhtml/di.xml`); a module adds form
 * fields by adding a mapper, without changing the hydrator.
 *
 * @api
 */
interface BannerFormMapperInterface
{
    /**
     * Copy this concern's posted fields onto the banner
     *
     * A field the form did not post is left as it is. A value the banner rejects is recorded in `$errors` with a
     * translated message; the other fields are still applied.
     *
     * @param PostData $post
     * @param BannerInterface $banner
     * @param FieldErrors $errors
     * @return void
     */
    public function hydrate(PostData $post, BannerInterface $banner, FieldErrors $errors): void;

    /**
     * This concern's form values for the banner
     *
     * @param BannerInterface $banner
     * @return array<string, mixed> Form field name => value in the shape the form element expects
     */
    public function export(BannerInterface $banner): array;
}
