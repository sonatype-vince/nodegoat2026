<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class EnucleateValueTypeModule {
	
	const VIEW_HTML = 1;
	const VIEW_TEXT = 2;
	
	const FIELD_MODE_VIEW = 1;
	const FIELD_MODE_FILTER = 2;
	const FIELD_MODE_INPUT = 4;
	
	protected static $arr_modules = [ // Add all possible modules to this list
		'music_notation' => true,
		'filecard' => true
	];
	
	public static function enable($type) {
		
		static::$arr_modules[$type] = true;
	}
	public static function disable($type) {
		
		static::$arr_modules[$type] = false;
	}
	
	public static function getClassName($type) {
		
		$str_class = get_class().str_replace('_', '', ucwords($type, '_'));
		
		return $str_class;
	}
	
	public static function parseTypeObjectDescriptionValueTypeOptions($arr_value_type_settings, $arr_description = null) { // For applying additional value type settings
		
		$type = ($arr_value_type_settings['type'] ?? '');
		
		if (!$type) {
			return $arr_value_type_settings;
		}
		
		$str_class = static::getClassName($type);
		$str_class::updateValueTypeSettings($arr_value_type_settings, $arr_description);
		
		return $arr_value_type_settings;
	}
	
	public static function createTypeObjectDescriptionValueTypeOptions($arr_value_type_settings, $str_name_settings, $arr_type_set) { // For offering additional value type options
		
		$type = ($arr_value_type_settings['type'] ?? '');
		
		if (!$type) {
			return;
		}
		
		$str_class = static::getClassName($type);
		$arr_html = $str_class::createValueTypeOptions($arr_value_type_settings, $str_name_settings, $arr_type_set);
		
		return $arr_html;
	}
	
	public static function formatToJSON($type, $value) { // For storing in the database
		
		if (!$type) {
			return null;
		}
		
		if (is_array($value)) {
			
			$value = value2JSON($value);
		} else {
			
			if ($value[0] !== '{' || $value[strlen($value)-1] !== '}') {
			
				$num_pos_open = strpos($value, '{');
				$num_pos_close = ($num_pos_open !== false ? strpos($value, '}', $num_pos_open) : false);
				
				if ($num_pos_close === false) {
					$value = null;
				} else {
					$value = value2JSON(JSON2Value(substr($value, $num_pos_open, $num_pos_close+1-$num_pos_open))); // Do extra JSON parse-cleanup
				}
			} else {
				
				$value = $value;
			}
			
			$str_class = static::getClassName($type);
			$str_class::checkJSON($value);
		}
		
		if ($value === null || $value === 'null' || $value === '[]') {
			return null;
		}
		
		return $value;
	}
		
	public static function init($type) {
		
		$is_active = (static::$arr_modules[$type] ?? null);
		
		if (!$is_active) {
			error(getLabel('msg_object_description_value_type_missing'));
		}
		
		$str_class = static::getClassName($type);
		
		$class = new $str_class;
		
		return $class;
	}
		
	public static function iterateModules() {
				
		foreach (static::$arr_modules as $type => $is_active) {
			
			if (!$is_active) {
				continue;
			}
			
			$str_class = static::getClassName($type);
			
			yield $type => $str_class;
		}
	}
	
	public static function getModules() {
		
		$arr = [];
		
		foreach (static::iterateModules() as $type => $str_class) {
						
			$arr[$type] = ['id' => $type, 'name' => $str_class::getName()];
		}
		
		return $arr;
	}
	
	public static function getModulesStyles() {
		
		$str = '';
		
		foreach (static::iterateModules() as $type => $str_class) {
			
			$str .= $str_class::getStyle();
		}
		
		return $str;
	}
	
	public static function getModulesScripts() {
		
		$str = '';
		
		foreach (static::iterateModules() as $type => $str_class) {
			
			$str .= $str_class::getScript();
		}
		
		return $str;
	}
}
