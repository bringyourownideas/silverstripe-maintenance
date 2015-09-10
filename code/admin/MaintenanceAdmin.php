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
	 * @var array
	 */
	private static $managed_models = array(
		'ComposerSecurityVulnerability',
		'ComposerUpdate',
	);

	/**
	 * hide the importer option
	 *
	 * @var boolean
	 */
	public $showImportForm = false;

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
		$field = $form->Fields()->fieldByName('ComposerUpdate');
		if ($field) $field->setConfig(new GridFieldConfig_Base());

		return $form;
	}
}
