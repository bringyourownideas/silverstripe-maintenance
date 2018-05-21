<?php

use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfo;

/**
 * Describes an installed composer package version.
 */
class Package extends DataObject
{
    private static $db = [
        'Name' => 'Varchar(255)',
        'Description' => 'Varchar(255)',
        'Version' => 'Varchar(255)',
        "Type" => 'Varchar(255)',
    ];

    private static $summary_fields = [
        'Summary',
        'Version',
    ];

    public function getTitle()
    {
        $niceName = preg_replace('#^[^/]+/(silverstripe-)?#', '', $this->Name);
        $niceName = explode('-', $niceName);
        $niceName = implode(' ', $niceName);
        $niceName = ucwords($niceName);
        return $niceName;
    }

    public function getSummary()
    {
        return $this->renderWith('Package_summary');
    }

    /**
     * requireDefaultRecords() gets abused to update the information on dev/build.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $task = new UpdatePackageInfo;
        $task->run(null);
    }
}
