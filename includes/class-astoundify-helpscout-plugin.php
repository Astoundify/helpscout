<?php

class PluginHandler {
	private $input = false;

	/**
	 * Returns the requested HTTP header.
	 *
	 * @param string $header
	 * @return bool|string
	 */
	private function getHeader( $header ) {
		if ( isset( $_SERVER[$header] ) ) {
			return $_SERVER[$header];
		}
		return false;
	}

	/**
	 * Retrieve the JSON input
	 *
	 * @return bool|string
	 */
	private function getJsonString() {
		if ( $this->input === false ) {
			$this->input = @file_get_contents( 'php://input' );
		}
		return $this->input;
	}

	/**
	 * Generate the signature based on the secret key, to compare in isSignatureValid
	 *
	 * @return bool|string
	 */
	private function generateSignature() {
		$str = $this->getJsonString();
		if ( $str ) {
			return base64_encode( hash_hmac( 'sha1', $str, HELPSCOUT_SECRET_KEY, true ) );
		}
		return false;
	}

	/**
	 * Returns true if the current request is a valid webhook issued from Help Scout, false otherwise.
	 *
	 * @return boolean
	 */
	private function isSignatureValid() {
		$signature = $this->generateSignature();

		if ( !$signature || !$this->getHeader( 'HTTP_X_HELPSCOUT_SIGNATURE' ) )
			return false;

		return $signature == $this->getHeader( 'HTTP_X_HELPSCOUT_SIGNATURE' );
	}

	/**
	 * Create a response.
	 *
	 * @return array
	 */
	public function getResponse() {
		$ret = array( 'html' => '' );

		if ( !$this->isSignatureValid() ) {
			return array( 'html' => 'Invalid signature' );
		}
		$data = json_decode( $this->input, true );

		// do some stuff
		$ret['html'] = $this->fetchHtml( $data );

		// Used for debugging
		// $ret['html'] = '<pre>'.print_r($data,1).'</pre>' . $ret['html'];

		return $ret;
	}

	/**
	 * Generate output for the response.
	 *
	 * @param $data
	 * @return string
	 */
	private function fetchHtml( $data ) {
		global $wpdb;

		if ( isset( $data['customer']['emails'] ) && is_array( $data['customer']['emails'] ) ) {

			if(($key = array_search(HELPSCOUT_EMAIL, $messages)) !== false) {
			    unset($data['customer']['emails'][$key]);
			}

		} else {

			if ( $data['customer']['email'] == HELPSCOUT_EMAIL ) {
				return 'Cannot query customer licenses.  E-mail from ' . HELPSCOUT_EMAIL;
			}

		}

		$email = $data[ 'customer' ][ 'email' ];
		$user = get_user_by( 'email', $email );

		$tf_key = get_user_meta( $user->ID, 'tf_key', true );

		$themeinfo = get_user_meta( $user->ID, 'tf_info', true );
		$themeinfo[] = '<strong>Key:</strong> ' . $tf_key;

		$html = '';
		$html .= '<h4 class="toggleBtn"><i class="icon-gear"></i> ThemeForest Information</h4>';
		$html .= '<ul><li>';
		$html .= implode( '</li><li>', $themeinfo );
		$html .= '</li></ul>';

		$rcpinfo = array();

		if ( rcp_is_active( $user->ID ) ) {
			$rcp = sprintf( '<strong style="color: green;">Valid Subscription</strong> %s', $subscription );
		} else {
			$rcp = sprintf( '<strong style="color: red;">Subscription Expired</strong> %s', $subscription );
		}

		$rcpinfo[] = $rcp;
		$rcpinfo[] = sprintf( '<strong>Status</strong>: %s', rcp_get_status( $user->ID ) );
		$rcpinfo[] = sprintf( '<strong>Subscription Level</strong>: %s', rcp_get_subscription( $user->ID ) );
		$rcpinfo[] = sprintf( '<strong>Expiration</strong>: %s', rcp_get_expiration_date( $user->ID ) );

		$html .= '<h4 class="toggleBtn"><i class="icon-gear"></i> Subscription</h4>';
		$html .= '<ul><li>';
		$html .= implode( '</li><li>', $rcpinfo );
		$html .= '</li></ul>';

		return $html;
	}
}