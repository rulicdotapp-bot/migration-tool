<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <?php 
    $gtm_head = get_field('google_tag_manager_gtm', 'option');
    if ( !empty($gtm_head) ) {
        echo $gtm_head;
    }
    
    $global_font  = get_field('global_font_family', 'option') ?: '"Inter", sans-serif';
    $custom_width = get_field('global_max_width', 'option') ?: 1250;

    $color_primary       = get_field('global_color_primary', 'option') ?: '#ff5e00';
    $color_primary_hover = get_field('global_color_primary_hover', 'option') ?: '#e05300';
    $color_dark          = get_field('global_color_dark', 'option') ?: '#161c2d';
    ?>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="google-site-verification" content="NDqEUp4Za21rPfu2mVEEGto1EIEzvFo_E1uC-4kAnrs" />

    <link rel="icon" type="image/png" href="<?php echo get_template_directory_uri(); ?>/assets/images/favicon.png">
    <link rel="apple-touch-icon" href="<?php echo get_template_directory_uri(); ?>/assets/images/favicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap">
    </noscript>
    <script>
        (function() {
            let assetsLoaded = false;

            const delayedScripts = [
                '/wp-content/plugins/contact-form-7/includes/swv/js/index.js',
                '/wp-content/plugins/contact-form-7/includes/js/index.js'
            ];

            function loadDelayedAssets() {
                if (assetsLoaded) return;
                assetsLoaded = true;

                const cf7Style = document.createElement('link');
                cf7Style.rel = 'stylesheet';
                cf7Style.href = '/wp-content/plugins/contact-form-7/includes/css/styles.css';
                document.head.appendChild(cf7Style);

                delayedScripts.forEach(function(src) {
                    const script = document.createElement('script');
                    script.src = src;
                    script.defer = true;
                    document.body.appendChild(script);
                });

                triggerEvents.forEach(function(event) {
                    window.removeEventListener(event, loadDelayedAssets, { passive: true });
                });
            }

            const triggerEvents = ['pointerdown', 'mousemove', 'scroll', 'touchstart', 'keydown'];
            triggerEvents.forEach(function(event) {
                window.addEventListener(event, loadDelayedAssets, { passive: true });
            });
        })();
    </script>
    <?php wp_head();  ?>
    
    <style data-dc-tpl="4">
        /* Single source of truth for theme colors — change a value here (or
           the matching field under Theme Settings → Global Settings) and it
           propagates to every section on the site. */
        :root {
            --color-primary: <?php echo esc_attr($color_primary); ?>;
            --color-primary-hover: <?php echo esc_attr($color_primary_hover); ?>;
            --color-primary-light: #f5760a;
            --color-primary-dark-alt: #d44d02;
            --color-dark: <?php echo esc_attr($color_dark); ?>;
            --color-accent-light: #ff7b25;
            --color-rating-gold: #e68a00;
            --color-text: #1a1a1a;
            --color-white: #ffffff;
            --color-cta-gradient-start: #e64a00;
            --color-cta-gradient-end: #b31b10;
        }
        html { scroll-behavior: smooth; }
        body { 
            margin: 0; 
            font-family: <?php echo $global_font; ?>;
            background: var(--color-white);
            -webkit-font-smoothing: antialiased; 
        }
        summary::-webkit-details-marker { display: none; }
        @keyframes ssn-pulse { 0% { transform: scale(0.5); opacity: 0.9; } 100% { transform: scale(1.7); opacity: 0; } }
        
        .site-container,
        .main-header__inner { 
            max-width: <?php echo esc_attr($custom_width); ?>px !important; 
        }
        
        .main-cta__btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background-color: var(--color-primary);
            color: var(--color-white);
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 8px;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .main-cta__btn:hover {
            background-color: var(--color-primary-hover);
        }
        
        .main-cta__btn svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
            transition: transform 0.2s ease;
        }
        
        .main-cta__btn:hover svg {
            transform: translateX(4px);
        }

        /* Essential Accessibility CSS for Screen Readers & AI Scrapers */
        .screen-reader-text {
            border: 0;
            clip: rect(1px, 1px, 1px, 1px);
            clip-path: inset(50%);
            height: 1px;
            margin: -1px;
            overflow: hidden;
            padding: 0;
            position: absolute;
            width: 1px;
            word-wrap: normal !important;
        }
        .skip-link:focus {
            top: 0;
            left: 0;
            background: var(--color-primary);
            color: var(--color-white);
            padding: 15px;
            z-index: 999999;
            position: absolute;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body <?php body_class(); ?>>
    <?php 
    $gtm_body = get_field('google_tag_manager_noscript', 'option');
    if ( !empty($gtm_body) ) {
        echo $gtm_body;
    }
    ?>

    <a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e( 'Skip to content', 'textdomain' ); ?></a>

    <header role="banner" itemscope itemtype="https://schema.org/WPHeader">
        <?php get_template_part( 'template-parts/top-bar' ); ?>
        <?php get_template_part( 'template-parts/main-header' ); ?>
    </header>

    <main id="main-content" role="main" itemprop="mainContentOfPage">