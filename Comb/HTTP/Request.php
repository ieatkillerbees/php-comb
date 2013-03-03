<?php
/**
 * Request.php
 * 
 * Request - A wrapper for PHP's cURL implementation
 * 
 * Request implements a generic HTTP request.
 * 
 * @author Samantha Quinones <samantha@tembies.com>
 * @package Comb
 * @version 0.1
 */

namespace Comb\HTTP;

interface RequestInterface
{
	/**
	 * Execute the request and return a formatted response object.
	 * @return Response
	 */
	public function execute();
}

/**
 * Request - A generic HTTP request
 *
 * @property bool  	 $allow_redirect 	Follow redirects from target service?
 * @property integer $max_redirects  	Maximum number of redirects to allow before aborting.
 * @property string  $user_agent		User agent string to sent to target service.
 * @property bool	 $set_referrer		Expose referrer info to target service?
 * @property integer $connect_timeout	Connection timeout in whole seconds.
 * @property integer $resp_timeout		Response timeout in whole seconds.
 * @property array	 $allowed_verbs		Array of allowed HTTP verbs.
 * @property string  $request_method	Request method, a valid HTTP verb.
 * @property string  $service_url		URL of the target web service
 * @method setUrl
 * @method setMethod
 */
class Request implements RequestInterface
{
	/**
	 * cURL resource handle
	 * @var curl_resource 
	 */
	private $_curl_handler  = null;
	private $_options = [
		"allow_redirect" 	=> true,		// Follow reditect paths
		"max_redirects"	 	=> 10,			// Avoid infinite redirection
		"user_agent"	 	=> "php-comb",	// User agent
		"set_referrer"	 	=> true, 		// Expose referrer info
		"connect_timeout"	=> 120,			// seconds
		"resp_timeout"	 	=> 120,			// seconds
		"request_method"	=> "GET",		// request method
		"allowed_verbs"		=> ["GET", "HEAD", "POST", "PUT", "DELETE", "TRACE", "OPTIONS", "CONNECT", "PATCH"],
		"service_url"		=> null, 
	];

	/**
	 * Mapping of options to CURLOPTS constants
	 * @internal
	 */
	private $_curlopts = [
		"allow_redirect" 	=> CURLOPT_FOLLOWLOCATION,
		"max_redirects"	 	=> CURLOPT_MAXREDIRS,
		"user_agent"	 	=> CURLOPT_USERAGENT,
		"set_referrer"	 	=> CURLOPT_AUTOREFERER,
		"connect_timeout" 	=> CURLOPT_CONNECTTIMEOUT,
		"resp_timeout"		=> CURLOPT_TIMEOUT,
	];

	/**
	 * Magic method to retrieve psudeoproperties.
	 * @param string $property
	 * @throws \RuntimeException
	 * @return multitype:boolean number string NULL multitype:string
	 */
	public function __get($property)
	{
		if (array_key_exists($property, $this->_options)) {
			return $this->_options[$property];
		} else {
			throw new \RuntimeException("{$property} is not a property of this object");
		}
	}
	
	/**
	 * Transform a property name to a method name.
	 * @param string $property
	 * @return string
	 */
	private function _propertyToMethod($property)
	{
		$property = ucwords(str_replace("_", " ", $property));
		return str_replace(" ", "", $property);
	}
	
	/**
	 * Magic methos to set pseudoproperties
	 * @param string $property
	 * @param string $value
	 * @throws \RuntimeException
	 * @return void
	 */
	public function __set($property, $value)
	{
		
		if (method_exists($this, "_set" . $this->_propertyToMethod($property))) {
			return call_user_func(array($this, "_set" . $this->_propertyToMethod($property)), $value);
		}

		if (array_key_exists($property, $this->_options)) {
			$this->_options[$property] = $value;
		} else {
			throw new \RuntimeException("{$property} is not a property of this object");
		}
	}
	
	/**
	 * Magic caller method
	 * @param string $method
	 * @param array  $args
	 * @throws \RuntimeException
	 * @return multitype:boolean number string NULL multitype:string
	 */
	public function __call($method, array $args)
	{
		if (method_exists($this, "_" . $method)) {
			return call_user_func_array([$this, "_" . $method], $args);
		} else {
			throw new \RuntimeException("{$method} is not a method of this object");
		}
	}
	
	/**
	 * @return null
	 * @param $url string Target URL
	 * @param $options array An array of options
	 */
	public function __construct(array $options = [])
	{
		$this->_options = array_merge($this->_options, $options);
	}

	/*
	 * Getters & Setters										  *
	 */
	
	/**
	 * Set the HTTP method if allowed
	 * @param $method An HTTP verb
 	 * @throws Exception
 	 * @return null
	 */
	private function _setRequestMethod($method) 
	{
		if (!(in_array($method, $this->allowed_verbs))) {
			throw new \InvalidArgumentException("${method} is not a recognized HTTP verb/method");
		}
		$this->request_method = $method;
	}
	
	/**
	 * Sets the service URL if valid
	 * @param string $url
	 * @throws \InvalidArgumentException
	 * @return void
	 */
	private function _setServiceUrl($url)
	{
		if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
			throw new \InvalidArgumentException("${url} is not a syntactically valid URL");
		}
		$this->service_url = $url;
	}
	
	/**
	 * For any entry in the options that corresponds to a CURL opt, 
	 * extract the appropriate CURLOPT constant and execute 
	 * curl_setopt
	 * @return void
	 * @codeCoverageIgnore
	 */
	private function _setCurlOptions()
	{
		foreach ($this->_options as $option => $value) {
			if (array_key_exists($option, $this->_curlopts)) 
			{
				curl_setopt($this->_curl_handler, $this->_curlopts[$option], $value);
			}
		}
	}
	
	/**
	 * Returns the response as a string.
	 * @return \Comb\Request\Response
	 */
	protected function _executeCurl()
	{
		// Create a cURL handler
		$this->_curl_handler = curl_init();
		
		// Instruct cURL to return the response as a string.
		curl_setopt($this->_curl_handler, CURLOPT_RETURNTRANSFER, true);
		
		// Set the target URL
		curl_setopt($this->_curl_handler, CURLOPT_URL, $this->service_url);
		
		// Set verbosity on and capture headers
		curl_setopt($this->_curl_handler, CURLOPT_VERBOSE, false);
		curl_setopt($this->_curl_handler, CURLOPT_HEADER, true);
		
		// Capture the response
		$response = new Response($this->_curl_handler, $this->_options);
		
		// Close the handler.
		curl_close($this->_curl_handler);
		
		// Return the response
		return $response;
	}
	
	/**
	 * Execute the request and return the response as a string.
	 */
	public function execute()
	{
		return $this->_executeCurl();
	}
}