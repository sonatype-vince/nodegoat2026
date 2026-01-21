<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class AnalyseTypeObjectsNative extends AnalyseTypeObjects {
	
	protected function runDegreeCentrality() {
		
		$mode_weighted = $this->arr_analyse['settings']['weighted']['mode'];

		$this->arr_store = [];
		
		$arr_row = fgetcsv($this->resource, null, ',', '"', CSV_ESCAPE); // Heading
		
		if ($mode_weighted == static::WEIGHTED_UNWEIGHTED) {
			
			$arr_check = [];
			
			while (($arr_row = fgetcsv($this->resource, null, ',', '"', CSV_ESCAPE)) !== false) {
				
				$str_identifier = $arr_row[1].'_'.$arr_row[2];
				
				if (isset($arr_check[$str_identifier])) {
					continue;
				}
					
				$arr_check[$str_identifier] = true;
				
				$object_id_from = explode('-', $arr_row[1]);
				$object_id_from = $object_id_from[1];
				
				$s_arr = &$this->arr_store[$object_id_from][0];
				
				if ($s_arr === null) {
					$s_arr = 0;
				}
			
				$s_arr++;
			}
		} else {
			
			$num_weight_max = 0;
			$num_weight_limit_max = (int)$this->arr_analyse['settings']['weighted']['max'];
			
			while (($arr_row = fgetcsv($this->resource, null, ',', '"', CSV_ESCAPE)) !== false) {
				
				$object_id_from = explode('-', $arr_row[1]);
				$object_id_from = $object_id_from[1];
				
				$s_arr = &$this->arr_store[$object_id_from][0];
				
				if ($s_arr === null) {
					$s_arr = 0;
				}
			
				$s_arr += (int)$arr_row[3]; // Weight
				
				if ($s_arr > $num_weight_max) {
					$num_weight_max = $s_arr;
				}
			}
					
			if ($num_weight_limit_max) {
				$num_weight_max = $num_weight_limit_max;
			}
			
			foreach ($this->arr_store as $object_id => &$arr_value) {
				
				$s_arr = &$arr_value[0];
			
				if ($s_arr > $num_weight_max) {
					$s_arr = $num_weight_max;
				}
				
				if ($mode_weighted === static::WEIGHTED_CLOSENESS) { // Reverse weight based on maximum weight
					$s_arr = 1 + ($num_weight_max - $s_arr);
				}
			}
		}
	}
	
	private $is_disconnected = null;
	private $resource_start = null;
	private $resource_end = null;
	private $type_id_end = null;
	private $arr_object_description_start = null;
	private $arr_object_description_end = null;
	
	protected function inputVectorDistance() {
		
		$this->is_disconnected = (!$this->arr_analyse['scope']['paths']); // If there is no graph collection table
		$this->type_id_end = (int)$this->arr_analyse['settings']['end_type_id'];
		
		$arr_type_set = StoreType::getTypeSetByFlatMap($this->type_id, [$this->arr_analyse['settings']['start_vector'] => true]);
		if ($arr_type_set['object_sub_details']) {
			$this->arr_object_description_start = ['object_sub_details_id' => current(arrValuesRecursive('object_sub_details_id', $arr_type_set['object_sub_details'])), 'object_sub_description_id' => current(arrValuesRecursive('object_sub_description_id', $arr_type_set['object_sub_details']))];
		} else {
			$this->arr_object_description_start = current($arr_type_set['object_descriptions']);
		}
		$arr_type_set = StoreType::getTypeSetByFlatMap($this->type_id_end, [$this->arr_analyse['settings']['end_vector'] => true]);
		if ($arr_type_set['object_sub_details']) {
			$this->arr_object_description_end = ['object_sub_details_id' => current(arrValuesRecursive('object_sub_details_id', $arr_type_set['object_sub_details'])), 'object_sub_description_id' => current(arrValuesRecursive('object_sub_description_id', $arr_type_set['object_sub_details']))];
		} else {
			$this->arr_object_description_end = current($arr_type_set['object_descriptions']);
		}
		
		if (!$this->arr_object_description_start['object_description_id'] && !$this->arr_object_description_start['object_sub_description_id']) {
			error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
		}
		if (!$this->arr_object_description_end['object_description_id'] && !$this->arr_object_description_end['object_sub_description_id']) {
			error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
		}

		if (!$this->is_disconnected) {
			
			$arr_collect_info = $this->collect->getResultInfo();
			$arr_paths = ($arr_collect_info['types'][$this->type_id_end] ?? []);
			$str_path_end = false;

			foreach ($arr_paths as $str_path) {
							
				if ($arr_collect_info['connections'][$str_path]['end']) {
					$str_path_end = $str_path;
				}
			}
			
			if (!$str_path_end) {
				error(getLabel('msg_analysis_vector_scope_mismatch'), TROUBLE_ERROR, LOG_CLIENT);
			}
			
			$this->resource_start = ['objects' => $this->resource, 'objects_filtering' => null];
			$this->resource_end = ['objects' => $this->resource, 'objects_filtering' => null];
			
			$arr_filter_end = null;
			if ($this->arr_analyse['settings']['end_filter']) {
				$arr_filter_end = FilterTypeObjects::convertFilterInput($this->arr_analyse['settings']['end_filter']);
			}
				
			$this->collect->setGenerateCallback(function($filter, $cur_type_id, $str_path) use ($str_path_end, $arr_filter_end) {
				
				if ($str_path == CollectTypesObjects::PATH_START) { // Start filter
					
					$is_filtering = false;
					$object_sub_details_id = $this->arr_object_description_start['object_sub_details_id'];
					
					if ($object_sub_details_id) {
						
						if ($filter->isQueryingObjectSubDetails($object_sub_details_id)) {
							
							if (!$filter->isFilteringObjectSubDetails($object_sub_details_id, true)) {
								$filter->setFiltering(['object_sub_details' => [$object_sub_details_id => true]], true);
							}
							$is_filtering = true;
						}
					}
					
					if ($is_filtering) {
						$this->resource_start['objects_filtering'] = $filter;
					}
				} else if ($str_path == $str_path_end) { // End filter
					
					if ($arr_filter_end) {
						$filter->setFilter($arr_filter_end);
					}
					
					$is_filtering = false;
					$object_sub_details_id = $this->arr_object_description_end['object_sub_details_id'];
					
					if ($object_sub_details_id) {
						
						if ($filter->isQueryingObjectSubDetails($object_sub_details_id)) {
							
							if (!$filter->isFilteringObjectSubDetails($object_sub_details_id, true)) {
								$filter->setFiltering(['object_sub_details' => [$object_sub_details_id => true]], true);
							}
							$is_filtering = true;
						}
					}
					
					if ($is_filtering) {
						$this->resource_end['objects_filtering'] = $filter;
					}
				}
			});
		} else {
			
			$this->resource_start = ['objects' => null, 'objects_filtering' => null];
			$this->resource_end = ['objects' => null, 'objects_filtering' => null];
			
			if ($this->type_id_end != $this->type_id || $this->arr_analyse['settings']['end_filter']) {
				
				// End filter
				
				$arr_filter = FilterTypeObjects::convertFilterInput($this->arr_analyse['settings']['end_filter']);

				$filter = new FilterTypeObjects($this->type_id_end, GenerateTypeObjects::VIEW_ID);
				$filter->setScope($this->collect->getScope());
				$filter->setFilter($arr_filter);
				
				$is_filtering = false;
				$object_sub_details_id = $this->arr_object_description_end['object_sub_details_id'];
					
				if ($object_sub_details_id) {
					
					if ($filter->isQueryingObjectSubDetails($object_sub_details_id)) {
						
						$filter->setFiltering(['object_sub_details' => [$object_sub_details_id => true]], true);
						$is_filtering = true;
					}
				}
				
				$arr_limit_filters = $this->collect->getLimitTypeFilters($this->type_id_end); // Get project filters already added to collect when start and end Types are the same 
				
				if ($arr_limit_filters) {
					
					foreach ($arr_limit_filters as &$arr_limit_type_filter) {
						$arr_limit_type_filter = $arr_limit_type_filter['filter'];
					}
					unset($arr_limit_type_filter);
					
					$filter->setFilter($arr_limit_filters);
				}
				
				if ($this->type_id_end != $this->type_id) { // Otherwise get project filters from specific end Type
					
					$arr_project = StoreCustomProject::getProjects($this->project_id);
					$arr_use_project_ids = array_keys($arr_project['use_projects']);
				
					if ($arr_project['types'][$this->type_id_end]['type_filter_id']) {
						
						$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($this->project_id, false, false, $arr_project['types'][$this->type_id_end]['type_filter_id'], true, $arr_use_project_ids);
						$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_project_filters['object']), $arr_project['types'][$this->type_id_end]['type_filter_object_subs']);
					}
				}
				
				$this->resource_end['objects'] = $filter->storeResultTemporarily(uniqid(), true);
				if ($is_filtering) {
					$this->resource_end['objects_filtering'] = $filter;
				}
				
				// Start filter
				
				$this->collect->setGenerateCallback(function($filter, $cur_type_id, $str_path) {
					
					if ($str_path != CollectTypesObjects::PATH_START) { // Should not happen
						return;
					}

					$is_filtering = false;
					$object_sub_details_id = $this->arr_object_description_start['object_sub_details_id'];
					
					if ($object_sub_details_id && $filter->isQueryingObjectSubDetails($object_sub_details_id)) {
						
						$filter->setFiltering(['object_sub_details' => [$object_sub_details_id => true]], true);
						$is_filtering = true;
					}
				
					$this->resource_start['objects'] = $filter->storeResultTemporarily(true, true);
					if ($is_filtering) {
						$this->resource_start['objects_filtering'] = $filter;
					}
					
					return false; // Do not let collect generate
				});
			} else {
				
				// Start & end filter

				$this->collect->setGenerateCallback(function($filter, $cur_type_id, $str_path) {
					
					if ($str_path != CollectTypesObjects::PATH_START) { // Should not happen
						return;
					}
					
					$arr_filtering = [];
					$object_sub_details_id = $this->arr_object_description_start['object_sub_details_id'];
					
					if ($object_sub_details_id) {
						
						if ($filter->isQueryingObjectSubDetails($object_sub_details_id)) {
							$arr_filtering['object_sub_details'][$object_sub_details_id] = true;
						}
					}
					
					$object_sub_details_id = $this->arr_object_description_end['object_sub_details_id'];

					if ($object_sub_details_id) {
						
						if ($filter->isQueryingObjectSubDetails($object_sub_details_id)) {
							$arr_filtering['object_sub_details'][$object_sub_details_id] = true;
						}
					}
					
					if ($arr_filtering) {
						$filter->setFiltering($arr_filtering, true);
					}
				
					$this->resource_start['objects'] = $filter->storeResultTemporarily(true, true);
					if ($arr_filtering) {
						$this->resource_start['objects_filtering'] = $filter;
					}
					
					$this->resource_end = $this->resource_start;
					
					return false; // Do not let collect generate
				});
			}
		}
	}
	
	protected function runVectorDistance() {
		
		$mode_weighted = ($this->is_disconnected ? static::WEIGHTED_UNWEIGHTED : $this->arr_analyse['settings']['weighted']['mode']);

		$str_sql_from_table = '';
		$str_sql_from_field = '';
		$str_sql_to_table = '';
		$str_sql_to_field = '';
		
		$str_value_type = 'vector';
		$str_sql_field = StoreType::getValueTypeValue($str_value_type, 'search');
		
		$str_sql_connect = 'nodegoat_to_from.id';
		
		if (isset($this->arr_object_description_start['object_sub_description_id'])) {
			
			$str_sql_exists = '';
			if ($this->resource_start['objects_filtering']) {
				$arr_sql = $this->resource_start['objects_filtering']->format2SQLFilteredObjectSubDetails($this->arr_object_description_start['object_sub_details_id'], false, 'nodegoat_tos_def_from.object_sub_id');
				$str_sql_exists = 'AND EXISTS ('.(count($arr_sql) > 1 ? '('.implode(') UNION (', $arr_sql).')' : $arr_sql[0]).')';
			}
			
			$str_sql_from_table = "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos_from ON (nodegoat_tos_from.object_id = ".$str_sql_connect." AND nodegoat_tos_from.object_sub_details_id = ".(int)$this->arr_object_description_start['object_sub_details_id']." AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'object_sub', 'nodegoat_tos_from').")
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($str_value_type, 'search')." nodegoat_tos_def_from ON (nodegoat_tos_def_from.object_sub_id = nodegoat_tos_from.id AND nodegoat_tos_def_from.object_sub_description_id = ".(int)$this->arr_object_description_start['object_sub_description_id']." AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'record_search', 'nodegoat_tos_def_from')."
					".$str_sql_exists."
				)";
				
			$str_sql_from_field = 'nodegoat_tos_def_from.'.$str_sql_field;
		} else {
			
			$str_sql_from_table = "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($str_value_type, 'search')." nodegoat_to_def_from ON (nodegoat_to_def_from.object_id = ".$str_sql_connect." AND nodegoat_to_def_from.object_description_id = ".(int)$this->arr_object_description_start['object_description_id']." AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'record_search', 'nodegoat_to_def_from').")";
			$str_sql_from_field = 'nodegoat_to_def_from.'.$str_sql_field;
		}
		
		$str_sql_connect = 'nodegoat_to_from.id_to';
		
		if ($this->is_disconnected) { // Use or reuse separate table
			
			$str_sql_to_table = "JOIN ".$this->resource_end['objects']." nodegoat_to_to ON (nodegoat_to_to.id != nodegoat_to_from.id)";
			$str_sql_connect = 'nodegoat_to_to.id';
		}
		
		if (isset($this->arr_object_description_end['object_sub_description_id'])) {
			
			$str_sql_exists = '';
			if ($this->resource_end['objects_filtering']) {
				$arr_sql = $this->resource_end['objects_filtering']->format2SQLFilteredObjectSubDetails($this->arr_object_description_end['object_sub_details_id'], false, 'nodegoat_tos_def_to.object_sub_id');
				$str_sql_exists = 'AND EXISTS ('.(count($arr_sql) > 1 ? '('.implode(') UNION (', $arr_sql).')' : $arr_sql[0]).')';
			}
			
			$str_sql_to_table .= " JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos_to ON (nodegoat_tos_to.object_id = ".$str_sql_connect." AND nodegoat_tos_to.object_sub_details_id = ".(int)$this->arr_object_description_end['object_sub_details_id']." AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'object_sub', 'nodegoat_tos_to').")
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($str_value_type, 'search')." nodegoat_tos_def_to ON (nodegoat_tos_def_to.object_sub_id = nodegoat_tos_to.id AND nodegoat_tos_def_to.object_sub_description_id = ".(int)$this->arr_object_description_end['object_sub_description_id']." AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'record_search', 'nodegoat_tos_def_to')."
					".$str_sql_exists."
				)";
			
			$str_sql_to_field = 'nodegoat_tos_def_to.'.$str_sql_field;
		} else {

			$str_sql_to_table .= " JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($str_value_type, 'search')." nodegoat_to_def_to ON (nodegoat_to_def_to.object_id = ".$str_sql_connect." AND nodegoat_to_def_to.object_description_id = ".(int)$this->arr_object_description_end['object_description_id']." AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'record_search', 'nodegoat_to_def_to').")";
			$str_sql_to_field = 'nodegoat_to_def_to.'.$str_sql_field;
		}
		
		$str_sql_calculate = '';
		
		switch ($this->arr_analyse['settings']['operator']) {
			case 'euclidean':
				
				if (DB::ENGINE_IS_MYSQL) {
					$str_sql_calculate = 'VEC_DISTANCE_EUCLIDEAN('.$str_sql_from_field.', '.$str_sql_to_field.')';
				} else {
					$str_sql_calculate = '('.$str_sql_from_field.' <-> '.$str_sql_to_field.')';
				}
				
				break;
			case 'cosine':
				
				if (DB::ENGINE_IS_MYSQL) {
					$str_sql_calculate = 'VEC_DISTANCE_COSINE('.$str_sql_from_field.', '.$str_sql_to_field.')';
				} else {
					$str_sql_calculate = '('.$str_sql_from_field.' <=> '.$str_sql_to_field.')';
				}
				
				break;
		}
		
		if ($mode_weighted == static::WEIGHTED_UNWEIGHTED) {
			$str_sql_calculate = 'SUM('.$str_sql_calculate.')';
		} else {
			$str_sql_calculate = 'SUM('.$str_sql_calculate.' * nodegoat_to_from.weight)';
		}

		$res = DB::query("SELECT
			nodegoat_to_from.id,
			".$str_sql_calculate." AS distance
				FROM ".$this->resource_start['objects']." nodegoat_to_from
				".$str_sql_from_table."
				".$str_sql_to_table."
			GROUP BY nodegoat_to_from.id
		");
		
		$this->arr_store = [];
		
		if ($mode_weighted == static::WEIGHTED_UNWEIGHTED) {
			
			while ($arr_row = $res->fetchRow()) {
			
				$this->arr_store[$arr_row[0]][0] = (float)$arr_row[1];
			}
		} else {
			
			$num_weight_max = 0;
			$num_weight_limit_max = (int)$this->arr_analyse['settings']['weighted']['max'];
		
			while ($arr_row = $res->fetchRow()) {

				$s_arr = &$this->arr_store[$arr_row[0]][0];
				$s_arr = (float)$arr_row[1];
				
				if ($s_arr > $num_weight_max) {
					$num_weight_max = $s_arr;
				}
			}
	
			if ($num_weight_limit_max) {
				$num_weight_max = $num_weight_limit_max;
			}
			
			foreach ($this->arr_store as $object_id => &$arr_value) {
				
				$s_arr = &$arr_value[0];
			
				if ($s_arr > $num_weight_max) {
					$s_arr = $num_weight_max;
				}
				
				if ($mode_weighted === static::WEIGHTED_CLOSENESS) { // Reverse weight based on maximum weight
					$s_arr = 1 + ($num_weight_max - $s_arr);
				}
			}
		}
				
		$this->formatResults($this->arr_analyse['settings']['mode']);
	}
	
	protected function formatResults($num_mode) {
		
		if ($num_mode === static::RESULT_ABSOLUTE) {
			return;
		}
		
		//$num_total = count($this->arr_store);
		$num_total = 0;
		$num_range_min = null;
		$num_range_max = null;
		
		foreach ($this->arr_store as &$arr_value) {
			
			$num_primary = &$arr_value[0];
						
			$num_total += $num_primary;
			
			if ($num_mode !== static::RESULT_NORMALISED) {
				continue;
			}
			
			if ($num_primary > $num_range_max || $num_range_max === null) {
				$num_range_max = $num_primary;
			}
			if ($num_primary < $num_range_min || $num_range_min === null) {
				$num_range_min = $num_primary;
			}
		}
		
		foreach ($this->arr_store as &$arr_value) {
			
			$num_primary = &$arr_value[0];
			
			if ($num_mode === static::RESULT_RELATIVE) {
				
				$num_primary = ($num_primary / $num_total);
			} else if ($num_mode === static::RESULT_NORMALISED) {

				if ($num_range_max === $num_range_min || $num_primary === $num_range_max) {
					
					$num_primary = 1;
				} else if ($num_primary > 0) {
															
					$num_primary = (($num_primary - $num_range_min) / ($num_range_max - $num_range_min));

					if ($num_primary < PHP_FLOAT_MIN) { // Large normalised datasets can get really close to 0, too close for floating point
						$num_primary = 0;
					}
				}
			}
		}
	}
}
