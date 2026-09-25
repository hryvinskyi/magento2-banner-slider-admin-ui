<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Banner;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\BannerFormHydrator;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\RefusedPostStore;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\AbstractEntityFormDataProvider;
use Hryvinskyi\BannerSliderApi\Api\BannerRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterfaceFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

/**
 * Banner form data: the stored banner exported by the banner form hydrator, or, for a new banner, the slider given
 * in the request (the "Add New Banner" button of a slider's banner grid passes it). A refused save of the same banner
 * is shown again through the hydrator.
 */
class FormDataProvider extends AbstractEntityFormDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param RefusedPostStore $refusedPosts
     * @param PoolInterface $pool
     * @param EntityIdReader $idReader
     * @param BannerRepositoryInterface $bannerRepository
     * @param BannerInterfaceFactory $bannerFactory
     * @param BannerFormHydrator $hydrator
     * @param RequestInterface $request
     * @param array<mixed> $meta
     * @param array<mixed> $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        RefusedPostStore $refusedPosts,
        PoolInterface $pool,
        EntityIdReader $idReader,
        private readonly BannerRepositoryInterface $bannerRepository,
        private readonly BannerInterfaceFactory $bannerFactory,
        private readonly BannerFormHydrator $hydrator,
        private readonly RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $refusedPosts,
            $pool,
            $idReader,
            'hryvinskyi_banner_slider_banner',
            $meta,
            $data
        );
    }

    /**
     * @inheritDoc
     */
    protected function record(int $entityId, ?PostData $refusedPost): array
    {
        $banner = $this->bannerRepository->getById($entityId);
        $values = $refusedPost === null
            ? $this->hydrator->export($banner)
            : $this->hydrator->restore($refusedPost, $banner);

        return array_replace($values, [BannerInterface::BANNER_ID => (string)$entityId]);
    }

    /**
     * @inheritDoc
     */
    protected function newRecord(?PostData $refusedPost): array
    {
        if ($refusedPost !== null) {
            return $this->hydrator->restore($refusedPost, $this->bannerFactory->create());
        }
        $sliderId = $this->idReader->read($this->request, BannerInterface::SLIDER_ID);

        return $sliderId === null ? [] : [BannerInterface::SLIDER_ID => (string)$sliderId];
    }
}
