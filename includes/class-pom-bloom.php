<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
DEFINE( 'SCRIPT_DEBUG', 1 );

class POM_Bloom {

    /**
     * The single instance of POM_Bloom.
     * @var    object
     * @access   private
     * @since    1.0.0
     */
    private static $_instance = null;

    /**
     * Settings class object
     * @var     object
     * @access  public
     * @since   1.0.0
     */
    public $settings = null;

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;

    /**
     * The token.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_token;

    /**
     * The main plugin file.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    /**
     * The main plugin directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_dir;

    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;

    /**
     * Suffix for Javascripts.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $script_suffix;

    /**
     * Constructor function.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function __construct( $file = '', $version = '1.0.0' ) {
        $this->_version = $version;
        $this->_token   = 'pom_bloom';

        // Load plugin environment variables
        $this->file       = $file;
        $this->dir        = dirname( $this->file );
        $this->assets_dir = trailingslashit( $this->dir ) . 'assets';
        $this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

        $this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        register_activation_hook( $this->file, array( $this, 'install' ) );

        // Load frontend JS & CSS
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

        // Load admin JS & CSS
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

        // Load API for generic admin functions
        if ( is_admin() ) {
            $this->admin = new POM_Bloom_Admin_API();
        }

        // Handle localisation
        $this->load_plugin_textdomain();
        add_action( 'init', array( $this, 'load_localisation' ), 0 );

        // Load Custom Post types and taxonomies
        $this->set_data_structures();
        $this->program = new POM_Bloom_Program( $this );

    } // End __construct ()

    /**
     * Load custom post types and taxonomies
     * @return void
     */
    public function set_data_structures() {
        $this->register_post_type(
            'bloom-assessments',
            'Bloom Assessment Questions',
            'Bloom Assessment Question',
            'Assessment questions for Bloom',
            array(
                'public'              => false,
                'publicly_queryable'  => false,
                'exclude_from_search' => true,
                'show_ui'             => true,
                'show_in_menu'        => 'pom_bloom_main_settings',
                'show_in_nav_menus'   => false,
                'query_var'           => false,
                'can_export'          => true,
                'rewrite'             => false,
                'capability_type'     => 'page',
                'has_archive'         => false,
                'hierarchical'        => false,
                'supports'            => array( 'title', 'slug' ),
                'menu_position'       => 5,
                'menu_icon'           => 'dashicons-admin-post',
                'taxonomies'          => array( 'bloom-categories' )
            )
        );
        $this->register_post_type(
            'bloom_suggested',
            __( 'Bloom Suggested Goals','pom-bloom' ),
            __( 'Bloom Suggested Goal','pom-bloom' ),
            __( 'Suggested Goals','pom-bloom' ),
            array(
                'public'              => false,
                'publicly_queryable'  => false,
                'exclude_from_search' => true,
                'show_ui'             => true,
                'show_in_menu'        => 'pom_bloom_main_settings',
                'show_in_nav_menus'   => false,
                'query_var'           => false,
                'can_export'          => true,
                'rewrite'             => false,
                'capability_type'     => 'page',
                'has_archive'         => false,
                'hierarchical'        => false,
                'supports'            => array( 'title' ),
                'taxonomies'          => array( 'bloom-categories' )
            )
        );
        $this->register_post_type(
            'bloom-user-goals',
            __( 'Bloom User Goals', 'pom-bloom' ),
            __( 'Bloom User Goal', 'pom-bloom' ),
            __( 'Weekly Bloom Goals', 'pom-bloom' ),
            array(
                'public'              => false,
                'publicly_queryable'  => false,
                'exclude_from_search' => true,
                'show_ui'             => true,
                'show_in_menu'        => 'pom_bloom_main_settings',
                'show_in_nav_menus'   => false,
                'query_var'           => false,
                'can_export'          => true,
                'rewrite'             => false,
                'capability_type'     => 'page',
                'has_archive'         => false,
                'hierarchical'        => false,
                'supports'            => array( 'title', 'author' ),
                'taxonomies'          => array( 'bloom-categories', 'bloom-goalset' )
            )
        );
        $this->register_taxonomy(
            'bloom-categories',
            __( 'Bloom Categories', 'pom-bloom' ),
            __( 'Bloom Category', 'pom-bloom' ),
            array( 'bloom-assessments', 'bloom-user-goals', 'bloom-suggested' ),
            array(
                'hierarchical'          => true,
                'public'                => false,
                'show_ui'               => true,
                'show_in_nav_menus'     => true,
                'show_tagcloud'         => false,
                'meta_box_cb'           => null,
                'show_admin_column'     => true,
                'update_count_callback' => '',
                'query_var'             => $this->taxonomy,
                'rewrite'               => false,
                'sort'                  => '',
            )
        );
        $this->register_taxonomy(
            "bloom-goalsets",
            __( 'Bloom Goalsets', 'pom-bloom' ),
            __( 'Bloom Goalset', 'pom-bloom' ),
            array( 'bloom-user-goals' )
        );


    }

