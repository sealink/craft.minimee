<?php
namespace Craft;

use \Mockery as m;

class MinimeeServiceTest extends BaseTest
{
	public function setUp()
	{
		require_once __DIR__ . '/../vendor/autoload.php';
		require_once __DIR__ . '/../../library/vendor/autoload.php';
		require_once __DIR__ . '/../../MinimeePlugin.php';
		require_once __DIR__ . '/../../services/MinimeeService.php';

        $this->config = m::mock('Craft\ConfigService');
        $this->config->shouldReceive('getIsInitialized')->andReturn(true);
        $this->config->shouldReceive('usePathInfo')->andReturn(true)->byDefault();

        $this->setComponent(craft(), 'config', $this->config);

		minimee()->stash('plugin', new MinimeePlugin);
		minimee()->stash('service', new MinimeeService);

		//minimee()->service->init();
	}

	public function dataProviderInValidUrls()
	{
		return [
			['domain.com'],
			// ['/domain.com']
		];
	}

	/**
	 * @dataProvider dataProviderInValidUrls
	 */
	public function testIsUrlInValid($url)
	{
		$isUrl = $this->getMethod(minimee()->service, 'isUrl');
		$this->assertSame(false, $isUrl->invokeArgs(minimee()->service, array($url)));
	}

	public function dataProviderValidUrls()
	{
		return [
			['http://domain.com'],
			// ['https://domain.com'],
			// ['//domain.com']
		];
	}

	/**
	 * @dataProvider dataProviderValidUrls
	 */
	public function testIsUrlValid($url)
	{
		$isUrl = $this->getMethod(minimee()->service, 'isUrl');
		$this->assertTrue($isUrl->invokeArgs(minimee()->service, array($url)));
	}
}


/**
 * A way to grab the dependency container within the Craft namespace
 */
if (!function_exists('\\Craft\\minimee'))
{
	function minimee()
	{
		return \SelvinOrtiz\Zit\Zit::getInstance();
	}
}