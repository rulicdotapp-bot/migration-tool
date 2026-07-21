<?php
$subheadline = get_field('why_choose_subheadline', 'option') ?: 'Dringende een Elektricien nodig? Wij zijn 24/7 bereikbaar';
$heading     = get_field('why_choose_heading', 'option');
$description = get_field('about_description', 'option');
$intro_text  = get_field('why_choose_intro', 'option');
$bullets     = get_field('why_choose_bullets', 'option');
if ( ! $bullets ) {
    $bullets = array(
        array(
            'title' => 'Snelle service',
            'text'  => 'u bent snel geholpen door een elektricien uit de buurt',
        ),
        array(
            'title' => 'Vakkundig en veilig',
            'text'  => 'alle werkzaamheden worden uitgevoerd volgens de geldende normen en voorschriften',
        ),
        array(
            'title' => 'Persoonlijk en eerlijk advies',
            'text'  => 'u krijgt altijd een transparant advies dat past bij uw situatie',
        ),
    );
}
$side_image  = get_field('about_side_image', 'option');
$global_phone = get_field('global_phone_display', 'option');
$rating_value = get_field('hero_rating_value', 'option');
$rating_text  = get_field('hero_rating_text', 'option');
?>

<style>
.why-choose-section {
    background-color: var(--color-white);
    padding: clamp(50px, 7vw, 90px) 16px;
    margin: 0 auto;
}

.why-choose-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: clamp(40px, 6vw, 70px);
    align-items: center;
}

.why-choose-content {
    display: flex;
    flex-direction: column;
}

.why-choose-content__subheadline {
    color: var(--color-primary);
    font-size: clamp(14px, 1.1vw, 16px);
    font-weight: 600;
    font-style: italic;
    margin: 0 0 12px 0;
    line-height: 1.3;
}

.why-choose-content__heading {
    color: var(--color-dark);
    font-size: clamp(24px, 2.6vw, 32px);
    font-weight: 800;
    font-style: italic;
    line-height: 1.25;
    margin: 0 0 18px 0;
}

.why-choose-content__desc {
    font-size: clamp(14px, 1.1vw, 15px);
    line-height: 1.65;
    margin: 0 0 20px 0;
}

.why-choose-content__intro {
    font-size: clamp(14px, 1.1vw, 15px);
    line-height: 1.65;
    margin: 0 0 28px 0;
}

