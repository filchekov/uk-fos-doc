<?php
/**
 * @package	Module UkFos AJAX contact form for Joomla! 3.2+
 * @author	SK <mightyskeet@gmail.com>
 * @license	GNU/GPLv3 [http://www.gnu.org/licenses/gpl-3.0.html]
 */

defined("_JEXEC") or die("Restricted access");
defined("DS") or define("DS", DIRECTORY_SEPARATOR);

class ModUkFosDocHelper
{
	public static $fallback = false;
	public static $origin;
	public static $mod_id;
	public static $remote_ip;

	public function outputMsg($state, $msg) {
		if (self::$fallback) {
			$app = JFactory::getApplication();
			$app->enqueueMessage($msg, ($state == 0 ? "error" : "message"));
			$app->redirect(JRoute::_(self::$origin));
		}

		return json_encode(["state"	=> (bool) $state, "msg"	=> $msg]);
		// return new JResponseJson(null, $msg, true);
	}

	public function getMsgText($text, $const) {
		return $text ? $text : JText::_($const);
	}

	public function prepareFields($fields_obj, $exclude, $inverse = false) {
		foreach ($fields_obj as $field) {
			if ($inverse xor $field->{$exclude[0]} == $exclude[1]) {
				unset($field);
			}
		}

		return $fields_obj;
	}

	public function parseOptions($str) {
		$str = explode(PHP_EOL, $str);

		foreach ($str as $i => &$option) {
			if (strpos($option, "=")) {
				$pair = explode("=", $option);

				$option = [
					"name"  => trim($pair[0]),
					"value" => trim($pair[1])
				];
			} else {
				$option = trim($option);

				$option = [
					"name"  => $option,
					"value" => $option
				];
			}
		}

		return $str;
	}

	public function parseComma(&$str) {
		if (!$str) return;

		$str = array_map("trim", explode(",", $str));
		return $str;
	}

	public function getAttr($str, $attr, $method = null) {
		preg_match("/$attr=\"(.*)\"/", $str, $matches);

		if (!$matches[1]) return;

		if ($method) {
			$method = array(__CLASS__, "parse" . ucfirst($method));
			call_user_func_array($method, array(&$matches[1]));
		}

		return $matches[1];
	}

	public function getRemoteIp($default = "UNKNOWN") {
		$vars = array(
			"HTTP_CLIENT_IP",
			"HTTP_X_FORWARDED_FOR",
			"HTTP_X_FORWARDED",
			"HTTP_FORWARDED_FOR",
			"HTTP_FORWARDED",
			"REMOTE_ADDR"
		);

		foreach ($vars as $var) {
			if (getenv($var)) return getenv($var);
		}

		return $default;
	}

	private function addLog($status, $content = "", $error = "") {
		$entry			= new stdClass();
		$entry->mod_id	= self::$mod_id;
		$entry->date	= date("Y-m-d H:i:s");
		$entry->status	= $status;
		$entry->ip		= self::$remote_ip;
		$entry->content	= $content;
		$entry->error	= $error;

		return JFactory::getDbo()->insertObject('#__mod_uk_fos_doc_log', $entry);
	}

	public static function removeLogsAjax() {
		$input = JFactory::getApplication()->input;
		$list = $input->getString("data");

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->delete($db->quoteName('#__mod_uk_fos_doc_log'));
		$query->where($db->quoteName('id') . " IN " . "(" . implode(",", $list) . ")");
		$db->setQuery($query);
		$result = $db->execute();

		echo $result ? "success" : "error";
	}

