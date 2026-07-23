<?php
/**
 * template-parts/service-area-section.php
 */
$heading        = get_field('map_heading', 'option');
$neighborhoods = get_field('map_neighborhoods', 'option');
$intro_text    = get_field('map_intro', 'option') ?: 'Onze lokale aanwezigheid biedt u grote voordelen:';
$bullets       = get_field('map_bullets', 'option');
if ( ! $bullets ) {
    $bullets = array(
        array(
            'title' => 'Snelle aanrijtijden',
            'text'  => 'Bij spoedgevallen, zoals ernstige storingen of stormschade, hoeft u niet lang te wachten. Omdat onze Elektriciens lokaal opereren, zijn ze vaak binnen korte tijd bij u op locatie.',
        ),
        array(
            'title' => 'Kennis van de regio',
            'text'  => 'Onze experts zijn bekend met de lokale bouwstijlen en specifieke omstandigheden in uw omgeving.',
        ),
        array(
            'title' => 'Geen hoge voorrijkosten',
            'text'  => 'Doordat wij altijd dichtbij zijn, kunnen we de kosten voor u beheersbaar houden.',
        ),
    );
}
$phone_number  = get_field('global_phone_display', 'option');
$map_iframe    = get_field('map_iframe', 'option');


if ( ! empty( $map_iframe ) ) {
    libxml_use_internal_errors( true );
    $dom = new DOMDocument();
    $dom->loadHTML( mb_convert_encoding( $map_iframe, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    
    $iframes = $dom->getElementsByTagName( 'iframe' );
    if ( $iframes->length > 0 ) {
        $iframe = $iframes->item( 0 );
        
        if ( ! $iframe->hasAttribute( 'title' ) ) {
            $iframe->setAttribute( 'title', ! empty( $heading ) ? esc_attr( $heading ) : esc_attr__( 'Servicegebied kaart', 'textdomain' ) );
        }
        if ( ! $iframe->hasAttribute( 'loading' ) ) {
            $iframe->setAttribute( 'loading', 'lazy' );
        }
        $map_iframe = $dom->saveHTML();
    }
    libxml_clear_errors();
}

$map_uid = 'map-sec-' . uniqid();
?>

<style>
.theme-service-area-section {
    background-color: var(--color-white);
    padding: clamp(50px, 7vw, 90px) 16px;
    margin: 0 auto;
}

.theme-service-area-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1.05fr 0.95fr;
    gap: clamp(40px, 5vw, 65px);
    align-items: flex-start;
}

.theme-service-area-content {
    display: flex;
    flex-direction: column;
}

.theme-service-area-heading {
    color: var(--color-dark);
    font-size: clamp(23px, 2.5vw, 30px);
    font-weight: 800;
    font-style: italic;
    line-height: 1.25;
    margin: 0 0 24px 0;
}

/* 3-Column Neighborhood List Grid */
.theme-neighborhoods-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px 16px;
    margin: 0 0 32px 0;
    padding: 0;
    list-style: none;
}

.theme-neighborhood-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--color-dark);
    font-size: clamp(13.5px, 1.05vw, 14.5px);
    font-style: italic;
    font-weight: 500;
    line-height: 1.4;
}

.theme-neighborhood-item__icon {
    color: var(--color-primary);
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
}

.theme-service-area-intro {
    color: var(--color-dark);
    font-size: clamp(14px, 1.1vw, 15px);
    font-weight: 500;
    margin: 0 0 10px 0;
    line-height: 1.5;
}

/* Features Bullets List */
.theme-service-area-bullets {
    list-style: none;
    padding: 0;
    margin: 0 0 20px 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.theme-service-area-bullet {
    position: relative;
    padding-left: 16px;
    color: #4e5d78;
    font-size: clamp(13.5px, 1.05vw, 14.5px);
    line-height: 1.6;
}

.theme-service-area-bullet::before {
    content: "•";
    position: absolute;
    left: 0;
    top: -1px;
    color: var(--color-dark);
    font-size: 16px;
}

.theme-service-area-bullet strong {
    color: var(--color-dark);
    font-weight: 700;
}


/* Map Right Frame Layout */
.theme-service-area-map {
    width: 100%;
    position: sticky;
    top: 40px;
}

.theme-service-area-map__wrapper {
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
    background: #f8fafc;
}

.theme-service-area-map__wrapper iframe {
    width: 100% !important;
    height: 100% !important;
    border: 0 !important;
    display: block;
}

@media (max-width: 991px) {
    .theme-service-area-container {
        grid-template-columns: 1fr;
        gap: 45px;
    }
    .theme-service-area-map {
        position: static;
    }
    .theme-service-area-map__wrapper {
        aspect-ratio: 16 / 11;
    }
}

@media (max-width: 575px) {
    .theme-neighborhoods-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
}
</style>

<section class="theme-service-area-section" aria-labelledby="<?php echo $map_uid; ?>-heading">
    <div class="theme-service-area-container site-container">
        
        <div class="theme-service-area-content">
            <?php if ( $heading ) : ?>
                <h2 class="theme-service-area-heading" id="<?php echo $map_uid; ?>-heading"><?php echo esc_html( $heading ); ?></h2>
            <?php endif; ?>

            <?php if ( $neighborhoods ) : ?>
                <ul class="theme-neighborhoods-grid" role="list" aria-label="<?php esc_attr_e( 'Werkgebieden', 'textdomain' ); ?>">
                    <?php foreach ( $neighborhoods as $item ) : ?>
                        <?php if ( empty($item['name']) ) continue; ?>
                        
                        <li class="theme-neighborhood-item" itemscope itemtype="https://schema.org/Place">
                            <span class="theme-neighborhood-item__icon" aria-hidden="true">
                                <svg width="14" height="18" viewBox="0 0 14 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M7 0C3.13 0 0 3.13 0 7C0 12.25 7 20 7 20C7 20 14 12.25 14 7C14 3.13 10.87 0 7 0ZM7 9.5C5.62 9.5 4.5 8.38 4.5 7C4.5 5.62 5.62 4.5 7 4.5C8.38 4.5 9.5 5.62 9.5 7C9.5 8.38 8.38 9.5 7 9.5Z"/>
                                </svg>
                            </span>
                            <span itemprop="name"><?php echo esc_html( $item['name'] ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ( $intro_text ) : ?>
                <p class="theme-service-area-intro"><?php echo esc_html( $intro_text ); ?></p>
            <?php endif; ?>

            <?php if ( $bullets ) : ?>
                <ul class="theme-service-area-bullets" role="list">
                    <?php foreach ( $bullets as $bullet ) : ?>
                        <?php if ( empty($bullet['title']) ) continue; ?>
                        <li class="theme-service-area-bullet">
                            <strong><?php echo esc_html( $bullet['title'] ); ?>:</strong> 
                            <span><?php echo esc_html( $bullet['text'] ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ( $phone_number ) : ?>
                <a style="width: fit-content;" href="tel:<?php echo esc_attr(str_replace(' ', '', $phone_number)); ?>" class="main-cta__btn" aria-label="<?php echo esc_attr( sprintf( __( 'Bel ons direct via %s', 'textdomain' ), $phone_number ) ); ?>">
                    <?php echo esc_html( $phone_number ); ?>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            <?php endif; ?>
        </div>

        <div class="theme-service-area-map">
            <div class="theme-service-area-map__wrapper">
                <?php if ( $map_iframe ) : ?>
                    <?php echo ( $map_iframe ); ?>
                <?php else : ?>
                    <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f1f5f9; color:#94a3b8; font-weight:600;" aria-hidden="true">Google Maps Embed Container</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>