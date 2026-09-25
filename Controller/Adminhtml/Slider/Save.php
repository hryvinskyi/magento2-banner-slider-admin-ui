<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Slider;

use Hryvinskyi\BannerSliderAdminUi\Model\Form\RefusedPostStore;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\SliderFormSubmission;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validation\ValidationException;

/**
 * Saves the slider form with its breakpoints, then returns to the grid or, on "Save and Continue" or any error, to
 * the form. On error the post is kept for the form of the same slider to show again.
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::slider_save';
    public const PERSISTOR_KEY = 'hryvinskyi_banner_slider_slider';

    /**
     * @param Context $context
     * @param SliderFormSubmission $submission
     * @param RefusedPostStore $refusedPosts
     * @param EntityIdReader $idReader
     */
    public function __construct(
        Context $context,
        private readonly SliderFormSubmission $submission,
        private readonly RefusedPostStore $refusedPosts,
        private readonly EntityIdReader $idReader
    ) {
        parent::__construct($context);
    }

    /**
     * Save the posted slider
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
            $slider = $this->submission->submit($post);
            $this->refusedPosts->forget(self::PERSISTOR_KEY);
            $this->messageManager->addSuccessMessage(__('The slider has been saved.'));

            return $this->getRequest()->getParam('back') === 'edit'
                ? $redirect->setPath('*/*/edit', ['slider_id' => $slider->getSliderId()])
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
                __('Something went wrong while saving the slider.')
            );
        }

        $sliderId = $this->idReader->parse($post[SliderInterface::SLIDER_ID] ?? null);
        $this->refusedPosts->keep(self::PERSISTOR_KEY, $sliderId, $post);

        return $sliderId === null
            ? $redirect->setPath('*/*/new')
            : $redirect->setPath('*/*/edit', ['slider_id' => $sliderId]);
    }
}
