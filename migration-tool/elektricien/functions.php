<?php

// Base slug for this site's bundled static default images
// (assets/images/{slug}-*.jpg) — shown whenever an ACF image field is
// empty. Computed from the site's own title (not hardcoded) so every site
// this theme gets deployed to ends up with uniquely named/alt-texted
// images automatically, with no per-site code edits. The migration tool
// renames the bundled image files to match this same slug when it
// deploys the theme to a new site — see the image-rename step in
// migration-tool/lib/migrate.ts, which computes it with the identical
// sanitize_title( get_bloginfo( 'name' ) ) call so the two sides agree.
function theme_image_slug() {
    static $slug = null;
    if ( $slug === null ) {
        $slug = sanitize_title( get_bloginfo( 'name' ) );
    }
    return $slug;
}

// Builds the URL for a bundled static default image (assets/images/{slug}-{name}.*),
// checking the actual file's extension on disk rather than assuming one —
// the migration tool can upload a per-site logo in whatever format the
// operator provides (png/jpg/webp), so the filename's extension isn't
// fixed the way the rest of this file's naming scheme otherwise is.
function theme_static_image_url( $name, $default_ext = 'webp' ) {
    $base_dir = get_template_directory() . '/assets/images/';
    $base_uri = get_template_directory_uri() . '/assets/images/';
    $slug     = theme_image_slug();

    $exts = array_unique( array( $default_ext, 'webp', 'png', 'jpg', 'jpeg' ) );
    foreach ( $exts as $ext ) {
        $file = $base_dir . $slug . '-' . $name . '.' . $ext;
        if ( file_exists( $file ) ) {
            // A re-migration can overwrite this same filename with new
            // content (e.g. a replaced logo) — without a cache-busting
            // query string, browsers and page-cache plugins keep serving
            // the old file from that same URL indefinitely.
            return $base_uri . $slug . '-' . $name . '.' . $ext . '?v=' . filemtime( $file );
        }
    }
    return $base_uri . $slug . '-' . $name . '.' . $default_ext;
}

require_once get_template_directory() . '/inc/class-tgm-plugin-activation.php';
require_once get_template_directory() . '/inc/rest-import.php';

add_action( 'tgmpa_register', 'mytheme_register_local_plugins' );

function mytheme_register_local_plugins() {
    $plugins = array(
        array(
            'name'             => 'Advanced Custom Fields Pro', 
            'slug'             => 'advanced-custom-fields-pro', 
            'source'           => get_template_directory() . '/plugins/advanced-custom-fields-pro.zip', 
            'required'         => true,                                 
            'force_activation' => true,                                 
        ),
        array(
            'name'             => 'Contact Form 7',
            'slug'             => 'contact-form-7',
            'required'         => true,
            'force_activation' => true,
        ),
    );

    $config = array(
        'id'           => 'mytheme-tgmpa',         
        'default_path' => '',                      
        'menu'         => 'tgmpa-install-plugins', 
        'has_notices'  => true,                    
        'dismissable'  => false,                   
        'is_automatic' => true,                    
    );

    tgmpa( $plugins, $config );
}

function mytheme_create_hero_contact_form() {
    if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
        return;
    }

    $form_title = 'Hero form';
    $form_slug  = '6ad45cc';

    $existing_form = get_posts( array(
        'name'           => $form_slug,
        'post_type'      => 'wpcf7_contact_form',
        'posts_per_page' => 1,
        'post_status'    => 'any',
    ) );

    if ( empty( $existing_form ) ) {
        $form_content = implode( "\n", array(
            '<div class="hero__form-group">',
            '    <label class="screen-reader-text" for="hero-postcode">Postcode</label>',
            '    [text* postcode id:hero-postcode class:hero__input class:hero__input--postcode placeholder "Postcode"]',
            '</div>',
            '',
            '<div class="hero__form-group">',
            '    <label class="screen-reader-text" for="hero-huisnummer">Huisnummer</label>',
            '    [text* huisnummer id:hero-huisnummer class:hero__input class:hero__input--nr placeholder "Nr."]',
            '</div>',
            '',
            '<div class="hero__form-group">',
            '    <label class="screen-reader-text" for="hero-toevoeging">Toevoeging</label>',
            '    [text toevoeging id:hero-toevoeging class:hero__input class:hero__input--toev placeholder "Toev."]',
            '</div>',
            '',
            '[submit class:hero__form-btn "VERSTUREN"]'
        ) );

        $mail_settings = array(
            'active'             => true,
            'recipient'          => get_option( 'admin_email' ),
            'sender'             => '"' . get_bloginfo( 'name' ) . '" <wordpress@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '>',
            'subject'            => 'Nieuwe aanvraag via Hero Formulier - [postcode]',
            'body'               => implode( "\n", array(
                'Er is een nieuwe postcode-aanvraag binnengekomen via de website.',
                '',
                'Hier zijn de ingevulde gegevens:',
                '--------------------------------------------------',
                'Postcode:    [postcode]',
                'Huisnummer:  [huisnummer]',
                'Toevoeging:  [toevoeging]',
                '--------------------------------------------------',
                '',
                'Verzonden vanaf pagina: [_url]',
                'Datum & Tijd:            [_date] [_time]'
            ) ),
            'additional_headers' => '',
            'attachments'        => '',
            'use_html'           => false,
            'exclude_blank'      => false,
        );

        $form_id = wp_insert_post( array(
            'post_title'  => $form_title,
            'post_name'   => $form_slug,
            'post_content'=> $form_content,
            'post_status' => 'publish',
            'post_type'   => 'wpcf7_contact_form',
        ) );

        if ( $form_id && ! is_wp_error( $form_id ) ) {
            update_post_meta( $form_id, '_form', $form_content );
            update_post_meta( $form_id, '_mail', $mail_settings );
            update_post_meta( $form_id, '_additional_settings', '' );
        }
    }
}
add_action( 'admin_init', 'mytheme_create_hero_contact_form' );

