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

// @TODO provide autoloader interface
require("exceptions.php");

interface RequestInterface
{
	public function execute();
}

/**
 * Request - A generic HTTP request
 *
 */
class Request implements RequestInterface
{
	/**
	 * @var array List of valid HTTP verbs.
	 */
	private $_allowed_verbs = ["GET", "HEAD", "POST", "PUT", "DELETE", "TRACE", "OPTIONS", "CONNECT", "PATCH"];

	/**
	 * @var array Options hasg
	 */
	private $_options	  = array();
	
	/**
	 * @var phpcURL interface
	 */
	private $_curlHandler = null;

	/**
	 * @var string HTTP method/verb
	 */
	private $_method	  = null;
	
	/**
	 * @var string Target URL
	 */
	private $_url		  = null;
	
	/**
	 * @return null
	 * @param $url string Target URL
	 * @param $options array An array of options
	 */
	public function __construct($url, array $options = array())
	{
		$this->setUrl($url);
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
	public function setMethod($method) 
	{
		if (!(in_array($method, $this->_allowed_verbs))) {
			throw new \Exception("${method} is not a recognized HTTP verb/method");
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
	
	/**
	 * Returns the response as a string.
	 * @return string
	 */
	protected function _executeCurl()
	{
		// Create a cURL handler
		$this->_curlHandler = curl_init();
		
		// Instruct cURL to return the response as a string.
		curl_setopt($this->_curlHandler, CURLOPT_RETURNTRANSFER, 1);
		
		// Set the target URL
		curl_setopt($this->_curlHandler, CURLOPT_URL, $this->_url);
		
		// Capture the response
		$response = curl_exec($this->_curlHandler);
		
		// Close the handler.
		curl_close($this->_curlHandler);
		
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