# App Icons - PearBlog ULTRA PRO

Complete app icon package for iOS and Android platforms.

## 📱 iOS App Icons

### Requirements Overview
- **Format:** PNG (no JPEG)
- **Color Space:** sRGB or P3
- **Alpha Channel:** NO (must have opaque background)
- **Rounded Corners:** NO (iOS adds them automatically)
- **File Naming:** Must match Apple's requirements exactly

### Complete iOS Icon Set

```
AppIcon.appiconset/
├── Icon-App-20x20@1x.png          (20x20)    - iPhone Notification (iOS 7-13)
├── Icon-App-20x20@2x.png          (40x40)    - iPhone Notification (iOS 7-13)
├── Icon-App-20x20@3x.png          (60x60)    - iPhone Notification (iOS 7-13)
├── Icon-App-29x29@1x.png          (29x29)    - iPhone Settings (iOS 5-6)
├── Icon-App-29x29@2x.png          (58x58)    - iPhone Settings/Spotlight
├── Icon-App-29x29@3x.png          (87x87)    - iPhone Settings/Spotlight
├── Icon-App-40x40@1x.png          (40x40)    - iPad Spotlight
├── Icon-App-40x40@2x.png          (80x80)    - iPhone Spotlight
├── Icon-App-40x40@3x.png          (120x120)  - iPhone Spotlight
├── Icon-App-60x60@2x.png          (120x120)  - iPhone App
├── Icon-App-60x60@3x.png          (180x180)  - iPhone App
├── Icon-App-76x76@1x.png          (76x76)    - iPad App
├── Icon-App-76x76@2x.png          (152x152)  - iPad App
├── Icon-App-83.5x83.5@2x.png      (167x167)  - iPad Pro App
└── Icon-App-1024x1024@1x.png      (1024x1024) - App Store
```

### iOS Design Guidelines

