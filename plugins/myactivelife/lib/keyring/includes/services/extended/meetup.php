<?php

class Keyring_Service_Meetup extends Keyring_Service_OAuth2 {
	const NAME  = 'meetup';
	const LABEL = 'MeetUp';

	function __construct() {
		parent::__construct();
		// Enable "basic" UI for entering key/secret
		if ( ! KEYRING__HEADLESS_MODE ) {
			add_action( 'keyring_meetup_manage_ui', array( $this, 'basic_ui' ) );
			add_filter( 'keyring_meetup_basic_ui_intro', array( $this, 'basic_ui_intro' ) );
		}

		$this->set_endpoint( 'authorize',    'https://secure.meetup.com/oauth2/authorize',   'GET'  );
		$this->set_endpoint( 'access_token', 'https://secure.meetup.com/oauth2/access',       'POST' );
		$this->set_endpoint( 'user',         'https://api.meetup.com/2/member/self',    'GET'  );

		$creds = $this->get_credentials();
		$this->app_id  = $creds['app_id'];
		$this->key     = $creds['key'];
		$this->secret  = $creds['secret'];

		$this->consumer = new OAuthConsumer( $this->key, $this->secret, $this->callback_url );
		$this->signature_method = new OAuthSignatureMethod_HMAC_SHA1;

		$this->authorization_header    = 'Bearer';
		$this->authorization_parameter = false;
	}

	function basic_ui_intro() {
		echo '<p>' . sprintf( __( 'You\'ll need to <a href="%s">register a new application</a> on Meetup so that you can connect.', 'keyring' ), 'https://secure.meetup.com/meetup_api/oauth_consumers/' ) . '</p>';
		echo '<p>' . __( "Once you've registered your application, copy the <strong>Client ID</strong> into the <strong>App ID </strong> field below, and the <strong>Client Secret</strong> value into <strong>API Secret</strong>.", 'keyring' ) . '</p>';
	}

	function build_token_meta( $token ) {
		$expires = isset( $token['expires_in'] ) ? gmdate( 'Y-m-d H:i:s', time() + $token['expires_in'] ) : 0;
		
		$meta = array(
			'refresh_token' => $token['refresh_token'],
			'expires'       => $expires,
			'_classname'    => get_called_class(),
		);
		
		$this->set_token(
			new Keyring_Access_Token(
				$this->get_name(),
				$token['access_token'],
				array()
			)
		);
		$response = $this->request( $this->user_url, array( 'method' => $this->user_method ) );
		if ( Keyring_Util::is_error( $response ) ) {
			
		} else {
			// Only useful thing in that request is userID
			$meta['user_id'] = (int) $response->id;
			$meta['name'] = $response->name;
			$meta['picture'] = $response->photo->photo_link;

			return apply_filters( 'keyring_access_token_meta', $meta, 'meetup', $token, $profile, $this );
		}
		return array();
	}

	function get_display( Keyring_Access_Token $token ) {
		return $token->get_meta( 'name' );;
	}
	
	function test_connection() {
		$this->maybe_refresh_token();

		$res = $this->request( 'https://api.meetup.com/2/member/self' );
		if ( !Keyring_Util::is_error( $res ) )
			return true;

		return $res;
	}
	
	
	function maybe_refresh_token() {
		global $wpdb;

		if ( empty( $this->token->meta ) || empty( $this->token->meta['expires'] ) )
			return;
	
		if ( $this->token->meta['expires'] && $this->token->is_expired() ) {
			$api_url  = 'https://secure.meetup.com/oauth2/access';
			
			$refresh = $this->request( $api_url, array(
				'method'       => 'POST',
				'raw_response' => true,
				'body' => array(
					'client_id'=>$this->key,
					'client_secret'=>$this->secret,
					'redirect_uri'=>$this->callback_url,
					'refresh_token'=>$this->token->meta['refresh_token'],
					'grant_type'=>'refresh_token')
			) );

			if ( !Keyring_Util::is_error( $refresh ) ) {
				$token_id = $this->token->unique_id;
				$token = $this->parse_access_token( $refresh );

				// Fake request token
				global $keyring_request_token;
				$keyring_request_token = new Keyring_Request_Token(
					$this->get_name(),
					array()
				);
				// Build (real) access token
				
				$access_token = new Keyring_Access_Token(
					$this->get_name(),
					$token['access_token'],
					$this->build_token_meta( $token ),
					$token_id
				);
					// Store the updated access token
				$access_token = apply_filters( 'keyring_access_token', $access_token, $token );
				$id = $this->store->update( $access_token );
				// And switch to using it
				$this->set_token( $access_token );
			}
		}
	}
}

add_action( 'keyring_load_services', array( 'Keyring_Service_Meetup', 'init' ) );