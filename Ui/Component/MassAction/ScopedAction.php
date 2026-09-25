<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\Component\MassAction;

/**
 * A mass action of a listing that can be opened for part of its rows, which posts that scope with the selection.
 *
 * A request parameter such as `slider_id` narrows the rows a listing shows, but a mass action posts only the
 * selection and the grid filters, so "select all" would reach every row of the table. Each parameter named in the
 * action's `scopeParams` data item is therefore added to the action URL with the value the listing was opened with,
 * or an empty value when it was opened without one. The controller can then tell "the whole listing" (empty) from a
 * page that did not send its scope (missing), and refuse the latter instead of widening the selection.
 */
class ScopedAction extends AuthorizedAction
{
    /**
     * Add the listing's scope parameters to the action URL
     *
     * @return void
     */
    public function prepare(): void
    {
        parent::prepare();

        $config = $this->getConfiguration();
        $url = $config['url'] ?? null;
        $scope = $this->scope();
        if (!is_string($url) || $url === '' || $scope === []) {
            return;
        }

        $config['url'] = $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($scope);
        $this->setData('config', $config);
    }

    /**
     * The scope parameters with the values of the current request; a parameter that is not plain text is left out
     *
     * @return array<string, string>
     */
    private function scope(): array
    {
        $names = $this->getData('scopeParams');
        $scope = [];
        foreach (is_array($names) ? $names : [] as $name) {
            if (!is_string($name) || $name === '') {
                continue;
            }
            $value = $this->getContext()->getRequestParam($name);
            if ($value === null || is_scalar($value)) {
                $scope[$name] = (string)$value;
            }
        }

        return $scope;
    }
}
