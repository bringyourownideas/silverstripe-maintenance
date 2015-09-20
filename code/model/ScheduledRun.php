<?php
/**
 * This is a simple object to holder different sets of settings for a scheduled run.
 *
 * The number of settings and details will be extended later on.
 */
class ScheduledRun extends DataObject {
	/**
	 * @var array
	 */
	private static $db = array(
		'Type' => 'Varchar(128)',
	);

	/**
	 * Triggered by ScheduledExecutionJob this will add another job for the actual work.
	 * This "forking" of another process simply happens to make sure the scheduled process doesn't
	 * get delayed further and further because of the actual execution time.
	 */
	public function onScheduledExecution() {
		// only attempt to execute this if the class exists
		if (class_exists($this->Type)) {
			// find which job needs to be added
			$job = singleton($this->Type)->jobName;
			singleton('QueuedJobService')->queueJob(new $job);
		}
	}

	/**
	 * delivers the title for the job queue
	 *
	 * @return string
	 */
	public function getTitle() {
		return singleton($this->Type)->singular_name();
	}
}
