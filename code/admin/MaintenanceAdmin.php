<?php
/**
 * Model Admin to display the known security vulnerabilities and available update
 */
class MaintenanceAdmin extends ModelAdmin {
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
	);

	/**
	 * Check which classes should be managed using this model admin - some may not exist
	 *
	 * @return array
	 */
	public function getManagedModels() {
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
	 * @param  int $id
	 * @param  FieldList $fields
	 * @return CMSForm
	 */
	public function getEditForm($id = null, $fields = null) {
		$form = parent::getEditForm($id, $fields);

		// remove all the edit options
		$field = $form->Fields()->fieldByName('ComposerSecurityVulnerability');
		if ($field) $field->setConfig(new GridFieldConfig_Base());
		if ($field) $field->getConfig()->addComponent(new GridFieldExternalLink());
		$field = $form->Fields()->fieldByName('ComposerUpdate');
		if ($field) $field->setConfig(new GridFieldConfig_Base());

		return $form;
	}
}
