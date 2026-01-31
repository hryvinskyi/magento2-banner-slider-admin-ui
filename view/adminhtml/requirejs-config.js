/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

var config = {
    map: {
        '*': {
            'cropperjs': 'Hryvinskyi_BannerSliderAdminUi/js/lib/cropper.min',
            'imageCompressor': 'Hryvinskyi_BannerSliderAdminUi/js/service/image-compressor',
            'cropperConfig': 'Hryvinskyi_BannerSliderAdminUi/js/config/responsive-cropper-config',
            'cropAjaxService': 'Hryvinskyi_BannerSliderAdminUi/js/service/crop-ajax-service',
            'cropperManager': 'Hryvinskyi_BannerSliderAdminUi/js/service/cropper-manager',
            'fileUtils': 'Hryvinskyi_BannerSliderAdminUi/js/service/file-utils'
        }
    },
    shim: {
        'cropperjs': {
            exports: 'Cropper'
        }
    },
    paths: {
        'responsiveCropper': 'Hryvinskyi_BannerSliderAdminUi/js/form/components/responsive-cropper'
    }
};
