<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Controller\Adminhtml;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;

/**
 * A backend action context double that records the messages a controller adds and where it redirects.
 */
trait BackendActionContext
{
    /**
     * Messages added, as "type: text"
     *
     * @var list<string>
     */
    private array $messages = [];

    /**
     * Last redirect path and parameters
     *
     * @var array{0: string, 1: array<mixed>}|null
     */
    private ?array $redirectedTo = null;

    /**
     * @var Redirect
     */
    private Redirect $redirect;

    /**
     * A context whose request answers the given parameters and post
     *
     * @param array<string, mixed> $params
     * @param array<mixed> $post
     * @return Context
     */
    private function actionContext(array $params = [], array $post = []): Context
    {
        $request = $this->createMock(HttpRequest::class);
        $request->method('getParam')->willReturnCallback(
            static fn (string $name, mixed $default = null): mixed => $params[$name] ?? $default
        );
        $request->method('getPostValue')->willReturn($post);

        $messages = $this->createMock(ManagerInterface::class);
        $types = ['addSuccessMessage' => 'success', 'addErrorMessage' => 'error', 'addNoticeMessage' => 'notice'];
        foreach ($types as $method => $type) {
            $messages->method($method)->willReturnCallback(
                function (string|Phrase $message) use ($type, $messages): ManagerInterface {
                    $this->messages[] = $type . ': ' . $message;

                    return $messages;
                }
            );
        }
        $messages->method('addExceptionMessage')->willReturnCallback(
            function (\Exception $exception, string|Phrase|null $alternative = null) use ($messages): ManagerInterface {
                $this->messages[] = 'exception: ' . $alternative;

                return $messages;
            }
        );

        $this->redirect = $this->createMock(Redirect::class);
        $this->redirect->method('setPath')->willReturnCallback(function (string $path, array $params = []): Redirect {
            $this->redirectedTo = [$path, $params];

            return $this->redirect;
        });
        $redirectFactory = $this->createMock(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($this->redirect);

        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getMessageManager')->willReturn($messages);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);

        return $context;
    }
}
