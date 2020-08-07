<?php
class Advanced_Ads_Adsense_Report_Column{
	public $name;
	public function __construct($name){
		$this->name = $name;
	}
}
class Advanced_Ads_AdSense_Report_Dimension extends Advanced_Ads_Adsense_Report_Column{
	public function __construct($name){
		parent::__construct($name);
	}
}
class Advanced_Ads_AdSense_Report_Metric extends Advanced_Ads_Adsense_Report_Column{
	public function __construct($name){
		parent::__construct($name);
	}
}
class Advanced_Ads_Adsense_Dimensional_Data{
	
}
/**
 * Represents the response that will be sent to the javascript frontend
 * after a report has been requested
 * this holds all the plot & trace info
 */
class Advanced_Ads_Adsense_Report_Response{
	public $plots = array();
}

class Advanced_Ads_AdSense_Report_Builder{
	/**
	 * This determines the time in seconds a transient or option representing the response 
	 * of the adsense server will be valid.
	 */
	const TRANSIENT_VALIDITY = HOUR_IN_SECONDS;
	private $dimensions = array();
	private $metrics = array();
	private $dt_start;
	private $dt_end;
	private $overwrite_dt_identifier;
	private $store_as_transient = false;
	
	public function addDimension($name){
		$dim = new Advanced_Ads_AdSense_Report_Dimension($name);
		$this->dimensions[] = $dim;
	}
	public function addMetric($name){
		$dim = new Advanced_Ads_AdSense_Report_Metric($name);
		$this->metrics[] = $dim;
	}
	public function setDaterange($dt_start, $dt_end){
		$this->dt_start = $dt_start;
		$this->dt_end = $dt_end;
	}
	public function setOverwriteDaterangeIdentifier($id){
		$this->overwrite_dt_identifier = $id;
	}
	public function setStoreAsTransient($store_as_transient_bool){
		$this->store_as_transient = $store_as_transient_bool;
	}
	public function setDaterangeByDays($nbDays, $dt_end = null){
		if (! $dt_end) $dt_end = (new DateTime())->format("Y-m-d");
		$dt = new DateTime($dt_end);
		$dt->sub(new DateInterval("P" . $nbDays . "D"));
		$this->setDaterange($dt->format("Y-m-d"), $dt_end);
	}
	
	public function getUrl($pubId){
		$url  = 'https://www.googleapis.com/adsense/v1.4/accounts/' . $pubId . '/reports';
		$url .= "?startDate=$this->dt_start&endDate=$this->dt_end";
		foreach ($this->dimensions as $dim){
			$url .= "&dimension=$dim->name";
		}
		foreach ($this->metrics as $metric){
			$url .= "&metric=$metric->name";
		}
		$url.="&useTimezoneReporting=true";
		return $url;
	}
	/**
	 * Generate an identifier that will be used to store and retrieve transients.
	 */
	public function getIdentifier(){
		$id = "advanced_ads_adsense_report_";
		foreach ($this->dimensions as $dim){
			$id .= $dim->name . "_";
		}
		foreach ($this->metrics as $metric){
			$id .= $metric->name . "_";
		}
		if ($this->overwrite_dt_identifier){
			$id .= $this->overwrite_dt_identifier;
		}
		else{
			$id.= $this->dt_start . "_" . $this->dt_end;
		}
		return $id;
	}
	
	public function request_raw(){
		//gather the data for the request
		$gadsense_data = Advanced_Ads_AdSense_Data::get_instance();
		$pub_id = trim($gadsense_data->get_adsense_id());
		$access_token  = Advanced_Ads_AdSense_MAPI::get_access_token($pub_id);
		//build the url and request it
		$url = $this->getUrl($pub_id);
		return $this->process_request($url, $access_token);
	}
	
	public static function get_age_in_seconds($updated_at){
		//  when upgrading from a previous version of AA to V1.14.2+ a refresh of the adsense dashboard
		//  within one hour, might raise a notice when users are running their site in dev mode
		//  originally updated_at was a DateTime, it is a simple timestamp now, so we need to catch
		//  the DateTimes and replace them with numbers.
		if (!$updated_at || ! is_integer($updated_at) ){
			//trigger a reload, by setting it to a low, failsafe value
			$updated_at = 0;
		}
		return (new DateTime())->getTimestamp() - $updated_at;
	}
	
