<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Slider;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\RefusedPostStore;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\SliderFormHydrator;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\AbstractEntityFormDataProvider;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterfaceFactory;
use Hryvinskyi\BannerSliderApi\Api\SliderRepositoryInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

/**
 * Slider form data: the stored slider exported by the slider form hydrator; a new slider starts from the field
 * defaults of the form. A refused save of the same slider is shown again through the hydrator.
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
     * @param SliderRepositoryInterface $sliderRepository
     * @param SliderInterfaceFactory $sliderFactory
     * @param SliderFormHydrator $hydrator
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
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly SliderInterfaceFactory $sliderFactory,
        private readonly SliderFormHydrator $hydrator,
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
            'hryvinskyi_banner_slider_slider',
            $meta,
            $data
        );
    }

    /**
     * @inheritDoc
     */
    protected function record(int $entityId, ?PostData $refusedPost): array
    {
        $slider = $this->sliderRepository->getById($entityId);
        $values = $refusedPost === null
            ? $this->hydrator->export($slider)
            : $this->hydrator->restore($refusedPost, $slider);

        return array_replace($values, [SliderInterface::SLIDER_ID => (string)$entityId]);
    }

    /**
     * @inheritDoc
     */
    protected function newRecord(?PostData $refusedPost): array
    {
        return $refusedPost === null ? [] : $this->hydrator->restore($refusedPost, $this->sliderFactory->create());
    }
}
