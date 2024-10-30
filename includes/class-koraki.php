<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://koraki.io
 * @since      1.0.0
 *
 * @package    Koraki
 * @subpackage Koraki/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Koraki
 * @subpackage Koraki/includes
 * @author     Madusha <madusha@koraki.io>
 */
class Koraki {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Koraki_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;
    
     /**
	 * The Koraki API host.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    Koraki base host.
	 */
	protected $host;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'KORAKI_VERSION' ) ) {
			$this->version = KORAKI_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'koraki';
        $this->host = 'https://api.koraki.io';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Koraki_Loader. Orchestrates the hooks of the plugin.
	 * - Koraki_i18n. Defines internationalization functionality.
	 * - Koraki_Admin. Defines all hooks for the admin area.
	 * - Koraki_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-koraki-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-koraki-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-koraki-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-koraki-public.php';

		$this->loader = new Koraki_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Koraki_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Koraki_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Koraki_Admin( $this->get_plugin_name(), $this->get_version(), $this->host );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        
        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'koraki_add_meta_boxes');
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu_items' );
        $this->loader->add_action( 'toplevel_page_koraki' , $plugin_admin, 'koraki_admin_page' );
        $this->loader->add_action( 'admin_init' , $plugin_admin, 'plugin_admin_init' );
        $this->loader->add_action( 'save_post', $plugin_admin, 'koraki_save_meta_boxes');
        $this->loader->add_action( 'save_post', $plugin_admin, 'koraki_post_created_or_updated', 10, 3 );
        $this->loader->add_action( 'transition_comment_status', $plugin_admin, 'koraki_review_updated', 10, 3 );
        $this->loader->add_action( 'transition_comment_status', $plugin_admin, 'koraki_comment_updated', 10, 3 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Koraki_Public( $this->get_plugin_name(), $this->get_version(), $this->host );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->loader->add_action( 'woocommerce_checkout_order_processed', $plugin_public, 'koraki_order_created' );
        $this->loader->add_action( 'woocommerce_created_customer', $plugin_public, 'koraki_customer_created' );
        $this->loader->add_action( 'comment_post', $plugin_public, 'koraki_review_created' );
		$this->loader->add_action( 'comment_post', $plugin_public, 'koraki_comment_created' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Koraki_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}


}
