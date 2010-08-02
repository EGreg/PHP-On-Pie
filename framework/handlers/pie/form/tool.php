<?php

/**
 * This tool is meant to be wrapped in a <form> tag
 * @param array $params
 *  An associative array of parameters, containing:
 *  "fields" => an associative array of fieldname => fieldinfo pairs,
 *   where fieldinfo contains the following:
 *     "type" => the type of the field (@see Pie_Html::smartTag())
 *     "attributes" => additional attributes for the field input
 *     "value" => the initial value of the field input
 *     "options" => options for the field input (if type is "select", "checkboxes" or "radios")
 *     "message" => initial message, if any to put in the field's message space
 */
function pie_form_tool($params)
{
	if (empty($params['fields'])) {
		return '';
	}
	
	$field_defaults = array(
		'type' => 'text',
		'attributes' => array(),
		'value' => null,
		'options' => array(),
		'message' => ''
	);
	$tr_array = array();
	foreach ($params['fields'] as $name => $field) {
		if (!is_array($field)) {
			$name2 = '"'.addslashes($name).'"';
			throw new Pie_Exception_WrongType(array(
				'field' => "\$params[$name2]",
				'type' => 'array'
			));
		}
		$field2 = array_merge($field_defaults, $field);
		$type = $field2['type'];
		$attributes = $field2['attributes'];
		$value = $field2['value'];
		$options = $field2['options'];
		$message = $field2['message'];
		$attributes['name'] = $name;
		if (ctype_alnum($type)) {
			if (isset($attributes['class'])) {
				if (is_array($attributes['class'])) {
					foreach ($attributes['class'] as $k => $v) {
						$attributes['class'][$k] .= " $type";
					}
				} else {
					$attributes['class'] .= " $type";
				}
			} else {
				$attributes['class'] = " $type";
			}
		}
		switch ($type) {
			case 'textarea':
				$tr_rest = "<td class='pie_form_fieldinput' colspan='2'>"
					. Pie_Html::smartTag($type, $attributes, $value, $options)
					. "</td></tr><tr><td class='pie_form_placeholder'>"
					. "</td><td class='pie_form_undermessage pie_form_textarea_undermessage' colspan='2'>"
					. "<div class='pie_form_undermessagebubble'>$message</div></td>";
				break;
			default:
				$tr_rest = "<td class='pie_form_fieldinput'>"
 					. Pie_Html::smartTag($type, $attributes, $value, $options)
 					. "</td><td class='pie_form_fieldmessage pie_form_{$type}_message'>$message</td>"
					. "</tr><tr><td class='pie_form_placeholder'>"
					. "</td><td class='pie_form_undermessage pie_form_{$type}_undermessage' colspan='2'>"
					. "<div class='pie_form_undermessagebubble'></div></td>";
				break;
		}
		$label = isset($field['label']) ? $field['label'] : $name;
		$name_text = Pie_Html::text($name);
		$tr_array[] = "<tr><td class='pie_form_fieldname' data-fieldname=\"$name_text\">$label</td>$tr_rest</tr>";
	}
	$result = "<table class='pie_form_tool_table' cellspacing='0'>\n"
		.implode("\n\t", $tr_array)
		."\n</table>";
	Pie_Response::addScript('plugins/pie/js/PieTools.js');
	return $result;
}