add_action( 'after_switch_theme', 'mytheme_deactivate_incompatible_plugins' );

function mytheme_deactivate_incompatible_plugins() {
    $incompatible_plugins = array(
        'elementor/elementor.php',     // Elementor Free
        'elementor-pro/elementor-pro.php' // Elementor Pro
    );

    if ( ! function_exists( 'deactivate_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $deactivated_names = array();

    foreach ( $incompatible_plugins as $plugin ) {
        if ( is_plugin_active( $plugin ) ) {
            deactivate_plugins( $plugin );
            
            $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
            $deactivated_names[] = $plugin_data['Name'];
        }
    }


    if ( ! empty( $deactivated_names ) ) {
        set_transient( 'mytheme_deactivated_notice', $deactivated_names, 45 );
    }
}




if (function_exists('acf_add_options_page')) {
    acf_add_options_page(array(
        'page_title' => 'Theme Settings',
        'menu_title' => 'Theme Settings',
        'menu_slug'  => 'theme-settings',
        'capability' => 'edit_posts',
        'redirect'   => true,
        'icon_url'   => 'dashicons-admin-generic',
    ));

    acf_add_options_sub_page(array(
        'page_title'  => 'Global Configuration',
        'menu_title'  => 'Global Settings',
        'menu_slug'   => 'theme-global-settings',
        'parent_slug' => 'theme-settings',
    ));

    acf_add_options_sub_page(array(
        'page_title'  => 'Header & Top Bar',
        'menu_title'  => 'Header Settings',
        'menu_slug'   => 'theme-header-settings',
        'parent_slug' => 'theme-settings',
    ));

    acf_add_options_sub_page(array(
        'page_title'  => 'Hero Section Settings',
        'menu_title'  => 'Hero Section',
        'menu_slug'   => 'theme-hero-settings',
        'parent_slug' => 'theme-settings',
    ));
}

if (function_exists('acf_add_local_field_group')) {

    acf_add_local_field_group(array(
        'key'      => 'group_global_settings',
        'title'    => 'Global & Tracking Settings',
        'fields'   => array(
            array(
                'key'           => 'field_global_phone_display',
                'label'         => 'Global Phone (Display Format)',
                'name'          => 'global_phone_display',
                'type'          => 'text',
                'default_value' => '085 - 130 49 89',
            ),
            array(
                'key'           => 'field_global_phone_clean',
                'label'         => 'Global Phone (Dial Digits)',
                'name'          => 'global_phone_clean',
                'type'          => 'text',
                'default_value' => '0851304989',
            ),
            array(
                'key'           => 'field_global_font_family',
                'label'         => 'Font Family stack',
                'name'          => 'global_font_family',
                'type'          => 'text',
                'default_value' => '"Inter", sans-serif',
            ),
            array(
                'key'           => 'field_global_max_width',
                'label'         => 'Global Container Max Width (px)',
                'name'          => 'global_max_width',
                'type'          => 'number',
                'default_value' => 1250,
                'append'        => 'px',
            ),
            array(
                'key'           => 'field_global_color_primary',
                'label'         => 'Primary Brand Color',
                'name'          => 'global_color_primary',
                'type'          => 'color_picker',
                'default_value' => '#ff5e00',
                'instructions'  => 'Main CTA / accent color used across every section.',
            ),
            array(
                'key'           => 'field_global_color_primary_hover',
                'label'         => 'Primary Brand Color (Hover)',
                'name'          => 'global_color_primary_hover',
                'type'          => 'color_picker',
                'default_value' => '#e05300',
            ),
            array(
                'key'           => 'field_global_color_dark',
                'label'         => 'Dark / Navy Color',
                'name'          => 'global_color_dark',
                'type'          => 'color_picker',
                'default_value' => '#161c2d',
                'instructions'  => 'Footer background, dark cards, and dark headings.',
            ),
            array(
                'key'          => 'field_google_tag_manager_gtm',
                'label'        => 'Google Tag Manager Script (Head)',
                'name'         => 'google_tag_manager_gtm',
                'type'         => 'textarea',
                'rows'         => 4,
                'instructions' => 'Paste complete script block including wrapping <script> tags.',
            ),
            array(
                'key'          => 'field_google_tag_manager_noscript',
                'label'        => 'Google Tag Manager Noscript (Body)',
                'name'         => 'google_tag_manager_noscript',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'Paste complete noscript block including wrapping <noscript> tags.',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'theme-global-settings',
                ),
            ),
        ),
    ));

    acf_add_local_field_group(array(
        'key'    => 'group_header_settings_combined',
        'title'  => 'Header & Top Bar Settings',
        'fields' => array(
            array(
                'key'           => 'field_top_bar_marquee',
                'label'         => 'Enable Marquee Animation',
                'name'          => 'top_bar_marquee',
                'type'          => 'true_false',
                'default_value' => 1,
                'ui'            => 1,
            ),
            array(
                'key'           => 'field_top_bar_items',
                'label'         => 'Top Bar Items',
                'name'          => 'top_bar_items',
                'type'          => 'repeater',
                'layout'        => 'table',
                'button_label'  => 'Add Item',
                'default_value' => array(
                    array( 'text' => 'Vandaag gebeld = vandaag geholpen' ),
                    array( 'text' => 'Transparante tarieven' ),
                    array( 'text' => 'Gediplomeerde Elektriciens' ),
                    array( 'text' => 'Meer dan 20 jaar ervaring' ),
                    array( 'text' => '100% tevredenheidsgarantie.' ),
                ),
                'sub_fields'    => array(
                    array(
                        'key'   => 'field_top_bar_item_text',
                        'label' => 'Text',
                        'name'  => 'text',
                        'type'  => 'text',
                    ),
                ),
            ),
            array(
                'key'           => 'field_header_logo',
                'label'         => 'Logo Image',
                'name'          => 'header_logo',
                'type'          => 'image',
                'return_format' => 'array',
                'preview_size'  => 'medium',
            ),
            array(
                'key'           => 'field_header_cta_text',
                'label'         => 'CTA Button Text',
                'name'          => 'header_cta_text',
                'type'          => 'text',
                'default_value' => 'Offerte aanvragen',
            ),
            array(
                'key'           => 'field_header_cta_link',
                'label'         => 'CTA Button Link',
                'name'          => 'header_cta_link',
                'type'          => 'text',
                'default_value' => '#offerte-modal',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'theme-header-settings',
                ),
            ),
        ),
    ));

    acf_add_local_field_group(array(
        'key'    => 'group_hero_settings_combined',
        'title'  => 'Hero Section Settings',
        'fields' => array(
            array(
                'key'           => 'field_hero_bg_image',
                'label'         => 'Background Image',
                'name'          => 'hero_bg_image',
                'type'          => 'image',
                'return_format' => 'array',
            ),
            array(
                'key'           => 'field_hero_rating_value',
                'label'         => 'Rating (out of 5)',
                'name'          => 'hero_rating_value',
                'type'          => 'number',
                'default_value' => 4.5,
                'step'          => 0.1,
            ),
            array(
                'key'           => 'field_hero_rating_text',
                'label'         => 'Rating Text',
                'name'          => 'hero_rating_text',
                'type'          => 'text',
                'default_value' => '4.5 uit 5 op basis van 662+ reviews',
            ),
            array(
                'key'           => 'field_hero_title',
                'label'         => 'Title',
                'name'          => 'hero_title',
                'type'          => 'textarea',
                'rows'          => 2,
                'default_value' => "Elektricien\nAmstelveen",
            ),
            array(
                'key'          => 'field_hero_usp_items',
                'label'        => 'USP List',
                'name'         => 'hero_usp_items',
                'type'         => 'repeater',
                'layout'       => 'table',
                'button_label' => 'Add USP',
                'sub_fields'   => array(
                    array(
                        'key'   => 'field_hero_usp_text',
                        'label' => 'Text',
                        'name'  => 'text',
                        'type'  => 'text',
                    ),
                ),
            ),
            array(
                'key'           => 'field_hero_badge_text',
                'label'         => 'Badge Text',
                'name'          => 'hero_badge_text',
                'type'          => 'text',
                'default_value' => 'Gratis inspectie!',
            ),
            array(
                'key'           => 'field_hero_badge_value',
                'label'         => 'Badge Value Text',
                'name'          => 'hero_badge_value',
                'type'          => 'text',
                'default_value' => 't.w.v. €150,-',
            ),
            array(
                'key'           => 'field_hero_card_title',
                'label'         => 'Card Title',
                'name'          => 'hero_card_title',
                'type'          => 'text',
                'default_value' => 'De Beste Service.',
            ),
            array(
                'key'           => 'field_hero_card_subtitle',
                'label'         => 'Card Subtitle',
                'name'          => 'hero_card_subtitle',
                'type'          => 'text',
                'default_value' => '100% tevredenheidsgarantie.',
            ),
            array(
                'key'          => 'field_hero_card_usp_items',
                'label'        => 'Card USP List',
                'name'         => 'hero_card_usp_items',
                'type'         => 'repeater',
                'layout'       => 'table',
                'button_label' => 'Add Item',
                'sub_fields'   => array(
                    array(
                        'key'   => 'field_hero_card_usp_text',
                        'label' => 'Text',
                        'name'  => 'text',
                        'type'  => 'text',
                    ),
                ),
            ),
            array(
                'key'           => 'field_hero_form_heading',
                'label'         => 'Form Heading',
                'name'          => 'hero_form_heading',
                'type'          => 'text',
                'default_value' => 'Ontvang direct de laagste prijs',
            ),
            array(
                'key'           => 'field_hero_form_button_text',
                'label'         => 'Form Button Text',
                'name'          => 'hero_form_button_text',
                'type'          => 'text',
                'default_value' => 'Ontvang laagste prijs',
            ),
            array(
                'key'           => 'field_hero_form_disclaimer',
                'label'         => 'Form Disclaimer',
                'name'          => 'hero_form_disclaimer',
                'type'          => 'textarea',
                'rows'          => 2,
                'default_value' => 'Na invullen ontvangt u binnen één dag een reactie en wordt er een offerte voor u opgesteld.',
            ),
            array(
                'key'           => 'field_hero_bottom_title',
                'label'         => 'Bottom Title',
                'name'          => 'hero_bottom_title',
                'type'          => 'text',
                'default_value' => 'Elektricien Amstelveen',
            ),
            array(
                'key'           => 'field_hero_bottom_subtitle',
                'label'         => 'Bottom Subtitle',
                'name'          => 'hero_bottom_subtitle',
                'type'          => 'text',
                'default_value' => 'De Beste Service. 100% tevredenheidsgarantie.',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'theme-hero-settings',
                ),
            ),
        ),
    ));
}


