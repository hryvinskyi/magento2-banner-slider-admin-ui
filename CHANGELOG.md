# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.3] - 2026-02-01

### Added
- Number input field alongside quality sliders for WebP and AVIF
  - Allows direct value entry in addition to slider control
  - Real-time synchronization between slider and input
- `generatePreviewImages()` function for browser-only preview generation
- `syncQualityInputs()` helper for slider/input synchronization
- Image settings change tracking for smart regeneration
  - `getChangedBreakpointIds()` - returns list of breakpoints with changed settings
  - `generateBreakpointImagesByIds()` - regenerates only specified breakpoints
  - `storeSavedCropsState()` - stores state for comparison

### Changed
- "Generate Images" button renamed to "Generate Preview"
  - Now only visible when "Show Quality Comparison" is enabled
  - Generates preview images in browser only (no server upload)
  - Uses blob URLs for temporary preview display
- Quality slider updates no longer trigger cropper reinitialization
- Form save now only regenerates images for breakpoints with changed settings
  - Tracks changes per breakpoint: crop position, quality, source image
  - Only regenerates affected breakpoints, not all
  - Skips regeneration entirely when only non-image fields change (dates, status, etc.)

### Fixed
- "Use Desktop Image" button now correctly loads desktop image
  - Clears `source_image_url` and `source_image` when reverting to desktop
- Cropper blinking issues on various user actions:
  - Moving `destroyCropper()` call before observable updates
  - Using `silent: true` in save callbacks to prevent unnecessary re-renders
  - Quality changes no longer cause cropper to reinitialize
  - Toggling "Show Quality Comparison" no longer causes cropper blink
  - Added cropper state tracking to skip redundant reinitialization
- Crop box going outside image boundary on right side
  - Renamed custom `.cropper-container` wrapper to `.cropper-wrapper` to avoid CSS conflict with Cropper.js
  - Added validation to clamp restored crop data within image bounds
  - Ensures crop box position and size never exceed image dimensions
- Crop position not saving correctly when using Desktop Image
  - `onCropEnd` callback now captures crop data at the moment user releases mouse
  - Prevents cropper snap-back from overwriting user's intended position

### Removed
- "Save & Generate All Images" button from responsive cropper UI

## [1.0.2] - 2026-01-31

### Added
- Listing data providers with modifier support:
  - `Ui/Listing/DataProvider/Slider` - Slider grid data provider
  - `Ui/Listing/DataProvider/Banner` - Banner grid data provider
- Form data modifiers for slider:
  - `PrepareBreakpoints` - Loads breakpoints data for slider form
  - `PrepareCustomerGroups` - Converts customer group IDs from string to array
  - `PrepareStores` - Converts store IDs from string to array
- Navigation buttons:
  - "Manage Banners" button on banner form (links to banner listing filtered by slider)
  - "Back to Slider" button on banner listing (visible when filtered by slider_id)
  - "Edit Slider" button next to slider dropdown on banner form (opens slider edit in new tab)
- Custom slider select component:
  - `slider-select.js` - Extended select with edit button
  - `slider-select.html` - Template with inline edit button

### Changed
- Refactored `Slider/FormDataProvider` to use `PrepareDataProcessorInterface`
- Removed `BreakpointRepositoryInterface` direct dependency from `Slider/FormDataProvider`
- Updated `GenericButton` to include `getSliderId()` method
- Configured DI for slider and banner listing data processors

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
