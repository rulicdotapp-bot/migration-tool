<?php
/**
 * footer.php
 */
$footer_logo  = get_field('footer_logo', 'option');
$footer_desc  = get_field('footer_description', 'option') ?: trim( get_field('hero_title', 'option') . ' staat 24/7 voor u klaar voor alle soorten elektrotechnische werkzaamheden, storingen en installaties.' );
$footer_phone = get_field('global_phone_display', 'option');
$copyright    = get_field('footer_copyright', 'option');
?>

<style>
.theme-site-footer {
    background-color: var(--color-dark);
    color: var(--color-white);
    padding: clamp(40px, 5vw, 60px) 16px 30px 16px;
    font-family: inherit;
    max-width:100%;
}

.theme-footer-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 40px;
}

.theme-footer-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 30px;
}

.theme-footer-brand {
    max-width: 400px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.theme-footer-logo {
    max-height: 45px;
    width: 200px;
    object-fit: contain;
    display: block;
}

/* Fallback stylized logo text if no image uploaded */
.theme-footer-logo-text {
    font-size: 22px;
    font-weight: 800;
    font-style: italic;
    color: #ffffff;
    text-decoration: none;
}

.theme-footer-logo-text span {
    color: var(--color-primary);
}

.theme-footer-description {
    color: #94a3b8;
    font-size: 14px;
    line-height: 1.6;
    margin: 0;
}

/* Footer Call Actions */
.theme-footer-actions {
    display: flex;
    align-items: center;
}

.theme-footer-btn {
    background-color: var(--color-primary);
    color: var(--color-white);
    font-size: clamp(15px, 1.1vw, 16px);
    font-weight: 800;
    font-style: italic;
    text-decoration: none;
    padding: 14px 28px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    transition: background-color 0.2s ease;
}

.theme-footer-btn:hover {
    background-color: var(--color-primary-hover);
}

/* Bottom Divider Bar & Copyrights */
.theme-footer-divider {
    height: 1px;
    background-color: #2d3748;
    width: 100%;
    margin: 0;
}

.theme-footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.theme-footer-copyright {
    color: #94a3b8;
    font-size: 13.5px;
    margin: 0;
}

.theme-footer-legal-links {
    display: flex;
    gap: 24px;
    list-style: none;
    padding: 0;
    margin: 0;
}

.theme-footer-legal-links a {
    color: #94a3b8;
    font-size: 13.5px;
    text-decoration: none;
    transition: color 0.2s ease;
}

.theme-footer-legal-links a:hover {
    color: #ffffff;
}

@media (max-width: 767px) {
    .theme-footer-top {
        flex-direction: column;
        align-items: flex-start;
    }
    .theme-footer-actions {
        width: 100%;
    }
    .theme-footer-btn {
        width: 100%;
        justify-content: center;
    }
    .theme-footer-bottom {
        flex-direction: column-reverse;
        align-items: flex-start;
    }
}
</style>

<footer class="theme-site-footer" itemscope itemtype="https://schema.org/LocalBusiness">
    <div class="theme-footer-container">
        
        <div class="theme-footer-top">
            <div class="theme-footer-brand">
                <?php if ( $footer_logo ) : ?>
                    <img class="theme-footer-logo" src="<?php echo esc_url( $footer_logo ); ?>" alt="<?php echo esc_attr( get_bloginfo('name') ); ?>" itemprop="logo" loading="lazy">
                <?php else : ?>
                    <img class="theme-footer-logo" src="<?php echo esc_url( theme_static_image_url( 'logo-footer' ) ); ?>" alt="<?php echo esc_attr( get_bloginfo('name') ); ?>" itemprop="logo" loading="lazy">
                <?php endif; ?>

                <meta itemprop="url" content="<?php echo esc_url( home_url('/') ); ?>">

                <?php if ( $footer_desc ) : ?>
                    <p class="theme-footer-description"><?php echo esc_html( $footer_desc ); ?></p>
                <?php endif; ?>
            </div>

            <div class="theme-footer-actions">
                <?php if ( $footer_phone ) : ?>
                    <?php $clean_phone = preg_replace('/[^0-9+]/', '', $footer_phone); ?>
                    <a class="theme-footer-btn" href="tel:<?php echo esc_attr( $clean_phone ); ?>" itemprop="telephone" aria-label="<?php echo esc_attr( sprintf( __( 'Bel ons op %s', 'textdomain' ), $footer_phone ) ); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                            <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 0 0-1.01.24l-2.2 2.2c-2.83-1.44-5.15-3.75-6.59-6.59l2.2-2.21c.28-.26.36-.65.25-1.02C8.79 6.34 8.59 5.15 8.59 3.92c0-.55-.45-1-1-1H4.01c-.55 0-1 .45-1 1 0 9.39 7.63 17.02 17 17.02.55 0 1-.45 1-1v-3.56c0-.55-.45-1-1-1z"/>
                        </svg>
                        <span><?php echo esc_html( $footer_phone ); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <hr class="theme-footer-divider">

        <div class="theme-footer-bottom">
            <p class="theme-footer-copyright">
                <?php echo esc_html( $copyright ); ?>
            </p>
            
            <nav aria-label="<?php esc_attr_e( 'Juridische links', 'textdomain' ); ?>">

            </nav>
        </div>

    </div>
</footer>
<?php get_template_part('template-parts/quote-modal'); ?>
<?php wp_footer(); ?>
</body>
</html>