	public function renderField($field, $name, $form, $input_cls = "", $label_cls = "") {
		if (!$field->published) return;

		if ($input_cls) $input_cls = " " . $input_cls;
		if ($label_cls) $label_cls = " " . $label_cls;

		$id = "uk-fos-" . $form->settings->mod_id . "-" . $name;
		$attributes = array();

		if ($field->type == "file") {
			$name = "files[" . $name . "][]";
		} elseif ($field->type == "checkbox-group") {
			$groupname = $id . "-group";
			$name .= "[]";
		}

		if ($form->labeling == "label" or $field->type == "checkbox-group" or $field->type == "radio") {
			if ($field->type == "checkbox-group") {
				$label = "<label class=\"uk-form-label" . $label_cls . "\">" . $field->title . "</label>";
			} else {
				$label = "<label for=" . $id . " class=\"uk-form-label" . $label_cls . "\">" . $field->title . "</label>";
			}
		} elseif (in_array($field->type, ["text", "textarea", "email", "tel", "number", "url"])) {
			$attributes["ph"] = "placeholder=\"" . $field->title . "\"";
		}

		if ($field->required) $attributes["rq"] = "required";

		if ($field->attributes) {
			$attributes["at"] = $field->attributes;
		}

		switch ($field->type) {
			case "select":
				$sql = self::getAttr($field->attributes, "data-sql");

				if ($sql) {
					$attributes["at"] = str_replace("data-sql=\"$sql\"", "", $attributes["at"]);

					$db = JFactory::getDbo();
					$db->setQuery($sql);
					$db->execute();
					$options = $db->loadAssocList();
				} elseif ($field->options) {
					$options = self::parseOptions($field->options);
				} else break;

				$selected = self::getAttr($field->attributes, "data-selected");

				$html = "<select class=\"not-selected" . $input_cls . "\" name=\"" . $name
				. "\" id=\"" . $id . "\" " . implode(" ", $attributes) . ">";

				if ($form->labeling != "label") {
					$html .= "<option value=\"\"" . (!$selected ? "selected" : "")
					. " disabled >" . $field->title . "</option>";
				}

				foreach ($options as $option) {
					$option_attr = "";

					if ($selected and $selected == $option["name"]) {
						$option_attr = " selected";
					}

					$html .= "<option value=\"" . $option["value"] . "\""
					. $option_attr . " >" . $option["name"] . "</option>";
				}

				$html .= "</select>";
				break;

			case "textarea":
				$html  = "<textarea class=\"form-control" . $input_cls . "\" name=\"" . $name . "\" ";
				$html .= "id=\"" . $id . "\" " . implode(" ", $attributes) . "></textarea>";
				break;

			case "file":
				$html = "<div class=\"uk-placeholder uk-text-center upload-drop" . $input_cls . "\">";
					if ($form->labeling != "label") {
						$html .= "<p class=\"uk-h4 uk-margin-small\">" . $field->title . "</p>";
					}

					$html .= "<p class=\"uk-text-muted uk-margin-small\">";
						$html .= "<span class=\"dragndrop uk-hidden\">" . JText::_("MOD_UK_FOS_DOC_DROP_HERE_OR") . "</span> ";
						$html .= "<label for=\"" . $id . "\" class=\"uk-link\">" . JText::_("MOD_UK_FOS_DOC_SELECT_FILE_TO_UPLOAD") . "</label>";
					$html .= "</p>";

					$html .= "<input class=\"form-control uk-hidden\" type=\"file\" name=\"" . $name . "\" ";
					$html .= "id=\"" .  $id . "\" " . implode(" ", $attributes) . "/>";

					$html .= "<div class=\"upload-names\"></div>";
				$html .= "</div>";
				break;

			case "checkbox":
				$html  = "<label for=" . $id . " class=\"uk-form-label" . $label_cls . "\">";
				$html .= "<input class=\"form-control" . $input_cls . "\" type=\"checkbox\" name=\"" . $name . "\" ";
				$html .= "id=\"" . $id . "\" " . implode(" ", $attributes) . " value=\"" . JText::_('JYES') . "\" />" . $field->title;
				$html .= "</label>";

				$label = "";
				break;

			case "radio":
			case "checkbox-group":
				if ($field->type == "checkbox-group") {
					$field->type = "checkbox";
					$isGroup = true;
				}

				$sql = self::getAttr($field->attributes, "data-sql");

				if ($sql) {
					$attributes["at"] = str_replace("data-sql=\"$sql\"", "", $attributes["at"]);

					$db = JFactory::getDbo();
					$db->setQuery($sql);
					$db->execute();
					$options = $db->loadAssocList();
				} elseif ($field->options) {
					$options = self::parseOptions($field->options);
				} else break;

				$selected = self::getAttr($field->attributes, "data-selected", "comma");

				if ($isGroup) {
					$wrapper_class = "checkbox-group";
					$wrapper_class .= $attributes["rq"] ? " " . $attributes["rq"] : "";

					$html .= "<div class=\"$wrapper_class\" id=\"$groupname\" " . $attributes["at"] . ">";
					$groupname = " group-item " . $groupname;
				}

				foreach ($options as $i => $option) {
					$option_attr = "";

					if ($selected and in_array($option["name"], $selected)) {
						$option_attr = " checked";
					}

					$html .= "<label for=\"$id-$i\" class=\"uk-form-label" . $label_cls . "\">";
					$html .= "<input class=\"form-control$groupname" . $input_cls . "\" type=\"" . $field->type . "\" name=\"" . $name . "\" ";
					$html .= "id=\"$id-$i\" " . $option_attr . " value=\"" . $option["value"] . "\" />" . $option["name"];
					$html .= "</label>";
				}

				if ($isGroup) $html .= "</div>";

				break;

			case "html":
				$html  = "<div " . implode(" ", $attributes) . ">";
				$html .= $field->options;
				$html .= "</div>";

				$label = "";
				break;

			default:
				$html  = "<input class=\"form-control" . $input_cls . "\" type=\"" . $field->type . "\" name=\"" . $name . "\" ";
				$html .= "id=\"" . $id . "\" " . implode(" ", $attributes) . "/>";
				break;
		}

		return (object) ["html" => $html, "label" => $label];
	}

