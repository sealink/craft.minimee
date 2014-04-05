<?php
namespace Craft;

use \Mockery as m;

class MinimeeServiceTest extends BaseTest
{
	public function setUp()
	{
		$_SERVER['SERVER_SOFTWARE'] = 'Apache';
		
		$this->autoload();

        // $this->config = m::mock('Craft\ConfigService');
        // $this->config->shouldReceive('getIsInitialized')->andReturn(true);
        // $this->config->shouldReceive('get')->with('usePathInfo')->andReturn(true)->byDefault();
        // $this->config->shouldReceive('get')->with('translationDebugOutput')->andReturn(false)->byDefault();
        // $this->config->shouldReceive('get')->with('resourceTrigger')->andReturn('resource')->byDefault();
        // $this->config->shouldReceive('get')->with('version')->andReturn('2.0');

        // $this->setComponent(craft(), 'config', $this->config);

		minimee()->stash('plugin', new MinimeePlugin);
		minimee()->stash('service', new MinimeeService);

		// TODO: figure outo how to propery mock config so that we can run init()
		//minimee()->service->init();
	}

	public function autoload()
	{
		// our tests use this
		require_once __DIR__ . '/../../library/vendor/autoload.php';

		// These are usually automatically loaded by Craft
		require_once __DIR__ . '/../../MinimeePlugin.php';
		require_once __DIR__ . '/../../services/MinimeeService.php';

		// this usually happens in MinimeePlugin::init()
		require_once __DIR__ . '/../vendor/autoload.php';

		// And these Craft can usually autoload
		// require_once __DIR__ . '/../../models/Minimee_AssetBaseModel.php';
		// require_once __DIR__ . '/../../models/Minimee_LocalAssetModel.php';
		// require_once __DIR__ . '/../../models/Minimee_RemoveAssetModel.php';
		// require_once __DIR__ . '/../../models/Minimee_SettingsModel.php';
	}

	public function testSetCacheTimestampAlwaysSetsMax()
	{
		$dt = new DateTime('now');
		$nowTimestamp = $dt->getTimestamp();

		minimee()->service->cacheTimestamp = $dt;
		$this->assertEquals($nowTimestamp, minimee()->service->cacheTimestamp);

		// reduce by a day
		$dt->modify("-1 day");
		$yesterdayTimestamp = $dt->getTimestamp();

		minimee()->service->cacheTimestamp = $dt;
		$this->assertEquals($nowTimestamp, minimee()->service->cacheTimestamp);

		// increase by 2 days
		$dt->modify("+2 day");
		$tomorrowTimestamp = $dt->getTimestamp();

		minimee()->service->cacheTimestamp = $dt;
		$this->assertEquals($tomorrowTimestamp, minimee()->service->cacheTimestamp);

		// test that setting it to the same value has no ill effect
		minimee()->service->cacheTimestamp = $dt;
		$this->assertEquals($tomorrowTimestamp, minimee()->service->cacheTimestamp);
	}

	public function testGetCacheTimestampWhenZeroReturnsPaddedZeros()
	{
		$getCacheTimestamp = $this->getMethod(minimee()->service, 'getCacheTimestamp');
		$this->assertEquals('00000000', $getCacheTimestamp->invoke(minimee()->service));
	}

	public function testGetCacheHashIsEncrypted()
	{
		$getCacheHash = $this->getMethod(minimee()->service, 'getCacheHash');
		$this->assertEquals(sha1(''), $getCacheHash->invoke(minimee()->service));
	}

	public function testMakeTagsByTypePassingCssStringUsingDefaultTemplate()
	{
		$css = 'http://domain.dev/cache/filename.hash.css';
		$cssTagTemplate = minimee()->service->settings->cssTagTemplate;

		$rendered = sprintf($cssTagTemplate, $css);
		$this->assertEquals($rendered, minimee()->service->makeTagsByType('css', $css));
	}

