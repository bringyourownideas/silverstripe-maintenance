<?php

namespace BringYourOwnIdeas\Maintenance\Model;

use BringYourOwnIdeas\Maintenance\Jobs\CheckForUpdatesJob;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;
use Symbiote\QueuedJobs\Services\QueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;

/**
 * Describes an installed composer package version.
 */
class Package extends DataObject
{
    private static $table_name = 'Package';

    private static $db = [
        'Name' => 'Varchar(255)',
        'Description' => 'Varchar(255)',
        'Version' => 'Varchar(255)',
        'Type' => 'Varchar(255)',
        'Supported' => 'Boolean',
        'Rating' => 'Int'
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
        $summary = $this->renderWith(__CLASS__ . '/Summary');
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
     * @param array $extraBadges allow a user to include extra badges at call time
     *
     * @return ArrayList
     */
    public function getBadges($extraBadges = [])
    {
        $badgeDefinitions = array_merge($this->badges, $extraBadges);
        $badges = ArrayList::create();
        foreach ($badgeDefinitions as $title => $type) {
            $badges->push(ArrayData::create([
                'Title' => $title,
                'Type' => $type,
            ]));
        }

        $this->extend('updateBadges', $badges);
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
     * Returns a JSON data schema for the frontend React components to use
     *
     * @return array
     */
    public function getDataSchema()
    {
        $schema = [
            'description' => $this->Description,
            'link' => 'https://addons.silverstripe.org/add-ons/' . $this->Name,
            'linkTitle' => _t(
                __CLASS__ . '.ADDONS_LINK_TITLE',
                'View {package} on addons.silverstripe.org',
                ['package' => $this->Title]
            ),
            'rating'=> (int) $this->Rating
        ];

        $this->extend('updateDataSchema', $schema);

        return $schema;
    }

    /**
     * Queue up a job to check for updates to packages if there isn't a pending job in the queue already
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        $pendingJobs = QueuedJobDescriptor::get()->filter([
            'Implementation' => CheckForUpdatesJob::class,
            'JobStatus' => [
                QueuedJob::STATUS_NEW,
                QueuedJob::STATUS_INIT,
                QueuedJob::STATUS_RUN,
            ],
        ]);
        if ($pendingJobs->count()) {
            return;
        }

        /** @var QueuedJobService $jobService */
        $jobService = QueuedJobService::singleton();
        $jobService->queueJob(Injector::inst()->create(CheckForUpdatesJob::class));
    }
}
