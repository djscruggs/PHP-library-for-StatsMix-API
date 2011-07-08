<?php

/**
 * This File implements the StatsMix REST API in PHP.
 * Full API documentation is at http://www.statsmix.com/developers/documentation
 * @author Derek Scruggs <me@derekscruggs.com>
 * @version 1.0
 * @package statsmix
 */
//Requires PHP v 5.2 or higher
class StatsMix{
	
	static $api_key;
	
	static $version = '1.0';
	/**
	 * Points to instance of SmTrack - StatsMix::track() proxies calls through this object
	 * @var SmTrack
	 */
	static $track; // 
	
	/**
	 * Format that an api call should be returned in - json or xml
	 * @var string;
	 */
	static $format = 'xml'; 

	/**
	 * Xml or json result of a web api call
	 * @var string;
	 */
	static $response;
	
	/**
	 * Error, if any, from a web api call
	 * @var string;
	 */
	static $error = false;
	
	/**
	 * If set to true, calls to web API are ignored (useful in development mode)
	 * @var boolean;
	 */
	static $ignore = false;
	
	/**
	 * If set, redirects all StatsMix::track() calls to the same metric specified by this name (useful in development mode)
	 * @var string;
	 */
	static $test_metric_name = null; 

	/**
	 * Set the api key - if set here, SmResource classes will check for it's existence automatically
	 * @param string $key
	 */
	static function set_api_key($key){
		self::$api_key = $key;
	}
	/**
	 * Get the api key
	 * @return string
	 */
	static function get_api_key(){
		return self::$api_key;
	}
	/**
	 * Get the version of this library
	 * @return string
	 */
	static function get_version(){
		return self::$version;
	}
	/**
	 * Get the version of this library
	 * @return string
	 */
	static function get_error(){
		return self::$error;
	}
	
	/**
	 * Set format of api results - either 'xml' or 'json'
	 * @param string $format
	 */
	static function set_format($format){
		if(!self::$track)
			self::$track = new SmTrack;
		self::$track->format = $format;
	}
	
	/**
	 * Set StatsMix::ignore
	 * @param boolean $boolean
	 */
	static function set_ignore($boolean){
		self::$ignore = (bool) $boolean;
	}
	
	/**
	 * Set $test_metric_name
	 * @param string $name
	 */
	static function set_test_metric_name($name){
		self::$test_metric_name = $name;
	}
	
	/**
	 * Get response from API call
	 */
	static function get_response(){
		return self::$response;
	}
	
	/**
	 * @param string $name name of the metric this stat should be attached to 
	 * @param double $value value of the stat you want to create - up to 13 digits with two digits right of the decimal point. Defaults to 1 if not set.
	 * @param array $options Hash of additional values you want to pass in - currently ref_id, generated_at, meta but may include others in the future
	 */
	
	function track($name, $value = null, $options = array()){
		self::$error = false;
		if(self::$ignore)
			return true;
		if(!self::$track)
			self::$track = new SmTrack;
		if(self::$test_metric_name)
			$name = self::$test_metric_name;
		self::$response = self::$track->save($name, $value, $options);
		if(self::$track->error)
			self::$error = self::$track->error;
		return self::$response;
	}	
}

/**
 * SmBase is an abstraction layer for managing web API communications. 
 * Most of its variables and methods are protected. 
 * Child classes make use of them, but SmBase cannot be instantiated directly.
 * @package statsmix
 */
abstract class SmBase{
	/**
 	 * StatsMix api key. Found in the My Account => API Key section of StatsMix
	 * @var string
	 */
  	protected $_api_key; //get this from the API section of your account settings

	/**
 	 * Error message, if any, returned by an operation
	 * @var string
	 */
  	protected $_error; 

	/**
 	 * Formatted data returned from api calls
	 * @var string
	 */
  	protected $_data;

	/**
 	 * Raw data returned from api calls
	 * @var string
	 */
  	protected $_response;

	/**
	 * Format of the request - json or xml
	 * @var string
	 */
 	protected $_format = 'xml';

	

	public function __construct(){
		$this->_setup_api_key();
	}
	/**
	 * All child classes are expected tohave a uri that is the base endpoint for performing operations
	 * @return string
	 */
	abstract public function get_base_uri();
	
