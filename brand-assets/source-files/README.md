# Source Files - PearBlog ULTRA PRO

Design source files for brand assets - editable originals for designers.

## 📁 What Goes Here

Store your original, editable design files in this directory:

### Figma Files
- `pearblog-brand-system.fig` - Complete brand system
- `pearblog-logos.fig` - Logo variations
- `pearblog-social-templates.fig` - Social media templates
- `pearblog-app-icons.fig` - Mobile app icons

### Adobe Files
- `pearblog-logo-master.ai` - Illustrator master logo
- `pearblog-effects.psd` - Photoshop effects/composites
- `pearblog-brand-guide.indd` - InDesign brand guide

### Sketch Files
- `pearblog-design-system.sketch` - Complete design system
- `pearblog-icon-library.sketch` - Icon components

### Other
- `pearblog-animations.aep` - After Effects animations
- `pearblog-3d-models.blend` - Blender 3D models
- Font files (if custom typography used)

## 🎨 Recommended Structure

```
source-files/
├── figma/
│   ├── pearblog-brand-system.fig
│   └── pearblog-components.fig
├── adobe/
│   ├── illustrator/
│   │   └── pearblog-logo-master.ai
│   ├── photoshop/
│   │   └── pearblog-effects.psd
│   └── after-effects/
│       └── pearblog-animations.aep
├── sketch/
│   └── pearblog-design-system.sketch
├── fonts/
│   ├── Inter/
│   ├── Poppins/
│   └── JetBrains-Mono/
└── README.md (this file)
```

## 📝 File Naming Convention

Use consistent naming:
```
pearblog-{asset-type}-{variation}.{ext}

Examples:
pearblog-logo-primary.ai
pearblog-logo-animated.aep
pearblog-social-templates.fig
pearblog-app-icons-ios.sketch
```

## 🔧 Export Settings

### From Figma
- Use "Export" panel
- SVG: "Presentation Attributes"
- PNG: @1x, @2x, @3x for Retina
- Optimize on export

### From Illustrator
- Save As → SVG
- Options: Presentation Attributes
- Decimal Places: 2
- Export PNG at multiple sizes

### From Photoshop
- Save for Web (Legacy)
- PNG-24 with transparency
- Or use Export As → PNG
- Maintain aspect ratio

### From Sketch
- Use Export Presets
- SVG for vectors
- PNG @1x, @2x, @3x
- Optimize exports

## 💾 Version Control

**Important:** Source files can be large!

### Git LFS (Large File Storage)
If using Git, set up Git LFS for large files:

```bash
git lfs install
git lfs track "*.fig"
git lfs track "*.ai"
git lfs track "*.psd"
git lfs track "*.sketch"
git lfs track "*.aep"
```

### Alternative: Cloud Storage
Consider storing large source files in:
- Google Drive / Dropbox
- Figma (cloud-native)
- Adobe Creative Cloud
- Sketch Cloud

Then reference them here:
```
source-files/
└── CLOUD_LINKS.md
    ├── Figma: https://figma.com/file/...
    ├── Drive: https://drive.google.com/...
    └── Adobe: https://adobe.com/...
```

## 📊 File Organization Best Practices

### Layer Organization
- Use meaningful layer names
- Group related elements
- Lock finished layers
- Hide unused elements

### Artboard/Frame Setup
- One artboard per variation
- Consistent naming
- Include safe zones/guides
- Document dimensions

### Color Management
- Use color styles/swatches
- Match brand colors exactly
- Use CMYK for print, RGB for digital
- Include color profiles

### Typography
- Embed/outline fonts for final files
- Keep text editable in working files
- Use character/paragraph styles
- Document font licenses

## 🎯 Before Exporting

Checklist before exporting final assets:

- [ ] All layers properly named
- [ ] Colors match brand guidelines
- [ ] Typography uses correct fonts
- [ ] Artboards/frames properly sized
- [ ] Guides and grids cleaned up
- [ ] Hidden layers removed
- [ ] Effects flattened (if needed)
- [ ] Export settings verified
- [ ] File saved with version number

## 🔄 Workflow

### Initial Design
1. Create master logo in vector editor (AI/Figma)
2. Design all variations from master
3. Export base versions
4. Test at various sizes

### Variations & Effects
1. Open master in appropriate tool
2. Apply effects (glow, neon, etc.)
3. Export with effects applied
4. Optimize file sizes

### Iteration
1. Make changes in source file
2. Re-export affected assets
3. Replace in brand-assets folder
4. Update version notes

## 📋 Asset Export Map

Map source files to exported assets:

```
pearblog-logo-master.ai →
  ├── /logo/pearblog-logo-primary.svg
  ├── /logo/pearblog-logo-primary.png (various sizes)
  ├── /logo/pearblog-logo-dark.svg
  ├── /logo/pearblog-icon.svg
  └── /favicon/favicon-*.png (all sizes)

pearblog-effects.psd →
  ├── /animated/pearblog-logo-glow.png
  ├── /animated/pearblog-logo-neon.png
  └── /logo/pearblog-logo-3d.png

pearblog-social-templates.fig →
  ├── /social/pearblog-og-default.png
  ├── /social/pearblog-twitter-card.png
  └── /social/templates/*.png

pearblog-app-icons.fig →
  ├── /app-icons/ios/AppIcon.appiconset/*
  └── /app-icons/android/mipmap-*/*
```

## 🛡️ Backup Strategy

**CRITICAL:** Always backup source files!

### Regular Backups
- Daily: Auto-backup to cloud
- Weekly: Manual backup to external drive
- Monthly: Archive with version tag

### Backup Locations
1. Primary: Cloud storage (Drive/Dropbox)
2. Secondary: External hard drive
3. Tertiary: Version control (Git LFS)

### What to Backup
- All source files (.ai, .psd, .fig, .sketch)
- Font files
- Export presets
- Color palettes
- Style guides

## 📖 Documentation

Include in source files:
- Layer descriptions
- Design decisions notes
- Export instructions
- Color code references
- Font information
- Revision history

## 🔗 Links & Resources

### Design Software
- **Figma:** https://figma.com
- **Adobe Creative Cloud:** https://adobe.com
- **Sketch:** https://sketch.com
- **Affinity Designer:** https://affinity.serif.com

### Export Tools
- **SVGOMG:** https://jakearchibald.github.io/svgomg/
- **TinyPNG:** https://tinypng.com
- **ImageOptim:** https://imageoptim.com (Mac)

### Tutorials
- Figma: Export best practices
- Illustrator: Logo design techniques
- Photoshop: Web optimization
- Sketch: Symbol management

---

**Status:** Awaiting source files upload
**Priority:** HIGH - Required for future edits
**Owner:** Design team
**Last Updated:** 2026-04-03