	public function build($plotter, $type, $forceRefresh = false, $allowRefresh = true){
		$transient_id = $this->getIdentifier();
		$response = $this->get_option($transient_id);
//		$wants_refresh = $forceRefresh || $response == null || ! isset($response['body']) || ! isset($response['body']->rows);
        $wants_refresh = $forceRefresh || $response == null || ! isset($response['body']);
		if ($response){
			$age_in_seconds = self::get_age_in_seconds($response['updatedAt']);
			if ($age_in_seconds >= self::TRANSIENT_VALIDITY) $wants_refresh = true;
			
		}
		if ($wants_refresh){
			if ($allowRefresh){

				$response = $this->request_raw();
				if (is_array($response) && isset($response['body']) && isset($response['headers'])){
				    //  remove all but body and header
                    foreach ($response as $key => $val){
                        if ($key != "body" && $key != "header"){
                            unset($response[$key]);
                        }
                    }
					$response['updatedAt'] = (new DateTime())->getTimestamp();
					//set_transient($transient_id, $response, self::TRANSIENT_VALIDITY);
					//update_option($transient_id, $response);
					$this->save_option($transient_id, $response);
				}
			}
		}
		else if (! $forceRefresh){
			$response = $this->get_option($transient_id); //  just a little fallback. it actually should never happen.
		}
		if ($response){
			return new Advanced_Ads_AdSense_Report($response, $plotter, $type);
		}
		return null;
	}
	
	public function request_report($plotter, $type){
		$response = $this->request_raw();
		try{
			$report = $this->build($plotter, $type, true);
			if ($response){
				$response = $report->generateResponse();
				return $response;
			}
		}
		catch(Exception $ex){}
		return null;
	}
	
	private function get_option($id){
		if ($this->store_as_transient) {
			return get_transient($id);
		}
		else{
			return get_option($id);
		}
	}
	private function save_option($id, $value){
		if ($this->store_as_transient) {
			set_transient($id, $value, self::TRANSIENT_VALIDITY);
		}
		else{
			update_option($id, $value);
		}
	}
	
	private function process_request($url, $access_token){
		if ( ! isset( $access_token['msg'] ) ) {
			$headers  = array(
				'Authorization' => 'Bearer ' . $access_token,
			);
			$adsense_data = wp_remote_get( $url, array( 'headers' => $headers ) );
			Advanced_Ads_AdSense_MAPI::log("Fetched AdSense Report from $url");
			return $adsense_data;
		} else {
			return -1;
		}
	}
	
	/**
	 * A quick way to create a dashboard summary.
	 */
	public static function createDashboardSummary($secondary_dimension_name = "DOMAIN_NAME", $filter_value = null, $overwrite_dt_identifier = null, $optional_dimension_names = null, $force_refresh = false, $allow_refresh = true){
		$builder = new Advanced_Ads_AdSense_Report_Builder();
		$builder->setDaterangeByDays(30);
		$builder->addDimension("DATE");
		$builder->addDimension($secondary_dimension_name);
		$builder->addMetric("EARNINGS");
		$builder->setOverwriteDaterangeIdentifier($overwrite_dt_identifier);
		$report = $builder->build("plotly", "lines", $force_refresh, $allow_refresh);
		$summary = Advanced_Ads_AdSense_Dashboard_Summary::create($report, $filter_value, $optional_dimension_names);
        $summary->dimension_name = $secondary_dimension_name;
		return $summary;
	}
}

class Advanced_Ads_AdSense_Report{
	public $valid;
	public $errors;
	public $dimensions;
	public $dimensionalData;
	public $metrics;
	public $columns;
	public $plotGenerator;
	public $secondaryDimension;
	
