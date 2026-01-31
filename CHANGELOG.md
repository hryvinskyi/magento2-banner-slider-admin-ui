# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-01-31

### Added
- Custom Aspect Ratio option for video banners
  - New "Custom Aspect Ratio" option in Aspect Ratio dropdown
  - Input field for custom ratio values (e.g., 3:2, 16:10, 2.35:1)
  - Client-side validation for aspect ratio format
  - `PrepareCustomAspectRatio` data processor for form loading
  - `SaveCustomAspectRatio` data processor for saving custom values
  - `aspect-ratio-input.js` UI component with validation

### Changed
- `AspectRatio` source model now includes `CUSTOM` and `PREDEFINED_RATIOS` constants
- `video-uploader.js` now validates aspect ratio format and falls back to 16:9 for invalid values

## [1.0.0] - 2026-01-31

### Added
- Initial release of Banner Slider Admin UI module
- Admin menu integration under Content > Banner Slider
- Slider management:
  - Index controller with grid listing
  - New/Edit controllers for slider forms
  - Save controller with full data persistence
- Banner management:
  - Index controller with grid listing
  - New/Edit controllers for banner forms
  - Save controller with data persistence
  - Delete controller for banner removal
- Image upload functionality:
  - `Image/Upload` controller for handling uploads
  - Validation and storage integration
- Video upload functionality:
  - `Video/Upload` controller for video files
  - Support for MP4 and WebM formats
- Responsive crop system:
  - `ResponsiveCrop/Upload` - Source image upload
  - `ResponsiveCrop/UploadBreakpointImage` - Per-breakpoint uploads
  - `ResponsiveCrop/UploadCompressed` - Pre-compressed file uploads
  - `ResponsiveCrop/Generate` - Automatic crop generation
  - `ResponsiveCrop/Save` - Crop data persistence
  - `ResponsiveCrop/Breakpoints` - Breakpoint retrieval
- UI Components:
  - Slider form (`hryvinskyi_banner_slider_slider_form.xml`)
  - Slider listing (`hryvinskyi_banner_slider_slider_listing.xml`)
  - Banner form (`hryvinskyi_banner_slider_banner_form.xml`)
  - Banner listing (`hryvinskyi_banner_slider_banner_listing.xml`)
- Data providers:
  - `Banner/FormDataProvider` for banner form data
  - `Slider/FormDataProvider` for slider form data
  - `Banner/Modifier/ResponsiveCropperModifier` for cropper UI
- Grid columns:
  - `Thumbnail` column for banner previews
  - `BannerActions` column for banner row actions
  - `SliderActions` column for slider row actions
  - `Status` column with visual indicators
- JavaScript components:
  - `responsive-cropper.js` - Interactive cropping interface
  - `image-compressor.js` - WebP/AVIF client-side compression
  - `cropper-manager.js` - Crop coordinate handling
  - `crop-ajax-service.js` - AJAX service for crop operations
  - `file-utils.js` - File utilities
  - `video-uploader.js` - Video upload element
- Bundled libraries:
  - cropper.min.js for image cropping
  - img-comparison-slider for previews
  - jsquash for WASM-based image encoding
- LESS stylesheets for admin interface
