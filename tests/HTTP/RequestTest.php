<?php
require "/usr/share/php/PHPUnit/Autoload.php";
class RequestTest extends PHPUnit_Framework_Testcase
{
	public function setUp()
	{
		$this->ro = new \Comb\HTTP\Request([
			'service_url' => "http://localhost",		
		]);
	}
	
	/**
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage foo is not a property of this object
	 */
	public function testMagicGetInvalidProperty()
	{
		$foo = $this->ro->foo;
	}
	
	/**
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage foo is not a property of this object
	 */
	public function testMagicSetInvalidProperty()
	{
		$this->ro->foo 			= "foo";
	}
	
	public function testMagicSetSimpleProperty()
	{
		$this->ro->resp_timeout = 60;
		$this->assertEquals(60, $this->ro->resp_timeout);
	}
	
	/**
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage foo is not a method of this object
	 */
	public function testMagicCallInvalidMethod()
	{
		$this->ro->foo();
	}
	
	public function testMagicCallMethod()
	{
		$this->ro->setServiceUrl("http://localhost");
		$this->assertEquals("http://localhost", $this->ro->service_url);
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage MONKEY is not a syntactically valid URL
	 */
	public function testServiceUrlValidation()
	{
		$this->ro->service_url = "MONKEY";
		$this->ro->service_url = "http://localhost";
		$this->assertEquals("http://localhost", $this->ro->service_url);
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage FOO is not a recognized HTTP verb/method
	 */
	public function testRequestMethodValidation()
	{
		$this->ro->request_method = "FOO";
	}
	
	
	
	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage FOO is not a recognized HTTP verb/method
	 */
	public function testMethodValidation()
	{
		$this->ro->request_method = "FOO";
	}
	
	public function testSimpleGet()
	{
		$this->ro->service_url 	  = "http://localhost";
		$this->ro->request_method = "GET";
		$response = $this->ro->execute();
		$this->assertInstanceOf("Comb\HTTP\Response", $response);
	}
}