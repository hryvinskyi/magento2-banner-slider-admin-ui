<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

namespace Hryvinskyi\BannerSliderAdminUi\Ui\Listing\Column;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CustomerGroup extends Column
{
    /**
     * CustomerGroup constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param GroupCollection $groupCollection
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly GroupCollection $groupCollection,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    #[\Override]
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$this->getData('name')])) {
                    $item[$this->getData('name')] = $this->prepareItem($item);
                }
            }
        }

        return $dataSource;
    }

    /**
     * Get customer group name
     *
     * @param array $item
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function prepareItem(array $item): string
    {
        $content = [];
        $origGroup = $item[$this->getData('name')];
        if (!is_array($origGroup)) {
            $origGroup = explode(',', (string) $origGroup);
        }

        $origGroup = array_intersect($this->groupCollection->getAllIds(), $origGroup);
        foreach ($origGroup as $group) {
            $content[] = $this->groupRepository->getById($group)->getCode();
        }

        if (empty($content) || count($content) === $this->groupCollection->count()) {
            return __('All Customer Groups')->render();
        }

        return implode(', ', $content);
    }
}
