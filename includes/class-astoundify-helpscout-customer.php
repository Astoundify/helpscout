<?php

class Astoundify_Help_Scout_Customer {

	public function __construct() {
		include( 'class-astoundify-helpscout-plugin.php' );

		add_action( 'init', array( $this, 'add_rewrite_endpoints' ) );
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
	}

	public function add_rewrite_endpoints() {
		add_rewrite_endpoint( 'customer', EP_PERMALINK | EP_PAGES );
	}

	public function template_redirect() {
		global $wp_query;

		if ( isset( $wp_query->query_vars[ 'customer' ] ) ) {
			$plugin = new PluginHandler();

			echo json_encode( $plugin->getResponse() );

			exit();
		}
	}

}

new Astoundify_Help_Scout_Customer;