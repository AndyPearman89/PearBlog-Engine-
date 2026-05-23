<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <?php wp_head(); ?>
</head>
<body <?php body_class('poradnik-landing-minimal'); ?>>
<?php if (function_exists('wp_body_open')) { wp_body_open(); } ?>

<!-- Minimal Header for Landing Pages -->
<header class="plv5-minimal-header" style="
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    padding: 1rem 0;
">
    <div class="pb-container" style="
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    ">
        <!-- Logo -->
        <div class="plv5-header__logo">
            <a href="<?php echo esc_url(home_url('/')); ?>" style="
                font-size: 1.5rem;
                font-weight: 800;
                color: #0066ff;
                text-decoration: none;
            ">
                <?php bloginfo('name'); ?>
            </a>
        </div>

        <!-- CTA Button -->
        <div class="plv5-header__cta">
            <a href="#plv5HeroForm" class="plv5-btn-small" style="
                display: inline-block;
                padding: 0.75rem 1.5rem;
                background: #0066ff;
                color: white;
                border-radius: 0.5rem;
                text-decoration: none;
                font-weight: 600;
                font-size: 0.875rem;
                transition: all 0.3s;
            ">
                Rozpocznij za darmo
            </a>
        </div>
    </div>
</header>

<!-- Add spacing for fixed header -->
<div style="height: 80px;"></div>
