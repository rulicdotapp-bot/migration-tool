<?php
/**
 * template-parts/about-section.php
 */
$heading     = get_field('about_heading', 'option');
$intro_text  = get_field('about_intro_text', 'option');
$subheading  = get_field('about_subheading', 'option') ?: 'Waarom kiezen voor onze dienstverlening?';
$subtext     = get_field('about_subtext', 'option') ?: 'Wij hanteren te allen tijde een transparante werkwijze met heldere tarieven vooraf, zodat u nooit voor verrassingen komt te staan.';
$points      = get_field('about_points', 'option');
if ( ! $points ) {
    $points = array(
        array(
            'label' => 'Snelle responstijd:',
            'text'  => 'Bij stormschade of een acute lekkage telt elke minuut. Omdat wij lokaal opereren, zijn we snel bij u om verdere schade te voorkomen.',
        ),
        array(
            'label' => 'Kennis van de regio:',
            'text'  => 'Elke regio heeft zijn eigen bouwstijlen en specifieke weersinvloeden. Uw lokale Elektricien kent de veelvoorkomende constructies in uw wijk en weet precies welke materialen het beste presteren.',
        ),
        array(
            'label' => 'Persoonlijk contact:',
            'text'  => 'U bent geen nummer, maar een buurtgenoot. Wij hechten waarde aan duurzame relaties en leveren werk waar we trots op kunnen zijn.',
        ),
    );
}
$image       = get_field('about_image', 'option');
?>
<style>
.about-section {
    margin: 0 auto;
    padding: clamp(40px, 6vw, 80px) 16px;
}

.about-section__grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: clamp(24px, 5vw, 64px);
    align-items: center;
}

.about-section__heading {
    font-style: italic;
    font-weight: 800;
    font-size: clamp(22px, 2.5vw, 30px);
    color: var(--color-text);
    margin: 0 0 10px;
    line-height: 1.3;
}

.about-section__subheading {
    font-style: italic;
    font-weight: 800;
    font-size: clamp(18px, 2vw, 22px);
    color: var(--color-text);
    margin: 32px 0 10px;
    line-height: 1.3;
}

.about-section__text {
    font-style: normal; 
    font-size: clamp(14px, 1.05vw, 15px);
    line-height: 1.65;
    color: #2d2d2d;
    margin: 0 0 16px;
}

.about-section__list {
    margin: 20px 0 0;
    padding-left: 0;
    list-style: none; /* Custom layout for clean spacing alignment */
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.about-section__list li {
    font-style: normal; /* Fixed: standard list text should be upright */
    font-size: clamp(14px, 1.05vw, 15px);
    line-height: 1.6;
    color: #2d2d2d;
    position: relative;
    padding-left: 20px;
}

/* Custom styled bullet marker to stay clean and sharp */
.about-section__list li::before {
    content: "•";
    color: var(--color-text);
    font-weight: bold;
    position: absolute;
    left: 0;
    top: 0;
}

/* Explicitly mutes custom pseudo bullets from being read out by screens and AI engines */
.about-section__list li[aria-hidden="true"]::before {
    speak: none;
}

.about-section__list li strong {
    color: var(--color-text);
    font-weight: 700;
}

.about-section__image img {
    width: 100%;
    height: auto;
    border-radius: 12px;
    display: block;
}

@media (max-width: 991px) {
    .about-section__grid {
        grid-template-columns: 1fr;
        gap: 32px;
    }
    .about-section__image {
        max-width: 550px;
        margin: 0 auto;
    }
}
</style>

<section class="about-section site-container" itemscope itemtype="https://schema.org/AboutPage" aria-labelledby="about-section-title">
    <div class="about-section__grid">

        <div class="about-section__content">
            <?php if ($heading) : ?>
                <h2 class="about-section__heading" id="about-section-title" itemprop="name"><?php echo esc_html($heading); ?></h2>
            <?php endif; ?>

            <?php if ($intro_text) : ?>
                <p class="about-section__text" itemprop="description"><?php echo esc_html($intro_text); ?></p>
            <?php endif; ?>

            <?php if ($subheading) : ?>
                <h3 class="about-section__subheading"><?php echo esc_html($subheading); ?></h3>
            <?php endif; ?>

            <?php if ($subtext) : ?>
                <p class="about-section__text"><?php echo esc_html($subtext); ?></p>
            <?php endif; ?>

            <?php if ($points) : ?>
            <ul class="about-section__list">
                <?php foreach ($points as $point) : ?>
                    <li>
                        <?php if (!empty($point['label'])) : ?>
                            <strong><?php echo esc_html($point['label']); ?></strong>
                        <?php endif; ?>
                        <?php echo ' ' . esc_html($point['text']); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

<?php if ( ! empty( $image ) && is_array( $image ) ) : 
    $image_id = $image['ID'];
    
    // Pull the non-hard-cropped vertical variations
    $img_mobile  = wp_get_attachment_image_url( $image_id, 'about_vertical_mobile' );
    $img_desktop = wp_get_attachment_image_url( $image_id, 'about_vertical_desktop' );
    
    $src_url     = $img_desktop ? $img_desktop : $image['url'];
    $srcset_html = $img_mobile ? 'srcset="' . esc_url($img_mobile) . ' 400w, ' . esc_url($img_desktop) . ' 500w"' : '';
    $alt_text    = ! empty( $image['alt'] ) ? $image['alt'] : ( ! empty( $heading ) ? $heading : '' );

    // Grab the exact crop metadata so width/height matching properties are perfectly proportional
    $meta        = wp_get_attachment_metadata($image_id);
    $width       = ! empty($meta['sizes']['about_vertical_desktop']['width']) ? $meta['sizes']['about_vertical_desktop']['width'] : '462';
    $height      = ! empty($meta['sizes']['about_vertical_desktop']['height']) ? $meta['sizes']['about_vertical_desktop']['height'] : '817';
?>
    <div class="about-section__image">
        <img 
            src="<?php echo esc_url( $src_url ); ?>" 
            <?php echo $srcset_html; ?>
            sizes="(max-width: 600px) 100vw, 462px"
            alt="<?php echo esc_attr( $alt_text ); ?>"
            width="<?php echo esc_attr($width); ?>"
            height="<?php echo esc_attr($height); ?>"
            style="width: 100%; height: auto; object-fit: contain; border-radius: 16px;"
            loading="lazy"
            itemprop="image"
        >
    </div>
<?php else : ?>
    <div class="about-section__image">
        <img
            src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/' . theme_image_slug() . '-about.webp' ); ?>"
            sizes="(max-width: 600px) 100vw, 462px"
            alt="<?php echo esc_attr( $heading ); ?>"
            width="462"
            height="817"
            style="width: 100%; height: auto; object-fit: contain; border-radius: 16px;"
            loading="lazy"
            itemprop="image"
        >
    </div>
<?php endif; ?>

    </div>
</section>