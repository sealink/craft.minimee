<?php
namespace Craft;

use \Mockery as m;

class MinimeeSettingsModelTest extends BaseTest
{
	protected $_model;

	/**
	 * Called at the start of each test run; helps bootstrap our tests
	 *
	 * @return void
	 */
	public function setUp()
	{
		require_once __DIR__ . '/../vendor/autoload.php';
	}

	public function testGetFilesystemPathWithValue()
	{
		$this->_populateWith(array(
			'filesystemPath' => '/var/www/public/'
		));

		$this->assertEquals('/var/www/public/', $this->_model->filesystemPath);
	}

	public function testGetFilesystemPathWithoutValue()
	{
		$this->_populateWith(array());

		$this->assertEquals('/some/path/to/craft.dev/', $this->_model->filesystemPath);
	}

	public function testGetFilesystemPathHasTrailingSlash()
	{
		$this->_populateWith(array(
			'filesystemPath' => '/var/www/public/'
		));

		$this->assertEquals('/var/www/public/', $this->_model->filesystemPath);

		$this->_populateWith(array(
			'filesystemPath' => '/var/www/public'
		));

		$this->assertEquals('/var/www/public/', $this->_model->filesystemPath);
	}

	public function testGetFilesystemPathParsesEnvironmentVariable()
	{
		$config = m::mock('Craft\ConfigService')->makePartial();
		$config->shouldReceive('parseEnvironmentString')->with('{stringToParse}')->andReturn('/var/www/public/');
		$this->setComponent(craft(), 'config', $config);

		$this->_populateWith(array(
			'filesystemPath' => '{stringToParse}'
		));

		$this->assertEquals('/var/www/public/', $this->_model->filesystemPath);
	}

	public function testGetBaseUrlWithValue()
	{
		$this->_populateWith(array(
			'baseUrl' => 'http://domain.com/'
		));

		$this->assertEquals('http://domain.com/', $this->_model->baseUrl);
	}

	public function testGetBaseUrlWithoutValue()
	{
		$this->_populateWith(array());

		$this->assertEquals('http://craft.dev/', $this->_model->baseUrl);
	}

	public function testGetBaseUrlHasTrailingSlash()
	{
		$this->_populateWith(array(
			'baseUrl' => 'http://domain.com'
		));

		$this->assertEquals('http://domain.com/', $this->_model->baseUrl);

		$this->_populateWith(array(
			'baseUrl' => 'http://domain.com/'
		));

		$this->assertEquals('http://domain.com/', $this->_model->baseUrl);
	}

	public function testGetBaseUrlParsesEnvironmentVariable()
	{
		$config = m::mock('Craft\ConfigService')->makePartial();
		$config->shouldReceive('parseEnvironmentString')->with('{stringToParse}')->andReturn('http://domain.com/');
		$this->setComponent(craft(), 'config', $config);

		$this->_populateWith(array(
			'baseUrl' => '{stringToParse}'
		));

		$this->assertEquals('http://domain.com/', $this->_model->baseUrl);
	}

	public function testGetCachePathWithValue()
	{
		$this->_populateWith(array(
			'cachePath' => '/var/www/public/cache/'
		));

		$this->assertEquals('/var/www/public/cache/', $this->_model->cachePath);
	}

	public function testGetCachePathWithoutValue()
	{
		$path = m::mock('Craft\PathService')->makePartial();
		$path->shouldReceive('getStoragePath')->andReturn('/var/www/craft/storage/');
		$this->setComponent(craft(), 'path', $path);

		$this->_populateWith(array());

		$this->assertEquals('/var/www/craft/storage/minimee/', $this->_model->cachePath);
	}

	public function testGetCachePathHasTrailingSlash()
	{
		$this->_populateWith(array(
			'cachePath' => '/var/ww/public/cache'
		));

		$this->assertEquals('/var/ww/public/cache/', $this->_model->cachePath);

		$this->_populateWith(array(
			'cachePath' => '/var/ww/public/cache/'
		));

		$this->assertEquals('/var/ww/public/cache/', $this->_model->cachePath);
	}

