# Magento 2 & Adobe Commerce Banner Slider Admin UI

Admin panel interface for managing Banner Sliders.

> **Part of [hryvinskyi/magento2-banner-slider-pack](https://github.com/hryvinskyi/magento2-banner-slider-pack)** - Complete Banner Slider solution for Magento 2

## Description

This module provides the admin panel interface for creating and managing banner sliders. It includes CRUD interfaces for sliders and banners, image and video upload handling, and a responsive cropper tool for creating optimized images across different breakpoints.

## Features

- Complete admin CRUD interface for sliders and banners
- Responsive image cropper with breakpoint support
- Client-side WebP/AVIF compression using jsquash library
- Drag-and-drop image and video uploads
- Before/after image comparison preview
- UI component-based forms and listings

## Admin Menu

Navigate to **Content > Banner Slider** to access:

| Menu Item | Action |
|-----------|--------|
| Sliders | Manage slider configurations |
| Banners | Manage banner content |

## Controllers

### Slider Operations
- `Index` - List all sliders
- `New` - Create new slider
- `Edit` - Edit existing slider
- `Save` - Save slider data

### Banner Operations
- `Index` - List all banners
- `New` - Create new banner
- `Edit` - Edit existing banner
- `Save` - Save banner data
- `Delete` - Delete banner

### Upload Controllers
- `Image/Upload` - Handle image file uploads
- `Video/Upload` - Handle video file uploads

### Responsive Crop Operations
- `ResponsiveCrop/Upload` - Upload source image for cropping
- `ResponsiveCrop/UploadBreakpointImage` - Upload image for specific breakpoint
- `ResponsiveCrop/UploadCompressed` - Upload pre-compressed images (WebP/AVIF)
- `ResponsiveCrop/Generate` - Generate responsive crops from source
- `ResponsiveCrop/Save` - Save crop configuration
- `ResponsiveCrop/Breakpoints` - Get breakpoints for slider

## UI Components

### Forms
- `hryvinskyi_banner_slider_slider_form.xml` - Slider edit form
- `hryvinskyi_banner_slider_banner_form.xml` - Banner edit form with responsive cropper

### Listings
- `hryvinskyi_banner_slider_slider_listing.xml` - Slider grid
- `hryvinskyi_banner_slider_banner_listing.xml` - Banner grid

## Slider Form Fields

| Field | Description |
|-------|-------------|
| Name | Slider identifier |
| Status | Enable/disable slider |
| Location | Widget placement location |
| Priority | Display priority |
| Store Views | Store view assignment |
| Customer Groups | Customer group targeting |
| Effect | Animation effect type |
| Auto Width/Height | Automatic sizing |
| Loop | Infinite loop |
| Lazy Load | Lazy image loading |
| Auto Play | Auto-advance slides |
| Auto Play Timeout | Time between slides |
| Navigation | Show prev/next arrows |
| Pagination | Show dot pagination |
| Responsive | Enable responsive mode |
| Responsive Items | Items per breakpoint |
| From/To Date | Scheduled visibility |

## Banner Form Fields

| Field | Description |
|-------|-------------|
| Slider | Parent slider assignment |
| Name | Banner identifier |
| Status | Enable/disable banner |
| Type | Image, Video, or Custom HTML |
| Image | Desktop image upload |
| Video URL | YouTube/Vimeo URL |
| Video Path | Local video file |
| Video Aspect Ratio | Custom aspect ratio |
| Video as Background | Background mode toggle |
| Custom Content | HTML content editor |
| Link URL | Click-through URL |
| Title | Alt text for images |
| Open in New Tab | Link target |
| Position | Display order |
| From/To Date | Scheduled visibility |
| Responsive Crops | Breakpoint-specific images |

## JavaScript Components

| Component | Description |
|-----------|-------------|
| `responsive-cropper.js` | Interactive cropping tool |
| `image-compressor.js` | Client-side WebP/AVIF compression |
| `cropper-manager.js` | Crop coordinate management |
| `crop-ajax-service.js` | AJAX operations for crops |
| `file-utils.js` | File handling utilities |
| `video-uploader.js` | Video upload form element |

## Bundled Libraries

- **cropper.min.js** - Image cropping library
- **img-comparison-slider** - Before/after comparison
- **jsquash** - WASM-based WebP/AVIF encoding

## Dependencies

- PHP 8.1+
- magento/framework
- magento/module-ui
- magento/module-backend
- hryvinskyi/magento2-banner-slider-api
- hryvinskyi/magento2-banner-slider

## Installation

This module is typically installed as part of the `hryvinskyi/magento2-banner-slider-pack` metapackage:

```bash
composer require hryvinskyi/magento2-banner-slider-pack
php bin/magento module:enable Hryvinskyi_BannerSliderAdminUi
php bin/magento setup:upgrade
php bin/magento cache:flush
```

## Author

**Volodymyr Hryvinskyi**
- Email: volodymyr@hryvinskyi.com

## License

MIT
