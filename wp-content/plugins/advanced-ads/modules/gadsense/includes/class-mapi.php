<?php
class Advanced_Ads_AdSense_MAPI {
	
	const OPTNAME = 'advanced-ads-adsense-mapi';
	
	const ALERTS_URL = 'https://www.googleapis.com/adsense/v1.4/accounts/PUBID/alerts/';
	
	const CID = '400595147946-alk0j13qk563bg94rd4f3ip2t0b2tr5r.apps.googleusercontent.com';
	
	const CS = '5jecyWgvCszB8UxSM0oS1W22';
	
	const CALL_PER_24H = 20;
	
	private static $instance = null;
	
	private static $default_options = array();
	
	private static $empty_account_data = array(
		'default_app' => array(
			'access_token'  => '',
			'refresh_token' => '',
			'expires'       => 0,
			'token_type'    => '',
		),
		'user_app'    => array(
			'access_token'  => '',
			'refresh_token' => '',
			'expires'       => 0,
			'token_type'    => '',
		),
		'ad_units'    => array(),
		'details'     => array(),
		'alerts'      => array(),
	);
	
	private function __construct() {
		
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		
		add_action( 'wp_ajax_advads_gadsense_mapi_confirm_code', array( $this, 'ajax_confirm_code' ) );
		//add_action( 'wp_ajax_advads_gadsense_mapi_get_adUnits', array( $this, 'ajax_get_adUnits' ) );
		add_action( 'wp_ajax_advads_gadsense_mapi_get_details', array( $this, 'ajax_get_account_details' ) );
		add_action( 'wp_ajax_advads_gadsense_mapi_select_account', array( $this, 'ajax_account_selected' ) );
		add_action( 'wp_ajax_advads_mapi_get_adCode', array( $this, 'ajax_get_adCode' ) );
		add_action( 'wp_ajax_advads-mapi-reconstructed-code', array( $this, 'ajax_save_reconstructed_code' ) );
		add_action( 'wp_ajax_advads-mapi-save-manual-code', array( $this, 'ajax_save_manual_code' ) );
		add_action( 'wp_ajax_advads-mapi-revoke-token', array( $this, 'ajax_revoke_tokken' ) );
		add_action( 'wp_ajax_advads-mapi-get-alerts', array( $this, 'ajax_get_account_alerts' ) );
		add_action( 'wp_ajax_advads-mapi-dismiss-alert', array( $this, 'ajax_dismiss_alert' ) );
		add_action( 'wp_ajax_advads-mapi-dismiss-connect-error', array( $this, 'ajax_dismiss_connect_error' ) );
		add_action( 'wp_ajax_advads_gadsense_dashboard', array( 'Advanced_Ads_Overview_Widgets_Callbacks', 'ajax_gadsense_dashboard' ) );

		add_action( 'admin_footer', array( $this, 'admin_footer' ) );
		
		self::$default_options = array(
			'accounts'          => array(),
			'ad_codes'          => array(),
			'unsupported_units' => array(),
			'quota'             => array(
				'count' => self::CALL_PER_24H,
				'ts'    => 0,
			),
			'connect_error' => array(),
		);
		
		add_filter( 'advanced-ads-support-messages', array( 'Advanced_Ads_AdSense_MAPI', 'adsense_warnings_check' ) );
		
		add_action( 'wp_loaded', array( $this, 'update_ad_health_notices' ) );
		
	}
	
	/**
	 * Update all MAPI related notices.
	 */
	public function update_ad_health_notices() {
		$mapi_options = self::get_option();
		
		$connection_error_messages = self::get_connect_error_messages();
		
		$health_class = Advanced_Ads_Ad_Health_Notices::get_instance();
		
		// Last connection failed.
		if ( isset ( $mapi_options['connect_error'] ) && ! empty( $mapi_options['connect_error'] ) ) {
			
			if ( isset( $connection_error_messages[ $mapi_options['connect_error']['reason'] ] ) ) {
				$health_class->add( 'adsense_connect_' . $mapi_options['connect_error']['reason'] );
			} else {
				$health_class->add( 'adsense_connect_' . $mapi_options['connect_error']['reason'], array(
          'text' => esc_html__( 'Last AdSense account connection attempt failed.', 'advanced-ads' ) . ' ' . $mapi_options['connect_error']['message'],
					'type' => 'problem',
				) );
			}
			
			foreach( $health_class->notices as $key => $value ) {
				// There was already a connection error but the user tried again and obtained another error.
				if ( false !== stripos( $key, 'adsense_connect_' ) && 'adsense_connect_' . $mapi_options['connect_error']['reason'] !== $key ) {
					$health_class->remove( $key );
				}
			}
			
		} else {
			
			// Once a connection has been established (or a the warning has been discarded on the AA settings page), remove connection related notices.
			foreach( $health_class->notices as $key => $value ) {
				if ( false !== stripos( $key, 'adsense_connect_' ) ) {
					$health_class->remove( $key );
				}
			}
			
		}
		
		$gadsense_data = Advanced_Ads_AdSense_Data::get_instance();
		$adsense_id = $gadsense_data->get_adsense_id();
		
		$alerts = Advanced_Ads_AdSense_MAPI::get_stored_account_alerts( $adsense_id );
		
		// AdSense account alerts (can not happens simultaneously with the connection error).
		if ( is_array( $alerts ) && isset( $alerts['items'] ) && is_array( $alerts['items'] ) && $alerts['items'] ) {
			
			$alerts_advads_messages = Advanced_Ads_Adsense_MAPI::get_adsense_alert_messages();
			$item_ids = array();
			
			foreach ( $alerts['items'] as $internal_id => $item ) {
				$item_ids[] = $item['id'];
				if ( isset( $alerts_advads_messages[ $item['id'] ] ) ) {
					$health_class->add( 'adsense_alert_' . $item['id'] );
				} else {
					$health_class->add( 'adsense_alert_' . $item['id'], array( 'text' => $item['message'] . ' ' . self::get_adsense_error_link( $item['id'] ), 'type' => 'problem' ) );
				}
				
			}
			
			// Remove notices that no more exist in the AdSense account (or have been dismissed).
			$_remove_ids = array();
			foreach( $health_class->notices as $key => $value ) {
				if ( false !== stripos( $key, 'adsense_alert_' ) ) {
					$alert_id = substr( $key, strlen( 'adsense_alert_' ) );
					if ( ! in_array( $alert_id, $item_ids, true ) ) {
						$_remove_ids[] = $key;
					}
				}
			}
			foreach( $_remove_ids as $id ) {
				$health_class->remove( $id );
			}
			
		} else {
			// No more alerts.
			foreach( $health_class->notices as $key => $value ) {
				if ( false !== stripos( $key, 'adsense_alert_' ) ) {
					$health_class->remove( $key );
				}
			}
			
		}
	}
	
