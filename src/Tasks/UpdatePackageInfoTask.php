<?php

namespace BringYourOwnIdeas\Maintenance\Tasks;

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use BringYourOwnIdeas\Maintenance\Util\SupportedAddonsLoader;
use RuntimeException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\Queries\SQLDelete;
use BringYourOwnIdeas\Maintenance\Model\Package;
use SilverStripe\Dev\BuildTask;

/**
 * Parses a composer lock file in order to cache information about the installation.
 */
class UpdatePackageInfoTask extends BuildTask
{

    /**
     * @var array Injector configuration
     * @config
     */
    private static $dependencies = [
        'ComposerLoader' => '%$BringYourOwnIdeas\\Maintenance\\Util\\ComposerLoader',
        'SupportedAddonsLoader' => '%$BringYourOwnIdeas\\Maintenance\\Util\\SupportedAddonsLoader',
    ];

    /**
     * The "types" of composer libraries that will be processed. Anything without these types will be ignored.
     *
     * @config
     * @var array
     */
    private static $allowed_types = [
        'silverstripe-module',
        'silverstripe-vendormodule',
    ];

    /**
     * @var ComposerLoader
     */
    protected $composerLoader;

    /**
     * @var SupportedAddonsLoader
     */
    protected $supportedAddonsLoader;

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
     * @return UpdatePackageInfoTask $this
     */
    public function setComposerLoader($composerLoader)
    {
        $this->composerLoader = $composerLoader;
        return $this;
    }

    /**
     * @return SupportedAddonsLoader
     */
    public function getSupportedAddonsLoader()
    {
        return $this->supportedAddonsLoader;
    }

    /**
     * @param SupportedAddonsLoader $supportedAddonsLoader
     * @return $this
     */
    public function setSupportedAddonsLoader(SupportedAddonsLoader $supportedAddonsLoader)
    {
        $this->supportedAddonsLoader = $supportedAddonsLoader;
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
     * @param HTTPRequest $request unused, can be null (must match signature of parent function).
     */
    public function run($request)
    {
        $composerLock = $this->getComposerLoader()->getLock();
        $rawPackages = array_merge($composerLock->packages, (array) $composerLock->{'packages-dev'});
        $packages = $this->getPackageInfo($rawPackages);

        $supportedPackages = $this->getSupportedPackages();

        // Extensions to the process that add data may rely on external services.
        // There may be a communication issue between the site and the external service,
        // so if there are 'none' we should assume this is untrue and _not_ proceed
        // to remove everything. Stale information is better than no information.
        if ($packages) {
            // There is no onBeforeDelete for Package
            SQLDelete::create('"Package"')->execute();
            foreach ($packages as $package) {
                if (is_array($supportedPackages)) {
                    $package['Supported'] = in_array($package['Name'], $supportedPackages);
                }
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

    /**
     * Return an array of supported modules as fetched from addons.silverstripe.org. Outputs a message and returns null
     * if an error occurs
     *
     * @return null|array
     */
    public function getSupportedPackages()
    {
        try {
            return $this->getSupportedAddonsLoader()->getAddonNames() ?: [];
        } catch (RuntimeException $exception) {
            echo $exception->getMessage() . PHP_EOL;
        }

        return null;
    }
}
