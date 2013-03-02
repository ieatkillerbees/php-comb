<?php
require("../request.php");

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
	 * @expectedException Exception
	 * @expectedExceptionMessage FOO is not a recognized HTTP verb/method
	 */
	public function testMethodValidation()
	{
		$ro = new Comb\Request\Request("http://localhost");
		$ro->setMethod("FOO");
	}
	
	public function testSimpleGet()
	{
		$this->ro->setUrl("http://localhost");
		$this->ro->setMethod("GET");
		echo $this->ro->execute();
	}
}