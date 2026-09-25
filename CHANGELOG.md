# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2026-09-25

Requires `hryvinskyi/magento2-banner-slider-api` 2.1 and `hryvinskyi/magento2-banner-slider` 2.1.

### Added
- Slider form: "Show Pause/Play Button" in Slider Options, right after Autoplay and shown only while Autoplay is on;
  checked by default. Saved through the options mapper (`show_autoplay_toggle`); a post without the field leaves
  the stored setting unchanged.

## [2.0.0] - 2026-09-25

A breaking release on `hryvinskyi/magento2-banner-slider-api` 2.0 and `hryvinskyi/magento2-banner-slider` 2.0.

### Added
- `etc/acl.xml` (moved from the core module): the same `Hryvinskyi_BannerSlider::` resource ids as 1.x, plus
  `Hryvinskyi_BannerSlider::config` under Stores > Configuration. Buttons, row actions and mass actions are hidden
  from admins without the matching resource; the in-page menu shows only permitted items.
- Configuration section `hryvinskyi_banner_slider`: default extra crop formats, WebP/AVIF quality, JPEG quality of server-cut crops, image and video
  upload limits, privacy-enhanced video embeds, and the daily unused-media clean-up switch.
- Slider form: "Slides per Page" rules (min width, slides, gap), "All Store Views", date and time in the store's
  time zone (stored in UTC).
- `banner_slider/breakpoint/imageUpload`: crop source image upload through the core image upload service.
- Form mapping extension point: `Api/Form/BannerFormMapperInterface` and `Api/Form/SliderFormMapperInterface`
  pools, with `Api/Form/PostData` and `Api/Form/FieldErrors`.
- `i18n/en_US.csv`, unit tests, and node tests of the crop editor's modules (`Test/Js/run.mjs`).
- Crop editor: "Show this crop on the storefront", "Delete Crop", a changed-crop marker on the tabs, file sizes and
  savings in the preview comparison.
- Validation rules `validate-hbs-link-url`, `validate-hbs-identifier`, `validate-hbs-aspect-ratio`,
  `validate-hbs-location` and `validate-hbs-css-length`, matching the server rules.
- `view/adminhtml/web/js/lib/THIRD_PARTY.md` (bundled libraries, versions, licences) and `etc/config.xml`, which
  excludes the bundled codecs from JavaScript minification.

### Changed
- Banners are saved only through the banner editor and sliders only through the slider editor, each in one
  transaction; breakpoints are the complete posted set (hidden `breakpoints_submitted` marker), and a post without
  the marker leaves them unchanged.
- Every invalid field is reported at once, with a translated message naming it, instead of "Something went wrong".
- A failed save brings the form of the same banner or slider back with the entered values (crop areas included;
  browser-prepared crop images are dropped and prepared again). Emptied lists stay empty, a removed image stays
  removed, and the crop editor shows the breakpoints of the slider that was submitted.
- Mass delete in the banner grid of one slider deletes only that slider's banners, "select all" included.
- The banner image and video fields offer the Upload button only; a file without a stored path is refused with a
  field error instead of removing the image and its crops.
- A new banner starts with no slider chosen ("-- Please Select --"); the slider is required.
- The crop editor's requests tell an ended admin session apart and ask the admin to sign in again.
- Uploaded image and video paths are accepted only when they are the stored value or a file of the upload folder.
- Browser-prepared crop images are decoded strictly, capped by the image upload limit, and their file names and
  extensions never come from the request.
- Upload endpoints answer the service's message for refused files and a generic message for anything else.
- Mass delete resolves the selection in one query; the edit pages redirect with a message for a deleted entity.
- Option sources come from the core module; listings use the core grid collections; the customer group column
  renders plain text.
- Requires PHP 8.3 or 8.4, Magento 2.4.7+, `hryvinskyi/magento2-base` ^2.2.
- The crop editor is split into small modules: pure logic under `js/cropper/` (geometry, payload and size budget,
  change tracking, format support, encoding, submission, editor state, file sizes), and a Knockout component that
  wires them to the page.
- Crops are posted with the banner form in the new `responsive_crops` format; only changed crops are sent. The
  request stays under 80 % of `post_max_size` by leaving the largest images to the server.
