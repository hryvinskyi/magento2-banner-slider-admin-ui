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
use Hryvinskyi\BannerSliderApi\Api\Banner\BannerEditorInterface;
use Hryvinskyi\BannerSliderApi\Api\BannerRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterfaceFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use Psr\Log\LoggerInterface;

/**
 * Saves a posted banner form: loads or creates the banner, applies the form and the crop changes, and hands both to
 * the banner editor, the one write path of a banner. Every invalid field is reported at once, before anything is
 * written.
 */
class BannerFormSubmission
{
    /**
     * @param BannerRepositoryInterface $bannerRepository
     * @param BannerInterfaceFactory $bannerFactory
     * @param BannerFormHydrator $hydrator
     * @param CropInputMapper $cropInputMapper
     * @param BannerEditorInterface $bannerEditor
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly BannerRepositoryInterface $bannerRepository,
        private readonly BannerInterfaceFactory $bannerFactory,
        private readonly BannerFormHydrator $hydrator,
        private readonly CropInputMapper $cropInputMapper,
        private readonly BannerEditorInterface $bannerEditor,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Save the posted banner
     *
     * @param array<mixed> $post
     * @return BannerInterface The saved banner
     * @throws ValidationException With every invalid field, or every rule the banner or its crops break
     * @throws NoSuchEntityException When the posted banner id does not exist
     * @throws CouldNotSaveException When the banner or a crop file cannot be stored
     * @throws LocalizedException When the posted banner id is not a number
     */
    public function submit(array $post): BannerInterface
    {
        $data = new PostData($post);
        $banner = $this->banner($data);
        $errors = new FieldErrors();
        $this->hydrator->hydrate($data, $banner, $errors);
        $crops = $this->cropInputMapper->map($data, $errors);

        if ($errors->hasErrors()) {
            $this->logger->info('Banner slider: banner form refused.', ['details' => $errors->getDetails()]);
            throw $errors->toException();
        }

        return $this->bannerEditor->save($banner, $crops);
    }

    /**
     * The stored banner the post edits, or a new one
     *
     * @param PostData $data
     * @return BannerInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function banner(PostData $data): BannerInterface
    {
        try {
            $bannerId = $data->integer(BannerInterface::BANNER_ID);
        } catch (\InvalidArgumentException) {
            throw new LocalizedException(__('The banner to save could not be identified.'));
        }

        return $bannerId !== null && $bannerId > 0
            ? $this->bannerRepository->getById($bannerId)
            : $this->bannerFactory->create();
    }
}
