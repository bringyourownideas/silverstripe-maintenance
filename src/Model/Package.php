<?php


/**
 * Describes an installed composer package version.
 */
class Package extends DataObject
{
    private static $db = [
        'Name' => 'Varchar(255)',
        'Description' => 'Varchar(255)',
        'Version' => 'Varchar(255)',
        'Type' => 'Varchar(255)',
        'Supported' => 'Boolean',
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Description' => 'Description',
        'Version' => 'Version',
    ];

    /**
     * @var array badge definitions - a keyed array in the format of [Title => Type] {@see getBadges()}
     */
    protected $badges = [];

    /**
     * Strips vendor and 'silverstripe-' prefix from Name property
     * @return string More easily digestable module name for human consumers
     */
    public function getTitle()
    {
        return preg_replace('#^[^/]+/(silverstripe-)?#', '', $this->Name);
    }

    /**
     * Returns HTML formatted summary of this object, uses a template to do this.
     * @return string
     */
    public function getSummary()
    {
        $summary = $this->renderWith('Package_summary');
        $this->extend('updateSummary', $summary);
        return $summary;
    }

    /**
     * Gives the summary template {@see getSummary()} a list of badges to show against a package
     *
     * badgeDefinitions are in the format [$title => $type] where:
     *   title is the unique string to display
     *   type is an optional class attribute (applied as a BEM modifier, by default)
     *
     * @return ArrayList
     */
    public function getBadges()
    {
        $badgeDefinitions = $this->badges;
        $badges = ArrayList::create();
        foreach ($badgeDefinitions as $title => $type) {
            $badges->push(ArrayData::create([
                'Title' => $title,
                'Type' => $type,
            ]));
        }

        $this->extend('updateBadges', $badgeDefinitions);
        return $badges;
    }

    /**
     * Adds a badge to the list of badges {@see $badges}
     *
     * @param string $title
     * @param string $type
     *
     * @return $this
     */
    public function addBadge($title, $type)
    {
        $this->badges[$title] = $type;
        return $this;
    }

    /**
     * Replaces the list of badges
     *
     * @param array $badges {@see $badges}
     *
     * @return $this
     */
    public function setBadges($badges)
    {
        $this->badges = $badges;
        return $this;
    }

    /**
     * requireDefaultRecords() gets abused to update the information on dev/build.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $task = Injector::inst()->create(UpdatePackageInfoTask::class);
        $task->run(null);
    }
}
