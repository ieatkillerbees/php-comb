<?php
/**
 * Response.php
 *
 * Comn - A wrapper for PHP's cURL implementation
 *
 * Response provides a sane OO interface to an HTTP response
 *
 * @author Samantha Quinones <samantha@tembies.com>
 * @package Comb
 * @version 0.1
 */

namespace Comb\HTTP;

class Response
{
	public $response_code;
	public $response_headers_raw;
	public $response_headers;
	public $response_body_raw;
	public $response_details;
	public $exception = false;

	private $_curl_handler;
	private $_options = [
		"success_codes" => [200,201,202,203,204,205,206,300,301,302,303,304,305,306,307],
	];

	public function __construct($curl_handler, array $options = []) {
		$this->_curl_handler = $curl_handler;
		$this->_options		 = array_merge($this->_options, $options);
		
		$raw_response = curl_exec($this->_curl_handler);
		list($headers, $body) = explode("\r\n\r\n", $raw_response, 2);
		$this->response_headers_raw = $headers;
		$this->response_body_raw	  = $body;
		$this->response_code		  = curl_getinfo($this->_curl_handler, CURLINFO_HTTP_CODE);
		$this->response_details		  = curl_getinfo($this->_curl_handler);

		// Post-request processing
		$this->setException();    // Set exception flag on error
		$this->response_headers = $this->_parseHeaders();
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	public function setException()
	{
		if (!in_array($this->response_code, $this->_options["success_codes"]))
		{
			$this->_exception = true;
		}
	}
	
	private function _parseHeaders()
	{
		$h_array = [];
		
		// Splt on a CRLF break followed by whitespace
		// \x0d = CR, \x0a = LF, \x09 = tab, \x20 = space 
		$headers = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $this->response_headers_raw));

		// Loop the headers
		foreach($headers as $header) {
			// If the line follows the HeaderName: HeaderVal pattern...
			if(preg_match('/([^:]+): (.+)/m', $header, $match)) 
			{
				$header_name = strtolower(preg_replace('/(?<=^|[\x09\x20\x2d])./e', 'strtoupper("\0")', strtolower(trim($match[1]))));
				$h_array[$header_name] = trim($match[2]);
			}
		}
		return $h_array;
	}
	
}