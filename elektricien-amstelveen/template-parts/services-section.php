<?php
/**
 * template-parts/services-section.php
 */
$heading    = get_field('services_heading', 'option');
$subheading = get_field('services_subheading', 'option');
$usps       = get_field('services_usps', 'option');
if ( ! $usps ) {
    $usps = array(
        array( 'text' => '24/7 spoedservice bij Storingen' ),
        array( 'text' => 'Lokale Elektricien met kennis' ),
        array( 'text' => 'Transparante prijzen & gratis inspectie' ),
        array( 'text' => 'Vakmanschap met garantie' ),
    );
}
$services   = get_field('services_list', 'option');

$services_uid = 'services-sec-' . uniqid();
?>
<style>
.services-section {
    margin: 0 auto;
    padding: clamp(50px, 7vw, 90px) 16px;
    background-color: #fdefeb; 
    border-radius: 12px;
}

.services-section__header {
    max-width: 820px;
    margin: 0 auto clamp(40px, 6vw, 64px);
    text-align: center;
}

.services-section__heading {
    font-style: italic;
    font-weight: 800;
    font-size: clamp(24px, 2.8vw, 34px);
    color: var(--color-dark);
    line-height: 1.25;
    margin: 0 0 16px;
}

.services-section__subheading {
    font-style: normal;
    font-weight: 400;
    font-size: clamp(14px, 1.1vw, 15.5px);
    line-height: 1.65;
    margin: 0 auto 40px;
}

/* Horizontal USP Row Style */
.services-section__usps {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    max-width: 960px;
    margin: 0 auto;
    padding-top: 12px;
}

.services-usp-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.services-usp-box-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
}

.services-usp-item__box {
    width: 60px;
    height: 60px;
    transform: rotate(-10deg);
    display: flex;
    align-items: center;
    justify-content: center;
}

.services-usp-item__checkmark {
    width: 35px;
    height: 35px;
    fill: none;
    stroke: var(--color-primary);
    stroke-width: 4;
    stroke-linecap: round;
    stroke-linejoin: round;
    transform: rotate(10deg);
}

.services-usp-item__text {
    font-style: italic;
    font-weight: 700;
    font-size: clamp(13px, 1.1vw, 15px);
    line-height: 1.3;
    color: var(--color-dark);
    white-space: pre-line;
    margin: 0;
}

/* Grid Layout Styling */
.services-section__grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 32px;
    max-width: 1200px;
    margin: 0 auto;
}

.service-grid-card {
    background: var(--color-white);
    border: 1px solid #edf2f7;
    border-radius: 12px;
    padding: clamp(24px, 4vw, 44px);
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
}

.service-grid-card__visual {
    width: 100%;
    aspect-ratio: 16 / 10;
    margin-bottom: 28px;
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.service-grid-card__img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.service-grid-card__placeholder {
    color: var(--color-primary);
    font-size: 54px;
}

.service-grid-card__title {
    font-style: italic;
    font-weight: 800;
    font-size: clamp(18px, 1.45vw, 22px);
    color: var(--color-dark);
    line-height: 1.3;
    margin: 0 0 14px;
}

.service-grid-card__desc {
    font-style: normal;
    font-size: 14px;
    line-height: 1.65;
    margin: 0 0 28px;
}


.service-grid-card__accordions {
    margin-top: auto;
    border-top: 1px solid #edf2f7;
}

.service-accordion {
    border-bottom: 1px solid #edf2f7;
}

.service-accordion__trigger {
    list-style: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 0;
    font-style: italic;
    font-weight: 700;
    font-size: 14.5px;
    color: var(--color-dark);
    cursor: pointer;
}

.service-accordion__trigger::-webkit-details-marker {
    display: none;
}

.service-accordion__trigger svg {
    width: 12px;
    height: 12px;
    fill: none;
    stroke: var(--color-dark);
    stroke-width: 3;
    transition: transform 0.2s ease;
}

.service-accordion[open] .service-accordion__trigger svg {
    transform: rotate(90deg);
}

.service-accordion__panel {
    font-style: normal;
    font-size: 13.5px;
    line-height: 1.6;
    padding: 0 0 18px;
    margin: 0;
}

/* Mobile & Tablet Responsiveness */
@media (max-width: 991px) {
    .services-section__grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    .services-section__usps {
        grid-template-columns: repeat(2, 1fr);
        gap: 24px 16px;
    }
}

@media (max-width: 480px) {
    .services-section__usps {
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
}
</style>

<section class="services-section site-container" aria-labelledby="<?php echo $services_uid; ?>-heading">
    <div class="services-section__header">
        <?php if ( $heading ) : ?>
            <h2 class="services-section__heading" id="<?php echo $services_uid; ?>-heading"><?php echo esc_html( $heading ); ?></h2>
        <?php endif; ?>
        <?php if ( $subheading ) : ?>
            <p class="services-section__subheading"><?php echo esc_html( $subheading ); ?></p>
        <?php endif; ?>

        <?php if ( $usps ) : ?>
            <div class="services-section__usps" role="region" aria-label="<?php esc_attr_e( 'Service voordelen', 'textdomain' ); ?>">
                <?php foreach ( $usps as $usp ) : ?>
                    <div class="services-usp-item">
                        <div class="services-usp-box-wrapper" aria-hidden="true">
                            <div class="services-usp-item__box">
                                <svg class="services-usp-item__checkmark" viewBox="0 0 24 24">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                        </div>
                        <p class="services-usp-item__text"><?php echo esc_html( $usp['text'] ); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ( $services ) : ?>
        <div class="services-section__grid">
            <?php foreach ( $services as $service ) : ?>
                
                <article class="service-grid-card" itemscope itemtype="https://schema.org/Service">
                    
                    <div class="service-grid-card__visual">
                        <?php if ( !empty($service['image']) ) : ?>
                            <img class="service-grid-card__img" src="<?php echo esc_url( $service['image'] ); ?>" alt="<?php echo esc_attr( $service['title'] ); ?>" loading="lazy" itemprop="image">
                        <?php else : ?>
                            <div class="service-grid-card__placeholder" aria-hidden="true">⚡</div>
                        <?php endif; ?>
                    </div>

                    <?php if ( !empty($service['title']) ) : ?>
                        <h3 class="service-grid-card__title" itemprop="name"><?php echo esc_html( $service['title'] ); ?></h3>
                    <?php endif; ?>

                    <?php if ( !empty($service['description']) ) : ?>
                        <p class="service-grid-card__desc" itemprop="description"><?php echo esc_html( $service['description'] ); ?></p>
                    <?php endif; ?>

                    <?php if ( !empty($service['faqs']) ) : ?>
                        <div class="service-grid-card__accordions" itemscope itemtype="https://schema.org/FAQPage">
                            <?php foreach ( $service['faqs'] as $faq ) : ?>
                                <?php if( empty($faq['question']) ) continue; ?>
                                
                                <details class="service-accordion" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                                    <summary class="service-accordion__trigger">
                                        <span itemprop="name"><?php echo esc_html( $faq['question'] ); ?></span>
                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                    </summary>
                                    <div class="service-accordion__panel-wrapper" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                                        <p class="service-accordion__panel" itemprop="text"><?php echo esc_html( $faq['answer'] ); ?></p>
                                    </div>
                                </details>

                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>