<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Banner;

use Hryvinskyi\BannerSlider\Model\BannerFactory;
use Hryvinskyi\BannerSliderAdminUi\Api\DataProvider\PrepareDataProcessorInterface;
use Hryvinskyi\BannerSliderApi\Api\BannerRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerExtensionFactory;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;

/**
 * Save banner controller
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner_save';

    /**
     * @param Context $context
     * @param BannerRepositoryInterface $bannerRepository
     * @param BannerFactory $bannerFactory
     * @param PrepareDataProcessorInterface $prepareDataProcessor
     * @param BannerExtensionFactory $extensionFactory
     */
    public function __construct(
        Context $context,
        private readonly BannerRepositoryInterface $bannerRepository,
        private readonly BannerFactory $bannerFactory,
        private readonly PrepareDataProcessorInterface $prepareDataProcessor,
        private readonly BannerExtensionFactory $extensionFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $bannerId = isset($data['banner_id']) ? (int)$data['banner_id'] : null;

        try {
            if ($bannerId) {
                $banner = $this->bannerRepository->getById($bannerId);
            } else {
                $banner = $this->bannerFactory->create();
            }

            $data['object_entity'] = $banner;
            $this->prepareDataProcessor->execute($data);
            $this->setExtensionAttributes($banner, $data);
            $this->bannerRepository->save($banner);

            $this->messageManager->addSuccessMessage(__('Banner has been saved.'));

            if ($this->getRequest()->getParam('back') === 'edit') {
                return $resultRedirect->setPath('*/*/edit', ['banner_id' => $banner->getBannerId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the banner.'));
        }

        return $resultRedirect->setPath('*/*/edit', ['banner_id' => $bannerId]);
    }

    /**
     * Set extension attributes on banner from request data
     *
     * @param BannerInterface $banner
     * @param array<string, mixed> $data
     * @return void
     */
    private function setExtensionAttributes(BannerInterface $banner, array $data): void
    {
        $extensionAttributes = $banner->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }

        $cropsData = $data['responsive_crops_data'] ?? null;

        if (!empty($cropsData) && is_array($cropsData)) {
            $extensionAttributes->setResponsiveCropsData($cropsData);
        }

        $banner->setExtensionAttributes($extensionAttributes);
    }
}
