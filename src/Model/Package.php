<?php
/**
 * Describes a installed composer package version.
 *
 * This is a DataObject to cache the a package's information for later use.
 */
class Package extends DataObject
{
    private static $db = [
        'Name' => 'Varchar(255)',
        'Description' => 'Varchar(255)',
        'Version' => 'Varchar(255)',
        // 'Direct' => 'Boolean',
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
