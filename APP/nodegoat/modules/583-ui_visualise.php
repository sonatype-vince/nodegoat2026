<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ui_visualise extends base_module {
	
	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	protected $arr_access = [
		'data_filter' => [],
		'data_view' => [],
		'data_visualise' => [
			'*' => false,
			'visualise' => true,
			'visualise_soc' => true,
			'visualise_time' => true,
			'review_data' => true
		],
		'ui' => [],
		'ui_view_objects' => [],
		'ui_view_object' => [],
		'ui_data' => [],
		'ui_selection' => []
	];
	
	public static function getScopeDateFilter($type_id, $scope_id) {
	
		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		$arr_project = StoreCustomProject::getProjects($public_user_interface_active_custom_project_id);
		$arr_ref_type_ids = StoreCustomProject::getScopeTypes($public_user_interface_active_custom_project_id);
					
		$arr_scope = data_visualise::getTypeScope($type_id);
		
		$filter_date_start = SiteStartEnvironment::getFeedback('filter_date_start');
		$filter_date_end = SiteStartEnvironment::getFeedback('filter_date_end');
		
		$arr_object_filter = [];
		
		if ($arr_scope['paths'] && ($filter_date_start || $filter_date_end)) {
						
			$trace = new TraceTypesNetwork(array_keys($arr_project['types']), true, true);
			$trace->filterTypesNetwork($arr_scope['paths']);
			$trace->run($type_id, false, cms_nodegoat_details::$num_network_trace_depth);
			$arr_type_network_paths = $trace->getTypeNetworkPaths(true);

			$collect = new CollectTypesObjects($arr_type_network_paths, GenerateTypeObjects::VIEW_VISUALISE);
			$collect->setScope(['types' => $arr_ref_type_ids, 'project_id' => $public_user_interface_active_custom_project_id]);
			$collect->init(false);
				
			$arr_collect_info = $collect->getResultInfo();
			
			foreach ($arr_collect_info['types'] as $reference_type_id => $arr_reference) {
			
				if ($reference_type_id == $type_id) {
					continue;
				}
				
				$arr_object_filter = ['type_id' => $type_id, 'options' => ['operator' => 'and'], 'referenced_types' => [$reference_type_id => ['any' => [[['object_filter' => [['object_subs' => [['object_sub_dates' => [['object_sub_date_type' => 'range', 'object_sub_date_from' => $filter_date_start, 'object_sub_date_to' => $filter_date_end]]]]]]]]]]]];

			}
		}
		
		return $arr_object_filter;		
	
	}

	public static function createVisualisation($value, $explore_object_id = false) {

		$arr = [];
		
		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		$arr_project = StoreCustomProject::getProjects($public_user_interface_active_custom_project_id);
		
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$arr_public_user_interface_module_vars = SiteStartEnvironment::getFeedback('arr_public_user_interface_module_vars');	
		$data_display_mode = $arr_public_user_interface_module_vars['display_mode'];
		
		$arr_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id, $public_user_interface_active_custom_project_id, false);
		$arr_selected_types = SiteStartEnvironment::getFeedback('selected_type_ids');
		$scenario_id = false;

		if ($arr_selected_types) {
			$arr_types = array_intersect($arr_types, $arr_selected_types);
		}			
			
		if ($explore_object_id) {
			
			$arr_id = explode('_', $explore_object_id);
			$type_id = (int)$arr_id[0];
			$object_id = (int)$arr_id[1];
			$data_display_mode = $arr_id[2];
			
			$arr_explore_filter = ['objects' => $object_id];
			
			$use_custom_project_id = ui::checkPrimaryProjectProjectID($type_id);
			
			if ($use_custom_project_id) {
				
				$public_user_interface_active_custom_project_id = $use_custom_project_id;
				$_SESSION['custom_projects']['project_id'] = $use_custom_project_id;
				$arr_project = StoreCustomProject::getProjects($public_user_interface_active_custom_project_id);
			}
	
			$explore_scope_id = (is_array($arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['scope']['explore'][$type_id]) ? $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['scope']['explore'][$type_id][$data_display_mode] : false);

			if ($explore_scope_id) {		
				
				SiteEndEnvironment::setFeedback('scope_id', $explore_scope_id, true);
			}
			
			$arr_types = [$type_id => true];
			
		} else {

			if (SiteStartEnvironment::getFeedback('scenario_id')) {
				
				$scenario_id = (int)SiteStartEnvironment::getFeedback('scenario_id');
				$arr_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($public_user_interface_active_custom_project_id, false, false, $scenario_id);
				$arr_types = [$arr_scenario['type_id'] => true];
							
				toolbar::setScenario($scenario_id);	
			}

			$arr_type_filters = toolbar::getFilter();
		}
		
		$create_visualisation_package = false;
		
		foreach ((array)$arr_types as $type_id => $values) {
			
			if (!$create_visualisation_package) {
				
				$arr_frame = data_visualise::getTypeFrame($type_id);
				
				if ($explore_object_id) {
					$arr_frame['area']['geo'] = ['latitude' => false, 'longitude' => false];
				}

				$arr_visual_settings = data_visualise::getVisualSettings(true, true);
				$arr_types_all = StoreType::getTypes();
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
				$create_visualisation_package = new CreateVisualisationPackage($arr_project, $arr_types_all, $arr_frame, $arr_visual_settings);
				$create_visualisation_package->setOutput($arr);
			}
			
			if ($explore_object_id) {
				
				$arr_filters = $arr_explore_filter;
				
			} else {
				
				$arr_filters = ($arr_type_filters ? current($arr_type_filters) : []);
				$browse_scope_id = false;
				
				if (is_array($arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['scope']['browse'][$type_id])) {
					
					$browse_scope_id = $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['scope']['browse'][$type_id][$data_display_mode];
				}

				if (!$scenario_id && $browse_scope_id) {

					SiteEndEnvironment::setFeedback('scope_id', $browse_scope_id, true);
					
					$arr_object_filter = self::getScopeDateFilter($type_id, $browse_scope_id);
					
					if (count($arr_object_filter)) {
						
						$arr_filters['object_filter'][] = $arr_object_filter;
					}	
				}
			}
			
			$arr_scope = data_visualise::getTypeScope($type_id);	
			$arr_context = data_visualise::getTypeContext($type_id);
			$arr_conditions = toolbar::getTypeConditions($type_id);
			$arr_ordering = current(toolbar::getOrder());
			
			$collect = data_visualise::getVisualisationCollector($type_id, $arr_filters, $arr_scope, $arr_conditions, $arr_ordering);
			$arr_collect_info = $collect->getResultInfo();
			$arr_collect_info['settings'] = $collect->getPathOptions();
			
			$active_scenario_id = toolbar::checkActiveScenario();
			$active_scenario_hash = false;
			
			if ($active_scenario_id) {
				
				$arr_model_condition_ids = toolbar::getTypeModelConditionIDs($type_id);
				$arr_collect_info['model_conditions'] = $arr_model_condition_ids;

				$active_scenario_hash = CacheProjectTypeScenario::generateHashVisualise($_SESSION['custom_projects']['project_id'], $active_scenario_id, $arr_collect_info);
			}

			$identifier_data = $type_id.'_'.value2Hash(serialize($arr_collect_info).'_'.serialize($arr_context));
			$identifier_date = DBFunctions::numTimeNow();
			
			$has_data = ($value['identifier'] && $value['identifier']['data'] == $identifier_data);
			
			if ($has_data) {

				$is_updated = FilterTypeObjects::getTypesUpdatedAfter($value['identifier']['date'], StoreCustomProject::getScopeTypes($_SESSION['custom_projects']['project_id']), true);
				
				if ($is_updated) {
					$has_data = false;
				} else {
					$identifier_date = $value['identifier']['date'];
				}
			}
			
			$arr_nodegoat_details = cms_nodegoat_details::getDetails();
			if ($arr_nodegoat_details['processing_time']) {
				timeLimit($arr_nodegoat_details['processing_time']);
			}
			if ($arr_nodegoat_details['processing_memory']) {
				memoryBoost($arr_nodegoat_details['processing_memory']);
			}
		
			if (!$has_data) {

				if ($active_scenario_hash) {
						
					$arr_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], false, false, $active_scenario_id, $arr_use_project_ids);

					if ($arr_scenario['cache_retain']) {
						
						// Possibility for additional boosting procedures
					}
				}

				$create_visualisation_package->addType($type_id, $collect, $arr_filters, $arr_scope, $arr_conditions, $active_scenario_id, $active_scenario_hash);

				if ($arr_context['include']) {

					foreach ($arr_context['include'] as $arr_include) {

						$context_type_id = $arr_include['type_id'];
						$context_scenario_id = $arr_include['scenario_id'];
						$arr_context_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], false, false, $context_scenario_id, $arr_use_project_ids);
						
						if (!$arr_context_scenario) {
							continue;
						}
						
						SiteEndEnvironment::setFeedback('context', ['type_id' => $context_type_id], true);
						
						$arr_filters = toolbar::getScenarioFilters($context_scenario_id);
						
						$cur_scope_id = SiteStartEnvironment::getFeedback('scope_id');
						SiteEndEnvironment::setFeedback('scope_id', ($arr_context_scenario['scope_id'] ?: false), true);
						$arr_scope = data_visualise::getTypeScope($context_type_id);
						SiteEndEnvironment::setFeedback('scope_id', $cur_scope_id, true);
						
						$cur_condition_id = SiteStartEnvironment::getFeedback('condition_id');
						SiteEndEnvironment::setFeedback('condition_id', ($arr_context_scenario['condition_id'] ?: false), true);
						$arr_conditions = toolbar::getTypeConditions($context_type_id);
						SiteEndEnvironment::setFeedback('condition_id', $cur_condition_id, true);

						$collect = data_visualise::getVisualisationCollector($context_type_id, $arr_filters, $arr_scope, $arr_conditions);
						$arr_collect_info = $collect->getResultInfo();
						$arr_collect_info['settings'] = $collect->getPathOptions();
						
						$arr_model_condition_ids = toolbar::getTypeModelConditionIDs($context_type_id);
						$arr_collect_info['model_conditions'] = $arr_model_condition_ids;
						
						$context_scenario_hash = CacheProjectTypeScenario::generateHashVisualise($_SESSION['custom_projects']['project_id'], $context_scenario_id, $arr_collect_info);
						
						$create_visualisation_package->addType($context_type_id, $collect, $arr_filters, $arr_scope, $arr_conditions, $context_scenario_id, $context_scenario_hash);
					}
					
					SiteEndEnvironment::setFeedback('context', null, true);
				}
			}
		}
		
		$create_visualisation_package->getPackage();
		
		$arr['identifier'] = ['data' => $identifier_data, 'date' => $identifier_date];
	
		// Send empty set to render visualisation with no results, in stead of emtpy space
		if (!$arr['data']['pack'][0]['objects']) {
			
			$arr['no_data'] = true;
			$arr['data']['date_range'] = ['min' => 100012315000, 'max' => 100101015000];
			$arr['data']['pack'][0]['objects'] = [0 => []];
			$arr['data']['legend'] = [];
			$arr['data']['info'] = [];
			$arr['html'] = '<div class="labmap">
					<div class="map"></div>
					<div class="controls"><div class="timeline hide"><div><div class="slider"></div></div></div><div class="legends"></div></div>
				</div>';
		}

		$_SESSION['custom_projects']['project_id'] = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');

		return $arr;
					
	}
			
	public static function css() {
		
		$return = '';
	
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.dynamic('project_visualise', function(elm_scripter) {
					
					var elm_visualisation = elm_scripter.find('[id^=y\\\:ui_visualise\\\:get_visulisation_data]');

					var func_run_visualisation = function(elm_target) {
					
						const elm_ui = elm_scripter.closest('.ui');
						const type = elm_visualisation.attr('data-visualisation_type');
						let obj_data = elm_scripter[0].obj_data;
					
						let elm_overlay = elm_scripter.find('.vis > .data-container');
						let elm_map = elm_overlay.find('.labmap');
						let is_new = true;
						let is_same = false;
						let has_new_data = false;
			
						if (elm_map.length) {
						
							is_new = false;
							is_same = elm_map.hasClass(type);
							elm_map.removeClass('plot soc line').addClass(type);
							elm_map.children('.controls').children('.geo, .soc').addClass('hide');
						}
					
						const func_handle_click = function() {
						
							elm_map.on('click', '[id=y\\\:data_visualise\\\:review_data-date]', function() {

								var cur_elm = $(this);
								var obj_labmap = cur_elm.closest('.labmap')[0].labmap;
								
								var dateint_range = obj_labmap.getDateRange();
								var dateinta_range = {min: DATEPARSER.dateInt2Absolute(dateint_range.min), max: DATEPARSER.dateInt2Absolute(dateint_range.max)};
								var arr_data = obj_labmap.getData();

								var arr_type_object_ids = {};
								
								for (var type_id in arr_data.info.types) { // Prepare and order the Types list
									arr_type_object_ids[type_id] = {};
								}
								
								var arr_value = {use_visualise: true, type_object_ids: arr_type_object_ids};
								
								// Single date sub-objects
								for (var i = 0, len = arr_data.date.arr_loop.length; i < len; i++) {
									
									var date = arr_data.date.arr_loop[i];
									var dateinta = DATEPARSER.dateInt2Absolute(date);
									var in_range = (dateinta >= dateinta_range.min && dateinta <= dateinta_range.max);
									
									if (!in_range) {
										continue;
									}

									var arr_object_subs = arr_data.date[date];
									
									for (var j = 0, len_j = arr_object_subs.length; j < len_j; j++) {
									
										var object_sub_id = arr_object_subs[j];
										var arr_object_sub = arr_data.object_subs[object_sub_id];
							
										if (arr_object_sub.object_sub_details_id === 'object') { // Dummy sub-object
											continue;
										}
																
										var object_id = arr_object_sub.object_id;
										var type_id = arr_data.objects[object_id].type_id;
										arr_type_object_ids[type_id][object_id] = object_id;
										
										// Full objects and possible partake in scopes
										for (var i_connected = 0, len_i_connected = arr_object_sub.connected_object_ids.length; i_connected < len_i_connected; i_connected++) {
											
											var connected_object_id = arr_object_sub.connected_object_ids[i_connected];	
											var in_scope = (connected_object_id != object_id);
								
											if (in_scope) {			
												var type_id = arr_data.objects[connected_object_id].type_id;
												arr_type_object_ids[type_id][connected_object_id] = connected_object_id;
											}
										}						
									}
								}
								
								// Sub-objects with a date range
								for (var i = 0, len = arr_data.range.length; i < len; i++) {
									
									var object_sub_id = arr_data.range[i];
									var arr_object_sub = arr_data.object_subs[object_sub_id];
									
									if (arr_object_sub.object_sub_details_id === 'object') { // Dummy sub-object
										continue;
									}		
									
									var dateinta_start = DATEPARSER.dateInt2Absolute(arr_object_sub.date_start);
									var dateinta_end = DATEPARSER.dateInt2Absolute(arr_object_sub.date_end);
						
									var in_range = ((dateinta_start >= dateinta_range.min && dateinta_start <= dateinta_range.max) || (dateinta_end >= dateinta_range.min && dateinta_end <= dateinta_range.max) || (dateinta_start < dateinta_range.min && dateinta_end > dateinta_range.max));

									if (!in_range) {
										continue;
									}
									
									var object_id = arr_object_sub.object_id;
									var type_id = arr_data.objects[object_id].type_id;
									arr_type_object_ids[type_id][object_id] = object_id;
									
									// Full objects and possible partake in scopes
									for (var i_connected = 0, len_i_connected = arr_object_sub.connected_object_ids.length; i_connected < len_i_connected; i_connected++) {
										
										var connected_object_id = arr_object_sub.connected_object_ids[i_connected];	
										var in_scope = (connected_object_id != object_id);
								
										if (in_scope) {		
											var type_id = arr_data.objects[connected_object_id].type_id;
											arr_type_object_ids[type_id][connected_object_id] = connected_object_id;
										}
									}		
								}	
								
								var elm_object = elm_ui.find('[id=y\\\:ui_view_object\\\:show_project_type_object-0]');
										
								elm_object.one('ajaxloaded', function(e) {
								
									delete arr_value.type_object_ids;
						
									var elm_tabs = e.detail.elm.children('.tabs');
									elm_tabs.find('table[id^=d\\\:ui_view_objects\\\:data-]').each(function() {
										
										var elm_table = $(this);

										var type_id = elm_table.attr('id').split('-')[1].split('_')[0];
										
										delete arr_value.object_ids;
										arr_value.object_ids = arr_type_object_ids[type_id];
									
										COMMANDS.setData(elm_table[0], arr_value);
									});
								});
				
								COMMANDS.setData(elm_object[0], {arr_type_object_ids: arr_type_object_ids}, true);
								elm_object.quickCommand(elm_object, {'html': 'append'});

							}).on('click touch review', '.paint', function(e) {
							
								var cur_elm = $(this);
								var arr_link = cur_elm[0].arr_link;
					
								if (!arr_link) {
									return;
								}
								
								var elm_object_thumbnail = elm_ui.find('[id=y\\\:ui_view_object\\\:show_project_type_object_thumbnail-0]');
								var elm_overlay_grid = elm_ui.find('.objects > .overlay-grid');
								var elm_overlay_grid_toggle = elm_ui.find('.objects > #overlay-grid-toggle');
								
								if (POSITION.hasTouch() && !elm_overlay_grid) {
								
									elm_object_thumbnail.html('<div class=object-thumbnail><div><div class=image></div><div class=name><span>...</span></div></div></div><button></button>');
								}
								
								var obj_labmap = cur_elm.closest('.labmap')[0].labmap;
								var arr_data = obj_labmap.getData();

								var arr_type_object_ids = {};
								
								if (arr_link.object_id && arr_link.type_id) {
								
									arr_type_object_ids[arr_link.type_id] = {};
									arr_type_object_ids[arr_link.type_id][arr_link.object_id] = arr_link.object_id;
									
								} else {
								
									for (const type_id in arr_data.info.types) { // Prepare and order the Types list
										arr_type_object_ids[type_id] = {};
									}
									
									if (arr_link.is_line) {
										arr_link.object_sub_ids = arrUnique(arr_link.object_sub_ids.concat(arr_link.connect_object_sub_ids));
									}
									if (!arr_link.object_ids) {
										arr_link.object_ids = [];
									}
								
									if (arr_link.object_sub_ids) { // Sub-objects
									
										for (let i = 0; i < arr_link.object_sub_ids.length; i++) {
										
											const object_sub_id = arr_link.object_sub_ids[i];
											const arr_object_sub = arr_data.object_subs[object_sub_id];
											
											let object_id = arr_object_sub.object_id;
											let type_id = arr_data.objects[object_id].type_id;

											arr_type_object_ids[type_id][object_id] = object_id;
											
											if (arr_object_sub.original_object_id) {
												
												object_id = arr_object_sub.original_object_id;
												type_id = arr_data.objects[object_id].type_id;

												arr_type_object_ids[type_id][object_id] = object_id;
											}
										}
									}
									if (arr_link.connect_object_ids) { // Object descriptions
									
										for (let i = 0; i < arr_link.connect_object_ids.length; i++) {
										
											const arr_object_link = arr_link.connect_object_ids[i];
											const object_id = arr_object_link.object_id;
											const type_id = arr_object_link.type_id;
											
											if (!arr_type_object_ids[type_id]) {
												arr_type_object_ids[type_id] = {};
											}
											
											arr_type_object_ids[type_id][object_id] = object_id;
										}
									}
									if (arr_link.object_ids) { // Objects
									
										for (let i = 0; i < arr_link.object_ids.length; i++) {
										
											const object_id = arr_link.object_ids[i];
											const arr_object = arr_data.objects[object_id];
											const type_id = arr_object.type_id;

											arr_type_object_ids[type_id][object_id] = object_id;
										}
									}
								}
								
								if (cur_elm.closest('.explore-object').length) {
									var explore = true;
								}
								
								var elm_object = elm_ui.find('[id=y\\\:ui_view_object\\\:show_project_type_object-0]');
								
								var arr_value = {use_visualise: true, type_id: arr_link.type_id, object_id: arr_link.object_id, type_object_ids: arr_type_object_ids};
								
								elm_object.one('ajaxloaded', function(e) {
								
									delete arr_value.type_object_sub_ids;
									delete arr_value.type_object_ids;
									
									var elm_tabs = e.detail.elm.children('.tabs');
									elm_tabs.find('table[id^=d\\\:ui_view_objects\\\:data-]').each(function() {
										
										var elm_table = $(this);										
										var type_id = elm_table.attr('id').split('-')[1].split('_')[0];
									
										delete arr_value.object_sub_ids;
										delete arr_value.object_ids;
										arr_value.object_ids = arr_type_object_ids[type_id];
									
										COMMANDS.setData(elm_table[0], arr_value);
									});
								});
								

								COMMANDS.setData(elm_object[0], {arr_type_object_ids: arr_type_object_ids, explore: explore}, true);
								
								if (POSITION.hasTouch()) {
								
									if (elm_overlay_grid.length) {
									
										for (var type_id in arr_type_object_ids) {
										
											for (var object_id in arr_type_object_ids[type_id]) {			
												
												elm_overlay_object_thumbnail = elm_overlay_grid.find('[id$='+type_id+'_'+object_id+']');
		
											}
										}
										
										if (elm_overlay_object_thumbnail.length) {
								
											elm_overlay_grid_toggle.prop('checked', true);
											moveScroll(elm_overlay_object_thumbnail, {elm_container: elm_overlay_grid});
										}
									
									} else {
								
										COMMANDS.setData(elm_object_thumbnail[0], arr_type_object_ids, true);
										
										elm_object_thumbnail.quickCommand(elm_object_thumbnail);
										
										elm_object_thumbnail.on('click', 'div.a', function() {
										
											elm_object.quickCommand(elm_object, {'html': 'append'});
											elm_object_thumbnail.html('');
											
										}).on('click', 'button', function() {
										
											elm_object_thumbnail.html('');
											
										});
									}
									
								} else {
								
									elm_object.quickCommand(elm_object, {'html': 'append'});
								}

							}).on('click', 'figure.types dl > div, figure.object-sub-details dl > div, figure.conditions dl > div', function() {
					
								var cur = $(this);
								var elm_source = cur.closest('figure');
								
								var str_target = (elm_source.hasClass('conditions') ? 'condition' : (elm_source.hasClass('object-sub-details') ? 'object-sub-details' : 'type'));
								var str_identifier = this.dataset.identifier;
								
								var obj_labmap = cur.closest('.labmap')[0].labmap;
								
								var state = (this.dataset.state == '1' || this.dataset.state === undefined ? false : true);
								this.dataset.state = (state ? '1' : '0');			
								
								obj_labmap.setDataState(str_target, str_identifier, state);
								obj_labmap.doDraw();
							});
						};
						
						const func_visualise = function() {
									
							if (type == 'plot') {
										
								if (is_same) {
								
									var obj_options = {};
										
									if (obj_data.data.zoom.scale) {
										obj_options.default_zoom = {scale: obj_data.data.zoom.scale};
									}
									if (obj_data.data.center) {
										obj_options.default_center = obj_data.data.center.coordinates;
										obj_options.origin = obj_data.data.center.coordinates;
									}
								} else {
								
									let arr_levels = [];
									
									for (let i = obj_data.data.zoom.geo.min; i <= obj_data.data.zoom.geo.max; i++) {
										arr_levels.push({level: i, width: 256 * Math.pow(2,i), height: 256 * Math.pow(2,i), tile_width: 256, tile_height: 256});
									}
									
									let arr_layers = [];
									
									if (obj_data.visual.settings.map_show) {
									
										for (let i = 0, len_i = obj_data.visual.settings.map_layers.length; i < len_i; i++) {
											
											const arr_map_layer = obj_data.visual.settings.map_layers[i];
											arr_layers.push({url: arr_map_layer.url, opacity: arr_map_layer.opacity, attribution: arr_map_layer.attribution_parsed});
										}
									}
																
									var obj_options = {
										call_class_paint: MapGeo,
										arr_class_paint_settings: {arr_visual: obj_data.visual},
										arr_class_data_settings: obj_data.data.settings,
										arr_levels: arr_levels,
										arr_layers: (arr_layers.length ? arr_layers : false),
										attribution: obj_data.data.attribution,
										background_color: obj_data.visual.settings.geo_background_color,
										allow_sizing: true,
										center_pointer: true,
										default_zoom: false,
										map: {}
									};
				
									if (obj_data.data.zoom.scale) {
										obj_options.default_zoom = {scale: obj_data.data.zoom.scale};
									}
									if (obj_data.data.center) {
										obj_options.default_center = obj_data.data.center.coordinates;
										obj_options.origin = obj_data.data.center.coordinates;
									}
									
									const arr_capture_settings = (obj_data.visual.capture.enable ? obj_data.visual.capture.settings : false);
									
									if (arr_capture_settings) {
										obj_options.map.svg = obj_data.visual.capture.settings.raster_include;
									}
								}
							} else if (type == 'soc') {
							
								if (is_same) {
									
									var obj_options = {};
									
									if (obj_data.data.zoom.level) {
										obj_options.default_zoom = {level: obj_data.data.zoom.level};
									}
								} else {
								
									let arr_levels = [];
								
									if (obj_data.visual.social.settings.display == 2) {
										
										for (let i = obj_data.data.zoom.social.min; i <= obj_data.data.zoom.social.max; i++) {
											arr_levels.push({level: i, auto: true});
										}
									} else {
									
										for (let i = obj_data.data.zoom.social.min; i <= obj_data.data.zoom.social.max; i++) {
											arr_levels.push({level: i, width: 100000 * Math.pow(1.5, i), height: 50000 * Math.pow(1.5, i)});
										}
									}
														
									var obj_options = {
										call_class_paint: MapSocial,
										arr_class_paint_settings: {arr_visual: obj_data.visual},
										arr_class_data_settings: obj_data.data.settings,
										arr_levels: arr_levels,
										arr_layers: false,
										attribution: obj_data.data.attribution,
										background_color: obj_data.visual.social.settings.background_color,
										allow_sizing: false,
										default_center: {x: 0.5, y: 0.5},
										center_pointer: false,
										default_zoom: false
									};
									
									if (obj_data.data.zoom.level) {
										obj_options.default_zoom = {level: obj_data.data.zoom.level};
									}
								}
							} else if (type == 'line') {
							
								if (is_same) {
									
									var obj_options = {};
								} else {
									
									const arr_levels = [{auto: true}];
																
									var obj_options = {
										call_class_paint: MapTimeline,
										arr_class_paint_settings: {arr_visual: obj_data.visual},
										arr_class_data_settings: obj_data.data.settings,
										arr_levels: arr_levels,
										arr_layers: false,
										attribution: obj_data.data.attribution,
										background_color: obj_data.visual.time.settings.background_color,
										allow_sizing: false,
										default_center: {x: 0.5, y: 0.5},
										default_zoom: 1,
										center_pointer: true
									};
								}
							}

							if (is_new) {
								obj_options.call_class_data = MapData;
							}
							if (is_new || has_new_data) {
								obj_options.arr_data = obj_data.data;
							}
							if (is_new) {
								obj_options.default_time = obj_data.data.time;
							}
							
							
							
							if (!is_same) {
								elm_overlay.children('.dialog').css('background-color', (obj_options.background_color ? obj_options.background_color : ''));
							}
							
							var obj_labmap = elm_map[0].labmap;
							
							if (!obj_labmap) {
							
								obj_labmap = new MapManager(elm_map);
								elm_map[0].labmap = obj_labmap;
							}
							
							obj_labmap.init(obj_options);
							
							
						};

						if (is_new) {
													
							if (obj_data) {
								COMMANDS.setData(elm_target[0], {identifier: obj_data.identifier});
							}
							
							COMMANDS.checkCacher(elm_target, 'quick', function(data) {
						
								if (!data) {
									return;
								}
								
								if (!obj_data || (obj_data.identifier.data != data.identifier.data || obj_data.identifier.date != data.identifier.date)) {
									
									obj_data = data;
									elm_scripter[0].obj_data = obj_data;
									has_new_data = true;
								} else {
									
									for (const key in data) { // Use (new) data when defined, otherwise keep current
							
										obj_data[key] = (obj_data[key] || {});
										Object.assign(obj_data[key], data[key]);
									}
								}
								
								if (is_new) {
								
									elm_map = $(obj_data.html).addClass(type);
									elm_overlay.html(elm_map);
									
									func_handle_click();

									var interval_close = setInterval(function() {
										
										if (onStage(elm_map[0])) {
											return;
										}
										
										clearInterval(interval_close);
										
										var obj_labmap = elm_map[0].labmap;
										obj_labmap.close();
									}, 1000);

									new ToolExtras(elm_map, {fullscreen: true, maximize: 'fixed', tools: true, hash: 'view'});
								}
								
								func_visualise();
							});
						} else {
						
							func_visualise();
						}
					}

					elm_visualisation.on('command', '[id=y\\\:ui_visualise\\\:get_visulisation_data-0]', function() {
				
						LOADER.keepAlive(this);
						
					});
					
					if (elm_visualisation.hasClass('start-visualisation')) {

						func_run_visualisation(elm_visualisation);
					}
					
					elm_visualisation.on('run', function() {
					
						func_run_visualisation(elm_visualisation);
					});					

				});
				
				SCRIPTER.dynamic('[data-method=run_project]', 'project_visualise');
				SCRIPTER.dynamic('[data-method=explore_object]', 'project_visualise');
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT 
		
		if ($method == "get_visulisation_data") {

			$this->html = self::createVisualisation($value, $id);
		}
					
		if ($method == "data") {

		}

		if ($method == "visualise_explore_object_geo" || $method == "visualise_explore_object_soc" || $method == "visualise_explore_object_time") {

			$arr_data_options = cms_nodegoat_public_interfaces::getPublicInterfaceDataOptions();
			$explore_object_id = $id;
			$data_display_mode = ($method == "visualise_explore_object_geo" ? 'geo' : ($method == "visualise_explore_object_soc" ? 'soc' : 'time'));
			
			$this->html = '<div data-method="explore_object"><div id="y:ui_visualise:get_visulisation_data-'.$explore_object_id.'_'.$data_display_mode.'" class="vis start-visualisation" data-visualisation_type="'.$arr_data_options[$data_display_mode]['visualisation_type'].'"><div class="data-container" data-host="explore"></div></div>';
		}
				
	}

}
