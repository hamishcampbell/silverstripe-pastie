<?php
/**
 * Pastie Snippet Source File
 * @package pastie
 * @subpackage core
 * @author Hamish Campbell <hn.campbell@gmail.com>
 */

/**
 * Pastie Snippet Object
 * 
 * Provides a storage method for snippets. Also wraps the GeSHi 
 * library and provides shortcode parsing for the [code] tag.
 * 
 * @package pastie
 * @subpackage core
 * @author Hamish Campbell <hn.campbell@gmail.com>
 */
class PastieSnippet extends DataObject {
	
	static $singular_name = "Snippet";
	
	static $plural_name = "Snippets";
	
	static $db = array(
		'Title' => 'Varchar',
		'Code' => 'Text',
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
	
	/**
	 * Return an i18n singular name - template accessible
	 * @param $lowercase All lowercase
	 * @return string
	 */
	function getSingularName($lowercase = false) {
		return $lowercase ? strtolower($this->i18n_singular_name()) : $this->i18n_singular_name();
	}
	
	/**
	 * Return an i18n plural name - template accessible
	 * @param $lowercase All lowercase
	 * @return string
	 */
	function getPluralName($lowercase = false) {
		return $lowercase ? strtolower($this->i18n_plural_name()) : $this->i18n_plural_name();
	}
	
	/**
	 * Format the content of this snippet with GeSHi and return the output.
	 * @return string Formatted code output
	 */
	function getFormattedOutput() {
		require_once dirname(__DIR__) . '/thirdparty/geshi/geshi.php';
		$g = new GeSHi($this->Code, $this->Language);
		$g->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
		return $g->parse_code();
	}
	
	/**
	 * Before Write - set the owner if it is new and someone is logged in.
	 */
	function onBeforeWrite() {
		parent::onBeforeWrite();
		if(!$this->OwnerID && !$this->ID)
			$this->OwnerID = Member::currentUserID ();
	}
	
	/**
	 * After Write - create the hash reference from the ID if it has not been created.
	 */
	function onAfterWrite() {
		if($this->ID && !$this->Reference) {
			$this->Reference = $this->hash();
			$this->write();
		}
	}
	
	/**
	 * Render the Snippet with the PastieSnippet template
	 */
	function forTemplate() {
		return $this->RenderWith('PastieSnippet');
	}
	
	/**
	 * Gets a single PastieSnippet by reference. It is important
	 * not to expose the ID to end users.
	 * @param string $reference
	 * @return PastieSnippet|false
	 */
	static function get_by_reference($reference) {
		if(!$reference) return false;
		$reference_SQL = Convert::raw2sql($reference);
		return DataObject::get_one('PastieSnippet', "\"Reference\" = '$reference_SQL'");
	}
	
	/**
	 * ShortcodeParser handler for the [code] tage. Accepts a 'ref' parameter to display
	 * an existing snippet, or a 'lang' parameter to select the language (php by default). The
	 * content of the tag will be processed by GeSHi and returned.
	 * 
	 * @param array $args
	 * @param string $content
	 * @param ShortcodeParser $instance
	 */
	static function shortcode_handler($args, $content = null, $instance = null) {
		$content = str_replace('<br/>', "\n", $content);
		if(isset($args['ref']) && $snippet = self::get_by_reference((string)$args['ref']))
			return $snippet->FormattedOutput;
		$snippet = new PastieSnippet();
		$snippet->Language = (isset($args['lang'])) ? (string)$args['lang'] : 'php';
		$snippet->Code = $content;
		return $snippet->FormattedOutput;
	}
	
	/**
	 * Returns a list of valid languages from GeSHi
	 * @return DatObjectSet
	 */
	static function get_valid_languages() {
		require_once dirname(__DIR__) . '/thirdparty/geshi/geshi.php';
		$g = new GeSHi();
		$languages = new DataObjectSet();
		foreach($g->get_supported_languages(false) as $language)
			$languages->push(new ArrayData(array(
				'ID' => $language, 
				'Name' => $language
			)));
		$languages->sort('Name');
		return $languages;
	}
	
	/**
	 * Generates a unique and hard to guess (maybe) short hash
	 * from the ID of the object.
	 * Adapted from http://blog.kevburnsjr.com/php-unique-hash
	 * @param int $len The desired length of the hash
	 * @return string
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
