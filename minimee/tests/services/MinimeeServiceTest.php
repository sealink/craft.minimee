<?php
namespace Craft;

use \Mockery as m;

class MinimeeServiceTest extends BaseTest
{
	public function setUp()
	{
		require_once __DIR__ . '/../vendor/autoload.php';

		$this->service = new MinimeeService();
//		$this->service->init();
	}

	public function dataProviderInValidUrls()
	{
		return [
			['domain.com'],
			['/domain.com']
		];
	}

	/**
	 * @dataProvider dataProviderInValidUrls
	 */
	public function testIsUrlInValid($url)
	{
		$isUrl = $this->getMethod($this->service, 'isUrl');
		$this->assertFalse($isUrl->invokeArgs($this->service, array($url)));
	}

	public function dataProviderValidUrls()
	{
		return [
			['http://domain.com'],
			['https://domain.com'],
			['//domain.com']
		];
	}

	/**
	 * @dataProvider dataProviderValidUrls
	 */
	public function testIsUrlValid($url)
	{
		$isUrl = $this->getMethod($this->service, 'isUrl');
		$this->assertTrue($isUrl->invokeArgs($this->service, array($url)));
	}
}