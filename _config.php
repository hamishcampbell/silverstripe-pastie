<?php
/**
 * Adds a short-code for [code]. This allows styled code to be included in any SiteTree
 * content area by wrapping the content in the [code] tag. An optional 'lang' parameter
 * can be used to define a valid GeSHi language set, or a 'ref' parameter can point to 
 * an exisiting Snippet saved in the system.
 * 
 * <example>
 * 	[code lang='php']
 * 		$example = "Hello, World!";
 * 		echo $example;
 * 	[/code]
 * 
 *	[code ref='as12n' /]
 * 
 **/
ShortcodeParser::get('default')->register('code', array('PastieSnippet', 'shortcode_handler'));