if (function_exists('acf_add_local_field_group')) {
acf_add_options_sub_page(array(
    'page_title'  => 'About Section Settings',
    'menu_title'  => 'About Section',
    'menu_slug'   => 'theme-about-settings',
    'parent_slug' => 'theme-settings',
));
}

if (function_exists('acf_add_local_field_group')) {

    acf_add_local_field_group(array(
        'key'    => 'group_about_settings',
        'title'  => 'About Section Settings',
        'fields' => array(
            array(
                'key'           => 'field_about_heading',
                'label'         => 'Main Heading',
                'name'          => 'about_heading',
                'type'          => 'text',
                'default_value' => 'Kwaliteit en service van een ervaren elektricien',
            ),
            array(
                'key'           => 'field_about_intro_text',
                'label'         => 'Intro Text',
                'name'          => 'about_intro_text',
                'type'          => 'textarea',
                'rows'          => 3,
                'default_value' => 'Bent u op zoek naar een betrouwbare en gecertificeerde elektricien? Onze specialisten staan dag en nacht voor u klaar om alle mogelijke elektra problemen snel en vakkundig op te lossen.',
            ),
            array(
                'key'           => 'field_about_subheading',
                'label'         => 'Subheading',
                'name'          => 'about_subheading',
                'type'          => 'text',
                'default_value' => 'Waarom kiezen voor onze dienstverlening?',
            ),
            array(
                'key'           => 'field_about_subtext',
                'label'         => 'Subtext',
                'name'          => 'about_subtext',
                'type'          => 'textarea',
                'rows'          => 2,
                'default_value' => 'Wij hanteren te allen tijde een transparante werkwijze met heldere tarieven vooraf, zodat u nooit voor verrassingen komt te staan.',
            ),
            array(
                'key'           => 'field_about_points',
                'label'         => 'Bullet Points List',
                'name'          => 'about_points',
                'type'          => 'repeater',
                'layout'        => 'row',
                'button_label'  => 'Add Bullet Point',
                'default_value' => array(
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
                ),
                'sub_fields'   => array(
                    array(
                        'key'         => 'field_about_point_label',
                        'label'       => 'Bold Label (Optional)',
                        'name'        => 'label',
                        'type'        => 'text',
                        'placeholder' => 'Snel ter plaatse:',
                    ),
                    array(
                        'key'         => 'field_about_point_text',
                        'label'       => 'Point Text',
                        'name'        => 'text',
                        'type'        => 'text',
                        'placeholder' => 'Binnen 20 tot 30 minuten zijn we bij u.',
                    ),
                ),
            ),
            array(
                'key'           => 'field_about_image',
                'label'         => 'About Section Image',
                'name'          => 'about_image',
                'type'          => 'image',
                'return_format' => 'array',
                'preview_size'  => 'medium',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'theme-about-settings',
                ),
            ),
        ),
    ));
}


