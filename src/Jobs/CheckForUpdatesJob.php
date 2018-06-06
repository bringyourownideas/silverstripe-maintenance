<?php

namespace BringYourOwnIdeas\Maintenance\Jobs;

use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfoTask;
use DateTime;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\FieldType\DBDatetime;
use Symbiote\QueuedJobs\Services\QueuedJob;
use SilverStripe\Core\Injector\Injector;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;

/**
 * Refresh report job. Runs as a queued job.
 *
 */
class CheckForUpdatesJob extends AbstractQueuedJob implements QueuedJob
{
    /**
     * Whether or not to reschedule a new job when one completes
     *
     * @config
     * @var bool
     */
    private static $reschedule = true;

    /**
     * The PHP time difference to reschedule a job for after one completes
     *
     * @config
     * @var string
     */
    private static $reschedule_delay = '+1 day';

    /**
     * Define the title
     *
     * @return string
     */
    public function getTitle()
    {
        return _t(__CLASS__ . '.TITLE', 'Check for updates to installed modules');
    }

    /**
     * Define the type.
     */
    public function getJobType()
    {
        $this->totalSteps = 1;
        return QueuedJob::QUEUED;
    }

    /**
     * Processes the task as a job
     */
    public function process()
    {
        // Run the UpdatePackageInfo task
        $updateTask = Injector::inst()->create(UpdatePackageInfoTask::class);
        $updateTask->run(null);

        // mark job as completed
        $this->isComplete = true;
    }

    /**
     * @inheritdoc
     */
    public function afterComplete()
    {
        // Gather config options
        $reschedule = Config::inst()->get(__CLASS__, 'reschedule');
        $rescheduleDelay = Config::inst()->get(__CLASS__, 'reschedule_delay');

        if ($reschedule === false) {
            return;
        }

        // Queue a new job to run in the future
        $injector = Injector::inst();
        $queuedJobService = $injector->get(QueuedJobService::class);

        $startAfter = new DateTime(DBDatetime::now()->getValue());
        $startAfter->modify($rescheduleDelay);
        $queuedJobService->queueJob(
            $injector->create(CheckForUpdatesJob::class),
            $startAfter->format(DateTime::ISO8601)
        );
    }
}
