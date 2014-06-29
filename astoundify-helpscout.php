<?php
/*
Plugin Name: Astoundify Help Scout
Plugin URI: https://astoundify.com/
Description: Verify a purchase code from Envato and create a new WordPress account (if a guest) and a new customer on Help Scout.
Version: 1.0.0
Author: Spencer Finnell
Author URI: http://astoundify.com
Requires at least: 3.9.1
Tested up to: 3.9.1
Text Domain: astoundify-helpscout
Domain Path: /languages
*/

class Astoundify_Help_Scout {

	public $plugin_dir;
	public $plugin_url;

	public function __construct() {
		$this->file         = __FILE__;

		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );

		$this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );
		$this->domain       = 'astoundify-helpscout';

		add_action( 'init', array( $this, 'setup_includes' ) );
	}

	public function setup_includes() {
		include( 'config.php' );
		include( 'includes/class-astoundify-helpscout-rcp.php' );
		include( 'includes/class-astoundify-helpscout-customer.php' );
	}

}

new Astoundify_Help_Scout;