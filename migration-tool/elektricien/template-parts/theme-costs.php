<?php
/**
 * template-parts/costs-section.php
 */
$subheadline  = get_field('costs_subheadline', 'option');
$heading      = get_field('costs_heading', 'option');
$description  = get_field('costs_description', 'option');
$intro_text   = get_field('costs_intro', 'option');
$intro_textP  = get_field('Factors_description', 'option');
$usps         = get_field('costs_usps', 'option');
$bullets      = get_field('costs_bullets', 'option');
$side_image   = get_field('costs_side_image', 'option');
$global_phone = get_field('global_phone_display', 'option'); 

$costs_uid = 'costs-sec-' . uniqid();
?>

<style>
.theme-costs-section {
    background-color: var(--color-white);
    padding: clamp(50px, 7vw, 90px) 16px;
    margin: 0 auto;
}

.theme-costs-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: clamp(40px, 6vw, 70px);
    align-items: center;
}

.theme-costs-content {
    display: flex;
    flex-direction: column;
}

.theme-costs-content__subheadline {
    color: var(--color-primary);
    font-size: clamp(14px, 1.1vw, 16px);
    font-weight: 600;
    font-style: italic;
    margin: 0 0 12px 0;
    line-height: 1.3;
}

.theme-costs-content__heading {
    color: var(--color-dark);
    font-size: clamp(24px, 2.6vw, 32px);
    font-weight: 800;
    font-style: italic;
    line-height: 1.25;
    margin: 0 0 18px 0;
}

.theme-costs-content__desc {
    font-size: clamp(14px, 1.1vw, 15px);
    line-height: 1.65;
    margin: 0 0 20px 0;
}

.theme-costs-content__intro {
    color: var(--color-dark);
    font-size: clamp(16px, 1.2vw, 18px);
    font-weight: 700;
    font-style: italic;
    line-height: 1.4;
    margin: 0 0 10px 0;
}

.theme-costs-bullets {
    list-style: none;
    padding: 0;
    margin: 0 0 32px 0;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.theme-costs-bullet-item {
    position: relative;
    padding-left: 24px;
    font-size: clamp(13.5px, 1.05vw, 14.5px);
    line-height: 1.6;
}

.theme-costs-bullet-item::before {
    content: "•";
    position: absolute;
    left: 4px;
    top: -2px;
    color: var(--color-dark);
    font-size: 20px;
}

.theme-costs-bullet-item strong {
    color: var(--color-dark);
    font-weight: 700;
}

.theme-costs-usps {
    list-style: none;
    padding: 0;
    margin: 0 0 24px 0;
    display: flex;
    flex-wrap: wrap;
    gap: 10px 20px;
}

.theme-costs-usp-item {
    position: relative;
    padding-left: 20px;
    color: var(--color-dark);
    font-size: clamp(13.5px, 1.05vw, 14.5px);
    font-weight: 600;
}

.theme-costs-usp-item::before {
    content: "\2714";
    position: absolute;
    left: 0;
    top: 0;
    color: var(--color-primary);
    font-size: 12px;
}

.theme-costs-cta {
    align-self: flex-start;
}

.theme-costs-media {
    width: 100%;
}

.theme-costs-media__frame {
    width: 100%;
    aspect-ratio: 10 / 9;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
}

.theme-costs-media__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

@media (max-width: 991px) {
    .theme-costs-container {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    .theme-costs-media {
        order: -1;
    }
    .theme-costs-media__frame {
        aspect-ratio: 16 / 11;
    }
}
</style>

<section class="theme-costs-section" aria-labelledby="<?php echo $costs_uid; ?>-heading">
    <div class="theme-costs-container site-container">
        
        <div class="theme-costs-content">
            <?php if ( $subheadline ) : ?>
                <span class="theme-costs-content__subheadline"><?php echo esc_html( $subheadline ); ?></span>
            <?php endif; ?>

            <?php if ( $heading ) : ?>
                <h2 class="theme-costs-content__heading" id="<?php echo $costs_uid; ?>-heading"><?php echo esc_html( $heading ); ?></h2>
            <?php endif; ?>

            <?php if ( $description ) : ?>
                <p class="theme-costs-content__desc"><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>

            <?php if ( $intro_text ) : ?>
                <h3 class="theme-costs-content__intro"><?php echo esc_html( $intro_text ); ?></h3>
            <?php endif; ?>
            
            <?php if ( $intro_textP ) : ?>
                <p class="theme-costs-content__desc"><?php echo esc_html( $intro_textP ); ?></p>
            <?php endif; ?>

            <?php if ( $usps ) : ?>
                <ul class="theme-costs-usps" role="list">
                    <?php foreach ( $usps as $usp ) : ?>
                        <?php if ( empty($usp['text']) ) continue; ?>
                        <li class="theme-costs-usp-item"><?php echo esc_html( $usp['text'] ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ( $bullets ) : ?>
                <ul class="theme-costs-bullets" role="list" aria-label="<?php esc_attr_e( 'Kostenoverzicht criteria', 'textdomain' ); ?>">
                    <?php foreach ( $bullets as $bullet ) : ?>
                        <?php if ( empty($bullet['title']) ) continue; ?>
                        
                        <li class="theme-costs-bullet-item" itemscope itemtype="https://schema.org/PriceSpecification">
                            <strong itemprop="name"><?php echo esc_html( $bullet['title'] ); ?></strong> – 
                            <span itemprop="description"><?php echo esc_html( $bullet['text'] ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ( $global_phone ) : 
                $clean_phone = preg_replace('/[^0-9+]/', '', $global_phone); 
            ?>
                <div class="theme-costs-cta">
                    <a href="tel:<?php echo esc_attr( $clean_phone ); ?>" class="main-cta__btn" aria-label="<?php echo esc_attr( sprintf( __( 'Bel ons op %s', 'textdomain' ), $global_phone ) ); ?>">
                        <?php echo esc_html( $global_phone ); ?>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="theme-costs-media">
            <div class="theme-costs-media__frame">
                <?php if ( $side_image ) : ?>
                    <img class="theme-costs-media__img" src="<?php echo esc_url( $side_image ); ?>" alt="<?php echo esc_attr( $heading ); ?>" loading="lazy">
                <?php else : ?>
                    <img class="theme-costs-media__img" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/' . theme_image_slug() . '-costs.jpg' ); ?>" alt="<?php echo esc_attr( $heading ); ?>" loading="lazy">
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>