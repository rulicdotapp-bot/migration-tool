<?php
/**
 * template-parts/hero.php
 */
$bg_image        = get_field('hero_bg_image', 'option');
$rating_value    = get_field('hero_rating_value', 'option');
$rating_text     = get_field('hero_rating_text', 'option');
$title           = get_field('hero_title', 'option');
$usp_items       = get_field('hero_usp_items', 'option');

$phone_display   = get_field('global_phone_display', 'option');
$phone_clean     = get_field('global_phone_clean', 'option');

$badge_text      = get_field('hero_badge_text', 'option');
$badge_value     = get_field('hero_badge_value', 'option');
$card_title      = get_field('hero_card_title', 'option');
$card_subtitle   = get_field('hero_card_subtitle', 'option');
$card_usp_items  = get_field('hero_card_usp_items', 'option');
$form_heading    = get_field('hero_form_heading', 'option');
$form_button     = get_field('hero_form_button_text', 'option');
$form_disclaimer = get_field('hero_form_disclaimer', 'option');

$bottom_title    = get_field('hero_bottom_title', 'option');
$bottom_subtitle = get_field('hero_bottom_subtitle', 'option') ?: 'De Beste Service. 100% tevredenheidsgarantie.';

$hero_uid = 'hero-form-' . uniqid();
?>
<style>
.hero {
    margin: 0 auto;
    padding: 24px 16px;
}

.hero__grid {
    display: grid;
    grid-template-columns: 2.1fr 1fr;
    gap: 24px;
}

/* LEFT */
.hero__left {
    position: relative;
    border-radius: 16px;
    min-height: 520px;
    overflow: hidden;
    display: flex;
    justify-content: flex-start;
    align-items: center;
}

/* Performance Fix: Absolute positioned LCP image element replaces CSS background-image */
.hero__left-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    z-index: 0;
}

.hero__left::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(0,0,0,0.65) 0%, rgba(0,0,0,0.25) 55%, rgba(0,0,0,0) 75%);
    z-index: 1;
}

.hero__overlay {
    position: relative;
    z-index: 2;
    padding: clamp(20px, 3vw, 40px);
    max-width: 480px;
}

.hero__rating {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--color-white);
    border-radius: 6px;
    padding: 6px 14px;
    margin-bottom: 18px;
}

.hero__stars {
    color: var(--color-rating-gold); /* Accessibility: Darker gold/amber variant for solid contrast against white backdrops */
    letter-spacing: 1px;
    font-size: 14px;
}

.hero__rating-text {
    font-size: clamp(10px, 2vw, 13px);
    font-weight: 700;
    font-style: italic;
    text-decoration: underline;
    color: var(--color-text);
}

.hero__title {
    color: var(--color-white);
    font-size: clamp(32px, 4.5vw, 48px);
    font-weight: 800;
    line-height: 1.1;
    margin: 0 0 18px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.4);
}