	public static function fallbackAjax() {
		self::$fallback	= true;

		return self::getAjax();
	}

	public static function getAjax() {
		$input = JFactory::getApplication()->input;
		$proof = $input->getString("uk_fos_doc_proof", "");

		if ($params->honeypot and !empty($proof)) {
			self::addLog(JText::_("MOD_UK_FOS_DOC_LOG_SPAM"), $proof);

			return self::outputMsg(0, JText::_("MOD_UK_FOS_DOC_HONEYPOT_MSG"));
		}

		self::$mod_id    = $input->getInt("mod_id");
		self::$remote_ip = self::getRemoteIp();
		self::$origin    = $input->getString("uk_fos_doc_origin");

		if (self::$mod_id) {
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
						->select($db->quoteName("params"))
						->from($db->quoteName("#__modules"))
						->where($db->quoteName("id") . " = " . $db->quote(self::$mod_id));
			$db->setQuery($query);
			$db->execute();
			$params = $db->loadResult();
		}

		if (!$params) return self::outputMsg(0, JText::_("MOD_UK_FOS_DOC_FATAL_ERROR_MSG"));
		$params = json_decode($params);

		if ($params->recaptcha) {
			JPluginHelper::importPlugin("captcha", "recaptcha");
			$recaptcha_response = JEventDispatcher::getInstance()->trigger("onCheckAnswer");

			if (!$recaptcha_response[0]) {
				$text = self::getMsgText($params->invalid_recaptcha_msg, "MOD_UK_FOS_DOC_INVALID_RECAPTCHA_MSG");
				return self::outputMsg(0, $text);
			}
		}

		$files_array  = $input->files->get("files");
		$upload_path  = $params->upload_path ? trim($params->upload_path, " \t/\\") : "images" . DS . "uploads";
		$upload_path .= DS . date("Y-m-d") . DS . date("H-i-s");

		$url_path  = JUri::base() . $upload_path;
		$file_path = JPATH_SITE . DS . $upload_path;

		if (self::$fallback) {
			$fields = self::prepareFields($params->field, ["type", "file"], true);
		}

		if ($files_array) {
			foreach ($files_array as $field_name) {
				$name = key($files_array);

				foreach ($field_name as $index => $file_info) {
					if (!empty($file_info["error"])) continue;

					if (self::$fallback) {
						$file_ext	= strtolower(array_pop(explode(".", $file_info["name"])));
						$file_index	= str_replace("field", "", $name);
						$allow_ext	= self::getAttr($fields[$file_index]["attributes"], "accept", "comma");

						if (!in_array($file_ext, $allow_ext)) {
							$msg = JText::_("MOD_UK_FOS_DOC_VALIDATE_ACCEPT");
							$msg .= ": " . implode(", ", $allow_ext);

							return self::outputMsg(0, $msg);
						}

						if ($file_info["size"] > $params->upload_max_size * 1000) {
							$msg = JText::_("MOD_UK_FOS_DOC_UPLOAD_MAX_SIZE_MSG");
							$msg = str_replace("{%1}", $file_info["name"], $msg);

							return self::outputMsg(0, $msg);
						}
					}

					if (!file_exists($file_path)) mkdir($file_path, 0755, true);

					$current_path	= $file_path . DS . $file_info["name"];
					$url			= $url_path  . DS . $file_info["name"];
					$files_paths[]	= [
						"name" => $file_info["name"],
						"path" => $current_path,
						"url"  => $url
					];

					move_uploaded_file($file_info["tmp_name"], $current_path);
				}
			}
		}

		$fields			= self::prepareFields($params->field, ["type", "file"]);
		$nodata_text	= self::getMsgText($params->nodata_text, "MOD_UK_FOS_DOC_NO_DATA_TEXT");
		// $fields_total	= count(get_object_vars($fields));
		$fields_total	= count((array) $fields);
		$form_data		= new stdClass;

		for ($i = 0; $i < $fields_total; $i++) {
			$_name  = "field" . $i;
			$_title = $fields->{$_name}->title;
			$_value = $input->getString($_name);

			if (!$_value) {
				if ($params->nodata_behavior == 1) {
					$_value = $nodata_text;
				} else continue;
			}

			$form_data->{$_name} = (object) ["title" => $_title, "value" => $_value];
		}

		if ($input->getString("added", "")) {
			$added_names = explode("&&", $input->getString("added"));

			foreach ($added_names as $key => $name) {
				$_name  = "added" . $key;
				$_title = $name;
				$_value = $input->getString($_name);

				if (!$_value) {
					if ($params->nodata_behavior == 1) {
						$_value = $nodata_text;
					} else continue;
				}

				$form_data->{$_name} = (object) ["title" => $_title, "value" => $_value];
			}
		}

		if ($params->use_mail_tpl) { // TODO: Replace with double brackets notation
			$content = preg_replace_callback("/\[(.*?)\]/", function ($match) use ($form_data, $params) {
				$mark = strtolower(trim($match[0], "[]"));

				if (is_numeric($mark)) {
					$mark = "field" . $mark;

					if (array_key_exists($mark, $form_data)) {
						return $form_data->{$mark}->value;
					}
				} elseif ($mark == "ip" and $params->send_remote_ip) {
					return self::$remote_ip;
				} elseif ($mark == "subject") {
					return $params->mail_subject;
				} elseif ($mark == "url") {
					return self::$origin;
				} else {
					return "[" . $mark . "]";
				}
			}, $params->mail_tpl);
		} else {
			$content  = "<h2>" . $params->mail_subject . "</h2>";
			$content .= "<ul>";

			foreach ($form_data as $field) {
				$content .= "<li><b>" . $field->title . "</b>: " . $field->value . "</li>";
			}

			if ($params->send_remote_ip) {
				$content .= "<li><b>IP</b>: " . self::$remote_ip . "</li>";
			}

			$content .= "</ul>";
		}

		if (!empty($files_paths)) {
			foreach ($files_paths as $file) {
				if ($params->attachment_behavior == 0) {
					$content .= "<hr/>";
					$content .= "<p><a href=\"" . $file["url"] . "\">" . $file["name"] . "</a></p>";
				}
			}
		}

		if ($params->track_url) {
			$content .= "<hr/>";
			$content .= "<p>" . JText::_("MOD_UK_FOS_DOC_SEND_FROM_URL") . ": ";
			$content .= "<a href=\"" . self::$origin . "\">" . self::$origin . "</a></p>";
		}

		if (!empty($params->sender)) {
			$sender = $params->sender;

			if (strpos($sender, ",")) $sender = explode(",", $sender);

		} else {
			$jConfig = JFactory::getConfig();
			$sender = array($jConfig->get("mailfrom"), $jConfig->get("fromname"));
		}

		$mailer = JFactory::getMailer();
		$mailer->setSender($sender);
		$mailer->addRecipient($params->recipient);

		if ($params->cc) $mailer->addCC(self::parseComma($params->cc));
		if ($params->bcc) $mailer->addBCC(self::parseComma($params->bcc));

		$mailer->setSubject($params->mail_subject);
		$mailer->isHTML(true);
		$mailer->Encoding = "base64";

		if (!empty($files_paths) and $params->attachment_behavior == 1) {
			foreach ($files_paths as $file) {
				$mailer->addAttachment($file["path"]);
			}
		}

		$mailer->setBody($content);
		$send = $mailer->Send();
		$jError = "";

		if (($send instanceof Exception) or ($send instanceof JException)) {
			$jError	= JError::getError();
			$send	= false;
		}

		if ((!$send and $params->log_behavior == 1) or $params->log_behavior == 2) {
			$send_status = $send ? JText::_("MOD_UK_FOS_DOC_LOG_SEND") : JText::_("MOD_UK_FOS_DOC_LOG_ERROR");

			if ($params->log_collect == 0) {
				$log_content = $send_status;
			} else {
				foreach ($form_data as $field) {
					$log_content .= "<div><b>" . $field->title . "</b>: " . $field->value . "</div>"; // TODO: Remove div?
				}

				if (!empty($files_paths)) {
					foreach ($files_paths as $file) {
						$log_content .= "<div>" . $file["path"] . "</div>"; // TODO: Remove div?
					}
				}
			}

			self::addLog($send_status, $log_content, $jError);
		}

		if ($send) {
			$text = self::getMsgText($params->success_msg, "MOD_UK_FOS_DOC_SUCCESS_MSG");
		} else {
			$text = self::getMsgText($params->error_msg, "MOD_UK_FOS_DOC_ERROR_MSG");
		}

		if (self::$fallback and $send) {
			if ($params->alert_system == "redirect" and $params->redirect_url) {
				self::$origin = JUri::base() . ltrim(urlencode($params->redirect_url), "/"); // TODO: urlencode()?
			}
		}

		return self::outputMsg($send, $text);
	}
}

