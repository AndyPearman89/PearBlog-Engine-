#!/usr/bin/env python3
"""
Generate all PearBlog brand asset raster outputs from SVG sources.

Usage:
    pip install cairosvg pillow
    python3 scripts/gen_brand_assets.py

Output directories:
    brand-assets/logo/        PNG variants for all logo SVGs + icon sizes
    brand-assets/favicon/     favicon.ico + PNG sizes + apple-touch-icon + mstile
    brand-assets/social/      OG image, Twitter card, profile images, banner templates
    brand-assets/app-icons/   iOS AppIcon.appiconset + Android mipmap-*
    brand-assets/animated/    Glow and neon effect PNGs + Lottie JSON files
"""

import io
import json
import os
from PIL import Image, ImageDraw, ImageFilter

try:
    import cairosvg
except ImportError:
    raise SystemExit("cairosvg is required: pip install cairosvg pillow")

REPO_ROOT = os.path.join(os.path.dirname(__file__), "..")
BASE = os.path.join(REPO_ROOT, "brand-assets")
LOGO = os.path.join(BASE, "logo")
FAV  = os.path.join(BASE, "favicon")
SOC  = os.path.join(BASE, "social")
ANI  = os.path.join(BASE, "animated")
APP  = os.path.join(BASE, "app-icons")


def ensure_dirs():
    for d in [
        LOGO, FAV, SOC, ANI,
        os.path.join(APP, "ios", "AppIcon.appiconset"),
        os.path.join(APP, "android", "mipmap-mdpi"),
        os.path.join(APP, "android", "mipmap-hdpi"),
        os.path.join(APP, "android", "mipmap-xhdpi"),
        os.path.join(APP, "android", "mipmap-xxhdpi"),
        os.path.join(APP, "android", "mipmap-xxxhdpi"),
        os.path.join(SOC, "templates"),
    ]:
        os.makedirs(d, exist_ok=True)


def svg_to_png(svg_path, out_path, width, height=None):
    kwargs = {"write_to": out_path, "output_width": width}
    if height:
        kwargs["output_height"] = height
    cairosvg.svg2png(url=svg_path, **kwargs)
    print(f"  {os.path.relpath(out_path, BASE)}")


def svg_to_img(svg_path, width, height=None):
    kwargs = {"output_width": width}
    if height:
        kwargs["output_height"] = height
    data = cairosvg.svg2png(url=svg_path, **kwargs)
    return Image.open(io.BytesIO(data)).convert("RGBA")


def gen_logos():
    print("=== Logo PNGs ===")
    logos = {
        "pearblog-logo-primary":   (1200, 360),
        "pearblog-logo-dark":      (1200, 360),
        "pearblog-logo-light":     (1200, 360),
        "pearblog-wordmark":       (800, 200),
        "pearblog-logo-mono-black": (1200, 360),
        "pearblog-logo-mono-white": (1200, 360),
    }
    for name, (w, h) in logos.items():
        svg = os.path.join(LOGO, f"{name}.svg")
        if os.path.exists(svg):
            svg_to_png(svg, os.path.join(LOGO, f"{name}.png"), w, h)

    icon_svg = os.path.join(LOGO, "pearblog-icon.svg")
    for sz in [16, 32, 48, 64, 96, 128, 192, 256, 512, 1024]:
        svg_to_png(icon_svg, os.path.join(LOGO, f"pearblog-icon-{sz}x{sz}.png"), sz, sz)


def gen_favicons():
    print("\n=== Favicon package ===")
    fav_svg = os.path.join(FAV, "favicon.svg")
    icon_svg = os.path.join(LOGO, "pearblog-icon.svg")
    fav_sizes = [16, 32, 48, 64, 96, 128, 256, 512]
    fav_imgs = []
    for sz in fav_sizes:
        out = os.path.join(FAV, f"favicon-{sz}x{sz}.png")
        svg_to_png(fav_svg, out, sz, sz)
        fav_imgs.append(Image.open(out).convert("RGBA"))

    ico_path = os.path.join(FAV, "favicon.ico")
    ico_imgs = [img for img, sz in zip(fav_imgs, fav_sizes) if sz <= 256]
    ico_imgs[0].save(
        ico_path, format="ICO",
        sizes=[(sz, sz) for sz in fav_sizes if sz <= 256],
        append_images=ico_imgs[1:],
    )
    print(f"  favicon/favicon.ico")

    svg_to_png(icon_svg, os.path.join(FAV, "apple-touch-icon.png"), 180, 180)
    for w, h in [(70, 70), (144, 144), (150, 150), (310, 150), (310, 310)]:
        svg_to_png(icon_svg, os.path.join(FAV, f"mstile-{w}x{h}.png"), w, h)