.hero__usp-list {
    list-style: none;
    margin: 0 0 24px;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.hero__usp-list li {
    color: var(--color-white);
    font-style: italic;
    font-weight: 600;
    font-size: clamp(14px, 1.3vw, 17px);
    display: flex;
    align-items: center;
    gap: 8px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

.hero__arrow {
    color: var(--color-accent-light); /* Enhanced contrast indicator arrow */
    font-weight: 900;
}

/* RIGHT CARD */
.hero__right {
    background: var(--color-dark);
    border-radius: 16px;
    padding: clamp(20px, 2.5vw, 32px);
    color: var(--color-white);
}

.hero__badge-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 18px;
}

.hero__badge {
    background: var(--color-white); /* Accessibility Fix: White background with black text hits maximum contrast ratios */
    color: var(--color-dark);
    font-weight: 800;
    font-size: 12px;
    text-transform: uppercase;
    padding: 6px 12px;
    border-radius: 4px;
}

.hero__badge-value {
    font-style: italic;
    font-size: 13px;
    color: #e2e5e9; /* Lightened from cfd3da for distinct contrast checks */
}

.hero__card-title {
    font-size: clamp(20px, 2vw, 24px);
    font-weight: 800;
    margin: 0 0 4px;
}

.hero__card-subtitle {
    color: var(--color-accent-light); /* Soft lightened high-contrast orange choice on deep slate backgrounds */
    font-weight: 800;
    font-size: clamp(16px, 1.6vw, 19px);
    margin: 0 0 18px;
}

.hero__card-usp-list {
    list-style: none;
    margin: 0 0 20px;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.hero__card-usp-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    font-style: italic;
    font-size: 14px;
    color: #f1f3f5;
}

.hero__check {
    background: var(--color-accent-light);
    color: var(--color-dark);
    border-radius: 50%;
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
}

.hero__check::before {
    content: "\2714";
}
.hero__check[aria-hidden="true"]::before {
    speak: none;
}

.hero__divider {
    border: none;
    border-top: 1px solid rgba(255,255,255,0.15);
    margin: 20px 0;
}

.hero__form-heading {
    font-weight: 800;
    font-size: 15px;
    margin: 0 0 12px;
}

.hero__form {
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr;
    gap: 8px;
    margin-bottom: 14px;
}

.hero__form-group {
    position: relative;
    width: 100%;
}

.hero__input {
    padding: 12px 10px;
    border: none;
    border-radius: 4px;
    font-size: 13px;
    background: var(--color-white);
    color: var(--color-text);
    width: 100%;
    box-sizing: border-box;
}

.hero__form-btn {
    grid-column: 1 / -1;
    background: var(--color-primary);
    color: var(--color-white);
    border: none;
    padding: 14px;
    border-radius: 4px;
    font-weight: 800;
    font-style: italic;
    font-size: 15px;
    cursor: pointer;
    text-transform: uppercase;
    text-shadow: 0 1px 1px rgba(0,0,0,0.2);
}

.hero__form-btn:hover {
    background: var(--color-primary-hover);
}

.hero__disclaimer {
    font-size: 11px;
    font-style: italic;
    color: #cdd2da;
    line-height: 1.5;
    margin: 0;
}

/* BOTTOM CTA BAR */
.hero__bottom-bar {
    margin-top: 24px;
    background-color: transparent;
    background-image: linear-gradient(93deg, var(--color-cta-gradient-start) 28%, var(--color-cta-gradient-end) 100%); /* Slightly darkened background base ensures white button/text contrast limits */
    border-radius: 16px;
    padding: clamp(20px, 3vw, 36px) clamp(20px, 4vw, 48px);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
}

.hero__bottom-text h3 {
    color: var(--color-white);
    font-weight: 800;
    font-style: italic;
    font-size: clamp(20px, 2vw, 26px);
    margin: 0 0 4px;
}

.hero__bottom-text p {
    color: var(--color-white);
    font-style: italic;
    font-size: clamp(16px, 1.3vw, 22px);
    margin: 0;
}

.hero__bottom-phone {
    background: var(--color-white);
    color: var(--color-text);
    font-weight: 800;
    font-style: italic;
    font-size: 16px;
    text-decoration: none;
    padding: 14px 22px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.hero__bottom-phone:hover {
    background-color: #f1f3f5;
    color: var(--color-text);
}

/* RESPONSIVE */
@media (max-width: 992px) {
    .hero__grid {
        grid-template-columns: 1fr;
    }
    .hero__left {
        min-height: 320px;
    }
}

@media (max-width: 600px) {
    .hero__overlay {
        max-width: 100%;
    }
    .hero__form {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .hero__form .hero__form-group:first-of-type {
        grid-column: 1 / -1;
    }
    .hero__bottom-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    .hero__bottom-phone {
        align-self: stretch;
        justify-content: center;
    }
}

.hero__form .wpcf7-form-control-wrap {
    display: block;
    width: 100%;
}
.hero__form .wpcf7-spinner {
    position: absolute;
    bottom: -20px;
    left: 0;
    margin: 0;
}

.hero__form .wpcf7-response-output {
    grid-column: 1 / -1; 
    width: 100%;
    box-sizing: border-box;
    margin: 12px 0 0 0 !important; 
    padding: 12px 16px !important;
    border-radius: 6px;
    font-size: 13px;
    font-style: italic;
    line-height: 1.4;
    text-align: left;
}

.hero__form .wpcf7-response-outputbr {
    display: none;
}

.wpcf7 .hidden-fields-container {
    display: none;
}
</style>

<section class="hero site-container" aria-label="<?php esc_attr_e( 'Introduction', 'textdomain' ); ?>">
    <div class="hero__grid">

        <div class="hero__left">
<?php if ( ! empty( $bg_image ) ) : 
    // Safely extract the clean URL string whether ACF returns an Array or a String
    $clean_url = is_array( $bg_image ) ? $bg_image['url'] : $bg_image;

    // Get the ID from the clean URL string
    $image_id = attachment_url_to_postid( $clean_url );

    if ( $image_id ) :
        $img_mobile  = wp_get_attachment_image_url( $image_id, 'hero_mobile' );
        $img_desktop = wp_get_attachment_image_url( $image_id, 'hero_desktop' );
        $alt_text    = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
        
        $src_url     = $img_desktop ? $img_desktop : $clean_url;
        $srcset_html = $img_mobile ? 'srcset="' . esc_url($img_mobile) . ' 600w, ' . esc_url($img_desktop) . ' 1000w"' : '';
    else :
        $src_url     = $clean_url;
        $srcset_html = '';
        $alt_text    = ! empty( $title ) ? $title : '';
    endif;
?>
    <img 
        class="hero__left-img" 
        src="<?php echo esc_url( $src_url ); ?>" 
        <?php echo $srcset_html; ?>
        sizes="(max-width: 992px) 100vw, 831px"
        alt="<?php echo esc_attr( $alt_text ? $alt_text : $title ); ?>"
        width="831"
        height="554"
        fetchpriority="high"
    >
<?php else : ?>
    <img
        class="hero__left-img"
        src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/' . theme_image_slug() . '-hero.webp' ); ?>"
        sizes="(max-width: 992px) 100vw, 831px"
        alt="<?php echo esc_attr( $title ); ?>"
        width="831"
        height="554"
        fetchpriority="high"
    >
<?php endif; ?>

            <div class="hero__overlay">

                <?php if ($rating_value && $rating_text) : ?>
                <div class="hero__rating" >
                    <span class="hero__stars" aria-hidden="true">
                        <?php
                        $full = floor($rating_value);
                        $half = ($rating_value - $full) >= 0.5;
                        for ($i = 0; $i < 5; $i++) {
                            if ($i < $full) echo '★';
                            elseif ($i == $full && $half) echo '★';
                            else echo '☆';
                        }
                        ?>
                    </span>
                    <meta itemprop="ratingValue" content="<?php echo esc_attr($rating_value); ?>">
                    <meta itemprop="bestRating" content="5">
                    <span class="hero__rating-text"><?php echo esc_html($rating_text); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($title) : ?>
                    <h1 class="hero__title"><?php echo nl2br( esc_html($title) ); ?></h1>
                <?php endif; ?>

                <?php if ($usp_items) : ?>
                <ul class="hero__usp-list">
                    <?php foreach ($usp_items as $item) : ?>
                        <li><span class="hero__arrow" aria-hidden="true">»</span><?php echo esc_html($item['text']); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <?php if ($phone_display && $phone_clean) : ?>
                <a href="tel:<?php echo esc_attr($phone_clean); ?>" class="main-cta__btn" aria-label="Bel ons direct op <?php echo esc_attr($phone_display); ?>">
                    <?php echo esc_html($phone_display); ?>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
                <?php endif; ?>

            </div>
        </div>

        <div class="hero__right">

            <?php if ($badge_text) : ?>
            <div class="hero__badge-row">
                <span class="hero__badge"><?php echo esc_html($badge_text); ?></span>
                <?php if ($badge_value) : ?><span class="hero__badge-value"><?php echo esc_html($badge_value); ?></span><?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($card_title) : ?><h2 class="hero__card-title"><?php echo esc_html($card_title); ?></h2><?php endif; ?>
            <?php if ($card_subtitle) : ?><p class="hero__card-subtitle"><?php echo esc_html($card_subtitle); ?></p><?php endif; ?>

            <?php if ($card_usp_items) : ?>
            <ul class="hero__card-usp-list">
                <?php foreach ($card_usp_items as $item) : ?>
                    <li><span class="hero__check" aria-hidden="true"></span><?php echo esc_html($item['text']); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <hr class="hero__divider" aria-hidden="true">

            <?php if ($form_heading) : ?><p class="hero__form-heading" id="<?php echo $hero_uid; ?>-heading"><?php echo esc_html($form_heading); ?></p><?php endif; ?>

            <?php 
            echo do_shortcode('[contact-form-7 id="6ad45cc" title="Hero form" html_class="hero__form"]'); 
            ?>

            <?php if ($form_disclaimer) : ?>
                <p class="hero__disclaimer"><?php echo esc_html($form_disclaimer); ?></p>
            <?php endif; ?>

        </div>
    </div>

    <?php if ($bottom_title || $bottom_subtitle) : ?>
    <div class="hero__bottom-bar" role="group" aria-label="Contact Highlight">
        <div class="hero__bottom-text">
            <?php if ($bottom_title) : ?><h3><?php echo esc_html($bottom_title); ?></h3><?php endif; ?>
            <?php if ($bottom_subtitle) : ?><p><?php echo esc_html($bottom_subtitle); ?></p><?php endif; ?>
        </div>
        <?php if ($phone_display && $phone_clean) : ?>
        <a href="tel:<?php echo esc_attr($phone_clean); ?>" class="main-cta__btn hero__bottom-phone" aria-label="Bel nu: <?php echo esc_attr($phone_display); ?>">
            <?php echo esc_html($phone_display); ?>
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('wpcf7mailsent', function(event) {
        const targetModal = document.getElementById('quote-modal-engine');
        const mountPoint = document.getElementById('modal-iframe-mount-point');
        
        if (targetModal && mountPoint) {
            const targetSrc = targetModal.getAttribute('data-src');

            if (!mountPoint.querySelector('iframe') && targetSrc) {
                const iframe = document.createElement('iframe');
                iframe.className = 'theme-modal-iframe';
                iframe.setAttribute('src', targetSrc);
                iframe.setAttribute('frameborder', '0');
                iframe.setAttribute('title', 'In 2 minuten bent u klaar');
                
                mountPoint.innerHTML = '';
                mountPoint.appendChild(iframe);
            }

            targetModal.classList.add('is-open');
            targetModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
    }, false);
});
</script>