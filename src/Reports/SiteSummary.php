<?php

namespace BringYourOwnIdeas\Maintenance\Reports;

use BringYourOwnIdeas\Maintenance\Forms\GridFieldDropdownFilter;
use BringYourOwnIdeas\Maintenance\Forms\GridFieldHtmlFragment;
use BringYourOwnIdeas\Maintenance\Forms\GridFieldLinkButton;
use BringYourOwnIdeas\Maintenance\Forms\GridFieldRefreshButton;
use BringYourOwnIdeas\Maintenance\Model\Package;
use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfoTask;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Reports\Report;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

/**
 * A report listing all installed modules used in this site (from a cache).
 */
class SiteSummary extends Report
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
        Requirements::css('bringyourownideas/silverstripe-maintenance: client/dist/styles/bundle.css');
        Requirements::javascript('bringyourownideas/silverstripe-maintenance: client/dist/js/bundle.js');

        /** @var GridField $grid */
        $grid = parent::getReportField();

        /** @var GridFieldConfig $config */
        $config = $grid->getConfig();

        $grid->addExtraClass('site-summary');

        $summaryFields = Package::create()->summaryFields();
        /** @var GridFieldExportButton $exportButton */
        $exportButton = $config->getComponentByType(GridFieldExportButton::class);
        $exportButton->setExportColumns($summaryFields);
        /** @var GridFieldPrintButton $printButton */
        $printButton = $config->getComponentByType(GridFieldPrintButton::class);
        $printButton->setPrintColumns($summaryFields);

        $versionHtml = ArrayData::create([
            'Title' => _t(__CLASS__ . '.VERSION', 'Version'),
            'Version' => $this->resolveCmsVersion(),
            'LastUpdated' => $this->getLastUpdated(),
        ])->renderWith(__CLASS__ . '/VersionHeader');

        $config->addComponents(
            Injector::inst()->create(GridFieldRefreshButton::class, 'buttons-before-left'),
            Injector::inst()->create(
                GridFieldLinkButton::class,
                'https://addons.silverstripe.org',
                _t(__CLASS__ . '.LINK_TO_ADDONS', 'Explore Addons'),
                'buttons-before-left'
            ),
            $this->getDropdownFilter(),
            $this->getInfoLink(),
            Injector::inst()->create(GridFieldHtmlFragment::class, 'header', $versionHtml)
        );

        // Re-order the paginator to ensure it counts correctly, and reorder the buttons
        $paginator = $config->getComponentByType(GridFieldPaginator::class);
        $config->removeComponent($paginator)->addComponent($paginator);

        $exportButton = $config->getComponentByType(GridFieldExportButton::class);
        $config->removeComponent($exportButton)->addComponent($exportButton);

        $printButton = $config->getComponentByType(GridFieldPrintButton::class);
        $config->removeComponentsByType($printButton)->addComponent($printButton);

        return $grid;
    }

    /**
     * Returns a dropdown filter with user configurable options in it
     *
     * @return GridFieldDropdownFilter
     */
    protected function getDropdownFilter()
    {
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

        return $dropdownFilter;
    }

    /**
     * Returns a link to more information on this module on the addons site
     *
     * @return GridFieldHtmlFragment
     */
    protected function getInfoLink()
    {
        return Injector::inst()->create(
            GridFieldHtmlFragment::class,
            'buttons-before-right',
            DBField::create_field('HTMLFragment', ArrayData::create([
                'Link' => 'https://addons.silverstripe.org/add-ons/bringyourownideas/silverstripe-maintenance',
                'Label' => _t(__CLASS__ . '.MORE_INFORMATION', 'More information'),
            ])->renderWith(__CLASS__ . '/MoreInformationLink'))
        );
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $alerts = $this->getAlerts();
        if ($alerts) {
            $summaryInfo = '<div class="site-alert alert alert-warning">' . implode("\n", $alerts) . '</div>';
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

    /**
     * Get the "last updated" date for the report. This is based on the modified date of any of the records, since
     * they are regenerated when the report is generated.
     *
     * @return string
     */
    public function getLastUpdated()
    {
        $packages = Package::get()->limit(1);
        if (!$packages->count()) {
            return '';
        }
        /** @var DBDatetime $datetime */
        $datetime = $packages->first()->dbObject('LastEdited');
        return $datetime->Date() . ' ' . $datetime->Time12();
    }
}
