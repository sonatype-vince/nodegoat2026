<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class EnucleateValueTypeModuleFilecard extends EnucleateValueTypeModuleBase {
	
	protected static $str_type = 'filecard';
		
	protected function createModuleTemplate() {
		
		$str_html_fields = '';
		$arr_fields = static::getModuleValueFields();
		
		$arr_check = $this->arr_value;
		
		$str_recognition = ($arr_check['recognition'] ?? '');
		$str_recognition = (is_array($str_recognition) ? value2JSON($str_recognition) : $str_recognition);
		unset($arr_check['recognition']);
		
		// Main fields
		
		foreach ($arr_fields as $str_identifier => $arr_field) {
			
			if (!bitHasMode($arr_field['mode'], parent::FIELD_MODE_INPUT)) {
				continue;
			}
			
			$str_value = ($arr_check[$str_identifier] ?? '');
			unset($arr_check[$str_identifier]);
			
			if (is_array($str_value)) {
				$str_value = value2JSON($str_value);
			}
			
			$str_html_fields .= '<li>
				<label>'.$arr_field['name'].'</label>
				<div><input type="text" name="'.$this->str_template_name.'['.strEscapeHTML($str_identifier).']" value="'.strEscapeHTML($str_value).'" placeholder="??" /></div>
			</li>';
		}
		
		// Custom fields from Object
		
		foreach ($arr_check as $str_identifier => $str_value) {
			
			if (is_array($str_value)) {
				$str_value = value2JSON($str_value);
			}
			
			$str_html_fields .= '<li>
				<label>'.strEscapeHTML($str_identifier).'</label>
				<div><input type="text" name="'.$this->str_template_name.'['.strEscapeHTML($str_identifier).']" value="'.strEscapeHTML($str_value).'" /></div>
			</li>';
		}
		
		// Put main field for recognition below
		
		$str_html_fields .= '<li>
			<label>'.$arr_fields['recognition']['name'].'</label>
			<div><textarea name="'.$this->str_template_name.'[recognition]">'.strEscapeHTML($str_recognition).'</textarea></div>
		</li>';
		
		$return = '<fieldset>
			<ul>
				'.$str_html_fields.'
			</ul>
		</fieldset>';

		return $return;
	}
	
	protected function enucleateModule($mode, $str_field = null) {
		
		$return = '';
		$arr_fields = static::getModuleValueFields();
		
		if ($str_field !== null) {
			
			if ($mode == static::VIEW_TEXT) {
				
				$return = ($this->arr_value[$str_field] ?? '');
				$return = (is_array($return) ? value2JSON($return) : $return);
			}
		} else {
			
			$arr_check = $this->arr_value;
			
			$str_recognition = ($arr_check['recognition'] ?? '');
			$str_recognition = (is_array($str_recognition) ? value2JSON($str_recognition) : $str_recognition);
			unset($arr_check['recognition']);
			
			// Main fields
			
			foreach ($arr_fields as $str_identifier => $arr_field) {
				
				if (!bitHasMode($arr_field['mode'], static::FIELD_MODE_VIEW) || !isset($arr_check[$str_identifier])) {
					continue;
				}
				
				$str_value = ($arr_check[$str_identifier] ?? '');
				unset($arr_check[$str_identifier]);
				
				if (is_array($str_value)) {
					$str_value = value2JSON($str_value);
				}
				
				if ($mode == static::VIEW_TEXT) {
					$return .= $str_identifier.': '.$str_value.EOL_1100CC;
				} else {
					$return .= '<dt>'.$arr_field['name'].'</dt><dd>'.$str_value.'</dd>';
				}
			}
			
			// Custom fields from Object
			
			foreach ($arr_check as $str_identifier => $str_value) {
				
				if (is_array($str_value)) {
					$str_value = value2JSON($str_value);
				}
				
				if ($mode == static::VIEW_TEXT) {
					$return .= $str_identifier.': '.$str_value.EOL_1100CC;
				} else {
					$return .= '<dt>'.strEscapeHTML($str_identifier).'</dt><dd>'.$str_value.'</dd>';
				}
			}
			
			// Put main field and image for recognition below
			
			if ($mode == static::VIEW_TEXT) {
				
				if ($str_recognition) {
					$return .= 'recognition: '.$str_recognition.EOL_1100CC;
				}
			} else {
					
				$str_html_image = '';
				$arr_size = [];
				
				if ($this->str_context) {
					
					$media = new EnucleateMedia($this->str_context, DIR_HOME_TYPE_OBJECT_MEDIA, '/'.DIR_TYPE_OBJECT_MEDIA);
					$arr_size = $media->getSizing();
					$media->setSizing(1000, false);
					
					$str_html_image = $media->enucleate();
				}
				
				$return = '<dl>'.$return.'</dl>'
					.'<div class="image" data-width="'.$arr_size['width'].'" data-height="'.$arr_size['height'].'" data-recognition="'.strEscapeHTML($str_recognition).'">'.$str_html_image.'</div>';
			}
		}
		
		return $return;
	}
	
	protected function parseModuleTemplate() {
		
		if (!empty($this->arr_value['recognition']) && !is_array($this->arr_value['recognition'])) {
			$this->arr_value['recognition'] = JSON2Value($this->arr_value['recognition']);
		}
		
		foreach ($this->arr_value as $key => $value) {
			
			if ($value === null || $value === '' || $value === []) {
				unset($this->arr_value[$key]);
			}
		}

		return $this->arr_value;
	}
	
	protected static function getModuleValueFields() {
		
		$arr_fields = [
			'name' => ['name' => getLabel('lbl_name'), 'type' => '', 'path' => '$.name', 'mode' => static::FIELD_MODE_VIEW | static::FIELD_MODE_FILTER | static::FIELD_MODE_INPUT]
		];
		
		Settings::get('value_type_module_filecard', null, [&$arr_fields]);
		
		$arr_fields += [
			'recognition' => ['name' => getLabel('lbl_value_type_module_filecard_recognition'), 'type' => '', 'path' => '$.recognition', 'mode' => static::FIELD_MODE_VIEW | static::FIELD_MODE_FILTER]
		];

		return $arr_fields;
	}
	
	public static function updateValueTypeSettings(&$arr_value_type_settings, $arr_description): void {
		
		if ($arr_description === null) {
			
			$arr_value = [];
			
			if ($arr_value_type_settings['object_description_id']) {
				$arr_value['object_description_id'] = (int)$arr_value_type_settings['object_description_id'];
			}
			
			$arr_value_type_settings = $arr_value;
		} else {
			
			if ($arr_value_type_settings['object_description_id']) {
				$arr_value_type_settings['context']['object_description_id'] = (int)$arr_value_type_settings['object_description_id']; // Set the selected Object Description as the module's 'context'
			}
		}
	}
	
	public static function createValueTypeOptions($arr_value_type_settings, $str_name_settings, $arr_type_set) {
		
		$arr_object_descriptions = [];
				
		foreach ($arr_type_set['object_descriptions'] as $collect_object_description_id => $arr_collect_object_description) {
		
			if ($arr_collect_object_description['object_description_value_type'] != 'media') {
				continue;
			}
			
			$arr_object_descriptions[] = $arr_collect_object_description;
		}
		
		$str_html = '<select name="'.$str_name_settings.'[object_description_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_object_descriptions, ($arr_value_type_settings['object_description_id'] ?: null), false, 'object_description_name', 'object_description_id')).'</select>';
		
		$arr_html = [getLabel('lbl_media').' '.getLabel('lbl_object_description') => $str_html];
		
		return $arr_html;
	}
	
	protected static function getModuleStyle() {
		
		return '
			.data_viewer '.static::STYLE_CLASS_ELEMENT.'.filecard dt { font-weight: bold; }
			.data_viewer '.static::STYLE_CLASS_ELEMENT.'.filecard dd { margin-left: 2em; }
			
			.data_viewer '.static::STYLE_CLASS_ELEMENT.'.filecard > dl + .image { margin-top: 10px; }
			.data_viewer '.static::STYLE_CLASS_ELEMENT.'.filecard > .image { width: min(100%, 600px); padding: 20px; position: relative; border: 1px solid #ccc; overflow: hidden; resize: both; }
			.data_viewer '.static::STYLE_CLASS_ELEMENT.'.filecard > .image > img,
			.data_viewer '.static::STYLE_CLASS_ELEMENT.'.filecard > .image > svg { width: 100%; height: auto; display: block; }

			.entry '.static::STYLE_CLASS_ELEMENT.'.filecard input[type=text] { max-width: 200px; }
			.entry '.static::STYLE_CLASS_ELEMENT.'.filecard textarea[name$="\[recognition\]"] { width: 350px; }
			.entry '.static::STYLE_CLASS_ELEMENT.'.filecard .image { max-width: 500px; }
		';
	}
	
	protected static function getModuleScriptTemplate() {
		
		return '';
	}
	
	protected static function getModuleScriptEnucleate() {
		
		return "
			const elm_module = ".static::SCRIPT_ELEMENT.";
			const elm_container = elm_module.find('.image');
			const elm_image = elm_container.children('img');
			
			if (!elm_image[0]) {
				return;
			}
			
			const str_src = elm_image[0].src;
			let arr_data = elm_container[0].dataset.recognition;
			
			if (!arr_data) {
				return;
			}
			
			arr_data = JSON.parse(arr_data);
			
			if (!arr_data) {
				return;
			}
						
			// Determine image size and scaling
			
			const num_width = elm_container[0].dataset.width;
			const num_height = elm_container[0].dataset.height;
			
			let num_ratio_width = 1;
			let num_ratio_height = 1;
			
			if (arr_data.image_dimensions) {
				
				if (arr_data.image_dimensions.width != undefined) { // Image sizing
				
					num_ratio_width = (num_width / arr_data.image_dimensions.width);
					num_ratio_height = (num_height / arr_data.image_dimensions.height);
				} else if (arr_data.image_dimensions[3] != undefined) { // Image bbox
				
					num_ratio_width = (num_width / arr_data.image_dimensions[2]);
					num_ratio_height = (num_height / arr_data.image_dimensions[3]);
				}
			}
			
			// Collect drawable items
			
			let arr_items = [];
			
			if (arr_data.segments) {
				
				arr_data.segments.forEach(arr_segment => {
					
					// If the segment contains segment.text_lines, use these more specific ones, otherwise just use the default segment.coordinates
					
					if (arr_segment.text_lines && Array.isArray(arr_segment.text_lines)) {
						arr_items = arr_items.concat(arr_segment.text_lines);
					} else {
						arr_items.push(arr_segment);
					}
				});
			} else if (arr_data.pairs) {
			
				for (key in arr_data.pairs) {
					
					const arr_bbox = arr_data.pairs[key];
					
					if (arr_bbox && Array.isArray(arr_bbox)) {
						arr_items.push({text: key, bbox: arr_bbox});
					}
				}
			}
			
			// Create SVG
			
			const stage_ns = 'http://www.w3.org/2000/svg';
			const elm_svg = document.createElementNS(stage_ns, 'svg');
			
			elm_svg.setAttribute('viewBox', '0 0 '+num_width+' '+num_height);
			elm_svg.setAttribute('width', num_width);
			elm_svg.setAttribute('height', num_height);
			
			const elm_style = document.createElementNS(stage_ns, 'style');
			elm_style.textContent = `
				.segment {
					fill: rgba(0, 150, 255, 0.2);
					stroke: #007bff;
					stroke-width: 1.5px;
					cursor: pointer;
					transition: all 0.2s ease-in-out;
				}
				.segment:hover {
					fill: rgba(0, 255, 100, 0.4);
					stroke: #00ff66;
					stroke-width: 2.5px;
				}
			`;
			elm_svg.appendChild(elm_style);
			
			const elm_background = document.createElementNS(stage_ns, 'image');
			elm_background.setAttribute('href', str_src);
			elm_background.setAttribute('width', num_width);
			elm_background.setAttribute('height', num_height);
			elm_background.setAttribute('x', '0');
			elm_background.setAttribute('y', '0');
			
			elm_svg.appendChild(elm_background);
			
			arr_items.forEach(arr_item => {
			
				const elm_polygon = document.createElementNS(stage_ns, 'polygon');
				
				if (arr_item.coordinates && arr_item.coordinates[0]) { // A full segment polygon (coordinates)
				
					str_points = arr_item.coordinates[0]
						.map(arr_point => (arr_point.x * num_ratio_width)+','+(arr_point.y * num_ratio_height))
						.join(' ');
				} else if (arr_item.bbox && arr_item.bbox[3] != undefined) { // A text line (bbox)
				
					// Convert [xmin, ymin, xmax, ymax] into 4 points
					const num_xmin = (arr_item.bbox[0] * num_ratio_width);
					const num_ymin = (arr_item.bbox[1] * num_ratio_height);
					const num_xmax = (arr_item.bbox[2] * num_ratio_width);
					const num_ymax = (arr_item.bbox[3] * num_ratio_height);
					
					str_points = num_xmin+','+num_ymin+' '+num_xmax+','+num_ymin+' '+num_xmax+','+num_ymax+' '+num_xmin+','+num_ymax;
				} else {
				
					return; // Skip if we don't have valid position data
				}
				
				elm_polygon.setAttribute('points', str_points);
				elm_polygon.setAttribute('class', 'segment');
				
				const str_text = (arr_item.text || arr_item.text_content || '');
				
				// Store metadata on the element (useful if you want to click and alert the confidence/text)
				elm_polygon.dataset.label = (arr_item.label || 'text_line');
				elm_polygon.dataset.confidence = (arr_item.confidence ? arr_item.confidence : null);
				
				if (str_text) {
					elm_polygon.dataset.text = str_text;
				}
				
				// Title tag
				let arr_title = [];
				if (arr_item.confidence) {
					arr_title.push('<b>Confidence</b>: '+((arr_item.confidence * 100).toFixed(1))+'%');
				}
				if (str_text) {
					arr_title.push('<b>Text</b>: \"'+str_text+'\"');
				}
				elm_polygon.setAttribute('title', arr_title.join('<br/>'));
				
				elm_svg.appendChild(elm_polygon);
			});

			elm_image[0].classList.add('hide');
			elm_container[0].appendChild(elm_svg);
		";
	}
}
