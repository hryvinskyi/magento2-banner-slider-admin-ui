<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Request;

use Magento\Framework\App\RequestInterface;

/**
 * Reads an entity id request parameter: a whole number greater than 0, or null for anything else.
 */
class EntityIdReader
{
    /**
     * The id in the parameter, or null when it is missing or not a positive whole number
     *
     * @param RequestInterface $request
     * @param string $name
     * @return int|null
     */
    public function read(RequestInterface $request, string $name): ?int
    {
        return $this->parse($request->getParam($name));
    }

    /**
     * The id in a raw value, or null when it is not a positive whole number
     *
     * @param mixed $value
     * @return int|null
     */
    public function parse(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }
        if (!is_string($value) || preg_match('/^\d{1,18}$/', trim($value)) !== 1) {
            return null;
        }
        $id = (int)$value;

        return $id > 0 ? $id : null;
    }
}
