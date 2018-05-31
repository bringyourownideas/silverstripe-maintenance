<?php

namespace BringYourOwnIdeas\Maintenance\Util;

use Exception;
use SilverStripe\Core\Extensible;

/**
 * The composer loader class is responsible for dealing directly with composer.json and composer.lock files,
 * in terms of loading and parsing their contents.
 *
 * Any requirements for dealing with these files directly should use this class as a proxy.
 */
class ComposerLoader
{
    use Extensible;

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
        $basePath = $this->getBasePath();
        $composerJson = file_get_contents($basePath . '/composer.json');
        $composerLock = file_get_contents($basePath . '/composer.lock');

        if (!$composerJson || !$composerLock) {
            throw new Exception('composer.json or composer.lock could not be found!');
        }

        $this->setJson(json_decode($composerJson));
        $this->setLock(json_decode($composerLock));

        $this->extend('onAfterBuild');
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