	/**
	 * Defaults to the same result as get_base_uri()  - overried to specify a different endpoint for post operations
	 * @return string
	 */
	public function get_post_uri(){
		return $this->get_base_uri();
	}
	
	/**
	 * Get the raw response from an operation - xml (default) or json (specified by instance variable $format)
	 * @return string
	 */
	public function get_response(){
		return $this->_response;
	}
	
	/**
	 * Get the formatted data that results from an operation - typically an StdObject or array of StdObjects or SimpleXML objects (depends on $format)
	 * @return mixed
	 */
	public function get_data(){
		return $this->_data;
	}
	
	/**
	 * Performs a post operation to the uri returned by $this->get_post_uri() (which by default is the same as $this->get_base_uri())
	 * @param array $data hash of key value pairs to post
	 * @return string - either xml (default) or json (can be explicitly set via $object->format)
	 */
	protected function _post($data){
		$this->_error = false;
		$this->_setup_api_key();
		if(!$this->_api_key)
			throw new StatsMixException('You must set api_key in ' . get_class($this) . ' before calling ' . __METHOD__);
		//setup curl
		if(!isset($data['format']))
			$data['format'] = $this->_format;
		$ch = $this->_setup_curl();
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch, CURLOPT_URL, $this->get_post_uri());
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
		
		$result = curl_exec($ch);
		$this->_setup_data($result);
		return $result;
	}
	/**
	 * Sets up the API key, which can be set explicitly in the object or via StatsMix::set_api_key
	 * @return string
	 */
	protected function _setup_api_key(){
		if($this->_api_key)
			return $this->_api_key;
		if(StatsMix::get_api_key())
			$this->_api_key = StatsMix::get_api_key();
		return $this->_api_key;
	}
	/**
	 * Sets up the curl for performing web requests
	 * @return curl resource
	 */
	protected function _setup_curl()
	{
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		//uncomment next two lines and create a file called errors.txt in this directory to see curl errors
		//$fp = fopen(dirname(__FILE__).'/errors.txt', "w");
		//curl_setopt($ch,CURLOPT_STDERR, $fp);
		curl_setopt($ch,CURLOPT_VERBOSE,true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-StatsMix-Token:' . $this->_setup_api_key()));
		curl_setopt($ch, CURLOPT_USERAGENT,"StatsMix PHP Library v" . StatsMix::get_version());
		return $ch;
	}
	/**
	 * Converts raw xml & json responses to PHP-friendly structures
	 * @see SmBase::get_data()
	 * @return void 
	 */
	protected function _setup_data($data = null)
	{
		$this->_response = $data;
		if($this->_format == 'xml')
		{
			//simplexml isn't really an object (per comment at http://www.php.net/manual/en/class.simplexmlelement.php#100811)
			//so we convert to an array, then an object again
			$this->_data = (object) (array) simplexml_load_string($this->_response);
			$this->_error = @$this->_data->error;
		} else {
			$this->_data = (object) json_decode($this->_response);
			$this->_error = @$this->_data->errors->error;
		}
	}
	public function __set($name,$value)
	{
		$name = strtolower($name);
		if(in_array($name,array('metric_id','profile_id','id')))
		{
			if($value && intval($value) == 0)
				throw new StatsMixException("Invalid value for $name. Value must be an integer.");
			$value = (int) $value ? (int) $value : null;
		}elseif($name == 'api_key')
		{
			if(strlen($value)==0)
				throw new StatsMixException("Invalid value for $name. Value must be non-empty string.");
		}elseif($name == 'format'){
			if(!in_array($value,array('json','xml')))
				throw new StatsMixException("Invalid value for $name. Value must be either \"xml\" or \"json\".");
		}elseif($name == 'rawdata' || $name == 'data'){
			throw new StatsMixException("$name is a protected element and cannot be set explicitly.");
		}
		$var = "_$name";
		$this->$var = $value;
	}
	
	public function __get($name)
	{
		if($name == 'response')
			return $this->get_response();
		if($name == 'data')
			return $this->get_data();
		$name = "_$name";
		return $this->$name;
	}
}

/** 
 * SmResource provides CRUD operations to StatsMix resources.
 * Child classes inherit from it and point to specific resource types.
 * @package statsmix
 */
abstract class SmResource extends SmBase
{
  