	function __construct($json_response, $plotter='jqplot', $type='lines'){
		$this->plotter = $plotter;
		$this->type = $type;
		$this->process_json_response($json_response);
// 		$this->plotGenerator = $plotter === 'jqplot'
// 			? new Advanced_Ads_AdSense_Plot_Generator_Jqplot($this)
// 			: new Advanced_Ads_AdSense_Plot_Generator_Plotly($this);
	}
	
	private function process_json_response($json_response){
		$valid = false;
		$errors = array();
		if ($json_response && is_array($json_response)){
			$body = isset($json_response['body']) ? json_decode($json_response['body']) : null;
			if ($body && isset($body->headers) && is_array($body->headers)){
				try{
					$headers = $body->headers;
					$dimensions = array();
					$metrics = array();
					foreach ($headers as $header){
						$name = $header->name;
						$type = $header->type;
						if ($type === "DIMENSION"){
							$object = new Advanced_Ads_AdSense_Report_Dimension($name);
							$dimensions[] = $object;
						}
						else if ($type === "METRIC_TALLY"){
							$object = new Advanced_Ads_AdSense_Report_Metric($name);
							$metrics[] = $object;
						}
						else if ($type === "METRIC_RATIO"){
							$object = new Advanced_Ads_AdSense_Report_Metric($name);
							$metrics[] = $object;
						}
						else if ($type === "METRIC_CURRENCY"){
							$object = new Advanced_Ads_AdSense_Report_Metric($name);
							$object->currency = $header->currency;
							$metrics[] = $object;
						}
						else{
							throw new RuntimeException("Unknown Header Type: $type");
						}
						$columns[] = $object;
					}
					
					$valid = count($dimensions) > 0 && count($dimensions) < 3 && count($metrics) > 0;
					if ($valid){
						$this->body = $body;
						$this->dimensions = $dimensions;
						$this->metrics = $metrics;
						$this->columns = $columns;
						$this->secondaryDimension = count($dimensions) > 1 ? $dimensions[1] : null;
						$this->updatedAt = isset($json_response['updatedAt']) ? $json_response['updatedAt'] : null;
					}
					else{
						throw new RuntimeException(__("Invalid response from AdSense."));
					}
				}
				catch (Exception $ex){
					$valid = false;
				}
			}
			else{
                if ($body->error && $body->error->errors && is_array($body->error->errors) && count($body->error->errors)){
                    foreach ($body->error->errors as $err){
                        $hint = Advanced_Ads_AdSense_MAPI::get_adsense_error_hint($err->reason);
                        if ($hint){
                            $errors[] = $err->message . " (" . $err->reason .").<br>" . $hint;
                        }
                        else{
                            $errors[] = $err->message . " (" . $err->reason .")";
                        }
                    }
                }
                //$errors[] = "Missing or incomplete response from AdSense 1231231231ssssssssssssssssssss.";
			}
		}
		$this->valid = $valid;
		if (! $valid && count($errors) == 0){
		    // Display a default error message.
		    $errors[] = __("Invalid response from AdSense.");
        }
		$this->errors = $errors;
	}

	public function filterRowsByPattern($column_index, $pattern){
		if (isset($this->body) && isset($this->body->rows) && is_array($this->body->rows)) {
            $filtered = array();
            foreach ($this->body->rows as $row) {
                if (preg_match($pattern, $row[$column_index])) {
                    $filtered[] = $row;
                }
            }
            $this->body->rows = $filtered;
        }
	}
	
	public function getRowsByDimensionValues($dimension_values, $dimension_index){
		$filtered = array();
		if ($this->body && isset($this->body->rows) && is_array($this->body->rows)) {
            foreach ($this->body->rows as $row) {
                if (in_array($row[$dimension_index], $dimension_values)) {
                    $filtered[] = $row;
                }
            }
        }
		return $filtered;
	}
	public function getDictByDimensionValues($dimension_values, $dimension_index, $value_column_index, $key_column_index){
		$dict = array();
		foreach ($this->body->rows as $row){
			if (in_array($row[$dimension_index], $dimension_values)){
				$dict[$row[$key_column_index]] = $row[$value_column_index];
			}
		}
		return $dict;
	}
	