**Color Background:**
- Use solid color or subtle gradient
- Recommended: White (#FFFFFF) or brand green (#4ADE80)
- No transparency (will show black if included)

**Safe Area:**
- Keep important content in center 80%
- iOS crops to rounded square (varies by iOS version)
- Icon can touch edges but prepare for corner rounding

**Design Consistency:**
- All sizes should look identical (just scaled)
- Same colors, same design, same proportions
- Test at actual size on device

**App Store Icon (1024x1024):**
- Highest quality version
- Used in App Store listing
- NO alpha channel
- NO rounded corners (Apple applies them)
- Maximum file size: 512KB recommended

### Contents.json for Xcode
```json
{
  "images": [
    {
      "size": "20x20",
      "idiom": "iphone",
      "filename": "Icon-App-20x20@2x.png",
      "scale": "2x"
    },
    {
      "size": "20x20",
      "idiom": "iphone",
      "filename": "Icon-App-20x20@3x.png",
      "scale": "3x"
    },
    // ... (complete JSON in source files folder)
  ],
  "info": {
    "version": 1,
    "author": "xcode"
  }
}
```

## 🤖 Android App Icons

### Requirements Overview
- **Format:** PNG (32-bit)
- **Color Space:** sRGB
- **Alpha Channel:** YES (transparency supported)
- **Shape:** Varies by device manufacturer
- **Adaptive Icons:** Required for Android 8.0+ (API 26+)

### Standard Launcher Icons

```
res/
├── mipmap-mdpi/
│   └── ic_launcher.png          (48x48)   - Baseline density (160dpi)
├── mipmap-hdpi/
│   └── ic_launcher.png          (72x72)   - High density (240dpi)
├── mipmap-xhdpi/
│   └── ic_launcher.png          (96x96)   - Extra-high density (320dpi)
├── mipmap-xxhdpi/
│   └── ic_launcher.png          (144x144) - Extra-extra-high density (480dpi)
├── mipmap-xxxhdpi/
│   └── ic_launcher.png          (192x192) - Extra-extra-extra-high density (640dpi)
└── ic_launcher-web.png          (512x512) - Google Play Store
```

### Adaptive Icons (Android 8.0+)

Adaptive icons consist of two layers:
1. **Foreground:** The icon content (logo, symbol)
2. **Background:** Solid color or pattern

```
res/
├── mipmap-anydpi-v26/
│   └── ic_launcher.xml
├── mipmap-mdpi/
│   ├── ic_launcher_foreground.png    (108x108)
│   └── ic_launcher_background.png    (108x108)
├── mipmap-hdpi/
│   ├── ic_launcher_foreground.png    (162x162)
│   └── ic_launcher_background.png    (162x162)
├── mipmap-xhdpi/
│   ├── ic_launcher_foreground.png    (216x216)
│   └── ic_launcher_background.png    (216x216)
├── mipmap-xxhdpi/
│   ├── ic_launcher_foreground.png    (324x324)
│   └── ic_launcher_background.png    (324x324)
└── mipmap-xxxhdpi/
    ├── ic_launcher_foreground.png    (432x432)
    └── ic_launcher_background.png    (432x432)
```

### Adaptive Icon Safe Zone

**Canvas:** 108x108dp
**Safe Zone:** 66x66dp (center 61%)
**Mask Shapes:** Circle, Square, Rounded Square, Squircle

```
┌─────────────────────────┐ 108dp
│  [33% padding]          │
│   ┌───────────────┐     │
│   │               │     │ 66dp
│   │  Safe Zone    │     │ (icon content)
│   │               │     │
│   └───────────────┘     │
│  [33% padding]          │
└─────────────────────────┘
```

**Critical:** Keep all important icon elements within the 66dp safe zone!

### ic_launcher.xml Example
```xml
<?xml version="1.0" encoding="utf-8"?>
<adaptive-icon xmlns:android="http://schemas.android.com/apk/res/android">
    <background android:drawable="@mipmap/ic_launcher_background"/>
    <foreground android:drawable="@mipmap/ic_launcher_foreground"/>
</adaptive-icon>
```

### Android Design Guidelines

**Foreground Layer:**
- Contains the PearBlog pear icon
- Transparent background (PNG with alpha)
- Keep within 66dp safe zone
- Can be simple graphic or detailed logo

**Background Layer:**
- Solid color (#4ADE80 or #0B1118)
- OR subtle pattern
- OR gradient
- Must extend to full 108dp canvas

**Legacy Icons (non-adaptive):**
- Include drop shadow for depth
- Slight 3D effect acceptable
- Works on all Android versions
- Fallback for devices without adaptive support

## 🎨 Design Specifications

### iOS Icon Design

**Recommended Approach:**
- White or light background (#F8FAFC)
- PearBlog icon in center (gradient version)
- 15% padding around icon
- No text (icon only for small sizes)
- Subtle shadow optional

**Alternative Dark:**
- Dark background (#0B1118)
- Light/white icon version
- Glowing effect for premium feel

### Android Icon Design

**Foreground Layer:**
```
Canvas: 108x108dp
Safe Zone: 66x66dp (center)
Icon Size: 60x60dp (optimal)
Padding: 24dp from edges
```

**Design for foreground:**
- PearBlog pear icon (simplified)
- Gradient or solid color
- High contrast
- Works on any background

**Background Layer:**
```
Solid Color: #4ADE80 (brand green)
OR
Gradient: #4ADE80 → #60A5FA
OR
Pattern: Subtle circuit pattern
```

## 🛠️ Generation Tools

### For iOS

**Using Figma:**
1. Create 1024x1024 artboard
2. Design icon with solid background
3. Use plugin "Icon Resizer" or "App Icon"
4. Export all required sizes

**Using Sketch:**
1. Design at 1024x1024
2. Use "Sketch App Icon Template"
3. Export all sizes at once

**Using Adobe Illustrator:**
1. Create 1024x1024 artboard
2. Export each size manually
3. OR use script for batch export

**Online Tool:**
- AppIcon.co - Upload 1024x1024, get all sizes
- MakeAppIcon.com - Automated resizing

### For Android

**Using Android Studio:**
1. Right-click res folder
2. New → Image Asset
3. Choose foreground and background
4. Auto-generates all densities

**Using Figma:**
1. Design adaptive icon layers (108dp each)
2. Export at all densities (mdpi through xxxhdpi)
3. Use plugin "Android Asset Exporter"

**Online Tool:**
- AppIcon.co - Android section
- AndroidAssetStudio.com - Icon generator

## 📋 Quality Checklist

### iOS Icons
- [ ] All required sizes present (20px to 1024px)
- [ ] NO alpha channel (completely opaque)
- [ ] NO rounded corners (iOS adds them)
- [ ] Consistent design across all sizes
- [ ] Readable at smallest size (20x20)
- [ ] App Store icon (1024x1024) is perfect
- [ ] Files are PNG format
- [ ] File names match Apple requirements
- [ ] Contents.json is valid

### Android Icons
- [ ] All density folders (mdpi to xxxhdpi)
- [ ] Adaptive icon layers (foreground + background)
- [ ] Safe zone respected (66dp center)
- [ ] Legacy icons for older Android
- [ ] Play Store icon (512x512)
- [ ] Transparent backgrounds where appropriate
- [ ] ic_launcher.xml configured correctly
- [ ] Tested on multiple device shapes

## 🧪 Testing

### iOS Testing
- [ ] Test on iPhone (standard display)
- [ ] Test on iPhone Plus/Pro Max (3x display)
- [ ] Test on iPad
- [ ] Test on iPad Pro
- [ ] View in App Store (1024x1024)
- [ ] Check Settings app appearance
- [ ] Check Spotlight search appearance
- [ ] Check notification appearance

### Android Testing
- [ ] Test on circular device (Pixel)
- [ ] Test on square device (Samsung)
- [ ] Test on rounded square
- [ ] Test on squircle device
- [ ] View in Play Store (512x512)
- [ ] Check launcher appearance
- [ ] Test adaptive animation (long-press)
- [ ] Test on various Android versions (7, 8, 9, 10+)

### Testing Tools
- **iOS:** Xcode Asset Catalog Viewer
- **Android:** Android Studio Layout Inspector
- **Both:** Physical devices (best method)
- **Online:** Icon Preview tools

## 📊 File Size Targets

```
iOS:
- 20-87px: <10KB each
- 120-180px: <30KB each
- 1024px: <200KB (App Store)

Android:
- mdpi-xhdpi: <20KB each
- xxhdpi-xxxhdpi: <40KB each
- 512px: <100KB (Play Store)
```

## 🎯 Platform-Specific Notes

### iOS Notes
- Icons appear in Home Screen, Settings, Spotlight, Notifications
- iOS applies automatic effects (rounded corners, shadow)
- Dark mode: Can provide dark variant (iOS 13+)
- App thinning: Only needed sizes downloaded to device
- Retina displays: Always provide @2x and @3x

### Android Notes
- Manufacturers apply different shapes (Samsung vs. Pixel)
- Adaptive icons allow animation and parallax effects
- Legacy icons needed for Android 7 and below
- Safe zone is critical for adaptive icons
- Background can be solid color (efficient) or drawable

### Cross-Platform Consistency
- Keep the same icon design concept
- Adjust for platform guidelines
- iOS: Opaque background required
- Android: Transparency supported
- Both: Readable at small sizes
- Both: Consistent brand identity

## 🚀 Quick Start Guide

### Step 1: Design Master Icon
Create 1024x1024 icon design:
- PearBlog pear icon
- Solid background (iOS requirement)
- Center-focused (Android safe zone)

### Step 2: Generate iOS Icons
Use AppIcon.co or manual export:
- Upload 1024x1024 master
- Download complete iOS icon set
- Add to Xcode project

### Step 3: Create Android Adaptive
Design two layers at 108dp:
- Foreground: Icon (keep in 66dp center)
- Background: Color or pattern
- Export all densities

### Step 4: Add to Projects
- iOS: Add .appiconset to Xcode Assets
- Android: Add to res/mipmap folders
- Configure ic_launcher.xml

### Step 5: Test
- Build on physical devices
- Check all sizes and contexts
- Verify on different OS versions

---

**Status:** Awaiting asset creation
**Priority:** HIGH - Required for app store submission
**Dependencies:** Logo files must be created first
