# App Icons

Generated 2026-06-14 from `logo/pearblog-icon.svg`.

## iOS — `ios/AppIcon.appiconset/`

Full icon set for iPhone and iPad, ready to drop into Xcode. Includes `Contents.json`.

| Filename | Logical Size | Scale | Use |
|----------|-------------|-------|-----|
| `AppIcon-20@1x.png` | 20pt | 1× | iPad Notification |
| `AppIcon-20@2x.png` | 20pt | 2× | iPhone/iPad Notification |
| `AppIcon-20@3x.png` | 20pt | 3× | iPhone Notification |
| `AppIcon-29@1x.png` | 29pt | 1× | iPad Settings |
| `AppIcon-29@2x.png` | 29pt | 2× | iPhone/iPad Settings |
| `AppIcon-29@3x.png` | 29pt | 3× | iPhone Settings |
| `AppIcon-40@1x.png` | 40pt | 1× | iPad Spotlight |
| `AppIcon-40@2x.png` | 40pt | 2× | iPhone/iPad Spotlight |
| `AppIcon-40@3x.png` | 40pt | 3× | iPhone Spotlight |
| `AppIcon-60@2x.png` | 60pt | 2× | iPhone Home Screen |
| `AppIcon-60@3x.png` | 60pt | 3× | iPhone Home Screen |
| `AppIcon-76@1x.png` | 76pt | 1× | iPad Home Screen |
| `AppIcon-76@2x.png` | 76pt | 2× | iPad Home Screen |
| `AppIcon-83@2x.png` | 83.5pt | 2× | iPad Pro Home Screen |
| `AppIcon-1024.png` | 1024pt | 1× | App Store |

## Android — `android/mipmap-*/`

Adaptive icon set for Android, one density per folder.

| Folder | Pixel Size | Use |
|--------|-----------|-----|
| `mipmap-mdpi/` | 48×48 | Baseline (1×) |
| `mipmap-hdpi/` | 72×72 | High density (1.5×) |
| `mipmap-xhdpi/` | 96×96 | Extra high density (2×) |
| `mipmap-xxhdpi/` | 144×144 | Extra-extra high density (3×) |
| `mipmap-xxxhdpi/` | 192×192 | Extra-extra-extra high density (4×) |

Each density folder contains three files:

| File | Description |
|------|-------------|
| `ic_launcher.png` | Standard square launcher icon |
| `ic_launcher_round.png` | Circular launcher icon (rounded mask applied) |
| `ic_launcher_foreground.png` | Adaptive icon foreground layer |

## Web Manifest

For PWA/web manifest, use the icon sizes from `logo/pearblog-icon-*.png`:

```json
{
  "icons": [
    { "src": "/brand-assets/logo/pearblog-icon-192x192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/brand-assets/logo/pearblog-icon-512x512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```

> Note: Regenerate all icons from `logo/pearblog-icon.svg` if the icon design changes.
