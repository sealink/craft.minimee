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
class MinimeeService extends BaseApplicationComponent
{
	const TimestampZero = '00000000';

	protected $_assets          = array();              // array of Minimee_AssetModelInterface
	protected $_type            = '';                   // css or js
	protected $_cacheBase       = '';                   // a concat of all asset filenames together
	protected $_cacheTimestamp  = self::TimestampZero;  // max timestamp of all assets
	protected $_settings        = null;                 // instance of Minimee_SettingsModel

	protected static $_pluginSettings	= array();		// static array of settings, a merge of DB and filesystem settings


	/*================= PUBLIC METHODS ================= */


	/**
	 * Shorthand function to process CSS
	 *
	 * @param Array $assets
	 * @param Array $settings
	 * @return String|Bool
	 */
	public function css($assets, $settings = array())
	{
		return $this->run(MinimeeType::Css, $assets, $settings);
	}

	/**
	 * Based on the cache's hashed base, attempts to delete any older versions of same name.
	 */
	public function deleteExpiredCache()
	{
		MinimeePlugin::log(Craft::t('Minimee is attempting to delete expired caches.'));

		$files = IOHelper::getFiles($this->settings->cachePath);

		foreach($files as $file)
		{
			// skip self
			if ($file === $this->cacheFilenamePath) continue;

			if (strpos($file, $this->hashOfCacheBasePath) === 0)
			{
				MinimeePlugin::log(Craft::t('Minimee is attempting to delete file: ') . $file);

				// suppress errors by passing true as second parameter
				IOHelper::deleteFile($file, true);
			}
		}
	}

	/**
	 * During startup, fetch settings from our plugin / config
	 *
	 * @return Void
	 */
	public function init()
	{
		parent::init();

		self::$_pluginSettings = minimee()->plugin->getSettings()->getAttributes();

		// as of v2.0 we can take filesystem configs
		if(version_compare('2.0', craft()->getVersion(), '<='))
		{
			foreach(self::$_pluginSettings as $attribute => $value)
			{
				if(craft()->config->exists($attribute, 'minimee'))
				{
					self::$_pluginSettings[$attribute] = craft()->config->get($attribute, 'minimee');
				}
			}
		}

		MinimeePlugin::log(Craft::t('Minimee has been initialised.'));
	}

	/**
	 * Shorthand function to process JS
	 *
	 * @param Array $assets
	 * @param Array $settings
	 * @return String|Bool
	 */
	public function js($assets, $settings = array())
	{
		return $this->run(MinimeeType::Js, $assets, $settings);
	}

	/**
	 * Generate the HTML tag based on type
	 * In future this will be configurable.
	 *
	 * @param String $type
	 * @param Array $assets
	 * @return String
	 */
	public function makeTagsByType($type, $assets = array())
	{
		$assets = ( ! is_array($assets)) ? array($assets) : $assets;
		$tags = '';

		foreach($assets as $asset)
		{
			switch ($type)
			{
				case (MinimeeType::Css) :

					$cssTagTemplate = $this->settings->cssTagTemplate;
					$tags .= sprintf($cssTagTemplate, $asset);

				break;

				case (MinimeeType::Js) :

					$jsTagTemplate = $this->settings->jsTagTemplate;
					$tags .= sprintf($jsTagTemplate, $asset);

				break;
			}
		}

		return $tags;
	}

	/**
	 * Wrapper for how we must return a twig option rather than raw HTML
	 *
	 * @param string
	 * @return Twig_Markup
	 */
	public function returnHtmlAsTwigMarkup($html)
	{
		// Prevent having to use the |raw filter when calling variable in template
		// http://pastie.org/6412894#1
		$charset = craft()->templates->getTwig()->getCharset();
		return new \Twig_Markup($html, $charset);
	}

	/**
	 * Main service function that encapsulates an entire Minimee run
	 *
	 * @param String $type
	 * @param Array $assets
	 * @param Array $settings
	 * @return Array|Bool
	 */
	public function run($type, $assets, $settings = array())
	{
		$assets = ( ! is_array($assets)) ? array($assets) : $assets;
		$settings = ( ! is_array($settings)) ? array($settings) : $settings;

		try
		{
			$this->reset()
				 ->setSettings($settings)
				 ->setType($type)
				 ->setAssets($assets)
				 ->flightcheck()
				 ->checkHeaders();

			$return = array();
			if($this->isCombineEnabled())
			{
				$return[] = $this->cache();
			}
			else
			{
				foreach($assets as $asset)
				{
					$return[] = $this->reset()
									 ->setSettings($settings)
									 ->setType($type)
									 ->setAssets($asset)
									 ->cache();
				}
			}
		}
		catch (Exception $e)
		{
			return $this->abort($e);
		}

		return $return;
	}


	/*================= PROTECTED METHODS ================= */