	public function getDistinctValuesByDimension($dimension_index){
		$map = array();
		$vals = array();
		if (isset($this->body) && isset($this->body->rows)) {
            foreach ($this->body->rows as $row) {
                $key = $row[$dimension_index];
                if (!isset($map[$key])) {
                    $map[$key] = 1;
                    $vals[] = $key;
                }
            }
        }
		return $vals;
	}
	
	public function generateResponse(){
		$response = new Advanced_Ads_Adsense_Report_Response();
		$response->plots = $this->generatePlots();
		$response->plotter = $this->plotter;
		$response->errors = $this->errors;
		return $response;
	}
	
	private function createDimensionalData(){
		$data = array();
		if ($this->secondaryDimension != null){
			//  extract the dimensional data
			foreach ($this->body->rows as $row){
				$key = $row[1];
				if (! isset($data[$key])){
					$data[$key] = array();
				}
				$data[$key][] = $row;
			}
		}
		else{
			foreach ($this->body->rows as $row){
				$data['default'][] = $row;
			}
		}
		return $data;
	}
	
	
	private function generatePlots(){
		$this->dimensionalData = $this->createDimensionalData();
		$plots = array();
		$plots[] = $this->generatePlot();
// 		$plots = $this->plotGenerator->generatePlots($this);
// 		foreach ($this->dimensionalData as $dimdata){
			
// 		}
		//$plots[] = $this->generatePlot();
		return $plots;
	}
	
	private function generatePlot(){
		return $this->plotGenerator->generatePlot($this);
	}
}

class Advanced_Ads_AdSense_Dashboard_Summary{
	private function __construct(){}

    /**
     * @param $report Advanced_Ads_AdSense_Report
     * @param $filter_value string A value for the filter (e.g. domain name)
     * @param $optional_dimension_names array an array with optional dimension names.
     * @return Advanced_Ads_AdSense_Dashboard_Summary
     */
	public static final function create($report, $filter_value, $optional_dimension_names){
		//TODO: check for validity
		$summary = new Advanced_Ads_AdSense_Dashboard_Summary();
		if ($report && $report->valid){
			$colDimension = 1;
			$colEarnings = 2;
			$summary->nb_rows = isset($report->body) && isset($report->body->rows) ? count($report->body->rows) : 0;
			$summary->dimension = $optional_dimension_names;
            $summary->filter_value = $filter_value;
			$dimension_values = $report->getDistinctValuesByDimension(1);

			$dims = array();
			foreach ($dimension_values as $dim){
				$name = ($optional_dimension_names && isset($optional_dimension_names[$dim]))
					? $optional_dimension_names[$dim] : $dim;
				$dims[$dim] = $name;
			}
			$summary->dimensions = $dims;
			$summary->dimension_name = $report->secondaryDimension->name;
			if ($filter_value){
				//  prevent values that don't exist as a dimension.
				//  this allows for a proper fallback in the main dashboard that tries to find the domain of the blog
				//  if it does not match, it will not display a bunch of zeroes but the sum of earnings of all domains
				//  we also make sure this only happens for the DOMAIN_NAME dimension, and not for others
				$apply_filter = true;

                $filter_value_exists_in_dimension = false;
                foreach ($dims as $key => $name){
                    if ($key == $filter_value) {
                        $filter_value_exists_in_dimension = true;
                        break;
                    }
                }
                $summary->filterValueExists = $filter_value_exists_in_dimension;
                if ($summary->dimension_name == "DOMAIN_NAME"){
                    $apply_filter = $filter_value_exists_in_dimension;
                    if (! $filter_value_exists_in_dimension)
                        $filter_value = null;
                    $summary->filter_value = $filter_value;
                }

				if ($apply_filter){
					$report->filterRowsByPattern($colDimension, '/' . $filter_value . '$/');
				}
			}
			
			$summary->earningsToday = self::sum($report, $colEarnings, 1);
			$summary->earningsYesterday = self::sum($report, $colEarnings, 1, 1);
			$summary->earnings7Days = self::sum($report, $colEarnings, 7, 1);
			$summary->earnings28Days = self::sum($report, $colEarnings, 28, 1);
			$summary->earningsThisMonth = self::sumDim($report, $colEarnings, self::createDateDimensionValuesCurrentMonth());
			
			$age_in_seconds = Advanced_Ads_AdSense_Report_Builder::get_age_in_seconds($report->updatedAt);
			$summary->requires_refresh = $age_in_seconds > Advanced_Ads_AdSense_Report_Builder::TRANSIENT_VALIDITY;
			
			if (! $report->updatedAt) $summary->age = __("Never", "advanced-ads");
			else{
                $tz = self::get_timezone();
                $date = new DateTime('now', $tz);
				$date_format = get_option( 'date_format' );
				$today_str = $date->format($date_format);

                $date = new DateTime();
				$date->setTimestamp($report->updatedAt);
				$date->setTimezone($tz);

				if (! is_a($date, 'DateTime')) {
                    $date = new DateTime('now', $tz);
                }
				$is_today = $date->format($date_format) === $today_str;
                if ($is_today) $summary->age = $date->format( get_option( 'time_format' ));
                else $summary->age = $date->format($date_format);
			}
			$summary->valid = $report->valid;
		}
		else {
		    if ($report->errors){
                $summary->errors = $report->errors;
            }
			$summary->valid = false;
			$summary->requires_refresh = true;
			$summary->age = __("Never", "advanced-ads");
		}

		return $summary;
	}

