<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class CreateVisualisationPackage {
	
	
	protected $arr_data = [];
	protected $arr_project = [];
	protected $arr_types_all = [];
	protected $arr_frame = [];
	protected $arr_visual_settings = [];
	
	protected $arr_collect_info = [];
	protected $attribution = '';
	protected $arr_pack_data = [];
	protected $arr_post_process_date = [];
	protected $arr_pack_lookup_geometry = [];
	
	protected $arr_package_html = [];
	protected $arr_package_data = [];
	
	protected $arr_type_sets = [];

    public function __construct($arr_project, $arr_types_all, $arr_frame, $arr_visual_settings) {
		
		
		$this->arr_project = $arr_project;
		$this->arr_types_all = $arr_types_all;
		
		$this->arr_frame = $arr_frame;
		$this->arr_visual_settings = $arr_visual_settings;
		
		$this->setOutput($this->arr_data);
    }
    
    public function setOutput(&$arr) {
		
		$this->arr_data =& $arr;
		
		$this->arr_data['data'] =& $this->arr_package_data;
		$this->arr_data['visual'] =& $this->arr_visual_settings;
		$this->arr_data['html'] =& $this->arr_package_html;
	}
	
	public function addType($type_id, $collect, $arr_filters, $arr_scope, $arr_conditions, $scenario_id = false, $scenario_hash = false) {
		
		$has_cache = null;
		$this->arr_pack_data = [];
		$this->arr_pack_lookup_geometry = [];
		
		if ($scenario_hash) {
			
			$arr_use_project_ids = array_keys($this->arr_project['use_projects']);
			$arr_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($this->arr_project['project']['id'], false, false, $scenario_id, $arr_use_project_ids);
			
			if ($arr_scenario['attribution']) {
				$this->attribution = $arr_scenario['attribution'];
			}
			
			$cache_scenario = new CacheProjectTypeScenario($this->arr_project['project']['id'], $scenario_id);						
			$has_cache = $cache_scenario->checkCacheVisualise($scenario_hash);
			
			if ($has_cache === true) {
						
				$arr_scenario_storage = $cache_scenario->getCache();

				$this->arr_pack_data = $arr_scenario_storage['arr_pack'];
				$arr_collect_info = $arr_scenario_storage['arr_collect_info'];

				foreach ($arr_collect_info['types'] as $cur_type_id => $arr_paths) {
					
					if ($this->arr_type_sets[$cur_type_id]) {
						continue;
					}
					
					$this->arr_type_sets[$cur_type_id] = StoreType::getTypeSet($cur_type_id);
				}
				
				$this->arr_collect_info = ($this->arr_collect_info ? array_merge_recursive($this->arr_collect_info, $arr_collect_info) : $arr_collect_info);
			} else {
				
				status(getLabel('msg_building_cache_scenario_visualisation'), false, getLabel('msg_wait'), ['identifier' => SiteStartEnvironment::getSessionID(true).'cache_scenario_visualisation', 'duration' => 1000, 'persist' => true]);
			}
		}
		
		if (!$this->arr_pack_data) {
			
			$collect->init();
			$arr_collect_info = $collect->getResultInfo();

			$arr_check = []; // Have arrays with unique values while preserving an array (not object) for fast javascript iteration
			$this->arr_post_process_date = [];
			$num_sort = 1;
			
			foreach ($arr_collect_info['types'] as $cur_type_id => $arr_paths) {
				
				if (isset($this->arr_type_sets[$cur_type_id])) {
					continue;
				}
				
				$this->arr_type_sets[$cur_type_id] = StoreType::getTypeSet($cur_type_id);
			}
			
			$this->arr_collect_info = ($this->arr_collect_info ? array_merge_recursive($this->arr_collect_info, $arr_collect_info) : $arr_collect_info);
		
			$arr_objects = $collect->getPathObjects(CollectTypesObjects::PATH_START);
			
			foreach ($arr_objects as $object_id => $arr_object) {
								
				$collect->getWalkedObject($object_id, [], function &($cur_target_object_id, $arr_collect, $source_path, $cur_path, $cur_target_type_id, $arr_info, $collect) use ($object_id, &$arr_check, &$num_sort) {
					
					// The applied Scope and this walk are selection-focussed (vs connection-focussed).
					// Meaning: the selected Objects and their values make the network, the connections allow to pass information along its lineage.
					
					$arr_object_descriptions = $this->arr_type_sets[$cur_target_type_id]['object_descriptions'];
					$arr_object_subs_details = $this->arr_type_sets[$cur_target_type_id]['object_sub_details'];
					
					$arr_object = $collect->getPathObject($cur_path, $arr_info['in_out'], $cur_target_object_id, $arr_info['object_id']);
					
					$do_collapse = ($arr_info['arr_collapse_source'] ? true : false);
					$is_collapsed = ($arr_info['arr_collapsed_source'] ? true : false);
					$arr_collapsing_source = ($is_collapsed ? $arr_info['arr_collapsed_source'] : $arr_info['arr_collapse_source']);
				
					$num_depth = ($source_path == CollectTypesObjects::PATH_START ? 0 : 1 + substr_count($source_path, '-'));
					
					$s_arr_object =& $this->arr_pack_data['objects'][$cur_target_object_id];
					
					if (!$do_collapse) { // Object is not needed when it is collapsed
						
						if (!isset($s_arr_object) || !isset($s_arr_object['name'])) { // Object can exist in multiple paths
							
							$s_arr_object = [
								'name' => $arr_object['object']['object_name'],
								'style' => [],
								'type_id' => $cur_target_type_id,
								'sort' => $num_sort
							];
														
							$num_sort++;
						}
						
						$this->parseStyle($s_arr_object['style'], $arr_object['object']['object_style'], $num_depth); // Add to the object style on every encounter
						
						if (!isset($arr_check[$cur_path.$arr_info['in_out'].'_objects_'.$cur_target_object_id])) { // Objects can exist in multiple paths, update object and its descriptions (in case of filtering) once in every path

							$s_arr_object_definitions =& $s_arr_object['object_definitions'];
							
							foreach ($arr_object['object_definitions'] as $object_description_id => $arr_object_definition) {
								
								$arr_object_description = $arr_object_descriptions[$object_description_id];
								
								if ((!$arr_object_definition['object_definition_value'] && !$arr_object_definition['object_definition_ref_object_id']) || $arr_object_definition['object_definition_style'] === GenerateTypeObjects::CONDITION_ACTION_HIDE) {
									continue;
								}

								if ($arr_object_description['object_description_is_dynamic']) {
									
									if ($arr_object_definition['object_definition_value']) {
										
										$s_arr =& $s_arr_object_definitions[$object_description_id];
																
										if (!isset($s_arr)) {
											
											$arr_values = $arr_object_definition['object_definition_value'];
											if (!$arr_object_description['object_description_has_multi']) {
												$arr_values = [$arr_values];
											}
											
											$s_arr = [
												'description_id' => $object_description_id,
												'value' => $arr_values,
												'ref_object_id' => [],
												'style' => []
											];
										}
									}
									
									if ($arr_object_definition['object_definition_ref_object_id']) {
										
										$arr_references = $arr_object_definition['object_definition_ref_object_id'];
										if (!$arr_object_description['object_description_has_multi']) {
											$arr_references = [$arr_references];
										}
										
										$arr_track_type_ids = [];
										
										foreach ($arr_references as $key => $arr_ref_type_objects) {
											
											foreach ($arr_ref_type_objects as $ref_type_id => $arr_ref_objects) {
												
												$arr_track_type_ids[$ref_type_id] = $ref_type_id;
												
												$s_arr =& $s_arr_object_definitions[$object_description_id.'_'.$ref_type_id];

												if (!isset($s_arr)) {
													
													$s_arr = [
														'description_id' => $object_description_id.'_'.$ref_type_id,
														'value' => [],
														'ref_object_id' => [],
														'style' => []
													];
												}
												
												foreach ($arr_ref_objects as $arr_ref_object) {
													
													$s_arr['value'][] = $arr_ref_object['object_definition_ref_object_name'];
													$s_arr['ref_object_id'][] = $arr_ref_object['object_definition_ref_object_id'];
												}
											}
										}
										
										if ($arr_object_definition['object_definition_style']) {
										
											foreach ($arr_track_type_ids as $ref_type_id) {
												
												$s_arr =& $s_arr_object_definitions[$object_description_id.'_'.$ref_type_id];
												$this->parseStyle($s_arr['style'], $arr_object_definition['object_definition_style'], $num_depth);
											}
										}
									}
								} else if (is_array($arr_object_description['object_description_ref_type_id'])) {
									
									$arr_references = (array)$arr_object_definition['object_definition_ref_object_id'];
									$arr_values = (array)$arr_object_definition['object_definition_value'];
									
									$arr_track_type_ids = [];
									
									foreach ($arr_references as $key => $str_identifier) {
										
										list($ref_type_id, $ref_object_id) = explode('_', $str_identifier);
										$arr_track_type_ids[$ref_type_id] = $ref_type_id;
										
										$s_arr =& $s_arr_object_definitions[$object_description_id.'_'.$ref_type_id];
											
										if (!isset($s_arr)) {
											
											$s_arr = [
												'description_id' => $object_description_id.'_'.$ref_type_id,
												'value' => (array)$arr_values[$key],
												'ref_object_id' => (array)$ref_object_id,
												'style' => []
											];
										} else {
										
											$s_arr['value'][] = $arr_values[$key];
											$s_arr['ref_object_id'][] = $ref_object_id;
										}
									}
									
									if ($arr_object_definition['object_definition_style']) {
										
										foreach ($arr_track_type_ids as $ref_type_id) {
											
											$s_arr =& $s_arr_object_definitions[$object_description_id.'_'.$ref_type_id];
											$this->parseStyle($s_arr['style'], $arr_object_definition['object_definition_style'], $num_depth);
										}
									}
								} else {
									
									$s_arr =& $s_arr_object_definitions[$object_description_id];
									
									if (!isset($s_arr)) {
										
										$s_arr = [
											'description_id' => $object_description_id,
											'value' => (array)$arr_object_definition['object_definition_value'],
											'ref_object_id' => (array)$arr_object_definition['object_definition_ref_object_id'],
											'style' => []
										];
									} else if ($s_arr['ref_object_id'] !== (array)$arr_object_definition['object_definition_ref_object_id']) {
										
										$s_arr['value'] = arrMergeValues($s_arr['value'], (array)$arr_object_definition['object_definition_value']);
										$s_arr['ref_object_id'] = arrMergeValues($s_arr['ref_object_id'], (array)$arr_object_definition['object_definition_ref_object_id']);
									}
									
									$this->parseStyle($s_arr['style'], $arr_object_definition['object_definition_style'], $num_depth);
								}
							}
							
							$arr_check[$cur_path.$arr_info['in_out'].'_objects_'.$cur_target_object_id] = true;
						}
					} else { // Though some information may still be needed
						
						if (!isset($s_arr_object)) {
							
							$s_arr_object = [
								'type_id' => $cur_target_type_id,
								'style' => []
							];
						}
							
						$s_arr_object =& $this->arr_pack_data['objects'][$arr_collapsing_source['object_id']];

						$this->parseStyle($s_arr_object['style'], $arr_object['object']['object_style'], $num_depth); // Add to the object style on every encounter, and collapse
						
						// Track collapsed edge weight
						
						$num_weight = ($arr_object['object']['object_style']['weight'] ?? null);
						
						if ($num_weight !== null) {
							$arr_collect['weight'] += (is_array($num_weight) ? array_sum($num_weight) : $num_weight);
						}
					}
					
					$arr_connect_object_sub_ids = [];
															
					foreach ($arr_object['object_subs'] as $object_sub_id => $arr_object_sub) {
						
						if (isset($arr_info['filtered']) && isset($arr_info['object_sub_details_id']) && $arr_object_sub['object_sub']['object_sub_details_id'] == $arr_info['object_sub_details_id'] && $object_sub_id != $arr_info['object_sub_id']) { // Subobjects can be skipped/dropped in the collection, make sure subobjects only add themselves
							continue;
						}
												
						$s_arr_object_sub =& $this->arr_pack_data['object_subs'][$object_sub_id];
						
						if (!isset($s_arr_object_sub)) { // Sub-objects can exist in multiple paths

							// Return *ymmdd
							$date_raw_start = ($arr_object_sub['object_sub']['object_sub_date_start'] ?: $arr_object_sub['object_sub']['object_sub_date_end']);
							$date_raw_end = ($arr_object_sub['object_sub']['object_sub_date_end'] ?: $date_raw_start);
							$date_start = $this->parseDate($date_raw_start);
							$date_end = $this->parseDate($date_raw_end);
							
							$location_geometry = $this->parseGeometry($arr_object_sub['object_sub']['object_sub_location_geometry'], $object_sub_id);

							$s_arr_object_sub = [
								'object_id' => $cur_target_object_id,
								'object_sub_details_id' => $arr_object_sub['object_sub']['object_sub_details_id'],
								'location_geometry' => $location_geometry,
								'location_name' => $arr_object_sub['object_sub']['object_sub_location_ref_object_name'],
								'location_object_id' => $arr_object_sub['object_sub']['object_sub_location_ref_object_id'],
								'location_type_id' => $arr_object_sub['object_sub']['object_sub_location_ref_type_id'],
								'date_start' => $date_start,
								'date_end' => $date_end,
								'style' => []
							];
							
							if ($date_start) {
								
								if (($date_raw_start == FormatTypeObjects::DATE_INT_MIN && $date_raw_end == FormatTypeObjects::DATE_INT_MIN) || ($date_raw_start == FormatTypeObjects::DATE_INT_MAX && $date_raw_end == FormatTypeObjects::DATE_INT_MAX)) {
									
									$this->arr_post_process_date[$object_sub_id] = $date_raw_start;
									
									$this->arr_pack_data['range'][] = $object_sub_id;
								} else {
									
									if ($date_start != $date_end) {
										$this->arr_pack_data['range'][] = $object_sub_id;
									} else {
										$this->arr_pack_data['date'][$date_start][] = $object_sub_id;
									}
									
									$date_start = ($date_raw_start == FormatTypeObjects::DATE_INT_MIN || $date_raw_start == FormatTypeObjects::DATE_INT_MAX ? ($date_raw_end == FormatTypeObjects::DATE_INT_MIN || $date_raw_end == FormatTypeObjects::DATE_INT_MAX ? false : $date_end) : $date_start);
									if ($date_start && (empty($this->arr_pack_data['date_range']['min']) || FormatTypeObjects::dateInt2Absolute($date_start) < FormatTypeObjects::dateInt2Absolute($this->arr_pack_data['date_range']['min']))) {
										$this->arr_pack_data['date_range']['min'] = $date_start;
									}
									$date_end = ($date_raw_end == FormatTypeObjects::DATE_INT_MIN || $date_raw_end == FormatTypeObjects::DATE_INT_MAX ? ($date_raw_start == FormatTypeObjects::DATE_INT_MIN || $date_raw_start == FormatTypeObjects::DATE_INT_MAX ? false : $date_start) : $date_end);
									if ($date_end && (empty($this->arr_pack_data['date_range']['max']) || FormatTypeObjects::dateInt2Absolute($date_end) > FormatTypeObjects::dateInt2Absolute($this->arr_pack_data['date_range']['max']))) {
										$this->arr_pack_data['date_range']['max'] = $date_end;
									}
								}
							}
						}
						
						$this->parseStyle($s_arr_object_sub['style'], $arr_object_sub['object_sub']['object_sub_style'], $num_depth); // Add to the sub-object style on every encounter,
						
						$arr_connect_object_sub_ids['_'.$object_sub_id] = $object_sub_id; // Make sure the key is not numeric to prevent potential sorting by client
						
						if (!isset($arr_check[$cur_path.$arr_info['in_out'].'_object_subs_'.$object_sub_id])) { // Subobjects can exist in multiple paths, update descriptions (in case of filtering) once in every path

							if (!$do_collapse) { // Sub-object description is not needed when it is collapsed
								
								$arr_object_sub_details = $arr_object_subs_details[$arr_object_sub['object_sub']['object_sub_details_id']];
								
								$s_arr_object_sub_definitions =& $s_arr_object_sub['object_sub_definitions'];
								
								foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_object_sub_definition) {
									
									$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];
									
									if ((!$arr_object_sub_definition['object_sub_definition_value'] && !$arr_object_sub_definition['object_sub_definition_ref_object_id']) || $arr_object_sub_definition['object_sub_definition_style'] === GenerateTypeObjects::CONDITION_ACTION_HIDE) {
										continue;
									}
									
									if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
										
										if ($arr_object_sub_definition['object_sub_definition_value']) {
											
											$s_arr =& $s_arr_object_sub_definitions[$object_sub_description_id];
																	
											if (!isset($s_arr)) {
												
												$s_arr = [
													'description_id' => $object_sub_description_id,
													'value' => [$arr_object_sub_definition['object_sub_definition_value']],
													'ref_object_id' => [],
													'style' => []
												];
											}
										}
										
										foreach ($arr_object_sub_definition['object_sub_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
											
											$s_arr =& $s_arr_object_sub_definitions[$object_sub_description_id.'_'.$ref_type_id];
											
											if (!isset($s_arr)) {
												
												$s_arr = [
													'description_id' => $object_sub_description_id.'_'.$ref_type_id,
													'value' => [],
													'ref_object_id' => [],
													'style' => []
												];
											}
											
											foreach ($arr_ref_objects as $arr_ref_object) {
												
												$s_arr['value'][] = $arr_ref_object['object_sub_definition_ref_object_name'];
												$s_arr['ref_object_id'][] = $arr_ref_object['object_sub_definition_ref_object_id'];
											}
											
											$this->parseStyle($s_arr['style'], $arr_object_sub_definition['object_sub_definition_style'], $num_depth);
										}
									} else if (is_array($arr_object_sub_description['object_sub_description_ref_type_id'])) {
										
										$str_identifier = $arr_object_sub_definition['object_sub_definition_ref_object_id'];										
										list($ref_type_id, $ref_object_id) = explode('_', $str_identifier);
											
										$s_arr =& $s_arr_object_sub_definitions[$object_sub_description_id.'_'.$ref_type_id];
												
										if (!isset($s_arr)) {
												
											$s_arr = [
												'description_id' => $object_sub_description_id.'_'.$ref_type_id,
												'value' => (array)$arr_object_sub_definition['object_sub_definition_value'],
												'ref_object_id' => (array)$ref_object_id,
												'style' => []
											];
										} else {
											
											$s_arr['value'][] = $arr_object_sub_definition['object_sub_definition_value'];
											$s_arr['ref_object_id'][] = $ref_object_id;
										}
										
										$this->parseStyle($s_arr['style'], $arr_object_sub_definition['object_sub_definition_style'], $num_depth);
									} else {
										
										$s_arr =& $s_arr_object_sub_definitions[$object_sub_description_id];
									
										if (!isset($s_arr)) {
											
											$s_arr = [
												'description_id' => $object_sub_description_id,
												'value' => (array)$arr_object_sub_definition['object_sub_definition_value'],
												'ref_object_id' => (array)$arr_object_sub_definition['object_sub_definition_ref_object_id'],
												'style' => []
											];
										} else if ($s_arr['ref_object_id'] !== (array)$arr_object_sub_definition['object_sub_definition_ref_object_id']) {
											
											$s_arr['value'] = arrMergeValues($s_arr['value'], (array)$arr_object_sub_definition['object_sub_definition_value']);
											$s_arr['ref_object_id'] = arrMergeValues($s_arr['ref_object_id'], (array)$arr_object_sub_definition['object_sub_definition_ref_object_id']);
										}
										
										$this->parseStyle($s_arr['style'], $arr_object_sub_definition['object_sub_definition_style'], $num_depth);
									}
								}
							}
							
							$arr_check[$cur_path.$arr_info['in_out'].'_object_subs_'.$object_sub_id] = true;
						}
					}

					if ($do_collapse) {

						$source_object_id = $arr_collapsing_source['object_id'];
						$source_object_sub_id = $arr_collapsing_source['object_sub_id'];
						
						if ($arr_object['object_definitions'] || $arr_object['object_subs']) { // Not 'object only'

							// Relocate the collapse source sub-object to the collapse source object
							
							if ($source_object_sub_id) {
								
								$s_arr_object_sub =& $this->arr_pack_data['object_subs'][$source_object_sub_id];
								
								if (!isset($s_arr_object_sub)) { // Sub-object could be missing when the sub-object itself is collapsed and not part of the selection
									
									$s_arr_object_sub = [
										'object_sub_details_id' => 'collapse',
										'original_object_id' => ($arr_info['in_out'] == TraceTypesNetwork::PATH_IN ? $cur_target_object_id : $arr_info['object_id']),
										'object_id' => $source_object_id,
										'style' => []
									];
								} else if ($s_arr_object_sub['object_id'] != $source_object_id) {
									
									unset($arr_connect_object_sub_ids['_'.$source_object_sub_id]);
									
									$use_object_sub_id = $this->collapseObjectSub($source_object_id, $source_object_sub_id, $source_object_sub_id.'_'.$source_object_id, $s_arr_object_sub);
																				
									$arr_collapsing_source['object_sub_id'] = $use_object_sub_id;
									$arr_connect_object_sub_ids['_+'.$use_object_sub_id] = $use_object_sub_id;
								}
							}
							
							if ($arr_object['object_definitions']) {
								
								foreach ($arr_object['object_definitions'] as $object_description_id => $arr_object_definition) {
									
									$arr_object_description = $arr_object_descriptions[$object_description_id];
									
									if (
										(!empty($this->arr_collect_info['collapse'][$cur_path]['object_descriptions'][$object_description_id]) && empty($this->arr_collect_info['connections'][$cur_path]['end']))
										|| !$arr_object_definition['object_definition_ref_object_id'] || (!$arr_object_description['object_description_ref_type_id'] && !$arr_object_description['object_description_is_dynamic']) || $arr_object_definition['object_definition_style'] === GenerateTypeObjects::CONDITION_ACTION_HIDE
									) {
										continue;
									}
									
									if ($arr_object_description['object_description_is_dynamic']) {
										
										$arr_references = $arr_object_definition['object_definition_ref_object_id'];
										if (!$arr_object_description['object_description_has_multi']) {
											$arr_references = [$arr_references];
										}
										
										$arr_types_ref_object_ids = [];
										$arr_types_values = [];
										
										foreach ($arr_references as $key => $arr_ref_type_objects) {
											
											foreach ($arr_ref_type_objects as $ref_type_id => $arr_ref_objects) {

												foreach ($arr_ref_objects as $arr_ref_object) {
													
													$arr_types_ref_object_ids[$ref_type_id][] = $arr_ref_object['object_definition_ref_object_id'];
													$arr_types_values[$ref_type_id][] = $arr_ref_object['object_definition_ref_object_name'];
												}
											}											
										}
										
										foreach ($arr_types_ref_object_ids as $ref_type_id => $arr_ref_object_ids) {
											
											$this->collapseObjectDescription($object_description_id.'_'.$ref_type_id, false, $arr_collapsing_source, $ref_type_id, $arr_types_values[$ref_type_id], $arr_ref_object_ids, $arr_object_definition['object_definition_style'], $arr_collect, $num_depth);
										}
									} else if (is_array($arr_object_description['object_description_ref_type_id'])) {
										
										$arr_references = (array)$arr_object_definition['object_definition_ref_object_id'];
										$arr_values = (array)$arr_object_definition['object_definition_value'];
										
										$arr_types_ref_object_ids = [];
										$arr_types_values = [];
										
										foreach ($arr_references as $key => $str_identifier) {
											
											list($ref_type_id, $ref_object_id) = explode('_', $str_identifier);

											$arr_types_ref_object_ids[$ref_type_id][] = $ref_object_id;
											$arr_types_values[$ref_type_id][] = $arr_values[$key];
										}
										
										foreach ($arr_types_ref_object_ids as $ref_type_id => $arr_ref_object_ids) {
											
											$this->collapseObjectDescription($object_description_id.'_'.$ref_type_id, false, $arr_collapsing_source, $ref_type_id, $arr_types_values[$ref_type_id], $arr_ref_object_ids, $arr_object_definition['object_definition_style'], $arr_collect, $num_depth);
										}
									} else {
										
										$this->collapseObjectDescription($object_description_id, false, $arr_collapsing_source, $arr_object_description['object_description_ref_type_id'], (array)$arr_object_definition['object_definition_value'], (array)$arr_object_definition['object_definition_ref_object_id'], $arr_object_definition['object_definition_style'], $arr_collect, $num_depth);
									}
								}
							}
							
							if ($arr_object['object_subs']) {
								
								foreach ($arr_object['object_subs'] as $object_sub_id => $arr_object_sub) {
									
									$s_arr_object_sub =& $this->arr_pack_data['object_subs'][$object_sub_id];
									$use_object_sub_id = $object_sub_id;
									
									// Relocate sub-objects to the collapse source object
									
									if ($s_arr_object_sub['object_id'] != $source_object_id || ($source_object_sub_id && $object_sub_id != $source_object_sub_id)) { // Always duplicate when source of collapse is a sub-object
										
										unset($arr_connect_object_sub_ids['_'.$object_sub_id]);
										
										$use_object_sub_id = $this->collapseObjectSub($source_object_id, $object_sub_id, $object_sub_id.'_'.$source_object_id.'_'.$source_object_sub_id, $s_arr_object_sub, $source_object_sub_id);

										$s_arr_object_sub =& $this->arr_pack_data['object_subs'][$use_object_sub_id];
										$arr_connect_object_sub_ids['_+'.$use_object_sub_id] = $use_object_sub_id;
									}
									
									// Track collapsed edge weight. Any selected and weighted Sub-Object will count towards the total
							
									$num_weight = ($arr_object_sub['object_sub']['object_sub_style']['weight'] ?? null);
									
									if ($num_weight !== null) {
										$arr_collect['weight'] += (is_array($num_weight) ? array_sum($num_weight) : $num_weight);
									}
									
									if (!$arr_object_sub['object_sub_definitions']) {
										continue;
									}
									
									$object_sub_details_id = $arr_object_sub['object_sub']['object_sub_details_id'];
									$arr_object_sub_details = $arr_object_subs_details[$object_sub_details_id];
									
									foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_object_sub_definition) {
										
										$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];
										
										if (
											(!empty($this->arr_collect_info['collapse'][$cur_path]['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]) && empty($this->arr_collect_info['connections'][$cur_path]['end']))
											|| !$arr_object_sub_definition['object_sub_definition_ref_object_id'] || (!$arr_object_sub_description['object_sub_description_ref_type_id'] && !$arr_object_sub_description['object_sub_description_is_dynamic']) || $arr_object_sub_definition['object_sub_definition_style'] === GenerateTypeObjects::CONDITION_ACTION_HIDE
										) {
											continue;
										}
										
										if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
											
											foreach ($arr_object_sub_definition['object_sub_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
												
												$arr_values = [];
												$arr_ref_object_ids = [];
												
												foreach ($arr_ref_objects as $arr_ref_object) {
													
													$arr_values[] = $arr_ref_object['object_sub_definition_ref_object_name'];
													$arr_ref_object_ids[] = $arr_ref_object['object_sub_definition_ref_object_id'];
												}
												
												$this->collapseObjectDescription($object_sub_description_id.'_'.$ref_type_id, $use_object_sub_id, $arr_collapsing_source, $ref_type_id, $arr_values, $arr_ref_object_ids, $arr_object_sub_definition['object_sub_definition_style'], $arr_collect, $num_depth);
											}
										} else if (is_array($arr_object_sub_description['object_sub_description_ref_type_id'])) {
										
											list($ref_type_id, $ref_object_id) = explode('_', $arr_object_sub_definition['object_sub_definition_ref_object_id']);
																						
											$this->collapseObjectDescription($object_sub_description_id.'_'.$ref_type_id, $use_object_sub_id, $arr_collapsing_source, $ref_type_id, (array)$arr_object_sub_definition['object_sub_definition_value'], (array)$ref_object_id, $arr_object_sub_definition['object_sub_definition_style'], $arr_collect, $num_depth);
										} else {
											
											$this->collapseObjectDescription($object_sub_description_id, $use_object_sub_id, $arr_collapsing_source, $arr_object_sub_description['object_sub_description_ref_type_id'], (array)$arr_object_sub_definition['object_sub_definition_value'], (array)$arr_object_sub_definition['object_sub_definition_ref_object_id'], $arr_object_sub_definition['object_sub_definition_style'], $arr_collect, $num_depth);
										}
									}
								}
							}
						}
						
						if ($arr_info['collapse_start'] && $arr_info['in_out'] == TraceTypesNetwork::PATH_OUT) { // If collapse source is the starting point, remove
							
							if ($arr_collapsing_source['object_description_id']) {

								unset($this->arr_pack_data['objects'][$source_object_id]['object_definitions'][$arr_collapsing_source['object_description_id'].($arr_collapsing_source['mutable'] ? '_'.$cur_target_type_id : '')]);
							} else if ($arr_collapsing_source['object_sub_description_id']) {

								unset($this->arr_pack_data['object_subs'][$source_object_sub_id]['object_sub_definitions'][$arr_collapsing_source['object_sub_description_id'].($arr_collapsing_source['mutable'] ? '_'.$cur_target_type_id : '')]);
							} else if ($arr_collapsing_source['object_sub_location']) {
							
							}
						}
					} else if ($is_collapsed && $arr_info['in_out'] == TraceTypesNetwork::PATH_IN) { // If source was part of a collapse, but the current object is not, reconfigure the reference to that source
						
						$source_object_id = $arr_collapsing_source['object_id'];
						$source_type_id = $arr_collapsing_source['type_id'];
						
						$arr_value = (array)$this->arr_pack_data['objects'][$source_object_id]['name'];
						$arr_ref_object_id = (array)$source_object_id;
						
						if ($arr_collapsing_source['object_description_id']) {
							$org_object_description_id = $arr_collapsing_source['object_description_id'];
						} else if ($arr_collapsing_source['object_sub_description_id']) {
							$org_object_description_id = $arr_collapsing_source['object_sub_description_id'];
						} else if ($arr_collapsing_source['object_sub_location']) {
							$org_object_description_id = $arr_collapsing_source['object_sub_location'];
						}
						$arr_info['object_id'] = $cur_target_object_id;
						
						$this->collapseObjectDescription($org_object_description_id, false, $arr_info, $source_type_id, $arr_value, $arr_ref_object_id);
						
						if ($arr_info['object_description_id'] || $arr_info['object_sub_description_id']) {
							
							if ($arr_info['object_description_id']) {
								
								$s_arr_object_definitions =& $this->arr_pack_data['objects'][$cur_target_object_id]['object_definitions'];
								$object_description_identifier = $arr_info['object_description_id'].($arr_collapsing_source['mutable'] ? '_'.$arr_info['type_id'] : '');
							} else {
								
								$s_arr_object_definitions =& $this->arr_pack_data['object_subs'][$arr_info['object_sub_id']]['object_sub_definitions'];
								$object_description_identifier = $arr_info['object_sub_description_id'].($arr_collapsing_source['mutable'] ? '_'.$arr_info['type_id'] : '');
							}

							if (isset($s_arr_object_definitions[$object_description_identifier])) {
								
								$s_arr =& $s_arr_object_definitions[$object_description_identifier];
								
								if ($s_arr['ref_object_id']) { // If collapsed description is part of the selection, remove
								
									$key = array_search($arr_info['object_id'], $s_arr['ref_object_id']);

									unset(
										$s_arr['value'][$key],
										$s_arr['ref_object_id'][$key]
									);
									
									// Make sure the arrays do not have nonsequential keys
									$s_arr['value'] = array_values($s_arr['value']);
									$s_arr['ref_object_id'] = array_values($s_arr['ref_object_id']);
								}
							}
						} else if ($arr_info['object_sub_location']) {
							
						}
					}
					
					$s_arr_object_connect_object_sub_ids =& $this->arr_pack_data['objects'][$object_id]['connect_object_sub_ids'];
					
					if ($s_arr_object_connect_object_sub_ids === null) {
						$s_arr_object_connect_object_sub_ids = [];
					}
					
					$s_arr_object_connect_object_sub_ids += $arr_connect_object_sub_ids;
					
					return $arr_collect;
				});
			}
			
			foreach ($this->arr_post_process_date as $object_sub_id => $date_start_raw) {
						
				$s_arr_object_sub =& $this->arr_pack_data['object_subs'][$object_sub_id];
				
				if ($date_start_raw == FormatTypeObjects::DATE_INT_MIN) {
					$s_arr_object_sub['date_end'] = ($this->arr_pack_data['date_range']['min'] ?? null);
				} else {
					$s_arr_object_sub['date_start'] = ($this->arr_pack_data['date_range']['max'] ?? null);
				}
			}
		}
		
		if ($has_cache === false) {
							
			$arr_store = ['arr_pack' => $this->arr_pack_data, 'arr_collect_info' => $arr_collect_info];
			
			// Parse package
			
			GenerateTypeObjects::setClearSharedTypeObjectNames(false);
			
			Response::holdFormat(true);
			Response::setFormat(Response::OUTPUT_JSON);
			
			$str = Response::parse($arr_store);
			
			Response::holdFormat();
			
			unset($arr_store);
			GenerateTypeObjects::setClearSharedTypeObjectNames(true);
			
			// Store package
			
			$cache_scenario->updateCache($str);
			
			clearStatus(SiteStartEnvironment::getSessionID(true).'cache_scenario_visualisation');
		}
		
		if ($this->arr_pack_data) {
			
			$this->arr_package_data['pack'][] = $this->arr_pack_data;
		}
	}
	
	protected function collapseObjectSub($object_id, $object_sub_id, $use_object_sub_id, $arr_object_sub, $collapse_object_sub_id = null) {

		if (!isset($arr_object_sub['original_object_id'])) { // First encounter, claim
			
			$s_arr_object_sub =& $this->arr_pack_data['object_subs'][$object_sub_id];
		
			$s_arr_object_sub['original_object_id'] = $s_arr_object_sub['object_id'];
			$s_arr_object_sub['object_id'] = $object_id;
			
			$s_arr_object_sub['orginal_has_date'] = (bool)$s_arr_object_sub['date_start'];
		
			$use_object_sub_id = $object_sub_id;
		} else if (!isset($this->arr_pack_data['object_subs'][$use_object_sub_id])) { // More encounters, copy
		
			$this->arr_pack_data['object_subs'][$use_object_sub_id] = $arr_object_sub;
			$s_arr_object_sub =& $this->arr_pack_data['object_subs'][$use_object_sub_id];
			$s_arr_object_sub['object_id'] = $object_id;
			
			if ($s_arr_object_sub['orginal_has_date']) {
			
				if (isset($this->arr_post_process_date[$object_sub_id])) {
					
					$this->arr_post_process_date[$use_object_sub_id] = $this->arr_post_process_date[$object_sub_id];
					
					$this->arr_pack_data['range'][] = $use_object_sub_id;
				} else {
					
					if ($s_arr_object_sub['date_start'] != $s_arr_object_sub['date_end']) {
						$this->arr_pack_data['range'][] = $use_object_sub_id;
					} else {
						$this->arr_pack_data['date'][$s_arr_object_sub['date_start']][] = $use_object_sub_id;
					}
				}
			}
			
			$s_arr_object_sub['object_sub_definitions'] = [];
		} else {
			
			$s_arr_object_sub =& $this->arr_pack_data['object_subs'][$use_object_sub_id];
		}
		
		if ($collapse_object_sub_id && !$s_arr_object_sub['orginal_has_date']) {
			
			$arr_source_object_sub = $this->arr_pack_data['object_subs'][$collapse_object_sub_id];
			
			if ($arr_source_object_sub['date_start']) {
				
				$s_arr_object_sub['date_start'] = $arr_source_object_sub['date_start'];
				$s_arr_object_sub['date_end'] = $arr_source_object_sub['date_end'];
				
				if (isset($this->arr_post_process_date[$collapse_object_sub_id])) {
		
					$this->arr_post_process_date[$use_object_sub_id] = $this->arr_post_process_date[$collapse_object_sub_id];
					
					$this->arr_pack_data['range'][] = $use_object_sub_id;
				} else {
					
					if ($s_arr_object_sub['date_start'] != $s_arr_object_sub['date_end']) {
						$this->arr_pack_data['range'][] = $use_object_sub_id;
					} else {
						$this->arr_pack_data['date'][$s_arr_object_sub['date_start']][] = $use_object_sub_id;
					}
				}
			} else {
				
				$s_arr_object_sub['date_start'] = 0;
				$s_arr_object_sub['date_end'] = 0;
			}
		}
		
		return $use_object_sub_id;
	}
	
	protected function &collapseObjectDescription($use_object_description_id, $object_sub_id, $arr_collapse_to, $ref_type_id, $arr_value, $arr_ref_object_id, $arr_style = null, $arr_collect = null, $num_depth = null) {
		
		if ($arr_collapse_to['object_description_id']) {
			
			$arr_description_name = ['od', $arr_collapse_to['object_description_id'], ($object_sub_id ? 'osd' : 'od'), $use_object_description_id];
			$use_object_description_id = 'od'.$arr_collapse_to['object_description_id'].'_'.($object_sub_id ? 'osd' : 'od').$use_object_description_id;
		} else if ($arr_collapse_to['object_sub_description_id']) {
			
			$arr_description_name = ['osd', $arr_collapse_to['object_sub_description_id'], ($object_sub_id ? 'osd' : 'od'), $use_object_description_id];
			$use_object_description_id = 'osd'.$arr_collapse_to['object_sub_description_id'].'_'.($object_sub_id ? 'osd' : 'od').$use_object_description_id;
		} else if ($arr_collapse_to['object_sub_location']) {
			
			$arr_description_name = ['osl', $use_object_description_id];
			$use_object_description_id = 'osl'.$use_object_description_id;
		}
		
		$object_definition_id = $use_object_description_id;
		
		if (isset($arr_collect['weight'])) {
			
			if ($arr_style === null) {
				$arr_style = [];
			}
			$arr_style['link'] = ['weight' => $arr_collect['weight']];
		}
		
		if ($arr_style !== null) { // We need to add more grouping as the collapsed condition could be specifically relevant for a relation
			$object_definition_id .= '/'.(isset($arr_ref_object_id[1]) ? implode('/', $arr_ref_object_id) : $arr_ref_object_id[0]);
		}
		
		$use_object_sub_id = ($object_sub_id ?: $arr_collapse_to['object_sub_id'] ?: false); // Keep description at its own sub-object, otherwise the collapsing sub-object, or there is no sub-object applicable
		
		if ($use_object_sub_id) {
			
			$s_arr_parent =& $this->arr_pack_data['object_subs'][$use_object_sub_id];
			$s_arr_new =& $s_arr_parent['object_sub_definitions'][$object_definition_id];
			
			if (!isset($this->arr_pack_data['info']['post_process'][$use_object_description_id])) {
				$this->arr_pack_data['info']['post_process'][$use_object_description_id] = ['ref_type_id' => $ref_type_id, 'name' => $arr_description_name, 'object_sub' => true]; // Post-process name
			}
		} else {
			
			$s_arr_parent =& $this->arr_pack_data['objects'][$arr_collapse_to['object_id']];
			$s_arr_new =& $s_arr_parent['object_definitions'][$object_definition_id];
			
			if (!isset($this->arr_pack_data['info']['post_process'][$use_object_description_id])) {
				$this->arr_pack_data['info']['post_process'][$use_object_description_id] = ['ref_type_id' => $ref_type_id, 'name' => $arr_description_name]; // Post-process name
			}
		}

		if (!$s_arr_new) {
			
			$s_arr_new['description_id'] = $use_object_description_id;
			$s_arr_new['value'] = $arr_value;
			$s_arr_new['ref_object_id'] = $arr_ref_object_id;
			$s_arr_new['style'] = [];
			
		} else { // Stack all (duplicate) references when collapsing
		
			$s_arr_new['value'] = arrMerge($s_arr_new['value'], $arr_value);
			$s_arr_new['ref_object_id'] = arrMerge($s_arr_new['ref_object_id'], $arr_ref_object_id);
		}
		
		if ($arr_style !== null) {
			$this->parseStyle($s_arr_new['style'], $arr_style, $num_depth, $s_arr_parent['style']);
		}
		
		return $s_arr_new;
	}
	
	protected function &parseStyle(&$arr_style_target, $arr_style_add, $num_depth, &$arr_style_parent = null) {
		
		if (!$arr_style_add || ($arr_style_target == $arr_style_add)) {
			return;
		}
		
		$is_deeper = ($num_depth >= ($arr_style_target['depth'] ?? 0));

		foreach ($arr_style_add as $key => $value) {
			
			if ($key === 'conditions') {
							
				foreach ($value as $str_identifier => $arr_condition_value) {
					
					if ($arr_condition_value === null) {
						
						$arr_style_target['conditions'][$str_identifier] = null;
						continue;
					}
					
					$arr_style_target['conditions'][$str_identifier]['weight'] += $arr_condition_value['weight'];
					
					if (isset($arr_condition_value['color'])) {
						$arr_style_target['conditions'][$str_identifier]['color'] = $arr_condition_value['color'];
					}
					if (isset($arr_condition_value['icon'])) {
						$arr_style_target['conditions'][$str_identifier]['icon'] = $arr_condition_value['icon'];
					}
					
					if ($arr_style_parent !== null) {
						$arr_style_parent['conditions'][$str_identifier] = null;
					}
				};
			} else {
				
				if (is_array($value)) { // Array means do not override, but append
					
					if (!isset($arr_style_target[$key])) {
						$arr_style_target[$key] = $value;
					} else if (is_array($arr_style_target[$key])) {
						array_push($arr_style_target[$key], ...$value);
					} else if ($is_deeper) {
						$arr_style_target[$key] = [$arr_style_target[$key]];
						array_push($arr_style_target[$key], ...$value);
					}
				} else {
					
					if ($is_deeper || !isset($arr_style_target[$key])) {
						$arr_style_target[$key] = $value;
					}
				}
			}
		}
		
		if ($is_deeper) {
			$arr_style_target['depth'] = $num_depth;
		}
		
		return $arr_style_target;
	}
	
	public function getPackage() {
		
		if ($this->arr_package_data['pack']) {
			
			$this->arr_package_data['info'] = [];
			$this->arr_package_data['legend'] = [];
			$this->arr_package_data['geometry'] = [];
			$count_objects = 0;
			
			foreach ($this->arr_package_data['pack'] as &$arr_pack_data) {
					
				if (!$arr_pack_data['objects']) {
					continue;
				}
				
				if ($arr_pack_data['info']) {
					$this->arr_package_data['info'] = array_replace_recursive($this->arr_package_data['info'], $arr_pack_data['info']);
				}
				
				$date_min = $arr_pack_data['date_range']['min'];
				$date_max = $arr_pack_data['date_range']['max'];
				$s_date_package_min =& $this->arr_package_data['date_range']['min'];
				$s_date_package_max =& $this->arr_package_data['date_range']['max'];
				
				if ($date_min && (!$s_date_package_min || $date_min < $s_date_package_min)) {
					$s_date_package_min = $date_min;
				}
				if ($date_max && (!$s_date_package_max || $date_max > $s_date_package_max)) {
					$s_date_package_max = $date_max;
				}
				
				if ($arr_pack_data['geometry']) {
					$this->arr_package_data['geometry'] += $arr_pack_data['geometry'];
				}
				
				unset($arr_pack_data['info'], $arr_pack_data['date_range'], $arr_pack_data['geometry']);
				
				$count_objects += count($arr_pack_data['objects']);
			}
			
			if ($count_objects) {
				
				$date_now = (int)(date('Ymd').FormatTypeObjects::DATE_INT_SEQUENCE_NULL);
				if (!$this->arr_package_data['date_range']['min']) {
					$this->arr_package_data['date_range']['min'] = $date_now;
				}
				if (!$this->arr_package_data['date_range']['max']) {
					$this->arr_package_data['date_range']['max'] = $date_now;
				}

				// Gather information on types and subobjects for (quick) external access and legends
				
				$arr_legend = [];
				$arr_html_legend = [];
				$arr_types_found = [];
				
				foreach ($this->arr_type_sets as $type_id => $arr_type_set) {
					
					$this->arr_package_data['info']['types'][$arr_type_set['type']['id']] = ['name' => Labels::parseTextVariables($arr_type_set['type']['name'])];
					$arr_types_found[$type_id] = $type_id;
					
					foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
						
						if (!isset($this->arr_collect_info['definitions_found']['object_definition_'.$object_description_id])) {
							continue;
						}
						
						if (!data_model::checkClearanceTypeConfiguration(StoreType::CLEARANCE_PURPOSE_VIEW, $arr_type_set, $object_description_id)) {
							continue;
						}
						
						$is_mutable = is_array($arr_object_description['object_description_ref_type_id']);
						
						if (!$is_mutable) {
							
							$this->arr_package_data['info']['object_descriptions'][$object_description_id] = ['object_description_ref_type_id' => $arr_object_description['object_description_ref_type_id'], 'object_description_name' => Labels::parseTextVariables($arr_object_description['object_description_name'])];
							
							if ($arr_object_description['object_description_ref_type_id']) {
								$arr_types_found[$arr_object_description['object_description_ref_type_id']] = $arr_object_description['object_description_ref_type_id'];
							}
						}
						
						if ($is_mutable || $arr_object_description['object_description_is_dynamic']) {
							
							foreach ((array)$this->arr_collect_info['types_found']['object_definition_'.$object_description_id] as $found_type_id) {
								
								$this->arr_package_data['info']['object_descriptions'][$object_description_id.'_'.$found_type_id] = ['object_description_ref_type_id' => $found_type_id, 'object_description_name' => Labels::parseTextVariables($arr_object_description['object_description_name'].' ('.$this->arr_types_all[$found_type_id]['name'].')')];
								$arr_types_found[$found_type_id] = $found_type_id;
							}
						}
					}
					
					foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
						
						if (!isset($this->arr_collect_info['definitions_found']['object_sub_'.$object_sub_details_id])) {
							continue;
						}
						
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view']) {
							continue;
						}

						$str_object_sub_details_name = Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name']);
						
						$this->arr_package_data['info']['object_sub_details'][$arr_object_sub_details['object_sub_details']['object_sub_details_id']] = ['object_sub_details_name' => $str_object_sub_details_name];
						$arr_legend['object_sub_details'][$object_sub_details_id] = '<span>'.Labels::parseTextVariables($arr_type_set['type']['name']).' </span><span class="sub-name">'.$str_object_sub_details_name.'</span>';
						
						foreach ((array)$arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
							
							if (!isset($this->arr_collect_info['definitions_found']['object_sub_definition_'.$object_sub_description_id])) {
								continue;
							}
							
							if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view']) {
								continue;
							}
							
							$is_mutable = is_array($arr_object_sub_description['object_sub_description_ref_type_id']);
						
							if (!$is_mutable) {
							
								$this->arr_package_data['info']['object_sub_descriptions'][$object_sub_description_id] = ['object_sub_description_ref_type_id' => $arr_object_sub_description['object_sub_description_ref_type_id'], 'object_sub_description_name' => Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name'])];
								
								if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
									$arr_types_found[$arr_object_sub_description['object_sub_description_ref_type_id']] = $arr_object_sub_description['object_sub_description_ref_type_id'];
								}
							}
							
							if ($is_mutable || $arr_object_sub_description['object_sub_description_is_dynamic']) {
								
								foreach ((array)$this->arr_collect_info['types_found']['object_sub_definition_'.$object_sub_description_id] as $found_type_id) {
									
									$this->arr_package_data['info']['object_sub_descriptions'][$object_sub_description_id.'_'.$found_type_id] = ['object_sub_description_ref_type_id' => $found_type_id, 'object_sub_description_name' => Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name'].' ('.$this->arr_types_all[$found_type_id]['name'].')')];
									$arr_types_found[$found_type_id] = $found_type_id;
								}
							}
						}
					}
				}
				
				if (isset($this->arr_collect_info['types_found']['locations'])) {
					$arr_types_found += $this->arr_collect_info['types_found']['locations'];
				}
				
				// Post-process collapsed Description names
				
				foreach (($this->arr_package_data['info']['post_process'] ?? []) as $use_object_description_id => $arr_description_name) {
					
					$str_name = data_model::SYMBOL_COLLAPSED.' ';
					$str_identifier = $arr_description_name['name'][3];
					
					if ($arr_description_name['name'][2] == 'od') {

						if (!isset($this->arr_package_data['info']['object_descriptions'][$str_identifier])) {
							$str_identifier .= '_'.$arr_description_name['ref_type_id'];
						}
						
						$str_name .= $this->arr_package_data['info']['object_descriptions'][$str_identifier]['object_description_name'];
					} else if ($arr_description_name['name'][2] == 'osd') {
						
						if (!isset($this->arr_package_data['info']['object_sub_descriptions'][$str_identifier])) {
							$str_identifier .= '_'.$arr_description_name['ref_type_id'];
						}
						
						$str_name .= $this->arr_package_data['info']['object_sub_descriptions'][$str_identifier]['object_sub_description_name'];
					} else {
						
						$str_name .= getLabel('lbl_location');
					}
					
					if ($arr_description_name['object_sub']) {
						$this->arr_package_data['info']['object_sub_descriptions'][$use_object_description_id] = ['object_sub_description_ref_type_id' => $arr_description_name['ref_type_id'], 'object_sub_description_name' => $str_name];
					} else {
						$this->arr_package_data['info']['object_descriptions'][$use_object_description_id] = ['object_description_ref_type_id' => $arr_description_name['ref_type_id'], 'object_description_name' => $str_name];
					}
				}
				unset($this->arr_package_data['info']['post_process']);

				// Coloring
				$arr_colors = [
					['start' => 237/360, 'stop' => 208/360, 'sat' => .75, 'val' => .95, 'name' => 'blue'],
					['start' => 0, 'stop' => 22/360, 'sat' => .75, 'val' => .95, 'name' => 'red'],
					['start' => 284/360, 'stop' => 310/360, 'sat' => .75, 'val' => .95, 'name' => 'purple'],
					['start' => 202/360, 'stop' => 181/360, 'sat' => .75, 'val' => .95, 'name' => 'turquoise'],
					['start' => 28/360, 'stop' => 38/360, 'sat' => .75, 'val' => .95, 'name' => 'orange'],
					['start' => 120/360, 'stop' => 80/360, 'sat' => .75, 'val' => .95, 'name' => 'green'],
					['start' => 360/360, 'stop' => 360/360, 'sat' => .0, 'val' => .85, 'name' => 'other']
				];
				$arr_color_full = ['start' => 0, 'stop' => 1, 'sat' => .75, 'val' => .95, 'name' => 'full'];
				
				if (isset($arr_legend['object_sub_details'])) {
					
					$num_total = count($arr_legend['object_sub_details']);
					$num_total_colors = count($arr_colors);
					$i = 0;
					
					foreach ($arr_legend['object_sub_details'] as $object_sub_details_id => $str_object_sub_details_name) {
						
						if ($i >= $num_total_colors) {
							
							$cur_color = $arr_color_full;
							
							$range = ($cur_color['stop'] - $cur_color['start']) / ($num_total - $num_total_colors);
							$cur_color['start'] = $range * ($num_total - 1 - $i);
							$cur_color['stop'] = $range * (($num_total - 1 - $i) + 1);
							
						} else {
							
							$cur_color = ($arr_colors[$i] ?: end($arr_colors));
						}

						$this->arr_package_data['legend']['object_subs'][$object_sub_details_id] = [
							'color' => self::HSV2RGB($cur_color['start'], $cur_color['sat'], $cur_color['val'])
						];
						
						$arr_info = $this->arr_package_data['legend']['object_subs'][$object_sub_details_id];
						$arr_html_legend['object_sub_details'][$object_sub_details_id] = '<div data-identifier="'.$object_sub_details_id.'"><dt>'.$str_object_sub_details_name.'</dt><dd><span style="background-color: rgb('.$arr_info['color']['r'].','.$arr_info['color']['g'].','.$arr_info['color']['b'].');"></span></dd></div>';
						
						$i++;
					}
				}

				foreach ($arr_types_found as $type_id => $value) {
					
					if ($this->arr_types_all[$type_id]['color'] || !empty($this->arr_project['types'][$type_id]['color'])) {
						
						$str_color = ($this->arr_project['types'][$type_id]['color'] ?? null ?: $this->arr_types_all[$type_id]['color']);
						
						$this->arr_package_data['legend']['types'][$type_id] = [
							'color' => $str_color
						];
						$arr_html_legend['types'][$type_id] = '<div data-identifier="'.$type_id.'"><dt>'.Labels::parseTextVariables($this->arr_types_all[$type_id]['name']).'</dt><dd><span style="background-color: '.$str_color.';"></span></dd></div>';
					}
				}
				
				$arr_condition_settings = [];
				
				foreach ($this->arr_collect_info['conditions'] as $arr_conditions) {
					
					foreach ((array)$arr_conditions['object'] as $arr_condition_setting) {
									
						$arr_condition_settings[] = $arr_condition_setting;
					}
					
					foreach ((array)$arr_conditions['object_descriptions'] as $object_description_id => $arr_conditions_object_description) {
						foreach ($arr_conditions_object_description as $arr_condition_setting) {
							
							$arr_condition_settings[] = $arr_condition_setting;
						}
					}
					
					foreach ((array)$arr_conditions['object_sub_details'] as $object_sub_details_id => $arr_conditions_object_sub_details) {
						
						foreach ((array)$arr_conditions_object_sub_details['object_sub_details'] as $arr_condition_setting) {
						
							$arr_condition_settings[] = $arr_condition_setting;
						}
						
						foreach ((array)$arr_conditions_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_conditions_object_sub_description) {
							foreach ($arr_conditions_object_sub_description as $arr_condition_setting) {
							
								$arr_condition_settings[] = $arr_condition_setting;
							}
						}
					}
				}
				
				foreach ($arr_condition_settings as $key => $arr_condition_setting) {

					if (!$arr_condition_setting['condition_in_object_nodes_referencing'] && !$arr_condition_setting['condition_in_object_nodes_object']) {
						continue;
					}
					
					$str_identifier = $arr_condition_setting['condition_identifier'];
					
					$str_label = $arr_condition_setting['condition_label'];
					if (isset($this->arr_collect_info['conditions_found'][$str_identifier])) {
						$str_label .= ($str_label ? ' ' : '').$this->arr_collect_info['conditions_found'][$str_identifier];
					}
					$str_label = Labels::parseTextVariables($str_label);
					
					$str_color = ($arr_condition_setting['condition_actions']['color']['color'] ?? null);
					$str_weight = ($arr_condition_setting['condition_actions']['weight']['number'] ?? null);
					$str_icon = ($arr_condition_setting['condition_actions']['icon']['image'] ?? null);
					
					if ($str_icon) {
						$str_icon = '/'.DIR_CUSTOM_PROJECT_WORKSPACE.$str_icon;
					}

					$this->arr_package_data['legend']['conditions'][$str_identifier] = [
						'label' => $str_label,
						'color' => $str_color,
						'weight' => $str_weight,
						'icon' => $str_icon
					];
					
					if (!$arr_condition_setting['condition_label'] || (!$str_color && !$str_icon)) {
						continue;
					}
				
					$arr_html_legend['conditions'][] = '<div data-identifier="'.strEscapeHTML($str_identifier).'"><dt>'.$str_label.'</dt><dd><span style="background-color: '.$str_color.';"></span></dd></div>';
				}
				
				$this->arr_package_html = '<div class="labmap">
					<div class="map"></div>
					<div class="controls">
						<div class="geo hide"></div>
						<div class="soc hide"></div>
						<div class="time hide"></div>
						<div class="timeline">
							<div>
								<div class="slider"></div>
								<div class="buttons">
									<input type="button" id="y:data_visualise:review_data-date" value="'.getLabel('lbl_view_selection').'" />
								</div>
							</div>
						</div>
						<div class="legends">
							'.($arr_html_legend['types'] ? '<figure class="types">
								<dl>'.implode('', $arr_html_legend['types']).'</dl>
							</figure>' : '').'
							'.($arr_html_legend['object_sub_details'] ? '<figure class="object-sub-details">
								<dl>'.implode('', $arr_html_legend['object_sub_details']).'</dl>
							</figure>' : '').'
							'.($arr_html_legend['conditions'] ? '<figure class="conditions">
								<dl>'.implode('', $arr_html_legend['conditions']).'</dl>
							</figure>' : '').'
						</div>
					</div>
				</div>';
			}
			
			status(getLabel('msg_transferring'), false, false, ['persist' => true, 'duration' => 0]);
		}
		
		$this->arr_package_data['time'] = ['bounds' => [], 'selection' => []];
		if ($this->arr_frame['time']['bounds']['date_start'] && $this->arr_frame['time']['bounds']['date_end']) {
			$this->arr_package_data['time']['bounds']['min'] = $this->parseDate($this->arr_frame['time']['bounds']['date_start']);
			$this->arr_package_data['time']['bounds']['max'] = $this->parseDate($this->arr_frame['time']['bounds']['date_end']);
		}
		if ($this->arr_frame['time']['selection']['date_start'] && $this->arr_frame['time']['selection']['date_end']) {
			$this->arr_package_data['time']['selection']['min'] = $this->parseDate($this->arr_frame['time']['selection']['date_start']);
			$this->arr_package_data['time']['selection']['max'] = $this->parseDate($this->arr_frame['time']['selection']['date_end']);
		}
		
		$this->arr_package_data['center'] = null;
		if ($this->arr_frame['area']['geo']['latitude'] || $this->arr_frame['area']['geo']['longitude']) {
			$this->arr_package_data['center'] = [];
			$this->arr_package_data['center']['coordinates']['latitude'] = $this->arr_frame['area']['geo']['latitude'];
			$this->arr_package_data['center']['coordinates']['longitude'] = $this->arr_frame['area']['geo']['longitude'];
		}
		$this->arr_package_data['focus'] = null;
		if ($this->arr_frame['area']['social']['object_id']) {
			$this->arr_package_data['focus'] = ['object_id' => $this->arr_frame['area']['social']['object_id']];
		}
		$this->arr_package_data['zoom'] = [];
		if ($this->arr_frame['area']['geo']['zoom']['scale']) {
			$this->arr_package_data['zoom']['scale'] = $this->arr_frame['area']['geo']['zoom']['scale'];
		}
		$this->arr_package_data['zoom']['geo']['min'] = ($this->arr_frame['area']['geo']['zoom']['min'] ?: 1);
		$this->arr_package_data['zoom']['geo']['max'] = ($this->arr_frame['area']['geo']['zoom']['max'] ?: 18);
		if ($this->arr_frame['area']['social']['zoom']['level']) {
			$this->arr_package_data['zoom']['level'] = $this->arr_frame['area']['social']['zoom']['level'];
		}
		$this->arr_package_data['zoom']['social']['min'] = ($this->arr_frame['area']['social']['zoom']['min'] ?: -7);
		$this->arr_package_data['zoom']['social']['max'] = ($this->arr_frame['area']['social']['zoom']['max'] ?: 7);
		
		$this->arr_package_data['settings'] = []; // Include any setting that could change the data
		if ($this->arr_frame['object_subs']['unknown']) {
			$this->arr_package_data['settings']['object_subs']['unknown'] = $this->arr_frame['object_subs']['unknown'];
		}
		if ($this->arr_package_data['center']) {
			$this->arr_package_data['settings']['center'] = $this->arr_package_data['center'];
		}
		
		$this->arr_package_data['attribution'] = ['base' => getLabel('lbl_site_attribution')];
		if ($this->attribution) {
			$this->arr_package_data['attribution']['source'] = $this->attribution;
		}
		
		return $this->arr_data;
	}
	
	protected function parseDate($date) {
		
		if (!$date) {
			return 0;
		}
		
		return (int)preg_replace(['/0000(.{4})$/', '/00(.{4})$/'], ['0101$1', '01$1'], $date);
	}
	
	protected function parseGeometry($str_geometry, $str_identifier_use = null) {
				
		if (!$str_geometry) {
			return '';
		}
		
		$str_identifier = ($this->arr_pack_lookup_geometry[$str_geometry] ?? null);
		
		if ($str_identifier === null) {
			
			if ($str_identifier_use !== null) { // Any identifier used for lookup has to be unique across multiple data packages, e.g. sub-object ID
				$str_identifier = '_'.$str_identifier_use;
			} else {
				$str_identifier = value2Hash($str_geometry);
			}
			
			$this->arr_pack_data['geometry'][$str_identifier] = $str_geometry;
			
			$this->arr_pack_lookup_geometry[$str_geometry] = $str_identifier;
		}
		
		return $str_identifier;
	}

	public static function HSV2RGB($h, $s, $v) {
	
		//1
		$h *= 6;
		//2
		$i = floor($h);
		$f = $h - $i;
		//3
		$m = $v * (1 - $s);
		$n = $v * (1 - $s * $f);
		$k = $v * (1 - $s * (1 - $f));
		//4
		switch ($i) {
			case 0:
				list($r,$g,$b) = [$v,$k,$m];
				break;
			case 1:
				list($r,$g,$b) = [$n,$v,$m];
				break;
			case 2:
				list($r,$g,$b) = [$m,$v,$k];
				break;
			case 3:
				list($r,$g,$b) = [$m,$n,$v];
				break;
			case 4:
				list($r,$g,$b) = [$k,$m,$v];
				break;
			case 5:
			case 6: // For when $h=1 is given
				list($r,$g,$b) = [$v,$m,$n];
				break;
		}
		$r = round($r * 255);
		$g = round($g * 255);
		$b = round($b * 255);
		
		return ['r' => $r, 'g' => $g, 'b' => $b];
	}
	
	public static function RGB2HSL($r, $g, $b, $a = 1) {
		
		$r /= 255;
		$g /= 255;
		$b /= 255;

		$max = max($r, $g, $b);
		$min = min($r, $g, $b);
		$h = $s = $l = ($max + $min) / 2;

		if ($max == $min) {
			
			$h = $s = 0; // Achromatic
		} else {
			
			$d = $max - $min;
			$s = ($l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min));

			switch ($max) {
				case $r:
					$h = ($g - $b) / $d + ($g < $b ? 6 : 0);
					break;
				case $g:
					$h = ($b - $r) / $d + 2;
					break;
				case $b:
					$h = ($r - $g) / $d + 4;
					break;
			}

			$h /= 6;
		}

		return ['h' => $h, 's' => $s, 'l' => $l, 'a' => $a];
	}
	
	public static function parseCSSColor($str_color) {
		
		$arr_rgb = ['r' => 255, 'g' => 255, 'b' => 255, 'a' => 1];
		$num_length = strlen($str_color);

		if ($num_length == 7 || $num_length == 9) {
			
			$arr_rgb['r'] = hexdec($str_color[1].$str_color[2]);
			$arr_rgb['g'] = hexdec($str_color[3].$str_color[4]);
			$arr_rgb['b'] = hexdec($str_color[5].$str_color[6]);
			if ($num_length == 9) {
				$arr_rgb['a'] = round(hexdec($str_color[7].$str_color[8]) / 255, 3);
			}
		} elseif ($num_length == 4 || $num_length == 5) {

			$arr_rgb['r'] = hexdec($str_color[1].$str_color[1]);
			$arr_rgb['g'] = hexdec($str_color[2].$str_color[2]);
			$arr_rgb['b'] = hexdec($str_color[3].$str_color[3]);
			if ($num_length == 5) {
				$arr_rgb['a'] = round(hexdec($str_color[4].$str_color[4]) / 255, 3);
			}
		}
		
		return $arr_rgb;
	}
	
	public static function color2WeightedColor($num_weight, $str_color, $str_color_secondary = null) {
		
		static $arr_parsed_hsl = [];
		
		if (!isset($arr_parsed_hsl[$str_color])) {
			
			$arr_rgb = static::parseCSSColor($str_color);
			$arr_parsed_hsl[$str_color] = static::RGB2HSL($arr_rgb['r'], $arr_rgb['g'], $arr_rgb['b'], $arr_rgb['a']);
		}
		
		$arr_hsl = $arr_parsed_hsl[$str_color];
		
		if (isset($str_color_secondary)) {
			
			if (!isset($arr_parsed_hsl[$str_color_secondary])) {
			
				$arr_rgb = static::parseCSSColor($str_color_secondary);
				$arr_parsed_hsl[$str_color_secondary] = static::RGB2HSL($arr_rgb['r'], $arr_rgb['g'], $arr_rgb['b'], $arr_rgb['a']);
			}
			
			$arr_hsl_low = $arr_parsed_hsl[$str_color_secondary];
			
			// Average and weight old (secondary/source) and new (primary/target) hsl values
			
			$num_h = $arr_hsl['h']; // Hue is a special case, as it's circular. Given a range between 0 and 1, e.g. 0.2 and 0.9, the shortest circular distance is -0.3
			$num_difference = ($num_h - $arr_hsl_low['h']);
			
			if ($num_difference < -0.5) {
				
				$num_difference = ($num_difference + 1);
				$num_h = ($arr_hsl_low['h'] + ($num_difference * $num_weight));
				$num_h = ($num_h > 1 ? $num_h - 1 : $num_h);
			} else if ($num_difference > 0.5) {
				
				$num_difference = ($num_difference - 1);
				$num_h = ($arr_hsl_low['h'] + ($num_difference * $num_weight));
				$num_h = ($num_h < 0 ? $num_h + 1 : $num_h);
			} else {
				
				$num_h = ($arr_hsl_low['h'] + ($num_difference * $num_weight));
			}
			
			$arr_hsl['h'] = $num_h;
			$arr_hsl['s'] = ($arr_hsl_low['s'] + (($arr_hsl['s'] - $arr_hsl_low['s']) * $num_weight));
			$arr_hsl['l'] = ($arr_hsl_low['l'] + (($arr_hsl['l'] - $arr_hsl_low['l']) * $num_weight));
			if ($arr_hsl['a'] !== $arr_hsl_low['a']) {
				$arr_hsl['a'] = ($arr_hsl_low['a'] + (($arr_hsl['a'] - $arr_hsl_low['a']) * $num_weight));
			}
		} else {
		
			// Apply hue bias based on weight
			//$num_bias = ($num_weight - 0.5) * 2; // -1 to +1
			//$num_h_shift = 0.08 * $num_bias; // Strong warm/cool bias
			//$arr_hsl['h'] = fmod(($arr_hsl['h'] + $num_h_shift + 1), 1.0); // Wrap-around hue (0..1)

			// Lightness range 0.2 - 0.9
			$arr_hsl['l'] = 0.2 + ($num_weight * 0.7); // Center on half weight

			// Adapt saturation to avoid washed-out colors
			$arr_hsl['s'] = max($arr_hsl['s'] + (($num_weight - 0.5) * ((1 - $arr_hsl['s']) * 2)), 0.3); // Center on half weight, keep min 0.3 saturation
		}
		
		return 'hsl('.(int)($arr_hsl['h']*360).' '.(int)($arr_hsl['s']*100).'% '.(int)($arr_hsl['l']*100).'%'.($arr_hsl['a'] !== 1 ? ' / '.round($arr_hsl['a'], 3) : '').')';
	}
}
