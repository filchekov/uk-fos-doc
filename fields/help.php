<?php
defined("_JEXEC") or die;

class JFormFieldHelp extends JFormField {

	protected $type = "Help";

	protected function getInput() {
		$document	= JFactory::getDocument();
		$media_path	= JUri::root() . "media/mod_uk_fos_doc/";

		$document->addScript($media_path . "js/prism.js");
		$document->addStylesheet($media_path . "css/prism.css");

		$lang = JFactory::getLanguage()->getTag();
		$file = __DIR__ . "/help/" . $lang . ".html";

		if (!file_exists($file)) {
			$file = __DIR__ . "/help/en-GB.html";
		}

		require_once $file;
	}

	protected function getLabel() {
		return "";
	}
}
