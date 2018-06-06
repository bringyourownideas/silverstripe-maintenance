<?php

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
        return _t(__CLASS__ . '.TITLE', 'Check for updates');
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

    /**
     * @inheritdoc
     */
    public function afterComplete()
    {
        // Queue a new job to run in the future
        $injector = Injector::inst();
        $queuedJobService = $injector->get(QueuedJobService::class);

        $startAfter = new DateTime(SS_Datetime::now()->getValue());
        $startAfter->modify('+1 day');
        $queuedJobService->queueJob(
            $injector->create(CheckForUpdatesJob::class),
            $startAfter->format(DateTime::ISO8601)
        );
    }
}