if (function_exists('acf_add_local_field_group')) {
acf_add_options_sub_page(array(
    'page_title'  => 'Reviews Settings',
    'menu_title'  => 'Reviews Section',
    'menu_slug'   => 'theme-reviews-settings',
    'parent_slug' => 'theme-settings',
));

acf_add_local_field_group(array(
    'key' => 'group_reviews_settings',
    'title' => 'Reviews Section Settings',
    'fields' => array(
        array(
            'key'           => 'field_reviews_heading',
            'label'         => 'Section Heading',
            'name'          => 'reviews_heading',
            'type'          => 'text',
            'default_value' => 'Dit is wat klanten over ons zeggen.',
        ),
        array(
            'key'           => 'field_reviews_subheading',
            'label'         => 'Section Subheading',
            'name'          => 'reviews_subheading',
            'type'          => 'textarea',
            'rows'          => 2,
            'default_value' => 'Wij worden op verschillende platforms beoordeeld door onze klanten.',
        ),
        array(
            'key'          => 'field_reviews_list',
            'label'        => 'Customer Reviews',
            'name'         => 'reviews_list',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add New Review',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_review_text',
                    'label'         => 'Review Content',
                    'name'          => 'text',
                    'type'          => 'textarea',
                    'rows'          => 4,
                ),
                array(
                    'key'   => 'field_review_name',
                    'label' => 'Customer Name',
                    'name'  => 'name',
                    'type'  => 'text',
                ),
                array(
                    'key'   => 'field_review_location',
                    'label' => 'Customer Location',
                    'name'  => 'location',
                    'type'  => 'text',
                ),
            ),
        ),
    ),
    'location' => array(
        array(
            array(
                'param'    => 'options_page',
                'operator' => '==',
                'value'    => 'theme-reviews-settings',
            ),
        ),
    ),
));