def gen_social():
    print("\n=== Social PNGs ===")
    icon_svg   = os.path.join(LOGO, "pearblog-icon.svg")
    primary_svg = os.path.join(LOGO, "pearblog-logo-primary.svg")
    og_svg     = os.path.join(SOC, "pearblog-og-default.svg")
    tw_svg     = os.path.join(SOC, "pearblog-twitter-card.svg")

    svg_to_png(og_svg, os.path.join(SOC, "pearblog-og-default.png"), 1200, 630)
    svg_to_png(tw_svg, os.path.join(SOC, "pearblog-twitter-card.png"), 1200, 600)

    profile_sizes = {
        "facebook":  (180, 180),
        "twitter":   (400, 400),
        "instagram": (320, 320),
        "linkedin":  (400, 400),
        "youtube":   (800, 800),
        "github":    (460, 460),
    }
    for platform, (w, h) in profile_sizes.items():
        svg_to_png(icon_svg, os.path.join(SOC, f"pearblog-profile-{platform}.png"), w, h)

    for name, w, h in [
        ("twitter-banner",  1500, 500),
        ("linkedin-banner", 1584, 396),
        ("facebook-cover",  820, 312),
        ("youtube-banner",  2560, 1440),
    ]:
        tpl = Image.new("RGBA", (w, h), (11, 17, 24, 255))
        logo_img = svg_to_img(primary_svg, w // 2)
        lw, lh = logo_img.size
        tpl.paste(logo_img, ((w - lw) // 2, (h - lh) // 2), logo_img)
        tpl.save(os.path.join(SOC, "templates", f"pearblog-{name}.png"))
        print(f"  social/templates/pearblog-{name}.png")


def gen_ios_icons():
    print("\n=== iOS App Icons ===")
    icon_svg = os.path.join(LOGO, "pearblog-icon.svg")
    ios_dir  = os.path.join(APP, "ios", "AppIcon.appiconset")
    ios_sizes = [
        (20, 1), (20, 2), (20, 3),
        (29, 1), (29, 2), (29, 3),
        (40, 1), (40, 2), (40, 3),
        (60, 2), (60, 3),
        (76, 1), (76, 2),
        (83.5, 2),
        (1024, 1),
    ]
    contents_images = []
    for base_pt, scale in ios_sizes:
        px = int(base_pt * scale)
        fn = (
            f"AppIcon-{int(base_pt)}@{scale}x.png"
            if base_pt != 1024
            else "AppIcon-1024.png"
        )
        svg_to_png(icon_svg, os.path.join(ios_dir, fn), px, px)
        contents_images.append({
            "filename": fn,
            "idiom": (
                "universal" if base_pt == 1024
                else ("ipad" if base_pt in [76, 83.5] else "iphone")
            ),
            "scale": f"{scale}x",
            "size": f"{int(base_pt)}x{int(base_pt)}",
        })
    contents = {"images": contents_images, "info": {"author": "xcode", "version": 1}}
    with open(os.path.join(ios_dir, "Contents.json"), "w") as f:
        json.dump(contents, f, indent=2)
    print(f"  app-icons/ios/AppIcon.appiconset/Contents.json")


def gen_android_icons():
    print("\n=== Android App Icons ===")
    icon_svg = os.path.join(LOGO, "pearblog-icon.svg")
    android_sizes = {
        "mipmap-mdpi":    48,
        "mipmap-hdpi":    72,
        "mipmap-xhdpi":   96,
        "mipmap-xxhdpi":  144,
        "mipmap-xxxhdpi": 192,
    }
    for folder, sz in android_sizes.items():
        d = os.path.join(APP, "android", folder)
        os.makedirs(d, exist_ok=True)
        svg_to_png(icon_svg, os.path.join(d, "ic_launcher.png"), sz, sz)
        img = svg_to_img(icon_svg, sz, sz)
        mask = Image.new("L", (sz, sz), 0)
        draw = ImageDraw.Draw(mask)
        draw.rounded_rectangle([0, 0, sz, sz], radius=sz // 4, fill=255)
        rounded = Image.new("RGBA", (sz, sz), (0, 0, 0, 0))
        rounded.paste(img, (0, 0), mask)
        rounded.save(os.path.join(d, "ic_launcher_round.png"))
        svg_to_png(icon_svg, os.path.join(d, "ic_launcher_foreground.png"), sz, sz)
        print(f"  app-icons/android/{folder}/")


def gen_effects():
    print("\n=== Glow & Neon Effects ===")
    icon_svg = os.path.join(LOGO, "pearblog-icon.svg")
    icon_img = svg_to_img(icon_svg, 512, 512)

    # Glow
    glow = Image.new("RGBA", (512, 512), (11, 17, 24, 255))
    for radius, alpha in [(40, 80), (24, 120), (12, 160)]:
        r, g, b, a = icon_img.split()
        layer = Image.merge("RGBA", (
            r.point(lambda x: int(x * 0.29)),
            g.point(lambda x: min(255, int(x * 0.87))),
            b.point(lambda x: int(x * 0.50)),
            a,
        ))
        blurred = layer.filter(ImageFilter.GaussianBlur(radius=radius))
        br, bg2, bb, ba = blurred.split()
        blurred = Image.merge("RGBA", (br, bg2, bb, ba.point(lambda x: x * alpha // 255)))
        glow = Image.alpha_composite(glow, blurred)
    glow = Image.alpha_composite(glow, icon_img)
    glow.save(os.path.join(ANI, "pearblog-logo-glow.png"))
    print("  animated/pearblog-logo-glow.png")

    # Neon
    neon = Image.new("RGBA", (512, 512), (5, 2, 15, 255))
    for radius, tint, alpha in [
        (30, (74, 222, 128), 100),
        (18, (96, 165, 250), 130),
        (8, (255, 255, 255), 60),
    ]:
        tr, tg, tb = tint
        r, g, b, a = icon_img.split()
        layer = Image.merge("RGBA", (
            r.point(lambda x, tr=tr: int(x * (tr / 255))),
            g.point(lambda x, tg=tg: int(x * (tg / 255))),
            b.point(lambda x, tb=tb: int(x * (tb / 255))),
            a,
        ))
        blurred = layer.filter(ImageFilter.GaussianBlur(radius=radius))
        br, bg2, bb, ba = blurred.split()
        blurred = Image.merge("RGBA", (br, bg2, bb, ba.point(lambda x: x * alpha // 255)))
        neon = Image.alpha_composite(neon, blurred)
    neon = Image.alpha_composite(neon, icon_img)
    neon.save(os.path.join(ANI, "pearblog-logo-neon.png"))
    print("  animated/pearblog-logo-neon.png")


def gen_lottie():
    print("\n=== Lottie JSON ===")
    base = {
        "v": "5.9.0", "fr": 60, "ip": 0, "op": 120, "w": 512, "h": 512,
        "nm": "PearBlog Logo Intro", "ddd": 0, "assets": [],
        "layers": [
            {
                "ddd": 0, "ind": 1, "ty": 4, "nm": "Pear Icon", "sr": 1,
                "ks": {
                    "o": {"a": 1, "k": [{"t": 0, "s": [0], "i": {"x": [0.5], "y": [1]}, "o": {"x": [0.5], "y": [0]}}, {"t": 20, "s": [100]}]},
                    "r": {"a": 1, "k": [{"t": 0, "s": [-10], "i": {"x": [0.5], "y": [1]}, "o": {"x": [0.5], "y": [0]}}, {"t": 30, "s": [5]}, {"t": 60, "s": [0]}]},
                    "p": {"a": 0, "k": [256, 256, 0]},
                    "a": {"a": 0, "k": [256, 256, 0]},
                    "s": {"a": 1, "k": [{"t": 0, "s": [60, 60, 100], "i": {"x": [0.5], "y": [1]}, "o": {"x": [0.5], "y": [0]}}, {"t": 30, "s": [105, 105, 100]}, {"t": 60, "s": [100, 100, 100]}]},
                },
                "ao": 0, "shapes": [], "ip": 0, "op": 120, "st": 0, "bm": 0,
            },
            {
                "ddd": 0, "ind": 2, "ty": 4, "nm": "Glow Pulse", "sr": 1,
                "ks": {
                    "o": {"a": 1, "k": [{"t": 60, "s": [0]}, {"t": 90, "s": [40]}, {"t": 120, "s": [0]}]},
                    "s": {"a": 1, "k": [{"t": 60, "s": [100, 100, 100]}, {"t": 90, "s": [115, 115, 100]}, {"t": 120, "s": [100, 100, 100]}]},
                    "p": {"a": 0, "k": [256, 256, 0]},
                    "a": {"a": 0, "k": [256, 256, 0]},
                },
                "ao": 0, "shapes": [], "ip": 0, "op": 120, "st": 0, "bm": 0,
            },
        ],
        "markers": [],
    }
    with open(os.path.join(ANI, "pearblog-logo-lottie-intro.json"), "w") as f:
        json.dump(base, f, indent=2)
    loop = dict(base)
    loop["nm"] = "PearBlog Logo Loop"
    loop["markers"] = [{"tm": 60, "cm": "loop", "dr": 60}]
    with open(os.path.join(ANI, "pearblog-logo-lottie-loop.json"), "w") as f:
        json.dump(loop, f, indent=2)
    print("  animated/pearblog-logo-lottie-intro.json")
    print("  animated/pearblog-logo-lottie-loop.json")


if __name__ == "__main__":
    ensure_dirs()
    gen_logos()
    gen_favicons()
    gen_social()
    gen_ios_icons()
    gen_android_icons()
    gen_effects()
    gen_lottie()
    print("\n✅ All brand assets generated.")
