<?php

namespace BringYourOwnIdeas\Maintenance\Forms;

use BringYourOwnIdeas\Maintenance\Reports\SiteSummary;
use SilverStripe\ORM\DataList;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Symbiote\QueuedJobs\Services\QueuedJob;
use BringYourOwnIdeas\Maintenance\Jobs\CheckForUpdatesJob;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_URLHandler;

/**
 * Adds a "Refresh" button to the bottom or top of a GridField.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldRefreshButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
{
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
        /** @var QueuedJobDescriptor $job */
        $job = Injector::inst()
            ->get(QueuedJobService::class)
            ->getJobList(QueuedJob::QUEUED)
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

        return $job->exists();
    }

    /**
     * Handle the refresh, for both the action button and the URL
     */
    public function handleRefresh()
    {
        if (!$this->hasPendingJob()) {
            $injector = Injector::inst();
            $injector->get(QueuedJobService::class)->queueJob($injector->create(CheckForUpdatesJob::class));
        }
    }
}
