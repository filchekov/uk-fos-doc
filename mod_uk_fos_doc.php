<?php
/**
 * @package	Module UkFos AJAX contact form for Joomla! 3.2+
 * @author	SK <mightyskeet@gmail.com>
 * @license	GNU/GPLv3 [http://www.gnu.org/licenses/gpl-3.0.html]
 */

// error_reporting(E_ALL);

defined("_JEXEC") or die("Restricted access");

if (version_compare(PHP_VERSION, "5.5.0", "<")) {
	die(strtoupper($module->name) . ": PHP version must be at least 5.5.0");
}

if (version_compare(JVERSION, "3.6", "<")) {
	die(strtoupper($module->name) . ": Joomla! version must be at least 3.6");
}

require_once __DIR__ . "/helper.php";

$document	= JFactory::getDocument();
$itemId		= JFactory::getApplication()->getMenu()->getActive()->id;
$lang_tag	= explode("-", JFactory::getLanguage()->getTag());
$media_path	= "media/mod_uk_fos_doc/";
$mod_uk_fos_doc	= new ModUkFosDocHelper;

if ($params->get("jquery_use")) JHtml::_("jquery.framework");
if (version_compare(JVERSION, "3.7", ">=")) JHtmlBehavior::core();

switch ($params->get("js_load_method", "defer")) {
	case "async":
		$js_load_method_attribs = ["async" => true];
		break;
	case "defer":
		$js_load_method_attribs = ["defer" => true];
		break;
	case "both":
		$js_load_method_attribs = ["async" => true, "defer" => true];
		break;
	default:
		$js_load_method_attribs = [];
		break;
}

if ($params->get("core_js_use")) {
	$document->addScript($media_path . "js/notifications/uikit_notify/core.min.js", [], $js_load_method_attribs);
	$document->addScript($media_path . "js/notifications/uikit_notify/utility.min.js", [], $js_load_method_attribs);
}

if ($params->get("uikit_notify_js_use")) {
	$document->addScript($media_path . "js/notifications/uikit_notify/notify.min.js", [], $js_load_method_attribs);
}

$document->addScript($media_path . "js/validate/jquery.validate.min.js", [], $js_load_method_attribs);
$document->addScript($media_path . "js/uk-fos.js", [], $js_load_method_attribs);

$form					= new stdClass();
$form->orient			= $params->get("form_orient", "stacked");
$form->labeling			= $params->get("form_labeling", "label");
$form->intro			= $params->get("form_intro");
$form->terms_text		= $params->get("terms_text");
$form->honeypot			= $params->get("honeypot", 1);
$form->recaptcha		= $params->get("recaptcha", 0);
$form->nodata_behavior	= $params->get("nodata_behavior");
$form->send_remote_ip	= $params->get("send_remote_ip", 0);
$form->class			= $form->orient;
$form->track_url		= $params->get("track_url", 1);
$form->extra_js_success	= $params->get("extra_js_success");

if ($params->get("moduleclass_sfx")) {
	$form->class .= " " . htmlspecialchars($params->get("moduleclass_sfx"), ENT_COMPAT, "UTF-8");
}

$form->settings						= new stdClass();
$form->settings->mod_id				= $module->id;
$form->settings->Itemid				= $itemId;
$form->settings->alert_system		= $params->get("alert_system");
$form->settings->clean_form			= $params->get("clean_form");
$form->settings->close_modal		= $params->get("close_uikit_modal");
$form->settings->invalid_class		= $params->get("invalid_class");
$form->settings->valid_class		= $params->get("valid_class");
$form->settings->upload_max_size	= $params->get("upload_max_size", 5000) * 1000;
$form->settings->track_url			= $form->track_url;

switch ($form->settings->alert_system) {
	case "uikit_notify":
		$form->settings->alert_timeout	= $params->get("alert_timeout", 5000);
		$form->settings->alert_position	= $params->get("alert_position");
		break;

	case "redirect":
		$form->settings->redirect_url	= $params->getPath("redirect_url");
		break;

	case "joomla-message":
		JHtmlBehavior::core();
		break;
}

$form->align_elements = ($form->orient == "horizontal");
$btn = new stdClass();

switch ($params->get("btn_style")) { // TODO: Replace with own CSS classes
	case "primary":
		$btn->class = "uk-button-primary btn-primary";
		break;
	case "danger":
		$btn->class = "uk-button-danger btn-danger";
		break;
	case "success":
		$btn->class = "uk-button-success btn-success";
		break;
	case "link":
		$btn->class = "uk-button-link btn-link";
		break;
	default:
		$btn->class = "btn-default";
		break;
}

if ($params->get("btn_class")) $btn->class .= " " . $params->get("btn_class");

