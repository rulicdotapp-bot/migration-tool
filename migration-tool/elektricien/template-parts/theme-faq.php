<?php
/**
 * template-parts/faq-section.php
 */
$faq_heading = get_field('faq_heading', 'option');
$faq_items   = get_field('faq_items', 'option');

$faq_uid = 'faq-sec-' . uniqid();
?>

<style>
.theme-faq-section {
    background-color: var(--color-primary);
    padding: clamp(40px, 6vw, 70px) 16px;
    margin: 0 auto;
    border-radius: 12px;
}

.theme-faq-container {
    max-width: 1100px;
    margin: 0 auto;
}

.theme-faq-heading {
    color: var(--color-white);
    text-align: center;
    font-size: clamp(22px, 2.4vw, 30px);
    font-weight: 800;
    font-style: italic;
    margin: 0 0 clamp(30px, 4vw, 45px) 0;
    line-height: 1.3;
}

.theme-faq-card {
    background-color: var(--color-white);
    border-radius: 12px;
    padding: clamp(20px, 4vw, 45px) clamp(16px, 3.5vw, 40px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

.theme-faq-wrapper {
    display: flex;
    flex-direction: column;
    border-top: 1px solid #eef1f6;
}

.theme-faq-item {
    border-bottom: 1px solid #eef1f6;
}

.theme-faq-trigger {
    width: 100%;
    background: none;
    border: none;
    padding: 22px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    cursor: pointer;
    text-align: left;
    font-family: inherit;
}

.theme-faq-trigger__question {
    color: var(--color-dark);
    font-size: clamp(15px, 1.2vw, 16.5px);
    font-weight: 800;
    font-style: italic;
    line-height: 1.4;
    margin: 0;
}

.theme-faq-trigger__icon {
    position: relative;
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.theme-faq-trigger__icon::before,
.theme-faq-trigger__icon::after {
    content: "";
    position: absolute;
    background-color: var(--color-dark);
    transition: transform 0.25s ease;
    border-radius: 2px;
}

/* Horizontal line */
.theme-faq-trigger__icon::before {
    top: 7px;
    left: 0;
    width: 16px;
    height: 3px;
}

/* Vertical line */
.theme-faq-trigger__icon::after {
    top: 0;
    left: 7px;
    width: 3px;
    height: 16px;
}

/* Active Icon State (Transforms + into -) */
.theme-faq-item.is-active .theme-faq-trigger__icon::after {
    transform: rotate(90deg);
    opacity: 0;
}
.theme-faq-item.is-active .theme-faq-trigger__icon::before {
    transform: rotate(180deg);
}

.theme-faq-panel {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.theme-faq-panel__content {
    font-size: clamp(13.5px, 1.05vw, 14.5px);
    line-height: 1.65;
    padding: 0 0 24px 0;
    margin: 0;
}
</style>

<section class="theme-faq-section site-container" itemscope itemtype="https://schema.org/FAQPage" aria-labelledby="<?php echo $faq_uid; ?>-heading">
    <div class="theme-faq-container">
        
        <?php if ( $faq_heading ) : ?>
            <h2 class="theme-faq-heading" id="<?php echo $faq_uid; ?>-heading"><?php echo esc_html( $faq_heading ); ?></h2>
        <?php endif; ?>

        <?php if ( $faq_items ) : ?>
            <div class="theme-faq-card">
                <div class="theme-faq-wrapper">
                    <?php foreach ( $faq_items as $index => $item ) : ?>
                        <?php if ( empty($item['question']) ) continue; ?>
                        <?php $is_first = ($index === 0); ?>
                        
                        <div class="theme-faq-item<?php echo $is_first ? ' is-active' : ''; ?>" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                            
                            <button class="theme-faq-trigger" aria-expanded="<?php echo $is_first ? 'true' : 'false'; ?>" aria-controls="<?php echo $faq_uid; ?>-panel-<?php echo $index; ?>">
                                <h3 class="theme-faq-trigger__question" itemprop="name"><?php echo esc_html( $item['question'] ); ?></h3>
                                <span class="theme-faq-trigger__icon" aria-hidden="true"></span>
                            </button>
                            
                            <div id="<?php echo $faq_uid; ?>-panel-<?php echo $index; ?>" <?php echo $is_first ? ' style="max-height: unset;"' : ''; ?> class="theme-faq-panel" role="region" aria-labelledby="<?php echo $faq_uid; ?>-panel-<?php echo $index; ?>" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                                <p class="theme-faq-panel__content" itemprop="text">
                                    <?php echo esc_html( $item['answer'] ); ?>
                                </p>
                            </div>

                        </div>

                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const faqContainer = document.querySelector('[aria-labelledby="<?php echo $faq_uid; ?>-heading"]');
    if (!faqContainer) return;

    const faqItems = faqContainer.querySelectorAll('.theme-faq-item');

    faqItems.forEach(item => {
        const trigger = item.querySelector('.theme-faq-trigger');
        const panel = item.querySelector('.theme-faq-panel');

        trigger.addEventListener('click', function() {
            const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
            
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('is-active');
                    otherItem.querySelector('.theme-faq-trigger').setAttribute('aria-expanded', 'false');
                    otherItem.querySelector('.theme-faq-panel').style.maxHeight = null;
                }
            });

            if (isExpanded) {
                item.classList.remove('is-active');
                trigger.setAttribute('aria-expanded', 'false');
                panel.style.maxHeight = null;
            } else {
                item.classList.add('is-active');
                trigger.setAttribute('aria-expanded', 'true');
                panel.style.maxHeight = panel.scrollHeight + "px";
            }
        });
    });
});
</script>