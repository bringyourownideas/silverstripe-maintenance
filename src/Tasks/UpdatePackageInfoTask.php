<?php

namespace BringYourOwnIdeas\Maintenance\Tasks;

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use RuntimeException;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\ORM\DataObjectSchema;
use BringYourOwnIdeas\Maintenance\Model\Package;
use SilverStripe\Core\Manifest\VersionProvider;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\SupportedModules\BranchLogic;
use SilverStripe\SupportedModules\MetaData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Parses a composer lock file in order to cache information about the installation.
 */
class UpdatePackageInfoTask extends BuildTask
{
    protected static string $commandName = 'UpdatePackageInfoTask';

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

    public function getTitle(): string
    {
        return _t(__CLASS__ . '.TITLE', 'Refresh installed package info');
    }

    public static function getDescription(): string
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
    protected function execute(InputInterface $input, PolyOutput $output): int
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
        $supportedPackages = $this->getSupportedPackages($output);

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
                    $package['Supported'] = in_array($packageName, $supportedPackages ?? []);
                }
                Package::create()->update($package)->write();
            }
        }

        return Command::SUCCESS;
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
                array_map('ucfirst', array_keys($package ?? [])),
                $package ?? []
            );
        };

        $packageList = array_map($formatInfo, $packageList ?? []);
        $this->extend('updatePackageInfo', $packageList);
        return $packageList;
    }

    /**
     * Return an array of supported modules as fetched from silverstripe/supported-modules.
     * Outputs a message and returns null if an error occurs
     */
    public function getSupportedPackages(PolyOutput $output): ?array
    {
        try {
            $repos = MetaData::getAllRepositoryMetaData()[MetaData::CATEGORY_SUPPORTED];
            $version = VersionProvider::singleton()->getModuleVersion('silverstripe/framework');
            preg_match('/^([0-9]+)/', $version, $matches);
            $cmsMajor = BranchLogic::getCmsMajor(
                MetaData::getMetaDataForRepository('silverstripe/silverstripe-framework'),
                $matches[1] ?? ''
            );
            return array_filter(array_map(
                fn(array $item) => isset($item['majorVersionMapping'][$cmsMajor]) ? $item['packagist'] : null,
                $repos
            ));
        } catch (RuntimeException $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</>');
        }

        return null;
    }
}
