<?php

namespace BringYourOwnIdeas\Maintenance\Tasks;

use BuildTask;
use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use Injector;
use Package;
use SQLDelete;

/**
 * Parses a composer lock file in order to cache information about the installation.
 */
class UpdatePackageInfo extends BuildTask
{

    private $composerLoader;

    public function __construct($composerLoader = null)
    {
        parent::__construct();
        $this->composerLoader = $composerLoader ?: Injector::inst()->create(ComposerLoader::class);
    }

    /**
     * Fetch the composer loader
     *
     * @return ComposerLoader
     */
    public function getComposerLoader()
    {
        return $this->composerLoader;
    }

    /**
     * set composer loader - provided for use with Injector {@see Injector}
     *
     * @param ComposerLoader $composerLoader
     *
     * @return UpdatePackageInfo $this
     */
    public function setComposerLoader($composerLoader)
    {
        $this->composerLoader = $composerLoader;
        return $this;
    }

    public function getTitle()
    {
        return _t(__CLASS__ . '.TITLE', 'Refresh installed package info');
    }

    public function getDescription()
    {
        return _t(
            __CLASS__ . '.DESCRIPTION',
            'Repopulates installation summary, listing installed modules'.
                ' and information associated with each.'
        );
    }

    /**
     * Update database cached information about this site.
     */
    public function run($request)
    {
        $packages = $this->getPackageInfo($this->composerLoader->getLock()->packages);

        // Extensions to the process that add data may rely on external services.
        // There may be a communication issue between the site and the external service,
        // so if there are 'none' we should assume this is untrue and _not_ proceed
        // to remove everything. Stale information is better than no information.
        if ($packages) {
            // There is no onBeforeDelete for Package
            SQLDelete::create(Package::class)->execute();
            foreach ($packages as $package) {
                Package::create()->update($package)->write();
            }
        }
    }

    /**
     * Fetch information about the installed packages.
     *
     * @param array $packageList list of packages as objects, formatted as one finds in a composer.lock
     *
     * @return array indexed array of package information, represented as associative arrays.
     */
    public function getPackageInfo($packageList)
    {
        $formatInfo = function ($package) {
            // Convert object to array, with Capitalised keys
            $package = get_object_vars($package);
            return array_combine(
                array_map('ucfirst', array_keys($package)),
                $package
            );
        };

        $packageList = array_map($formatInfo, $packageList);
        $this->extend(__FUNCTION__, $packageList);
        return $packageList;
    }
}
