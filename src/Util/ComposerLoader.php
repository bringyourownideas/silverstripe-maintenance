<?php

namespace BringYourOwnIdeas\Maintenance\Util;

use Exception;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extensible;

/**
 * The composer loader class is responsible for dealing directly with composer.json and composer.lock files,
 * in terms of loading and parsing their contents.
 *
 * Any requirements for dealing with these files directly should use this class as a proxy.
 */
class ComposerLoader
{
    use Extensible, Configurable;

    /**
     * Set to a custom directory for Composer's '.composer' cache directory. This will only be used if the
     * `COMPOSER_HOME` environment variable is not defined and `HOME` is not defined or is not writable
     *
     * @config
     * @var string
     */
    private static $composer_cache_directory = '/tmp';

    /**
     * @var object
     */
    protected $json;

    /**
     * @var object
     */
    protected $lock;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @param string $basePath
     * @throws Exception
     */
    public function __construct($basePath = '')
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->build();
    }

    /**
     * Load and build the composer.json and composer.lock files
     *
     * @return $this
     * @throws Exception If either file could not be loaded
     */
    public function build()
    {
        // If there's no COMPOSER_HOME variable, set one
        // Mock COMPOSER_HOME if it's not defined already. Composer requires one of the two to be set.
        if (!Environment::getEnv('COMPOSER_HOME')) {
            // Check `HOME` and if it's writable (then we can let that be used).
            $home = Environment::getEnv('HOME');
            if (!$home || !is_dir($home) || !is_writable($home)) {
                // Set our own directory
                $composerCacheDirectory = $this->config()->get('composer_cache_directory');
                putenv('COMPOSER_HOME=' . $composerCacheDirectory);
            }
        }

        $basePath = $this->getBasePath();
        $composerJson = file_get_contents($basePath . '/composer.json');
        $composerLock = file_get_contents($basePath . '/composer.lock');

        if (!$composerJson || !$composerLock) {
            throw new Exception('composer.json or composer.lock could not be found!');
        }

        $this->setJson(json_decode($composerJson));
        $this->setLock(json_decode($composerLock));

        $this->extend('onAfterBuild');

        return $this;
    }

    /**
     * @param object $json
     * @return ComposerLoader
     */
    public function setJson($json)
    {
        $this->json = $json;
        return $this;
    }

    /**
     * @return object
     */
    public function getJson()
    {
        return $this->json;
    }

    /**
     * @param object $lock
     * @return ComposerLoader
     */
    public function setLock($lock)
    {
        $this->lock = $lock;
        return $this;
    }

    /**
     * @return object
     */
    public function getLock()
    {
        return $this->lock;
    }

    /**
     * Set the base path, if not specified the default will be `BASE_PATH`
     *
     * @param string $basePath
     * @return $this
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
        return $this;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath ?: BASE_PATH;
    }
}
