<?php
/**
 * @package	Module UkFos AJAX contact form for Joomla! 3.2+
 * @author	SK <mightyskeet@gmail.com>
 * @license	GNU/GPLv3 [http://www.gnu.org/licenses/gpl-3.0.html]
 */

defined("_JEXEC") or die("Restricted access");

if ($form->intro) echo $form->intro; ?>

<?php // Do not remove class "uk-fos-form" ?>
<!-- <form action="/" method="POST" name="uk-fos-<?php echo $module->id; ?>" id="uk-fos-<?php echo $module->id; ?>" class="uk-fos-form uk-form uk-form-<?php echo $form->class; ?>" enctype="multipart/form-data">
-->
<form onsubmit="before_submit(this)" method="POST" name="uk-fos-<?php echo $module->id; ?>" id="" class="uk-fos-doc-form uk-fos-form uk-form uk-form-<?php echo $form->class; ?>" enctype="multipart/form-data">
	<fieldset>

		<?php if ($form->honeypot) : ?>
			<?php // Do not remove or modify ?>
			<!--
			<input type="text" name="uk_fos_doc_proof" value="" tabindex="-1" hidden />
			--->
		<?php endif; ?>

		<?php // Do not remove or modify ?>
		<input type="hidden" name="uk_fos_doc_origin" value="<?php echo JUri::current(); ?>" />

		<?php foreach ($fields as $name => $field) : ?>
			<?php if (!$field->published) continue; ?>

			<?php
				// Here you can specify some additional classes for each field type and it's label.
				switch ($field->type) {
					case "text":			$input_class = ""; $label_class = ""; break;
					case "textarea":		$input_class = ""; $label_class = ""; break;
					case "email":			$input_class = ""; $label_class = ""; break;
					case "tel":				$input_class = ""; $label_class = ""; break;
					case "number":			$input_class = ""; $label_class = ""; break;
					case "date":			$input_class = ""; $label_class = ""; break;
					case "time":			$input_class = ""; $label_class = ""; break;
					case "url":				$input_class = ""; $label_class = ""; break;
					case "file":			$input_class = ""; $label_class = ""; break;
					case "select":			$input_class = ""; $label_class = ""; break;
					case "checkbox":		$input_class = ""; $label_class = ""; break;
					case "radio":			$input_class = ""; $label_class = ""; break;
					case "checkbox-group":	$input_class = ""; $label_class = ""; break;
					case "hidden":			$input_class = ""; $label_class = ""; break;
				}

				$rendered = $mod_uk_fos_doc->renderField($field, $name, $form, $input_class, $label_class);
			?>

			<?php // Do not remove class "uk-form-row" ?>
			<div class="uk-form-row form-group <?php echo $field->type == "hidden" ? "uk-margin-top-remove" : ""; ?>">
				<?php echo $rendered->label; ?>

				<div class="uk-form-controls">
					<?php echo $rendered->html; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<?php if ($form->recaptcha and $form->recaptcha_content) : ?>
			<div class="uk-form-row form-group"><?php echo $form->recaptcha_content; ?></div>
		<?php endif; ?>

		<div class="uk-form-row form-group <?php echo $form->align_elements ? "uk-form-controls" : "uk-text-center text-center"; ?>">
			<input class="uk-button <?php echo $btn->class; ?> msdoc" id='sbtn' type="submit" value="Сохранить">
		</div>

		<?php if ($form->terms_text) : ?>
			<div class="uk-form-row">
				<?php echo $form->terms_text; ?>
			</div>
		<?php endif; ?>
	</fieldset>
</form>

