<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\ResponsiveCrop;

use Hryvinskyi\BannerSliderAdminUi\Model\Presenter\BreakpointPresenter;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * The enabled breakpoints of a slider as JSON, for the crop editor when the banner's slider changes:
 * `GET ?slider_id=<id>` answers `{"breakpoints": [...]}` (empty for a missing or unknown slider).
 */
class Breakpoints extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner';

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param BreakpointRepositoryInterface $breakpointRepository
     * @param BreakpointPresenter $presenter
     * @param EntityIdReader $idReader
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly BreakpointRepositoryInterface $breakpointRepository,
        private readonly BreakpointPresenter $presenter,
        private readonly EntityIdReader $idReader
    ) {
        parent::__construct($context);
    }

    /**
     * Answer the slider's breakpoints
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $sliderId = $this->idReader->read($this->getRequest(), 'slider_id');
        $breakpoints = [];
        foreach ($sliderId === null ? [] : $this->breakpointRepository->getBySliderId($sliderId, true) as $breakpoint) {
            $breakpoints[] = $this->presenter->present($breakpoint);
        }

        return $this->jsonFactory->create()->setData(['breakpoints' => $breakpoints]);
    }
}
