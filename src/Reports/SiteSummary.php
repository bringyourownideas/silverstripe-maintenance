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

    public function sourceRecords()
    {
        $packageList = Package::get();
        $typeFilters = Config::inst()->get(UpdatePackageInfo::class, 'allowed_types');

        if (!empty($typeFilters)) {
            $packageList = $packageList->filter('Type', $typeFilters);
        }

        $this->extend('updateSourceRecords', $packageList);

        return $packageList;
    }

    /**
     * Provide column selection and formatting rules for the CMS report. You can extend data columns by extending
     * {@link Package::summary_fields}, or you can extend this method to adjust the formatting rules, or to provide
     * composite fields (such as Summary below) for the CMS report but not the CSV export.
     *
     * {@inheritDoc}
     */
    public function columns()
    {
        $columns = Package::create()->summaryFields();

        // Remove the default Title and Description and create Summary as a composite of both for the CMS report only
        unset($columns['Title'], $columns['Description']);
        $columns = ['Summary' => 'Summary'] + $columns;

        $this->extend('updateColumns', $columns);

        return $columns;
    }

    /**
     * Add a button row, including link out to the SilverStripe addons repository, and export button
     *
     * {@inheritdoc}
     */
    public function getReportField()
    {
        Requirements::css('silverstripe-maintenance/css/sitesummary.css');

        /** @var GridField $grid */
        $grid = parent::getReportField();

        /** @var GridFieldConfig $config */
        $config = $grid->getConfig();

        /** @var GridFieldExportButton $exportButton */
        $exportButton = $config->getComponentByType(GridFieldExportButton::class);
        $exportButton->setExportColumns(Package::create()->summaryFields());

        $versionHtml = ArrayData::create([
            'Title' => _t(__CLASS__ . '.VERSION', 'Version'),
            'Version' => $this->resolveCmsVersion(),
        ])->renderWith('SiteSummary_VersionHeader');

        $config->addComponents(
            Injector::inst()->create(GridFieldButtonRow::class, 'before'),
            Injector::inst()->create(
                GridFieldLinkButton::class,
                'https://addons.silverstripe.org',
                'buttons-before-left'
            ),
            Injector::inst()->create(GridFieldHtmlFragment::class, 'header', $versionHtml)
        );

        return $grid;
    }

    /**
     * Extract CMS and Framework version details from the records in the report
     *
     * @return string
     */
    protected function resolveCmsVersion()
    {
        $versionModules = [
            'silverstripe/framework' => 'Framework',
            'silverstripe/cms' => 'CMS',
        ];
        $this->extend('updateVersionModules', $versionModules);

        $records = $this->sourceRecords()->filter('Name', array_keys($versionModules));
        $versionParts = [];

        foreach ($versionModules as $name => $label) {
            $record = $records->find('Name', $name);
            if (!$record) {
                $version = _t(__CLASS__.'.VersionUnknown', 'Unknown');
            } else {
                $version = $record->Version;
            }

            $versionParts[] = "$label $version";
        }

        return implode(', ', $versionParts);
    }
}