	public function testGetCachePathParsesEnvironmentVariable()
	{
		$config = m::mock('Craft\ConfigService')->makePartial();
		$config->shouldReceive('parseEnvironmentString')->with('{stringToParse}')->andReturn('/var/ww/public/cache/');
		$this->setComponent(craft(), 'config', $config);

		$this->_populateWith(array(
			'cachePath' => '{stringToParse}'
		));

		$this->assertEquals('/var/ww/public/cache/', $this->_model->cachePath);
	}

	public function testGetCacheUrlWithValue()
	{
		$this->_populateWith(array(
			'cacheUrl' => 'http://domain.com/cache/'
		));

		$this->assertEquals('http://domain.com/cache/', $this->_model->cacheUrl);
	}

	public function testGetCacheUrlWithoutValue()
	{
		// Attempt 1: Try alias mocking
		// $urlHelper = m::mock('alias:\Craft\UrlHelper');
		// $urlHelper->shouldReceive('getResourceUrl')->andReturn('http://domain.com/resources/minimee/');

		// Attempt 2: Try to mock via PHPUnit
		// $UrlHelper = $this->getMockClass(
		// 	'Craft\UrlHelper',          // name of class to mock
		// 	array('getResourceUrl') 	// list of methods to mock
		// );
		// $UrlHelper::staticExpects($this->any())
		// 	->method('getResourceUrl')
		// 	->will($this->returnValue('http://domain.com/resources/minimee/'));
 
 		// Until we can successfully mock UrlHelper::getResourceUrl(),
 		// the below relies on the contents of craft/app/tests/bootstrap.php
		$this->_populateWith(array());
		$this->assertEquals('http://craft.dev/cpresources/minimee/', $this->_model->cacheUrl);
	}

	public function testGetCacheUrlHasTrailingSlash()
	{
		$this->_populateWith(array(
			'cacheUrl' => 'http://domain.com/cache'
		));

		$this->assertEquals('http://domain.com/cache/', $this->_model->cacheUrl);

		$this->_populateWith(array(
			'cacheUrl' => 'http://domain.com/cache/'
		));

		$this->assertEquals('http://domain.com/cache/', $this->_model->cacheUrl);
	}

	public function testGetCacheUrlParsesEnvironmentVariable()
	{
		$config = m::mock('Craft\ConfigService')->makePartial();
		$config->shouldReceive('parseEnvironmentString')->with('{stringToParse}')->andReturn('http://domain.com/cache/');
		$this->setComponent(craft(), 'config', $config);

		$this->_populateWith(array(
			'cacheUrl' => '{stringToParse}'
		));

		$this->assertEquals('http://domain.com/cache/', $this->_model->cacheUrl);
	}

	public function testGetAttributeAccessors()
	{
		$model = \Mockery::mock('Craft\Minimee_SettingsModel')->makePartial();

		$model->shouldReceive('getCachePath')->andReturn(true);
		$model->shouldReceive('getCacheUrl')->andReturn(true);
		$model->shouldReceive('getBaseUrl')->andReturn(true);
		$model->shouldReceive('getFilesystemPath')->andReturn(true);

		$this->assertSame(true, $model->cachePath);
		$this->assertSame(true, $model->cacheUrl);
		$this->assertSame(true, $model->baseUrl);
		$this->assertSame(true, $model->filesystemPath);
	}

	public function testForceTrailingSlashWithSlash()
	{
		$model = new Minimee_SettingsModel;

		$this->assertEquals('string/', $model->forceTrailingSlash('string/'));
	}

	public function testForceTrailingSlashWithoutSlash()
	{
		$model = new Minimee_SettingsModel;

		$this->assertEquals('string/', $model->forceTrailingSlash('string'));
	}

	public function dataProviderZeroOne()
	{
		return [
			[0],
			[1]
		];
	}

