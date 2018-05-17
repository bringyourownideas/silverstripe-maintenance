<?php

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;

class UpdatePackageInfo extends BuildTask
{
    public function getTitle() {
        return _t(__CLASS__ . '.TITLE', 'Refresh installed package info');
    }

    public function getDescription() {
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
        $loader = new ComposerLoader;

        $packages = $this->getPackageInfo($loader->getLock()->packages);

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
