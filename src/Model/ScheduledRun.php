<?php
/**
 * This is a simple object to hold different sets of settings for a scheduled run.
 *
 * The number of settings and details will be extended later on.
 */
class ScheduledRun extends DataObject
{
    /**
     * @var array
     */
    private static $db = array(
        'Type' => 'Varchar(128)',
    );

    /**
     * Triggered by ScheduledExecutionJob this will add another job for the actual work.
     *
     * This "forking" of another process simply happens to make sure the scheduled process doesn't
     * get delayed further and further because of the actual execution time.
     */
    public function onScheduledExecution()
    {
        $type = $this->getTypeInstance();
        if ($type && $type->hasMethod('getJobName')) {
            // find which job needs to be added
            $jobName = $type->getJobName();
            Injector::inst()->get(QueuedJobService::class)->queueJob(
                Injector::inst()->create($jobName)
            );
        }
    }

    /**
     * Gets a singleton instance of the job, referred to as "Type"
     *
     * @return DataObject|null
     */
    public function getTypeInstance()
    {
        return Injector::inst()->get($this->Type);
    }

    /**
     * delivers the title for the job queue
     *
     * @return string
     */
    public function getTitle()
    {
        return singleton($this->Type)->singular_name();
    }
}
