<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

Trait GenerateTypeObjectsLogicConditions {
	
	protected $arr_type_set_conditions = [];
	protected $arr_type_set_conditions_collect = [];
	
	protected $arr_object_conditions = [];
	protected $arr_object_conditions_name = [];
	protected $arr_object_conditions_identifiers = [];

	public function useSettingsConditions() {
		
		if (!$this->arr_type_set_conditions) {
			return;
		}

		$func_process_condition_action_values = function(&$arr_condition_setting, $group) {
			
			if ($this->str_identifier_scope !== false && $arr_condition_setting['condition_scope'] && !$arr_condition_setting['condition_scope'][$this->str_identifier_scope]) {
				
				$arr_condition_setting = false;
				return;
			}
			
			if (!empty($arr_condition_setting['condition_actions']['weight']['number_use_object_description_id'])) {
			
				$arr_value = explode('_', $arr_condition_setting['condition_actions']['weight']['number_use_object_description_id']);
				
				if ($arr_value[1] == 'sub') {
					
					if ($group == 'object') {
						// Not possible
					} else {
						$arr_condition_setting['condition_value']['object_sub_description_id'] = $arr_value[3];
					}
				} else {

					$arr_condition_setting['condition_value']['object_description_id'] = $arr_value[2];
				}
			}
			
			if (!empty($arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id'])) {
				
				$arr_condition_setting['condition_value']['object_analysis_id'] = $arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id'];
			}
		};
		
		if ($this->arr_type_set_conditions['object']) {
			
			foreach ($this->arr_type_set_conditions['object'] as $key => &$arr_condition_setting) {
				
				if ($arr_condition_setting['condition_in_object_nodes_object'] && $this->conditions != static::CONDITIONS_MODE_FULL) {
					
					unset($this->arr_type_set_conditions['object'][$key]);
					continue;
				}

				$func_process_condition_action_values($arr_condition_setting, 'object');
				
				if (!$arr_condition_setting) {
					
					unset($this->arr_type_set_conditions['object'][$key]);
					continue;
				}
				
				$arr_condition_setting['condition_group'] = 'object';
				$this->arr_type_set_conditions_collect[] =& $arr_condition_setting;
			}
		}
		if ($this->arr_type_set_conditions['object_descriptions']) {
			
			foreach ($this->arr_type_set_conditions['object_descriptions'] as $object_description_id => &$arr_condition_settings) {
				
				$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
				
				foreach ($arr_condition_settings as $key => &$arr_condition_setting) {
					
					if (
						(!$arr_condition_setting['condition_in_object_name'] && !$this->arr_selection['object_descriptions'][$object_description_id])
						||
						($arr_condition_setting['condition_in_object_nodes_referencing'] && $this->conditions != static::CONDITIONS_MODE_FULL)
					) {
						
						unset($arr_condition_settings[$key]);
						continue;
					}
					
					$func_process_condition_action_values($arr_condition_setting, 'object');
					
					if (!$arr_condition_setting) {
					
						unset($arr_condition_settings[$key]);
						continue;
					}
					
					$arr_condition_setting['condition_group'] = 'object';
					$arr_condition_setting['object_description_id'] = $object_description_id;
					$this->arr_type_set_conditions_collect[] =& $arr_condition_setting;
				}
				
				if (!$arr_condition_settings) {
					unset($this->arr_type_set_conditions['object_descriptions'][$object_description_id]);
				}
			}
		}
		if ($this->arr_type_set_conditions['object_sub_details']) {
			
			foreach ($this->arr_type_set_conditions['object_sub_details'] as $object_sub_details_id => &$arr_conditions_object_sub_details) {
				
				$in_selection_object_sub_details = (bool)($this->arr_selection['object_sub_details'][$object_sub_details_id] ?? false);

				if ($arr_conditions_object_sub_details['object_sub_details']) {
					
					if (!$in_selection_object_sub_details) {
					
						unset($arr_conditions_object_sub_details['object_sub_details']);
					} else {
						
						foreach ($arr_conditions_object_sub_details['object_sub_details'] as $key => &$arr_condition_setting) {
							
							if ($arr_condition_setting['condition_in_object_nodes_object'] && $this->conditions != static::CONDITIONS_MODE_FULL) {
								
								unset($arr_conditions_object_sub_details['object_sub_details'][$key]);
								continue;
							}
							
							$func_process_condition_action_values($arr_condition_setting, 'object_sub_details');
							
							if (!$arr_condition_setting) {
					
								unset($arr_conditions_object_sub_details['object_sub_details'][$key]);
								continue;
							}
							
							$arr_condition_setting['condition_group'] = 'object_sub_details';
							$arr_condition_setting['object_sub_details_id'] = $object_sub_details_id;
							$this->arr_type_set_conditions_collect[] =& $arr_condition_setting;
						}
						
						if (!$arr_conditions_object_sub_details['object_sub_details']) {
							unset($arr_conditions_object_sub_details['object_sub_details']);
						}
					}
				}
				
				if ($arr_conditions_object_sub_details['object_sub_descriptions']) {
					
					foreach ($arr_conditions_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => &$arr_condition_settings) {
						
						$in_selection_object_sub_description = ($in_selection_object_sub_details && !empty($this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]));

						foreach ($arr_condition_settings as $key => &$arr_condition_setting) {
							
							if (
								(!$arr_condition_setting['condition_in_object_name'] && !$in_selection_object_sub_description)
								||
								($arr_condition_setting['condition_in_object_nodes_referencing'] && $this->conditions != static::CONDITIONS_MODE_FULL)
							) {
								
								unset($arr_condition_settings[$key]);
								continue;
							}
							
							$func_process_condition_action_values($arr_condition_setting, 'object_sub_details');
							
							if (!$arr_condition_setting) {
					
								unset($arr_condition_settings[$key]);
								continue;
							}

							$arr_condition_setting['condition_group'] = ($arr_condition_setting['condition_in_object_name'] ? 'object' : 'object_sub_details');
							$arr_condition_setting['object_sub_details_id'] = $object_sub_details_id;
							$arr_condition_setting['object_sub_description_id'] = $object_sub_description_id;
							$this->arr_type_set_conditions_collect[] =& $arr_condition_setting;
						}
						
						if (!$arr_condition_settings) {
							unset($arr_conditions_object_sub_details['object_sub_descriptions'][$object_sub_description_id]);
						}
					}
					
					if (!$arr_conditions_object_sub_details['object_sub_descriptions']) {
						unset($arr_conditions_object_sub_details['object_sub_descriptions']);
					}
				}
				
				if (!$arr_conditions_object_sub_details) {
					unset($this->arr_type_set_conditions['object_sub_details'][$object_sub_details_id]);
				}
			}
		}
		
		foreach ($this->arr_type_set_conditions_collect as &$arr_condition_setting) {

			if ($arr_condition_setting['condition_filter']) {
					
				$arr_filter = FilterTypeObjects::convertFilterInput($arr_condition_setting['condition_filter']);
				
				$filter_condition = new FilterTypeObjects($this->type_id, static::VIEW_ID);
				$filter_condition->setFormatMode($this->mode_format); // Do not (and no need to) override active (set by this Generate) format mode when filtering Conditions
				$filter_condition->setScope($this->arr_scope);
				$filter_condition->setDifferentiationIdentifier($this->getDifferentiationIdentifier());
				
				$filter_condition->setFilter([
					['table' => [
						['table_name' => '[X]', 'is_source' => true]]
					]
				]);
				$filter_condition->setFilter($arr_filter);
									
				$condition_key = 1 + count($this->arr_columns_object_conditions);
				
				if ($arr_condition_setting['condition_group'] == 'object') {
					
					$sql_filter = $filter_condition->sqlQuery(['nodegoat_to.id', '1', $condition_key]);

					$this->arr_columns_object_conditions[] = ['condition_key' => $condition_key, 'sql' => $sql_filter, 'condition_group' => $arr_condition_setting['condition_group']];
				} else {
					
					$object_sub_details_id = $arr_condition_setting['object_sub_details_id'];
					
					if ($filter_condition->isQueryingObjectSubDetails($object_sub_details_id)) {

						$filter_condition->setFiltering(['object_sub_details' => [$object_sub_details_id => true]], true);
						
						$sql_filter = $filter_condition->sqlQuery([
							'columns' => ['nodegoat_to.id', ['object_sub_details_id' => $object_sub_details_id], $condition_key],
							'group' => ['nodegoat_to.id']
						]);

						$this->arr_columns_object_conditions[] = ['condition_key' => $condition_key, 'sql' => $sql_filter, 'condition_group' => $arr_condition_setting['condition_group'], 'object_sub_details_id' => $object_sub_details_id, 'condition_is_filtering_sub' => true];
						
						$arr_condition_setting['condition_is_filtering_sub'] = true;
					} else {
						
						$sql_filter = $filter_condition->sqlQuery(['nodegoat_to.id', '1', $condition_key]);
						
						$this->arr_columns_object_conditions[] = ['condition_key' => $condition_key, 'sql' => $sql_filter, 'condition_group' => $arr_condition_setting['condition_group'], 'object_sub_details_id' => $object_sub_details_id];
					}
				}
				
				$this->arr_sql_pre_queries[] = $sql_filter;
				
				$arr_sql_pre_settings = $filter_condition->getPre();
				$this->arr_sql_pre_settings += $arr_sql_pre_settings;
				
				$arr_condition_setting['condition_filter_key'] = $condition_key;
			}
			
			if ($arr_condition_setting['condition_value']) {
				
				if ($arr_condition_setting['condition_group'] == 'object') {
					
					$arr_sql_value = [];
					
					$object_description_id = $arr_condition_setting['condition_value']['object_description_id'];
					
					if ($object_description_id) {
						
						$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
						
						$column_name = 'des_'.(int)$object_description_id;
						
						$table_name = 'nodegoat_to_def';
						$version_select = $this->generateVersion('record', $table_name);
						$str_value_type = $arr_object_description['object_description_value_type'];
						$sql_value = $table_name.'.'.StoreType::getValueTypeValue($str_value_type);
						if ($str_value_type == 'numeric') {
							$sql_value = FormatTypeObjects::sqlInt2SQLNumeric($sql_value);
						}
						if ($arr_object_description['object_description_has_multi']) {
							$sql_value = 'SUM('.$sql_value.')';
						}
						
						$arr_sql_value[$column_name] = "(SELECT ".$sql_value." AS column_value
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($str_value_type)." AS ".$table_name."
							WHERE ".$table_name.".object_id = nodegoat_to.id AND ".$table_name.".object_description_id = ".(int)$object_description_id." AND ".$version_select."
						)";
					}
					
					$object_analysis_id = $arr_condition_setting['condition_value']['object_analysis_id'];
					
					if ($object_analysis_id) {
													
						$table_name = 'nodegoat_to_an';
						
						$arr_object_analysis_id = explode('_', $object_analysis_id);
						
						$analysis_id = (int)$arr_object_analysis_id[0];
						$analysis_user_id = (int)$arr_object_analysis_id[1];
						
						if ($analysis_id && !$analysis_user_id) {
							$analysis_user_id = 0;
						} else if ($analysis_user_id) {
							$analysis_user_id = ($this->arr_scope['users'] && in_array($analysis_user_id, $this->arr_scope['users']) ? $analysis_user_id : null);
						} else if (!$analysis_id) {
							$analysis_user_id = ($this->arr_scope['users'] ? current($this->arr_scope['users']) : null);
						}
						
						if ($analysis_user_id === null) { // Make sure we have something
							$analysis_user_id = 0;
						}
						
						$column_name = 'an_'.$analysis_id.'_'.$analysis_user_id;
						
						$arr_sql_value[$column_name] = "(SELECT
							".$table_name.".number
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_ANALYSES')." AS ".$table_name."
							WHERE ".$table_name.".object_id = nodegoat_to.id
								AND ".$table_name.".user_id = ".(int)$analysis_user_id."
								AND ".$table_name.".analysis_id = ".(int)$analysis_id."
								AND ".$table_name.".state = 1
						)";
						
						$this->arr_use_extensions['analysis'][$analysis_id.'_'.$analysis_user_id] = ['analysis_id' => $analysis_id, 'user_id' => $analysis_user_id];
					}
					
					$column_name = 'object_condition_value_';
					
					if (count($arr_sql_value) > 1) {
						
						$sql_value = implode(' * ', $arr_sql_value); // Multiply
						$column_name .= implode('_', array_keys($arr_sql_value));
					} else {
						
						$sql_value = current($arr_sql_value);
						$column_name .= key($arr_sql_value);
					}
					
					$s_arr =& $this->arr_columns_object_conditions_values[$column_name];
					
					if (!$s_arr) {
					
						$column_index = count($this->arr_columns_object_conditions_values) - 1;
						
						$s_arr = ['column_name' => $column_name, 'column_index' => $column_index, 'sql' => $sql_value];
					}
					
					$arr_condition_setting['condition_value_column_index'] = $s_arr['column_index'];
					
					if (isset($arr_condition_setting['condition_actions']['color']['color_secondary'])) {
						$arr_condition_setting['condition_value']['result'] = []; // Initialise storing the necessary result range
					}
				} else {
					
					$arr_sql_value = [];
					
					$object_sub_details_id = $arr_condition_setting['object_sub_details_id'];
					
					if ($arr_condition_setting['condition_value']['object_description_id']) {
						
						$object_description_id = $arr_condition_setting['condition_value']['object_description_id'];
						$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
						
						$column_name = 'des_'.(int)$object_description_id;
						$table_name = 'nodegoat_to_def';
						$version_select = $this->generateVersion('record', $table_name);
						$str_value_type = $arr_object_description['object_description_value_type'];
						$sql_value = $table_name.'.'.StoreType::getValueTypeValue($str_value_type);
						if ($str_value_type == 'numeric') {
							$sql_value = FormatTypeObjects::sqlInt2SQLNumeric($sql_value);
						}
						if ($arr_object_description['object_description_has_multi']) {
							$sql_value = 'SUM('.$sql_value.')';
						}
						
						$arr_sql_value[$column_name] = "(SELECT ".$sql_value." AS column_value
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($str_value_type)." AS ".$table_name."
							WHERE ".$table_name.".object_id = nodegoat_tos_".(int)$object_sub_details_id.".object_id AND ".$table_name.".object_description_id = ".(int)$object_description_id." AND ".$version_select."
						)";							
					} else if ($arr_condition_setting['condition_value']['object_sub_description_id']) {

						$object_sub_description_id = $arr_condition_setting['condition_value']['object_sub_description_id'];
						$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
						
						$column_name = 'des_'.(int)$object_sub_details_id.'_'.(int)$object_sub_description_id;
						$table_name = 'nodegoat_tos_def';
						$version_select = $this->generateVersion('record', $table_name);
						$str_value_type = $arr_object_sub_description['object_sub_description_value_type'];
						$sql_value = $table_name.'.'.StoreType::getValueTypeValue($str_value_type);
						if ($str_value_type == 'numeric') {
							$sql_value = FormatTypeObjects::sqlInt2SQLNumeric($sql_value);
						}
									
						$arr_sql_value[$column_name] = "(SELECT ".$sql_value." AS column_value
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($str_value_type)." AS ".$table_name."
							WHERE ".$table_name.".object_sub_id = nodegoat_tos_".(int)$object_sub_details_id.".id AND ".$table_name.".object_sub_description_id = ".(int)$object_sub_description_id." AND ".$version_select."
						)";
					}
					
					$object_analysis_id = $arr_condition_setting['condition_value']['object_analysis_id'];
					
					if ($object_analysis_id) {

						$table_name = 'nodegoat_to_an';
						
						$arr_object_analysis_id = explode('_', $object_analysis_id);
													
						$analysis_id = (int)$arr_object_analysis_id[0];
						$analysis_user_id = (int)$arr_object_analysis_id[1];
						
						if ($analysis_id && !$analysis_user_id) {
							$analysis_user_id = 0;
						} else if ($analysis_user_id) {
							$analysis_user_id = ($this->arr_scope['users'] && in_array($analysis_user_id, $this->arr_scope['users']) ? $analysis_user_id : null);
						} else if (!$analysis_id) {
							$analysis_user_id = ($this->arr_scope['users'] ? current($this->arr_scope['users']) : null);
						}
						
						if ($analysis_user_id === null) { // Make sure we have something
							$analysis_user_id = 0;
						}
						
						$column_name = 'an_'.$analysis_id.'_'.$analysis_user_id;
						
						$arr_sql_value[$column_name] = "(SELECT
							".$table_name.".number
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_ANALYSES')." AS ".$table_name."
							WHERE ".$table_name.".object_id = nodegoat_tos_".(int)$object_sub_details_id.".object_id
								AND ".$table_name.".user_id = ".(int)$analysis_user_id."
								AND ".$table_name.".analysis_id = ".(int)$analysis_id."
								AND ".$table_name.".state = 1
						)";
						
						$this->arr_use_extensions['analysis'][$analysis_id.'_'.$analysis_user_id] = ['analysis_id' => $analysis_id, 'user_id' => $analysis_user_id];
					}
					
					$column_name = 'object_condition_value_';
					
					if (count($arr_sql_value) > 1) {
						
						$sql_value = implode(' * ', $arr_sql_value); // Multiply
						$column_name .= implode('_', array_keys($arr_sql_value));
					} else {
						
						$sql_value = current($arr_sql_value);
						$column_name .= key($arr_sql_value);
					}
					
					$s_arr =& $this->arr_columns_subs_object_conditions_values[$object_sub_details_id][$column_name];
					
					if (!$s_arr) {
					
						$column_index = count($this->arr_columns_subs_object_conditions_values[$object_sub_details_id]) - 1;
						
						$s_arr = ['column_name' => $column_name, 'column_index' => $column_index, 'sql' => $sql_value];
					}
					
					$arr_condition_setting['condition_value_column_index'] = $s_arr['column_index'];
					
					if (isset($arr_condition_setting['condition_actions']['color']['color_secondary'])) {
						$arr_condition_setting['condition_value']['result'] = []; // Initialise storing the necessary result range
					}
				}
			}
			
			if ($arr_condition_setting['condition_function']) {
				
				if (!empty($arr_condition_setting['condition_function']['position'])) {
					
					if ($arr_condition_setting['condition_group'] == 'object') {
						
						if (!isset($arr_position_object_ids)) {
							
							$arr_position_object_ids = [];
							
							$this->addPost(function($arr_objects) use (&$arr_position_object_ids) {
								
								foreach ($arr_position_object_ids as $str_identifier => $object_id) {
									$this->addConditionFound($str_identifier, $arr_objects[$object_id]['object']['object_name']);
								}
							});
						}
						
						$arr_condition_setting['condition_function']['position'] = (int)$arr_condition_setting['condition_function']['position'];
						
						$arr_condition_setting['condition_function_call'] = function($object_id, &$arr_row) use (&$arr_condition_setting, &$arr_position_object_ids) {
							
							if ($this->num_generate_object !== $arr_condition_setting['condition_function']['position']) {
								return false;
							}
							
							$arr_position_object_ids[$arr_condition_setting['condition_identifier']] = $object_id;
							
							$arr_condition_setting['condition_function_call'] = false; // false = not needed anymore and do not apply condition, null = not needed anymore and do apply condition

							return true;
						};
					} else {
						
						// Only supports positioning grouped per Object's Sub-Objects
						
						$arr_condition_setting['condition_function']['position'] = (int)$arr_condition_setting['condition_function']['position'];
						
						$arr_condition_setting['condition_function_call'] = function($object_id, $object_sub_id, &$arr_row) use (&$arr_condition_setting) {
							
							if ($this->num_generate_object_sub !== $arr_condition_setting['condition_function']['position']) {
								return false;
							}

							return true;
						};
					}
				}
				
				if (!empty($arr_condition_setting['condition_function']['replace'])) { // replace:icon;X.svg;X->object_description_xx;svg->object_description_xxx
					
					$arr_parameters = explode(';', $arr_condition_setting['condition_function']['replace']);
					
					$str_target = $arr_parameters[0]; // Process target
					$str_target_text = $arr_parameters[1]; // Target text
					$arr_assign_parse = array_slice($arr_parameters, 2); // Parse pair(s)
					
					foreach ($arr_assign_parse as &$arr_parse) {
						
						$arr_parse = explode('->', $arr_parse);
						$arr_source_id = explode('_', $arr_parse[1]); // Source value(s)
						
						$arr_parse = ['text' => $arr_parse[0]];
						
						if ($arr_source_id[1] == 'sub') {
							
							if ($arr_condition_setting['condition_group'] == 'object') {
								// Not possible
							} else {
								$arr_parse['object_sub_description_id'] = $arr_source_id[3];
							}
						} else if ($arr_source_id[0] == 'object') {

							$arr_parse['object_description_id'] = $arr_source_id[2];
						} else if ($arr_source_id[0] == 'weight') {
							
							$arr_parse['weight'] = true;
						}
					}
					
					if ($str_target == 'icon') {
						
						if ($str_target_text === '') {
							$str_target_text = $arr_condition_setting['condition_actions']['icon']['image'];
						}
						
						$func_parse_icon = function($arr_object, $arr_object_sub = null) use ($str_target_text, $arr_assign_parse) {
								
							$str_icon = $str_target_text;
							
							foreach ($arr_assign_parse as $arr_parse) {
								
								$str_value = '';
								
								if (isset($arr_parse['object_description_id'])) {
									$str_value = ($arr_object['object_definitions'][$arr_parse['object_description_id']]['object_definition_value'] ?? '');
								} else if (isset($arr_parse['object_sub_description_id']) && $arr_object_sub !== null) {
									$str_value = ($arr_object_sub['object_sub_definitions'][$arr_parse['object_sub_description_id']]['object_sub_definition_value'] ?? '');
								}
								
								if (is_array($str_value)) {
									$str_value = ($str_value[0] ?? '');
								}
								
								if ($str_value === '') {
									continue;
								}
								
								$str_icon = str_replace($arr_parse['text'], $str_value, $str_icon);
							}
							
							if (!$str_icon) {
								return;
							}
							
							$media = new EnucleateMedia($str_icon, DIR_HOME_TYPE_OBJECT_MEDIA, '/'.DIR_TYPE_OBJECT_MEDIA);
							$str_icon = $media->enucleate(EnucleateMedia::VIEW_URL);
							
							return $str_icon;
						};
						
						if ($arr_condition_setting['condition_group'] == 'object') {
						
							$this->addPost(function(&$arr_objects) use ($arr_condition_setting, $func_parse_icon) {
								
								foreach ($arr_objects as $object_id => &$arr_object) {
									
									$arr_object_conditions =& $arr_object['object']['object_style']['conditions'][$arr_condition_setting['condition_identifier']];
									
									if (!isset($arr_object_conditions)) {
										continue;
									}
									
									$str_icon = $func_parse_icon($arr_object);
									
									$arr_object_conditions['icon'] = $str_icon;
								}
							});
						} else {
							
							$this->addPost(function(&$arr_objects) use ($arr_condition_setting, $func_parse_icon) {
								
								foreach ($arr_objects as $object_id => &$arr_object) {
									
									foreach ($arr_object['object_subs'] as &$arr_object_sub) {
										
										if ($arr_object_sub['object_sub']['object_sub_details_id'] != $arr_condition_setting['object_sub_details_id']) {
											continue;
										}
										
										$arr_object_conditions =& $arr_object_sub['object_sub']['object_sub_style']['conditions'][$arr_condition_setting['condition_identifier']];
										
										if (!isset($arr_object_conditions)) {
											continue;
										}
										
										$str_icon = $func_parse_icon($arr_object, $arr_object_sub);
										
										$arr_object_conditions['icon'] = $str_icon;
									}
								}
							});
						}
					} else if ($str_target == 'date') { // In development!

						$arr_target_settings = str2Array($str_target_text, '|');
						
						$func_parse_date = function(&$arr_object, &$arr_object_sub, $arr_object_conditions) use ($arr_target_settings, $arr_assign_parse) {
							
							$str_date = ($arr_target_settings[0] ?? '');
							
							foreach ($arr_assign_parse as $arr_parse) {
								
								$str_value = '';
								
								if (isset($arr_parse['object_description_id'])) {
									$str_value = ($arr_object['object_definitions'][$arr_parse['object_description_id']]['object_definition_value'] ?? '');
								} else if (isset($arr_parse['object_sub_description_id']) && $arr_object_sub !== null) {
									$str_value = ($arr_object_sub['object_sub_definitions'][$arr_parse['object_sub_description_id']]['object_sub_definition_value'] ?? '');
								} else if (isset($arr_parse['weight'])) {
									$str_value = (string)$arr_object_conditions['weight'];
								}

								if (is_array($str_value)) {
									$str_value = ($str_value[0] ?? '');
								}
								
								if ($str_value === '') {
									continue;
								}
								
								$str_date = str_replace($arr_parse['text'], $str_value, $str_date);
							}
							
							if (!$str_date) {
								return;
							}
							
							$arr_object_sub['object_sub']['object_sub_date_start'] = FormatTypeObjects::date2Integer($str_date);
							
							return $str_icon;
						};
						
						if ($arr_condition_setting['condition_group'] == 'object') {
						
							// Date only relevant for Sub-Objects
						} else {
							
							$this->addPost(function(&$arr_objects) use ($arr_condition_setting, $func_parse_date) {
								
								foreach ($arr_objects as $object_id => &$arr_object) {
									
									foreach ($arr_object['object_subs'] as &$arr_object_sub) {
										
										if ($arr_object_sub['object_sub']['object_sub_details_id'] != $arr_condition_setting['object_sub_details_id']) {
											continue;
										}
										
										$arr_object_conditions =& $arr_object_sub['object_sub']['object_sub_style']['conditions'][$arr_condition_setting['condition_identifier']];
										
										if (!isset($arr_object_conditions)) {
											continue;
										}
										
										$func_parse_date($arr_object, $arr_object_sub, $arr_object_conditions);
									}
								}
							});
						}
					}
				}
			}
		}
		unset($s_arr);
	}
	
	private function resetObjectConditions() {
		
		$this->arr_object_conditions = $this->arr_object_conditions_name = $this->arr_object_conditions_identifiers = [];
	}
	
	private function parseObjectConditions(&$arr_condition_setting, $arr_row) {

		$str_condition_identifier = $arr_condition_setting['condition_identifier'];
		
		if ($arr_condition_setting['condition_in_object_nodes_referencing']) {
			
			$description_id = null;
			if (isset($arr_condition_setting['object_sub_description_id'])) {
				$description_id = $arr_condition_setting['object_sub_description_id'];
			} else {
				$description_id = $arr_condition_setting['object_description_id'];
			}
			
			$this->arr_object_conditions_identifiers['self'][$str_condition_identifier] = null;
			
			$s_arr =& $this->arr_object_conditions_identifiers['descriptions'][$description_id][$str_condition_identifier];
		} else {
			
			$s_arr =& $this->arr_object_conditions_identifiers['self'][$str_condition_identifier];
		}
		
		$num_weight = 1;
		$num_amount = null;
		
		if (isset($arr_condition_setting['condition_actions']['weight'])) {
			
			$num_weight = ($arr_condition_setting['condition_actions']['weight']['number'] ?? null);
		
			if ($arr_condition_setting['condition_actions']['weight']['number_use_object_description_id'] || $arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id']) {
			
				$num_column_index = ($this->num_generate_column_index_conditions_values + $arr_condition_setting['condition_value_column_index']);
				
				$num_amount = (float)$arr_row[$num_column_index];
				$arr_condition_setting['condition_actions']['weight']['object_description_value'] = $num_amount;
				
				if ($num_weight === null || $num_weight > 0) { // Do apply only if num_weight is set
				
					$num_weight = (($num_weight ?? 1) * $num_amount);
					
					if ($num_weight < 1) {
						$num_weight = 1; // Make sure when a value is calculated, it will never be smaller than 1
					}
				} else {
					
					$num_amount = null;
				}
			}
			
			$num_weight = ($num_weight ?? 1);
		}
		
		$s_arr['weight'] = (int)$num_weight; // Eventual weight always will be integer
		
		if ($num_amount !== null && isset($arr_condition_setting['condition_value']['result'])) {
			
			$str_color_secondary = ($arr_condition_setting['condition_actions']['color']['color_secondary'] ?? null);
			
			if ($str_color_secondary !== null) {
				
				$str_color = $arr_condition_setting['condition_actions']['color']['color'];
				$arr_range = $arr_condition_setting['condition_value']['result']['range'];
				
				if ($arr_range['min'] < 1) { // A calculated value will never be smaller than 1
					$arr_range['min'] = (float)1;
				}
				if ($arr_range['max'] < 1) {
					$arr_range['max'] = (float)1;
				}
				
				if ($arr_range['min'] !== $arr_range['max']) {
					
					$num_weight_normalised = (($num_weight - $arr_range['min']) / ($arr_range['max'] - $arr_range['min']));
				
					$s_arr['color'] = CreateVisualisationPackage::color2WeightedColor($num_weight_normalised, $str_color, $str_color_secondary);
				}
			}
		}
	}
	
	private function setObjectNameConditions($target, $arr_actions) {
		
		if (!$this->arr_object_conditions_name[$target]) {
			$this->arr_object_conditions_name[$target] = []; // Make sure to clean redundant [object_description_id][/object_description_id] tags in the object name
		}
		if ($arr_actions) {
			$this->arr_object_conditions_name[$target][] = $arr_actions;
		}
	}
	
	private function setObjectConditions($target, $arr_actions) {
		
		if ($arr_actions) {
			$this->arr_object_conditions[$target][] = $arr_actions;
		}
	}

	private function applyObjectNameConditions(&$arr_object) {
		
		$str_object_name = $arr_object['object_name'];
		
		$arr_style_object = false;
		
		foreach ($this->arr_object_conditions_name as $target => $arr_condition_actions) {
			
			$str_open_regex = $str_close_regex = $str_open_limit = $str_close_limit = $str_spacing = $str_before = $str_after = $str_prefix = $str_affix = '';
			$arr_style = [];
			$arr_style_key_value = [];
			
			if ($target == 'object') {
				
				$str_self = $str_object_name;
			} else {
				
				$pos_start = strpos($str_object_name, '['.$target.']');
				
				if ($pos_start === false) { // The object description does not exist in the name
					continue;
				}
				
				$length_tag = strlen('['.$target.']');
				$pos_end = strpos($str_object_name, '[/'.$target.']')+$length_tag+1;
				$nr_spacing = (substr($str_object_name, $pos_start+$length_tag, 1) == ' ' ? (substr($str_object_name, $pos_start+$length_tag+1, 1) == '(' ? 2 : 1) : 0);
				
				$str_before = substr($str_object_name, 0, $pos_start);
				$str_after = substr($str_object_name, $pos_end);
				$pos_start = ($pos_start+$length_tag+$nr_spacing);
				$pos_end = ($pos_end-($length_tag+1));
				$str_self = substr($str_object_name, $pos_start, ($pos_end - $pos_start));
				
				if ($nr_spacing) {
					$str_spacing = ($nr_spacing == 2 ? ' (' : ' ');
				}
			}

			foreach ($arr_condition_actions as $arr_actions) {
				
				foreach ($arr_actions as $action => $arr_action) {
					
					switch ($action) {
						case 'background_color':
							if ($this->conditions == static::CONDITIONS_MODE_FULL) {
								$arr_style_key_value['background_color'] = $arr_action['color'];
							}
							$arr_style['background_color'] = 'background-color: '.$arr_action['color'].';';
							break;
						case 'text_emphasis':
							foreach ($arr_action['emphasis'] as $value) {
								switch ($value) {
									case 'bold':
										if ($this->conditions == static::CONDITIONS_MODE_FULL) {
											$arr_style_key_value['font_weight'] = 'bold';
										}
										$arr_style['font_weight'] = 'font-weight: bold;';
										break;
									case 'italic':
										if ($this->conditions == static::CONDITIONS_MODE_FULL) {
											$arr_style_key_value['font_style'] = 'italic';
										}
										$arr_style['font_style'] = 'font-style: italic;';
										break;
									case 'strikethrough':
										if ($this->conditions == static::CONDITIONS_MODE_FULL) {
											$arr_style_key_value['text_decoration'] = 'line-through';
										}
										$arr_style['text_decoration'] = 'text-decoration: line-through;';
										break;
								}
							}
							break;
						case 'text_color':
							if ($this->conditions == static::CONDITIONS_MODE_FULL) {
								$arr_style_key_value['text_color'] = $arr_action['color'];
							}
							$arr_style['text_color'] = 'color: '.$arr_action['color'].';';
							break;
						case 'limit_text':
							$arr_tag = Response::addParsePost(false, ['limit' => $arr_action['number'], 'affix' => $arr_action['value']]);
							$str_open_limit = $str_open_limit.$arr_tag['open'];
							$str_close_limit = $arr_tag['close'].$str_close_limit;
							break;
						case 'add_text_prefix':
							if ($target != 'object' && $arr_action['check']) { // 'check' means override default spacing
								$str_spacing = '';
							}
							$str_prefix = ($arr_action['check'] ? '' : $str_prefix).$arr_action['value']; // 'check' means override previous prefix
							break;
						case 'add_text_affix':
							$str_affix = ($arr_action['check'] ? '' : $str_affix).$arr_action['value']; // 'check' means override previous affix
							break;
						case 'regex_replace':
							$arr_tag = Response::addParsePost(false, ['regex' => $arr_action['regex']]);
							$str_open_regex = ($arr_action['check'] ? '' : $str_open_regex).$arr_tag['open']; // 'check' means override previous regex
							$str_close_regex = $arr_tag['close'].($arr_action['check'] ? '' : $str_close_regex);
							break;
					}
				}
			}
			
			$str_open = $str_open_regex.$str_open_limit;
			$str_close = $str_close_limit.$str_close_regex;
			
			if ($target == 'object') {
				
				if ($this->conditions == static::CONDITIONS_MODE_STYLE_INCLUDE && $arr_style) {
					$str_open = '<span style="'.implode('', $arr_style).'">'.$str_open;
					$str_close = $str_close.'</span>';
				} else if ($this->conditions == static::CONDITIONS_MODE_STYLE) {
					$arr_style_object = implode('', $arr_style);
				} else if ($this->conditions == static::CONDITIONS_MODE_FULL) {
					$str_open = '<span style="'.implode('', $arr_style).'">'.$str_open;
					$str_close = $str_close.'</span>';
					$arr_style_object = $arr_style_key_value;
				}
				$str_object_name = $str_open.$str_prefix.$str_self.$str_affix.$str_close;
			} else {
				
				if (($this->conditions == static::CONDITIONS_MODE_STYLE || $this->conditions == static::CONDITIONS_MODE_STYLE_INCLUDE || $this->conditions == static::CONDITIONS_MODE_FULL) && $arr_style) {
					$str_open = '<span style="'.implode('', $arr_style).'">'.$str_open;
					$str_close = $str_close.'</span>';
				}
				$str_object_name = $str_before.$str_spacing.$str_open.$str_prefix.$str_self.$str_affix.$str_close.$str_after;
			}
		}
		
		if ($arr_style_object) {
			$arr_object['object_name_style'] = $arr_style_object;
		}
		$arr_object['object_name'] = $str_object_name;
	}
	
	private function applyObjectConditions(&$arr) {
		
		$is_object_sub = false;
		
		foreach ($this->arr_object_conditions as $target => $arr_condition_actions) {
			
			$arr_style = [];
			$str_open = $str_close = '';
						
			foreach ($arr_condition_actions as $arr_actions) {
				
				$do_hide = false;
				
				foreach ($arr_actions as $action => $arr_action) {
					
					switch ($action) {
						case 'color':
							if ($this->conditions == static::CONDITIONS_MODE_FULL) {
								$str_color = $arr_action['color'];
								if (!$arr_action['check']) {
									$arr_style['color'] = $str_color;
								} else {
									if (!isset($arr_style['color'])) {
										$arr_style['color'] = [];
									} else {
										$arr_style['color'] = (array)$arr_style['color'];
									}
									$arr_style['color'][] = $str_color;
								}
							}
							break;
						case 'background_color':
							$arr_style['background_color'] = ($this->conditions == static::CONDITIONS_MODE_FULL ? $arr_action['color'] : 'background-color: '.$arr_action['color'].';');
							break;
						case 'text_emphasis':
							foreach ($arr_action['emphasis'] as $value) {
								switch ($value) {
									case 'bold':
										$arr_style['font_weight'] = ($this->conditions == static::CONDITIONS_MODE_FULL ? 'bold' : 'font-weight: bold;');
										break;
									case 'italic':
										$arr_style['font_style'] = ($this->conditions == static::CONDITIONS_MODE_FULL ? 'italic' : 'font-style: italic;');
										break;
									case 'strikethrough':
										$arr_style['text_decoration'] = ($this->conditions == static::CONDITIONS_MODE_FULL ? 'line-through' : 'text-decoration: line-through;');
										break;
								}
							}
							break;
						case 'text_color':
							$arr_style['text_color'] = ($this->conditions == static::CONDITIONS_MODE_FULL ? $arr_action['color'] : 'color: '.$arr_action['color'].';');
							break;
						case 'regex_replace':
							$arr_tag = Response::addParsePost(false, ['regex' => $arr_action['regex']]);
							$str_open = ($arr_action['check'] ? '' : $str_open).$arr_tag['open']; // 'check' means override previous regex
							$str_close = $arr_tag['close'].($arr_action['check'] ? '' : $str_close);
							break;
						case 'weight':
							if ($this->conditions == static::CONDITIONS_MODE_FULL) {
								$num_weight = (float)$arr_action['number'];
								if (isset($arr_action['object_description_value'])) {
									if (isset($arr_action['number']) && $num_weight == 0) { // Do not apply if weight calculation would end up 0 anyhow
										break;
									}
									$num_weight = (($num_weight ?: 1) * $arr_action['object_description_value']);
									if ($num_weight < 1) {
										$num_weight = 1; // Make sure when a value is calculated, it will never be smaller than 1
									}
								}
								if (!$arr_action['check']) {
									$arr_style['weight'] = (int)$num_weight; // Eventual weight always will be integer
								} else {
									if (!isset($arr_style['weight'])) {
										$arr_style['weight'] = [];
									} else {
										$arr_style['weight'] = (array)$arr_style['weight'];
									}
									$arr_style['weight'][] = (int)$num_weight; // Eventual weight always will be integer
								}
							}
							break;
						case 'remove':
							$do_hide = true;
							break;
						case 'geometry_color':
							if ($this->conditions == static::CONDITIONS_MODE_FULL) {
								if ($arr_action['color']) {
									$arr_style['geometry_color'] = $arr_action['color'];
								}
								if (isset($arr_action['opacity'])) {
									$arr_style['geometry_opacity'] = $arr_action['opacity'];
								}
							}
							break;
						case 'geometry_stroke_color':
							if ($this->conditions == static::CONDITIONS_MODE_FULL) {
								if ($arr_action['color']) {
									$arr_style['geometry_stroke_color'] = $arr_action['color'];
								}
								if (isset($arr_action['opacity'])) {
									$arr_style['geometry_stroke_opacity'] = $arr_action['opacity'];
								}
							}
							break;
						case 'icon':
							if ($this->conditions == static::CONDITIONS_MODE_FULL) {
								$str_url = ($arr_action['image'] ? '/'.DIR_CUSTOM_PROJECT_WORKSPACE.$arr_action['image'] : '');
								if (!$arr_action['check']) {
									$arr_style['icon'] = $str_url;
								} else {
									if (!isset($arr_style['icon'])) {
										$arr_style['icon'] = [];
									} else {
										$arr_style['icon'] = (array)$arr_style['icon'];
									}
									$arr_style['icon'][] = $str_url;
								}
							}
							break;
					}
				}
			}
			
			if ($do_hide) {
				$style = static::CONDITION_ACTION_HIDE;
			} else {
				if ($this->conditions == static::CONDITIONS_MODE_STYLE || $this->conditions == static::CONDITIONS_MODE_STYLE_INCLUDE) {
					$style = implode('', $arr_style);
				} else if ($this->conditions == static::CONDITIONS_MODE_FULL) {
					$style = $arr_style;
				}
			}
			
			if ($target == 'object') {
				$arr['object']['object_style'] = $style;
			} else if ($target == 'object_sub') {
				$arr['object_sub']['object_sub_style'] = $style;
				$is_object_sub = true;
			} else if (isset($arr['object_sub_definitions'])) {
				$arr['object_sub_definitions'][$target]['object_sub_definition_style'] = $style;
				if ($str_open !== '') {
					$arr['object_sub_definitions'][$target]['processing'] = [$str_open, $str_close];
				}
				$is_object_sub = true;
			} else {
				$arr['object_definitions'][$target]['object_definition_style'] = $style;
				if ($str_open !== '') {
					$arr['object_definitions'][$target]['processing'] = [$str_open, $str_close];
				}
			}
		}
		
		if ($this->conditions == static::CONDITIONS_MODE_FULL && $this->arr_object_conditions_identifiers) {
			
			if ($is_object_sub) {
				
				if ($arr['object_sub']['object_sub_style'] !== static::CONDITION_ACTION_HIDE) {
					
					if (isset($this->arr_object_conditions_identifiers['descriptions'])) {
						
						foreach ($this->arr_object_conditions_identifiers['descriptions'] as $target_id => $arr_identifiers) {
							$arr['object_sub_definitions'][$target_id]['object_sub_definition_style']['conditions'] = $arr_identifiers;
						}
					}
					
					$arr['object_sub']['object_sub_style']['conditions'] = $this->arr_object_conditions_identifiers['self'];
				}
			} else {
				
				if ($arr['object']['object_style'] !== static::CONDITION_ACTION_HIDE) {
					
					if (isset($this->arr_object_conditions_identifiers['descriptions'])) {
						
						foreach ($this->arr_object_conditions_identifiers['descriptions'] as $target_id => $arr_identifiers) {
							$arr['object_definitions'][$target_id]['object_definition_style']['conditions'] = $arr_identifiers;
						}
					}
					
					$arr['object']['object_style']['conditions'] = $this->arr_object_conditions_identifiers['self'];
				}
			}
		}
	}
}