.why-choose-bullets {
    list-style: none;
    padding: 0;
    margin: 0 0 32px 0;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.why-choose-bullet-item {
    position: relative;
    padding-left: 28px;
    color: var(--color-dark);
    font-size: clamp(13.5px, 1.05vw, 14.5px);
    line-height: 1.5;
    font-weight: 500;
}

.why-choose-bullet-item svg {
    position: absolute;
    left: 0;
    top: 2px;
    width: 18px;
    height: 18px;
    fill: none;
    stroke: var(--color-primary);
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.why-choose-bullet-item strong {
    font-weight: 700;
    font-style: italic;
}

.why-choose-bullet-item span {
    font-weight: 400;
}

.why-choose-cta {
    align-self: flex-start;
}

.why-choose-media {
    width: 100%;
}

.why-choose-media__frame {
    position: relative;
    width: 100%;
    aspect-ratio: 10 / 9;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
}

.why-choose-media__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.why-choose-google-card {
    position: absolute;
    right: 16px;
    bottom: 16px;
    background: var(--color-white);
    border-radius: 10px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    max-width: calc(100% - 32px);
}

.why-choose-google-card__logo {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.why-choose-google-card__text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.why-choose-google-card__stars {
    color: var(--color-rating-gold);
    font-size: 12px;
    letter-spacing: 1px;
}

.why-choose-google-card__rating {
    font-size: 11px;
    font-weight: 700;
    font-style: italic;
    color: var(--color-dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (max-width: 991px) {
    .why-choose-container {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    .why-choose-media {
        order: -1;
    }
    .why-choose-media__frame {
        aspect-ratio: 16 / 11;
    }
}
</style>

<section class="why-choose-section">
    <div class="why-choose-container site-container">
        
        <div class="why-choose-content">
            <?php if ( $subheadline ) : ?>
                <span class="why-choose-content__subheadline"><?php echo esc_html( $subheadline ); ?></span>
            <?php endif; ?>

            <?php if ( $heading ) : ?>
                <h2 class="why-choose-content__heading"><?php echo esc_html( $heading ); ?></h2>
            <?php endif; ?>

            <?php if ( $description ) : ?>
                <p class="why-choose-content__desc"><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>

            <?php if ( $intro_text ) : ?>
                <p class="why-choose-content__intro"><?php echo esc_html( $intro_text ); ?></p>
            <?php endif; ?>

            <?php if ( $bullets ) : ?>
                <ul class="why-choose-bullets">
                    <?php foreach ( $bullets as $bullet ) : ?>
                        <?php if ( empty($bullet['title']) ) continue; ?>
                        <li class="why-choose-bullet-item">
                            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <strong><?php echo esc_html( $bullet['title'] ); ?>:</strong> 
                            <span><?php echo esc_html( $bullet['text'] ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ( $global_phone ) : 
                $clean_phone = preg_replace('/[^0-9+]/', '', $global_phone); 
            ?>
                <div class="why-choose-cta">
                    <a href="tel:<?php echo esc_attr( $clean_phone ); ?>" class="main-cta__btn">
                        <?php echo esc_html( $global_phone ); ?>
                        <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="why-choose-media">
            <div class="why-choose-media__frame">
<?php if ( ! empty( $side_image ) ) : 
    $side_image_id = attachment_url_to_postid( $side_image );

    if ( $side_image_id ) :
        $img_mobile  = wp_get_attachment_image_url( $side_image_id, 'service_card_mobile' );
        $img_desktop = wp_get_attachment_image_url( $side_image_id, 'service_card_desktop' );
        $alt_text    = get_post_meta( $side_image_id, '_wp_attachment_image_alt', true );
        
        $src_url     = $img_desktop ? $img_desktop : $side_image;
        $srcset_html = $img_mobile ? 'srcset="' . esc_url($img_mobile) . ' 600w, ' . esc_url($img_desktop) . ' 400w"' : '';
    else :
        $src_url     = $side_image;
        $srcset_html = '';
        $alt_text    = ! empty( $heading ) ? $heading : '';
    endif;
?>
    <img 
        class="why-choose-media__img" 
        src="<?php echo esc_url( $src_url ); ?>" 
        <?php echo $srcset_html; ?>
        sizes="(max-width: 600px) 100vw, 400px"
        alt="<?php echo esc_attr( $alt_text ? $alt_text : $heading ); ?>"
        width="395"
        height="263"
        loading="lazy"
    >
<?php else : ?>
    <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f8fafc; color:#cbd5e1; font-size:40px;">📷</div>
<?php endif; ?>

<?php if ( $rating_value && $rating_text ) : ?>
    <div class="why-choose-google-card">
        <svg class="why-choose-google-card__logo" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
            <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12 c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24 c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
            <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039 l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
            <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36 c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
            <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571 c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
        </svg>
        <div class="why-choose-google-card__text">
            <span class="why-choose-google-card__stars" aria-hidden="true">
                <?php
                $full = floor( $rating_value );
                $half = ( $rating_value - $full ) >= 0.5;
                for ( $i = 0; $i < 5; $i++ ) {
                    if ( $i < $full ) { echo '★'; }
                    elseif ( $i == $full && $half ) { echo '★'; }
                    else { echo '☆'; }
                }
                ?>
            </span>
            <span class="why-choose-google-card__rating"><?php echo esc_html( $rating_text ); ?></span>
        </div>
    </div>
<?php endif; ?>
            </div>
        </div>

    </div>
</section>