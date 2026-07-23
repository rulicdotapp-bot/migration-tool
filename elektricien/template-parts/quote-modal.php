<?php
/**
 * template-parts/quote-modal.php
 * Ultra-Clean Modern Quote Modal Engine with Teamleader Embedded Integration
 */
?>

<style>
/* Modern Fluid Design Tokens */
:root {
    --modal-dark: var(--color-dark);
    --modal-muted: #64748b;
    --modal-border: #e2e8f0;
    --modal-bg-light: #f8fafc;
    --modal-radius: 12px;
}

/* Modal Overlay Base Screen */
.theme-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.theme-modal-overlay.is-open {
    opacity: 1;
    visibility: visible;
}

/* Modal Core Card Box Container */
.theme-modal-card {
    background: var(--color-white);
    width: 100%;
    max-width: 600px;
    max-height: calc(90vh - 20px); 
    margin-top: 20px;
    overflow-y: auto; 
    -webkit-overflow-scrolling: touch;
    border-radius: var(--modal-radius);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    padding: 36px 24px 24px 24px;
    position: relative;
    transform: scale(0.95) translateY(-10px);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.theme-modal-overlay.is-open .theme-modal-card {
    transform: scale(1) translateY(0);
}

.theme-modal-close {
    position: absolute;
    top: 12px;
    right: 12px;
    background: var(--modal-bg-light);
    border: none;
    cursor: pointer;
    color: var(--modal-muted);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    z-index: 10;
}

.theme-modal-close:hover {
    background-color: var(--modal-border);
    color: var(--modal-dark);
}

/* Responsive Iframe setup */
.theme-modal-iframe {
    width: 100%;
    min-height: 600px;
    height: 100%;
    border: 0;
    display: block;
}

.theme-modal-loader {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 600px;
    background: var(--modal-bg-light);
    color: var(--modal-muted);
    font-weight: 600;
    border-radius: 6px;
}

@media (max-width: 576px) {
    .theme-modal-card {
        padding: 40px 16px 16px 16px;
        margin: 12px;
        max-height: calc(100vh - 24px);
    }
    
    .theme-modal-iframe, .theme-modal-loader {
        min-height: 500px;
    }
}
</style>

<div id="quote-modal-engine" class="theme-modal-overlay" aria-hidden="true" role="dialog" data-src="https://meeting.teamleader.eu/embed/form/elektricien-247/in-2-minuten-bent-u-klaar/">
    <div class="theme-modal-card">
        
        <button class="theme-modal-close" id="close-quote-modal" aria-label="Sluit formuliervenster">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <div id="modal-iframe-mount-point">
            <div class="theme-modal-loader">Agenda laden...</div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('quote-modal-engine');
    const mountPoint = document.getElementById('modal-iframe-mount-point');
    const openTriggers = document.querySelectorAll('.main-header__cta'); 
    const closeBtn = document.getElementById('close-quote-modal');
    
    if (!modal || !closeBtn || !mountPoint) return;

    const targetSrc = modal.getAttribute('data-src');

    function openQuoteModal() {
        // Mount iframe right before opening
        if (!mountPoint.querySelector('iframe')) {
            const iframe = document.createElement('iframe');
            iframe.className = 'theme-modal-iframe';
            iframe.setAttribute('src', targetSrc);
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('title', 'In 2 minuten bent u klaar');
            
            // Clear baseline text loader element
            mountPoint.innerHTML = '';
            mountPoint.appendChild(iframe);
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeQuoteModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        
        // Wipe iframe resource loop entirely to preserve layout state allocation loops
        setTimeout(() => {
            mountPoint.innerHTML = '<div class="theme-modal-loader">Agenda laden...</div>';
        }, 250);
    }

    openTriggers.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            openQuoteModal();
        });
    });

    closeBtn.addEventListener('click', closeQuoteModal);
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeQuoteModal();
    });
});
</script>