<script type="text/javascript">
	function ReloadPage(){
		window.location.reload();
	}

   document.getElementById('sbtn').onclick = function (event){
        setTimeout(ReloadPage, 3000);
    } 

	function before_submit(elem) {
		//setCookie('upd','yes',7);
		// подготовить поля к отправке
		// перебераем все поля в форме
		jQuery(elem).each(function () {

			jQuery(this).find(":input, textarea").each(function () {
				var fieldName = jQuery(this).attr("name");
				var fieldRootName = jQuery(this).attr("root");
				var attr = jQuery(this).attr('root');
				var newName = "root" + fieldRootName + "_" + fieldName;

				var is_root = jQuery(this).attr("is_root");

				if (typeof is_root !== typeof undefined && is_root !== false) {
					jQuery(this).attr("name", "list" + is_root);
				}
				else 
				{
					if (typeof attr !== typeof undefined && attr !== false) {
						jQuery(this).attr("name", newName);
						//console.log(newName);
					} 
					else {
						//console.log(fieldName);
					}
				}

				// ищем второй верхний div в который вложено это поле
				if (jQuery(this).parent().parent().css('display') == "none") {
					jQuery(this).val("");
				}
			});
		});

		return true; 
	}

	// пронумеровать каждое начальное поле для списков
	jQuery("input[add='yes']").each(function () {

	});

	function delete_button_click(button) {
		jQuery(button).parent().parent().remove();
	}

	function insurance(elem) {
			var nameButton = jQuery(elem).attr("id");
			var number = nameButton.replace("add_button", "");
			// 
			// найти корневой элемент
			var nextRoot = jQuery("input[id='add_field" + number + "']");
			nextRoot.attr("is_root", nextRoot.attr("name").replace("field", ""));
			//console.log(nextRoot);
			/////
			var form = jQuery(elem).parent().parent().parent()[0];
    		var button_div = jQuery(elem).parent().parent()[0]; // это блок внутри которого находится кнопка
	    	// добавить в эту форму скрытое поле для опознания
			var newElement = document.createElement("div");
			newElement.setAttribute('class', 'uk-form-row form-group');
			//form.appendChild(newElement);
			// найти элемент в котором есть атрибут endname со значением add_field + number, 
			// а если его нет то добавить к элементу которого есть атрибут name c таким значением
			var endNameInput = jQuery("input[end_name='add_field" + number + "']");
			var subInput = document.getElementById("add_field" + number);
			var rootElement = undefined;

			if (endNameInput.length != 0) {
				//console.log("конец");
				//console.log(endNameInput[0]);
				rootElement = endNameInput[0];
				// найден конец списка, добавляем к нему
				insertAfter(newElement, rootElement.parentElement.parentElement);
				endNameInput.attr("end_name", "-");
			}
			else {
				rootElement = newElement;
				//console.log("начало");
				// у списка нет конца добавляем в начало
				insertAfter(newElement, subInput.parentElement.parentElement);
			}

			var newDiv = document.createElement("div");
			newDiv.setAttribute('class', 'uk-form-controls');
			newElement.appendChild(newDiv);

			// теперь до блока newDiv нужно вставить label
			var newLabel = document.createElement("label");
			newLabel.setAttribute('class', 'uk-form-label');
			newElement.insertBefore(newLabel, newDiv);
			
			var newInput = document.createElement("input");
			newInput.setAttribute('class', 'form-control');
			newInput.setAttribute('type', 'text');
			//newInput.setAttribute('name', 'field' + lastInputNumber);
			newDiv.appendChild(newInput);

			// найти номер предыдущего поля и пронумеровать текущий
			var txt = jQuery(newInput).parent().parent().prev().find('span').text();
			var span = document.createElement("span");
			span.innerHTML = Number(txt) + 1;
			/////////////////////////////////////////////////////newInput.parentNode.insertBefore(span, newInput);

			var deleteButton = document.createElement("button");

			deleteButton.setAttribute("type", "button");
			deleteButton.setAttribute("style", "background: -webkit-gradient(linear, 0 0, 0 100%, from(#39f), to(#39f));height: 48px;width: 50px;font-size: 22px;vertical-align: bottom;border: 0px;");
			deleteButton.setAttribute("class", "uk-for-del-button");
			deleteButton.setAttribute("title", "удалить поле");
			deleteButton.innerHTML = "X";
			deleteButton.setAttribute("onclick", "delete_button_click(this);");
			insertAfter(deleteButton, newInput);

			newInput.setAttribute("end_name", "add_field" + number);
			// добавить указатель на корень своего списка
			newInput.setAttribute("root", nextRoot.attr("name").replace("field", ""));
			// это элемент списка, если корневой элемент скрываемый то нужно указать от какого флажка(checkbox) это зависит, будет использоваться для реакции по клику на флажок,
			newInput.setAttribute("hide-checkbox-number", nextRoot.attr("data-showon").replace("=*", ""));
			// (s) установить имя, отсчитывая от предыдущего поля, а если предыдущее поле корень то установить имя - field0
			var prevInputAttrAdd = jQuery(newInput).parent().parent().prev().find("input");

			if (prevInputAttrAdd.attr("add") == "yes") {
				newInput.setAttribute("name", "field0");
			} 
			else {
				var prevInputNumberName = jQuery(newInput).parent().parent().prev().find("input").attr("name").replace("field", "");
				newInput.setAttribute("name", "field" + (Number(prevInputNumberName) + 1));
			}

			deleteButton.setAttribute("name", "field" + (Number(prevInputNumberName) + 1));

			let index = 0;
	}


    var element = document.getElementsByTagName("button");

    for (i = 0; i < element.length; i++) {
    	element[i].setAttribute("onclick", "capture_function(this);");
    }

    function capture_function(element) {
    	var checkClassName = element.parentElement.parentElement.parentElement.className.split(' ')[0];

    	if (checkClassName != "uk-fos-doc-form") {
    		jQuery(".uk-fos-doc-form").submit(function () {
				return false;
			});

			jQuery("*").remove(".uk-fos-doc-form");

			// добавить в эту форму скрытое поле для опознания
			var form = document.getElementsByClassName(checkClassName)[0];
			var newElement = document.createElement("input");
			newElement.setAttribute('name', 'not_doc_file_form');
			newElement.setAttribute('value', 'not_doc_file_form');
			form.appendChild(newElement);
			form.submit();
    	}
    }

    var doubleCallFixFlag = 0;

    function insertAfter(newNode, referenceNode) {
	    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
	}

    var inputAddCollection = jQuery("input[add='yes']");

    var idIndex = 0;

    if (jQuery("div[already='yes']").length == 0){
	    inputAddCollection.each(function (i) {
	    	jQuery(this).attr("id", "add_field" + idIndex);

	    	var button_div = jQuery(this).parent().parent()[0]; // это блок внутри которого находится кнопка
	    	var form       = jQuery(this).parent().parent().parent()[0]; // это форма
	    	var newElement = document.createElement("div");
				
			newElement.setAttribute('class', 'uk-form-row form-group');
			newElement.setAttribute('already', 'yes');
			// найти свое корневое поле
			var rootNumber = jQuery(button_div.previousSibling.previousSibling.childNodes[1].parentNode.nextSibling.nextSibling.childNodes[3].childNodes[1]).attr("data-showon").replace("=*", "");
			newElement.innerHTML = "<a onclick='insurance(this);' hide-checkbox-number='" + rootNumber + "' name='add_button' id='add_button" + idIndex + "' style='cursor: pointer'>" + jQuery(this).attr("add-label") + "</a>";
			// найти чекбох еси он есть
			if (jQuery("checkbox[name='field" + rootNumber + "']").attr("checked") != "checked")
				jQuery(newElement).hide();

			insertAfter(newElement, button_div);

			idIndex++;
	    });
	}

	jQuery("input[type='checkbox']").change(function() {
		var checkboxName = jQuery(this).attr('name').replace("field", "");
		var input =  jQuery("input[hide-checkbox-number='" + checkboxName + "']").parent().parent();
		var a =  jQuery("a[hide-checkbox-number='" + checkboxName + "']").parent();

	  	if (jQuery(this).attr('checked') == 'checked') {
	  		// получить имя поля и срыть все поля с его номером по атрибуту hide-checkbox-number
	  		input.show();
	  		a.show();
	  	} else {
	  		input.hide();
	  		a.hide();
	  	}
	});


    jQuery("input[type='Button']").click(function () {
    	doubleCallFixFlag++;

    	if (doubleCallFixFlag == 1) {
    		// найти имя последнего поля в форме
	    	var form = jQuery(this).parent().parent().parent()[0];
	    	var lastInput = jQuery(form).find("input");
	    	let lastInputNumber = Number(lastInput.length) - 3;
	    	//console.log(lastInput.length - 2);

    		var button_div = jQuery(this).parent().parent()[0]; // это блок внутри которого находится кнопка
	    	// добавить в эту форму скрытое поле для опознания
	    	var form = jQuery(this).parent().parent().parent()[0];

			var newElement = document.createElement("div");
			newElement.setAttribute('class', 'uk-form-row form-group');
			//form.appendChild(newElement);
			form.insertBefore(newElement, button_div);

			var newDiv = document.createElement("div");
			newDiv.setAttribute('class', 'uk-form-controls');
			newElement.appendChild(newDiv);

			// теперь до блока newDiv нужно вставить label
			var newLabel = document.createElement("label");
			newLabel.setAttribute('class', 'uk-form-label');
			newElement.insertBefore(newLabel, newDiv);
			
			var newInput = document.createElement("input");
			newInput.setAttribute('class', 'form-control');
			newInput.setAttribute('type', 'text');
			newInput.setAttribute('name', 'field' + lastInputNumber);
			newDiv.appendChild(newInput);

			jQuery(".add_more").text("add");
		}

		if (doubleCallFixFlag == 2) {
			doubleCallFixFlag = 0;
		}

		console.log(doubleCallFixFlag);
    });
</script>