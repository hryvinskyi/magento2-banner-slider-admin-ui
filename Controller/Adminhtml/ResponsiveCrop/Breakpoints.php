<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\ResponsiveCrop;

use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BreakpointInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Controller to fetch breakpoints for a slider via AJAX
 */
class Breakpoints extends Action
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner';

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param BreakpointRepositoryInterface $breakpointRepository
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly BreakpointRepositoryInterface $breakpointRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        $sliderId = (int)$this->getRequest()->getParam('slider_id');

        if (!$sliderId) {
            return $result->setData([
                'breakpoints' => [],
            ]);
        }

        $breakpoints = $this->breakpointRepository->getBySliderId($sliderId);
        $breakpointsData = [];

        /** @var BreakpointInterface $breakpoint */
        foreach ($breakpoints as $breakpoint) {
            $breakpointsData[] = [
                'breakpoint_id' => $breakpoint->getBreakpointId(),
                'name' => $breakpoint->getName(),
                'identifier' => $breakpoint->getIdentifier(),
                'media_query' => $breakpoint->getMediaQuery(),
                'min_width' => $breakpoint->getMinWidth(),
                'target_width' => $breakpoint->getTargetWidth(),
                'target_height' => $breakpoint->getTargetHeight(),
                'sort_order' => $breakpoint->getSortOrder(),
            ];
        }

        return $result->setData([
            'breakpoints' => $breakpointsData,
        ]);
    }
}
