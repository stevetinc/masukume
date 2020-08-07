<?php

class Advanced_Ads_AdSense_Data {

    private static $instance;
    private $options;
    private $resizing;

    private function __construct() {

	$options = get_option(GADSENSE_OPT_NAME, array());

	// set defaults
	if (!isset($options['adsense-id'])) {
	    
		$options['adsense-id'] = '';
		// starting version 1.7.9, the default limit setting was changed from true to false due to AdSense policy change
		$options['limit-per-page'] = false;

		update_option( 'GADSENSE_OPT_NAME', $options);
	}

	if (!isset($options['limit-per-page'])) {
	    // starting version 1.7.9, the default limit setting was changed from true to false due to AdSense policy change
	    $options['limit-per-page'] = false;
	}

	if (!isset($options['page-level-enabled'])) {
	    $options['page-level-enabled'] = false;
	    
	}
	if ( ! isset( $options['background'] ) ) {
		$options['background'] = false;
	}

	$this->options = $options;

	// Resizing method for responsive ads
	$this->resizing = array(
	    'auto' => __('Auto', 'advanced-ads'),
	);
    }

    /**
     * GETTERS
     */
    public function get_options() {
	return $this->options;
    }

    public function get_adsense_id( $ad = null ) {
		if ( ! empty( $ad ) ) {
			if ( isset( $ad->is_ad ) && true === $ad->is_ad && 'adsense' === $ad->type ) {
				$ad_content = json_decode( $ad->content );
				if ( $ad_content && isset( $ad_content->pubId ) && !empty( $ad_content->pubId ) ) {
					return $ad_content->pubId;
				}
			}
		}
		return trim($this->options['adsense-id']);
    }

    public function get_limit_per_page() {
	return $this->options['limit-per-page'];
    }

    public function get_responsive_sizing() {
	$resizing = $this->resizing;
	$this->resizing = apply_filters('advanced-ads-gadsense-responsive-sizing', $resizing);
	return $this->resizing;
    }

    public static function get_instance() {
	if (null == self::$instance) {
	    self::$instance = new self;
	}
	return self::$instance;
    }

    /**
     * ISSERS/HASSERS
     */
    public function is_page_level_enabled() {
	return $this->options['page-level-enabled'];
    }
    public function is_setup(){
    	if (isset($this->options) && is_array($this->options) && isset($this->options['adsense-id']) && $this->options['adsense-id']){
            $adsense_id = $this->get_adsense_id();
            if ($adsense_id) {
                return Advanced_Ads_AdSense_MAPI::has_token($adsense_id);
            }
        };
        return false;
    }
    // load stats from AdSense account.
	public function is_hide_stats(){
		if ( isset( $this->options ) && is_array( $this->options ) && isset( $this->options['hide-stats'] ) ){
			return true;
		};
		return false;
	}

}
