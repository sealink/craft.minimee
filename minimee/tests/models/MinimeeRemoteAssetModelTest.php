<?php
namespace Craft;

use \Guzzle\Http\Client as Client;
use \Guzzle\Plugin\Mock\MockPlugin as MockPlugin;
use \Guzzle\Http\Message\Response as Response;
use \Mockery as m;

class MinimeeRemoteAssetModelTest extends BaseTest
{
	protected $_model;

	/**
	 * Called at the start of each test run; helps bootstrap our tests
	 *
	 * @return void
	 */
	public function setUp()
	{
		$_SERVER['SERVER_SOFTWARE'] = 'Apache';
		
		require_once __DIR__ . '/../vendor/autoload.php';
	}

	public function testGetContentsSendsRequestOnlyOnce()
	{
		$mock = new MockPlugin();
		$mock->addResponse(new Response(200, array(), '* { color: red }'));
		$mock->addResponse(new Response(404));

		$client = new Client();
		$client->addSubscriber($mock);

		$remoteAsset = new Minimee_RemoteAssetModel(array(), $client);

		$this->assertEquals('* { color: red }', $remoteAsset->contents);
		$this->assertEquals('* { color: red }', $remoteAsset->contents);
	}

	/**
     * @expectedException Exception
     */
	public function testGetContentsIfNotExists()
	{
		$mock = new MockPlugin();
		$mock->addResponse(new Response(404));

		$client = new Client();
		$client->addSubscriber($mock);

		$remoteAsset = new Minimee_RemoteAssetModel(array(
			'filenamePath' => 'http://domain.dev/thisfilewillnotexist'
		), $client);

		$contents = $remoteAsset->contents;
	}
	
	public function testGetContentsIfExists()
	{
		$mock = new MockPlugin();
		$mock->addResponse(new Response(200, array(), '* { color: red }'));

		$client = new Client();
		$client->addSubscriber($mock);

		$remoteAsset = new Minimee_RemoteAssetModel(array(), $client);

		$this->assertEquals('* { color: red }', $remoteAsset->contents);
	}

	public function testGetLastTimeModifiedIsAlwaysZero()
	{
		$this->_populateWith(array());

		$lastTimeModified = $this->_model->lastTimeModified;

		$this->assertSame(0, $lastTimeModified->getTimestamp());
	}

	public function testExistsIsAlwaysTrue()
	{
		$this->_populateWith(array());

		$this->assertTrue($this->_model->exists());
	}

	public function testToStringReturnsFilename()
	{
		$this->_populateWith(array(
			'filename' => 'http://domain.com/assets/style.css'
		));

		$this->assertEquals('http://domain.com/assets/style.css', sprintf($this->_model));
	}

	public function testSetFilenamePathRemovesDoubleSlashes()
	{
		$this->_populateWith(array());

		$this->_model->filenamePath = 'http://domain.com///cache';
		$this->assertEquals('http://domain.com/cache', $this->_model->filenamePath);
	}

	public function testSetFilenamePathRemovesDoubleSlashesProtocolRelative()
	{
		$this->_populateWith(array());

		$this->_model->filenameUrl = '//domain.com///cache';
		$this->assertEquals('//domain.com/cache', $this->_model->filenameUrl);
	}

	public function testSetFilenameUrlRemovesDoubleSlashes()
	{
		$this->_populateWith(array());

		$this->_model->filenameUrl = 'http://domain.com///cache';
		$this->assertEquals('http://domain.com/cache', $this->_model->filenameUrl);
	}

	public function testSetFilenameUrlRemovesDoubleSlashesProtocolRelative()
	{
		$this->_populateWith(array());

		$this->_model->filenameUrl = '//domain.com///cache';
		$this->assertEquals('//domain.com/cache', $this->_model->filenameUrl);
	}

	protected function _inspect($data)
	{
		fwrite(STDERR, print_r($data));
	}

	/**
	 * Internal method for shorthand populating our Minimee_RemoteAssetModel
	 * 
	 * @param Array $attributes
	 * @return Minimee_RemoteAssetModel
	 */
	protected function _populateWith($attributes)
	{
		$this->_model = Minimee_RemoteAssetModel::populateModel($attributes);
	}
}