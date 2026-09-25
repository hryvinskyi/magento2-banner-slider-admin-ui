<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\RefusedPostStore;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Magento\Framework\Api\Filter;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

/**
 * Form data of one entity, read through its repository instead of a collection.
 *
 * The form asks for its record by adding a filter on the primary field; that id is kept and the entity is loaded
 * and exported once. When a save of this same entity was refused, its kept post is applied to the entity first (see
 * the form hydrators' `restore()`), so the admin sees what they submitted. The record then passes through the
 * modifier pool, which therefore builds on the submitted values (for a banner, the breakpoints of the submitted
 * slider). The record is keyed by the requested id (an empty string for a new entity), which is how the form looks
 * it up.
 */
abstract class AbstractEntityFormDataProvider extends AbstractDataProvider
{
    /**
     * @var int|null
     */
    private ?int $requestedId = null;

    /**
     * @var array<int|string, array<mixed>>|null
     */
    private ?array $loadedData = null;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param RefusedPostStore $refusedPosts
     * @param PoolInterface $pool
     * @param EntityIdReader $idReader
     * @param string $persistorKey Session key a refused save keeps its post under
     * @param array<mixed> $meta
     * @param array<mixed> $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        private readonly RefusedPostStore $refusedPosts,
        private readonly PoolInterface $pool,
        protected readonly EntityIdReader $idReader,
        private readonly string $persistorKey,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Keep the requested id; this provider has no collection to filter
     *
     * @param Filter $filter
     * @return void
     */
    public function addFilter(Filter $filter): void
    {
        if ($filter->getField() === $this->getPrimaryFieldName()) {
            $this->requestedId = $this->idReader->parse($filter->getValue());
            $this->loadedData = null;
        }
    }

    /**
     * The requested record, keyed by its id (an empty string for a new entity)
     *
     * @return array<int|string, array<mixed>>
     */
    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $key = $this->requestedId ?? '';
        $refused = $this->refusedPosts->take($this->persistorKey, $this->requestedId);
        $refusedPost = $refused === null ? null : new PostData($refused);
        try {
            $data = [
                $key => $this->requestedId === null
                    ? $this->newRecord($refusedPost)
                    : $this->record($this->requestedId, $refusedPost),
            ];
        } catch (NoSuchEntityException) {
            $data = [];
        }

        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $data = $modifier->modifyData($data);
        }

        $this->loadedData = $this->typed($data);

        return $this->loadedData;
    }

    /**
     * Form meta, passed through the modifier pool
     *
     * @return array<mixed>
     */
    public function getMeta(): array
    {
        $meta = parent::getMeta();
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }

    /**
     * The form values of a stored entity
     *
     * @param int $entityId
     * @param PostData|null $refusedPost The refused post of this entity to show again, if any
     * @return array<mixed>
     * @throws NoSuchEntityException When the entity does not exist
     */
    abstract protected function record(int $entityId, ?PostData $refusedPost): array;

    /**
     * The form values of a new entity
     *
     * @param PostData|null $refusedPost The refused post of a new entity to show again, if any
     * @return array<mixed>
     */
    abstract protected function newRecord(?PostData $refusedPost): array;

    /**
     * Keep only array records, as the form expects
     *
     * @param array<mixed> $data
     * @return array<int|string, array<mixed>>
     */
    private function typed(array $data): array
    {
        $records = [];
        foreach ($data as $key => $record) {
            if (is_array($record)) {
                $records[$key] = $record;
            }
        }

        return $records;
    }
}
