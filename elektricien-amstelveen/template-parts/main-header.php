<?php
/**
 * template-parts/main-header.php
 */
$logo          = get_field('header_logo', 'option');
$cta_text      = get_field('header_cta_text', 'option');
$cta_link      = get_field('header_cta_link', 'option');

$phone_display = get_field('global_phone_display', 'option');
$phone_clean   = get_field('global_phone_clean', 'option');

$header_uid = 'hdr-' . uniqid();
?>

<style>
.main-header {
    background: #fff;
    padding: 18px 16px;
    border-bottom: 1px solid #eee;
}

.main-header__inner {
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.main-header__logo img {
    max-height: 50px;
    max-width: 100%;
    width: auto;
    display: block;
}

.main-header__actions {
    display: flex;
    align-items: center;
    gap: 40px;
}

.main-header__cta,
.main-header__phone {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-style: italic;
    font-weight: bold;
    font-size: clamp(13px, 1.1vw, 16px);
    text-decoration: none;
    color: #1a1a1a;
    white-space: nowrap;
}

.main-header__phone {
    color: #1a1a1a;
}

.main-header__actions svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    display: block;
}

@media (max-width: 600px) {
    .main-header__inner {
        flex-wrap: nowrap;
    }

    .main-header__actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .main-header__logo img {
        max-width: 170px;
    }
}
</style>

<header class="main-header" role="banner">
    <div class="main-header__inner site-container">

        <div class="main-header__logo">
<a href="<?php echo esc_url( home_url('/') ); ?>" aria-label="<?php echo esc_attr( get_bloginfo('name') ); ?> - Home">
    <?php 
    if ( $logo ) : 
        // 1. Safely pull the URL string depending on ACF return format configuration
        $logo_url = is_array( $logo ) ? $logo['url'] : $logo;
        $logo_id  = attachment_url_to_postid( $logo_url );

        if ( $logo_id ) :
            $logo_1x  = wp_get_attachment_image_url( $logo_id, 'logo_desktop' );
            $logo_2x  = wp_get_attachment_image_url( $logo_id, 'logo_desktop_2x');
            $alt_text = get_post_meta( $logo_id, '_wp_attachment_image_alt', true );

            $src_url     = $logo_1x ? $logo_1x : $logo_url;
            $srcset_html = $logo_2x ? 'srcset="' . esc_url($logo_1x) . ' 1x, ' . esc_url($logo_2x) . ' 2x"' : '';
        else :
            $src_url     = $logo_url;
            $srcset_html = '';
            $alt_text    = is_array( $logo ) && ! empty( $logo['alt'] ) ? $logo['alt'] : '';
        endif;
    else :
        // 2. Fallback execution path for the hardcoded fallback logo asset URL string
        $fallback_url = 'https://elektricien-amstelveen.com/wp-content/uploads/2022/08/elektricien-amstelveen-logo-scaled.png';
        $logo_id      = attachment_url_to_postid( $fallback_url );

        if ( $logo_id ) :
            $logo_1x     = wp_get_attachment_image_url( $logo_id, 'logo_desktop' );
            $logo_2x     = wp_get_attachment_image_url( $logo_id, 'logo_desktop_2x' );
            $src_url     = $logo_1x ? $logo_1x : $fallback_url;
            $srcset_html = $logo_2x ? 'srcset="' . esc_url($logo_1x) . ' 1x, ' . esc_url($logo_2x) . ' 2x"' : '';
            $alt_text    = get_post_meta( $logo_id, '_wp_attachment_image_alt', true );
        else :
            $src_url     = $fallback_url;
            $srcset_html = '';
            $alt_text    = '';
        endif;
    endif;
    ?>
    <img 
        src="<?php echo esc_url( $src_url ); ?>" 
        <?php echo $srcset_html; ?>
        alt="<?php echo esc_attr( $alt_text ? $alt_text : get_bloginfo('name') ); ?>" 
        width="226" 
        height="50" 
        fetchpriority="high"
    >
</a>
        </div>

        <nav class="main-header__actions" aria-label="<?php esc_attr_e( 'Header navigatie', 'textdomain' ); ?>">
            <?php if ( $cta_text && $cta_link ) : ?>
                <a href="<?php echo esc_url( $cta_link ); ?>" class="main-header__cta">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" style="fill:var(--color-primary)" aria-hidden="true" focusable="false">
                        <path d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Zm0-80h560v-400H200v400Zm0-480h560v-80H200v80Zm0 0v-80 80Zm280 240q-17 0-28.5-11.5T440-440q0-17 11.5-28.5T480-480q17 0 28.5 11.5T520-440q0 17-11.5 28.5T480-400Zm-188.5-11.5Q280-423 280-440t11.5-28.5Q303-480 320-480t28.5 11.5Q360-457 360-440t-11.5 28.5Q337-400 320-400t-28.5-11.5ZM640-400q-17 0-28.5-11.5T600-440q0-17 11.5-28.5T640-480q17 0 28.5 11.5T680-440q0 17-11.5 28.5T640-400ZM480-240q-17 0-28.5-11.5T440-280q0-17 11.5-28.5T480-320q17 0 28.5 11.5T520-280q0 17-11.5 28.5T480-240Zm-188.5-11.5Q280-263 280-280t11.5-28.5Q303-320 320-320t28.5 11.5Q360-297 360-280t-11.5 28.5Q337-240 320-240t-28.5-11.5ZM640-240q-17 0-28.5-11.5T600-280q0-17 11.5-28.5T640-320q17 0 28.5 11.5T680-280q0 17-11.5 28.5T640-240Z"/>
                    </svg>
                    <?php echo esc_html( $cta_text ); ?>
                </a>
            <?php endif; ?>

            <?php if ( $phone_display && $phone_clean ) : ?>
                <a href="tel:<?php echo esc_attr( $phone_clean ); ?>" class="main-header__phone" aria-label="<?php echo esc_attr( sprintf( __( 'Bel ons op %s', 'textdomain' ), $phone_display ) ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" style="fill:var(--color-primary)" aria-hidden="true" focusable="false">
                        <path d="M798-120q-125 0-247-54.5T329-329Q229-429 174.5-551T120-798q0-18 12-30t30-12h162q14 0 25 9.5t13 22.5l26 140q2 16-1 27t-11 19l-97 98q20 37 47.5 71.5T387-386q31 31 65 57.5t72 48.5l94-94q9-9 23.5-13.5T670-390l138 28q14 4 23 14.5t9 23.5v162q0 18-12 30t-30 12ZM241-600l66-66-17-94h-89q5 41 14 81t26 79Zm358 358q39 17 79.5 27t81.5 13v-88l-94-19-67 67ZM241-600Zm358 358Z"/>
                    </svg>
                    <?php echo esc_html( $phone_display ); ?>
                </a>
            <?php endif; ?>
        </nav>

    </div>
</header>