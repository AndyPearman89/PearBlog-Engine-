# Animated Assets

## Vector / SVG Animation

- `pearblog-logo-animated.svg` — CSS/SVG keyframe animation, no JS required. Suitable for landing pages and product splash sections.

## Raster Special Effects (PNG)

Generated 2026-06-14 from `pearblog-icon.svg` (512×512):

| File | Description |
|------|-------------|
| `pearblog-logo-glow.png` | Multi-layer Gaussian-blur green glow on dark (#0B1118) background |
| `pearblog-logo-neon.png` | Neon outline effect with green/blue tints on near-black background |

## Lottie Animation (JSON)

| File | Description |
|------|-------------|
| `pearblog-logo-lottie-intro.json` | 2-second intro: fade-in + bounce-in scale + glow pulse (60fps, 120 frames) |
| `pearblog-logo-lottie-loop.json` | Looping variant with loop marker at frame 60 |

### Using Lottie

```html
<!-- Via lottie-web CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
<div id="lottie-logo" style="width:200px;height:200px;"></div>
<script>
  lottie.loadAnimation({
    container: document.getElementById('lottie-logo'),
    renderer: 'svg',
    loop: false,
    autoplay: true,
    path: '/brand-assets/animated/pearblog-logo-lottie-intro.json'
  });
</script>
```
