<?php
/**
 * template-parts/contact-section.php
 */
$contact_heading = get_field('contact_heading', 'option');
$contact_desc    = get_field('contact_description', 'option');
$contact_usps    = get_field('contact_usps', 'option');
$contact_iframe  = get_field('contact_iframe_code', 'option');

$iframe_src = '';

if ( ! empty( $contact_iframe ) ) {
    libxml_use_internal_errors( true );
    $dom = new DOMDocument();
    
    $dom->loadHTML( mb_convert_encoding( $contact_iframe, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    
    $iframes = $dom->getElementsByTagName( 'iframe' );
    if ( $iframes->length > 0 ) {
        $iframe = $iframes->item( 0 );
        
        // Extract the original source URL for our lazy loader
        $iframe_src = $iframe->getAttribute('src');
        
        if ( ! $iframe->hasAttribute( 'title' ) ) {
            $iframe->setAttribute( 'title', ! empty( $contact_heading ) ? esc_attr( $contact_heading ) : esc_attr__( 'Contactformulier', 'textdomain' ) );
        }
    }
    libxml_clear_errors();
}

$contact_uid = 'contact-sec-' . uniqid();
?>

<style>
.theme-contact-section {
    background-color: var(--color-primary);
    padding: clamp(50px, 7vw, 90px) 16px;
    margin: 0 auto;
}

.theme-contact-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: clamp(40px, 6vw, 80px);
    align-items: flex-start;
}

.theme-contact-info-panel {
    position: sticky;
    top: 40px;
    display: flex;
    flex-direction: column;
}

.theme-contact-heading {
    color: var(--color-white);
    font-size: clamp(24px, 2.6vw, 32px);
    font-weight: 800;
    font-style: italic;
    line-height: 1.25;
    margin: 0 0 20px 0;
}

.theme-contact-description {
    color: var(--color-white);
    font-size: clamp(14px, 1.1vw, 15px);
    line-height: 1.65;
    font-weight: 500;
    margin: 0;
}

.theme-contact-usps {
    list-style: none;
    padding: 0;
    margin: 20px 0 0 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.theme-contact-usp-item {
    position: relative;
    padding-left: 22px;
    color: var(--color-white);
    font-size: clamp(13.5px, 1.05vw, 14.5px);
    font-weight: 600;
}

.theme-contact-usp-item::before {
    content: "\2714";
    position: absolute;
    left: 0;
    top: 0;
}

.theme-contact-form-frame {
    width: 100%;
}

.theme-contact-form-card {
    background-color: var(--color-white);
    border-radius: 12px;
    padding: clamp(10px, 3vw, 25px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.theme-contact-iframe-wrapper {
    position: relative;
    width: 100%;
    min-height: 1150px; 
}

.theme-contact-iframe-wrapper iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100% !important;
    height: 100% !important;
    border: 0 !important;
    display: block;
}

.theme-contact-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    color: #94a3b8;
    border-radius: 8px;
    font-weight: 600;
}

@media (max-width: 991px) {
    .theme-contact-container {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    .theme-contact-info-panel {
        position: static;
    }
}
</style>

<section class="theme-contact-section" itemscope itemtype="https://schema.org/ContactPage" aria-labelledby="<?php echo $contact_uid; ?>-heading">
    <div class="theme-contact-container site-container">
        
        <div class="theme-contact-info-panel">
            <?php if ( $contact_heading ) : ?>
                <h2 class="theme-contact-heading" id="<?php echo $contact_uid; ?>-heading" itemprop="name"><?php echo esc_html( $contact_heading ); ?></h2>
            <?php endif; ?>

            <?php if ( $contact_desc ) : ?>
                <p class="theme-contact-description" itemprop="description">
                    <?php echo nl2br( esc_html( $contact_desc ) ); ?>
                </p>
            <?php endif; ?>

            <?php if ( $contact_usps ) : ?>
                <ul class="theme-contact-usps" role="list">
                    <?php foreach ( $contact_usps as $usp ) : ?>
                        <?php if ( empty($usp['text']) ) continue; ?>
                        <li class="theme-contact-usp-item"><?php echo esc_html( $usp['text'] ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="theme-contact-form-frame">
            <div class="theme-contact-form-card">
                <div id="lazy-contact-frame-container" class="theme-contact-iframe-wrapper" data-src="<?php echo esc_url($iframe_src); ?>" data-title="<?php echo ! empty( $contact_heading ) ? esc_attr( $contact_heading ) : 'Contactformulier'; ?>">
                    <?php if ( ! empty($iframe_src) ) : ?>
                        <div class="theme-contact-placeholder">
                            <p>Agenda laden...</p>
                        </div>
                    <?php else : ?>
                        <div class="theme-contact-placeholder" style="border: 2px dashed #cbd5e1;">
                            <p>📋 Teamleader Form Iframe Missing</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('lazy-contact-frame-container');
    if (!container) return;

    const iframeSrc = container.getAttribute('data-src');
    const iframeTitle = container.getAttribute('data-title');
    if (!iframeSrc) return;

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Build iframe dynamically
                const iframe = document.createElement('iframe');
                iframe.setAttribute('src', iframeSrc);
                iframe.setAttribute('title', iframeTitle);
                iframe.setAttribute('frameborder', '0');
                iframe.setAttribute('scrolling', 'no');
                
                // Clear out loader placeholder and mount iframe
                container.innerHTML = '';
                container.appendChild(iframe);
                
                observer.unobserve(container);
            }
        });
    }, { rootMargin: '200px 0px' });

    observer.observe(container);
});
</script>