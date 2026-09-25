<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form\Slider;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\SliderFormMapperInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\Visibility;
use Magento\Customer\Api\Data\GroupInterface;

/**
 * Store views and customer groups of a slider (`store_ids`, `customer_group_ids` multiselects).
 *
 * The customer group options include "ALL GROUPS" (the framework's all-groups id). Selecting it means every group,
 * whatever else is selected, and a slider for every group shows that option selected. A restricted slider shows its
 * groups; one with no group and no store shows nothing selected, so the required fields make the admin choose.
 */
class VisibilityMapper implements SliderFormMapperInterface
{
    public const ALL_GROUPS_OPTION = GroupInterface::CUST_GROUP_ALL;

    /**
     * @inheritDoc
     */
    public function hydrate(PostData $post, SliderInterface $slider, FieldErrors $errors): void
    {
        if (!$post->has(SliderInterface::STORE_IDS) && !$post->has(SliderInterface::CUSTOMER_GROUP_IDS)) {
            return;
        }

        $current = $slider->getVisibility();
        $storeIds = $current->getStoreIds();
        $groupIds = $current->getCustomerGroupIds();
        $allGroups = $current->isForAllCustomerGroups();
        $valid = true;

        if ($post->has(SliderInterface::STORE_IDS)) {
            $posted = $this->ids($post->values(SliderInterface::STORE_IDS));
            $valid = $posted !== null;
            $storeIds = $posted ?? $storeIds;
            if ($posted === null) {
                $errors->add(__('Select valid store views.'), 'Store view ids must be whole numbers.');
            }
        }
        if ($post->has(SliderInterface::CUSTOMER_GROUP_IDS)) {
            $posted = $this->ids($post->values(SliderInterface::CUSTOMER_GROUP_IDS));
            if ($posted === null) {
                $valid = false;
                $errors->add(__('Select valid customer groups.'), 'Customer group ids must be whole numbers.');
            } else {
                $allGroups = in_array(self::ALL_GROUPS_OPTION, $posted, true);
                $groupIds = $allGroups ? [] : $posted;
            }
        }
        if (!$valid) {
            return;
        }

        $errors->attempt(
            __('Select valid store views and customer groups.'),
            static fn () => $slider->setVisibility(new Visibility($storeIds, $groupIds, $allGroups))
        );
    }

    /**
     * @inheritDoc
     */
    public function export(SliderInterface $slider): array
    {
        $visibility = $slider->getVisibility();
        $groupIds = $visibility->isForAllCustomerGroups()
            ? [self::ALL_GROUPS_OPTION]
            : $visibility->getCustomerGroupIds();

        return [
            SliderInterface::STORE_IDS => array_map('strval', $visibility->getStoreIds()),
            SliderInterface::CUSTOMER_GROUP_IDS => array_map('strval', $groupIds),
        ];
    }

    /**
     * Posted ids as integers; null when one is not a whole number of 0 or more
     *
     * @param list<mixed> $values
     * @return list<int>|null
     */
    private function ids(array $values): ?array
    {
        $ids = [];
        foreach ($values as $value) {
            if (is_int($value) && $value >= 0) {
                $ids[] = $value;
                continue;
            }
            if (!is_string($value) || preg_match('/^\d{1,10}$/', trim($value)) !== 1) {
                return null;
            }
            $ids[] = (int)$value;
        }

        return $ids;
    }
}
