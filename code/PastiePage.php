<?php
/**
 * Pastie Page Source File
 * @package pastie
 * @subpackage core
 * @author Hamish Campbell <hn.campbell@gmail.com>
 */

/**
 * Pastie Page
 * 
 * Provides a page for creating and view pasties. 
 * 
 * @package pastie
 * @subpackage core
 * @author Hamish Campbell <hn.campbell@gmail.com>
 */
class PastiePage extends Page {
	
	static $db = array(
		'CanCreateMode' => "Enum('Anyone, LoggedInUsers, SpecificUsers', 'LoggedInUsers')",
		'MaximumPastieSize' => 'Int',
	);
	
	static $defaults = array(
		'CanCreateMode' => 'Logged in Users',
		'MaximumPastieSize' => '2056', 
	);
	
	static $many_many = array(
		'CanCreateGroups' => 'Group',
	);
	
	function i18nCreateModes() {
		$modes = $this->dbObject('CanCreateMode')->enumValues();
		foreach($modes as $key => $value)
			$modes[$key] = _t('PastieSnippet.' . strtoupper($value), $value);
		return $modes;
	}
	
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$tab = $fields->findOrMakeTab('Root.Behaviour');
		$tab->push(new OptionSetField('CanCreateMode', _t('PastieSnippet.WHOCANCREATE', 'Who can create Snippets?'), $this->i18nCreateModes()));
		$tab->push(new TreeMultiselectField('CanCreateGroups', _t('PastieSnippet.SPECIFICGROUPS', 'Specific User Groups')));
		$tab->push(new NumericField('MaximumPastieSize', _t('PastieSnippet.MAXCONTENTLENGTH', 'Maximum Content Length')));
		return $fields;
	}
	
	function LatestSnippets($limit = 10) {
		return DataObject::get('PastieSnippet', null, 'Created DESC', null, $limit);
	}
	
	function getPastieSingularName($lowercase = false) {
		$p = new PastieSnippet();
		return $p->getSingularName($lowercase);
	}
	
	function getPastiePluralName($lowercase = false) {
		$p = new PastieSnippet();
		return $p->getPluralName($lowercase);
	}
	
	function CanCreatePastie() {
		$canCreate = false;
		switch($this->CanCreateMode) {
			case "Anyone": 
				$canCreate = true;
				break;
			case "LoggedInUsers": 
				$canCreate = (bool)Member::currentUserID();
				break;
			case "SpecificUsers":
				if($member = Member::currentUser())
					$canCreate = $member->inGroups($this->CanCreateGroups());
		}
		$this->extend('CanCreatePastie', $canCreate);
		return $canCreate;
		
	}
}

class PastiePage_Controller extends Page_Controller {
	
	static $allowed_actions = array(
		'Form',
		'show',
		'raw',
		'preview',
	);
	
	function Form() {
		$formTitle = _t('PastieSnippet.CREATENEW', "Create a New {$this->PastieSingularName}");
		
		if(!$this->CanCreatePastie()) return;
		
		$parentReference = "";
		if($this->Action == 'show' && $snippet = PastieSnippet::get_by_reference($this->request->param('ID'))) {
			$parentReference = $snippet->Reference;
			$formTitle = _t('PastieSnippet.CREATECHILD', "Create a Child {$this->PastieSingularName}");
		} else {
			$snippet = new PastieSnippet();
		}
		
		$form = new Form(
			$this,
			'Form',
			new FieldSet(
				new HeaderField($formTitle, 3),
				new TextField('Title', _t('PastieSnippet.TITLE', 'Title')),
				new DropdownField('Language', _t('PastieSnippet.LANGUAGE', 'Language'), PastieSnippet::get_valid_languages()->toDropdownMap('ID', 'Name'), 'php'),
				new TextAreaField('Code', _t('PastieSnippet.CONTENT', 'Content'), 10),
				new HiddenField('ParentReference', 'ParentReference', $parentReference)
			),
			new FieldSet(
				new FormAction('doSave', _t('PastieSnippet.SAVE', 'Save'))
			)
		);
		return $form;
	}
	
	/**
	 * Show Action - Show a paricular snippet
	 */
	function show() {
		$snippet = PastieSnippet::get_by_reference($this->request->param('ID'));
		if(!$snippet) Director::redirect($this->Link());
		return $this->customise(array('Snippet' => $snippet));
	}
	
	/**
	 * Raw Action - Output the raw content of this snippet
	 */
	function raw() {
		$snippet = PastieSnippet::get_by_reference($this->request->param('ID'));
		if(!$snippet) Director::redirect($this->Link());
		$response = new SS_HTTPResponse($snippet->Code);
		$response->addHeader('Content-type', 'text/plain');
		$response->addHeader('Content-disposition', 'inline');
		return $response;
	}
	
	/**
	 * Preview Action - Generates formatted output from POST vars
	 */
	function preview() {
		$content = isset($_POST['content']) ? (string)$_POST['content'] : "";
		$lang = isset($_POST['lang']) ? (string)$_POST['lang'] : 'php';
		if(!Director::is_ajax()) Director::redirectBack();
		$snippet = new PastieSnippet();
		$snippet->Language = $lang;
		$snippet->Code = $content;
		return $snippet->FormattedOutput;
	}
	/**
	 * @param $data
	 * @param $form
	 */
	function doSave($data, $form) {
		if(!$this->CanCreatePastie()) return Security::permissionFailure();
		
		if(!isset($data['Code']) || strlen($data['Code']) == 0) {
			$form->sessionMessage(_t('PastieSnippet.CONTENTEMPTYERROR', 'Content cannot be empty.'), 'warning');
			return Director::redirectBack();
		}
		if($this->MaximumPastieSize && strlen($data['Code']) > $this->MaximumPastieSize) {
			$form->sessionMessage(_t('PastieSnippet.CONTENTTOOLARGE', 'Content exceeds maximum length.'), 'warning');
			return Director::redirectBack();
		}
		
		$languages = PastieSnippet::get_valid_languages();
		if(!isset($data['Language']) || !$languages->find('Name', $data['Language'])) {
			$form->sessionMessage(_t('PastieSnippet.SELECTVALIDLANGUAGE', 'Please select a valid language.'), 'warning');
			return Director::redirectBack();
		}
		
		$snippet = new PastieSnippet();
		$form->saveInto($snippet);
		
		if(isset($data['ParentReference']) && $parent = PastieSnippet::get_by_reference($data['ParentReference']))
			$snippet->ParentID = $parent->ID;
		
		$snippet->write();
		
		$form->sessionMessage(_t('PastieSnippet.SAVEDNEW', 'New Snippet Created'), 'good');
		Director::redirect($this->Link("show/{$snippet->Reference}"));
	}
}
