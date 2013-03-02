<?php
/**
 * request.php
 * 
 * Request - A wrapper for PHP's cURL implementation
 * 
 * Request implements a generic HTTP request.
 * 
 * @author Samantha Quinones <samantha@tembies.com>
 * @package Comb
 * @version 0.1
 */

namespace Comb\Request;
use Response;

// @TODO provide autoloader interface
require("response.php");

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
 * 
 * @method 
 */
class Request implements RequestInterface
{
	/**
	 * cURL resource handle
	 * @var curl_resource 
	 */
	private $_curl_handler  = null;
	private $_method	    = null;
	private $_url		    = null;
	private $_options = [
		"allow_redirect" 	=> true,		// Follow reditect paths
		"max_redirects"	 	=> 10,			// Avoid infinite redirection
		"user_agent"	 	=> "php-comb",	// User agent
		"set_referrer"	 	=> true, 		// Expose referrer info
		"connect_timeout"	=> 120,			// seconds
		"resp_timeout"	 	=> 120,			// seconds
		"request_method"	=> "GET",		// request method
		"allowed_verbs"		=> ["GET", "HEAD", "POST", "PUT", "DELETE", "TRACE", "OPTIONS", "CONNECT", "PATCH"] 
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

	public function __get($property)
	{
		if (array_key_exists($property, $this->_options)) {
			return $this->_options[$property];
		} elseif (property_exists($this, "_" . $property)) {
			return $this->_{$property};
		} else {
			throw new \RuntimeException("{$property} is not a property of this object.");
		}
	}
	
	public function __set($property, $value)
	{
		switch ($property) {
			case "request_method":
				$this->setMethod($value);
		}
	}
	
	public function __call($method, array $args)
	{
		if (method_exists($this, "_" . $method)) {
			return call_user_func_array([$this, "_" . $method], $args);
		} else {
			throw new \RuntimeException("{$method} is not a method of this object.");
		}
	}
	
	/**
	 * @return null
	 * @param $url string Target URL
	 * @param $options array An array of options
	 */
	public function __construct($url, array $options = [])
	{
		$this->setUrl($url);
		$this->_options = array_merge($this->_options, $options);
	}

	/*
	 * Getters & Setters										  *
	 */
	
	/**
	 * Set the HTTP method
	 * @param $method An HTTP verb
 	 * @throws Exception
 	 * @return null
	 */
	private function _setMethod($method) 
	{
		if (!(in_array($method, $this->allowed_verbs))) {
			throw new \InvalidArgumentException("${method} is not a recognized HTTP verb/method");
		}
		$this->_method = $method;
	}
	
	/**
	 * Returns the set HTTP method/verb
	 * @return string
	 */
	public function getMethod($method)
	{
		return $this->_method;
	}
	
	/**
	 * Sets the target URL
	 * @param $url A well-formed URL.
	 * @return null 
	 */
	public function setUrl($url)
	{
		if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
			throw new \Exception("${url} is not a syntactically valid URL");
		}
		$this->_url = $url;
	}
	
	/**
	 * Returns the target URL
	 * @return string
	 */
	public function getUrl()
	{
		return $this->_url;
	}
	
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
		curl_setopt($this->_curl_handler, CURLOPT_URL, $this->_url);
		
		// Set verbosity on and capture headers
		curl_setopt($this->_curl_handler, CURLOPT_VERBOSE, true);
		curl_setopt($this->_curl_handler, CURLOPT_HEADER, true);
		
		// Capture the response
		$response = new \Comb\Request\Response($this->_curl_handler, $this->_options);
		
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