acf_add_options_sub_page(array(
    'page_title'  => 'Why Choose Us Settings',
    'menu_title'  => 'Why Choose Us Settings',
    'menu_slug'   => 'why_choose_us_settings',
    'parent_slug' => 'theme-settings',
));


acf_add_local_field_group(array(
    'key' => 'group_why_choose_us',
    'title' => 'Why Choose Us Settings',
    'fields' => array(
        array(
            'key'           => 'field_why_choose_subheadline',
            'label'         => 'Why Choose Us Subheadline',
            'name'          => 'why_choose_subheadline',
            'type'          => 'text',
            'default_value' => 'Dringende een Elektricien nodig? Wij zijn 24/7 bereikbaar',
        ),
        array(
            'key'           => 'field_why_choose_heading',
            'label'         => 'Why Choose Heading',
            'name'          => 'why_choose_heading',
            'type'          => 'text',
            'default_value' => 'Uw betrouwbare Elektricien in Amstelveen',
        ),
        array(
            'key'           => 'field_about_description',
            'label'         => 'About Description Text',
            'name'          => 'about_description',
            'type'          => 'textarea',
            'rows'          => 5,
            'default_value' => 'Bent u op zoek naar een betrouwbare elektricien in Amstelveen of omgeving? Stop met zoeken, want bij ons bent u aan het juiste adres! Onze experts hebben jarenlange ervaring in het veilig aanleggen en onderhouden van elektra in zowel bestaande als nieuwbouw woningen. Wij staan garant voor kwalitatieve service, werken volgens de laatste veiligheidsnormen en hebben een snelle responstijd. Of het nu gaat om een kleine reparatie of een uitgebreide renovatie, wij zorgen dat u weer veilig kunt wonen en werken. Neem vandaag nog contact met ons op voor een vrijblijvende offerte!',
        ),
        
        array(
            'key'           => 'field_why_choose_intro',
            'label'         => 'Why Choose Us Intro Paragraph',
            'name'          => 'why_choose_intro',
            'type'          => 'textarea',
            'rows'          => 5,
        ),
        array(
            'key'           => 'field_why_choose_bullets',
            'label'         => 'Core Benefits / Bullet Points',
            'name'          => 'why_choose_bullets',
            'type'          => 'repeater',
            'layout'        => 'block',
            'button_label'  => 'Add New Benefit Item',
            'default_value' => array(
                array(
                    'title' => 'Snelle service',
                    'text'  => 'u bent snel geholpen door een elektricien uit de buurt',
                ),
                array(
                    'title' => 'Vakkundig en veilig',
                    'text'  => 'alle werkzaamheden worden uitgevoerd volgens de geldende normen en voorschriften',
                ),
                array(
                    'title' => 'Persoonlijk en eerlijk advies',
                    'text'  => 'u krijgt altijd een transparant advies dat past bij uw situatie',
                ),
            ),
            'sub_fields'   => array(
                array(
                    'key'   => 'field_benefit_title',
                    'label' => 'Benefit Bold Title',
                    'name'  => 'title',
                    'type'  => 'text',
                ),
                array(
                    'key'   => 'field_benefit_text',
                    'label' => 'Benefit Content Description',
                    'name'  => 'text',
                    'type'  => 'textarea',
                    'rows'  => 2,
                ),
            ),
        ),
        

        array(
            'key'           => 'field_about_side_image',
            'label'         => 'Right Side Banner Image',
            'name'          => 'about_side_image',
            'type'          => 'image',
            'return_format' => 'url',
        ),
    ),
    'location' => array(
        array(
            array(
                'param'    => 'options_page',
                'operator' => '==',
                'value'    => 'why_choose_us_settings', 
            ),
        ),
    ),
));









