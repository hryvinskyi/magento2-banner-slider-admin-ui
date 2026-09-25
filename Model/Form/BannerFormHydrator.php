<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\BannerFormMapperInterface;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;

/**
 * Translates the banner form to a banner and back through the pool of field mappers (`etc/adminhtml/di.xml`).
 */
class BannerFormHydrator
{
    /**
     * @var list<BannerFormMapperInterface>
     */
    private readonly array $mappers;

    /**
     * @param array<mixed> $mappers Mapper per concern, keyed by concern name
     * @throws \InvalidArgumentException When a pool entry is not a banner form mapper
     */
    public function __construct(array $mappers = [])
    {
        $pool = [];
        foreach ($mappers as $name => $mapper) {
            if (!$mapper instanceof BannerFormMapperInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Banner form mapper "%s" must implement %s.',
                    (string)$name,
                    BannerFormMapperInterface::class
                ));
            }
            $pool[] = $mapper;
        }
        $this->mappers = $pool;
    }

    /**
     * Apply the posted form to the banner, recording every rejected value in `$errors`
     *
     * @param PostData $post
     * @param BannerInterface $banner
     * @param FieldErrors $errors
     * @return void
     */
    public function hydrate(PostData $post, BannerInterface $banner, FieldErrors $errors): void
    {
        foreach ($this->mappers as $mapper) {
            $mapper->hydrate($post, $banner, $errors);
        }
    }

    /**
     * The form values that show a refused post of the form again
     *
     * The post is applied to the banner through every mapper, so what a missing key means (an emptied uploader, a list
     * of rows emptied under its marker) is decided exactly as on save, and the banner is exported. The posted values
     * are then laid over the export, so a value the banner refused is shown as it was entered. A mapper that cannot
     * read the post leaves its fields as they were.
     *
     * @param PostData $post
     * @param BannerInterface $banner The banner the post was meant for; the post is applied to it
     * @return array<mixed>
     */
    public function restore(PostData $post, BannerInterface $banner): array
    {
        $ignored = new FieldErrors();
        foreach ($this->mappers as $mapper) {
            try {
                $mapper->hydrate($post, $banner, $ignored);
            } catch (\InvalidArgumentException) {
                continue;
            }
        }

        return array_replace($this->export($banner), $post->toArray());
    }

    /**
     * The form values of the banner
     *
     * @param BannerInterface $banner
     * @return array<string, mixed>
     */
    public function export(BannerInterface $banner): array
    {
        $values = [];
        foreach ($this->mappers as $mapper) {
            $values = array_replace($values, $mapper->export($banner));
        }

        return $values;
    }
}