	/**
	 * Internal function used when aborting due to error
	 *
	 * @param String $e
	 * @param String $level
	 * @return Bool
	 */
	protected function abort($e, $level = LogLevel::Error)
	{
		MinimeePlugin::log(Craft::t('Minimee is aborting with the message: ') . $e, $level);

		if(craft()->config->get('devMode')
			&& $this->settings->enabled
			&& ($level == LogLevel::Warning || $level == LogLevel::Error))
		{
			throw new Exception($e);
		}

		return false;
	}

	/**
	 * Append an asset's name to the cacheBase
	 * @param String $name
	 * @return Void
	 */
	protected function appendToCacheBase($name)
	{
		$this->_cacheBase .= $name;
	}

	/**
	 * Fetch or creates cache.
	 *
	 * @return String
	 */
	protected function cache()
	{
		if( ! $this->cacheExists())
		{
			$this->createCache();
		}

		return $this->getCacheUrl();
	}

	/**
	 * Checks if the cache exists.
	 *
	 * @return Bool
	 */
	protected function cacheExists()
	{
		foreach ($this->assets as $asset)
		{
			$this->setMaxCacheTimestamp($asset->lastTimeModified);
			$this->appendToCacheBase($asset->filename);
		}

		if( ! IOHelper::fileExists($this->cacheFilenamePath))
		{
			return false;
		}

		if($this->settings->useResourceCache())
		{
			$cacheLastTimeModified = IOHelper::getLastTimeModified($this->cacheFilenamePath);

			if($cacheLastTimeModified->getTimestamp() < $this->cacheTimestamp)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate all assets prior to run.
	 *
	 * @return this
	 */
	protected function checkHeaders()
	{
		foreach($this->assets as $asset)
		{
			if( ! $asset->exists())
			{
				throw new Exception(Craft::t('Minimee could not find asset ' . $asset->filenamePath . '.'));
			}
		}

		return $this;
	}

	/**
	 * Creates cache of assets.
	 *
	 * @return Void
	 */
	protected function createCache()
	{
		$contents = '';
		
		foreach($this->assets as $asset)
		{
			$contents .= $this->minifyAsset($asset) . "\n";
		}

		IOHelper::writeToFile($this->cacheFilenamePath, $contents);

		$this->onCreateCache(new Event($this));
	}

	/**
	 * Return whether we should combine our cache or not
	 *
	 * @return Bool
	 */
	protected function isCombineEnabled()
	{
		switch($this->type)
		{
			case MinimeeType::Css :
				return (bool) $this->settings->combineCssEnabled;
			break;

			case MinimeeType::Js :
				return (bool) $this->settings->combineJsEnabled;
			break;
		}
	}

	/**
	 * Perform pre-flight checks to ensure we can run.
	 *
	 * @return this
	 */
	protected function flightcheck()
	{
		if ( ! self::$_pluginSettings)
		{
			throw new Exception(Craft::t('Minimee is not installed.'));
		}

		if( ! $this->settings->enabled)
		{
			throw new Exception(Craft::t('Minimee has been disabled via settings.'));
		}

		if( ! $this->settings->validate())
		{
			$exceptionErrors = '';
			foreach($this->settings->getErrors() as $error)
			{
				$exceptionErrors .= implode('. ', $error);
			}

			throw new Exception(Craft::t('Minimee has detected invalid plugin settings: ') . $exceptionErrors);
		}
		
		if($this->settings->useResourceCache())
		{
			IOHelper::ensureFolderExists($this->settings->cachePath);
		}
		else
		{
			if( ! IOHelper::folderExists($this->settings->cachePath))
			{
				throw new Exception(Craft::t('Minimee\'s Cache Folder does not exist: ' . $this->settings->cachePath));
			}
		}

		if( ! IOHelper::isWritable($this->settings->cachePath))
		{
			throw new Exception(Craft::t('Minimee\'s Cache Folder is not writable: ' . $this->settings->cachePath));
		}

		if( ! $this->assets)
		{
			throw new Exception(Craft::t('Minimee has no assets to operate upon.'));
		}

		if( ! $this->type)
		{
			throw new Exception(Craft::t('Minimee has no value for `type`.'));
		}

		return $this;
	}

	/**
	 * @return Array
	 */
	protected function getAssets()
	{
		return $this->_assets;
	}

	/**
	 * @return Array
	 */
	protected function getCacheBase()
	{
		return $this->_cacheBase;
	}

	/**
	 * @return String
	 */
	protected function getCacheFilename()
	{
		if($this->settings->useResourceCache())
		{
			return sprintf('%s.%s', $this->getHashOfCacheBase(), $this->type);
		}

		return sprintf('%s.%s.%s', $this->getHashOfCacheBase(), $this->cacheTimestamp, $this->type);
	}

	/**
	 * @return String
	 */
	protected function getCacheFilenamePath()
	{
		return $this->settings->cachePath . $this->cacheFilename;
	}

	/**
	 * @return String
	 */
	protected function getHashOfCacheBasePath()
	{
		return $this->settings->cachePath . $this->getHashOfCacheBase();
	}

	/**
	 * @return String
	 */
	protected function getCacheTimestamp()
	{
		return ($this->_cacheTimestamp) ? $this->_cacheTimestamp : self::TimestampZero;
	}

	/**
	 * @return String
	 */
	protected function getCacheUrl()
	{
		if($this->settings->useResourceCache())
		{
			$path = '/minimee/' . $this->cacheFilename;

			$dateParam = craft()->resources->dateParam;
			$params[$dateParam] = IOHelper::getLastTimeModified($this->cacheFilenamePath)->getTimestamp();

			return UrlHelper::getUrl(craft()->config->getResourceTrigger() . $path, $params);
		}
		
		return $this->settings->cacheUrl . $this->cacheFilename;
	}

	/**
	 * @return String
	 */
	protected function getHashOfCacheBase()
	{
		return sha1($this->_cacheBase);
	}

	/**
	 * @return Minimee_SettingsModel
	 */
	protected function getSettings()
	{
		// if null, then set based on our inits
		if(is_null($this->_settings))
		{
			$this->_settings = Minimee_SettingsModel::populateModel(self::$_pluginSettings);
		}

		return $this->_settings;
	}

	/**
	 * @return String
	 */
	protected function getType()
	{
		return $this->_type;
	}

	/**
	 * Determine if string is valid URL
	 *
	 * @param   string  String to test
	 * @return  bool    TRUE if yes, FALSE if no
	 */
	protected function isUrl($string)
	{
		// from old _isURL() file from Carabiner Asset Management Library
		// modified to support leading with double slashes
		return (preg_match('@((https?:)?//([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $string) > 0);
	}

	/**
	 * Given an asset, fetches and returns minified contents.
	 *
	 * @param Minimee_BaseAssetModel $asset
	 * @return String
	 */
	protected function minifyAsset($asset)
	{
		craft()->config->maxPowerCaptain();

		switch ($this->type) :
			
			case MinimeeType::Js:

				if($this->settings->minifyJsEnabled)
				{
					$contents = \JSMin::minify($asset->contents);
				}
				else
				{
					$contents = $asset->contents;
				}

			break;
			
			case MinimeeType::Css:

				$cssPrependUrl = dirname($asset->filenameUrl) . '/';

				$contents = \Minify_CSS_UriRewriter::prepend($asset->contents, $cssPrependUrl);

				if($this->settings->minifyJsEnabled)
				{
					$contents = \Minify_CSS::minify($contents);
				}

			break;

		endswitch;

		return $contents;
	}

	/**
	 * Raise our 'onCreateCache' event
	 *
	 * @return Void
	 */
	protected function onCreateCache($event)
	{
		$this->raiseEvent('onCreateCache', $event);
	}

	/**
	 * Safely resets service to prepare for a clean run.
	 *
	 * @return this
	 */
	protected function reset()
	{
		$this->_assets                  = array();
		$this->_settings                = null;
		$this->_type                    = '';
		$this->_cacheBase               = '';
		$this->_cacheTimestamp          = self::TimestampZero;

		return $this;
	}

	/**
	 * @param Array $assets
	 * @return this
	 */
	protected function setAssets($assets)
	{
		$assets = ( ! is_array($assets)) ? array($assets) : $assets;

		foreach($assets as $asset)
		{
			if ($this->isUrl($asset))
			{
				$model = array(
					'filename' => $asset,
					'filenameUrl' => $asset,
					'filenamePath' => $asset
				);

				$this->_assets[] = Minimee_RemoteAssetModel::populateModel($model);
			}
			else
			{
				$model = array(
					'filename' => $asset,
					'filenameUrl' => $this->settings->baseUrl . $asset,
					'filenamePath' => $this->settings->filesystemPath . $asset
				);

				$this->_assets[] = Minimee_LocalAssetModel::populateModel($model);
			}
		}

		return $this;
	}

	/**
	 * @param String $name
	 * @return Void
	 */
	protected function setCacheBase($name)
	{
		$this->_cacheBase = $name;
	}

	/**
	 * @param String $dateTime
	 * @return Void
	 */
	protected function setCacheTimestamp($timestamp)
	{
		$this->_cacheTimestamp = $timestamp ?: self::TimestampZero;
	}

	/**
	 * @param DateTime $lastTimeModified
	 * @return Void
	 */
	protected function setMaxCacheTimestamp(DateTime $lastTimeModified)
	{
		$timestamp = $lastTimeModified->getTimestamp();
		$this->cacheTimestamp = max($this->cacheTimestamp, $timestamp);
	}

	/**
	 * Configure our service based off the settings in plugin,
	 * allowing plugin settings to be overridden at runtime.
	 *
	 * @param Array $settingsOverrides
	 * @return void
	 */
	protected function setSettings($settingsOverrides)
	{
		$settingsOverrides = ( ! is_array($settingsOverrides)) ? array($settingsOverrides) : $settingsOverrides;

		$runtimeSettings = array_merge(self::$_pluginSettings, $settingsOverrides);

		$this->_settings = Minimee_SettingsModel::populateModel($runtimeSettings);

		return $this;
	}

	/**
	 * @param String $type
	 * @return this
	 */
	protected function setType($type)
	{

		$this->type = strtolower($type);

		return $this;
	}
}
