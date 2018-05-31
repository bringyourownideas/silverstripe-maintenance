<?php

use BringYourOwnIdeas\Maintenance\Forms\GridFieldDropdownFilter;
use BringYourOwnIdeas\Maintenance\Forms\GridFieldHtmlFragment;
use BringYourOwnIdeas\Maintenance\Forms\GridFieldLinkButton;
use BringYourOwnIdeas\Maintenance\Forms\GridFieldRefreshButton;

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
        $typeFilters = Config::inst()->get(UpdatePackageInfoTask::class, 'allowed_types');

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

        $grid->addExtraClass('site-summary');

        /** @var GridFieldExportButton $exportButton */
        $exportButton = $config->getComponentByType(GridFieldExportButton::class);
        $exportButton->setExportColumns(Package::create()->summaryFields());

        $versionHtml = ArrayData::create([
            'Title' => _t(__CLASS__ . '.VERSION', 'Version'),
            'Version' => $this->resolveCmsVersion(),
        ])->renderWith('SiteSummary_VersionHeader');

        /** @var GridFieldDropdownFilter $dropdownFilter */
        $dropdownFilter = Injector::inst()->create(
            GridFieldDropdownFilter::class,
            'addonFilter',
            'buttons-before-right',
            _t(__CLASS__ . '.ShowAllModules', 'Show all modules')
        );

        $dropdownFilter->addFilterOption(
            'supported',
            _t(__CLASS__ . '.FilterSupported', 'Supported modules'),
            ['Supported' => true]
        );
        $dropdownFilter->addFilterOption(
            'unsupported',
            _t(__CLASS__ . '.FilterUnsupported', 'Unsupported modules'),
            ['Supported' => false]
        );

        $this->extend('updateDropdownFilterOptions', $dropdownFilter);

        $config->addComponents(
            Injector::inst()->create(GridFieldButtonRow::class, 'before'),
            Injector::inst()->create(GridFieldRefreshButton::class, 'buttons-before-left'),
            Injector::inst()->create(
                GridFieldLinkButton::class,
                'https://addons.silverstripe.org',
                'buttons-before-left'
            ),
            $dropdownFilter,
            Injector::inst()->create(GridFieldHtmlFragment::class, 'header', $versionHtml)
        );

        // Re-order the paginator to ensure it counts correctly.
        $paginator = $config->getComponentByType(GridFieldPaginator::class);
        $config->removeComponent($paginator);
        $config->addComponent($paginator);

        return $grid;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $alerts = $this->getAlerts();
        if ($alerts) {
            $summaryInfo = '<div class="site-summary message warning">' . implode("\n", $alerts) . '</div>';
            $fields->unshift(LiteralField::create('AlertSummary', $summaryInfo));
        }
        return $fields;
    }

    /**
     * Return a list of alerts to display in a message box above the report
     * A combination of free text fields - combined alerts as opposed to a message box per alert.
     *
     * @return array
     */
    protected function getAlerts()
    {
        $alerts = [];
        $this->extend('updateAlerts', $alerts);
        return $alerts;
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