//echo "<pre>"; var_dump($_POST); die();

class Word extends ZipArchive{
	    // Файлы для включения в архив
	    private $files;
	    // Путь к шаблону
	    public $path;
	    // Содержимое документа
	    protected $content;

	    public function __construct($filename, $template_path = '/template/' ){
	      // Путь к шаблону
	      $this->path = dirname(__FILE__) . $template_path;

	      // Если не получилось открыть файл, то жизнь бессмысленна.
	      if ($this->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
	        die("Unable to open <$filename>\n");
	      }

	      // Структура документа
	      $this->files = array(
	        "word/_rels/document.xml.rels",
	        "word/theme/theme1.xml",
	        "word/fontTable.xml",
	        "word/settings.xml",
	        "word/styles.xml",
	        "word/stylesWithEffects.xml",
	        "word/webSettings.xml",
	        "_rels/.rels",
	        "docProps/app.xml",
	        "docProps/core.xml",
	        "[Content_Types].xml" );

	      // Добавляем каждый файл в цикле
	      foreach( $this->files as $f )
	        $this->addFile($f , $f );
	    }

	    // Регистрируем текст
	    public function assign( $text = '' ){

	      // Берем шаблон абзаца
	      //$p = file_get_contents('p.xml' );

	      // Нам нужно разбить текст по строкам
	      $text_array = explode( "\n", $text );

	      foreach( $text_array as $str )
	        $this->content .= str_replace( '{TEXT}', $str, $p );
	    }