    private static final function get_timezone(){
		$timezone_string = get_option( 'timezone_string' );
		if ( ! $timezone_string ) {
			$gmt_offset = get_option('gmt_offset');
			$sign = $gmt_offset < 0 ? "-" : "+";
			$gmt_offset = abs($gmt_offset);
			$gmt_offset_modulo = fmod($gmt_offset, 1.0);
			$hours = sprintf("%02d", $gmt_offset);
			$minutes = "00";
			if ($gmt_offset_modulo != 0){
				$minutes = (int)($gmt_offset_modulo * 60);
			}
			// PHP < 5.5.10 and HHVM do not recognize this format, 'UTC' will be used
			// https://stackoverflow.com/q/14068594
			$timezone_string = $sign . $hours . $minutes;
		}

        try {
            $tz = new DateTimeZone($timezone_string);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $tz = new DateTimeZone( 'UTC' );
        }
        return $tz;
    }

	private static final function sum($report, $col_index, $nb_days, $offset_days = 0){
		$dim = self::createDateDimensionValues($nb_days, $offset_days);
		return self::sumDim($report, $col_index, $dim);
	}
	private static final function sumDim($report, $colIndex, $dim){
// 		$dict = $report->getDictByDimensionValues($dim, 0, $colIndex, 0);
// 		return round(array_sum($dict),2) . " €";
		$rows = $report->getRowsByDimensionValues($dim, 0);
		$sum = 0.0;
		foreach ($rows as $row){
			$sum += $row[$colIndex];
		}
		$sum = number_format(round($sum, 2), 2);
		if (isset($report->columns[$colIndex]) && isset($report->columns[$colIndex]->currency))
			$sum .= " " . $report->columns[$colIndex]->currency;
		return $sum;
	}
	private static final function createDateDimensionValues($nbDays, $offsetDays = 0){
		$dim = array();
		$dt = new DateTime();
		for ($i=0; $i<$offsetDays; $i++){
			$dt = $dt->sub(new DateInterval("P1D"));
		}
		for ($i=0; $i<$nbDays; $i++){
			$dim[] = $dt->format("Y-m-d");
			$dt = $dt->sub(new DateInterval("P1D"));
		}
		return $dim;
	}
	private static final function createDateDimensionValuesCurrentMonth(){
		$dim = array();
		$dt = new DateTime();
		$month = $dt->format("m");
		$m = $month;
		while ($m == $month){
			$dim[] = $dt->format("Y-m-d");
			$dt = $dt->sub(new DateInterval("P1D"));
			$m = $dt->format("m");
		}
		return $dim;
	}
}

?>
