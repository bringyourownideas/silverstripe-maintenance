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
    ];

    private static $summary_fields = [
        'Name',
        'Version',
    ];

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