	public function testMakeTagsByTypePassingCssArrayUsingDefaultTemplate()
	{
		$cssArray = array(
			'http://domain.dev/cache/filename.hash1.css',
			'http://domain.dev/cache/filename.hash2.css'
		);
		$cssTagTemplate = minimee()->service->settings->cssTagTemplate;

		$rendered = '';
		foreach($cssArray as $css)
		{
			$rendered .= sprintf($cssTagTemplate, $css);
		}

		$this->assertEquals($rendered, minimee()->service->makeTagsByType('css', $cssArray));
	}

	public function testMakeTagsByTypePassingCssStringUsingCustomTemplate()
	{
		$css = 'http://domain.dev/cache/filename.hash.css';
		$cssTagTemplate = '<link rel="stylesheet" type="text/css" media="screen" href="%s"/>';

		minimee()->service->settings->cssTagTemplate = $cssTagTemplate;

		$rendered = sprintf($cssTagTemplate, $css);

		$this->assertEquals($rendered, minimee()->service->makeTagsByType('css', $css));

	}

	public function testMakeTagsByTypePassingCssArrayUsingCustomTemplate()
	{
		$cssArray = array(
			'http://domain.dev/cache/filename.hash1.css',
			'http://domain.dev/cache/filename.hash2.css'
		);
		$cssTagTemplate = '<link rel="stylesheet" type="text/css" media="screen" href="%s"/>';

		minimee()->service->settings->cssTagTemplate = $cssTagTemplate;

		$rendered = '';
		foreach($cssArray as $css)
		{
			$rendered .= sprintf($cssTagTemplate, $css);
		}

		$this->assertEquals($rendered, minimee()->service->makeTagsByType('css', $cssArray));
	}

	public function testMakeTagsByTypePassingJsStringUsingDefaultTemplate()
	{
		$js = 'http://domain.dev/cache/filename.hash.js';
		$jsTagTemplate = minimee()->service->settings->jsTagTemplate;

		$rendered = sprintf($jsTagTemplate, $js);
		$this->assertEquals($rendered, minimee()->service->makeTagsByType('js', $js));
	}

	public function testMakeTagsByTypePassingJsArrayUsingDefaultTemplate()
	{
		$jsArray = array(
			'http://domain.dev/cache/filename.hash1.js',
			'http://domain.dev/cache/filename.hash2.js'
		);
		$jsTagTemplate = minimee()->service->settings->jsTagTemplate;

		$rendered = '';
		foreach($jsArray as $js)
		{
			$rendered .= sprintf($jsTagTemplate, $js);
		}

		$this->assertEquals($rendered, minimee()->service->makeTagsByType('js', $jsArray));
	}

	public function testMakeTagsByTypePassingJsStringUsingCustomTemplate()
	{
		$js = 'http://domain.dev/cache/filename.hash.js';
		$jsTagTemplate = '<script src="%s" type="text/javascript" defer></script>';

		minimee()->service->settings->jsTagTemplate = $jsTagTemplate;

		$rendered = sprintf($jsTagTemplate, $js);

		$this->assertEquals($rendered, minimee()->service->makeTagsByType('js', $js));
	}

	public function testMakeTagsByTypePassingJsArrayUsingCustomTemplate()
	{
		$jsArray = array(
			'http://domain.dev/cache/filename.hash1.js',
			'http://domain.dev/cache/filename.hash2.js'
		);
		$jsTagTemplate = '<script src="%s" type="text/javascript" defer></script>';

		minimee()->service->settings->jsTagTemplate = $jsTagTemplate;

		$rendered = '';
		foreach($jsArray as $js)
		{
			$rendered .= sprintf($jsTagTemplate, $js);
		}

		$this->assertEquals($rendered, minimee()->service->makeTagsByType('js', $jsArray));
	}

	public function dataProviderInvalidUrls()
	{
		return [
			['domain.com'],
			['/domain.com']
		];
	}

	/**
	 * @dataProvider dataProviderInvalidUrls
	 */
	public function testIsUrlInvalid($url)
	{
		$isUrl = $this->getMethod(minimee()->service, 'isUrl');
		$this->assertSame(false, $isUrl->invokeArgs(minimee()->service, array($url)));
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