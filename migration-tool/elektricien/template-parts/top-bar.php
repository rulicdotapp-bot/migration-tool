<?php
/**
 * template-parts/top-bar.php
 */
$top_bar_items = get_field('top_bar_items', 'option');
if ( ! $top_bar_items ) {
    $top_bar_items = array(
        array( 'text' => 'Vandaag gebeld = vandaag geholpen' ),
        array( 'text' => 'Transparante tarieven' ),
        array( 'text' => 'Gediplomeerde Elektriciens' ),
        array( 'text' => 'Meer dan 20 jaar ervaring' ),
        array( 'text' => '100% tevredenheidsgarantie.' ),
    );
}
if ( $top_bar_items ) :
?>
<style>
.top-bar {
    background: linear-gradient(90deg, var(--color-primary-light) 0%, var(--color-primary) 50%, var(--color-primary-dark-alt) 100%);
    color: var(--color-white);
    padding: 10px 16px;
    overflow: hidden;
}

.top-bar__track {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: nowrap;
    gap: 36px;
    margin: 0 auto;
}

.top-bar__item {
    font-style: italic;
    font-weight: bold;
    font-size: clamp(12px, 1vw, 14px);
    white-space: nowrap;
    color: var(--color-white);
    display: inline-flex;
    align-items: center;
}

.top-bar__check {
    margin-right: 8px;
    font-style: normal;
    font-weight: 700;
}

.top-bar__item--dup {
    display: none;
}

@media (max-width: 1200px) {
    .top-bar__track {
        justify-content: flex-start;
        flex-wrap: nowrap;
        max-width: 100% !important; 
        width: max-content;
        animation: top-bar-scroll 20s linear infinite;
        gap: 24px;
    }

    .top-bar__item--dup {
        display: inline-flex;
    }
}

.top-bar__check::before {
    content: "\2714"; 
}

.top-bar__check[aria-hidden="true"]::before {
    speak: none;
}

.top-bar__check img.emoji {
    display: none !important; 
}

@keyframes top-bar-scroll {
    from { transform: translateX(0); }
    to   { transform: translateX(-50%); }
}

@media (max-width: 1200px) and (prefers-reduced-motion: reduce) {
    .top-bar__track {
        animation: none;
        overflow-x: auto;
    }
}
</style>

<div class="top-bar" role="region" aria-label="<?php esc_attr_e( 'Highlights', 'textdomain' ); ?>">
    <div class="top-bar__track site-container">
        
        <?php foreach ( $top_bar_items as $item ) : ?>
            <span class="top-bar__item">
                <span class="top-bar__check" aria-hidden="true"></span>
                <?php echo esc_html( $item['text'] ); ?>
            </span>
        <?php endforeach; ?>

        <?php foreach ( $top_bar_items as $item ) : ?>
            <span class="top-bar__item top-bar__item--dup" aria-hidden="true">
                <span class="top-bar__check" aria-hidden="true"></span>
                <?php echo esc_html( $item['text'] ); ?>
            </span>
        <?php endforeach; ?>
        
    </div>
</div>
<?php endif; ?>