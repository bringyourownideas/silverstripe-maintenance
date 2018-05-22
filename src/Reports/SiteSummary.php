<?php

use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfo;

/**
 * A report listing all installed modules used in this site (from a cache).
 */
class SiteSummary extends SS_Report
{
    public function title()
    {
        return _t(__CLASS__ . '.TITLE', 'Installed modules');
    }

    public function description()
    {
        return _t(
            __CLASS__ . '.DESCRIPTION',
            <<<DESC
Provides information about what SilverStripe modules are installed,
giving an insight to project statistics such as how big the installation is,
what it would take to upgrade it, and what functionality is available
to both editors and users.
DESC
        );
    }

    public function sourceRecords($params)
    {
        $packageList = Package::get();
        $typeFilters = Config::inst()->get(UpdatePackageInfo::class, 'allowed_types');

        if (!empty($typeFilters)) {
            $packageList = $packageList->filter('Type', $typeFilters);
        }

        $this->extend('updateSourceRecords', $packageList);

        return $packageList;
    }

    public function columns()
    {
        return [
            'Summary' => 'Description',
            'Version' => 'Version',
        ];
    }

    /**
     * Add a button row, including link out to the SilverStripe addons repository, and export button
     *
     * {@inheritdoc}
     */
    public function getReportField()
    {
        Requirements::css('silverstripe-maintenance/css/sitesummary.css');
        $grid = parent::getReportField();
        $config = $grid->getConfig();
        $config->addComponents(
            Injector::inst()->create('GridFieldButtonRow', 'before'),
            Injector::inst()->create('GridFieldLinkButton', 'https://addons.silverstripe.org', 'buttons-before-left')
        )
            ->getComponentByType(GridFieldExportButton::class)
            ->setExportColumns(
                Package::create()->summaryFields()
            );
        return $grid;
    }
}
