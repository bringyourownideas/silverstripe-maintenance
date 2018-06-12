<?php

namespace BringYourOwnIdeas\Maintenance\Tasks;

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use BringYourOwnIdeas\Maintenance\Util\ModuleHealthLoader;
use BringYourOwnIdeas\Maintenance\Util\SupportedAddonsLoader;
use RuntimeException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\ORM\DataObjectSchema;
use BringYourOwnIdeas\Maintenance\Model\Package;
use SilverStripe\Dev\BuildTask;

/**
 * Parses a composer lock file in order to cache information about the installation.
 */
class UpdatePackageInfoTask extends BuildTask
{
    /**
     * {@inheritDoc}
     * @var string
     */
    private static $segment = 'UpdatePackageInfoTask';

    /**
     * A custom memory limit to set for this to increase to (or do nothing if the memory is already set high enough)
     *
     * @config
     * @var string
     */
    private static $memory_limit = '256m';

    /**
     * @var array Injector configuration
     * @config
     */
    private static $dependencies = [
        'ComposerLoader' => '%$BringYourOwnIdeas\\Maintenance\\Util\\ComposerLoader',
        'SupportedAddonsLoader' => '%$BringYourOwnIdeas\\Maintenance\\Util\\SupportedAddonsLoader',
        'ModuleHealthLoader' => '%$BringYourOwnIdeas\\Maintenance\\Util\\ModuleHealthLoader',
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
     * @var ModuleHealthLoader
     */
    protected $moduleHealthLoader;

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

    /**
     * @return ModuleHealthLoader
     */
    public function getModuleHealthLoader()
    {
        return $this->moduleHealthLoader;
    }

    /**
     * @param ModuleHealthLoader $moduleHealthLoader
     * @return $this
     */
    public function setModuleHealthLoader(ModuleHealthLoader $moduleHealthLoader)
    {
        $this->moduleHealthLoader = $moduleHealthLoader;
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
        // Loading packages and all their updates can be quite memory intensive.
        $memoryLimit = $this->config()->get('memory_limit');
        if ($memoryLimit) {
            if (Environment::getMemoryLimitMax() < Convert::memstring2bytes($memoryLimit)) {
                Environment::setMemoryLimitMax($memoryLimit);
            }
            Environment::increaseMemoryLimitTo($memoryLimit);
        }

        $composerLock = $this->getComposerLoader()->getLock();
        $rawPackages = array_merge($composerLock->packages, (array) $composerLock->{'packages-dev'});
        $packages = $this->getPackageInfo($rawPackages);

        // Get "name" from $packages and put into an array
        $moduleNames = array_column($packages, 'Name');

        $supportedPackages = $this->getSupportedPackages();
        $moduleHealthInfo = $this->getHealthIndicator($moduleNames);

        // Extensions to the process that add data may rely on external services.
        // There may be a communication issue between the site and the external service,
        // so if there are 'none' we should assume this is untrue and _not_ proceed
        // to remove everything. Stale information is better than no information.
        if ($packages) {
            // There is no onBeforeDelete for Package
            $table = DataObjectSchema::create()->tableName(Package::class);
            SQLDelete::create("\"$table\"")->execute();
            foreach ($packages as $package) {
                $packageName = $package['Name'];
                if (is_array($supportedPackages)) {
                    $package['Supported'] = in_array($packageName, $supportedPackages);
                }
                if (is_array($moduleHealthInfo) && isset($moduleHealthInfo[$packageName])) {
                    $package['Rating'] = $moduleHealthInfo[$packageName];
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

    /**
     * Return an array of module health information as fetched from addons.silverstripe.org. Outputs a message and
     * returns null if an error occurs
     *
     * @param string[] $moduleNames
     * @return null|array
     */
    public function getHealthIndicator(array $moduleNames)
    {
        try {
            return $this->getModuleHealthLoader()->setModuleNames($moduleNames)->getModuleHealthInfo() ?: [];
        } catch (RuntimeException $exception) {
            echo $exception->getMessage() . PHP_EOL;
        }

        return null;
    }
}
