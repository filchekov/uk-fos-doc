<?php
/**
 * @package	Module UkFos AJAX contact form for Joomla! 3.2+
 * @author	SK <mightyskeet@gmail.com>
 * @license	GNU/GPLv3 [http://www.gnu.org/licenses/gpl-3.0.html]
 */

class mod_uk_fos_docInstallerScript {

	public static $url_update = "http://tpl.ais-web.ru/test-mod-ukfos-update";
	public static $url_info = "https://url/info";

	public function sendRequest($action, $version) {
		$data = http_build_query([
			"action"  => $action,
			"version" => $version
		]);

		$options = [
			"http" => [
				"method"  => "POST",
				"header"  => "Content-type: application/x-www-form-urlencoded" . PHP_EOL
							 . "Content-Length: " . strlen($data) . PHP_EOL,
				"content" => $data
			]
		];

		$context = stream_context_create($options);
		$request = @fopen(self::$url_update, "r", false, $context);
	}

	public function checkOverride($mod_sys_name, $mod_name) {
		$db = JFactory::getDBO();
		$query = "SELECT `template` FROM `#__template_styles` WHERE `client_id` = 0 AND `home` = 1";
		$db->setQuery($query);
		$tpl_name = $db->loadResult();

		$override = [JPATH_SITE, "templates", $tpl_name, "html", $mod_sys_name, "default.php"];

		if (file_exists(implode(DIRECTORY_SEPARATOR, $override))) {
			$message  = JText::_($mod_name . ": " . "Note that there is an module override file found within current site template"); // Add lang const

			$app = JFactory::getApplication();
			$app->enqueueMessage($message, "warning");
		}
	}

	public function install($parent) {
		self::sendRequest("install", $parent->get("manifest")->version);
	}

	public function uninstall($parent) {
		self::sendRequest("uninstall", $parent->get("manifest")->version);
	}

	public function update($parent) {
		$version = $parent->get("manifest")->version;

		self::sendRequest("update", $version);
		self::checkOverride($parent->get("element"), $parent->get("manifest")->name);

		$message  = str_replace("%v%", $version, JText::_("The module has been updated to version %v%")) . ". "; // Add lang const
		$message .= JText::_("See changelog on") . " " . JHtml::link(self::$url_info, self::$url_info, ["target" => "_blank"]); // Add lang const

		$app = JFactory::getApplication();
		$app->enqueueMessage($message);
	}
}
