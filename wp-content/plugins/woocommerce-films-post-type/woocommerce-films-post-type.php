<?php
/*
Plugin Name: Woocommerce Films Post Type
Plugin URI:
Description: Woocommerce Films Post Type
Version: 1.0.0
Author: HighSkill
Author URI: https://highskillweb.com/
Tested up to: 4.9.6
*/

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Functions used by plugins
 */
if ( ! class_exists( 'WC_Dependencies' ) )
    require_once 'class-wc-dependencies.php';

/**
 * WC Detection
 */
if ( ! function_exists( 'is_woocommerce_active' ) ) {
    function is_woocommerce_active() {
        return WC_Dependencies::woocommerce_active_check();
    }
}


if ( is_woocommerce_active() ) {

    //current plugin version
    define( 'WOOFPT_VER', '1.0.0' );
    define( 'WOOFPT_FILE', __FILE__ );

    if ( !class_exists( "WooFilmsPostType" ) ) {

        class WooFilmsPostType {

            var $plugin_dir;
            var $plugin_url;

            public function __clone() {
                _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-films-post-type' ), '2.1' );
            }


            public function __wakeup() {
                _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-films-post-type' ), '2.1' );
            }

            /**
             * constructor
             **/
            function __construct() {
                if ( ! class_exists( 'WC_Product_Data_Store_Extend_CPT' ) )
                    require_once 'class-wc-product-data-store-extend-cpt.php';

                $this->plugin_dir = dirname( WOOFPT_FILE ) . '/';
                $this->plugin_url = str_replace( array( 'http:', 'https:' ), '', plugins_url( '', WOOFPT_FILE ) ) . '/';

                add_action( 'init', array( $this, 'register_enable' ), 1 );
                add_action( 'init', array( $this, 'register_post_types' ), 20, 1 );

                add_filter( 'the_content', array( $this, 'films_add_to_cart_button' ), 20, 1 );
                add_filter( 'woocommerce_product_get_price', array( $this, 'films_woocommerce_get_price' ), 10, 2 );
                add_filter( 'woocommerce_data_stores', array( $this, 'woocommerce_data_stores' ), 2 );

                add_action( 'register_form', array( $this, 'show_fields' ) );
                add_action( 'user_register', array( $this, 'register_fields' ) );

                add_filter('login_redirect', array( $this, 'films_reg_redirect' ) );

                add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'redirect_checkout' ), 10, 3 );

                add_shortcode( 'filmsList', array( $this, 'films_list_page' ) );
            }

            /**
             * Set users_can_register enabled
             * @return bool
             */
            function register_enable() {
                $optionUsersCanRegister = (int)get_option( 'users_can_register', 0 );

                if ( $optionUsersCanRegister !== 1 )
                    update_option( 'users_can_register', 1, true );

                return true;
            }

            /**
             * Add to custom post type cart button
             * @param $content
             * @return string
             */
            function films_add_to_cart_button( $content ) {
                global $post;
                if ( $post->post_type !== 'films' )
                    return $content;

                ob_start();
                ?>
                <form action="" method="post">
                    <input name="add-to-cart" type="hidden" value="<?php echo $post->ID ?>" />
                    <input name="quantity" type="number" value="1" min="1"  />
                    <input name="submit" type="submit" value="Add to cart" />
                </form>
                <?php

                return $content . ob_get_clean();
            }

            /**
             * Get price from custom post type
             * @param $price
             * @param $product
             * @return mixed
             */
            function films_woocommerce_get_price( $price, $product ) {
                $price = get_post_meta( $product->id, "price", true );

                return $price;
            }

            /**
             * Register custom post type
             */
            function register_post_types() {
                register_post_type('films', array(
                    'label'  => null,
                    'labels' => array(
                        'name'               => __( 'Films', 'woocommerce-films-post-type' ),
                        'singular_name'      => __( 'Film', 'woocommerce-films-post-type' ),
                        'add_new'            => __( 'Add film', 'woocommerce-films-post-type' ),
                        'add_new_item'       => __( 'Added film', 'woocommerce-films-post-type' ),
                        'edit_item'          => __( 'Edit film', 'woocommerce-films-post-type' ),
                        'new_item'           => __( 'New film', 'woocommerce-films-post-type' ),
                        'view_item'          => __( 'View film', 'woocommerce-films-post-type' ),
                        'search_items'       => __( 'Search film', 'woocommerce-films-post-type' ),
                        'not_found'          => __( 'Not search', 'woocommerce-films-post-type' ),
                        'not_found_in_trash' => __( 'No search in cart', 'woocommerce-films-post-type' ),
                        'menu_name'          => __( 'Films', 'woocommerce-films-post-type' ),
                    ),
                    'description'         => '',
                    'public'              => true,
                    'publicly_queryable'  => null,
                    'exclude_from_search' => null,
                    'show_ui'             => true,
                    'show_in_menu'        => true,
                    'show_in_admin_bar'   => true,
                    'show_in_nav_menus'   => true,
                    'show_in_rest'        => null,
                    'rest_base'           => null,
                    'menu_position'       => null,
                    'menu_icon'           => null,
                    'hierarchical'        => false,
                    'supports'           => array( 'title', 'thumbnail', 'editor', 'excerpt', 'custom-fields'),
                    'taxonomies'          => array('category'),
                    'has_archive'         => false,
                    'rewrite'             => true,
                    'query_var'           => true,
                ) );
            }

            /**
             * Replace WC_Product_Data_Store_CPT class
             * @param $stores
             * @return mixed
             */
            function woocommerce_data_stores ( $stores ) {
                $stores[ 'product' ] = 'WC_Product_Data_Store_Extend_CPT';

                return $stores;
            }

            /**
             * Show skype field on registration page
             */
            function show_fields() {
                ?>
                <p>
                    <label>Skype<br/>
                        <input id="skype" class="input" type="text" value="<?php echo $_POST[ 'skype' ]; ?>" name="skype" /></label>
                </p>
                <?php
            }

            /**
             * Update skype field
             * @param $user_id
             * @param string $password
             * @param array $meta
             */
            function register_fields( $user_id, $password = "", $meta = array() ) {

                if ( !empty( $_POST[ 'skype' ] ) ) {
                    update_user_meta( $user_id, 'skype', $_POST['skype'] );
                }
            }

            /**
             * Redirect after registration user
             * @return bool
             */
            function films_reg_redirect() {
                $page = get_page_by_path( 'films-list' );

                wp_redirect( get_permalink($page->ID) );

                exit;
            }

            /**
             * Redirect after add film to checkout page
             * @param $url
             */
            function redirect_checkout( $url ) {
                return get_permalink( wc_get_page_id( 'checkout' ) );
            }

            /**
             * Show featured films on page
             * @param $atts
             * @return string
             */
            function films_list_page( $atts ) {
                $films = array();

                global $wpdb;

                $productsCategory = '';

                $queryProduct = "SELECT SQL_CALC_FOUND_ROWS  {$wpdb->prefix}posts.ID
                                  FROM {$wpdb->prefix}posts
                                  LEFT JOIN {$wpdb->prefix}term_relationships ON ({$wpdb->prefix}posts.ID = {$wpdb->prefix}term_relationships.object_id)
                                  WHERE 1=1";

                $queryProduct .= " AND {$wpdb->prefix}posts.post_type = 'films'
                                  AND (({$wpdb->prefix}posts.post_status = 'publish'))";

                $resultProduct = $wpdb->get_results( $queryProduct );

                foreach ( $resultProduct as $item ) {
                    $featured = (int)get_post_meta( $item->ID, 'featured', true );

                    if ($featured === 1) {
                        $product = wc_get_product( $item->ID );

                        ob_start();

                        ?>

                        <div>
                            <a href="<?php echo get_permalink( $item->ID ); ?>">
                                <?php echo $product->name; ?>
                            </a>
                        </div>

                        <?php

                        $productsCategory .= ob_get_contents();

                        ob_end_clean();
                    }

                }

                return $productsCategory;
            }

        }

    }

    /**
     * Function to initiate plugin
     */
    function init_woofpt() {

        new WooFilmsPostType();
    }

    add_action( 'plugins_loaded', 'init_woofpt', 99 );
}