  	/**
	 * Timestamp of when stat was stored in StatsMix (differs from generated_at, which can be set programmatically in order to backdate data)
	 * @var string
	 */
 	protected $_created_at;
 	
 	/**
	 * Timestamp of when stat was updated in StatsMix
	 * @var string
	 */
 	protected $_updated_at;
 	
 	/**
	 * Id of the resource you want to fetch OR automatically populated after calling create()
	 * @var int
	 */
 	protected $_id;
	
	public function __construct($id = null){
		if($id != null)
			$this->id = $id;
		parent::__construct();
	}
	
 	/** 
	 * Create a new resource
	 * @return string - xml (default) or json (specified by instance variable $format)
	 */
	public function create($data)
	{
		$result = parent::_post($data);
		if(!$this->_error)
		{
			$this->_id = @$this->_data->id ? $this->_data->id : false;
		}	
		return $result;
	}
	
	/** 
	 * Fetch a resource
	 * @return string - xml (default) or json (specified by instance variable $format)
	 */
	public function fetch()
	{
		$this->_error = false;
		$this->_setup_api_key();
		if(!$this->_api_key)
			throw new StatsMixException('You must set api_key in ' . get_class($this) . ' before calling ' . __METHOD__);
		$ch = $this->_setup_curl();
		curl_setopt($ch, CURLOPT_URL, $this->get_resource_uri());
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		$this->_setup_data($result);
		if(!$this->_error)
		{
			$data = (array) $this->_data;
			$fields = $this->get_readable_fields();
			foreach($fields as $key)
			{
				$var = $key;
				//xml uses different array key names (i.e. metric-id instead of metric_id) so we have to adjust based on format
				$this->$var = $data[$key];
			}
		}	
		return $result;
	}
	
	/** 
	 * Update an existing resource
	 * @return boolean
	 */
	public function update($data)
	{
		//die(print_r($data));
		$this->_error = false;
		$this->_setup_api_key();
		if(!$this->_api_key)
			throw new StatsMixException('You must set ' . get_class($this) . 	'::api_key before calling ' . __METHOD__);		
		$ch = $this->_setup_curl();
		curl_setopt($ch, CURLOPT_URL, $this->get_resource_uri());
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
		$result = curl_exec($ch);
		$this->_setup_data($result);
		// if($this->_error)
		// 			return false;
		return $result;
	}
	
	/**
	 * Returns a list of records
	 * @param integer $numRecords the number of records to return. The StatsMix API returns 50 by default.
	 * @param array options hash of additional variables and values to append to the query string
	 * @return string - xml (default) or json (specified by instance variable $format)
	 */
	public function get_list($numRecords = null, $options = array())
	{
		
		$this->_error = false;
		$this->_setup_api_key();
		if(!$this->_api_key)
			throw new StatsMixException('You must set instance variable ' . get_class($this) . 	'::api_key before calling ' . __METHOD__);
		$ch = $this->_setup_curl();
		$url = $this->get_list_uri();
		$separator = strrpos($url,'?') > 0 ? '&' : '?';
		if(intval($numRecords)>0){
			$url.= $separator . 'limit=' . intval($numRecords);
			$separator = '&';
		}
		if(is_array($options) && count($options)>0)
		{
			$i = 0;
			foreach($options as $key => $value)
			{
				if($i > 0)
					$separator = '&';
				$i++;
				$url.= $separator . urlencode($key) . '=' . urlencode($value);
			}
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		$result = curl_exec($ch);
		$this->_setup_data($result);
		// if($this->_error)
		// 			return false;
		return $result; 
	}
	
	/**
	 * Delete a resource
	 * @return boolean
	 */
	public function delete()
	{
		$this->_error = false;
		$this->_setup_api_key();
		$required = array('api_key','id');
		foreach($required as $var)
		{
			if(!$this->$var)
			{
				throw new StatsMixException('You must set instance variable ' . get_class($this) . 	'::' . $var . ' before calling ' . __METHOD__);
			}
		}
		$ch = $this->_setup_curl();
		curl_setopt($ch, CURLOPT_URL, $this->get_base_uri() . "/{$this->_id}.{$this->_format}");
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		$result = curl_exec($ch);
		$this->_setup_data($result);
		// if($this->_error)
		// 			return false;
		//$this->id = null;
		return $result;
	}
	
	/**
	 * Save a stat. If it's new, creates a new stat. Otherwise updates an existing stat
	 * @return boolean
	 */
	public function save()
	{
		if($this->_id)
			return $this->update();
		return $this->create();
	}
	
	
	/**
	 * Get array of fields that may be read for a resource - used to setup data
	 * @return array
	 */
	abstract public function get_readable_fields();
	
	/**
	 * Get uri used to fetch a list of resources
	 * @return string
	 */
	abstract public function get_list_uri();
	
	/**
	 * Get uri used to fetch an individual resource
	 * @return string
	 */
	abstract public function get_resource_uri();
	
	
}

/**
 * Implementation of StatsMix custom metric
 * @package statsmix
 */
class SmMetric extends SmResource {
	const base_uri = 'https://statsmix.com/api/v2/metrics';
	
