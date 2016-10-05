<?php
namespace Codem\Charts;
/**
 * ModelAdmin for charts
 */
class ChartAdmin extends \ModelAdmin {

	private static $url_segment = 'charts';
	private static $menu_title = 'Charts';
	private static $title = 'Charts';

	public static $managed_models = array(
		'Codem\Charts\Chart' => array('buttonName' => 'Chart', 'title' => 'Charts', 'type' => 'default', 'actions' => array(), 'allow_export' => 0, 'allow_add' => 1, 'allow_delete' => 0),
	);

	/**
	 * @return array Map of class name to an array of 'title' (see {@link $managed_models})
	 */
	public function getManagedModels() {
		//get declared models
		$models = parent::getManagedModels();
		return $models;
	}

	protected function getModelSettings() {
		$models = $this->getManagedModels();
		if(!empty($models[$this->modelClass])) {
			return $models[$this->modelClass];
		}
		return FALSE;
	}

	protected function getModelSetting($setting) {
		if($settings = $this->getModelSettings()) {
			return isset($settings[$setting]) ? $settings[$setting] : NULL;
		}
		return NULL;
	}

	protected function canSortOn($field) {
		$sng=singleton($this->modelClass);
		$fieldType=$sng->db($field);
		return !empty($fieldType);
	}

	public function getList() {
		$context = $this->getSearchContext();
		$params = $this->request->requestVar('q');
		$list = $context->getResults($params);

		$this->extend('updateList', $list);

		return $list;
	}

	public function getEditForm($id = null, $fields = null) {
		$list = $this->getList();
		$orderable_rows = $this->canSortOn('Sort');
		$default_sort = singleton($this->modelClass)->stat('default_sort');
		if($default_sort) {
			$list = $list->sort($default_sort);
		} else if($orderable_rows) {
			$list = $list->sort('Sort ASC');
		} else {
			$list = $list->sort('ID DESC');
		}

		$fieldConfig = GridFieldConfig_RecordEditor::create($this->stat('page_length'));
		$listField = GridField::create(
			$this->sanitiseClassName($this->modelClass),
			FALSE,
			$list,
			$fieldConfig
		);

		$fieldConfig->removeComponentsByType('GridFieldFilterHeader');

		if($this->getModelSetting('allow_add')) {
			$addNew = $fieldConfig->getComponentByType('GridFieldAddNewButton');
			if($buttonName = $this->getModelSetting('buttonName')) {
				$addNew->setButtonName('Create ' . $buttonName);
			}
		} else {
			$fieldConfig->removeComponentsByType('GridFieldAddNewButton');
		}


		if($this->getModelSetting('allow_export')) {
			$exportButton = new GridFieldExportButton('before');
			$exportButton->setExportColumns($this->getExportFields());
			$fieldConfig->addComponent($exportButton);
		} else {
			$fieldConfig->removeComponentsByType('GridFieldExportButton');
		}

		if($this->getModelSetting('allow_print')) {
			$printButton = new GridFieldPrintButton('before');
			$fieldConfig->addComponent($printButton);
		} else {
			$fieldConfig->removeComponentsByType('GridFieldPrintButton');
		}

		// Validation
		if(singleton($this->modelClass)->hasMethod('getCMSValidator')) {
			$detailValidator = singleton($this->modelClass)->getCMSValidator();
			$listField->getConfig()->getComponentByType('GridFieldDetailForm')->setValidator($detailValidator);
		}

		if($this->getModelSetting('editable_columns')) {
			$fieldConfig->removeComponentsByType('GridFieldEditableColumns');
			$fieldConfig->addComponent(new GridFieldEditableColumns());
		}

		if(class_exists('GridFieldOrderableRows') && !$default_sort && $orderable_rows) {
			$fieldConfig->addComponent(new GridFieldOrderableRows('Sort'));
		}

		if(class_exists('GridFieldBulkManager')) {
			$fieldConfig->addComponent(new GridFieldBulkManager());
		}

		$form = new Form(
			$this,
			'EditForm',
			new FieldList($listField),
			new FieldList()
		);
		$form->addExtraClass('cms-edit-form cms-panel-padded center');
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		$editFormAction = Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'EditForm');
		$form->setFormAction($editFormAction);
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');

		$this->extend('updateEditForm', $form);

		return $form;
	}

}