acf_add_options_sub_page(array(
    'page_title'  => 'Services Settings',
    'menu_title'  => 'Services Section',
    'menu_slug'   => 'theme-services-settings',
    'parent_slug' => 'theme-settings',
));

acf_add_local_field_group(array(
    'key' => 'group_services_settings',
    'title' => 'Services Section Settings',
    'fields' => array(
        array(
            'key'           => 'field_services_heading',
            'label'         => 'Section Heading',
            'name'          => 'services_heading',
            'type'          => 'text',
            'default_value' => 'Waar kan Elektricien Amstelveen u nog meer mee van dienst zijn?',
        ),
        array(
            'key'           => 'field_services_subheading',
            'label'         => 'Section Subheading',
            'name'          => 'services_subheading',
            'type'          => 'textarea',
            'rows'          => 3,
            'default_value' => 'Naast het oplossen van uw elektriciteitsproblemen en het plaatsen van nieuwe bekabeling, biedt Elektricien Amstelveen ook uitgebreide diensten aan op het gebied van energiebesparing. Onze experts kunnen u helpen bij het installeren van zonnepanelen en het optimaliseren van uw energieverbruik.',
        ),
        array(
            'key'           => 'field_services_usps',
            'label'         => 'Section USPs (4 Columns below description)',
            'name'          => 'services_usps',
            'type'          => 'repeater',
            'layout'        => 'table',
            'max'           => 4,
            'button_label'  => 'Add USP Item',
            'default_value' => array(
                array( 'text' => '24/7 spoedservice bij Storingen' ),
                array( 'text' => 'Lokale Elektricien met kennis' ),
                array( 'text' => 'Transparante prijzen & gratis inspectie' ),
                array( 'text' => 'Vakmanschap met garantie' ),
            ),
            'sub_fields'    => array(
                array(
                    'key'   => 'field_services_usp_text',
                    'label' => 'USP Label Text',
                    'name'  => 'text',
                    'type'  => 'text',
                ),
            ),
        ),
        array(
            'key'          => 'field_services_list',
            'label'        => 'Services Cards Grid',
            'name'         => 'services_list',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add New Service Card',
            'sub_fields'   => array(
                array(
                    'key'   => 'field_service_image',
                    'label' => 'Card Top Image',
                    'name'  => 'image',
                    'type'  => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'   => 'field_service_title',
                    'label' => 'Card Title',
                    'name'  => 'title',
                    'type'  => 'text',
                ),
                array(
                    'key'   => 'field_service_desc',
                    'label' => 'Card Description',
                    'name'  => 'description',
                    'type'  => 'textarea',
                    'rows'  => 3,
                ),
                array(
                    'key'          => 'field_service_faqs',
                    'label'        => 'Accordion Dropdowns',
                    'name'         => 'faqs',
                    'type'         => 'repeater',
                    'layout'       => 'table',
                    'button_label' => 'Add Accordion Row',
                    'sub_fields'   => array(
                        array(
                            'key'   => 'field_service_faq_q',
                            'label' => 'Title / Question',
                            'name'  => 'question',
                            'type'  => 'text',
                        ),
                        array(
                            'key'   => 'field_service_faq_a',
                            'label' => 'Content / Answer',
                            'name'  => 'answer',
                            'type'  => 'textarea',
                            'rows'  => 2,
                        ),
                    ),
                ),
            ),
        ),
    ),
    'location' => array(
        array(
            array(
                'param'    => 'options_page',
                'operator' => '==',
                'value'    => 'theme-services-settings',
            ),
        ),
    ),
));


acf_add_options_sub_page(array(
    'page_title'  => 'Costs Settings',
    'menu_title'  => 'Costs Section',
    'menu_slug'   => 'theme-costs-settings',
    'parent_slug' => 'theme-settings',
));


acf_add_local_field_group(array(
    'key' => 'group_theme_costs_settings',
    'title' => 'Costs Section Settings',
    'fields' => array(
        array(
            'key'           => 'field_costs_subheadline',
            'label'         => 'Costs Subheadline',
            'name'          => 'costs_subheadline',
            'type'          => 'text',
        ),
        array(
            'key'           => 'field_costs_heading',
            'label'         => 'Costs Heading',
            'name'          => 'costs_heading',
            'type'          => 'text',
            'default_value' => 'Kosten Elektricien: Transparant en Eerlijk',
        ),
        array(
            'key'           => 'field_costs_description',
            'label'         => 'Costs Description Text',
            'name'          => 'costs_description',
            'type'          => 'textarea',
            'rows'          => 5,
        ),
        array(
            'key'           => 'field_costs_intro',
            'label'         => 'Factors Heading',
            'name'          => 'costs_intro',
            'type'          => 'text',
        ),
        array(
            'key'           => 'Factors_description',
            'label'         => 'Factors Text',
            'name'          => 'Factors_description',
            'type'          => 'textarea',
            'rows'          => 5,
        ),
        array(
            'key'          => 'field_costs_usps',
            'label'        => 'Costs USP List',
            'name'         => 'costs_usps',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add USP Item',
            'sub_fields'   => array(
                array(
                    'key'   => 'field_costs_usp_text',
                    'label' => 'Text',
                    'name'  => 'text',
                    'type'  => 'text',
                ),
            ),
        ),
        array(
            'key'          => 'field_costs_bullets',
            'label'        => 'Costs Core Factors / Bullet Points',
            'name'         => 'costs_bullets',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add New Cost Factor',
            'sub_fields'   => array(
                array(
                    'key'   => 'field_cost_factor_title',
                    'label' => 'Factor Bold Title',
                    'name'  => 'title',
                    'type'  => 'text',
                ),
                array(
                    'key'   => 'field_cost_factor_text',
                    'label' => 'Factor Content Description',
                    'name'  => 'text',
                    'type'  => 'textarea',
                    'rows'  => 2,
                ),
            ),
        ),
        array(
            'key'           => 'field_costs_side_image',
            'label'         => 'Right Side Costs Image',
            'name'          => 'costs_side_image',
            'type'          => 'image',
            'return_format' => 'url',
        ),
    ),
    'location' => array(
        array(
            array(
                'param'    => 'options_page',
                'operator' => '==',
                'value'    => 'theme-costs-settings', 
            ),
        ),
    ),
));