	/**
	 * Discard account connection error
	 */
	public function ajax_dismiss_connect_error() {
		$nonce = isset( $_GET['nonce'] ) ? $_GET['nonce'] : '';
        if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
            die;
        }
		if ( false !== wp_verify_nonce( $nonce, 'advads-mapi' ) ) {
			$options = self::get_option();
			$options['connect_error'] = array();
			update_option( self::OPTNAME, $options );
			echo 1;
		}
		die;
	}
	
	/**
	 * Get available quota and eventual message about remaining call
	 */
	public function get_quota() {
		$options = $this->get_option();
		$now     = time();
		if ( self::use_user_app() ) {
			return array( 'count' => PHP_INT_MAX );
		} else {
			if ( $now > $options['quota']['ts'] + ( 24 * 3600 ) ) {
				return array(
					'count' => self::CALL_PER_24H,
				);
			} else {
				$msg = $this->get_quota_msg();
				return array(
					'count' => $options['quota']['count'],
					'msg'   => $msg,
				);
			}
		}
	}
	
	/**
	 *  Get the readable quota
	 */
	public function get_quota_msg() {
		
		$options = $this->get_option();
		$now     = time();
		$secs    = $options['quota']['ts'] + ( 24 * 3600 ) - $now;
		$hours   = floor( $secs / 3600 );
		$mins    = ceil( ( $secs - ( $hours * 3600 ) ) / 60 );
		
		if ( 60 == $mins ) {
			$hours += 1;
			$mins   = 0;
		}
		
		if ( 0 == $options['quota']['count'] ) {
			
			$msg = sprintf(
				/*
				 commented out so that these unused strings don’t show up for translators; using fixed strings instead in case we forget this when we might add the check again later
				 _x( 'No API call left before %1$s %2$s.', 'No call left for the next X hours Y minutes.', 'advanced-ads' ),
				 sprintf( _n( '%s hour', '%s hours', $hours, 'advanced-ads' ), $hours ),
				 sprintf( _n( '%s minute', '%s minutes', $mins, 'advanced-ads' ), $mins )
				 */
				'No API call left before %1$s %2$s.',
				sprintf( '%s hours', $hours ),
				sprintf( '%s minutes', $mins )
				);
			
			if ( 0 == $hours ) {
				/*
				 commented out so that these unused strings don’t show up for translators; using fixed strings instead in case we forget this when we might add the check again later
				 $msg = sprintf(
				 _x( 'No API call left before %s.', 'No call left for the next X time.', 'advanced-ads' ),
				 sprintf( _n( '%s minute', '%s minutes', $mins, 'advanced-ads' ), $mins )
				 );
				 */
				$msg = 'No API call left before.';
			}
			
			if ( 0 == $mins ) {
				/*
				 commented out so that these unused strings don’t show up for translators; using fixed strings instead in case we forget this when we might add the check again later
				 $msg = sprintf(
				 _x( 'No API call left before %s.', 'No call left for the next X time.', 'advanced-ads' ),
				 sprintf( _n( '%s hour', '%s hours', $hours, 'advanced-ads' ), $hours )
				 );
				 */
				$msg = 'No API call left.';
			}
		} else {
			
			$msg = sprintf(
				/*
				 commented out so that these unused strings don’t show up for translators; using fixed strings instead in case we forget this when we might add the check again later
				 _x( '%1$s for the next %2$s %3$s.', 'Calls remaining for the next X hours Y minutes.', 'advanced-ads' ),
				 sprintf( _n( '%s API call remaining.', '%s API calls remaining.', $options['quota']['count'], 'advanced-ads' ), $options['quota']['count'] ),
				 sprintf( _n( '%s hour', '%s hours', $hours, 'advanced-ads' ), $hours ),
				 sprintf( _n( '%s minute', '%s minutes', $mins, 'advanced-ads' ), $mins )
				 */
				'%1$s for the next %2$s %3$s',
				sprintf( '%s API calls remaining', $options['quota']['count'] ),
				sprintf( '%s hours', $hours ),
				sprintf( '%s minutes', $mins )
				);
			
			if ( 0 == $hours ) {
				/*
				 commented out so that these unused strings don’t show up for translators; using fixed strings instead in case we forget this when we might add the check again later
				 $msg = sprintf(
				 _x( '%1$s for the next %2$s', 'Calls remaining for the next X time.', 'advanced-ads' ),
				 sprintf( _n( '%s API call remaining.', '%s API calls remaining.', $options['quota']['count'], 'advanced-ads' ), $options['quota']['count'] ),
				 sprintf( _n( '%s minute', '%s minutes', $mins, 'advanced-ads' ), $mins )
				 );
				 */
				$msg = sprintf( '%s API calls remaining.', $options['quota']['count'] );
			}
			
			if ( 0 == $mins ) {
				/*
				 commented out so that these unused strings don’t show up for translators; using fixed strings instead in case we forget this when we might add the check again later
				 $msg = sprintf(
				 _x( '%1$s for the next %2$s', 'calls remaining for the next X time', 'advanced-ads' ),
				 sprintf( _n( '%s API call remaining', '%s API calls remaining', $options['quota']['count'], 'advanced-ads' ), $options['quota']['count'] ),
				 sprintf( _n( '%s hour', '%s hours', $hours, 'advanced-ads' ), $hours )
				 );
				 */
				$msg = sprintf(
					'%1$s for the next %2$s',
					sprintf( '%s API calls remaining', $options['quota']['count'] ),
					sprintf( '%s hours', $hours )
					);
			}
		}
		return $msg;
	}
	
	/**
	 *  Decrement quota by 1, and return message about remaining call
	 */
	public function decrement_quota() {
		$options = $this->get_option();
		if ( 0 < $options['quota']['count'] ) {
			$options['quota']['count']--;
			$now = time();
			if ( $now > $options['quota']['ts'] + ( 24 * 3600 ) ) {
				$options['quota']['ts'] = $now;
			}
			update_option( self::OPTNAME, $options );
			return $this->get_quota_msg();
		}
	}
	
	/**
	 * Return the ad code for a given client and unit
	 *
	 * @return [str]|[arr] the ad code or info on the error.
	 */
	public function get_ad_code( $adUnit ) {
		$gadsense_data = Advanced_Ads_AdSense_Data::get_instance();
		$adsense_id    = $gadsense_data->get_adsense_id();
		$options       = self::get_option();
		
		$gadsense_data = Advanced_Ads_AdSense_Data::get_instance();
		$adsense_id    = $gadsense_data->get_adsense_id();
		
		$url          = 'https://www.googleapis.com/adsense/v1.4/accounts/' . $adsense_id . '/adclients/ca-' . $adsense_id . '/adunits/' . $adUnit . '/adcode';
		$access_token = self::get_access_token( $adsense_id );
		
		if ( ! isset( $access_token['msg'] ) ) {
			$headers  = array(
				'Authorization' => 'Bearer ' . $access_token,
			);
			$response = wp_remote_get( $url, array( 'headers' => $headers ) );
			self::log( 'Get ad code for ad Unit [' . $adUnit . ']' );
			
			if ( is_wp_error( $response ) ) {
				return array(
					'status' => false,
					'msg'    => sprintf( esc_html__( 'Error while retrieving ad code for "%s".', 'advanced-ads' ), $adUnit ),
					'raw'    => $response->get_error_message(),
				);
			} else {
				$adCode = json_decode( $response['body'], true );
				if ( null === $adCode || ! isset( $adCode['adCode'] ) ) {
					if ($adCode['error'] &&
						$adCode['error']['errors'] &&
						isset( $adCode['error']['errors'][0] ) &&
						isset( $adCode['error']['errors'][0]['reason'] ) &&
						'doesNotSupportAdUnitType' == $adCode['error']['errors'][0]['reason']
						) {
							if ( array_key_exists( $adUnit, $options['ad_codes'] ) ) {
								if ( array_key_exists( $adUnit, $options['unsupported_units'] ) ) {
									unset( $options['unsupported_units'][ $adUnit ] );
								}
							} else {
								$options['unsupported_units'][ $adUnit ] = 1;
							}
							update_option( self::OPTNAME, $options );
							return array(
								'status' => false,
								'msg'    => 'doesNotSupportAdUnitType',
							);
						} else {
							return array(
								'status' => false,
								'msg'    => sprintf( esc_html__( 'Invalid response while retrieving ad code for "%s".', 'advanced-ads' ), $adUnit ),
								'raw'    => $response['body'],
							);
						}
				} else {
					$options['ad_codes'][ $adUnit ] = $adCode['adCode'];
					if ( isset( $options['unsupported_units'][ $adUnit ] ) ) {
						unset( $options['unsupported_units'][ $adUnit ] );
					}
					update_option( self::OPTNAME, $options );
					return $adCode['adCode'];
				}
			}
		} else {
			// return the original error info
			return $access_token;
		}
	}
	
	/**
	 *  Get/Update ad unit list for a given client
	 *
	 *  @return [bool]|[array] TRUE on success, error info (as array) if an error occurred.
	 */
	public static function get_ad_units( $account ) {
		$gadsense_data = Advanced_Ads_AdSense_Data::get_instance();
		$url           = 'https://www.googleapis.com/adsense/v1.4/accounts/' . $account . '/adclients/ca-' . $account . '/adunits?includeInactive=true';
		$access_token  = self::get_access_token( $account );
		
		$options = self::get_option();
		
		if ( ! isset( $access_token['msg'] ) ) {
			$headers  = array(
				'Authorization' => 'Bearer ' . $access_token,
			);
			$response = wp_remote_get( $url, array( 'headers' => $headers ) );
			self::log( 'Get ad units list for ca-' . $account );
			
			if ( is_wp_error( $response ) ) {
				return array(
					'status' => false,
					'msg'    => sprintf( esc_html__( 'Error while retrieving adUnits list for "%s".', 'advanced-ads' ), $account ),
					'raw'    => $response->get_error_message(),
				);
			} else {
				$adUnits = json_decode( $response['body'], true );
                
				if ( null === $adUnits || ! isset( $adUnits['kind'] ) || 'adsense#adUnits' !== $adUnits['kind'] ) {
				    $error_message = sprintf( esc_html__( 'Invalid response while retrieving adUnits list for "%s".', 'advanced-ads' ), $account );
                    //  check the response for errors and display them for better problem solving
                    if ($adUnits && isset($adUnits['error']) && isset($adUnits['error']['errors']) && count($adUnits['error']['errors'])) {
                        foreach ($adUnits['error']['errors'] as $err) {
                            $hint = self::get_adsense_error_hint($err['reason']);
                            if ($hint) {
                                $error_message .= "<p class=\"description\">$hint</p>";
                            }
                            $error_message .= '<p class="description">' . __('Reason:', 'advanced-ads') . ' "' . $err['reason'] . '"<br>';
                            $error_message .= __('Message:', 'advanced-ads') . ' "' . $err['message'] . '"</p>';
                        }
                    }

					return array(
						'status' => false,
						'msg'    => $error_message,
						'raw'    => $response['body'],
					);
                } else {
                    if ( ! isset( $adUnits['items'] ) ) {
                        return array(
                            'status' => false,
                            'msg'    => sprintf(
                                    // translators: %1$s is the AdSense publisher ID; %2$s a starting a tag to the AdSense ad unit list and %3$s the closing link.
                                    esc_html__( 'The account "%1$s" does not seem to have any ad units. Please create some %2$shere%3$s.', 'advanced-ads' ),
                                    $account,
                                    '<a href="https://www.google.com/adsense/new/u/0/'.$account.'/main/myads-viewall-adunits?product=SELF_SERVICE_CONTENT_ADS" target="_blank">',
                                    '</a>' ),
                            'raw'    => $response['body'],
                        );
                    } else {
                        $new_adUnits = array();
                        foreach ( $adUnits['items'] as $item ) {
                            $new_adUnits[ $item['id'] ] = $item;
                        }
                        $options['accounts'][ $account ]['ad_units'] = $new_adUnits;
                        update_option( self::OPTNAME, $options );
                        return true;
                    }
                }
			}
		} else {
			// return the original error info
			return $access_token;
		}
	}
	
	/**
	 *  Get the appropriate access token (default one or from user's Google app). Update it if needed.
	 *
	 *  @return [str]|[array] the token on success, error info (as array) if an error occurred.
	 */
	public static function get_access_token( $account ) {
		$options = self::get_option();
		if ( isset( $options['accounts'][ $account ] ) ) {
			if ( self::use_user_app() ) {
				if ( time() > $options['accounts'][ $account ]['user_app']['expires'] ) {
					$new_tokens = self::renew_access_token( $account );
					if ( $new_tokens['status'] ) {
						return $new_tokens['access_token'];
					} else {
						// return all error info [arr]
						return $new_tokens;
					}
				} else {
					return $options['accounts'][ $account ]['user_app']['access_token'];
				}
			} else {
				if ( time() > $options['accounts'][ $account ]['default_app']['expires'] ) {
					$new_tokens = self::renew_access_token( $account );
					if ( $new_tokens['status'] ) {
						return $new_tokens['access_token'];
					} else {
						// return all error info [arr]
						return $new_tokens;
					}
				} else {
					return $options['accounts'][ $account ]['default_app']['access_token'];
				}
			}
		} else {
			// Account does not exists.
			if ( ! empty( $options['accounts'] ) ) {
				// There is another account connected.
				return array(
					'status' => false,
					'msg' => esc_html__( 'It seems that some changes have been made in the Advanced Ads settings. Please refresh this page.', 'advanced-ads' ),
					'reload' => true,
				);
			} else {
				// No account at all.
				return array(
					'status' => false,
					'msg' => wp_kses( sprintf( __( 'Advanced Ads does not have access to your account (<code>%s</code>) anymore.', 'advanced-ads' ), $account ), array( 'code' => true ) ),
					'reload' => true,
				);
			}
		}
	}
	
	/**
	 *  Renew the current access token.
	 */
	public static function renew_access_token( $account ) {
		$cid           = self::CID;
		$cs            = self::CS;
		$options       = self::get_option();
		$access_token  = $options['accounts'][ $account ]['default_app']['access_token'];
		$refresh_token = $options['accounts'][ $account ]['default_app']['refresh_token'];
		
		if ( self::use_user_app() ) {
			$gadsense_data = Advanced_Ads_AdSense_Data::get_instance();
			$_options      = $gadsense_data->get_options();
			$cid           = ADVANCED_ADS_MAPI_CID;
			$cs            = ADVANCED_ADS_MAPI_CIS;
			$access_token  = $options['accounts'][ $account ]['user_app']['access_token'];
			$refresh_token = $options['accounts'][ $account ]['user_app']['refresh_token'];
		}
		
		$url  = 'https://www.googleapis.com/oauth2/v4/token';
		$args = array(
			'body' => array(
				'refresh_token' => $refresh_token,
				'client_id'     => $cid,
				'client_secret' => $cs,
				'grant_type'    => 'refresh_token',
			),
		);
		
		$response = wp_remote_post( $url, $args );
		self::log( 'Refresh access token' );
		
		if ( is_wp_error( $response ) ) {
			return array(
				'status' => false,
				'msg'    => sprintf( esc_html__( 'error while renewing access token for "%s"', 'advanced-ads' ), $account ),
				'raw'    => $response->get_error_message(),
			);
		} else {
			$tokens = json_decode( $response['body'], true );
			//  checking for the $tokens is not enough. it can be empty.
            //  monitored this, when the access token is revoked from the outside
            //  this can happen, when the user connects from another domain.
			if ( null !== $tokens && isset($tokens['expires_in']) ) {
				$expires = time() + absint( $tokens['expires_in'] );
				if ( self::use_user_app() ) {
					$options['accounts'][ $account ]['user_app']['access_token'] = $tokens['access_token'];
					$options['accounts'][ $account ]['user_app']['expires']      = $expires;
				} else {
					$options['accounts'][ $account ]['default_app']['access_token'] = $tokens['access_token'];
					$options['accounts'][ $account ]['default_app']['expires']      = $expires;
				}
				update_option( self::OPTNAME, $options );
				return array(
					'status'       => true,
					'access_token' => $tokens['access_token'],
				);
			} else {
				return array(
					'status' => false,
					'msg'    => sprintf( esc_html__( 'invalid response received while renewing access token for "%s"', 'advanced-ads' ),  $account ),
					'raw'    => $response['body'],
				);
			}
		}
	}
	
	/**
	 *  Recoke a refresh token
	 */
        public function ajax_revoke_tokken() {
		
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
        if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
            die;
        }
		if ( false !== wp_verify_nonce( $nonce, 'advads-mapi' ) ) {
			$adsense_id = stripslashes( $_POST['adsenseId'] );
			$options    = self::get_option();
			
			if ( self::use_user_app() ) {
				$token = $options['accounts'][ $adsense_id ]['user_app']['refresh_token'];
			} else {
				$token = $options['accounts'][ $adsense_id ]['default_app']['refresh_token'];
			}
			$url  = 'https://accounts.google.com/o/oauth2/revoke?token=' . $token;
			$args = array(
				'timeout' => 5,
				'header'  => array( 'Content-type' => 'application/x-www-form-urlencoded' ),
			);
			
			$response = wp_remote_post( $url, $args );
			
			self::log( 'Revoke API access for ca-' . $adsense_id );
			
			if ( is_wp_error( $response ) ) {
				echo json_encode( array( 'status' => false ) );
			} else {
			    //  remove all the adsense stats
                global $wpdb;
                $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'advanced_ads_adsense_report_%';" );
                delete_option("advanced-ads-adsense-dashboard-filter");

				header( 'Content-Type: application/json' );
				unset( $options['accounts'][ $adsense_id ] );
				update_option( self::OPTNAME, $options );
				echo json_encode( array( 'status' => true ) );
			}
		}
		die;
		
	}

	/**
	 * When a user manually adds an ad code, save it
	 */
	public function ajax_save_manual_code() {
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
			die();
		}

		if ( ! wp_verify_nonce( isset( $_POST['nonce'] ) ? $_REQUEST['nonce'] : '', 'advads-mapi' ) ) {
			die();
		}

		$publisher_id = sanitize_text_field( wp_unslash( isset( $_POST['parsed_code']['pubId'] ) ? $_POST['parsed_code']['pubId'] : '' ) );
		if ( ! $this->check_valid_publisher( $publisher_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'This ad code is from a different AdSense Account', 'advanced-ads' ),
				),
				400
			);
		}

		if ( empty( $_POST['parsed_code']['slotId'] ) || empty( $_POST['raw_code'] ) ) {
			die();
		}

		static $options;
		if ( is_null( $options ) ) {
			$options = self::get_option();
		}

		$slot_id = 'ca-' . $publisher_id . ':' . sanitize_text_field( wp_unslash( $_POST['parsed_code']['slotId'] ) );

		// phpcs:disable WordPress.Security
		$options['ad_codes'][ $slot_id ] = urldecode( $_POST['raw_code'] );
		// phpcs:enable

		if ( array_key_exists( $slot_id, $options['unsupported_units'] ) ) {
			unset( $options['unsupported_units'][ $slot_id ] );
		}

		wp_send_json_success( array( 'updated' => update_option( self::OPTNAME, $options ) ) );
	}

	/**
	 * Check if the provided AdSense Publisher ID matches the saved ID
	 *
	 * @param string $pub AdSense Publisher ID.
	 *
	 * @return bool
	 */
	protected function check_valid_publisher( $pub ) {
		return Advanced_Ads_AdSense_Data::get_instance()->get_adsense_id() === $pub;
	}

	/**
	 * Save ad code reconstructed from ad parameters
	 */
	public function ajax_save_reconstructed_code() {
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
        if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
            die;
        }
		if ( false !== wp_verify_nonce( $nonce, 'advads-mapi' ) ) {
			$code          = stripslashes( $_POST['code'] );
			$slot          = stripslashes( $_POST['slot'] );
			$gadsense_data = Advanced_Ads_AdSense_Data::get_instance();
			$adsense_id    = $gadsense_data->get_adsense_id();
			$options       = self::get_option();
			$options['ad_codes'][ 'ca-' . $adsense_id . ':' . $slot ] = $code;
			update_option( self::OPTNAME, $options );
			header( 'Content-Type: application/json' );
			echo json_encode( array( 'status' => true ) );
		}
		die;
	}
	
	/**
	 * Get ad code for a given unit
	 */
	public function ajax_get_adCode() {
        if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
            die;
        }
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		if ( false !== wp_verify_nonce( $nonce, 'advads-mapi' ) ) {
			$unit = stripslashes( $_POST['unit'] );
			
			if ( ! self::use_user_app() ) {
				$quota = $this->get_quota();
				
				// No more quota left
				if ( $quota['count'] < 1 ) {
					$quota_msg = $this->get_quota_msg();
					header( 'Content-Type: application/json' );
					$quota_msg = $this->get_quota_msg();
					echo wp_json_encode(
						array(
							'quota'    => 0,
							'quotaMsg' => $quota_msg,
						)
						);
					die;
				}
			}
			
			$code = $this->get_ad_code( $unit );
			
			/**
			 * Ad code is returned as string. Otherwise it's an error
			 */
			if ( is_string( $code ) ) {
				$ad_units = array_filter(
					Advanced_Ads_Network_Adsense::get_instance()->get_external_ad_units(),
					function ( Advanced_Ads_Ad_Network_Ad_Unit $ad_unit ) use ( $unit ) {
						return $ad_unit->id === $unit;
					}
				);
				$ad_unit  = reset( $ad_units );
				$response = array(
					'code' => $code,
					'type' => self::format_ad_data( $ad_unit, 'type' ),
				);

				/**
				 *  Add quota info for default API creds
				 */
				if ( ! self::use_user_app() ) {
					$quota                = $this->get_quota();
					$quota_msg            = $this->get_quota_msg();
					$response['quota']    = $quota['count'];
					$response['quotaMsg'] = $quota_msg;
				}
				
				header( 'Content-Type: application/json' );
				echo wp_json_encode( $response );
				
			} else {
				
				// return info about the error
				header( 'Content-Type: application/json' );
				echo wp_json_encode( $code );
				
			}
		}
		die;
	}

    /**
     *  Dismiss an account alert
     */
    public function ajax_dismiss_alert() {
        if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
            die;
        }
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		if ( false !== wp_verify_nonce( $nonce, 'advads-mapi' ) ) {
			$account = stripslashes( $_POST['account'] );
            $id = stripslashes( $_POST['id'] );
            $options = self::get_option();
            
            $items = array();
            
            // the account exists.
            if ( isset( $options['accounts'][ $account ] ) ) {
                // the alert exists.
                if ( isset( $options['accounts'][ $account ]['alerts']['items'][ $id ] ) ) {
                    unset( $options['accounts'][ $account ]['alerts']['items'][ $id ] );
                    
                    update_option( self::OPTNAME, $options );
                    $items = $options['accounts'][ $account ]['alerts']['items'];
                }
            }
            header( 'Content-Type: application/json' );
            echo json_encode( array(
                'status' => true,
                'alerts' => $items,
                'length' => count( $items ),
            ) );
        }
        die;
    }
    
    /**
     *  Get / Update the list of alerts on an AdSense account.
     */
    public function ajax_get_account_alerts() {
        if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
            die;
        }
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		if ( false !== wp_verify_nonce( $nonce, 'mapi-alerts' ) ) {
			$account = stripslashes( $_POST['account'] );
            $options = self::get_option();
            
            // the account exists.
            if ( isset( $options['accounts'][ $account ] ) && self::has_token( $account ) ) {
                $access_token = $this->get_access_token( $account );
                $url = str_replace( 'PUBID', $account, self::ALERTS_URL );
                
                // the token is valid.
                if ( ! isset( $access_token['msg'] ) ) {
                    $headers  = array(
                        'Authorization' => 'Bearer ' . $access_token,
                    );
                    $response = wp_remote_get( $url, array( 'headers' => $headers ) );
                    
                    $this->log( 'Get AdSense alerts for ' . $account );
                    
                    // the HTTP response is not an error.
                    if ( ! is_wp_error( $response ) ) {
                        $alerts = json_decode( $response['body'], true );
                        
                        // the response body is valid.
                        if ( null !== $alerts || !is_array( $alerts ) || empty( $alerts['kind'] ) ) {
                            $items = array();
                            if ( isset( $alerts['items'] ) ) {
                                foreach ( $alerts['items'] as $item ) {
                                    // Do not store alerts of type "INFO".
                                    if ( 0 != strcasecmp( $item['severity'], 'INFO' ) ) {
                                        $items[ wp_generate_password( 6, false ) ] = $item;
                                    }
                                }
                            }
                            
                            $alerts_array = array(
                                'items' => $items ,
                                'lastCheck' => time(),
                            );
                            $options['accounts'][ $account ]['alerts'] = $alerts_array;
                            update_option( self::OPTNAME, $options );
                            $results = array(
                                'status' => true,
                                'alerts' => $items,
                                'length' => count( $items ),
                            );
                            header( 'Content-Type:application/json' );
                            echo wp_json_encode( $results );
                        } else {
                            $results = array( 
                                'status' => false,
                                'msg'    => esc_html__( 'Invalid response body while retrieving account alerts', 'advanced-ads' ),
                            );
                            header( 'Content-Type:application/json' );
                            echo wp_json_encode( $results );
                        }
                        
                    } else {
                        $results = array( 
                            'status' => false,
                            'msg'    => esc_html__( 'error while retrieving account alerts', 'advanced-ads' ),
                            'raw'    => $response->get_error_message(),
                        );
                        header( 'Content-Type:application/json' );
                        echo wp_json_encode( $results );
                    }
                } else {
                    // return the original error info
                    return $access_token;
                }
            
            } else {
                header( 'Content-Type:application/json' );
                echo wp_json_encode( array( 'status' => false ) );
            }
            
        }
        die;
    }
    