- The fallback crop image is JPEG for a JPEG source and PNG for every other source (transparent PNGs no longer turn
  black), the same rule the server applies; a variant in the fallback format is not encoded twice.
- Default formats and qualities, and which formats the server can encode, come from the configuration; formats only
  the server can encode are marked "Generated on save".
- Every failed request or encoding is shown to the admin as plain text; a failed encoding no longer blocks the save.
- Changing the banner's slider loads its breakpoints from the breakpoints endpoint URL in the editor config.
- The custom video aspect ratio field is shown and required only for the "Custom" choice and is validated once, by
  the same `W:H` rule as the server. The video uploader accepts MP4 (also as `.m4v`) and WebM only.
- The status column accepts `1`, `'1'` and `true`.
- Every visible string of the templates is translatable; a breakpoint without a target height shows "× auto".

### Removed
- EntityManager extension handlers and the `responsive_crops_data` extension attribute; the core module persists
  crops.
- Endpoints `responsivecrop/save`, `responsivecrop/generate`, `responsivecrop/uploadCompressed`,
  `responsivecrop/upload`, `responsivecrop/uploadBreakpointImage`.
- Events `hryvinskyi_slider_data_object_populate_before` / `_after`: add a slider form mapper instead.
- JavaScript: `js/service/crop-ajax-service.js`, `cropper-manager.js`, `file-utils.js`, `image-compressor.js`,
  `js/config/responsive-cropper-config.js`, the unused crop editor functions and the global RequireJS aliases
  (`cropperjs`, `imageCompressor`, `cropperConfig`, `cropAjaxService`, `cropperManager`, `fileUtils`,
  `responsiveCropper`); modules are required by their full path. The crop auto-save is gone: crops are saved with
  the banner.
- `Api/DataProvider/*`, `Api/ResponsiveCropsSaverInterface`, `Model/DataProvider/*`, `Model/Source/*`,
  `Ui/Listing/DataProvider/*` and the dependency on `hryvinskyi/magento2-media-uploader` and `Magento_Cms`.

## [1.0.6] - 2026-02-05

### Fix
- Fix PHP8.1 compatibility

## [1.0.5] - 2026-02-02

### Added
- Custom CSS field for sliders using CodeMirror editor in new "Custom Styles" fieldset

### Fixed
- Unnecessary image generation on banner save when no crop settings changed
  - Added `hasBreakpointsNeedingGeneration()` to detect if regeneration is required
  - Added `generateChangedImagesForFormSubmit()` to only process changed breakpoints
  - Save now skips image generation entirely when no changes detected
  - Fixes 3-4 second delay when saving banners without opening Image Settings tab
- Images being deleted on save when no new images provided
  - `ResponsiveCropsSaver::saveImages()` now skips processing if no base64 images in request
  - Existing images are preserved when saving without regenerating
- Breakpoint images not updating when desktop image changed
  - Added `resetBreakpointsUsingDesktopImage()` to clear crop data for non-custom breakpoints
  - When desktop image changes, all breakpoints using it are reset and will regenerate on save
  - Also handles image deletion scenario (reset on delete, not just replace)
- Cropper not initializing after delete and re-upload of desktop image
  - Changed cropper-area from `visible` to `if` binding in template
  - Forces DOM element recreation ensuring `load` event fires properly

## [1.0.4] - 2026-02-02

### Added
- Extensible admin menu component integration from `Hryvinskyi_Base` module
  - Shared menu layout handle `banner_slider_menu` for consistent navigation
  - Menu items: Sliders, Banners, Configuration
  - Page-specific titles with current item highlighting
  - Menu configured via layout XML for easy customization

### Changed
- Added `Hryvinskyi_Base` module dependency in `etc/module.xml`
- Updated all admin layout files to use the new menu system:
  - `banner_slider_slider_index.xml` - Sliders listing
  - `banner_slider_slider_edit.xml` - Edit slider form
  - `banner_slider_slider_new.xml` - New slider form
  - `banner_slider_banner_index.xml` - Banners listing
  - `banner_slider_banner_edit.xml` - Edit banner form
  - `banner_slider_banner_new.xml` - New banner form

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
