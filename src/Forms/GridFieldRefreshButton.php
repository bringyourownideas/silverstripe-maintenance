<?php

namespace BringYourOwnIdeas\Maintenance\Forms;

use BringYourOwnIdeas\Maintenance\Jobs\CheckForUpdatesJob;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;
use Symbiote\QueuedJobs\Services\QueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;

/**
 * Adds a "Refresh" button to the bottom or top of a GridField.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldRefreshButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
{
    private static $dependencies = [
        'QueuedJobService' => '%$' . QueuedJobService::class,
    ];

    /**
     * @var array
     * @config
     */
    private static $allowed_actions = ["check"];

    /**
     * Fragment to write the button to.
     * @var string
     */
    protected $targetFragment;

    /**
     * @var QueuedJobService
     */
    protected $queuedJobService;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct($targetFragment)
    {
        $this->targetFragment = $targetFragment;
    }

    /**
     * @param GridField $gridField
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        Requirements::javascript('bringyourownideas/silverstripe-maintenance: client/dist/js/bundle.js');

        $button = GridField_FormAction::create(
            $gridField,
            'refresh',
            _t(__CLASS__ . '.REFRESH', 'Check for updates'),
            'refresh',
            null
        );

        $button->addExtraClass('btn btn-primary font-icon-sync');

        $button->setAttribute('data-check', $gridField->Link('check'));
        $button->setAttribute(
            'data-message',
            _t(
                __CLASS__ . '.MESSAGE',
                'Updating this list may take 2-3 minutes. You can continue to use the CMS while we run the update.'
            )
        );

        if ($this->hasPendingJob()) {
            $button->setTitle(_t(__CLASS__ . '.UPDATE', 'Updating...'));
            $button->setDisabled(true);
        }

        return [
            $this->targetFragment => ArrayData::create(['Button' => $button->Field()])
                ->renderWith(__CLASS__)
        ];
    }

    /**
     * Refresh is an action button.
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getActions($gridField)
    {
        return ['refresh'];
    }

    /**
     * Handle the refresh action.
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param array $arguments
     * @param array $data
     *
     * @return null
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == 'refresh') {
            return $this->handleRefresh($gridField);
        }
    }

    /**
     * Refresh is accessible via the url
     *
     * @param GridField $gridField
     * @return array
     */
    public function getURLHandlers($gridField)
    {
        return [
            'check' => 'handleCheck'
        ];
    }

    /**
     * @see hasPendingJob
     * @return string JSON encoded value for whether there is a job pending or in process to update the report
     */
    public function handleCheck()
    {
        $isRunning = $this->hasPendingJob();
        return Convert::raw2json($isRunning);
    }

    /**
     * Check the queue for refresh jobs that are not 'done'
     * in one manner or another (e.g. stalled or cancelled)
     *
     * @return boolean
     */
    public function hasPendingJob()
    {
        // We care about any queued job in the immediate queue, or any queue if the job is already running
        /** @var QueuedJobDescriptor $immediateJob */
        $immediateJob = $this->getQueuedJobService()
            ->getJobList(QueuedJob::IMMEDIATE)
            ->filter([
                'Implementation' => CheckForUpdatesJob::class
            ])
            ->exclude([
                'JobStatus' => [
                    QueuedJob::STATUS_COMPLETE,
                    QueuedJob::STATUS_CANCELLED,
                    QueuedJob::STATUS_BROKEN
                ]
            ]);

        /** @var QueuedJobDescriptor $runningJob */
        $runningJob = QueuedJobDescriptor::get()
            ->filter([
                'Implementation' => CheckForUpdatesJob::class,
                'JobStatus' => QueuedJob::STATUS_RUN,
            ]);

        return $immediateJob->exists() || $runningJob->exists();
    }

    /**
     * Handle the refresh, for both the action button and the URL
     */
    public function handleRefresh()
    {
        if ($this->hasPendingJob()) {
            return;
        }

        // Queue the job in the immediate queue
        $job = Injector::inst()->create(CheckForUpdatesJob::class);
        $jobDescriptorId = $this->getQueuedJobService()->queueJob($job, null, null, QueuedJob::IMMEDIATE);

        // Check the job descriptor on the queue
        $jobDescriptor = QueuedJobDescriptor::get()->filter('ID', $jobDescriptorId)->first();

        // If the job is not immediate, change it to immediate and reschedule it to occur immediately
        if ($jobDescriptor->JobType !== QueuedJob::IMMEDIATE) {
            $jobDescriptor->JobType = QueuedJob::IMMEDIATE;
            $jobDescriptor->StartAfter = null;
            $jobDescriptor->write();
        }
    }

    /**
     * @return QueuedJobService
     */
    public function getQueuedJobService()
    {
        return $this->queuedJobService;
    }

    /**
     * @param QueuedJobService $queuedJobService
     * @return $this
     */
    public function setQueuedJobService(QueuedJobService $queuedJobService)
    {
        $this->queuedJobService = $queuedJobService;
        return $this;
    }
}
