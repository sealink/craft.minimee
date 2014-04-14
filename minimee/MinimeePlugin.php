<?php namespace Craft;

/**
 * Minimee by John D Wells
 *
 * @author     	John D Wells <http://johndwells.com>
 * @package    	Minimee
 * @since		Craft 1.3
 * @copyright 	Copyright (c) 2014, John D Wells
 * @license 	http://opensource.org/licenses/mit-license.php MIT License
 * @link       	http://github.com/johndwells/Minimee-Craft
 */

/**
 * 
 */
class MinimeePlugin extends BasePlugin
{

	/**
	 * @return String
	 */
	public function getName()
	{
		return 'Minimee';
	}

	/**
	 * @return String
	 */
	public function getVersion()
	{
		return '0.8.1';
	}

	/**
	 * @return String
	 */
	public function getDeveloper()
	{
		return 'John D Wells';
	}

	/**
	 * @return String
	 */
	public function getDeveloperUrl()
	{
		return 'http://johndwells.com';
	}

	/**
	 * @return Bool
	 */
	public function hasCpSection()
	{
		return false;
	}

	/**
	 * Hook & Event binding is done during initialisation
	 * 
	 * @return Void
	 */
	public function init()
	{
		$this->_autoload();

		minimee()->stash('plugin', $this);
		minimee()->stash('service', craft()->minimee);

		$this->_bindEvents();
	}

	/**
	 * Logging any messages to Craft.
	 * 
	 * @param String $msg
	 * @param String $level
	 * @param Bool $force
	 * @return Void
	 */
	public static function log($msg, $level = LogLevel::Info, $force = false)
	{
		if(version_compare('2.0', craft()->getVersion(), '<'))
		{
			Craft::log($msg, $level, $force);
		}
		else
		{
			parent::log($msg, $level, $force);
		}
	}

	/**
	 * We define our setting attributes by way of our own Minimee_SettingsModel.
	 * 
	 * @return Array
	 */
	public function defineSettings()
	{
		Craft::import('plugins.minimee.models.Minimee_ISettingsModel');
		Craft::import('plugins.minimee.models.Minimee_SettingsModel');

		$settings = new Minimee_SettingsModel();

		return $settings->defineAttributes();
	}

	/**
	 * Renders the settings form to configure Minimee
	 * @return String
	 */
	public function getSettingsHtml()
	{
		$filesystemConfigPath = CRAFT_CONFIG_PATH . 'minimee.php';

		return craft()->templates->render('minimee/settings', array(
			'settings' => $this->getSettings(),
			'filesystemConfigExists' => (bool) IOHelper::fileExists($filesystemConfigPath)

		));
	}

	/**
	 * Register our Twig filter
	 *
	 * @return Twig_Extension
	 **/
	public function addTwigExtension()
	{
		Craft::import('plugins.minimee.twigextensions.MinimeeTwigExtension');

		return new MinimeeTwigExtension();
	}

	public function prepSettings($settings)
	{
		Craft::import('plugins.minimee.models.Minimee_ISettingsModel');
		Craft::import('plugins.minimee.models.Minimee_SettingsModel');

		$settingsModel = new Minimee_SettingsModel();

		return $settingsModel->prepSettings($settings);
	}

	/**
	 * Enable ability to serve cache assets from resources/minimee folder
	 *
	 * @return String
	 */
	public function getResourcePath($path)
	{
		if (strncmp($path, 'minimee/', 8) === 0)
		{
			return craft()->path->getStoragePath().'minimee/'.substr($path, 8);
		}
	}

	/**
	 * Register our cache path that can then be deleted from CP
	 */
	function registerCachePaths()
	{
		return array(
			minimee()->service->settings->cachePath => Craft::t('Minimee caches')
		);
	}

	/**
	 * Watch for the "createCache" event, and if in devMode, try to 
	 * clean up any expired caches
	 *
	 * @return void
	 */
	protected function _bindEvents()
	{
		craft()->on('minimee.createCache', function(Event $event) {
			if(craft()->config->get('devMode'))
			{
				minimee()->service->deleteExpiredCache();
			}
		});
	}

	/**
	 * Require any enums used across Minimee
	 *
	 * @return Void
	 */
	protected function _autoload()
	{
		require_once CRAFT_PLUGINS_PATH . 'minimee/library/vendor/autoload.php';

		Craft::import('plugins.minimee.enums.MinimeeType');
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