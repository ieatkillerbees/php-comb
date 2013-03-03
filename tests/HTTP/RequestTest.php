<?php
require("../request.php");
use \Comb\Request;
class RequestTest extends PHPUnit_Framework_Testcase
{
	public function setUp()
	{
		$this->ro = new Comb\Request\Request("http://localhost");
	}
	
	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage MONKEY is not a syntactically valid URL
	 */
	public function testUrlValidation()
	{
		$this->ro->setUrl("MONKEY");
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage FOO is not a recognized HTTP verb/method
	 */
	public function testMethodValidation()
	{
		$ro = new Comb\Request\Request("http://localhost");
		$ro->request_method = "FOO";
	}
	
	public function testSimpleGet()
	{
		$this->ro->setUrl("http://localhost");
		$this->ro->setMethod("GET");
		
		$response = $this->ro->execute();
		$this->assertInstanceOf("Comb\Request\Response", $response);
	}
}