	/**
	 * @dataProvider dataProviderZeroOne
	 */
	public function testPrepSettingsCastBools($zeroOne)
	{
		$model = new Minimee_SettingsModel;

		// Bools are saved as 0s and 1s in DB
		$prepped = $model->prepSettings(
			array(
        		'enabled' => $zeroOne,
        		'combineCssEnabled' => $zeroOne,
        		'combineJsEnabled' => $zeroOne,
        		'minifyCssEnabled' => $zeroOne,
        		'minifyJsEnabled' => $zeroOne
			)
		);

		$bool = (bool) $zeroOne;

		$this->_populateWith($prepped);

		$this->assertSame($bool, $this->_model->enabled);
		$this->assertSame($bool, $this->_model->combineCssEnabled);
		$this->assertSame($bool, $this->_model->combineJsEnabled);
		$this->assertSame($bool, $this->_model->minifyCssEnabled);
		$this->assertSame($bool, $this->_model->minifyJsEnabled);
	}

	public function testToStringReturnsOneOrZero()
	{
		$this->_populateWith(array(
			'enabled' => true
		));

		$this->assertSame('1', sprintf($this->_model));

		$this->_populateWith(array(
			'enabled' => false
		));

		$this->assertSame('0', sprintf($this->_model));
	}

	public function testUseResourceCacheWhenBothNonEmpty()
	{
		$this->_populateWith(array(
			'cachePath' => '/path/to/cache',
			'cacheUrl' => 'http://domain.com/cache'
		));

		$this->assertSame(false, $this->_model->useResourceCache());
	}

	public function testUseResourceCacheWhenOneIsEmpty()
	{
		$this->_populateWith(array(
			'cachePath' => '',
			'cacheUrl' => 'http://domain.com/cache'
		));

		$this->assertSame(false, $this->_model->useResourceCache());

		$this->_populateWith(array(
			'cachePath' => '/path/to/cache',
			'cacheUrl' => ''
		));

		$this->assertSame(false, $this->_model->useResourceCache());
	}

	public function testUseResourceCacheWhenBothEmpty()
	{
		$this->_populateWith(array(
			'cachePath' => '',
			'cacheUrl' => ''
		));

		$this->assertSame(true, $this->_model->useResourceCache());
	}

	public function testValidateCachePathAndUrlWhenBothEmpty()
	{
		$this->_populateWith(array(
			'cachePath' => '',
			'cacheUrl' => ''
		));

		$this->assertSame(true, $this->_model->validate());
	}

	public function testValidateCachePathAndUrlWhenBothNonEmpty()
	{
		$this->_populateWith(array(
			'cachePath' => '/path/to/cache',
			'cacheUrl' => 'http://domain.com/cache'
		));

		$this->assertSame(true, $this->_model->validate());
	}

	public function testValidateCachePathAndUrlWhenOneIsEmpty()
	{
		$this->_populateWith(array(
			'cachePath' => '',
			'cacheUrl' => 'http://domain.com/cache'
		));

		$this->_model->validateCachePathAndUrl();
		$this->assertSame(true, $this->_model->hasErrors());

		$this->_populateWith(array(
			'cachePath' => '/path/to/cache',
			'cacheUrl' => ''
		));

		$this->assertSame(false, $this->_model->validate());
		$this->assertSame(true, $this->_model->hasErrors());

		$errors = $this->_model->getErrors();
		$this->assertEquals(2, count($errors));
		$this->assertArrayHasKey('cachePath', $errors);
		$this->assertArrayHasKey('cacheUrl', $errors);
	}

	protected function _inspect($data)
	{
		fwrite(STDERR, print_r($data));
	}

	/**
	 * Internal method for shorthand populating our Minimee_SettingsModel
	 * 
	 * @param Array $attributes
	 * @return Minimee_SettingsModel
	 */
	protected function _populateWith($attributes)
	{
		$this->_model = Minimee_SettingsModel::populateModel($attributes);
	}
}