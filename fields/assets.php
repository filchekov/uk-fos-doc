<?php

defined("_JEXEC") or die;

class JFormFieldAssets extends JFormField {

	protected $type = "Assets";

	protected function getInput() {
		$document	= JFactory::getDocument();
		$media_path	= JUri::root() . "media/mod_uk_fos_doc/";

		$document->addScript($media_path . "js/backend.js");
		$document->addStylesheet($media_path . "css/backend.css");
	}

	protected function getLabel() {
		return "";
	}
}
