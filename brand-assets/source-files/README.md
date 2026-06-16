# Design Source Files

This directory holds original design source files for PearBlog brand assets.

## Expected Files

| File | Tool | Contents |
|------|------|---------|
| `PearBlog-Logo.fig` | Figma | Primary logo, icon, wordmark, all variations + brand color/type styles |
| `PearBlog-Icons.ai` | Adobe Illustrator | Vector icon source with layers |
| `PearBlog-Brand.sketch` | Sketch | Mac-based design file (mirror of Figma) |
| `PearBlog-Effects.psd` | Photoshop | Glow, neon, and raster effect compositions |

## Status

Source files have not yet been committed to the repository. All published SVG assets in `logo/`, `favicon/`, `social/`, and `animated/` were hand-crafted in SVG and serve as the authoritative source.

## Regenerating Assets

To regenerate all raster outputs from the SVG sources, run:

```bash
pip install cairosvg pillow
python3 scripts/gen_brand_assets.py
```

Or individually using cairosvg:

```bash
python3 -c "import cairosvg; cairosvg.svg2png(url='logo/pearblog-icon.svg', write_to='logo/pearblog-icon-512x512.png', output_width=512, output_height=512)"
```
