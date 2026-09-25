<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\Listing\Column;

use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Customer groups of each slider row as text: "All Customer Groups", the group names, or "None".
 *
 * The rows carry the `all_customer_groups` flag and the `customer_group_ids` list; group names are read once per
 * page through the customer group repository.
 */
class CustomerGroup extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array<mixed> $components
     * @param array<mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Replace each row's group ids with their names
     *
     * @param array<mixed> $dataSource
     * @return array<mixed>
     * @throws LocalizedException When the customer groups cannot be read
     */
    public function prepareDataSource(array $dataSource): array
    {
        $data = $dataSource['data'] ?? null;
        $items = is_array($data) ? $data['items'] ?? null : null;
        $fieldName = $this->getData('name');
        if (!is_array($data) || !is_array($items) || !is_string($fieldName)) {
            return $dataSource;
        }

        $names = $this->groupNames();
        foreach ($items as $index => $item) {
            if (is_array($item)) {
                $item[$fieldName] = $this->label($item, $names);
                $items[$index] = $item;
            }
        }
        $data['items'] = $items;
        $dataSource['data'] = $data;

        return $dataSource;
    }

    /**
     * The text of one row
     *
     * @param array<mixed> $item
     * @param array<int,string> $names
     * @return string
     */
    private function label(array $item, array $names): string
    {
        $flag = $item[SliderInterface::ALL_CUSTOMER_GROUPS] ?? null;
        if ($flag === true || $flag === 1 || $flag === '1') {
            return (string)__('All Customer Groups');
        }

        $labels = [];
        $ids = $item[SliderInterface::CUSTOMER_GROUP_IDS] ?? [];
        foreach (is_array($ids) ? $ids : [] as $groupId) {
            if (is_numeric($groupId) && isset($names[(int)$groupId])) {
                $labels[] = $names[(int)$groupId];
            }
        }

        return $labels === [] ? (string)__('None') : implode(', ', $labels);
    }

    /**
     * Customer group names by id
     *
     * @return array<int, string>
     * @throws LocalizedException
     */
    private function groupNames(): array
    {
        $names = [];
        foreach ($this->groupRepository->getList($this->searchCriteriaBuilder->create())->getItems() as $group) {
            $groupId = $group->getId();
            if ($groupId !== null) {
                $names[(int)$groupId] = (string)$group->getCode();
            }
        }

        return $names;
    }
}
