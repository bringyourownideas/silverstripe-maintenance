<?php

namespace BringYourOwnIdeas\Maintenance\Forms;

use ArrayData;
use CheckForUpdatesJob;
use Convert;
use GridField;
use GridField_ActionProvider;
use GridField_FormAction;
use GridField_HTMLProvider;
use GridField_URLHandler;
use Injector;
use QueuedJob;
use QueuedJobDescriptor;
use QueuedJobService;
use Requirements;

/**
 * Adds a "Refresh" button to the bottom or top of a GridField.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldRefreshButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
{
    private static $dependencies = [
        'QueuedJobService' => '%$QueuedJobService'
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
        Requirements::javascript('silverstripe-maintenance/javascript/CheckForUpdates.js');

        $button = GridField_FormAction::create(
            $gridField,
            'refresh',
            _t('GridFieldRefreshButton.REFRESH', 'Check for updates'),
            'refresh',
            null
        );

        $button->setAttribute('data-icon', 'arrow-circle-double');
        $button->setAttribute('data-check', $gridField->Link('check'));
        $button->setAttribute(
            'data-message',
            _t(
                'GridFieldRefreshButton.MESSAGE',
                'Updating this list may take 2-3 minutes. You can continue to use the CMS while we run the update.'
            )
        );

        if ($this->hasActiveJob()) {
            $button->setTitle(_t('GridFieldRefreshButton.UPDATE', 'Updating...'));
            $button->setDisabled(true);
        }

        return [
            $this->targetFragment => ArrayData::create([
                'Button' => $button->Field()
            ])->renderWith('GridFieldRefreshButton')
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
     * @see hasActiveJob
     * @return string JSON boolean
     */
    public function handleCheck()
    {
        $isRunning = $this->hasActiveJob();
        return Convert::raw2json($isRunning);
    }

    /**
     * Check the queue for refresh jobs that are not 'done'
     * in one manner or another (e.g. stalled or cancelled)
     *
     * @return boolean
     */
    public function hasActiveJob()
    {
        // We care about any queued job in the immediate queue, or any queue if the job is already running
        /** @var QueuedJobDescriptor $job */

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
        if ($this->hasActiveJob()) {
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
