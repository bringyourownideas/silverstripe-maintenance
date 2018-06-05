<?php

namespace BringYourOwnIdeas\Maintenance\Jobs;

use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfoTask;
use Symbiote\QueuedJobs\Services\QueuedJob;
use SilverStripe\Core\Injector\Injector;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;

/**
 * Refresh report job. Runs as a queued job.
 *
 */
class CheckForUpdatesJob extends AbstractQueuedJob implements QueuedJob
{
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
     * Create the instance of the task
     */
    public function setup()
    {
        $this->task = Injector::inst()->create(CheckForUpdatesJob::class);
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
}