	/**
	 * Fields that are readable from the API
	 * @var array
	 */
	protected $_readable_fields = array('name','profile_id','sharing','created_at','updated_at','include_in_email','url');
	
	/**
	 * Name of the metric
	 * @var string
	 */
 	protected $_name;

	/**
	 * Sharing - on or off
	 * @var boolean
	 */
 	protected $_sharing;

	/**
	 * Include in daily email? - on or off
	 * @var boolean
	 */
 	protected $_include_in_email;

	/**
	 * StatsMix profile id - required when creating a metric
	 * @var int
	 */
	protected $_profile_id;
	
	/**
	 * url that metric can be viewed at (read only)
	 * @var string
	 */
	protected $_url;
	
	/**
	 * Gets the uri endpoint for metrics
	 * @return string
	 */
	public function get_base_uri(){
		return self::base_uri;
	}
	
	public function get_list_uri(){
		return self::base_uri . ".{$this->_format}";
	}
	
	public function get_resource_uri(){
		return self::base_uri . "/{$this->_id}.{$this->_format}";
	}
	public function get_readable_fields(){
		return $this->_readable_fields;
	}
	public function create(){
		if (!$this->_name)
			throw new StatsMixException("name of metric to create must be set before calling " . __METHOD__);
		if ($this->_profile_id)
			$data['profile_id'] = $this->_profile_id;
		$data['name'] = $this->_name;
		$data['sharing'] = $this->_sharing ? 'public' : 'none';
		if(isset($this->_include_in_email)){
			$data['include_in_email'] = $this->_include_in_email ? 1 : 0;
		} 
		return parent::create($data);
	}
	public function update(){
		if($this->_name)
			$data['name'] = $this->_name;
		if($this->_profile_id)
			$data['profile_id'] = $this->_profile_id;
		if(isset($this->_sharing))
			$data['sharing'] = $this->_sharing;
		if(isset($this->_include_in_email))
			$data['include_in_email'] = $this->_include_in_email ? 1 : 0;
		return parent::update($data);
	}
	public function __set($name,$value)
	{
		$name = strtolower($name);
		if($name == 'sharing'){
			$value = (bool) $value ? 'public' : 'none';
		}
		if($name == 'include_in_email')
			$value = $value ? 1 : 0;
		parent::__set($name,$value);
	}
}

/**
 * Implementation of StatsMix stat
 * @package statsmix
 */
class SmStat extends SmResource {
	const base_uri = 'https://statsmix.com/api/v2/stats';
	
	/**
	 * Fields that are readable from the API
	 * @var array
	 */
	protected $_readable_fields = array('metric_id','generated_at','created_at','updated_at','value','ref_id','meta');
	
	/**
	 * Value of the stat - up to 13 digits with two digits right of the decimal point
	 * @var double
	 */
 	protected $_value;

	/**
	 * Alternate id of stat you want to fetch or create
	 * @var string
	 */
 	protected $_ref_id;

	/**
	 * Array of key-value pairs of metadata you want to store with the stat
	 * @var string
	 */
 	protected $_meta;
	
	/**
	 * StatsMix metric id. Required when creating a stat. Found by navigating to the custom metric you created
	 * @var int
	 */
	protected $_metric_id;	
	
	/**
	 * StatsMix profile id. Optionals when creating a stat. Found by navigating to the custom metric you created
	 * @var int
	 */
	protected $_profile_id;
	
	
	/**
	 * Timestamp you want to use when creating the stat. //defaults to current date and time (UTC) if not set
	 * @var string
	 */
 	protected $_generated_at;
	
