<?php

namespace BringYourOwnIdeas\Maintenance\Model;

use BringYourOwnIdeas\Maintenance\Jobs\CheckForUpdatesJob;
use SilverStripe\Core\Injector\Injector;
use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfoTask;
use SilverStripe\ORM\DataObject;
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
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Description' => 'Description',
        'Version' => 'Version',
    ];

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
        return $this->renderWith('Package_summary');
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