acf_add_options_sub_page(array(
    'page_title'  => 'FAQ Settings',
    'menu_title'  => 'FAQ Section',
    'menu_slug'   => 'theme-faq-settings',
    'parent_slug' => 'theme-settings',
));


acf_add_local_field_group(array(
    'key' => 'group_theme_faq_settings',
    'title' => 'FAQ Section Settings',
    'fields' => array(
        array(
            'key'           => 'field_faq_heading',
            'label'         => 'Section Heading',
            'name'          => 'faq_heading',
            'type'          => 'text',
            'default_value' => 'Veelgestelde vragen aan onze Elektriciens',
        ),
        array(
            'key'          => 'field_faq_items',
            'label'        => 'FAQ Accordion Items',
            'name'         => 'faq_items',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add New FAQ',
            'sub_fields'   => array(
                array(
                    'key'   => 'field_faq_question',
                    'label' => 'Question',
                    'name'  => 'question',
                    'type'  => 'text',
                ),
                array(
                    'key'   => 'field_faq_answer',
                    'label' => 'Answer Content',
                    'name'  => 'answer',
                    'type'  => 'textarea',
                    'rows'  => 4,
                ),
            ),
        ),
    ),
    'location' => array(
        array(
            array(
                'param'    => 'options_page',
                'operator' => '==',
                'value'    => 'theme-faq-settings', 
            ),
        ),
    ),
));



acf_add_options_sub_page(array(
    'page_title'  => 'Map Settings',
    'menu_title'  => 'Map Section',
    'menu_slug'   => 'theme-map-settings',
    'parent_slug' => 'theme-settings',
));



acf_add_local_field_group(array(
    'key' => 'group_theme_map_settings',
    'title' => 'Map / Service Area Settings',
    'fields' => array(
        array(
            'key'           => 'field_map_heading',
            'label'         => 'Section Heading',
            'name'          => 'map_heading',
            'type'          => 'text',
            'default_value' => 'Werkgebied van onze elektriciens in Amstelveen en omstreken:',
        ),
        array(
            'key'          => 'field_map_neighborhoods',
            'label'        => 'Neighborhoods List',
            'name'         => 'map_neighborhoods',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add Neighborhood',
            'sub_fields'   => array(
                array(
                    'key'   => 'field_map_neighborhood_name',
                    'label' => 'Neighborhood Name',
                    'name'  => 'name',
                    'type'  => 'text',
                ),
            ),
        ),
        array(
            'key'           => 'field_map_intro',
            'label'         => 'Benefits Intro Text',
            'name'          => 'map_intro',
            'type'          => 'text',
            'default_value' => 'Onze lokale aanwezigheid biedt u grote voordelen:',
        ),
        array(
            'key'           => 'field_map_bullets',
            'label'         => 'Benefits Features',
            'name'          => 'map_bullets',
            'type'          => 'repeater',
            'layout'        => 'block',
            'button_label'  => 'Add Benefit',
            'default_value' => array(
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
            ),
            'sub_fields'    => array(
                array(
                    'key'   => 'field_map_bullet_title',
                    'label' => 'Benefit Title (Bold)',
                    'name'  => 'title',
                    'type'  => 'text',
                ),
                array(
                    'key'   => 'field_map_bullet_text',
                    'label' => 'Benefit Description',
                    'name'  => 'text',
                    'type'  => 'textarea',
                    'rows'  => 2,
                ),
            ),
        ),
        array(
            'key'           => 'field_map_iframe',
            'label'         => 'Google Maps Embed Code / Iframe Code',
            'name'          => 'map_iframe',
            'type'          => 'textarea',
            'rows'          => 4,
            'placeholder'   => '<iframe src="https://www.google.com/maps/embed..."></iframe>',
        ),
    ),
    'location' => array(
        array(
            array(
                'param'    => 'options_page',
                'operator' => '==',
                'value'    => 'theme-map-settings', 
            ),
        ),
    ),
));




