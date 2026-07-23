<?php
/**
 * template-parts/reviews-section.php
 */
$heading    = get_field('reviews_heading', 'option') ?: 'Dit is wat klanten over ons zeggen.';
$subheading = get_field('reviews_subheading', 'option') ?: 'Wij worden op verschillende platforms beoordeeld door onze klanten.';
$reviews    = get_field('reviews_list', 'option');

$reviews_uid = 'reviews-sec-' . uniqid();

if ( ! empty( $reviews ) && is_array( $reviews ) ) {
    $total_reviews = count( $reviews );
    
    $site_url  = home_url( '/' );
    $site_name = get_bloginfo( 'name' );

    $schema_data = [
        "@context" => "https://schema.org",
        "@type"    => "Electrician", 
        "@id"      => esc_url( $site_url . '#business' ),
        "name"     => esc_html( $site_name ),
        "url"      => esc_url( $site_url ),
        "aggregateRating" => [
            "@type"       => "AggregateRating",
            "ratingValue" => "5", 
            "bestRating"  => "5",
            "reviewCount" => (string) $total_reviews
        ],
        "review" => []
    ];

    // Loop through and format the reviews for the JSON payload
    foreach ( $reviews as $review ) {
        $schema_data['review'][] = [
            "@type" => "Review",
            "author" => [
                "@type" => "Person",
                "name"  => esc_html( $review['name'] )
            ],
            "reviewRating" => [
                "@type"       => "Rating",
                "ratingValue" => "5",
                "bestRating"  => "5"
            ],
            "reviewBody" => esc_html( $review['text'] )
        ];
    }

    echo '<script type="application/ld+json">' . wp_json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
}
?>

<style>
.reviews-section {
    margin: 0 auto;
    padding: clamp(40px, 6vw, 80px) 16px;
    overflow: hidden;
}

.reviews-section__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: clamp(24px, 4vw, 40px);
    gap: 24px;
}

.reviews-section__title-group {
    max-width: 650px;
}

.reviews-section__heading {
    font-style: italic;
    font-weight: 800;
    font-size: clamp(24px, 2.6vw, 32px);
    color: var(--color-text);
    margin: 0 0 10px;
    line-height: 1.2;
}

.reviews-section__subheading {
    font-style: normal;
    font-weight: 400;
    font-size: clamp(14px, 1.2vw, 17px);
    color: #4a4a4a;
    margin: 0;
    line-height: 1.5;
}

.reviews-section__nav {
    display: flex;
    gap: 12px;
    flex-shrink: 0;
}

.reviews-section__btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background-color: var(--color-primary);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s ease, transform 0.1s ease;
}

.reviews-section__btn:hover {
    background-color: var(--color-primary-hover);
}

.reviews-section__btn:active {
    transform: scale(0.95);
}

.reviews-section__btn svg {
    width: 18px;
    height: 18px;
    fill: none;
    stroke: var(--color-white);
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.reviews-section__container {
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    margin: 0 -16px;
    padding: 4px 16px;
}

.reviews-section__container::-webkit-scrollbar {
    display: none;
}

.reviews-section__track {
    display: flex;
    gap: 24px;
}

.review-card {
    flex: 0 0 27%;
    background: var(--color-white);
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 32px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.review-card__stars {
    display: flex;
    gap: 4px;
    color: var(--color-primary);
    font-size: 18px;
    margin-bottom: 20px;
    line-height: 1;
}

.review-card__text {
    font-style: italic;
    font-size: clamp(13px, 1vw, 14.5px);
    line-height: 1.65;
    color: #2d2d2d;
    margin: 0 0 24px;
    flex-grow: 1;
}

.review-card__author {
    margin-top: auto;
}

.review-card__name {
    font-style: normal;
    font-weight: 700;
    font-size: 15px;
    color: var(--color-text);
    margin: 0 0 4px;
}

.review-card__location {
    font-style: normal;
    font-weight: 400;
    font-size: 13.5px;
    color: #666666;
    margin: 0;
}

@media (max-width: 767px) {
    .reviews-section__header {
        flex-direction: column;
    }
    .review-card {
        flex: 0 0 290px;
        padding: 24px;
    }
}
</style>

<section class="reviews-section site-container" aria-labelledby="<?php echo esc_attr( $reviews_uid ); ?>-heading">
    <div class="reviews-section__header">
        <div class="reviews-section__title-group">
            <?php if ( $heading ) : ?>
                <h2 class="reviews-section__heading" id="<?php echo esc_attr( $reviews_uid ); ?>-heading"><?php echo esc_html( $heading ); ?></h2>
            <?php endif; ?>
            <?php if ( $subheading ) : ?>
                <p class="reviews-section__subheading"><?php echo esc_html( $subheading ); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="reviews-section__nav" role="group" aria-label="<?php esc_attr_e( 'Review navigatie', 'textdomain' ); ?>">
            <button class="reviews-section__btn reviews-section__btn--prev" aria-label="<?php esc_attr_e( 'Vorige beoordelingen', 'textdomain' ); ?>" aria-controls="<?php echo esc_attr( $reviews_uid ); ?>-track">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <button class="reviews-section__btn reviews-section__btn--next" aria-label="<?php esc_attr_e( 'Volgende beoordelingen', 'textdomain' ); ?>" aria-controls="<?php echo esc_attr( $reviews_uid ); ?>-track">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        </div>
    </div>

    <div class="reviews-section__container" id="<?php echo esc_attr( $reviews_uid ); ?>-track">
        <div class="reviews-section__track">
            <?php if ( $reviews ) : foreach ( $reviews as $review ) : ?>
                
                <article class="review-card">
                    <div>
                        <div class="review-card__stars" role="img" aria-label="5 van de 5 sterren">
                            <span aria-hidden="true">★★★★★</span>
                        </div>
                        <p class="review-card__text">
                            <?php echo esc_html( $review['text'] ); ?>
                        </p>
                    </div>
                    <div class="review-card__author">
                        <p class="review-card__name"><?php echo esc_html( $review['name'] ); ?></p>
                        <p class="review-card__location"><?php echo esc_html( $review['location'] ); ?></p>
                    </div>
                </article>

            <?php endforeach; endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {    
    const container = document.getElementById('<?php echo esc_js( $reviews_uid ); ?>-track');    
    const section = container ? container.closest('.reviews-section') : null;
    
    if (!container || !section) return;

    const prevBtn = section.querySelector('.reviews-section__btn--prev');    
    const nextBtn = section.querySelector('.reviews-section__btn--next');        
    
    if (!prevBtn || !nextBtn) return;

    const firstCard = container.querySelector('.review-card');
    let scrollAmount = 340; 

    if (firstCard) {
        scrollAmount = firstCard.offsetWidth + 24;
    }

    window.addEventListener('resize', function() {
        if (firstCard) {
            scrollAmount = firstCard.offsetWidth + 24;
        }
    }, { passive: true });

    nextBtn.addEventListener('click', function() {        
        container.scrollLeft += scrollAmount;    
    });

    prevBtn.addEventListener('click', function() {        
        container.scrollLeft -= scrollAmount;    
    });
});
</script>