<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form;

use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Keeps the post of a refused form save in the admin session until the form is shown again.
 *
 * The post is kept together with the id of the entity it was meant for (null for a new one), one post per form. It
 * is handed back only to the form of that same entity, and only once: showing the form of any entity clears it, so a
 * post kept for one banner never fills the form of another.
 */
class RefusedPostStore
{
    private const ENTITY_ID = 'entity_id';
    private const POST = 'post';

    /**
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        private readonly DataPersistorInterface $dataPersistor
    ) {
    }

    /**
     * Keep a refused post of a form
     *
     * @param string $form Session key of the form
     * @param int|null $entityId The entity the post was meant for; null for a new one
     * @param array<mixed> $post
     * @return void
     */
    public function keep(string $form, ?int $entityId, array $post): void
    {
        $this->dataPersistor->set($form, [self::ENTITY_ID => $entityId, self::POST => $post]);
    }

    /**
     * Take the kept post of a form, if it was meant for the given entity; the kept post is cleared either way
     *
     * @param string $form Session key of the form
     * @param int|null $entityId The entity the form shows; null for a new one
     * @return array<mixed>|null
     */
    public function take(string $form, ?int $entityId): ?array
    {
        $kept = $this->dataPersistor->get($form);
        if ($kept === null) {
            return null;
        }
        $this->dataPersistor->clear($form);

        if (!is_array($kept) || !array_key_exists(self::ENTITY_ID, $kept) || $kept[self::ENTITY_ID] !== $entityId) {
            return null;
        }
        $post = $kept[self::POST] ?? null;

        return is_array($post) && $post !== [] ? $post : null;
    }

    /**
     * Drop the kept post of a form
     *
     * @param string $form Session key of the form
     * @return void
     */
    public function forget(string $form): void
    {
        $this->dataPersistor->clear($form);
    }
}
