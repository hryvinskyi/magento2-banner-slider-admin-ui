<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Slider;

use Hryvinskyi\BannerSliderAdminUi\Api\DataProvider\PrepareDataProcessorInterface;
use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BreakpointInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BreakpointInterfaceFactory;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterfaceFactory;
use Hryvinskyi\BannerSliderApi\Api\SliderRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\DataObject;
use Magento\Framework\EntityManager\EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\Filter\DateTime;

/**
 * Save slider controller
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::slider_save';

    /**
     * @param Context $context
     * @param SliderRepositoryInterface $sliderRepository
     * @param SliderInterfaceFactory $sliderFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DateTime $dateFilter
     * @param EventManager $eventManager
     * @param BreakpointRepositoryInterface $breakpointRepository
     * @param BreakpointInterfaceFactory $breakpointFactory
     * @param PrepareDataProcessorInterface $prepareDataProcessor
     */
    public function __construct(
        Context $context,
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly SliderInterfaceFactory $sliderFactory,
        private readonly DataObjectHelper $dataObjectHelper,
        private readonly DateTime $dateFilter,
        private readonly EventManager $eventManager,
        private readonly BreakpointRepositoryInterface $breakpointRepository,
        private readonly BreakpointInterfaceFactory $breakpointFactory,
        private readonly PrepareDataProcessorInterface $prepareDataProcessor
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

        $sliderId = isset($data['slider_id']) ? (int)$data['slider_id'] : null;

        try {
            if ($sliderId) {
                $slider = $this->sliderRepository->getById($sliderId);
            } else {
                $slider = $this->sliderFactory->create();
            }

            $breakpointsData = $data['breakpoints']['breakpoints_container'] ?? [];
            unset($data['breakpoints']);

            $this->prepareDataProcessor->execute($data);
            $this->populateSlider($slider, $data);
            $this->sliderRepository->save($slider);

            $this->saveBreakpoints((int)$slider->getSliderId(), $breakpointsData);

            $this->messageManager->addSuccessMessage(__('Slider has been saved.'));

            if ($this->getRequest()->getParam('back') === 'edit') {
                return $resultRedirect->setPath('*/*/edit', ['slider_id' => $slider->getSliderId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the slider.'));
        }

        return $resultRedirect->setPath('*/*/edit', ['slider_id' => $sliderId]);
    }

    /**
     * Populate slider with form data
     *
     * @param SliderInterface $slider
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    private function populateSlider(SliderInterface $slider, array $data): void
    {
        $dataObject = new DataObject($data);

        $this->eventManager->dispatch(
            'hryvinskyi_slider_data_object_populate_before',
            ['object' => $slider, 'data' => $dataObject]
        );

        $data = $dataObject->getData();

        if (isset($data[SliderInterface::STORE_IDS]) && is_array($data[SliderInterface::STORE_IDS])) {
            $data[SliderInterface::STORE_IDS] = implode(',', $data[SliderInterface::STORE_IDS]);
        }

        if (isset($data[SliderInterface::CUSTOMER_GROUP_IDS]) &&
            is_array($data[SliderInterface::CUSTOMER_GROUP_IDS])) {
            $data[SliderInterface::CUSTOMER_GROUP_IDS] = implode(',', $data[SliderInterface::CUSTOMER_GROUP_IDS]);
        }

        if (isset($data[SliderInterface::FROM_DATE])) {
            $data[SliderInterface::FROM_DATE] = $data[SliderInterface::FROM_DATE] !== ''
                ? $this->dateFilter->filter($data[SliderInterface::FROM_DATE])
                : null;
        }

        if (isset($data[SliderInterface::TO_DATE])) {
            $data[SliderInterface::TO_DATE] = $data[SliderInterface::TO_DATE] !== ''
                ? $this->dateFilter->filter($data[SliderInterface::TO_DATE])
                : null;
        }

        $dataObject = new DataObject($data);

        $this->eventManager->dispatch(
            'hryvinskyi_slider_data_object_populate_after',
            ['object' => $slider, 'data' => $dataObject]
        );

        $data = $dataObject->getData();


        $this->dataObjectHelper->populateWithArray($slider, $data, SliderInterface::class);
    }

    /**
     * Save breakpoints for slider
     *
     * @param int $sliderId
     * @param array<int, array<string, mixed>> $breakpointsData
     * @return void
     */
    private function saveBreakpoints(int $sliderId, array $breakpointsData): void
    {
        $existingBreakpoints = $this->breakpointRepository->getBySliderId($sliderId);
        $existingIds = [];

        foreach ($existingBreakpoints as $breakpoint) {
            $existingIds[$breakpoint->getBreakpointId()] = $breakpoint;
        }

        $submittedIds = [];

        foreach ($breakpointsData as $breakpointData) {
            $breakpointId = isset($breakpointData['breakpoint_id']) && $breakpointData['breakpoint_id'] !== ''
                ? (int)$breakpointData['breakpoint_id']
                : null;

            if ($breakpointId && isset($existingIds[$breakpointId])) {
                $breakpoint = $existingIds[$breakpointId];
                $submittedIds[] = $breakpointId;
            } else {
                $breakpoint = $this->breakpointFactory->create();
            }

            $breakpointData['slider_id'] = $sliderId;
            $breakpoint->addData($this->prepareBreakpointData($breakpointData));
            $this->breakpointRepository->save($breakpoint);
            $submittedIds[] = $breakpoint->getBreakpointId();
        }

        foreach ($existingIds as $id => $breakpoint) {
            if (!in_array($id, $submittedIds, true)) {
                $this->breakpointRepository->delete($breakpoint);
            }
        }
    }

    /**
     * Prepare breakpoint data with proper type casting
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function prepareBreakpointData(array $data): array
    {
        $intFields = [
            BreakpointInterface::BREAKPOINT_ID,
            BreakpointInterface::SLIDER_ID,
            BreakpointInterface::MIN_WIDTH,
            BreakpointInterface::TARGET_WIDTH,
            BreakpointInterface::TARGET_HEIGHT,
            BreakpointInterface::SORT_ORDER,
            BreakpointInterface::STATUS,
        ];

        foreach ($intFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $data[$field] = (int)$data[$field];
            } elseif (array_key_exists($field, $data)) {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
