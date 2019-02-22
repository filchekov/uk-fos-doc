<?php
defined("_JEXEC") or die("Restricted access");
defined("DS") or define("DS", DIRECTORY_SEPARATOR);

jimport("joomla.form.formfield");

class JFormFieldMaillog extends JFormField {

	protected $type = "maillog";

	public function getInput() {
		$data	= $this->form->getData();
		$params	= $data->get("params");
		$mod_id	= $data->get('id');

		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
					->select("*")
					->from($db->quoteName("#__mod_uk_fos_doc_log"))
					->where($db->quoteName("mod_id") . " = ". $db->quote($mod_id))
					->order("id ASC");
		$db->setQuery($query);
		$logs = $db->loadObjectList();
		$count = $db->getAffectedRows();
		$content = "";

		if ($count > 0) {
			$content .= "<div class=\"margin-bottom\">";
				$content .= "<a href=\"#\" id=\"log-remove-selected\" class=\"btn btn-danger\">" . JText::_("MOD_UK_FOS_DOC_LOG_REMOVE_SELECTED") . "</a>";
			$content .= "</div>";

			$content .= "<table id=\"logs\" class=\"table table-striped table-bordered\">";

			$content .= "<tr>";
				$content .= "<th class=\"span1\"><input type=\"checkbox\" id=\"log-select-all\" /></th>";
				$content .= "<th class=\"span1\">" . JText::_("MOD_UK_FOS_DOC_LOG_ID") . "</th>";
				$content .= "<th class=\"span4\">" . JText::_("MOD_UK_FOS_DOC_LOG_DATE") . "</th>";
				$content .= "<th class=\"span2\">" . JText::_("MOD_UK_FOS_DOC_LOG_STATUS") . "</th>";
				$content .= "<th class=\"span3\">" . JText::_("MOD_UK_FOS_DOC_LOG_IP") . "</th>";
				$content .= "<th class=\"\">"      . JText::_("MOD_UK_FOS_DOC_LOG_CONTENT") . "</th>";
				$content .= "<th class=\"span5\">" . JText::_("MOD_UK_FOS_DOC_LOG_ERROR") . "</th>";
			$content .= "</tr>";

			foreach ($logs as $num => $log) {
				$content .= "<tr>";
					$content .= "<td><input type=\"checkbox\" value=\"" . $log->id . "\" name=\"log-select[]\" /></td>";

					$content .= "<td>" . $log->id		. "</td>";
					$content .= "<td>" . $log->date		. "</td>";
					$content .= "<td>" . $log->status	. "</td>";
					$content .= "<td>" . $log->ip		. "</td>";
					$content .= "<td>" . $log->content	. "</td>";
					$content .= "<td>" . $log->error	. "</td>";
				$content .= "</tr>";
			}

			$content .= "</table>";

			return $content;
		} else {
			return "<div class=\"alert span3 alert-large alert-warning\">" . JText::_("MOD_UK_FOS_DOC_LOG_NO_DATA") . "</div>";
		}
	}

	public function getLabel() {
		return "";
	}
}