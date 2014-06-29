<?php

include_once( 'helpscout/src/HelpScout/ApiClient.php' );

use HelpScout\ApiClient;

class Astoundify_Help_Scout_RCP {

	public function __construct() {
		add_action( 'init', array( $this, 'setup_actions' ) );
	}

	public function setup_actions() {
		if ( ! is_user_logged_in() || ! get_user_meta( get_current_user_id(), 'tf_key', true ) ) {
			return;
		}

		add_action( 'rcp_after_register_form_fields', array( $this, 'tf_key_field' ) );
		add_action( 'rcp_form_errors', array( $this, 'rcp_form_errors' ), 100 );
		add_action( 'rcp_form_processing', array( $this, 'rcp_form_processing' ), 10, 3 );
	}

	public function tf_key_field() {
	?>
		<p id="rcp_tf_key_wrap">
			<label for="tf_key">ThemeForest Purchase Key</label>
			<input name="rcp_tf_key" id="tf_key" class="required" type="text" <?php if( isset( $_POST['rcp_tf_key'] ) ) { echo 'value="' . esc_attr( $_POST['rcp_tf_key'] ) . '"'; } ?> />
		</p>
	<?php
	}

	public function rcp_form_errors( $postdata ) {
		$existing_keys = get_option( 'tf_keys', array() );

		$tf_key = isset( $_POST[ 'rcp_tf_key' ] ) ? esc_attr( $_POST[ 'rcp_tf_key' ] ) : false;

		if ( $tf_key ) {
			$this->tf_key = $tf_key;
		} else {
			return rcp_errors()->add( 'no-tf-key', __( 'Please enter a ThemeForest Purchase Key' ), 'register' );
		}

		$existing = array_search( $tf_key, $existing_keys );

		if ( false === $existing ) {
			update_option( 'tf_keys', array_merge( array( $tf_key ), $existing_keys ) );
		} else {
			return rcp_errors()->add( 'duplicate-tf-key', __( 'This Purchase Key is already associated with an account.' ), 'register' );
		}

		$url = sprintf(
			'http://marketplace.envato.com/api/edge/%s/%s/verify-purchase:%s.json',
			ENVATO_USERNAME,
			ENVATO_API_KEY,
			$tf_key
		);

		$response = wp_remote_get( $url, array( 'sslverify' => false ) );
		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );

		if ( ! empty( $body[ 'verify-purchase' ] ) ) {
			$this->verify_purchase = $body[ 'verify-purchase' ];
		} else {
			return rcp_errors()->add( 'invalid-tf-key', __( 'Your ThemeForest Purchase Key is invalid.' ), 'register' );
		}
	}

	public function rcp_form_processing( $postdata, $user_id, $price ) {
		update_user_meta( $user_id, 'tf_key', $this->tf_key );
		update_user_meta( $user_id, 'tf_info', $this->verify_purchase );

		$this->helpscout($user_id);
	}

	public function helpscout($user_id) {
		$user = new WP_User( $user_id );

		try {
			$client = ApiClient::getInstance();
			$client->setKey( HELPSCOUT_SUPPORT_API_KEY );

			$customer = new \HelpScout\model\Customer();
			$customer->setFirstName( $user->user_firstname );
			$customer->setLastName( $user->user_lastname );

			// Emails: at least one email is required
			$emailWork = new \HelpScout\model\customer\EmailEntry();
			$emailWork->setValue( $user->user_email );
			$emailWork->setLocation("work");

			$customer->setEmails(array($emailWork));

			$client->createCustomer($customer);
		} catch( Exception $e ) {
			wp_die( $e->getMessage() );
		}
	}

}

new Astoundify_Help_Scout_RCP;