acf_add_options_sub_page(array(
    'page_title'  => 'Contact Form Settings',
    'menu_title'  => 'Contact Section',
    'menu_slug'   => 'theme-contact-settings',
    'parent_slug' => 'theme-settings',
));


acf_add_local_field_group(array(
    'key' => 'group_theme_contact_settings',
    'title' => 'Contact Form Section Settings',
    'fields' => array(
        array(
            'key'           => 'field_contact_heading',
            'label'         => 'Section Heading',
            'name'          => 'contact_heading',
            'type'          => 'text',
            'default_value' => 'Direct hulp van een elektricien in Amstelveen nodig?',
        ),
        array(
            'key'           => 'field_contact_description',
            'label'         => 'Description Text',
            'name'          => 'contact_description',
            'type'          => 'textarea',
            'rows'          => 5,
            'default_value' => 'Heeft u dringend behoefte aan de deskundige hulp van een professionele elektricien in Amstelveen? Maak u geen zorgen! Elektricien Amstelveen is 24 uur per dag en 7 dagen per week beschikbaar om u snel en efficiënt te helpen bij elk elektrisch probleem.',
        ),
        array(
            'key'           => 'field_contact_iframe_code',
            'label'         => 'Form Iframe / Shortcode Embed',
            'name'          => 'contact_iframe_code',
            'type'          => 'textarea',
            'rows'          => 4,
            'placeholder'   => '<iframe src="https://teamleader.eu/..."></iframe>',
        ),
        array(
            'key'          => 'field_contact_usps',
            'label'        => 'Contact USP List',
            'name'         => 'contact_usps',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add USP Item',
            'sub_fields'   => array(
                array(
                    'key'   => 'field_contact_usp_text',
                    'label' => 'Text',
                    'name'  => 'text',
                    'type'  => 'text',
                ),
            ),
        ),
    ),
    'location' => array(
        array(
            array(
                'param'    => 'options_page',
                'operator' => '==',
                'value'    => 'theme-contact-settings', 
            ),
        ),
    ),
));



acf_add_options_sub_page(array(
    'page_title'  => 'Footer Settings',
    'menu_title'  => 'Footer Section',
    'menu_slug'   => 'theme-footer-settings',
    'parent_slug' => 'theme-settings',
));


acf_add_local_field_group(array(
    'key' => 'group_theme_footer_settings',
    'title' => 'Footer Settings',
    'fields' => array(
        array(
            'key'   => 'field_footer_logo',
            'label' => 'Footer Logo',
            'name'  => 'footer_logo',
            'type'  => 'image',
            'return_format' => 'url',
        ),
        array(
            'key'           => 'field_footer_desc',
            'label' => 'Footer Description',
            'name'          => 'footer_description',
            'type'          => 'textarea',
            'rows'          => 3,
            'default_value' => 'Elektricien Amstelveen staat 24/7 voor u klaar voor alle soorten elektrotechnische werkzaamheden, storingen en installaties.',
        ),
        array(
            'key'           => 'field_footer_copyright',
            'label'         => 'Copyright Text',
            'name'          => 'footer_copyright',
            'type'          => 'text',
            'default_value' => '© 2026 Elektricien Amstelveen. Alle rechten voorbehouden.',
        ),
    ),
    'location' => array(
        array(
            array(
                'param'    => 'options_page',
                'operator' => '==',
                'value'    => 'theme-footer-settings', 
            ),
        ),
    ),
));

}

add_filter('wpcf7_autop_or_not', '__return_false');


// Register optimized responsive image dimensions
add_action('after_setup_theme', function() {
    add_image_size('logo_desktop', 226, 50, false);
    add_image_size('logo_desktop_2x', 452, 100, false);

    add_image_size('service_card_desktop', 400, 270, true);
    add_image_size('service_card_mobile', 600, 400, true);

    add_image_size('hero_desktop', 1000, 650, true);
    add_image_size('hero_mobile', 600, 400, true);
    
    add_image_size('about_vertical_desktop', 500, 850, false); 
	add_image_size('about_vertical_mobile', 400, 650, false);
    
});


add_filter('wp_editor_set_quality', function($quality, $mime_type) {
    if ('image/webp' === $mime_type || 'image/jpeg' === $mime_type) {
        return 70;
    }
    return $quality;
}, 10, 2);

add_filter('script_loader_tag', function($tag, $handle, $src) {
    if ( is_admin() ) {
        return $tag;
    }

    $defer_handles = array(
        'wp-hooks', 
        'wp-i18n', 
        'contact-form-7', 
        'wp-polyfill', 
        'swv'
    );
    
    if ( in_array($handle, $defer_handles, true) ) {
        return str_replace(' src', ' defer src', $tag);
    }
    return $tag;
}, 10, 3);

add_action('wp_enqueue_scripts', function() {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('wc-blocks-style');

    if ( is_front_page() ) {
        wp_dequeue_style('contact-form-7');
    }
}, 999);

remove_action('wp_head', 'wp_site_icon', 99);



