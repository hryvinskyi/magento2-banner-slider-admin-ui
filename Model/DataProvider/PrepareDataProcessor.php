<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\DataProvider;

use Hryvinskyi\BannerSliderAdminUi\Api\DataProvider\PrepareDataInterface;
use Hryvinskyi\BannerSliderAdminUi\Api\DataProvider\PrepareDataProcessorInterface;
use InvalidArgumentException;

/**
 * Composite data processor that executes multiple data transformations in sorted order.
 */
class PrepareDataProcessor implements PrepareDataProcessorInterface
{
    /**
     * @var PrepareDataInterface[]
     */
    private array $sortedItems;

    /**
     * @param array<string, array{object: PrepareDataInterface, sortOrder: string|int}> $items
     */
    public function __construct(
        private readonly array $items = []
    ) {
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function execute(array &$data): void
    {
        foreach ($this->getSortedItems() as $item) {
            $item->execute($data);
        }
    }

    /**
     * Get items sorted by sortOrder.
     *
     * @return PrepareDataInterface[]
     * @throws InvalidArgumentException When item does not implement PrepareDataInterface
     */
    private function getSortedItems(): array
    {
        if (!isset($this->sortedItems)) {
            $this->sortedItems = [];
            $sortedData = [];

            foreach ($this->items as $key => $item) {
                $this->validateItem($item, $key);
                $sortOrder = (int)($item['sortOrder'] ?? 0);
                $sortedData[$key] = [
                    'object' => $item['object'],
                    'sortOrder' => $sortOrder,
                ];
            }

            uasort($sortedData, static function (array $a, array $b): int {
                return $a['sortOrder'] <=> $b['sortOrder'];
            });

            foreach ($sortedData as $item) {
                $this->sortedItems[] = $item['object'];
            }
        }

        return $this->sortedItems;
    }

    /**
     * Validate that item implements PrepareDataInterface.
     *
     * @param array{object: mixed, sortOrder?: string|int} $item
     * @param string $key
     * @return void
     * @throws InvalidArgumentException When item does not implement PrepareDataInterface
     */
    private function validateItem(array $item, string $key): void
    {
        if (!isset($item['object'])) {
            throw new InvalidArgumentException(
                sprintf('Item "%s" must have an "object" key.', $key)
            );
        }

        if (!$item['object'] instanceof PrepareDataInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Item "%s" must implement %s, got %s.',
                    $key,
                    PrepareDataInterface::class,
                    get_class($item['object'])
                )
            );
        }
    }
}
