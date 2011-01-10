<?php
class PastiePage extends Page {
	
	static $db = array(
		'CanCreateMode' => "Enum('Anyone, Logged In Users, Specific Users', 'Logged In Users')",
		'MaximumPastieSize' => 'Int',
	);
	
	static $defaults = array(
		'CanCreateMode' => 'Logged in Users',
		'MaximumPastieSize' => '2056', 
	);
	
	static $many_many = array(
		'CanCreateGroups' => 'Group',
	);
	
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab('Root.Behaviour', new OptionSetField(
			'CanCreateMode', 'Who can create Pasties?', $this->dbObject('CanCreateMode')->enumValues()
		));
		$fields->addFieldToTab('Root.Behaviour', new TreeMultiselectField('CanCreateGroups', 'Specific User Groups'));
		$fields->addFieldToTab('Root.Behaviour', new NumericField('MaximumPastieSize', 'Maximum Pastie Content Length'));
		return $fields;
	}
	
	function LatestSnippets($limit = 10) {
		return DataObject::get('PastieSnippet', null, 'Created DESC', null, $limit);
	}
	
	function CanCreatePastie() {
		$canCreate = false;
		switch($this->CanCreateMode) {
			case "Anyone": 
				$canCreate = true;
				break;
			case "Logged In Users": 
				$canCreate = (bool)Member::currentUserID();
				break;
			case "Specific Users":
				if($member = Member::currentUser())
					$canCreate = $member->inGroups($this->CanCreateGroups());
		}
		$this->extend('CanCreatePastie', $canCreate);
		return $canCreate;
		
	}
}

class PastiePage_Controller extends Page_Controller {
	
	function Form() {
		$formTitle = "Create a New Snippet";
		
		if(!$this->CanCreatePastie()) return;
		
		$parentReference = "";
		if($this->Action == 'show' && $snippet = PastieSnippet::get_by_reference($this->request->param('ID'))) {
			$parentReference = $snippet->Reference;
			$formTitle = "Create a Child Snippet";
		} else {
			$snippet = new PastieSnippet();
		}
				
				$form = new Form(
			$this,
			'Form',
			new FieldSet(
				new HeaderField($formTitle, 3),
				new TextField('Title', 'Title'),
				new TextAreaField('Content', 'Content', $parentContent),
				new DropdownField('Language', 'Language', PastieSnippet::get_valid_languages()->toDropdownMap('ID', 'Name'), 'php'),
				new HiddenField('ParentReference', 'ParentReference', $parentReference)
			),
			new FieldSet(
				new FormAction('doSave', 'Save')
			)
		);
		$form->loadDataFrom($snippet);
		return $form;
	}
	
	function show() {
		$snippet = PastieSnippet::get_by_reference($this->request->param('ID'));
		if(!$snippet) Director::redirect($this->Link());
		return $this->customise(array('Snippet' => $snippet));
	}
	
	function raw() {
		$snippet = PastieSnippet::get_by_reference($this->request->param('ID'));
		if(!$snippet) Director::redirect($this->Link());
		$response = new SS_HTTPResponse($snippet->Content);
		$response->addHeader('Content-type', 'text/plain');
		$response->addHeader('Content-disposition', 'inline');
		return $response;
	}
	
	/**
	 * @param $data
	 * @param $form
	 */
	function doSave($data, $form) {
		if(!$this->CanCreatePastie()) return Security::permissionFailure();
		
		if(!isset($data['Content']) || strlen($data['Content']) == 0) {
			$form->sessionMessage('Content cannot be empty.', 'warning');
			return Director::redirectBack();
		}
		if($this->MaximumPastieSize && strlen($data['Content']) > $this->MaximumPastieSize) {
			$form->sessionMessage('Content cannot exceed ' . $this->MaximumPastieSize . ' characters.');
			return Director::redirectBack();
		}
		
		$languages = PastieSnippet::get_valid_languages();
		if(!isset($data['Language']) || !$languages->find('Name', $data['Language'])) {
			$form->sessionMessage('Please select a valid language.', 'warning');
			return Director::redirectBack();			
		}
		
		$snippet = new PastieSnippet();
		$form->saveInto($snippet);
		
		if(isset($data['ParentReference']) && $parent = PastieSnippet::get_by_reference($data['ParentReference']))
			$snippet->ParentID = $parent->ID;
		
		$snippet->write();
		
		$form->sessionMessage('Saved', 'good');
		Director::redirect($this->Link("show/{$snippet->Reference}"));
	}
}