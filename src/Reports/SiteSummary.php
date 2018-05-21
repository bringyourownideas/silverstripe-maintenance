<?php

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
        return Package::get()->filter('Type:StartsWith', 'silverstripe');
    }

    public function columns()
    {
        return singleton(Package::class)->summaryFields();
    }
}