	public function get_base_uri(){
		return self::base_uri;
	}
	
	public function get_list_uri(){
		if (!$this->_metric_id)
			throw new StatsMixException("metric_id of stats to fetch must be set before calling " . __METHOD__);
		return self::base_uri . ".{$this->_format}?metric_id={$this->_metric_id}";
	}
	
	public function get_resource_uri(){
		if($this->_id){
			$id = $this->_id;
		} elseif($this->_ref_id){
			$id = rawurlencode($this->_ref_id);
		} else {
			throw new StatsMixException("id or ref_id of stat to fetch must be set before calling " . __METHOD__);
		}
		if(!$this->_id && !$this->_metric_id){
			throw new StatsMixException("when using ref_id you must also set metric_id before calling " . __METHOD__);
		}
		$url = self::base_uri . "/{$id}.{$this->_format}";
		if(!$this->_id){
			$url.= '?metric_id=' . $this->_metric_id;
		}
		return $url;
	}
	/* array of field names to look for when fetching */
	public function get_readable_fields(){
		return $this->_readable_fields;
	}
	
	public function update(){
		//setup data to PUT
		$data = array();
		if(!$this->_id && !$this->_ref_id){
			throw new StatsMixException("You must set id or ref_id before calling " . __METHOD__);
		} elseif(!$this->_id && !$this->_metric_id){
			throw new StatsMixException("When using ref_id you must also set metric_id before calling " . __METHOD__);
	    }
		if(isset($this->_value))
			$data['value'] = $this->_value;
		if(isset($this->_generated_at))
			$data['generated_at'] = $this->_generated_at;
		if(isset($this->_ref_id))
			$data['ref_id'] = $this->_ref_id;
		if(is_array($this->_meta))
			$data['meta'] = json_encode($this->_meta);
		return parent::update($data);
	}
	
	public function create(){
		//setup data to POST
		
		if (!$this->_metric_id)
			throw new StatsMixException("metric_id of stat to create must be set before calling " . __METHOD__);
		$data['metric_id'] = $this->_metric_id;
		$data['value'] = $this->_value;
		$data['generated_at'] = $this->_generated_at ? $this->_generated_at : gmdate('Y-m-dTH:i:s');
		
		
		if(is_array($this->_meta))
			$data['meta'] = json_encode($this->_meta);
		if($this->_ref_id)
			$data['ref_id'] = $this->_ref_id;
		return parent::create($data);
	}
	
	/**
	 * @param integer $numRecords number of records to return. The StatsMix API returns 50 by default.
	 * @param array $options hash of additional search parameters, currently only start_date and end_date are supported
	 */
	public function get_list($numRecords = null,$options = array()){
		if(!$this->_metric_id)
			throw new StatsMixException("metric_id of stats to fetch must be set before calling " . __METHOD__);
		return parent::get_list($numRecords,$options);
	}
	public function __set($name,$value)
	{
		$name = strtolower($name);
		if($name == 'generated_at' && $value != null){
			$time = strtotime($value);
			if(!$time)
				throw new StatsMixException("Invalid value for $name. Value must be a valid date. (submitted: $value)");
		}
		return parent::__set($name,$value);
	}
}

/**
 * Wrapper class for the track endpoint
 */
class SmTrack extends SmBase{
	const base_uri = 'https://statsmix.com/api/v2/track';
	
	/**
	 * @param string $name name of the metric this stat should be attached to 
	 * @param double $value value of the stat you want to create - up to 13 digits with two digits right of the decimal point. Defaults to 1 if not set.
	 * @param array $options Hash of additional values you want to pass in - currently ref_id, generated_at, meta but may include others in the future
	 */
	
	function save($name, $value = null, $options = array())
	{
		$data['name'] = $name;
		if($value)
			$data['value'] = $value;
		$data = array_merge($options,$data);
		if(is_array(@$data['meta']))
			$data['meta'] = json_encode($data['meta']);
		return $this->_post($data);
	}
	
	public function get_base_uri(){
		return self::base_uri;
	}
	
	public function get_post_uri(){
		return $this->get_base_uri();
	}
}

/**
 * Custom exception class so it's easier to trap for StatsMix errors vs. other exceptions
 * @package statsmix
 */
class StatsMixException extends Exception
{
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}