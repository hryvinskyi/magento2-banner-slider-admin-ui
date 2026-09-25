<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Banner;

use Hryvinskyi\BannerSliderAdminUi\Model\Form\BannerFormSubmission;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\PersistableBannerPost;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\RefusedPostStore;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validation\ValidationException;

/**
 * Saves the banner form, then returns to the grid or, on "Save and Continue" or any error, to the form.
 *
 * On error the post is kept for the form of the same banner to show again, without the browser-encoded crop images.
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner_save';
    public const PERSISTOR_KEY = 'hryvinskyi_banner_slider_banner';

    /**
     * @param Context $context
     * @param BannerFormSubmission $submission
     * @param RefusedPostStore $refusedPosts
     * @param PersistableBannerPost $persistablePost
     * @param EntityIdReader $idReader
     */
    public function __construct(
        Context $context,
        private readonly BannerFormSubmission $submission,
        private readonly RefusedPostStore $refusedPosts,
        private readonly PersistableBannerPost $persistablePost,
        private readonly EntityIdReader $idReader
    ) {
        parent::__construct($context);
    }

    /**
     * Save the posted banner
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create();
        $request = $this->getRequest();
        $post = $request instanceof HttpRequest ? $request->getPostValue() : null;
        if (!is_array($post) || $post === []) {
            return $redirect->setPath('*/*/');
        }

        try {
            $banner = $this->submission->submit($post);
            $this->refusedPosts->forget(self::PERSISTOR_KEY);
            $this->messageManager->addSuccessMessage(__('The banner has been saved.'));

            return $this->getRequest()->getParam('back') === 'edit'
                ? $redirect->setPath('*/*/edit', ['banner_id' => $banner->getBannerId()])
                : $redirect->setPath('*/*/');
        } catch (ValidationException $exception) {
            foreach ($exception->getErrors() as $error) {
                $this->messageManager->addErrorMessage($error->getMessage());
            }
            if ($exception->getErrors() === []) {
                $this->messageManager->addErrorMessage($exception->getMessage());
            }
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage(
                $exception,
                __('Something went wrong while saving the banner.')
            );
        }

        return $this->backToForm($post);
    }

    /**
     * Keep the post for the form and return to it
     *
     * @param array<mixed> $post
     * @return ResultInterface
     */
    private function backToForm(array $post): ResultInterface
    {
        if ($this->persistablePost->hasEncodedImages($post)) {
            $this->messageManager->addNoticeMessage(
                __('The crop images prepared in the browser were not kept. They are prepared again when you save.')
            );
        }
        $bannerId = $this->idReader->parse($post[BannerInterface::BANNER_ID] ?? null);
        $this->refusedPosts->keep(self::PERSISTOR_KEY, $bannerId, $this->persistablePost->reduce($post));
        if ($bannerId !== null) {
            return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['banner_id' => $bannerId]);
        }
        $sliderId = $this->idReader->parse($post[BannerInterface::SLIDER_ID] ?? null);

        return $this->resultRedirectFactory->create()->setPath(
            '*/*/new',
            $sliderId === null ? [] : ['slider_id' => $sliderId]
        );
    }
}
