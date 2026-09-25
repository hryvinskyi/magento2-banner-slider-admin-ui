# Magento 2 & Adobe Commerce Banner Slider Admin UI

Admin panel for the banner slider: slider and banner grids and forms, the responsive crop editor, uploads, ACL,
the admin menu and the configuration section.

> **Part of [hryvinskyi/magento2-banner-slider-pack](https://github.com/hryvinskyi/magento2-banner-slider-pack)** -
> complete Banner Slider solution for Magento 2

## Where it sits

This module only talks to the service contracts of `hryvinskyi/magento2-banner-slider-api`: every save goes through
the slider editor or the banner editor, every read through the repositories. The core module
(`hryvinskyi/magento2-banner-slider`) is named only in XML (option sources, grid collections, the aspect-ratio
presets); no PHP class of this module imports it.

## Admin menu and permissions

**Content > Banner Slider** lists Sliders and Banners; **Stores > Configuration > Hryvinskyi > Banner Slider** holds
the settings. The in-page menu shows only the items the admin may open.

| ACL resource | Grants |
|---|---|
| `Hryvinskyi_BannerSlider::banner_slider` | The Banner Slider menu |
| `Hryvinskyi_BannerSlider::slider` | Slider grid and form |
| `Hryvinskyi_BannerSlider::slider_save` | Add and save sliders |
| `Hryvinskyi_BannerSlider::slider_delete` | Delete sliders (single and mass) |
| `Hryvinskyi_BannerSlider::banner` | Banner grid and form |
| `Hryvinskyi_BannerSlider::banner_save` | Add and save banners, upload images and videos |
| `Hryvinskyi_BannerSlider::banner_delete` | Delete banners (single and mass) |
| `Hryvinskyi_BannerSlider::config` | The configuration section (under Stores > Configuration) |

Buttons, row actions and mass actions an admin may not use are hidden; the controllers check the same resources.
The ids are those of 1.x, so existing role assignments keep working.

## Sliders

- **General:** name, status, location code (letters, digits, `_`, `-`; sliders sharing a location show the one with
  the lowest priority value), priority, store views ("All Store Views" = store 0), customer groups ("ALL GROUPS" =
  every group, including groups created later), and the From / To window.
- **Dates** are entered in the store's time zone and stored in UTC.
- **Slider Options:** effect, autoplay and its timeout (at least 1000 ms), "Show Pause/Play Button" (shown only
  while autoplay is on; on by default), loop, arrows, dots, lazy loading, auto width/height, preload count.
- **Slides per Page:** rules of the form "from this min width, show N slides with this gap" (N from 1 to 10; gap
  empty or a CSS length such as `16px` or `1rem`).
- **Custom Styles:** plain CSS for `.banner-slider-{slider_id}`; `<` is refused.
- **Responsive Breakpoints:** the crop sizes banners get. The identifier (lowercase letters, digits, `_`, `-`, up
  to 50) ends up in crop file names. Removing a breakpoint removes the crops made for it; a new slider saved without
  breakpoints gets the default Desktop, Tablet and Mobile set.

Every invalid field is reported at once with a message naming it; nothing is saved until the whole form is valid.

## Banners

- **General:** name, status, slider, type (Image, Video, Custom HTML), position, From / To window.
- **Image:** the uploaded image is the default source of every responsive crop. Only a file uploaded through the
  image field, or the image already stored on the banner, can be saved; the pixel size is read from the file. The
  image and video fields have no "Select from Gallery": a gallery file has no stored path the banner could keep.
- **Responsive Images:** the crop editor, one crop per enabled breakpoint of the banner's slider, optionally cut
  from its own uploaded image, with extra formats (WebP, AVIF). Images the browser prepares are checked on the
  server; any it cannot prepare are encoded on the server.
- **Video:** a YouTube or Vimeo URL (`http`/`https`), or an uploaded MP4/M4V/WebM file; aspect ratio from the presets
  or a custom `W:H` in whole numbers from 1 to 100; background mode.
- **Link:** an `http:`, `https:`, `mailto:` or `tel:` URL, or a relative one such as `/sale.html`.
- **Custom Content:** trusted admin HTML; the storefront renders it through the template filter, so widgets and
  directives work.

If a save fails, the form of the same banner comes back with what was entered, including a changed slider (the crop
editor then shows that slider's breakpoints) and a removed image. The crop areas are kept; the crop images prepared
in the browser are not (they are too large for the admin session) and are prepared again on the next save. Sliders
work the same way: emptied breakpoint or "Slides per Page" lists stay empty.

In the banner grid of one slider, mass delete deletes only that slider's banners, "select all" included.

## Responsive crop editor

The **Responsive Images** block of the banner form holds one crop per enabled breakpoint of the banner's slider.

- **Tabs:** one per breakpoint, widest first, with its target size (`1920 × 800 px`, or `1920 × auto` when the
  breakpoint keeps the crop's aspect ratio). A dot marks the crops changed since the form was opened. Choosing
  another slider for the banner loads that slider's breakpoints.
- **Crop box:** keeps the breakpoint's aspect ratio (free when the target height is empty) and stays inside the
  image. A crop without an area starts from the largest centred area of that ratio.
- **Source image:** the banner image, or an image of the crop's own (JPEG, PNG, GIF, WebP or AVIF, up to the image
  upload limit). Replacing or removing the banner image restarts every crop cut from it.
- **Formats:** the fallback image is JPEG for a JPEG source and PNG for any other source, so transparency survives.
  The extra formats (WebP, AVIF) start from the configured defaults and qualities. The browser encodes them with the
  bundled WebAssembly codecs; a format the browser cannot encode but the server can is marked "Generated on save".
- **Preview** encodes the current crop in the browser and compares each extra format with the fallback image, with
  file sizes. Without a preview the comparison shows the saved files.
- **Show this crop on the storefront** hides a crop without deleting it; **Delete Crop** removes it on save.

On save, only the crops that changed are sent. The images prepared in the browser travel with the form; the server
checks them and generates whatever is missing. The editor keeps the whole request under 80 % of PHP's
`post_max_size` by leaving the largest images to the server, and drops any image larger than the image upload limit.
Anything that could not be done in the browser (an image that failed to encode or load, a crop without an area) is
listed before the form is sent. An invalid form is not encoded at all; the form shows its errors first.

### Content Security Policy

The codecs are compiled with WebAssembly, which needs `'unsafe-eval'` (or `'wasm-unsafe-eval'`) in `script-src`.
Magento's default admin policy already allows `'unsafe-eval'`, and the admin policy is report-only by default. A
store that switches the admin CSP to restrict mode must keep `'unsafe-eval'` in `script-src`, as Magento's admin
itself requires. The codec files are served from the admin static files (`'self'`); `etc/config.xml` excludes them
from JavaScript minification because they load their sibling files by exact name.

The bundled libraries, their versions and licences are listed in `view/adminhtml/web/js/lib/THIRD_PARTY.md`.

### Form validation rules

`view/adminhtml/web/js/validation/rules.js` registers these rules on the admin form validator (an empty value
passes; `required-entry` decides whether a field may be empty). They match the server's rules:

| Rule | Accepts |
|---|---|
| `validate-hbs-link-url` | `http:`, `https:`, `mailto:`, `tel:` or a relative URL |
| `validate-hbs-identifier` | `^[a-z0-9][a-z0-9_-]{0,49}$` |
| `validate-hbs-aspect-ratio` | `W:H`, whole numbers from 1 to 100 |
| `validate-hbs-location` | `^[A-Za-z0-9_-]{1,255}$` |
| `validate-hbs-css-length` | a number with `px`, `rem`, `em` or `%` |

## Configuration (Stores > Configuration > Hryvinskyi > Banner Slider)

| Group | Field | Default |
|---|---|---|
| Images | Default Extra Formats | WebP |
| Images | WebP Quality / AVIF Quality | 85 / 80 |
| Images | Maximum Image Upload Size (MB) | 10 (also caps each browser-prepared crop image) |
| Videos | Privacy-Enhanced Embeds (store view scope) | Yes |
| Videos | Maximum Video Upload Size (MB) | 100 |
| Media Clean-up | Delete Unused Files Daily | No |

Enable the daily clean-up only after `bin/magento banner-slider:media:cleanup --dry-run` lists no file you need.

## Admin endpoints

| Route | Method | Purpose |
|---|---|---|
| `banner_slider/slider/{index,new,edit,save,delete,massDelete}` | | Slider grid and form |
| `banner_slider/banner/{index,new,edit,save,delete,massDelete}` | | Banner grid and form |
| `banner_slider/image/upload` | POST | Banner image upload (file field from `param_name`, default `image`) |
| `banner_slider/breakpoint/imageUpload` | POST | Crop source image upload (file field `breakpoint_image`) |
| `banner_slider/video/upload` | POST | Video upload (file field from `param_name`, default `video_path`) |
| `banner_slider/responsivecrop/breakpoints` | GET | `?slider_id=` → `{"breakpoints": [...]}`, enabled only |

Uploads answer `{file, name, url, size, type, width?, height?}`, or `{error, errorcode}`.

## Extending the forms

The banner and slider forms are mapped by pools of field mappers
(`Api/Form/BannerFormMapperInterface`, `Api/Form/SliderFormMapperInterface`), registered on
`Model/Form/BannerFormHydrator` and `Model/Form/SliderFormHydrator` in `etc/adminhtml/di.xml`. To map a new field,
add a mapper: `hydrate()` applies its posted fields (recording rejected values in `FieldErrors`), `export()` returns
its form values. The form data providers also run a `Magento\Ui` modifier pool for meta and extra data.

## Development

- PHP unit tests: `Test/Unit`.
- The crop editor's logic lives in dependency-free AMD modules under `view/adminhtml/web/js/cropper/` (geometry,
  post payload and size budget, change tracking, format support, encoding, submission, editor state, file sizes);
  the Knockout component and its view only wire them to the page. `node Test/Js/run.mjs` tests them in plain node
  (no packages needed) and prints `N passed`.

## Dependencies

- PHP 8.3 or 8.4, Magento 2.4.7 or later
- `hryvinskyi/magento2-banner-slider-api` ^2.0, `hryvinskyi/magento2-banner-slider` ^2.0
- `hryvinskyi/magento2-base` ^2.2 (admin menu and configuration tab), `hryvinskyi/magento2-configuration-fields`
  (CSS editor)

## Installation

Installed with the `hryvinskyi/magento2-banner-slider-pack` metapackage:

```bash
composer require hryvinskyi/magento2-banner-slider-pack
php bin/magento setup:upgrade
php bin/magento cache:flush
```

## Author

**Volodymyr Hryvinskyi** - volodymyr@hryvinskyi.com

## License

MIT