$btn->text = $params->get("btn_text", JText::_("MOD_UK_FOS_DOC_BUTTON_TEXT_DEFAULT"));
$btn->onsubmit_text = $params->get("btn_onsubmit_text");

if ($form->recaptcha) {
	JPluginHelper::importPlugin("captcha", "recaptcha");
	$dispatcher = JEventDispatcher::getInstance();
	$dispatcher->trigger("onInit", "dynamic_recaptcha_" . $module->id);

	$align_class				= $form->align_elements ? "class=\"uk-form-controls\"" : "class=\"\"";
	$recaptcha_set				= [null, "dynamic_recaptcha_" . $module->id, $align_class];
	$form->recaptcha_content	= $dispatcher->trigger("onDisplay", $recaptcha_set);
	$form->recaptcha_content	= $form->recaptcha_content[0];
}

$validator_override = array(
	"basic" => [
		"upload_max_size"	=> JText::_("MOD_UK_FOS_DOC_UPLOAD_MAX_SIZE_MSG"),
		"no_response_data"	=> JText::_("MOD_UK_FOS_DOC_NO_RESPONSE_DATA"),
		"fatal_error"		=> JText::_("MOD_UK_FOS_DOC_FATAL_ERROR"),
		"time"				=> JText::_("MOD_UK_FOS_DOC_VALIDATE_TIME"),
		"accept"			=> JText::_("MOD_UK_FOS_DOC_VALIDATE_ACCEPT"),
		"pattern"			=> JText::_("MOD_UK_FOS_DOC_VALIDATE_PATTERN"),
		"required"			=> JText::_("MOD_UK_FOS_DOC_VALIDATE_REQUIRED"),
		"remote"			=> JText::_("MOD_UK_FOS_DOC_VALIDATE_REMOTE"),
		"email"				=> JText::_("MOD_UK_FOS_DOC_VALIDATE_EMAIL"),
		"url"				=> JText::_("MOD_UK_FOS_DOC_VALIDATE_URL"),
		"date"				=> JText::_("MOD_UK_FOS_DOC_VALIDATE_DATE"),
		"dateISO"			=> JText::_("MOD_UK_FOS_DOC_VALIDATE_DATE_ISO"),
		"number"			=> JText::_("MOD_UK_FOS_DOC_VALIDATE_NUMBER"),
		"digits"			=> JText::_("MOD_UK_FOS_DOC_VALIDATE_DIGITS"),
		"creditcard"		=> JText::_("MOD_UK_FOS_DOC_VALIDATE_CREDIT_CARD"),
		"equalTo"			=> JText::_("MOD_UK_FOS_DOC_VALIDATE_EQUAL_TO"),
		"extension"			=> JText::_("MOD_UK_FOS_DOC_VALIDATE_EXTENSION")
	],
	"format" => [
		"maxlength"				=> JText::_("MOD_UK_FOS_DOC_VALIDATE_FORMAT_MAX_LENGTH"),
		"minlength"				=> JText::_("MOD_UK_FOS_DOC_VALIDATE_FORMAT_MIN_LENGTH"),
		"rangelength"			=> JText::_("MOD_UK_FOS_DOC_VALIDATE_FORMAT_RANGE_LENGTH"),
		"range"					=> JText::_("MOD_UK_FOS_DOC_VALIDATE_FORMAT_RANGE"),
		"max"					=> JText::_("MOD_UK_FOS_DOC_VALIDATE_FORMAT_MAX"),
		"min"					=> JText::_("MOD_UK_FOS_DOC_VALIDATE_FORMAT_MIN"),
		"require_from_group"	=> JText::_("MOD_UK_FOS_DOC_VALIDATE_REQUIRE_FROM_GROUP")
	]
);

if (version_compare(JVERSION, "3.7", ">=")) {
	if (!$document->getScriptOptions("uk-fos-overrides")) {
		$document->addScriptOptions("uk-fos-overrides", $validator_override);
	}

	$document->addScriptOptions("uk-fos-" . $module->id, (array) $form->settings);
} else {
	$mod_options = "
	(function($) {
		if (typeof window.ukFosSettings === \"undefined\") {
			window.ukFosSettings = {};
			$.extend($.validator.messages, " . json_encode((object) $validator_override) . ");
		}
		$.extend(window.ukFosSettings, {
			\"uk-fos-" . $module->id . "\": " . json_encode($form->settings) . "
		});
	})(jQuery);";

	$document->addScriptDeclaration($mod_options);
}

if ($form->extra_js_success) {
	$extra_js_success = "
	jQuery('html').on('ajax-state-success', '#uk-fos-$module->id', function() {
		$form->extra_js_success
	});
	";

	$document->addScriptDeclaration($extra_js_success);
}

$fields = $params->get("field");

require JModuleHelper::getLayoutPath("mod_uk_fos_doc", $params->get("layout", "default"));
