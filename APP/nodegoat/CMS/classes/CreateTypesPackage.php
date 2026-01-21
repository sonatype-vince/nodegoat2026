<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class CreateTypesPackage {
	
	const MODE_DEFAULT = 0;
	const MODE_TEMPLATE = 1;
	
	
	protected $user_id = false;
	protected $project_id = false;
	protected $arr_project = [];
	protected $is_administrator = false;
	
	protected $mode = self::MODE_DEFAULT;
	
	protected $arr_config = [];
	protected $arr_config_collect = [];
	
	public function __construct($user_id, $project_id, $is_administrator = false) {
		
		
		$this->user_id = $user_id;
		
		$this->is_administrator = (bool)$is_administrator;
		
		if ($project_id) {

			$this->arr_project = StoreCustomProject::getProjects($project_id);
			$this->project_id = $this->arr_project['project']['id'];
		}
	}
	
	public function setMode($mode = self::MODE_DEFAULT) {
		
		$this->mode = (int)$mode;
	}
	
	public function setConfigCollect(&$arr_config = [], &$arr_collect = []) {
		
		$this->arr_config =& $arr_config;
		$this->arr_config_collect =& $arr_collect;
	}
	
	public function parseTypes($arr_type_ids, $num_nodegoat_clearance = 0) {
		
		if ($this->mode == static::MODE_TEMPLATE) {
			$store_type = new StoreType(false, $this->user_id);
		}
		
		$arr_date_options = StoreType::getDateOptions();
		$arr_location_options = StoreType::getLocationOptions();
		
		$arr_types = [];
		
		foreach ($arr_type_ids as $type_id) {
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			
			if ($arr_type_set['type']['class'] == StoreType::TYPE_CLASS_SYSTEM || ($this->mode == static::MODE_TEMPLATE && $arr_type_set['type']['class'] == StoreType::TYPE_CLASS_REVERSAL)) {
				continue;
			}
						
			$use_type_id = ($this->mode == static::MODE_TEMPLATE ? $arr_type_set['type']['name'] : (int)$type_id);
			
			$s_arr_type =& $arr_types[$use_type_id];
			
			$s_arr_type = ['type' => [], 'object_descriptions' => [], 'object_sub_details' => []];
			
			$s_arr_type['type'] = [
				'id' => $use_type_id,
				'class' => (int)$arr_type_set['type']['class'],
				'name' => $arr_type_set['type']['name'],
				'color' => $arr_type_set['type']['color']
			];

			if ($this->is_administrator) {
				
				$s_arr_type['type'] += [
					'use_object_name' => (bool)$arr_type_set['type']['use_object_name'],
					'object_name_in_overview' => (bool)$arr_type_set['type']['object_name_in_overview']
				];
			}
			
			foreach ($arr_type_set['definitions'] as $definition_id => $arr_definition) {
				
				$definition_id = (int)$definition_id;
				
				if ($this->mode == static::MODE_TEMPLATE) {
					$definition_id = $arr_definition['definition_name'];
				}
				
				$s_arr_type['definitions'][$definition_id] = [
					'definition_id' => $definition_id,
					'definition_name' => $arr_definition['definition_name'],
					'definition_text' => $arr_definition['definition_text']
				];
			}
            
			foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				$object_description_id = (int)$object_description_id;

				if (!StoreType::checkTypeConfigurationUserClearance($arr_type_set, $num_nodegoat_clearance, $object_description_id, false, false, StoreType::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess(($this->arr_project['types'] ?? []), $arr_type_set, $object_description_id, false, false, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
					continue;
				}
				
				
				if ($this->mode == static::MODE_TEMPLATE) {
					$object_description_id = $arr_object_description['object_description_name'];
				}
				
				$object_description_ref_type_id = null;
				
				if ($arr_object_description['object_description_ref_type_id']) {
					
					$object_description_ref_type_id = arrParseRecursive($arr_object_description['object_description_ref_type_id'], TYPE_INTEGER);
					if (is_array($object_description_ref_type_id)) {
						$object_description_ref_type_id = array_values($object_description_ref_type_id);
					}
					
					if ($this->mode == static::MODE_TEMPLATE) {
						
						if (is_array($object_description_ref_type_id)) {
							
							foreach ($object_description_ref_type_id as &$ref_type_id) {
								$ref_type_id = $store_type->getTypeName($ref_type_id);
							}
							unset($ref_type_id);
						} else {
							
							$object_description_ref_type_id = $store_type->getTypeName($object_description_ref_type_id);
						}
					}
				}
				
				$s_arr_type['object_descriptions'][$object_description_id] = [
					'object_description_id' => $object_description_id,
					'object_description_name' => $arr_object_description['object_description_name'],
					'object_description_value_type_base' => $arr_object_description['object_description_value_type_base'],
					'object_description_value_type_settings' => $arr_object_description['object_description_value_type_settings'],
					'object_description_is_required' => (bool)$arr_object_description['object_description_is_required'],
					'object_description_is_unique' => (bool)$arr_object_description['object_description_is_unique'],
					'object_description_is_identifier' => (bool)$arr_object_description['object_description_is_identifier'],
					'object_description_has_multi' => (bool)$arr_object_description['object_description_has_multi'],
					'object_description_ref_type_id' => $object_description_ref_type_id
				];
				
				if ($this->is_administrator) {
					
					$s_arr_type['object_descriptions'][$object_description_id] += [
						'object_description_in_name' => (bool)$arr_object_description['object_description_in_name'],
						'object_description_in_search' => (bool)$arr_object_description['object_description_in_search'],
						'object_description_in_overview' => (bool)$arr_object_description['object_description_in_overview'],
						'object_description_clearance_view' => (int)$arr_object_description['object_description_clearance_view'],
						'object_description_clearance_edit' => (int)$arr_object_description['object_description_clearance_edit']
					];
				}
			}
			
			foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				$object_sub_details_id = (int)$object_sub_details_id;
				
				if (!StoreType::checkTypeConfigurationUserClearance($arr_type_set, $num_nodegoat_clearance, false, $object_sub_details_id, false, StoreType::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess(($this->arr_project['types'] ?? []), $arr_type_set, false, $object_sub_details_id, false, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
					continue;
				}
				
				$original_object_sub_details_id = $object_sub_details_id; // Could be changed due to mode 'template'
				$arr_object_sub_details_self = $arr_object_sub_details['object_sub_details'];

				$object_sub_details_date_use_object_sub_details_id = ((int)$arr_object_sub_details_self['object_sub_details_date_use_object_sub_details_id'] ?: null);
				$object_sub_details_date_start_use_object_sub_description_id = ((int)$arr_object_sub_details_self['object_sub_details_date_start_use_object_sub_description_id'] ?: null);
				$object_sub_details_date_start_use_object_description_id = ((int)$arr_object_sub_details_self['object_sub_details_date_start_use_object_description_id'] ?: null);
				$object_sub_details_date_end_use_object_sub_description_id = ((int)$arr_object_sub_details_self['object_sub_details_date_end_use_object_sub_description_id'] ?: null);
				$object_sub_details_date_end_use_object_description_id = ((int)$arr_object_sub_details_self['object_sub_details_date_end_use_object_description_id'] ?: null);
				$object_sub_details_location_ref_type_id = ((int)$arr_object_sub_details_self['object_sub_details_location_ref_type_id'] ?: null);
				$object_sub_details_location_ref_object_sub_details_id = ((int)$arr_object_sub_details_self['object_sub_details_location_ref_object_sub_details_id'] ?: null);
				$object_sub_details_location_use_object_sub_details_id = ((int)$arr_object_sub_details_self['object_sub_details_location_use_object_sub_details_id'] ?: null);
				$object_sub_details_location_use_object_sub_description_id = ((int)$arr_object_sub_details_self['object_sub_details_location_use_object_sub_description_id'] ?: null);
				$object_sub_details_location_use_object_description_id = ((int)$arr_object_sub_details_self['object_sub_details_location_use_object_description_id'] ?: null);
				
				if ($this->mode == static::MODE_TEMPLATE) {

					$object_sub_details_date_use_object_sub_details_id = $store_type->getTypeObjectSubDetailsName($type_id, $object_sub_details_date_use_object_sub_details_id);
					$object_sub_details_date_start_use_object_sub_description_id = $store_type->getTypeObjectSubDescriptionName($type_id, $object_sub_details_id, $object_sub_details_date_start_use_object_sub_description_id);
					$object_sub_details_date_start_use_object_description_id = $store_type->getTypeObjectDescriptionName($type_id, $object_sub_details_date_start_use_object_description_id);
					$object_sub_details_date_end_use_object_sub_description_id = $store_type->getTypeObjectSubDescriptionName($type_id, $object_sub_details_id, $object_sub_details_date_end_use_object_sub_description_id);
					$object_sub_details_date_end_use_object_description_id = $store_type->getTypeObjectDescriptionName($type_id, $object_sub_details_date_end_use_object_description_id);
					$object_sub_details_location_ref_object_sub_details_id = $store_type->getTypeObjectSubDetailsName($object_sub_details_location_ref_type_id, $object_sub_details_location_ref_object_sub_details_id);
					$object_sub_details_location_ref_type_id = $store_type->getTypeName($object_sub_details_location_ref_type_id);
					$object_sub_details_location_use_object_sub_details_id = $store_type->getTypeObjectSubDetailsName($type_id, $object_sub_details_location_use_object_sub_details_id);
					$object_sub_details_location_use_object_sub_description_id = $store_type->getTypeObjectSubDescriptionName($type_id, $object_sub_details_id, $object_sub_details_location_use_object_sub_description_id);
					$object_sub_details_location_use_object_description_id = $store_type->getTypeObjectDescriptionName($type_id, $object_sub_details_location_use_object_description_id);
					$object_sub_details_id = $arr_object_sub_details_self['object_sub_details_name'];
				}
				
				$s_arr_object_sub_details =& $s_arr_type['object_sub_details'][$object_sub_details_id];
				
				$s_arr_object_sub_details = ['object_sub_details' => [], 'object_sub_descriptions' => []];
				
				$s_arr_object_sub_details['object_sub_details'] = [
					'object_sub_details_id' => $object_sub_details_id,
					'object_sub_details_name' => $arr_object_sub_details_self['object_sub_details_name'],
					'object_sub_details_is_single' => (bool)$arr_object_sub_details_self['object_sub_details_is_single'],
					'object_sub_details_is_required' => (bool)$arr_object_sub_details_self['object_sub_details_is_required'],
					'object_sub_details_has_date' => (bool)$arr_object_sub_details_self['object_sub_details_has_date'],
					'object_sub_details_is_date_period' => (bool)$arr_object_sub_details_self['object_sub_details_is_date_period'],
					'object_sub_details_date_setting' => ($arr_date_options[$arr_object_sub_details_self['object_sub_details_date_setting']]['id'] ?? ''),
					'object_sub_details_date_use_object_sub_details_id' => $object_sub_details_date_use_object_sub_details_id,
					'object_sub_details_date_start_use_object_sub_description_id' => $object_sub_details_date_start_use_object_sub_description_id,
					'object_sub_details_date_start_use_object_description_id' => $object_sub_details_date_start_use_object_description_id,
					'object_sub_details_date_end_use_object_sub_description_id' => $object_sub_details_date_end_use_object_sub_description_id,
					'object_sub_details_date_end_use_object_description_id' => $object_sub_details_date_end_use_object_description_id,
					'object_sub_details_has_location' => (bool)$arr_object_sub_details_self['object_sub_details_has_location'],
					'object_sub_details_location_setting' => ($arr_location_options[$arr_object_sub_details_self['object_sub_details_location_setting']]['id'] ?? ''),
					'object_sub_details_location_ref_type_id' => $object_sub_details_location_ref_type_id,
					'object_sub_details_location_ref_type_id_locked' => (bool)$arr_object_sub_details_self['object_sub_details_location_ref_type_id_locked'],
					'object_sub_details_location_ref_object_sub_details_id' => $object_sub_details_location_ref_object_sub_details_id,
					'object_sub_details_location_ref_object_sub_details_id_locked' => (bool)$arr_object_sub_details_self['object_sub_details_location_ref_object_sub_details_id_locked'],
					'object_sub_details_location_use_object_sub_details_id' => $object_sub_details_location_use_object_sub_details_id,
					'object_sub_details_location_use_object_sub_description_id' => $object_sub_details_location_use_object_sub_description_id,
					'object_sub_details_location_use_object_description_id' => $object_sub_details_location_use_object_description_id,
					'object_sub_details_location_use_object_id' => (bool)$arr_object_sub_details_self['object_sub_details_location_use_object_id']
				];
				
				if ($this->is_administrator) {
					
					$s_arr_object_sub_details['object_sub_details'] += [
						'object_sub_details_clearance_view' => (int)$arr_object_sub_details_self['object_sub_details_clearance_view'],
						'object_sub_details_clearance_edit' => (int)$arr_object_sub_details_self['object_sub_details_clearance_edit']
					];
				}
				
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					$object_sub_description_id = (int)$object_sub_description_id;
					
					if (!StoreType::checkTypeConfigurationUserClearance($arr_type_set, $num_nodegoat_clearance, false, $object_sub_details_id, $object_sub_description_id, StoreType::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess(($this->arr_project['types'] ?? []), $arr_type_set, false, $object_sub_details_id, $object_sub_description_id, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
						continue;
					}
					
					$object_sub_description_use_object_description_id = ((int)$arr_object_sub_description['object_sub_description_use_object_description_id'] ?: null);
					
					if ($this->mode == static::MODE_TEMPLATE) {
						
						$object_sub_description_id = $arr_object_sub_description['object_sub_description_name'];
						$object_sub_description_use_object_description_id = $store_type->getTypeObjectDescriptionName($type_id, $object_sub_description_use_object_description_id);
					}
					
					$object_sub_description_ref_type_id = null;

					if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
					
						$object_sub_description_ref_type_id = arrParseRecursive($arr_object_sub_description['object_sub_description_ref_type_id'], TYPE_INTEGER);
						if (is_array($object_sub_description_ref_type_id)) {
							$object_sub_description_ref_type_id = array_values($object_sub_description_ref_type_id);
						}
						
						if ($this->mode == static::MODE_TEMPLATE) {
							
							if (is_array($object_sub_description_ref_type_id)) {
								
								foreach ($object_sub_description_ref_type_id as &$ref_type_id) {
									$ref_type_id = $store_type->getTypeName($ref_type_id);
								}
								unset($ref_type_id);
							} else {
								
								$object_sub_description_ref_type_id = $store_type->getTypeName($object_sub_description_ref_type_id);
							}
						}
					}
														
					$s_arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id] = [
						'object_sub_description_id' => $object_sub_description_id,
						'object_sub_description_name' => $arr_object_sub_description['object_sub_description_name'],
						'object_sub_description_value_type_base' => $arr_object_sub_description['object_sub_description_value_type_base'],
						'object_sub_description_value_type_settings' => $arr_object_sub_description['object_sub_description_value_type_settings'],
						'object_sub_description_is_required' => (bool)$arr_object_sub_description['object_sub_description_is_required'],
						'object_sub_description_ref_type_id' => $object_sub_description_ref_type_id,
						'object_sub_description_use_object_description_id' => $object_sub_description_use_object_description_id,
					];
					
					if ($this->is_administrator) {
					
						$s_arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id] += [
							'object_sub_description_in_name' => (bool)$arr_object_sub_description['object_sub_description_in_name'],
							'object_sub_description_in_search' => (bool)$arr_object_sub_description['object_sub_description_in_search'],
							'object_sub_description_in_overview' => (bool)$arr_object_sub_description['object_sub_description_in_overview'],
							'object_sub_description_clearance_view' => (int)$arr_object_sub_description['object_sub_description_clearance_view'],
							'object_sub_description_clearance_edit' => (int)$arr_object_sub_description['object_sub_description_clearance_edit']
						];
					}
				}
			}
		}
		unset($s_arr_type, $s_arr_object_sub_details);
		
		return $arr_types;
	}
		
	public function parseTypesOpenAPI($arr_type_ids, $num_nodegoat_clearance = 0) {
		
		if (!$this->project_id) { // All descriptions rely on a Project
			return;
		}
		
		$store_type = new StoreType(false, $this->user_id);
		
		$arr_use_project_ids = array_keys($this->arr_project['use_projects']);
		
		$arr_types_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($this->project_id, $_SESSION['USER_ID'], false, false, ($num_nodegoat_clearance == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids);
		$arr_types_scopes = cms_nodegoat_custom_projects::getProjectTypeScopes($this->project_id, $_SESSION['USER_ID'], false, false, $arr_use_project_ids);
		$arr_types_conditions = cms_nodegoat_custom_projects::getProjectTypeConditions($this->project_id, $_SESSION['USER_ID'], false, false, ($num_nodegoat_clearance == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids);
		
		$this->arr_config_collect['type_X_id'] = [];
		$this->arr_config_collect['type_X_object'] = [];
		$this->arr_config_collect['filter_X_id'] = [];
		$this->arr_config_collect['scope_X_id'] = [];
		$this->arr_config_collect['condition_X_id'] = [];
		
		foreach ($arr_type_ids as $type_id) {
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			
			if ($arr_type_set['type']['class'] == StoreType::TYPE_CLASS_SYSTEM || $arr_type_set['type']['class'] == StoreType::TYPE_CLASS_REVERSAL) {
				continue;
			}
			
			$arr_project_type = $this->arr_project['types'][$type_id];
			
			$this->arr_config['components']['schemas']['model.type_'.$type_id.'_id'] = [
				'const' => (int)$type_id,
				'title' => 'Type ID '.$type_id,
				'description' => Labels::parseTextVariables($arr_type_set['type']['name'])
			];
			
			$this->arr_config_collect['type_X_id'][] = ['$ref' => '#/components/schemas/model.type_'.$type_id.'_id'];

			// Data
			
			$str_identifier = 'type_'.$type_id.'_object';
			
			$this->arr_config_collect['type_X_object'][] = ['$ref' => '#/components/schemas/data.'.$str_identifier];
						
			$this->arr_config['components']['schemas']['data.'.$str_identifier] = [
				'title' => StoreType::getTypeClassObjectName($arr_type_set['type']['class']).' - '.Labels::parseTextVariables($arr_type_set['type']['name']),
				'description' => (parseBody($arr_project_type['type_information']) ?: ''),
				'type' => 'object',
				'properties' => [
					'object' => [
						'type' => 'object',
						'properties' => [
							'nodegoat_id' => ['$ref' => '#/components/schemas/data.nodegoat_id'],
							'object_id' => ['$ref' => '#/components/schemas/data.object_id'],
							'type_id' => ['$ref' => '#/components/schemas/model.type_'.$type_id.'_id'],
							'object_name' => [
								'type' => 'string',
								'description' => 'The name of a '.Labels::parseTextVariables($arr_type_set['type']['name'])
							],
						]
					],
					'object_definitions' => [
						'type' => 'object',
						'properties' => [
							// SET
						]
					],
					'object_subs' => [
						'type' => 'array',
						'items' => [
							'anyOf' => [
								// SET
							]
						]
					]
				]
			];
			
			foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				$object_description_id = (int)$object_description_id;

				if (!StoreType::checkTypeConfigurationUserClearance($arr_type_set, $num_nodegoat_clearance, $object_description_id, false, false, StoreType::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess($this->arr_project['types'], $arr_type_set, $object_description_id, false, false, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
					continue;
				}
				
				$str_identifier_description = $str_identifier.'_description_'.$object_description_id;

				$this->arr_config['components']['schemas']['model.'.$str_identifier_description.'_id'] = [
					'const' => (int)$object_description_id,
					'title' => 'Object Description ID '.$object_description_id,
					'description' => Labels::parseTextVariables($arr_object_description['object_description_name'])
				];
				
				$s_arr =& $this->arr_config['components']['schemas']['data.'.$str_identifier]['properties']['object_definitions']['properties'][$object_description_id];
											
				$s_arr = [
					'type' => 'object',
					'title' => 'Object Definition - '.Labels::parseTextVariables($arr_object_description['object_description_name']),
					'description' => (parseBody($arr_project_type['configuration']['object_descriptions'][$object_description_id]['information'] ?? null) ?: ''),
					'properties' => [
						'object_description_id' => ['$ref' => '#/components/schemas/model.'.$str_identifier_description.'_id']
						// SET
					],
					'required' => ['object_description_id']
				];
	
				$is_ref_type_id = (bool)$arr_object_description['object_description_ref_type_id'];
				$is_mutable = ($is_ref_type_id && is_array($arr_object_description['object_description_ref_type_id']));
				$has_multi = $arr_object_description['object_description_has_multi'];
				$is_dynamic = $arr_object_description['object_description_is_dynamic'];
				
				if ($is_ref_type_id || $is_dynamic) {

					$str_description_reference = '';
					$str_description_value = '';
					
					if ($is_dynamic) {
						
						$str_description_reference = 'any Type';
						$str_description_value = 'any Object from any Type';
					} else {
						
						$arr_references_type_identifiers = [];
						$arr_references_type_names = [];
						
						foreach ((array)$arr_object_description['object_description_ref_type_id'] as $reference_type_id) {
							
							$arr_references_type_identifiers[$reference_type_id] = 'Type '.(int)$reference_type_id;
							$arr_references_type_names[$reference_type_id] = Labels::parseTextVariables($store_type->getTypeName($reference_type_id));
						}
						
						if ($is_mutable) {
							
							$str_description_reference = 'any of '.implode(',', $arr_references_type_identifiers);
							$str_description_value = 'any of '.implode(',', $arr_references_type_names);
						} else {
							
							$str_description_reference = current($arr_references_type_identifiers);
							$str_description_value = current($arr_references_type_names);
						}
					}
					
					if ($has_multi) {
						
						$s_arr['properties']['object_definition_ref_object_id']['type'] = 'array';
						$s_arr['properties']['object_definition_ref_object_id']['items']['type'] = ($is_mutable ? 'string' : 'integer');
						$s_arr['properties']['object_definition_ref_object_id']['description'] = 'List of references to Objects that '.($is_dynamic || $is_mutable ? 'can belong' : 'belong').' to '.$str_description_reference;
						
						$s_arr['properties']['object_definition_value']['type'] = 'array';
						$s_arr['properties']['object_definition_value']['items']['type'] = 'string';
						$s_arr['properties']['object_definition_value']['description'] = 'List of names from '.$str_description_value;
					} else {
						
						$s_arr['properties']['object_definition_ref_object_id']['type'] = ($is_mutable ? 'string' : 'integer');
						$s_arr['properties']['object_definition_ref_object_id']['description'] = 'Reference to an Object that '.($is_dynamic || $is_mutable ? 'can belong' : 'belongs').' to '.$str_description_reference;
						
						$s_arr['properties']['object_definition_value']['type'] = 'string';
						$s_arr['properties']['object_definition_value']['description'] = 'Name from '.$str_description_value;
					}
				} else {
					
					$arr_cast_value_type = $this->translateToOpenAPISchemaDataType($arr_object_description['object_description_value_type'], $arr_object_description['object_description_value_type_settings']);
					$str_value_type = StoreType::getValueType($arr_object_description['object_description_value_type'], 'name');
					
					if ($has_multi) {
						
						$s_arr['properties']['object_definition_value']['type'] = 'array';
						$s_arr['properties']['object_definition_value']['items']['type'] = $arr_cast_value_type['type'];
						$s_arr['properties']['object_definition_value']['description'] = 'List of '.$str_value_type;
					} else {
						
						$s_arr['properties']['object_definition_value']['type'] = $arr_cast_value_type['type'];
						$s_arr['properties']['object_definition_value']['description'] = $str_value_type;
					}
				}
			}
			
			if (!$this->arr_config['components']['schemas']['data.'.$str_identifier]['properties']['object_definitions']['properties']) {
				unset($this->arr_config['components']['schemas']['data.'.$str_identifier]['properties']['object_definitions']);
			}
			
			foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				$object_sub_details_id = (int)$object_sub_details_id;
				
				if (!StoreType::checkTypeConfigurationUserClearance($arr_type_set, $num_nodegoat_clearance, false, $object_sub_details_id, false, StoreType::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess($this->arr_project['types'], $arr_type_set, false, $object_sub_details_id, false, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
					continue;
				}
				
				$str_identifier_sub = $str_identifier.'_sub_details_'.$object_sub_details_id;
				
				$this->arr_config['components']['schemas']['model.'.$str_identifier_sub.'_id'] = [
					'const' => (int)$object_sub_details_id,
					'title' => 'Sub-Object Details ID '.$object_sub_details_id,
					'description' => Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name'])
				];

				$this->arr_config['components']['schemas']['data.'.$str_identifier]['properties']['object_subs']['items']['anyOf'][] = ['$ref' => '#/components/schemas/data.'.$str_identifier_sub];
				
				$this->arr_config['components']['schemas']['data.'.$str_identifier_sub] = [
					'type' => 'object',
					'title' => 'Sub-Object - '.Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name']),
					'description' => (parseBody($arr_project_type['configuration']['object_sub_details'][$object_sub_details_id]['object_sub_details']['information'] ?? null) ?: ''),
					'properties' => [
						'object_sub' => [
							'type' => 'object',
							'properties' => [
								'object_sub_id' => ['$ref' => '#/components/schemas/data.object_sub_id'],
								'object_sub_details_id' => ['$ref' => '#/components/schemas/model.'.$str_identifier_sub.'_id']
								// SET
							],
							'required' => ['object_sub_details_id']
						],
						'object_sub_definitions' => [
							'type' => 'object',
							'properties' => [
								// SET
							]
						]
					]
				];
				
				$s_arr =& $this->arr_config['components']['schemas']['data.'.$str_identifier_sub]['properties']['object_sub']['properties'];
				
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_has_date']) {
					
					if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_date_period']) {
						$s_arr['object_sub_date_start'] = ['$ref' => '#/components/schemas/data.object_sub_date_period_start'];
						$s_arr['object_sub_date_end'] = ['$ref' => '#/components/schemas/data.object_sub_date_period_end'];
					} else {
						$s_arr['object_sub_date_start'] = ['$ref' => '#/components/schemas/data.object_sub_date_single'];
					}
					$s_arr['object_sub_date_chronology'] = ['$ref' => '#/components/schemas/data.object_sub_date_chronology'];
				}
				
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_has_location']) {
					
					$s_arr['object_sub_location_ref_object_id'] = ['$ref' => '#/components/schemas/data.object_sub_location_ref_object_id'];
					$s_arr['object_sub_location_ref_object_name'] = ['$ref' => '#/components/schemas/data.object_sub_location_ref_object_name'];
					$s_arr['object_sub_location_ref_type_id'] = ['$ref' => '#/components/schemas/data.object_sub_location_ref_type_id'];
					$s_arr['object_sub_location_geometry'] = ['$ref' => '#/components/schemas/data.object_sub_location_geometry'];
				}
				
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					if (!StoreType::checkTypeConfigurationUserClearance($arr_type_set, $num_nodegoat_clearance, false, $object_sub_details_id, $object_sub_description_id, StoreType::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess($this->arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
						continue;
					}
					
					$str_identifier_sub_description = $str_identifier_sub.'_description_'.$object_sub_description_id;
					
					$this->arr_config['components']['schemas']['model.'.$str_identifier_sub_description.'_id'] = [
						'const' => (int)$object_sub_description_id,
						'title' => 'Sub-Object Description ID '.$object_sub_description_id,
						'description' => Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name'])
					];
					
					$s_arr =& $this->arr_config['components']['schemas']['data.'.$str_identifier_sub]['properties']['object_sub_definitions']['properties'][$object_sub_description_id];	
												
					$s_arr = [
						'type' => 'object',
						'title' => 'Sub-Object Definition - '.Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name']),
						'description' => (parseBody($arr_project_type['configuration']['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]['information'] ?? null) ?: ''),
						'properties' => [
							'object_sub_description_id' => ['$ref' => '#/components/schemas/model.'.$str_identifier_sub_description.'_id']
							// SET
						],
						'required' => ['object_sub_description_id']
					];
					
					$is_ref_type_id = (bool)$arr_object_sub_description['object_sub_description_ref_type_id'];
					$is_mutable = ($is_ref_type_id && is_array($arr_object_sub_description['object_sub_description_ref_type_id']));
					$is_dynamic = $arr_object_sub_description['object_sub_description_is_dynamic'];
					
					if ($is_ref_type_id || $is_dynamic) {

						$str_description_reference = '';
						$str_description_value = '';
						
						if ($is_dynamic) {
							
							$str_description_reference = 'any Type';
							$str_description_value = 'any Object from any Type';
						} else {
							
							$arr_references_type_identifiers = [];
							$arr_references_type_names = [];
							
							foreach ((array)$arr_object_sub_description['object_sub_description_ref_type_id'] as $reference_type_id) {
								
								$arr_references_type_identifiers[$reference_type_id] = 'Type '.(int)$reference_type_id;
								$arr_references_type_names[$reference_type_id] = Labels::parseTextVariables($store_type->getTypeName($reference_type_id));
							}
							
							if ($is_mutable) {
								
								$str_description_reference = 'any of '.implode(',', $arr_references_type_identifiers);
								$str_description_value = 'any of '.implode(',', $arr_references_type_names);
							} else {
								
								$str_description_reference = current($arr_references_type_identifiers);
								$str_description_value = current($arr_references_type_names);
							}
						}

						$s_arr['properties']['object_sub_definition_ref_object_id']['type'] = ($is_mutable ? 'string' : 'integer');
						$s_arr['properties']['object_sub_definition_ref_object_id']['description'] = 'Reference to an Object that '.($is_dynamic || $is_mutable ? 'can belong' : 'belongs').' to '.$str_description_reference;
							
						$s_arr['properties']['object_sub_definition_value']['type'] = 'string';
						$s_arr['properties']['object_sub_definition_value']['description'] = 'Name from '.$str_description_value;
					} else {
						
						$arr_cast_value_type = $this->translateToOpenAPISchemaDataType($arr_object_sub_description['object_sub_description_value_type'], $arr_object_sub_description['object_sub_description_value_type_settings']);
						$str_value_type = StoreType::getValueType($arr_object_sub_description['object_sub_description_value_type'], 'name');
						
						$s_arr['properties']['object_sub_definition_value']['type'] = $arr_cast_value_type['type'];
						$s_arr['properties']['object_sub_definition_value']['description'] = $str_value_type;
					}
				}
				
				if (!$this->arr_config['components']['schemas']['data.'.$str_identifier_sub]['properties']['object_sub_definitions']['properties']) {
					unset($this->arr_config['components']['schemas']['data.'.$str_identifier_sub]['properties']['object_sub_definitions']);
				}
			}
			
			if (!$this->arr_config['components']['schemas']['data.'.$str_identifier]['properties']['object_subs']['items']['anyOf']) {
				unset($this->arr_config['components']['schemas']['data.'.$str_identifier]['properties']['object_subs']);
			}
			
			// Other
			
			if (isset($arr_types_filters[$type_id])) {
			
				foreach ($arr_types_filters[$type_id] as $filter_id => $arr_project_filter) {
					
					try {
						$arr_description = ParseTypeFeatures::parseDescriptionTypeFilter($type_id, $arr_project_filter);
					} catch (Exception $e) {
						continue;
					}

					if ($arr_description) {

						$arr_parameters = [];
						
						foreach ($arr_description['parameters'] as $parameter => $arr_parameter) {
							
							$arr_parameters[$parameter] = [
								...$arr_parameter['type'],
								'description' => (string)$arr_parameter['description']
							];
						}
						
						$str_description = Labels::parseTextVariables($arr_description['text']);
						
						$this->arr_config['components']['schemas']['other.filter_'.$filter_id.'_id'] = [
							'type' => 'object',
							'title' => 'Filter form for Filter ID '.$filter_id.' for Type ID '.$type_id,
							'properties' => [
								'filter_id' => [
									'const' => (int)$filter_id,
									'title' => 'Filter ID '.$filter_id.' for Type ID '.$type_id
								],
								...$arr_parameters
							],
							'required' => ['filter_id'],
							'description' => 'Use and adjust this form to Filter on: '.Labels::parseTextVariables($arr_project_filter['name']).($str_description ? '.'.EOL_1100CC.$str_description : '')
						];
					} else {
						
						$str_description = Labels::parseTextVariables($arr_project_filter['description']);
						
						$this->arr_config['components']['schemas']['other.filter_'.$filter_id.'_id'] = [
							'const' => (int)$filter_id,
							'title' => 'Filter ID '.$filter_id.' for Type ID '.$type_id,
							'description' => Labels::parseTextVariables($arr_project_filter['name']).($str_description ? '.'.EOL_1100CC.$str_description : '')
						];
					}

					$this->arr_config_collect['filter_X_id'][] = ['$ref' => '#/components/schemas/other.filter_'.$filter_id.'_id'];
				}
			}

			if (isset($arr_types_scopes[$type_id])) {
			
				foreach ($arr_types_scopes[$type_id] as $scope_id => $arr_scope) {
					
					$str_description = Labels::parseTextVariables($arr_scope['description']);
					
					$this->arr_config['components']['schemas']['other.scope_'.$scope_id.'_id'] = [
						'const' => (int)$scope_id,
						'title' => 'Scope ID '.$scope_id.' for Type ID '.$type_id,
						'description' => Labels::parseTextVariables($arr_scope['name']).($str_description ? '.'.EOL_1100CC.$str_description : '')
					];

					$this->arr_config_collect['scope_X_id'][] = ['$ref' => '#/components/schemas/other.scope_'.$scope_id.'_id'];
				}
			}
			
			if (isset($arr_types_conditions[$type_id])) {
			
				foreach ($arr_types_conditions[$type_id] as $condition_id => $arr_condition) {
					
					$str_description = Labels::parseTextVariables($arr_condition['description']);
					
					$this->arr_config['components']['schemas']['other.condition_'.$condition_id.'_id'] = [
						'const' => (int)$condition_id,
						'title' => 'Condition ID '.$condition_id.' for Type ID '.$type_id,
						'description' => Labels::parseTextVariables($arr_condition['name']).($str_description ? '.'.EOL_1100CC.$str_description : '')
					];

					$this->arr_config_collect['condition_X_id'][] = ['$ref' => '#/components/schemas/other.condition_'.$condition_id.'_id'];
				}
			}
		}
		
		// Model

		$this->arr_config['components']['schemas']['model.type_id'] = ['type' => 'integer', 'title' => 'Type ID'];
		$this->arr_config['components']['schemas']['model.definition_id'] = ['type' => 'integer', 'title' => 'Object Description ID'];
		$this->arr_config['components']['schemas']['model.object_description_id'] = ['type' => 'integer', 'title' => 'Object Description ID'];
		$this->arr_config['components']['schemas']['model.object_sub_details_id'] = ['type' => 'integer', 'title' => 'Sub-Object Details ID'];
		$this->arr_config['components']['schemas']['model.object_sub_description_id'] = ['type' => 'integer', 'title' => 'Sub-Object Description ID'];
		
		$arr_value_types_base = StoreType::getValueTypesBase();
		unset($arr_value_types_base['object_description']);
		$this->arr_config['components']['schemas']['model.object_description_value_type_base'] = ['type' => 'string', 'enum' => array_keys($arr_value_types_base), 'title' => 'Value types for Object Description'];
				
		$arr_value_types_base = StoreType::getValueTypesBase();
		$this->arr_config['components']['schemas']['model.object_sub_description_value_type_base'] = ['type' => 'string', 'enum' => array_keys($arr_value_types_base), 'title' => 'Value types for Sub-Object Descriptions'];
		
		if ($this->is_administrator) {
			$this->arr_config['components']['schemas']['model.clearance'] = ['type' => 'integer', 'enum' => [0,1,2,3,4,5], 'title' => 'Data Model clearance levels'];
		}
				
		$this->arr_config['components']['schemas']['model.type'] = [
			'type' => 'object',
			'title' => 'Type',
			'properties' => [
				'type' => [
					'type' => 'object',
					'properties' => [
						'type_id' => ['$ref' => '#/components/schemas/model.type_id'],
						'class' => ['type' => 'integer'],
						'name' => ['type' => 'string'],
						'color' => ['type' => 'string'],
						...($this->is_administrator ? [
							'use_object_name' => ['type' => 'boolean'],
							'object_name_in_overview' => ['type' => 'boolean']
						] : [])
					]
				],
				'definitions' => [
					'type' => 'array',
					'items' => [
						'type' => 'object',
						'properties' => [
							'definition_id' => ['$ref' => '#/components/schemas/model.definition_id'],
							'definition_name' => ['type' => 'string'],
							'definition_text' => ['type' => 'string']
						]
					]
				],
				'object_descriptions' => [
					'type' => 'array',
					'items' => [
						'type' => 'object',
						'properties' => [
							'object_description_id' => ['$ref' => '#/components/schemas/model.object_description_id'],
							'object_description_name' => ['type' => 'string'],
							'object_description_value_type_base' => ['$ref' => '#/components/schemas/model.object_description_value_type_base'],
							'object_description_value_type_settings' => ['type' => ['array', 'object']],
							'object_description_is_required' => ['type' => 'boolean'],
							'object_description_is_unique' => ['type' => 'boolean'],
							'object_description_is_identifier' => ['type' => 'boolean'],
							'object_description_has_multi' => ['type' => 'boolean'],
							'object_description_ref_type_id' => ['$ref' => '#/components/schemas/model.type_id'],
							...($this->is_administrator ? [
								'object_description_in_name' => ['type' => 'boolean'],
								'object_description_in_search' => ['type' => 'boolean'],
								'object_description_in_overview' => ['type' => 'boolean'],
								'object_description_clearance_view' => ['$ref' => '#/components/schemas/model.clearance'],
								'object_description_clearance_edit' => ['$ref' => '#/components/schemas/model.clearance']
							] : [])
						]
					]
				],
				'object_sub_details' => [
					'type' => 'array',
					'items' => [
						'type' => 'object',
						'properties' => [
							'object_sub_details' => [
								'type' => 'object',
								'properties' => [
									'object_sub_details_id' => ['$ref' => '#/components/schemas/model.object_sub_details_id'],
									'object_sub_details_name' => ['type' => 'string'],
									'object_sub_details_is_single' => ['type' => 'boolean'],
									'object_sub_details_is_required' => ['type' => 'boolean'],
									'object_sub_details_has_date' => ['type' => 'boolean'],
									'object_sub_details_is_date_period' => ['type' => 'boolean'],
									'object_sub_details_date_setting' => ['type' => 'string'],
									'object_sub_details_date_use_object_sub_details_id' => ['$ref' => '#/components/schemas/model.object_sub_details_id', 'title' => 'Use a different Sub-Object Details to source for the date.'],
									'object_sub_details_date_start_use_object_sub_description_id' => ['$ref' => '#/components/schemas/model.object_sub_description_id', 'title' => 'Use a Sub-Object Description to source for the date start.'],
									'object_sub_details_date_start_use_object_description_id' => ['$ref' => '#/components/schemas/model.object_description_id', 'title' => 'Use a Object Description to source for the date start.'],
									'object_sub_details_date_end_use_object_sub_description_id' => ['$ref' => '#/components/schemas/model.object_sub_description_id', 'title' => 'Use a Sub-Object Description to source for the date end.'],
									'object_sub_details_date_end_use_object_description_id' => ['$ref' => '#/components/schemas/model.object_description_id', 'title' => 'Use a Object Description to source for the date end.'],
									'object_sub_details_has_location' => ['type' => 'boolean'],
									'object_sub_details_location_setting' => ['type' => 'string'],
									'object_sub_details_location_ref_type_id' => ['$ref' => '#/components/schemas/model.type_id'],
									'object_sub_details_location_ref_type_id_locked' => ['type' => 'boolean'],
									'object_sub_details_location_ref_object_sub_details_id' => ['$ref' => '#/components/schemas/model.object_sub_details_id'],
									'object_sub_details_location_ref_object_sub_details_id_locked' => ['type' => 'boolean'],
									'object_sub_details_location_use_object_sub_details_id' => ['$ref' => '#/components/schemas/model.object_sub_details_id'],
									'object_sub_details_location_use_object_sub_description_id' => ['$ref' => '#/components/schemas/model.object_sub_description_id'],
									'object_sub_details_location_use_object_description_id' => ['$ref' => '#/components/schemas/model.object_description_id'],
									'object_sub_details_location_use_object_id' => ['type' => 'boolean'],
									...($this->is_administrator ? [
										'object_sub_details_clearance_view' => ['$ref' => '#/components/schemas/model.clearance'],
										'object_sub_details_clearance_edit' => ['$ref' => '#/components/schemas/model.clearance']
									] : [])
								]
							],
							'object_sub_descriptions' => [
								'type' => 'array',
								'items' => [
									'type' => 'object',
									'properties' => [
										'object_sub_description_id' => ['$ref' => '#/components/schemas/model.object_sub_description_id'],
										'object_sub_description_name' => ['type' => 'string'],
										'object_sub_description_value_type_base' => ['$ref' => '#/components/schemas/model.object_sub_description_value_type_base'],
										'object_sub_description_value_type_settings' => ['type' => ['array', 'object']],
										'object_sub_description_is_required' => ['type' => 'boolean'],
										'object_sub_description_ref_type_id' => ['$ref' => '#/components/schemas/model.type_id'],
										'object_sub_description_use_object_description_id' => ['$ref' => '#/components/schemas/model.object_description_id'],
										...($this->is_administrator ? [
											'object_sub_description_in_name' => ['type' => 'boolean'],
											'object_sub_description_in_search' => ['type' => 'boolean'],
											'object_sub_description_in_overview' => ['type' => 'boolean'],
											'object_sub_description_clearance_view' => ['$ref' => '#/components/schemas/model.clearance'],
											'object_sub_description_clearance_edit' => ['$ref' => '#/components/schemas/model.clearance']
										] : [])
									]
								]
							]
						]
					]
				]
			]
		];
		
		return $arr_config;
	}
	
	protected function translateToOpenAPISchemaDataType($value_type, $arr_type_settings = []) {
		
		$str_cast_type = '';
		
		switch ($value_type) {
			case 'boolean':
				$str_cast_type = 'boolean';
				break;
			case 'integer':
				$str_cast_type = 'integer';
				break;
			case 'numeric':
				$str_cast_type = 'number';
				break;
			default:
				$str_cast_type = 'string';
		}
				
		return ['type' => $str_cast_type];
	}
}
