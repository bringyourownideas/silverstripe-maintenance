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

    /**
     * @var array Injector configuration
     * @config
     */
    private static $dependencies = [
        'ComposerLoader' => '%$BringYourOwnIdeas\\Maintenance\\Util\\ComposerLoader'
    ];

    /**
     * @var ComposerLoader
     */
    protected $composerLoader;

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
     *
     * @param SS_HTTPRequest $request unused, can be null (must match signature of parent function).
     */
    public function run($request)
    {
        $packages = $this->getPackageInfo($this->getComposerLoader()->getLock()->packages);

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
        $this->extend('updatePackageInfo', $packageList);
        return $packageList;
    }
}
