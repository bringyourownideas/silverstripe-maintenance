<?php
/**
 * Model Admin to display the known security vulnerabilities and available updates.
 */
class MaintenanceAdmin extends ModelAdmin
{
    /**
     * @var string
     */
    private static $url_segment = 'maintenance';

    /**
     * @var string
     */
    private static $menu_title = 'Maintenance';

    /**
     * hide the importer option
     *
     * @var boolean
     */
    public $showImportForm = false;

    /**
     * list of data object which should be managed - if they exist
     *
     * @var array
     */
    protected $managedModels = array(
        'ComposerSecurityVulnerability',
        'ComposerUpdate',
        'ComposerPackageVersion',
    );

    /**
     * Check which classes should be managed using this model admin - some may not exist
     *
     * @return array
     */
    public function getManagedModels()
    {
        $models = array();

        foreach ($this->managedModels as $dataObject) {
            if (class_exists($dataObject)) {
                $models[$dataObject] = array('title' => singleton($dataObject)->singular_name());
            }
        }

        return $models;
    }

    /**
     * adjust the gridfield: remove all options to change content
     *
     * @param int $id
     * @param FieldList $fields
     * @return CMSForm
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        // remove all the edit options
        $field = $form->Fields()->fieldByName('ComposerSecurityVulnerability');
        if ($field) {
            $field->setConfig(new GridFieldConfig_Base());
        }
        if ($field) {
            $field->getConfig()->addComponent(new GridFieldExternalLink());
        }

        // allow scheduled runs of the composer security vulnerability check if this is a admin and queuedjobs installed
        if ($field) {
            $this->addSimpleScheduleForm($form, 'ComposerSecurityVulnerability');
        }

        // add the available composer updates, if this package is installed
        $field = $form->Fields()->fieldByName('ComposerPackageVersion');
        if ($field) {
            $field->setConfig(new GridFieldConfig_Base());
        }

        // add the available composer updates, if this package is installed
        $field = $form->Fields()->fieldByName('ComposerUpdate');
        if ($field) {
            $field->setConfig(new GridFieldConfig_Base());
        }

        // allow scheduled runs of the composer security vulnerability check if this is a admin and queuedjobs installed
        if ($field) {
            $this->addSimpleScheduleForm($form, 'ComposerUpdate');
        }

        return $form;
    }

    /**
     * adds the simple schedule form for the queuedjobs
     *
     * @param Form $form
     * @param string $type
     */
    protected function addSimpleScheduleForm(Form $form, $type)
    {
        if (Permission::check('ADMIN') && class_exists('AbstractQueuedJob')) {
            // add a message
            $form->Fields()->push(
                LiteralField::create(
                    'Message',
                    _t(
                        'Maintenance.Message',
                        'Automatic checks can help to increase the security of your website!' .
                        ' You can define a regular update schedule below.'
                    )
                )
            );

            // determine the default values
            $runs = ScheduledRun::get()->filter('Type', $type);
            $interval = '';
            $unit = 'Hour';
            if ($runs->count() > 0) {
                $run = $runs->first();
                $interval = $run->ExecuteInterval;
                $unit = $run->ExecuteEvery;
            }

            // add the hidden field for the type and the interval option
            $form->Fields()->push(HiddenField::create('Type', 'Type', $type));
            $form->Fields()->push(FieldGroup::create(
                new NumericField('ExecuteInterval', '', $interval),
                new DropdownField(
                    'ExecuteEvery',
                    '',
                    array(
                        'Hour' => _t('ScheduledExecution.ExecuteEveryHour', 'Hour(s)'),
                        'Day' => _t('ScheduledExecution.ExecuteEveryDay', 'Day(s)'),
                        'Week' => _t('ScheduledExecution.ExecuteEveryWeek', 'Week(s)'),
                        'Month' => _t('ScheduledExecution.ExecuteEveryMonth', 'Month(s)'),
                        'Year' => _t('ScheduledExecution.ExecuteEveryYear', 'Year(s)'),
                    ),
                    $unit
                )
            )->setTitle(_t('ScheduledExecution.EXECUTE_EVERY', 'Execute every')));

            // add a new action for this purpose
            $form->Actions()->push(
                FormAction::create('setSchedule', _t('Maintenance.SetSchedulde', 'Set scheduled execution'))
            );
        }
    }

    /**
     * sets the scheduled run for a queuedjob up
     *
     * @param array $data
     * @param Form $form
     * @return SS_HTTPResponse
     */
    public function setSchedule($data, Form $form)
    {
        // only admins are allowed to adjust this
        if (Permission::check('ADMIN') && isset($data['ExecuteInterval']) && isset($data['ExecuteEvery'])) {
            // find and update the scheduled run settings
            $runs = ScheduledRun::get()->filter('Type', $data['Type']);
            if ($runs->count() > 0) {
                // update an existing entry
                $run = $runs->first();
                $run->ExecuteInterval = (int) $data['ExecuteInterval'];
                $run->ExecuteEvery = (string) $data['ExecuteEvery'];
                $run->write();

                // special case - interval = 0 - this way the queuedjob will be regenerated if it gets reactivated.
                if ($run->ExecuteInterval === 0) {
                    $run->delete();
                }
            } else {
                // add a new scheduled job
                $run = new ScheduledRun();
                $run->Type = (string) $data['Type'];
                $run->ExecuteInterval = (int) $data['ExecuteInterval'];
                $run->ExecuteEvery = (string) $data['ExecuteEvery'];
                $run->write();

                // queue the first run
                $run->ScheduledJobID = singleton('QueuedJobService')->queueJob(new ScheduledExecutionJob($run));
                $run->write();
            }
        }

        // redirect back
        return $this->responseNegotiator->respond($this->getRequest());
    }
}
