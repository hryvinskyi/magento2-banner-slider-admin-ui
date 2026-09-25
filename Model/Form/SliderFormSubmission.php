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
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterfaceFactory;
use Hryvinskyi\BannerSliderApi\Api\Slider\SliderEditorInterface;
use Hryvinskyi\BannerSliderApi\Api\SliderRepositoryInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use Psr\Log\LoggerInterface;

/**
 * Saves a posted slider form: loads or creates the slider, applies the form and the breakpoint rows, and hands both
 * to the slider editor, the one write path of a slider. Every invalid field is reported at once, before anything is
 * written.
 */
class SliderFormSubmission
{
    /**
     * @param SliderRepositoryInterface $sliderRepository
     * @param SliderInterfaceFactory $sliderFactory
     * @param SliderFormHydrator $hydrator
     * @param BreakpointInputMapper $breakpointInputMapper
     * @param SliderEditorInterface $sliderEditor
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly SliderInterfaceFactory $sliderFactory,
        private readonly SliderFormHydrator $hydrator,
        private readonly BreakpointInputMapper $breakpointInputMapper,
        private readonly SliderEditorInterface $sliderEditor,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Save the posted slider
     *
     * @param array<mixed> $post
     * @return SliderInterface The saved slider
     * @throws ValidationException With every invalid field, or every rule the slider or its breakpoints break
     * @throws NoSuchEntityException When the posted slider id does not exist
     * @throws AlreadyExistsException When a unique key is already taken
     * @throws CouldNotSaveException When the slider or a breakpoint cannot be stored
     * @throws LocalizedException When the posted slider id is not a number
     */
    public function submit(array $post): SliderInterface
    {
        $data = new PostData($post);
        $slider = $this->slider($data);
        $errors = new FieldErrors();
        $this->hydrator->hydrate($data, $slider, $errors);
        $breakpoints = $this->breakpointInputMapper->map($data, $slider->getSliderId(), $errors);

        if ($errors->hasErrors()) {
            $this->logger->info('Banner slider: slider form refused.', ['details' => $errors->getDetails()]);
            throw $errors->toException();
        }

        return $this->sliderEditor->save($slider, $breakpoints);
    }

    /**
     * The stored slider the post edits, or a new one
     *
     * @param PostData $data
     * @return SliderInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function slider(PostData $data): SliderInterface
    {
        try {
            $sliderId = $data->integer(SliderInterface::SLIDER_ID);
        } catch (\InvalidArgumentException) {
            throw new LocalizedException(__('The slider to save could not be identified.'));
        }

        return $sliderId !== null && $sliderId > 0
            ? $this->sliderRepository->getById($sliderId)
            : $this->sliderFactory->create();
    }
}