    /**
     * Wrapper function to register a new post type
     *
     * @param  string $post_type   Post type name
     * @param  string $plural      Post type item plural name
     * @param  string $single      Post type item single name
     * @param  string $description Description of post type
     *
     * @return object              Post type class object
     */
    public function register_post_type( $post_type = '', $plural = '', $single = '', $description = '', $args = array() ) {

        if ( !$post_type || !$plural || !$single ) {
            return;
        }

        $post_type = new POM_Bloom_Post_Type( $post_type, $plural, $single, $description, $args );

        return $post_type;
    }

    /**
     * Wrapper function to register a new taxonomy
     *
     * @param  string $taxonomy   Taxonomy name
     * @param  string $plural     Taxonomy single name
     * @param  string $single     Taxonomy plural name
     * @param  array  $post_types Post types to which this taxonomy applies
     *
     * @return object             Taxonomy class object
     */
    public function register_taxonomy( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $args = array() ) {

        if ( !$taxonomy || !$plural || !$single ) {
            return;
        }

        $taxonomy = new POM_Bloom_Taxonomy( $taxonomy, $plural, $single, $post_types, $args );

        return $taxonomy;
    }

    /**
     * Load frontend CSS.
     * @access  public
     * @since   1.0.0
     * @return void
     */
    public function enqueue_styles() {
        wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
        wp_enqueue_style( $this->_token . '-frontend' );
    } // End enqueue_styles ()

    /**
     * Load frontend Javascript.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function enqueue_scripts() {
        wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
//		wp_enqueue_script( $this->_token . '-frontend' );
    } // End enqueue_scripts ()

    /**
     * Load admin CSS.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_enqueue_styles( $hook = '' ) {
        wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
//		wp_enqueue_style( $this->_token . '-admin' );
    } // End admin_enqueue_styles ()

    /**
     * Load admin Javascript.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_enqueue_scripts( $hook = '' ) {
        wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
        wp_enqueue_script( $this->_token . '-admin' );
    } // End admin_enqueue_scripts ()

    /**
     * Load plugin localisation
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function load_localisation() {
        load_plugin_textdomain( 'pom-bloom', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
    } // End load_localisation ()

    /**
     * Load plugin textdomain
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function load_plugin_textdomain() {
        $domain = 'pom-bloom';

        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
    } // End load_plugin_textdomain ()

    /**
     * Main POM_Bloom Instance
     *
     * Ensures only one instance of POM_Bloom is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see   POM_Bloom()
     * @return Main POM_Bloom instance
     */
    public static function instance( $file = '', $version = '1.0.0' ) {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self( $file, $version );
        }

        return self::$_instance;
    } // End instance ()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
    } // End __clone ()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
    } // End __wakeup ()

    /**
     * Installation. Runs on activation.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function install() {
        $this->_log_version_number();
    } // End install ()

    /**
     * Log the plugin version number.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    private function _log_version_number() {
        update_option( $this->_token . '_version', $this->_version );
    } // End _log_version_number ()

    private function update() {
        global $wpdb;
        $installed_ver = get_option( $this->_token . '_version' );

        if ( $installed_ver != $this->_version ) {

            switch ( $installed_ver ) {
                // 1.0 -> 1.01
                case "1.0":
                    $table_name = $wpdb->prefix . 'bloom_assessments';

                    $sql = <<<ASSESSMENT_TABLE
CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		user_id mediumint(9) NOT NULL,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		question_id mediumint(9) NOT NULL,
		response tinyint(1) NOT NULL,
		UNIQUE KEY id (id)
	);
ASSESSMENT_TABLE;
                    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                    dbDelta( $sql );

//                    $table_name = $wpdb->prefix . 'bloom_goalsets';
//                    $sql        = <<<GOALSETS_TABLE
//CREATE TABLE $table_name (
//    id mediumint(9) NOT NULL AUTO_INCREMENT,
//    user_id mediumint(9) NOT NULL,
//    goalset date DEFAULT '0000-00-00' NOT NULL,
//    question_id mediumint(9) DEFAULT NULL NULL,
//    question TEXT DEFAULT '' NOT NULL,
//    per_week tinyint(1) DEFAULT 1 NOT NULL,
//    completed INT(9) DEFAULT 0 NOT NULL,
//    UNIQUE KEY id (id)
//    );
//GOALSETS_TABLE;
//                    dbDelta( $sql );

                    update_option( $this->_token . '_version', "1.01" );

            }

            $this->update(); // Keep running update until you've updated to the latest


        }
    }

}