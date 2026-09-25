<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\SliderFormMapperInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;

/**
 * Translates the slider form to a slider and back through the pool of field mappers (`etc/adminhtml/di.xml`).
 */
class SliderFormHydrator
{
    /**
     * @var list<SliderFormMapperInterface>
     */
    private readonly array $mappers;

    /**
     * @param array<mixed> $mappers Mapper per concern, keyed by concern name
     * @throws \InvalidArgumentException When a pool entry is not a slider form mapper
     */
    public function __construct(array $mappers = [])
    {
        $pool = [];
        foreach ($mappers as $name => $mapper) {
            if (!$mapper instanceof SliderFormMapperInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Slider form mapper "%s" must implement %s.',
                    (string)$name,
                    SliderFormMapperInterface::class
                ));
            }
            $pool[] = $mapper;
        }
        $this->mappers = $pool;
    }

    /**
     * Apply the posted form to the slider, recording every rejected value in `$errors`
     *
     * @param PostData $post
     * @param SliderInterface $slider
     * @param FieldErrors $errors
     * @return void
     */
    public function hydrate(PostData $post, SliderInterface $slider, FieldErrors $errors): void
    {
        foreach ($this->mappers as $mapper) {
            $mapper->hydrate($post, $slider, $errors);
        }
    }

    /**
     * The form values that show a refused post of the form again
     *
     * The post is applied to the slider through every mapper, so what a missing key means (an emptied uploader, a list
     * of rows emptied under its marker) is decided exactly as on save, and the slider is exported. The posted values
     * are then laid over the export, so a value the slider refused is shown as it was entered. A mapper that cannot
     * read the post leaves its fields as they were.
     *
     * @param PostData $post
     * @param SliderInterface $slider The slider the post was meant for; the post is applied to it
     * @return array<mixed>
     */
    public function restore(PostData $post, SliderInterface $slider): array
    {
        $ignored = new FieldErrors();
        foreach ($this->mappers as $mapper) {
            try {
                $mapper->hydrate($post, $slider, $ignored);
            } catch (\InvalidArgumentException) {
                continue;
            }
        }

        return array_replace($this->export($slider), $post->toArray());
    }

    /**
     * The form values of the slider
     *
     * @param SliderInterface $slider
     * @return array<string, mixed>
     */
    public function export(SliderInterface $slider): array
    {
        $values = [];
        foreach ($this->mappers as $mapper) {
            $values = array_replace($values, $mapper->export($slider));
        }

        return $values;
    }
}
