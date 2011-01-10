<?php
class PastieSnippet extends DataObject {
	
	static $db = array(
		'Title' => 'Varchar',
		'Content' => 'Text',
		'Language' => 'Varchar',
		'Reference' => 'Varchar',
	);
	
	static $has_one = array(
		'Owner' => 'Member',
		'Parent' => 'PastieSnippet',
	);
	
	static $has_many = array(
		'Children' => 'PastieSnippet',
	);
	
	static $indexes = array(
		'Reference' => true
	);
	
	static $default_sort = "Created DESC";
	
	static function get_valid_languages() {
		require_once dirname(__DIR__) . '/thirdparty/geshi/geshi.php';
		$g = new GeSHi();
		$languages = new DataObjectSet();
		foreach($g->get_supported_languages(false) as $language)
			$languages->push(new ArrayData(array(
				'ID' => $language, 
				'Name' => $language
			)));
		return $languages;
	}
	
	static function get_by_reference($reference) {
		if(!$reference) return false;
		$reference_SQL = Convert::raw2sql($reference);
		return DataObject::get_one('PastieSnippet', "\"Reference\" = '$reference_SQL'");
	}
	
	function getFormattedOutput() {
		require_once dirname(__DIR__) . '/thirdparty/geshi/geshi.php';
		$g = new GeSHi($this->Content, $this->Language);
		$g->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
		return $g->parse_code();
	}
	
	function onBeforeWrite() {
		parent::onBeforeWrite();
		if(!$this->OwnerID && !$this->ID)
			$this->OwnerID = Member::currentUserID ();
	}
	
	function onAfterWrite() {
		if($this->ID && !$this->Reference) {
			$this->Reference = $this->hash();
			$this->write();
		}
	}
	
	function forTemplate() {
		return $this->RenderWith('PastieSnippet');
	}
	
	/**
	 * The following hash code adapted from
	 * http://blog.kevburnsjr.com/php-unique-hash
	 */
	private function hash($len = 6) {
		$base = 36;
		$gp = array(1,23,809,28837,1038073,37370257 /*,1345328833*/);
		$maxlen = count($gp);
		$len = $len > ($maxlen-1) ? ($maxlen-1) : $len;
		while($len < $maxlen && pow($base,$len) < $this->ID) $len++; 
		if($len >= $maxlen) throw new Exception($this->ID." out of range (max ".pow($base,$maxlen-1).")");
		$ceil = pow($base,$len);
		$prime = $gp[$len];
		$dechash = ($this->ID * $prime) % $ceil;
		$hash = base_convert($dechash, 10, $base);
		return str_pad($hash, $len, "0", STR_PAD_LEFT);
	}
}