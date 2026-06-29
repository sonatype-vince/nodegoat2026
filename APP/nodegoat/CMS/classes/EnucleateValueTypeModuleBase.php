<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

abstract class EnucleateValueTypeModuleBase extends EnucleateValueTypeModule {
	
	protected static $str_type;
	
	protected $arr_value = [];
	protected $str_context = null;
	protected $arr_settings = [];
	
	protected $str_template_name = '';
	protected $arr_template_validate = [];
	
	const STYLE_CLASS_ELEMENT = '.value-type-module';
	const SCRIPT_ELEMENT = 'elm_scripter';
		
    public function __construct($arr_value = [], $arr_settings = []) {
		
		$this->setValue($arr_value);
		$this->setConfiguration($arr_settings);
	}
	
	public function setValue($arr_value, $str_context = null) {
		
		$this->arr_value = $arr_value;
		$this->str_context = $str_context;
	}
	public function setConfiguration($arr_settings) {
		
		$this->arr_settings = $arr_settings;
	}
	
	public function createTemplate($str_template_name) {
		
		$this->str_template_name = $str_template_name;
		
		$html_template = $this->createModuleTemplate();
				
		$return = '<div class="value-type-module '.static::$str_type.' template" data-form_name="'.$this->str_template_name.'">
		
			<div class="options">
				 '.$html_template.'
			</div>
			
		</div>';
		
		return $return;
	}
	
	abstract protected function createModuleTemplate();
	
	public function getTemplateValidate() {
				
		return $this->arr_template_validate;
	}
	
	public function parseTemplate() {
		
		$arr_value = $this->parseModuleTemplate();
		
		return $arr_value;
	}
	
	abstract protected function parseModuleTemplate();
	
	public function enucleate($mode = parent::VIEW_HTML, $str_field = null) {
		
		$return = $this->enucleateModule($mode, $str_field);
		
		if ($mode == parent::VIEW_HTML && $return) {
			$return = '<div class="value-type-module '.static::$str_type.' enucleate">'.$return.'</div>';
		}
		
		return $return;
	}
	
	abstract protected function enucleateModule($mode, $str_field);
	
	public static function getValueFields($mode = null) {
		
		$arr = [
			'any' => ['name' => getLabel('lbl_any'), 'path' => '$', 'type' => '', 'mode' => parent::FIELD_MODE_FILTER]
		];
		
		$arr += static::getModuleValueFields();
		
		if ($mode !== null) {
			
			foreach ($arr as $key => $value) {
				
				if (!bitHasMode($value['mode'], $mode)) {
					unset($arr[$key]);
				}
			}
		}
		
		return $arr;
	}
	
	abstract protected static function getModuleValueFields();
	
	public static function updateValueTypeSettings(&$arr_value_type_settings, $arr_description): void {
		
		// Process/extend the Object Description's value type settings
		
		if ($arr_description === null) { // Parse when storing the Object Description

			$arr_value = [];
			
			$arr_value_type_settings = $arr_value;
		} else { // Update the Object Description
			
		}
	}
	
	public static function createValueTypeOptions($arr_value_type_settings, $str_name_settings, $arr_type_set) {
		
		// Extend the Object Description's value type options with an array of label => html
		
		return;
	}
	
	public static function checkJSON(&$str_json): void {
		
		// Parse/process the JSON to be stored
	}
	
	public static function getName() {
		
		return getLabel('lbl_object_description_value_type_'.static::$str_type);
	}
	
	public static function getStyle() {
		
		return static::getModuleStyle();
	}
	
	protected static function getModuleStyle() {
		return '';
	}
	
	public static function getScript() {
		
		$str_script = "";
		
		$str_script_template = static::getModuleScriptTemplate();
		
		if ($str_script_template) {
			
			$str_script .= "
				SCRIPTER.dynamic('.value-type-module.".static::$str_type.".template', function(".static::SCRIPT_ELEMENT.") {
					".$str_script_template."
				});
			";
		}
		
		$str_script_enucleate = static::getModuleScriptEnucleate();
		
		if ($str_script_enucleate) {
			
			$str_script .= "
				SCRIPTER.dynamic('.value-type-module.".static::$str_type.".enucleate', function(".static::SCRIPT_ELEMENT.") {
					".$str_script_enucleate."
				});
			";
		}
				
		return $str_script;
	}
	
	protected static function getModuleScriptTemplate() {
		return '';
	}
	
	protected static function getModuleScriptEnucleate() {
		return '';
	}
}
