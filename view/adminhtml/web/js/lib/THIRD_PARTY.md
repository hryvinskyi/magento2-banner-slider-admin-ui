# Third-party libraries bundled with the admin crop editor

These files are shipped unmodified. Update them as a whole from their source, then update this list.

| Library | Files | Version | Licence | Source |
|---|---|---|---|---|
| Cropper.js | `cropper.min.js` | 1.6.1 (build of 2023-09-17, from the file header) | MIT | https://github.com/fengyuanchen/cropperjs |
| img-comparison-slider | `img-comparison-slider/index.js`, `../../css/lib/img-comparison-slider/styles.css` | Not stated in the files (a minified build; it references a missing `index.js.map`) | MIT | https://github.com/sneas/img-comparison-slider |
| jSquash WebP encoder (`@jsquash/webp`) | `jsquash/webp/encode.js`, `meta.js`, `utils.js`, `codec/enc/webp_enc.{js,wasm}`, `codec/enc/webp_enc_simd.{js,wasm}` | Not stated in the files | Apache-2.0 (the codec is compiled from libwebp, BSD-3-Clause) | https://github.com/jamsinclair/jSquash |
| jSquash AVIF encoder (`@jsquash/avif`) | `jsquash/avif/encode.js`, `meta.js`, `utils.js`, `codec/enc/avif_enc.{js,wasm}`, `codec/enc/avif_enc_mt.{js,wasm}`, `codec/enc/avif_enc_mt.worker.mjs` | Not stated in the files; its options include `bitDepth`, `lossless` and `enableSharpYUV` | Apache-2.0 (the codec is compiled from libavif and libaom, BSD-2-Clause) | https://github.com/jamsinclair/jSquash |
| wasm-feature-detect (subset: `simd`, `threads`) | `jsquash/wasm-feature-detect.js` | Not stated in the file | Apache-2.0 | https://github.com/GoogleChromeLabs/wasm-feature-detect |

`jsquash/*/utils.js` and the encoder entry files carry Google's Apache-2.0 notice (they come from Squoosh); `utils.js`
also notes Jamie Sinclair's change that allows a pre-compiled WebAssembly module.

## Loading

- Cropper.js is an AMD/UMD build, required by module id `Hryvinskyi_BannerSliderAdminUi/js/lib/cropper.min`.
- img-comparison-slider registers the `<img-comparison-slider>` element; the banner edit layout loads it with its
  stylesheet.
- The jSquash encoders are ES modules loaded with `import()` on first use. They fetch their sibling files and the
  `.wasm` binaries by exact name, so `etc/config.xml` excludes this folder from JavaScript minification.
- WebAssembly compilation needs `'unsafe-eval'` (or `'wasm-unsafe-eval'`) in the admin `script-src`, which the
  default Magento admin policy already has.