//    /**
//     * Show / Hide idle ads on the ad list table.
//     */
//    public function ajax_toggle_idle_ads() {
//        if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
//            die;
//        }
//
//		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
//		if ( false !== wp_verify_nonce( $nonce, 'advads-mapi' ) ) {
//
//			$hide = (bool)$_POST['hide'];
//
//			ob_start();
//			Advanced_Ads_AdSense_Admin::get_mapi_ad_selector( $hide );
//			$ad_selector = ob_get_clean();
//
//			$response = array(
//				'status' => true,
//				'html'   => $ad_selector,
//			);
//			header( 'Content-Type: application/json' );
//			echo wp_json_encode( $response );
//		}
//		die;
//	}
	
	/**
	 * Get / Update the ad unit list for a given ad client. The corresponding <select /> input used in the ad selector is passed as a fied of an array
	 */
	public function ajax_get_adUnits() {
        if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
            die;
        }
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		if ( false !== wp_verify_nonce( $nonce, 'advads-mapi' ) && isset($_POST['account'])) {
			$account = stripslashes( $_POST['account'] );
			$units   = self::get_ad_units( $account );
			
			if ( true === $units ) {
				$options  = self::get_option();
				$ad_units = $options['accounts'][ $account ]['ad_units'];
				
				ob_start();
				Advanced_Ads_AdSense_Admin::get_mapi_ad_selector();
				$ad_selector = ob_get_clean();
				
				$response = array(
					'status' => true,
					'html'   => $ad_selector,
				);
				
				/**
				 *  Add quota info for default API creds
				 */
				if ( ! self::use_user_app() ) {
					$quota                = $this->get_quota();
					$quota_msg            = $this->get_quota_msg();
					$response['quota']    = $quota['count'];
					$response['quotaMsg'] = $quota_msg;
				}
			} else {
				/**
				 *  Return the error info [arr]
				 */
				$response = $units;
			}
			header( 'Content-Type: application/json' );
			echo wp_json_encode( $response );
		}
		die;
	}
	
	/**
	 * Save account and token after account selection MCN.
	 */
	public function ajax_account_selected() {
        if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
            die;
        }
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		if ( false !== wp_verify_nonce( $nonce, 'advads-mapi' ) ) {
			$token_data = wp_unslash( $_POST['token_data'] );
			$account    = wp_unslash( $_POST['account'] );
			
			if ( $token_data && $account ) {
				
				self::save_token_from_data( $token_data, $account, array( 'autoads' => isset( $_POST['autoads'] ) ) );
				
				header( 'Content-Type: application/json' );
				echo json_encode(
					array(
						'status'     => true,
						'adsense_id' => $account['id'],
					)
					);
				
			} else {
				
				$error = 'Token data missing';
				if ( $token_data ) {
					$error = 'No account provided';
				}
				header( 'Content-Type: application/json' );
				echo json_encode(
					array(
						'status'    => false,
						'error_msg' => $error,
					)
					);
				
			}
		}
		die;
	}
	
	/**
	 * Get AdSense account details from a new access token.
	 */
	public function ajax_get_account_details() {
        if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
            die;
        }
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		if ( false !== wp_verify_nonce( $nonce, 'advads-mapi' ) ) {
			
			$url        = 'https://www.googleapis.com/adsense/v1.4/accounts';
			$token_data = wp_unslash( $_POST['token_data'] );
			
			if ( ! is_array( $token_data ) ) {
				
				header( 'Content-Type: application/json' );
				echo json_encode(
					array(
						'status'    => false,
						'error_msg' => esc_html__( 'No token provided. Token data needed to get account details.', 'advanced-ads' ),
					)
					);
				die;
				
			}
			
			$headers = array( 'Authorization' => 'Bearer ' . $token_data['access_token'] );
			$response = wp_remote_get( $url, array( 'headers' => $headers ) );
			
			self::log( 'Get account details from new access token' );
			
			if ( is_wp_error( $response ) ) {
				
				header( 'Content-Type: application/json' );
				echo json_encode(
					array(
						'status'    => false,
						'error_msg' => $response->get_error_message(),
					)
					);
				
			} else {
				
				$accounts = json_decode( $response['body'], true );
				
				if ( isset( $accounts['items'] ) ) {
					$options = self::get_option();
					$options['connect_error'] = array();
					update_option( self::OPTNAME, $options );
					
					if ( 2 > count( $accounts['items'] ) ) {
						
						$adsense_id = $accounts['items'][0]['id'];
						self::save_token_from_data( $token_data, $accounts['items'][0], array( 'autoads' => isset( $_POST['autoads'] ) ) );
						
						header( 'Content-Type: application/json' );
						echo json_encode(
							array(
								'status'     => true,
								'adsense_id' => $adsense_id,
							)
							);
						
					} else {
						$html    = '';
						$details = array();
						foreach ( $accounts['items'] as $item ) {
							$html                  .= '<option value="' . esc_attr( $item['id'] ) . '">' . $item['name'] . ' [' . $item['id'] . ']</option>';
							$details[ $item['id'] ] = $item;
						}
						header( 'Content-Type: application/json' );
						echo json_encode(
							array(
								'status'     => true,
								'html'       => $html,
								'details'    => $details,
								'token_data' => $token_data,
							)
							);
						
					}
				} else {
					if ( isset( $accounts['error'] ) ) {
						$msg = esc_html__( 'An error occurred while requesting account details.', 'advanced-ads' );
						if ( isset( $accounts['error']['message'] ) ) {
							$msg = $accounts['error']['message'];
						}
						
						$options = self::get_option();
						$options['connect_error'] = array(
							'message' => $msg,
						);
						
						if ( isset( $accounts['error']['errors'][0]['reason'] ) ) {
							$options['connect_error']['reason'] = $accounts['error']['errors'][0]['reason'];
						}
						
						update_option( self::OPTNAME, $options );
						
						header( 'Content-Type: application/json' );
						echo json_encode(
							array(
								'status'    => false,
								'error_msg' => $msg,
								'raw'       => $accounts['error'],
							)
							);
						
					}
				}
			}
		}
		die;
	}
	
	/**
	 * Submit Google API confirmation code. Save the token and update ad client list.
	 */
	public function ajax_confirm_code() {
        if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
            die;
        }
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		if ( false !== wp_verify_nonce( $nonce, 'advads-mapi' ) ) {
			$code = urldecode( $_POST['code'] );
			$cid  = self::CID;
			$cs   = self::CS;
			
			$use_user_app = self::use_user_app();
			
			if ( $use_user_app ) {
				$cid = ADVANCED_ADS_MAPI_CID;
				$cs  = ADVANCED_ADS_MAPI_CIS;
			}
			
			$code_url     = 'https://www.googleapis.com/oauth2/v4/token';
			$redirect_uri = 'urn:ietf:wg:oauth:2.0:oob';
			$grant_type   = 'authorization_code';
			
			$args = array(
				'timeout' => 10,
				'body'    => array(
					'code'          => $code,
					'client_id'     => $cid,
					'client_secret' => $cs,
					'redirect_uri'  => $redirect_uri,
					'grant_type'    => $grant_type,
				),
			);
			
			$response = wp_remote_post( $code_url, $args );
			
			self::log( 'Confirm authorization code' );
			
			if ( is_wp_error( $response ) ) {
				return json_encode(
					array(
						'status' => false,
						'msg'    => 'error while submitting code',
						'raw'    => $response->get_error_message(),
					)
					);
			} else {
				$token      = json_decode( $response['body'], true );
				
				if ( null !== $token && isset( $token['refresh_token'] ) ) {
					$expires          = time() + absint( $token['expires_in'] );
					$token['expires'] = $expires;
					header( 'Content-Type: application/json' );
					echo json_encode(
						array(
							'status'     => true,
							'token_data' => $token,
						)
						);
					
				} else {
					header( 'Content-Type: application/json' );
					echo json_encode(
						array(
							'status'        => false,
							'response_body' => $response['body'],
						)
						);
				}
			}
		}
		die;
	}
	
	/**
	 * Enqueue admin scripts
	 */
	public function admin_scripts( $hook ) {
		if ( 'advanced-ads_page_advanced-ads-settings' == $hook ) {
			wp_enqueue_script( 'gasense/mapi/settings', GADSENSE_BASE_URL . 'admin/assets/js/mapi-settings.js', array( 'jquery' ), ADVADS_VERSION );
		}
	}
	
	/**
	 * Print alert data in admin footer
	 */
	public function admin_footer() {
		$data = Advanced_Ads_AdSense_Data::get_instance();
		$adsense_id = $data->get_adsense_id();
		$has_token = Advanced_Ads_AdSense_MAPI::has_token( $adsense_id );
		$alerts = self::get_stored_account_alerts( $adsense_id );
		$refresh_alerts = false;
		
		// default value, never checked for alerts.
		if ( array() === $alerts && $has_token ) {
			$refresh_alerts = true;
		}
		if ( $has_token && is_array( $alerts ) && isset( $alerts['lastCheck'] ) ) {
			// check weekly for alerts.
			if ( time() > absint( $alerts['lastCheck'] ) + 3600 * 24 * 7 ) {
				$refresh_alerts = true;
			}
		}
		if ( $refresh_alerts ) {
			$nonce = wp_create_nonce( 'mapi-alerts' );
			?>
            <input type="hidden" id="advads-mapi-refresh-alerts" />
            <script type="text/javascript">
            ;(function($){
                            
                $( '#mapi-alerts-overlay' ).css( 'display', 'block' );

                var pubId = $( '#adsense-id' ).val();
                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'advads-mapi-get-alerts',
                        account: '<?php echo wp_strip_all_tags( $adsense_id ); ?>',
                        nonce: '<?php echo wp_strip_all_tags( $nonce ); ?>',
                    },
                    success:function(response, status, XHR){
                        if ( 'undefined' != typeof response.alerts ) {
                            $( '#advads-mapi-refresh-alerts' ).trigger( 'advadsMapiRefreshAlerts', [response] );
                        }
                        $( '#mapi-alerts-overlay' ).css( 'display', 'none' );
                    },
                    error:function(request, status, error){
                        $( '#mapi-alerts-overlay' ).css( 'display', 'none' );
                    },
                });

            })(window.jQuery);
            </script>
            <?php
        }
    }
    
    public static function log( $task = 'No task provided' ) {
        if ( ! defined( 'ADVANCED_ADS_LOG_ADSENSE_API' ) || ! ADVANCED_ADS_LOG_ADSENSE_API ) {
            return;
        }

        $message = date_i18n( '[Y-m-d H:i:s]' ) . ' ' . $task . "\n";
	    error_log( $message, 3, WP_CONTENT_DIR . '/advanced-ads-google-api-requests.log' );
    }
    
	/**
	 *  Sort ad units list alphabetically
	 */
	public static function get_sorted_adunits( $adunits ) {
		$units_sorted_by_name = array();
		$units_by_id          = array();
		foreach ( $adunits as $unit ) {
			$units_sorted_by_name[ $unit['name'] ] = $unit['id'];
			$units_by_id[ $unit['id'] ]            = $unit;
		}
		ksort( $units_sorted_by_name );
		$units_sorted_by_name = array_flip( $units_sorted_by_name );
		$results              = array();
		foreach ( $units_sorted_by_name as $id => $name ) {
			$results[ $name ] = $units_by_id[ $id ];
		}
		return $results;
	}

	/**
	 * Format ad type and size strings from Google for display
	 *
	 * @param Advanced_Ads_Ad_Network_Ad_Unit $ad_unit the ad unit for which to format the details.
	 * @param string                          $format takes either type or size.
	 *
	 * @return string
	 */
	public static function format_ad_data( Advanced_Ads_Ad_Network_Ad_Unit $ad_unit, $format = 'type' ) {
		if ( 'type' === $format ) {
			$str     = $ad_unit->display_type;
			$options = self::get_option();
			if ( array_key_exists( $ad_unit->id, $options['ad_codes'] ) ) {
				preg_match_all( '/data-ad-format="(?<format>.+?)"|data-ad-layout="(?<layout>.+?)"/', $options['ad_codes'][ $ad_unit->id ], $matches );
				$format = array_filter( $matches['format'] );
				$layout = array_filter( $matches['layout'] );
				$format = reset( $format );
				$layout = reset( $layout );
				if ( empty( $format ) ) {
					$format = '';
				}
				if ( empty( $layout ) ) {
					$layout = '';
				}

				if ( 'autorelaxed' === $format ) {
					$str = _x( 'Matched Content', 'AdSense ad type', 'advanced-ads' );
				} elseif ( 'fluid' === $format ) {
					if ( 'in-article' === $layout ) {
						$str = _x( 'In-article', 'AdSense ad type', 'advanced-ads' );
					} else {
						$str = _x( 'In-feed', 'AdSense ad type', 'advanced-ads' );
					}
				}
			}
			if ( strpos( $str, 'TEXT' ) !== false ) {
				$str = _x( 'Display', 'AdSense ad type', 'advanced-ads' );
			} elseif ( 'LINK' === $str ) {
				$str = _x( 'Link', 'AdSense ad type', 'advanced-ads' );
			}
		} elseif ( 'size' === $format ) {
			// size.
			$str = $ad_unit->display_size;
			$str = str_replace( 'SIZE_', '', $str );
			$str = str_replace( '_', 'x', $str );
			$str = strtolower( $str );
			$str = ucwords( $str );
		} else {
			$str = '';
		}

		return $str;
	}

	/**
	 * Check if the credential are the default ones or from user's app
	 */
	public static function use_user_app() {
		if ( ( defined( 'ADVANCED_ADS_MAPI_CID' ) && '' != ADVANCED_ADS_MAPI_CID ) && ( defined( 'ADVANCED_ADS_MAPI_CIS' ) && '' != ADVANCED_ADS_MAPI_CIS ) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function has_token( $adsense_id = '' ) {
		if ( empty( $adsense_id ) ) {
			return false;
		}

		$has_token = false;
		$options   = self::get_option();
		if ( self::use_user_app() ) {
			if ( isset( $options['accounts'][ $adsense_id ] ) && ! empty( $options['accounts'][ $adsense_id ]['user_app']['refresh_token'] ) ) {
				$has_token = true;
			}
		} else {
			if ( isset( $options['accounts'][ $adsense_id ] ) && ! empty( $options['accounts'][ $adsense_id ]['default_app']['refresh_token'] ) ) {
				$has_token = true;
			}
		}
		return $has_token;

	}

	/**
	 * Save token obtained from confirmation code
	 */
	public static function save_token_from_data( $token, $details, $args = array() ) {

		$options    = self::get_option();
		$adsense_id = $details['id'];

		if ( ! isset( $options['accounts'][ $adsense_id ] ) ) {
			$options['accounts'][ $adsense_id ] = self::$empty_account_data;
		}
		if ( self::use_user_app() ) {
			$options['accounts'][ $adsense_id ]['user_app'] = array(
				'access_token'  => $token['access_token'],
				'refresh_token' => $token['refresh_token'],
				'expires'       => $token['expires'],
				'token_type'    => $token['token_type'],
			);
		} else {
			$options['accounts'][ $adsense_id ]['default_app'] = array(
				'access_token'  => $token['access_token'],
				'refresh_token' => $token['refresh_token'],
				'expires'       => $token['expires'],
				'token_type'    => $token['token_type'],
			);
		}
		$options['accounts'][ $adsense_id ]['details'] = $details;
		update_option( self::OPTNAME, $options );

		$gadsense_data                          = Advanced_Ads_AdSense_Data::get_instance();
		$gadsense_options                       = $gadsense_data->get_options();
		$gadsense_options['adsense-id']         = $adsense_id;
		$gadsense_options['page-level-enabled'] = isset( $args['autoads'] ) && $args['autoads'];
		update_option( GADSENSE_OPT_NAME, $gadsense_options );

	}

    /**
     *  Get a list of stored alerts for a given AdSense account.
     *  
     *  @param string $pub_id the publisher account.
     *  @return array $alerts
     */
    public static function get_stored_account_alerts( $pub_id = '' ) {
        if ( empty( $pub_id ) ) {
            return false;
        }
        $options = self::get_option();
        if ( isset( $options['accounts'][ $pub_id ] ) ) {
            if ( isset( $options['accounts'][ $pub_id ]['alerts'] ) && is_array( $options['accounts'][ $pub_id ]['alerts'] ) ) {
                $alerts = $options['accounts'][ $pub_id ]['alerts'];
                return self::filter_stored_account_alerts( $alerts );
            } else {
                return array();
            }
        }
        return false;
    }

    /**
     *  Remove unneeded stored alerts.
     *
     *  @param array $alerts Existing alerts.
     *  @return array $alerts Modified allerts.
     */
    public static function filter_stored_account_alerts( array $alerts ) {
        if ( empty( $alerts['items'] ) || ! is_array( $alerts['items'] ) ) {
            return $alerts;
        }

        // Remove `ads.txt` related alerts if the file is displayed to visitors.
        if ( Advanced_Ads_Ads_Txt_Admin::is_displayed() ) {
            $ads_txt_alerts = array( 'ALERT_TYPE_ADS_TXT_UNAUTHORIZED', 'ADS_TXT_MISSING', 'ADS_TXT_ISSUES' );

            foreach ( $alerts['items'] as $internal_id => $item ) {
                if ( isset( $item['id'] ) && in_array( $item['id'], $ads_txt_alerts ) ) {
                    unset( $alerts['items'][ $internal_id ] );
                }
            }
        }

        return $alerts;
    }
    
    /**
     * Checks if there is any AdSense warning for the currently connected AdSense account.
     * 
     * @param array $messages The array of messages.
     * 
     * @return array The modified array.
     */
    public static function adsense_warnings_check( $messages ) {
        $data = Advanced_Ads_AdSense_Data::get_instance();
        $adsense_id = $data->get_adsense_id();
        $alerts = self::get_stored_account_alerts( $adsense_id );
        if ( !is_array( $messages ) ) {
            $messages = array();
        }
        if ( !empty( $alerts ) && !empty( $alerts['items'] ) ) {
            $messages[] = sprintf(
                wp_kses(
                    __( 'There are one or more warnings about the currently linked AdSense account. You can view them <a href="%s">here</a>', 'advanced-ads' ),
                    array( 'a' => array( 'href' => true ) )
                ),
                esc_url( admin_url( 'admin.php?page=advanced-ads-settings#top#adsense' ) )
            );
        }
        return $messages;
    }
    
	/**
	 * Get the class's option
	 */
	public static function get_option() {
		$options = get_option( self::OPTNAME, array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		return $options + self::$default_options;
	}

    /**
     * Get the URL to the AdSense error page        
     * 
     * @param string $code Add the error code to the URL.
     * 
     * @return string The entire text with the url.
     */
    public static function get_adsense_error_link( $code = '' ) {
        if ( ! empty( $code ) ) {
            $code = '-' . $code;
        }
        $link = sprintf(
            // translators: %1$s is an anchor (link) opening tag, %2$s is the closing tag.
            esc_attr__( 'Learn more about AdSense account issues %1$shere%2$s.', 'advanced-ads' ),
            '<a href="' . ADVADS_URL . 'adsense-errors/#utm_source=advanced-ads&utm_medium=link&utm_campaign=adsense-error'. $code .'" target="_blank">',
            '</a>'
        );
        return $link;
    }
    
    /**
     * Get custom account connection error message list.
     */
    public static function get_connect_error_messages() {
        $health_class = Advanced_Ads_Ad_Health_Notices::get_instance();
        $messages = array();
        foreach( $health_class->default_notices as $key => $value ) {
            if ( 0 === strpos( $key, 'adsense_connect_' ) ) {
                $messages[ substr( $key, strlen( 'adsense_connect_' ) ) ] = $value['text'];
            }
        }
        return $messages;
    }
    
    /**
     * Get custom messages for AdSense alerts.
     */
    public static function get_adsense_alert_messages() {
        $health_class = Advanced_Ads_Ad_Health_Notices::get_instance();
        $messages = array();
        foreach( $health_class->default_notices as $key => $value ) {
            if ( 0 === strpos( $key, 'adsense_alert_' ) ) {
                $messages[ substr( $key, strlen( 'adsense_alert_' ) ) ] = $value['text'];
            }
        }
        return $messages;
    }
    
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

    /**
     * Get a hint for an error object that was received from AdSense
     * @param $reason string the reason from the response's error
     * @return string|void if there is a hint for this reason, a string containing the hint will be returned.
     */
    public static final function get_adsense_error_hint($reason){

        switch($reason){
            case "authError":
                /* translators: 1:A link to the settings page
                   translators: 2:The name of an ad network
                 */
                return sprintf(__( 'Please try to <a href="%1$s" target="_blank">reconnect to your %2$s account</a>.', 'advanced-ads' ), admin_url( 'admin.php?page=advanced-ads-settings#top#adsense' ), 'AdSense');
        }
    }
}
