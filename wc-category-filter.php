<?php
/**
 * Plugin Name: WooCommerce Category Filter
 * Plugin URI: https://innov8ion.tech/
 * Description: A custom plugin to filter WooCommerce products by category using a shortcode.
 * Version: 1.0
 * Author: Author
 * Author URI: https://innov8ion.tech/
 * License: GPL2
 * Text Domain: wc-category-filter
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue scripts and styles
function wc_category_filter_enqueue_scripts() {
    wp_enqueue_style( 'wc-category-filter-style', plugins_url( '/assets/css/style.css', __FILE__ ) );
    wp_enqueue_script( 'wc-category-filter-script', plugins_url( '/assets/js/filter.js', __FILE__ ), array('jquery'), '1.0', true );

    // Localize script for Ajax URL
    wp_localize_script( 'wc-category-filter-script', 'wc_filter_params', array(
        'ajax_url' => admin_url( 'admin-ajax.php' )
    ));
}
add_action( 'wp_enqueue_scripts', 'wc_category_filter_enqueue_scripts' );

// Create the product category filter form as a shortcode
function wc_category_filter_form() {
    $taxonomies = get_taxonomies();
    echo '<pre>';
    print_r($taxonomies);
    echo '</pre>';

    // Check if the taxonomy exists
    if (!taxonomy_exists('product_cat')) {
        return '<p>Error: The "product_cat" taxonomy does not exist.</p>';
    }

    // Get WooCommerce product categories
    $categories = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false, // Change this to false to show all categories
    ));

    ob_start();

    echo '<pre>';
    print_r($categories); // Debug: Print the categories array
    echo '</pre>';

    if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
        echo '<form id="wc-category-filter">';
        echo '<select name="product_cat" id="product_cat">';
        echo '<option value="">' . esc_html__( 'Select Category', 'wc-category-filter' ) . '</option>';
        foreach ( $categories as $category ) {
            echo '<option value="' . esc_attr( $category->slug ) . '">' . esc_html( $category->name ) . '</option>';
        }
        echo '</select>';
        echo '<button type="submit">' . esc_html__( 'Filter', 'wc-category-filter' ) . '</button>';
        echo '</form>';
        echo '<div id="wc-category-filter-results"></div>';
    } else {
        echo '<p>Error: No categories found or an error occurred.</p>';
        if ( is_wp_error( $categories ) ) {
            echo '<p>Error message: ' . esc_html( $categories->get_error_message() ) . '</p>';
        } else {
            echo '<p>Categories array is empty.</p>';
        }
    }

    return ob_get_clean();
}

// Register the shortcode
function wc_category_filter_shortcode() {
    $output = "<!-- WC Category Filter Shortcode Start -->\n";
    $output .= wc_category_filter_form();
    $output .= "\n<!-- WC Category Filter Shortcode End -->";
    return $output;
}
add_shortcode( 'wc_category_filter', 'wc_category_filter_shortcode' );

// Handle Ajax request for filtering products
function wc_category_filter() {
    check_ajax_referer( 'wc_category_filter_nonce', 'nonce' );

    $category = isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : '';

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'product_cat'    => $category,
    );

    $query = new WP_Query( $args );

    ob_start();

    if ( $query->have_posts() ) {
        woocommerce_product_loop_start();
        while ( $query->have_posts() ) {
            $query->the_post();
            wc_get_template_part( 'content', 'product' );
        }
        woocommerce_product_loop_end();
    } else {
        echo '<p>' . esc_html__( 'No products found', 'wc-category-filter' ) . '</p>';
    }

    wp_reset_postdata();
    $products = ob_get_clean();

    wp_send_json_success( $products );
}
add_action( 'wp_ajax_wc_category_filter', 'wc_category_filter' );
add_action( 'wp_ajax_nopriv_wc_category_filter', 'wc_category_filter' );