	    // Упаковываем архив
	    public function create($pattern_name) {
	    	//echo "<pre>"; var_dump($_POST); die();
			$this->open('tmp/'.$pattern_name.'.docx', ZipArchive::CREATE);
			// копируем все файлы для создания ms-word документа
			$this->addFromString("docProps/app.xml", file_get_contents("mod_uk_fos_doc_template/".$pattern_name."/docProps/app.xml"));
			$this->addFromString("docProps/core.xml", file_get_contents("mod_uk_fos_doc_template/".$pattern_name."/docProps/core.xml"));
			$this->addFromString("word/_rels/document.xml.rels", file_get_contents("mod_uk_fos_doc_template/".$pattern_name."/word/_rels/document.xml.rels"));

			$text = file_get_contents("mod_uk_fos_doc_template/".$pattern_name."/word/document.xml");

			$formContent = "";

			$firstFildSave = $_POST["uk_fos_doc_origin"];

			unset($_POST["uk_fos_doc_origin"]);

			if (strpos($text, "{doc_form}") !== false) { // ищем в шаблоне метку {doc_form} и заменяем ее на то что в форме
				foreach ($_POST as $key => $value) {
					if (strpos($key, "field") !== false) {
						//$formContent .= '<w:p><w:pPr><w:pStyle w:val="Normal"/><w:rPr></w:rPr></w:pPr><w:r><w:rPr></w:rPr><w:t>'."666".'</w:t></w:r></w:p>';
						$formContent .= '<w:p><w:pPr><w:pStyle w:val="Style15"/><w:spacing w:before="0" w:after="0"/><w:rPr></w:rPr></w:pPr><w:r><w:rPr></w:rPr><w:t>'.$value.'</w:t></w:r></w:p><w:p><w:pPr><w:pStyle w:val="Style15"/><w:spacing w:before="0" w:after="0"/><w:rPr></w:rPr></w:pPr><w:r><w:rPr></w:rPr></w:r></w:p>';
					}
				}

				$text = str_replace("{doc_form}", $formContent, $text);
			}
			else {
				//echo "<pre>"; var_dump($_POST); die();
				// перебераем этот массив последовательно
				foreach ($_POST as $key => $value) {
					$divideToLinesValue = explode("\n", str_replace("\r", '', $value));
					$value = implode('</w:t></w:r></w:p><w:p><w:pPr><w:pStyle w:val="Normal"/><w:rPr></w:rPr></w:pPr><w:r><w:rPr></w:rPr><w:t>', $divideToLinesValue);

					//echo "<pre>"; var_dump($value); die();
					// если это поле с именем в котром есть словосочетание из букв field, то заменяем все метки в шаблоне на значение этого поля
					$easyField = stripos($key, "field") !== false;

					//echo $key."<br>";

					if ($easyField) {
						$text = str_replace('{'.$key.'}', $value, $text);
					}

					//$divideToLinesText = explode("\n", str_replace("\r", '', $text));
					//$text = implode('</w:t></w:r></w:p><w:p><w:pPr>w:pStyle w:val="Normal"/><w:rPr></w:rPr></w:pPr><w:r><w:rPr></w:rPr><w:t>', $divideToLinesText);

					//$text = '---'.$text.'---';

					//echo "<pre>"; var_dump($text); die();

					// если это поле с именем в котром есть словосочетание из букв list,  то ищем все следующие поля этого списка, формируем текст на их основе, и заменяем все метки на сформированное значение
					$listRootField = stripos($key, "list") !== false;

					$currentList = array();

					if ($listRootField) {
						$currentList[] = $value;
						$numberRootField = str_replace("list", "", $key);
						// найти все поля от текущего принадлежащие этому корню
						foreach ($_POST as $innerKey => $innerValue) {
							// получить все поля с именем в котором есть слово rootНомер_поля
							$isChild = NULL;
							
							$isChild = stripos($innerKey, "root$numberRootField") !== false;

							if ($isChild) {
								$currentList[] = $innerValue;
							}
						}

						$listText = "";

						for ($i = 0; $i < (count($currentList) - 1); $i++) {
							$listText .= ($i + 1).'. '.$currentList[$i].'</w:t><w:br/><w:br/><w:t>'; 
						}

						$listText .= count($currentList).'. '.$currentList[count($currentList) - 1];
						$text = str_replace('{'.$key.'}', $listText, $text); // и заменить в шаблоне
					}
				}

				$_POST["uk_fos_doc_origin"] = $firstFildSave;
			}

			$this->addFromString("word/document.xml", $text);
			$this->addFromString("word/fontTable.xml", file_get_contents("mod_uk_fos_doc_template/".$pattern_name."/word/fontTable.xml"));
			$this->addFromString("word/settings.xml", file_get_contents("mod_uk_fos_doc_template/".$pattern_name."/word/settings.xml"));
			$this->addFromString("word/styles.xml", file_get_contents("mod_uk_fos_doc_template/".$pattern_name."/word/styles.xml"));
			$this->addFromString("_rels/.rels", file_get_contents("mod_uk_fos_doc_template/".$pattern_name."/_rels/.rels"));
			$this->addFromString('[Content_Types].xml', file_get_contents('mod_uk_fos_doc_template/'.$pattern_name.'/[Content_Types].xml'));
			$this->close();
	    }
	}

	if (empty($_POST["not_doc_file_form"])) { # кнопка была нажата из модуля uk_fos
		$w = new Word($params["pattern_name"].".docx");

		$w->create($params["pattern_name"]);

		foreach ($_POST as $key => $value) {
		    // это уже параметер котрый нужно вставить в шаблон
		 	if (stristr($value, "http:") == "") {
		 		$value = "";
		 	}

		 	if ($value != "") {
		 		$name = "./tmp/".$params["pattern_name"].'.docx';
		 		//echo "<pre>"; var_dump($name); die();
		 		//echo "<pre>"; var_dump($name); die();
				/*$fp = fopen($name, 'rb');
				// отправляем нужные заголовки
				header("Content-Type: application/msword");
				header('Content-Disposition: attachment; filename=' . basename($name));
				header("Content-Length: " . filesize($name));
				// скидываем картинку и останавливаем выполнение скрипта
				fpassthru($fp);*/
				//exit;

				//header("Location: http://test.lawmobile.ru/tmp/hide.docx");
				
				header("Location: /tmp/".$params["pattern_name"].'.docx');
				//echo "<pre>"; var_dump("Location: ".$_SERVER['SERVER_NAME']."/tmp/".$params["pattern_name"].'.docx'); die();

		 	}
	 	}
	} 

