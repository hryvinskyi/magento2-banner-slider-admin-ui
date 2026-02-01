/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([], function () {
    'use strict';

    return {
        // Quality defaults
        WEBP_QUALITY_DEFAULT: 85,
        AVIF_QUALITY_DEFAULT: 80,

        // File constraints
        MAX_FILE_SIZE: 10 * 1024 * 1024, // 10MB
        ALLOWED_IMAGE_TYPES: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],

        // Cropper settings
        CROPPER_MAX_WIDTH: 1920,
        CROPPER_MAX_HEIGHT: 800,

        // Timing
        AUTO_SAVE_DELAY: 1000,
    };
});
