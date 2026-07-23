<?php
/**
 * SiteGen Content Import Endpoint
 * --------------------------------
 * Drop this file in the theme as inc/rest-import.php and add to functions.php:
 *
 *     require_once get_template_directory() . '/inc/rest-import.php';
 *
 * Endpoint:
 *     POST /wp-json/sitegen/v1/import
 *
 * Auth: WordPress Application Passwords (user must have manage_options).
 *   Create one under Users → Profile → Application Passwords, then:
 *     curl -u "admin:xxxx xxxx xxxx xxxx" \
 *          -H "Content-Type: application/json" \
 *          -d @theme-fields.json \
 *          https://SITE/wp-json/sitegen/v1/import
 *
 * Payload (produced by transform.js):
 * {
 *   "sideload_images": true,
 *   "fields": {
 *     "hero_title": "Elektricien\nDordrecht",
 *     "hero_usp_items": [ { "text": "24/7 bereikbaar" } ],
 *     "hero_bg_image": "https://old-site.nl/wp-content/uploads/hero.jpg",
 *     ...
 *   }
 * }
 *
 * Behaviour:
 *  - Each key in `fields` must be an ACF field name registered on the
 *    options pages (acf_add_local_field_group). Unknown names are reported
 *    back in `skipped`, never silently ignored.
 *  - Image fields given as URL strings are sideloaded into the Media
 *    Library (deduped by URL) and stored as attachment IDs. Works inside
 *    repeaters too (e.g. services_list[].image).
 *  - Also accepts GET /wp-json/sitegen/v1/export to read back all current
 *    values (useful for verification and backups).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

    register_rest_route( 'sitegen/v1', '/import', array(
        'methods'             => 'POST',
        'callback'            => 'sitegen_handle_import',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'sitegen/v1', '/export', array(
        'methods'             => 'GET',
        'callback'            => 'sitegen_handle_export',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );
} );

/**
 * POST /sitegen/v1/import
 */
function sitegen_handle_import( WP_REST_Request $request ) {

    if ( ! function_exists( 'update_field' ) || ! function_exists( 'acf_get_field' ) ) {
        return new WP_Error( 'acf_missing', 'ACF is not active on this site.', array( 'status' => 500 ) );
    }

    // A full import can sideload a dozen+ remote images in this one request;
    // each is a synchronous HTTP fetch, so the default 30s limit can cut the
    // import off partway through and leave some image fields empty.
    if ( function_exists( 'set_time_limit' ) ) {
        @set_time_limit( 300 );
    }

    $body     = $request->get_json_params();
    $fields   = isset( $body['fields'] ) && is_array( $body['fields'] ) ? $body['fields'] : null;
    $sideload = ! empty( $body['sideload_images'] );

    if ( null === $fields ) {
        return new WP_Error( 'bad_payload', 'Missing "fields" object in payload.', array( 'status' => 400 ) );
    }

    $updated = array();
    $skipped = array();
    $errors  = array();
    $cleared = array();

    // ---- Clear stale fields first --------------------------------------
    $clear = isset( $body['clear'] ) && is_array( $body['clear'] ) ? $body['clear'] : array();
    foreach ( $clear as $name ) {
        $field = acf_get_field( $name );
        if ( ! $field ) { continue; }
        $empty = ( 'repeater' === $field['type'] || 'gallery' === $field['type'] ) ? array() : '';
        update_field( $field['key'], $empty, 'option' );
        $cleared[] = $name;
    }

    foreach ( $fields as $name => $value ) {

        $field = acf_get_field( $name );

        if ( ! $field ) {
            $skipped[] = $name; // not a registered field on this theme
            continue;
        }

        if ( $sideload ) {
            $value = sitegen_resolve_images( $field, $value, $errors );
        }

        $ok = update_field( $field['key'], $value, 'option' );

        // update_field returns false when the value is unchanged too,
        // so verify by reading back instead of trusting the boolean.
        $stored = get_field( $name, 'option', false );
        if ( $ok || null !== $stored ) {
            $updated[] = $name;
        } else {
            $errors[] = array( 'field' => $name, 'error' => 'update_field failed' );
        }
    }

    return rest_ensure_response( array(
        'success' => empty( $errors ),
        'updated' => $updated,
        'cleared' => $cleared,
        'skipped' => $skipped,
        'errors'  => $errors,
    ) );
}

/**
 * GET /sitegen/v1/export — read back all option fields of our groups.
 */
function sitegen_handle_export() {
    if ( ! function_exists( 'get_field_objects' ) ) {
        return new WP_Error( 'acf_missing', 'ACF is not active.', array( 'status' => 500 ) );
    }
    $objects = get_field_objects( 'option' );
    $out = array();
    if ( $objects ) {
        foreach ( $objects as $name => $obj ) {
            $out[ $name ] = $obj['value'];
        }
    }
    return rest_ensure_response( array( 'fields' => $out ) );
}

/**
 * Recursively convert image-URL strings into attachment IDs for
 * image fields, including image sub_fields inside repeaters.
 */
function sitegen_resolve_images( $field, $value, array &$errors ) {

    $type = isset( $field['type'] ) ? $field['type'] : '';

    // Direct image field given as a URL string → sideload.
    if ( 'image' === $type && is_string( $value ) && preg_match( '#^https?://#i', $value ) ) {
        $id = sitegen_sideload( $value, $errors );
        return $id ? $id : null;
    }

    // Repeater: walk rows and resolve image sub_fields.
    if ( 'repeater' === $type && is_array( $value ) && ! empty( $field['sub_fields'] ) ) {
        $subs = array();
        foreach ( $field['sub_fields'] as $sf ) {
            $subs[ $sf['name'] ] = $sf;
        }
        foreach ( $value as $i => $row ) {
            if ( ! is_array( $row ) ) continue;
            foreach ( $row as $sub_name => $sub_val ) {
                if ( isset( $subs[ $sub_name ] ) ) {
                    $value[ $i ][ $sub_name ] = sitegen_resolve_images( $subs[ $sub_name ], $sub_val, $errors );
                }
            }
        }
    }

    return $value;
}

/**
 * Download a remote image into the Media Library, deduplicated by
 * source URL (stored in _sitegen_source_url meta).
 *
 * @return int|false attachment ID
 */
function sitegen_sideload( $url, array &$errors ) {

    // Dedupe: did we already import this exact URL?
    $existing = get_posts( array(
        'post_type'      => 'attachment',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_sitegen_source_url',
        'meta_value'     => $url,
    ) );
    if ( $existing ) {
        return (int) $existing[0];
    }

    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $id = media_sideload_image( $url, 0, null, 'id' );

    if ( is_wp_error( $id ) ) {
        $errors[] = array( 'image' => $url, 'error' => $id->get_error_message() );
        return false;
    }

    update_post_meta( $id, '_sitegen_source_url', $url );
    return (int) $id;
}