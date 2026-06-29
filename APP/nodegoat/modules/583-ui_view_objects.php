<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ui_view_objects extends base_module {
	
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
		'ui_data' => [],
		'ui_view_object' => [],
		'ui_visualise' => [],
		'ui_selection' => []
	];
	
	public static function handleTypeObjectIds($id = false, $arr_visualisation_data = false) {

		// id from click. Can be signgle ID or an array from a body text tag. 
		// arr_visualisation_data from visualisation. either show selected objects that can be viewed, or references per viewable object type

		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');		
		$arr_public_interface_projects_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id);
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id, $public_user_interface_active_custom_project_id);
		$arr_public_interface_project_filter_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id, $public_user_interface_active_custom_project_id, true);
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$arr_project = StoreCustomProject::getProjects($public_user_interface_active_custom_project_id);
	
		$return = '<div class="head">
					<h1>'.getLabel('lbl_references').'</h1>
					<div class="navigation-buttons">
						<button class="close" type="button">
							<span class="icon">'.getIcon('close').'</span>
						</button>
					</div>
				</div>';

		$arr_type_objects = [];
		$arr_types_objects = [];
		
		$count = 0;	
					
		if ($id) {		
			
			$arr_id = explode('|', $id);
			$arr_type_objects = [];
			
			foreach ($arr_id as $type_object_tag) {
				
				$arr_type_object_tag = explode('_', $type_object_tag);
				$type_id = (int)$arr_type_object_tag[0];
				$object_id = (int)$arr_type_object_tag[1];
				
				$arr_type_objects[$object_id]['object'] = ['type_id' => $type_id, 'object_id' => $object_id];
				$arr_types_objects[$type_id][$object_id] = $object_id;
				
				$count++;
			}			
		}
		
		if ($arr_visualisation_data) {
			
			$arr_type_object_ids = $arr_visualisation_data['arr_type_object_ids'];
			
			foreach ((array)$arr_type_object_ids as $type_id => $arr_objects) {
				
				if (!$arr_objects) {
					continue;
				}	
				
				foreach ($arr_objects as $object_id) {
			
					$arr_type_objects[$object_id]['object'] = ['type_id' => $type_id, 'object_id' => $object_id];
					$arr_types_objects[$type_id][$object_id] = $object_id;
						
					$count++;
				}
			}
		}
		
		if ($count == 1) {
		
			$object_id = key($arr_type_objects);
			$type_id = $arr_type_objects[$object_id]['object']['type_id'];
			
			if (
				($arr_public_interface_projects_types[$type_id] && !$arr_public_interface_project_filter_types[$type_id]) || 
				($arr_public_interface_settings['types'][$type_id]['view'] || $arr_public_interface_settings['types'][$type_id]['media'])
			) {
				// show a signle object that is allowed to be viewed
				return ui_view_object::createViewTypeObject($type_id, $object_id);
				
			} else {		
				// if object is not allowed to be viewed, show cross references
				$return .= self::createViewTypeObjectsList(false, ['referenced_type_id' => (int)$type_id, 'referenced_object_id' => (int)$object_id]);
			} 
			
		} else {
			
			if ($arr_public_interface_settings['show_objects_list'] && $arr_types_objects) {
				
				$arr_tag_tabs = [];
				
				$i = 2;
								
				foreach ((array)$arr_types_objects as $ref_type_id => $arr_objects) {

					// only show objects of types in any PUI project
					if (
						($arr_public_interface_projects_types[$type_id] && !$arr_public_interface_project_filter_types[$type_id]) || 
						($arr_public_interface_settings['types'][$type_id]['view'] || $arr_public_interface_settings['types'][$type_id]['media'])
					) {
					
						$arr_type_set = StoreType::getTypeSet($ref_type_id);
						
						$key = $i;
						
						if ($arr_public_interface_project_types[$ref_type_id]) {
							
							$key = 0;	
							
						} else if ($arr_public_interface_settings['types'][$ref_type_id]['primary']) {
							
							$key = 1;
							
						}
						
						$arr_tag_tabs['links'][$key.'-'.$ref_type_id] = '<li><a href="#">'.Labels::parseTextVariables($arr_type_set['type']['name']).' ('.count((array)$arr_objects).')</a></li>';
						$arr_tag_tabs['content'][$key.'-'.$ref_type_id] = '<div>'.self::createViewTypeObjectsList($ref_type_id, false, true, array_keys($arr_objects)).'</div>';
						
						$i++;
					}
				}
				
				ksort($arr_tag_tabs['links']);
				ksort($arr_tag_tabs['content']);
				
				if (count((array)$arr_tag_tabs['links'])) {
					$return .= '<div class="tabs list-view">
						<ul>
							'.implode('', $arr_tag_tabs['links']).'
						</ul>
						'.implode('', $arr_tag_tabs['content']).'
					</div>';
				}
				
			} else {
				
				$i = 0;
				foreach ($arr_type_objects as $arr_object) {

					// only show objects of types in any PUI project
					if (
						($arr_public_interface_projects_types[$type_id] && !$arr_public_interface_project_filter_types[$type_id]) || 
						($arr_public_interface_settings['types'][$type_id]['view'] || $arr_public_interface_settings['types'][$type_id]['media'])
					) {
					
						 $return .= ui_view_object::createViewTypeObjectThumbnail($arr_object); 
						 $i++;
						 
						 if ($i > 100) {
							 break;
						 }
					}
				}
			}
		}	
		
		return '<div data-method="view_object_new" class="list">'.$return.'</div>';
		
	}
		
	public static function createViewTypeObjectsList($type_id = false, $arr_options = [], $pause = false, $arr_object_ids = false) {

		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id, $public_user_interface_active_custom_project_id, false);
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);	

		$arr_selected_types = SiteStartEnvironment::getFeedback('selected_type_ids');

		if ($arr_selected_types) {
			$arr_public_interface_project_types = $arr_selected_types;
		}	
		
		$scenario_id = (int)SiteStartEnvironment::getFeedback('scenario_id');
		
		$str_settings = 'load';
		
		if ($arr_options['referenced_object_id']) {
			
			$str_settings = '';
			
			if ($arr_options['referenced_type_id']) {
				$str_settings = 'referenced0type|'.(int)$arr_options['referenced_type_id'];
			}
			if ($arr_options['referenced_object_id']) {
				$str_settings .= ($str_settings ? '|' : '').'referenced0object|'.(int)$arr_options['referenced_object_id'];
			}
			
		} else if ($scenario_id) {
				
			$arr_type_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($public_user_interface_active_custom_project_id, false, false, $scenario_id); 
			$type_id = $arr_type_scenario['type_id'];
			$arr_public_interface_project_types = [$type_id => true];
		
		} 
		
		if ($type_id) {
			
			$arr_public_interface_project_types = [$type_id => true];
		}
		
		foreach ((array)$arr_public_interface_project_types as $type_id => $value) {

			$arr_type_set = StoreType::getTypeSet($type_id);
						
			$elm_table = cms_general::createDataTableHeading('d:ui_view_objects:data-'.$type_id.'_'.$str_settings, ['filter' => false, 'pause' => ($pause ? true : false), 'search' => true, 'order' => true]).
						'<thead><tr>';
							if ($arr_type_set['type']['object_name_in_overview']) {
								$elm_table .= '<th class="max limit"><span>'.getLabel('lbl_name').'</span></th>';
							}
								
							$num_column = 0;
							
							foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
							
								if (!$arr_object_description['object_description_in_overview'] || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, $object_description_id)) {
									continue;
								}
								
								$elm_table .= '<th class="limit'.(!$arr_type_set['type']['object_name_in_overview'] && $num_column == 0 ? ' max' : '').'"><span>'.Labels::parseTextVariables($arr_object_description['object_description_name']).'</span></th>';
								
								$num_column++;

							}

						$elm_table .= '</tr></thead>
						<tbody>
							<tr>
								<td colspan="'.(count((array)$arr_type_set['object_descriptions'])).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
							</tr>
						</tbody>
					</table>';
		
			$arr_tag_tabs['links'][$type_id] = '<li><a href="#">'.Labels::parseTextVariables($arr_type_set['type']['name']).'</a></li>';				
			$arr_tag_tabs['content'][$type_id] = '<div '.($arr_object_ids ? 'data-object_ids="'.value2JSON($arr_object_ids).'"' : '').'>'.$elm_table.'</div>';
		}
		
		if (count((array)$arr_tag_tabs['links'])) {
			
			if (count((array)$arr_tag_tabs['links']) > 1) {
				
				$return = '<div class="tabs list-view">
					<ul>
						'.implode('', $arr_tag_tabs['links']).'
					</ul>
					'.implode('', $arr_tag_tabs['content']).'
				</div>';
				
			} else {
				
				$return = '<div class="tabs list-view"><ul></ul>'.implode('', $arr_tag_tabs['content']).'</div>';
			}
		}
		
		return $return;
	}
	
	public static function createViewTypeObjectsGrid($min, $max) {	

		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id, $public_user_interface_active_custom_project_id, false);
		$arr_selected_types = SiteStartEnvironment::getFeedback('selected_type_ids');
							
		if ($arr_selected_types) {
			$arr_public_interface_project_types = array_intersect($arr_public_interface_project_types, $arr_selected_types);
		}
		
		$arr_objects = [];
		
		if (SiteStartEnvironment::getFeedback('scenario_id')) {
			
			$type_id = toolbar::getFilterTypeID();
			
			$scenario_id = (int)SiteStartEnvironment::getFeedback('scenario_id');
			$arr_filter = toolbar::getScenarioFiltersData($scenario_id);
		
			$arr_objects_info = self::getPublicInterfaceObjects([$type_id => $type_id], $arr_filter, true, $max, $min, true, ['info' => true]);
			$arr_objects = $arr_objects_info['objects'];
			$arr_types_info = $arr_objects_info['info'];
			
		} else {
			
			$arr_objects_info = self::getPublicInterfaceObjects($arr_public_interface_project_types, false, true, $max, $min, true, ['info' => true]);
			$arr_objects = $arr_objects_info['objects'];
			$arr_types_info = $arr_objects_info['info'];
		}

		$elm_data = '';
		
		foreach ((array)$arr_objects as $arr_object) {
			
			$elm_data .= ui_view_object::createViewTypeObjectThumbnail($arr_object); 
		}


		return $elm_data;
	}
			
	public static function css() {
		
		$return = '						
					.ui > div.beta.projects > .project > .data > .objects > div.no-data ~ * { display: none !important; }
					.ui > div.beta.projects > .project > .data > .objects > input { display: none; }
					.ui > div.beta.projects > .project > .data > .objects > label { position: absolute; z-index: 1; cursor: pointer; }
					
					.ui > div.beta.projects > .project > .data > .objects { display: flex; flex-wrap: wrap; justify-content: center; align-content: flex-start; }
					
					.ui > div.beta.projects > .project > .data > .objects > .overlay-grid { position: absolute; z-index: 1; }
					
					.ui > div.beta.projects > .project > .data > .objects > input:not(:checked) + label { display: block; width: 160px; height: 30px; right: 20px; top: 260px; }
					.ui > div.beta.projects > .project > .data > .objects > input:not(:checked) + label > span:first-child { display: block; width: 100%; line-height: 30px; text-align: center; background-color: #0096e4; color: #fff; letter-spacing: 2px; text-transform: uppercase; }
					.ui > div.beta.projects > .project > .data > .objects > input:not(:checked) + label > span:last-child { display: none; }
					.ui > div.beta.projects > .project > .data > .objects > input:not(:checked) + label + div.overlay-grid-next-prev { position: absolute; display: block; height: 30px; right: 20px; top: 20px; z-index: 1; }
					.ui > div.beta.projects > .project > .data > .objects > input:not(:checked) + label + div.overlay-grid-next-prev > span { cursor: pointer; display: inline-block; text-align: center; height: 25px; width: 25px; color: #fff; background-color: #0096e4; }
					.ui > div.beta.projects > .project > .data > .objects > input:not(:checked) + label + div.overlay-grid-next-prev > span:first-child { margin-right: 5px; }
					.ui > div.beta.projects > .project > .data > .objects > input:not(:checked) + label + div + div.overlay-grid { width: 160px; height: 200px; top: 50px; right: 20px; }
					.ui > div.beta.projects > .project > .data > .objects > input:not(:checked) + label + div + div.overlay-grid > .object-thumbnail { position: absolute; top: 20px; left: 10px; z-index: 1;}
					.ui > div.beta.projects > .project > .data > .objects > input:not(:checked) + label + div + div.overlay-grid > .object-thumbnail:nth-of-type(2) { width: 150px; top: 10px; left: 5px; z-index: 2; }
					.ui > div.beta.projects > .project > .data > .objects > input:not(:checked) + label + div + div.overlay-grid > .object-thumbnail:nth-of-type(1) { width: 160px; top: 0px; left: 0px; z-index: 3; }
					
					.ui > div.beta.projects > .project > .data > .objects > input:checked + label { width: 60px; height: 60px; right: 20px; top: 20px; background-color: #fff; }
					.ui > div.beta.projects > .project > .data > .objects > input:checked + label > span:first-child { display: none; }
					.ui > div.beta.projects > .project > .data > .objects > input:checked + label > span:last-child {display: block; width: 100%; height: 100%; text-align: center; background-color: #0096e4; color: #fff;  }
					.ui > div.beta.projects > .project > .data > .objects > input:checked + label + div { display: none; }
					.ui > div.beta.projects > .project > .data > .objects > input:checked + label + div + div.overlay-grid { left: 20px; top: 20px; right: 60px; display: flex; flex-wrap: wrap; justify-content: center; align-content: flex-start; }

					.ui > div.beta.projects > .project > .data > .objects > div.grid { padding: 50px; }							
					.ui > div.beta.projects > .project > .data > .objects .object-thumbnail { position: relative; display: inline-block; width: 140px; height: 170px; background-color: #ededed; margin: 0px 55px 55px 0px; border: 1px solid #d0d0d0; }
					.ui > div.beta.projects > .project > .data > .objects .object-thumbnail:last-child { margin-right: 0px; }
					.ui > div.beta.projects > .project > .data > .objects .object-thumbnail .image { margin: 4px 4px 0 4px; width: calc(100% - 8px); height: 131px; background-repeat: no-repeat; background-position: center 10%; background-size: cover; }
					.ui > div.beta.projects > .project > .data > .objects .object-thumbnail .image span { height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3em; font-family: serif; }
					.ui > div.beta.projects > .project > .data > .objects .object-thumbnail .name { position: absolute; bottom: 0px; width: 100%; min-height: 35px; max-height: 100%; display: flex; overflow: hidden; justify-content: center; align-items: center; box-sizing: border-box; background-color: #ededed; padding: 5px; text-align: center; vertical-align: middle; color: #000; }
					.ui > div.beta.projects > .project > .data > .objects .object-thumbnail:hover { text-decoration: none; background-color: #0096e4; border-color: #0096e4; }
					.ui > div.beta.projects > .project > .data > .objects .object-thumbnail:hover .image span,
					.ui > div.beta.projects > .project > .data > .objects > .object-thumbnail:hover .name { color: #fff; background-color: #0096e4; }
					.ui > div.beta.projects > .project > .data > .objects > button { display: block; width: 20%; margin: 20px 40% 20px 40%; text-align: center; background-color: rgba(255,255,255, 0.3); padding: 20px; box-sizing: border-box; border: 0; font-weight: bold; color: #444; }
					.ui > div.beta.projects > .project > .data > .objects > button:hover { text-decoration: none; background-color: #0096e4; color: #fff; }

					/*.ui > div.beta.projects > .project > .data > .objects > [data-visualisation_type] { position: absolute; top: 0; right: 0; bottom: 0; left: 0; width: 100%; flex: 2; height: 100%; } */
					
					/* .ui > div.beta.projects > .project > .data > .objects > .tabs.list-view { position: relative; margin: 5px; width: 100%; max-width: 98vw; }	*/
					
					';
	
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.dynamic('project_data', function(elm_scripter) {
	
					var func_display_data = function(new_data) {
				
						display_mode = elm_scripter.find('div.visualisation-buttons > span.active').attr('data-display_mode');
						visualisation_list_view = elm_scripter.find('div.visualisation-buttons').attr('data-visualisation_list_view');

						// Update URL based on selected display mode
						let current_url = LOCATION.getURL();
						let arr_url = current_url.split('/');
						arr_url[6] = display_mode;
						LOCATION.replace(arr_url.join('/'));
						
						elm_scripter.find('.start-over').removeClass('hide');
						elm_scripter.find('.project-scenarios').addClass('hide');
						
						elm_scripter.find('.objects > div').removeClass('active').addClass('hide');
						
						if (new_data) {
							elm_scripter.find('.objects > div > .data-container').empty();
						}
						
						if (display_mode == 'grid') {
						
							const elm_target = elm_scripter.find('.objects > .grid');
							const elm_target_container = elm_target.find('.data-container');
							elm_target.addClass('active');

							if (elm_target_container.children().length == 0) {

								elm_target.attr('data-min', 50);
								elm_target.attr('data-max', 100);							
								COMMANDS.setData(elm_target[0], {min: 0, max: 50, random: elm_target.attr('data-random')});
								elm_target.quickCommand(elm_target_container, {html: 'append'});
								
								elm_target_button = elm_target.find('button');
								elm_target_button.removeClass('hide');
							}
							
							elm_target.removeClass('hide');
							
						}
						
						if (display_mode == 'list' || (visualisation_list_view && display_mode == 'geo' )|| (visualisation_list_view && display_mode == 'soc') || (visualisation_list_view && display_mode == 'time')) {
						
							const elm_target = elm_scripter.find('.objects > .list');
							const elm_target_container = elm_target.find('.data-container');
							elm_target.addClass('active');
							
							if (elm_target_container.children().length == 0) {
								elm_target.quickCommand(elm_target_container);
							}
							
							elm_target.removeClass('hide');
							
						} 
						
						if (display_mode == 'geo' || display_mode == 'soc' || display_mode == 'time') {

							const elm_target = elm_scripter.find('.objects > .vis');
							const visualisation_types = {'geo': 'plot', 'soc': 'soc', 'time': 'line'};

							elm_target.attr('data-visualisation_type', visualisation_types[display_mode]);
														
							elm_target.addClass('active');
							elm_target.removeClass('hide');	
							
							elm_target.trigger('run');									
						}
					}
					
					elm_scripter.on('filter-update', function() {
			
						func_display_data(true);						
					});
					
					elm_scripter.on('click', 'div.visualisation-buttons > span.a', function() {
						
						const elm_button = $(this);
						
						if (elm_button.parent().children().length) {
							elm_button.siblings().removeClass('active');
						}
						
						elm_button.addClass('active');
						
						func_display_data(false);
						
					}).on('click', '[id^=y\\\:ui_view_objects\\\:run_scenario-]', function() {

						const scenario_id = 'scenario_'+$(this).attr('data-scenario_id');
						const elm_scenario_container = elm_scripter.find('div.result-info');
						elm_scenario_container.removeClass('hide');
						const elm_scenario = elm_scripter.find('div.result-info > span.a');
						const elm_scenario_name = elm_scenario.find('span.name');
						const options = JSON.parse($(this).attr('data-options'));
						
						elm_scenario.attr('id', 'y\\:ui\\:view_text-'+scenario_id);
						elm_scenario_name.html($(this).text());
						
						let active_elm = false;
						
						elm_scripter.find('div.visualisation-buttons > span.a').removeClass('active');
						elm_scripter.find('div.visualisation-buttons > span.a').addClass('hide');
						
						elm_scripter.find('div.visualisation-buttons > span.a').each(function() {
							
							const display_mode = $(this).attr('data-display_mode');
							
							if (options[display_mode]) {

								$(this).removeClass('hide');
								
								if (!active_elm) {
									$(this).addClass('active');
									active_elm = true;
								}
							}
						});
						
						$(this).quickCommand(function() {

							func_display_data(true);
						});
						
					}).on('click', '.objects > .grid > button', function() {

						const elm_target = elm_scripter.find('.objects > .grid');
						const elm_target_container = elm_target.find('.data-container');

						let cur_min = elm_target.attr('data-min');
						let cur_max = elm_target.attr('data-max');
						elm_target.attr('data-min', Number(cur_min)+50);
						elm_target.attr('data-max', Number(cur_max)+50);
						
						COMMANDS.setData(elm_target[0], {min: cur_min, max: cur_max, random: elm_target.attr('data-random')});
						elm_target.quickCommand(elm_target_container, {html: 'append'});
												
					}).on('click', '[id^=y\\\:ui_view_object\\\:show_project_type_object-].a', function() {
		
						var elm_object = elm_scripter.find('.data > .object');	
						$(this).quickCommand(elm_object, {'html': 'append'});
						
						elm_object[0].elm_prevnext = false;
	
					}).on('command', '.datatable [id^=x\\\:ui_view_object\\\:show_project_type_object-]', function() {
					
						var elm_object = elm_scripter.find('.data > .object');		
						$(this).data({target: elm_object, options: {'html': 'append'}});
						
						elm_object[0].elm_prevnext = $(this);
						
					});
					
				});

				SCRIPTER.dynamic('[data-method=run_project]', 'project_data');
							
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT 
		
		if ($method == "get_list_data") {
			
			// clean up previously set filters from (Cross Referenced) Object Views
			toolbar::setFilter([]);
			
			$this->html = self::createViewTypeObjectsList();
		}
		
		if ($method == "get_grid_data") {
			
			$this->html = self::createViewTypeObjectsGrid($value['min'], $value['max']);
		}

		if ($method == "run_scenario") {
				
			if ((int)$id) {
					
				ui::setPublicUserInterfaceModuleVars(['set' => 'scenario', 'id' => $id, 'display_mode' => false]);
			} 
		}
		
		if ($method == "list_cross_referenced_objects") {

			$arr_id = explode('_', $id);
			$type_id = $arr_id[0];
			$ref_type_id = $arr_id[1];
			$ref_object_id = $arr_id[2];
			
			$this->html = '<div data-method="view_object_new" class="list">'.self::createViewTypeObjectsList((int)$type_id, ['referenced_type_id' => (int)$ref_type_id, 'referenced_object_id' => (int)$ref_object_id]).'</div>';
		}
		
		if ($method == "export") {
			
			$arr_id = explode('_', $id);
			$type_id = $arr_id[0];
			$export_settings_id = $arr_id[1];
			
			$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		
			$arr_export_settings = cms_nodegoat_custom_projects::getProjectTypeExportSettings($public_user_interface_active_custom_project_id, false, $type_id, $export_settings_id);
			$arr_export_settings = toolbar::parseTypeExportSettings($type_id, $arr_export_settings);
				
			if ($this->is_download) {
				
				$arr_filters = current(toolbar::getFilter());
				$arr_ordering = current(toolbar::getOrder());
				$arr_conditions = toolbar::getTypeConditions($type_id);

				$str_format_type = $arr_export_settings['format']['type'];
				$str_class = 'ExportTypesObjectsNetwork'.strtoupper($str_format_type);
				
				$export = new $str_class($type_id, $arr_export_settings['scope']['types'], $arr_export_settings['format']);
				
				$collect = toolbar::getExportCollector($type_id, $arr_filters, $arr_export_settings['scope'], $arr_conditions, $arr_ordering, $str_class::getCollectorSettings());
				
				$arr_nodegoat_details = cms_nodegoat_details::getDetails();
				if ($arr_nodegoat_details['processing_time']) {
					timeLimit($arr_nodegoat_details['processing_time']);
				}
				if ($arr_nodegoat_details['processing_memory']) {
					memoryBoost($arr_nodegoat_details['processing_memory']);
				}
			
				$export->init($collect);
								
				$has_package = $export->createPackage($arr_export_settings['format']['settings'][$str_format_type]);
				
				if (!$has_package) {
										
					$this->message = getLabel('msg_export_not_available');
					return;
				}

				$export->readPackage('export');
				
				exit;
			} else {
				 
				$this->do_download = true;
			}			
		}
					
		if ($method == "data") {
		
			$arr_id = explode('_', $id);
			$type_id = (int)$arr_id[0];
			$str_settings = $arr_id[1];
				
			$arr_value = ($value ?: []);
			if ($arr_value && !is_array($arr_value)) {
				$arr_value = json_decode($arr_value, true);
			}
			
			$option_referenced_type = false;
			$option_referenced_object = false;	
				
			if (strpos($str_settings, '|') !== false) { // Advanced options
				
				$arr_settings = explode('|', $str_settings);

				$num_key_referenced_type = array_search('referenced0type', $arr_settings);
				
				if ($num_key_referenced_type !== false) {
					
					$option_referenced_type = $arr_settings[$num_key_referenced_type+1];
					
					$num_key_referenced_object = array_search('referenced0object', $arr_settings);
					
					if ($num_key_referenced_object !== false) {		
									
						$option_referenced_object = $arr_settings[$num_key_referenced_object+1];

					}
				}
			}

			if (!custom_projects::checkAccessType(StoreCustomProject::ACCESS_PURPOSE_VIEW, $type_id)) {
				return;
			}
			
			$use_custom_project_id = ui::checkPrimaryProjectProjectID($type_id);
		
			if ($use_custom_project_id) {
				
				$public_user_interface_active_custom_project_id = $use_custom_project_id;
				
			} else {
				
				$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');	
			}
			
			$arr_project = StoreCustomProject::getProjects($public_user_interface_active_custom_project_id);
			$arr_ref_type_ids = StoreCustomProject::getScopeTypes($public_user_interface_active_custom_project_id);
			$arr_type_set = StoreType::getTypeSet($type_id);
			
			$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');	
			$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);		
			$sort_description = $arr_public_interface_settings['types'][$type_id]['sort_description'];
				
			$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_OVERVIEW, true);
			$filter->setConditions(GenerateTypeObjects::CONDITIONS_MODE_STYLE, toolbar::getTypeConditions($type_id));
			$filter->setScope(['types' => $arr_ref_type_ids, 'project_id' => $public_user_interface_active_custom_project_id]);


			$arr_selection = [['object' => true, 'object_descriptions' => [], 'object_sub_details' => []]];
			
			foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				if (!$arr_object_description['object_description_in_overview'] || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, $object_description_id)) {
					continue;
				}
				
				$arr_selection['object_descriptions'][$object_description_id] = $object_description_id;
			}
			
			$filter->setSelection($arr_selection);
						
			$arr_ordering = [];
			$has_order = ($_POST['arr_order_column'] ?: false);
			
			if ($has_order) {
			
				foreach ($_POST['arr_order_column'] as $nr_order => list($num_column, $str_direction)) {
					
					if ($num_column == 0 && $arr_type_set['type']['object_name_in_overview']) { // Object name
						
						$arr_ordering['object_name'] = $str_direction;
					} else {
						
						$count_column = ($arr_type_set['type']['object_name_in_overview'] ? 1 : 0);
						
						foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
								
							if (!$arr_selection['object_descriptions'][$object_description_id]) {
								continue;
							}
							
							if ($num_column == $count_column) {
								$arr_ordering['object_description_'.$object_description_id] = $str_direction;
							}
							$count_column++;
						}
						
						if ($arr_selection['object']['object_analysis']) { 
							
							if ($num_column == $count_column) {
								$arr_ordering['object_analysis'] = $str_direction; // Object analysis
							}
							$count_column++;
						}
						
						if ($num_column == $count_column) {
							$arr_ordering['date'] = $str_direction; // Object dating
						}
					}
				}
			} else if ($sort_description) {
				
				$arr_ordering[$sort_description] = 'asc';
				
			} else {
				
				$arr_ordering['object_name'] = 'asc';
			}

			$filter->setOrder($arr_ordering);
			
			if (isset($_POST['num_records_start']) && $_POST['num_records_length'] != '-1') {
				$filter->setLimit([$_POST['num_records_start'], $_POST['num_records_length']]);
			}

			$arr_filter = [];
		
			if (($arr_value['use_visualise'] || $arr_value['use_object_ids']) && !$option_referenced_object) {
			
				$arr_filter_prepare = [];
				
				if ($arr_value['date_range']) { // Use active filters to evaluate results
					
					$arr_type_filters = toolbar::getFilter();
					$arr_filters = current($arr_type_filters);
					$source_type_id = key($arr_type_filters);
					
					$collect = data_visualise::getVisualisationCollector($source_type_id, $arr_filters, data_visualise::getTypeScope($source_type_id));
					$arr_collect_info = $collect->getResultInfo();

					foreach ($arr_collect_info['types'] as $cur_type_id => $arr_paths) {
						
						if ($cur_type_id != $type_id) {
							continue;
						}
							
						foreach ($arr_paths as $path) {
							$filter->setFilter($arr_collect_info['filters'][$path]);
						}
					}
				
					$arr_filter_prepare['date_int'] = ['start' => $arr_value['date_range']['min'], 'end' => $arr_value['date_range']['max']];
				} 

				if ($arr_value['object_ids']) {
					
					$arr_filter_prepare['objects'] = array_merge((array)$arr_filter_prepare['objects'], $arr_value['object_ids']);
				}
			
				if (!$arr_filter_prepare) {
					
					$arr_filter_set = ['objects' => -1]; // Find nothing!
				} else {
					
					if ($arr_filter_prepare['object_subs']) {
						
						$arr_filter_set = ['object_subs' => $arr_filter_prepare['object_subs']];
					} else {
						
						$arr_filter_set = ['objects' => $arr_filter_prepare['objects']];
					}
					
					if ($arr_filter_prepare['date_int']) {
						
						$arr_filter_set['date_int'] = $arr_filter_prepare['date_int'];
					}
				}
				
				if ($arr_filter_set) {
					$filter->setFilter($arr_filter_set);
				}
				
			} else if ($option_referenced_object) { // Referenced object id
				
				$arr_filter['referenced_object'] = ['object_id' => $option_referenced_object, 'type_id' => $option_referenced_type, 'options' => ['sources' => true]];
				
			} else {
			
				$arr_active_filters = toolbar::getFilter();
		
				if ($arr_active_filters[$type_id]) {
					$arr_filter = $arr_active_filters[$type_id];
				}
			}
				
			$arr_set_cache = null;
			$has_set_cache = null;
			
			$filter_set = clone $filter;
			
			if ($arr_filter) {
				$filter->setFilter($arr_filter);
			}
			
			$scenario_id = (int)SiteStartEnvironment::getFeedback('scenario_id');
			
			if ($scenario_id) {
				
				$arr_filters = $filter->getDepth();
				$arr_filters = ($arr_filters['arr_filters'] ?? []);

				$arr_scenario_filters = toolbar::getScenarioFilters($scenario_id);
				
				if ($arr_filters === $arr_scenario_filters) { // Check for valid (active) Scenario filter
				
					$scenario_hash = CacheProjectTypeScenario::generateHashFilter($_SESSION['custom_projects']['project_id'], $scenario_id, $arr_scenario_filters); // Hash only includes the filter part

					$cache_scenario = new CacheProjectTypeScenario($_SESSION['custom_projects']['project_id'], $scenario_id);						
					$has_scenario_cache = $cache_scenario->checkCacheFilter($scenario_hash);
					
					$arr_set_cache = ['result' => null];
					
					if ($has_scenario_cache) {
					
						$arr_set_cache['result'] = $cache_scenario->getCache();
					} else {
						
						status(getLabel('msg_building_cache_scenario_filter'), false, getLabel('msg_wait'), ['identifier' => SiteStartEnvironment::getSessionID(true).'cache_scenario_filter', 'duration' => 1000, 'persist' => true]);
					}
				}
			}

			$arr_filter_search = false;
			
			if ($_POST['search']) {
				
				$arr_filter_search = ['search' => $_POST['search']];
				$filter->setFilter($arr_filter_search);
			}
						
			$arr_filters = $filter->getDepth();
			$arr_filters = ($arr_filters['arr_filters'] ?? []);
			toolbar::setFilter([(int)$type_id => $arr_filters]);
			toolbar::setOrder([(int)$type_id => $arr_ordering]);
		
			if ($arr_set_cache) {
				
				$has_set_cache = ($arr_set_cache['result'] !== null);
				
				if ($has_set_cache) {
					
					$arr_filter_set = data_filter::parseUserFilterInput($arr_set_cache['result']);
					
					$filter = $filter_set;

					$filter->setDifferentiationIdentifier(SiteStartEnvironment::getSessionID()); // Keep temporary table name the same (when applicable) over multiple requests
					$table_name = $filter->storeIDsTemporarily($arr_filter_set['objects'], true);
					
					if (!$has_order) { // Set order to temporary table and internally to none when scenario is opened
						
						$filter->setFilter(['table' => [
							['table_name' => $table_name, 'table_alias' => 't_order', 'order' => 't_order.order_id']
						]]);
						$filter->setOrder([], true);
					} else {
						
						$filter->setFilter(['table' => [['table_name' => $table_name]]]);
					}

					if ($arr_filter_search) {
						$filter->setFilter($arr_filter_search);
					}
				}
			}
			
			if ($arr_project['types'][$type_id]['type_filter_id']) {

				$arr_use_project_ids = array_keys($arr_project['use_projects']);
				$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($public_user_interface_active_custom_project_id, false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
				
				$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_project_filters['object']));
			}
			
			$arr = $filter->init();

			if ($arr_set_cache) {
				
				if (!$has_set_cache && !$arr_filter_search) { // Only set cache when no user-specific filters are active
					
					$arr_set_result = [];

					$arr_info = $filter->getResultInfo(['objects' => true]);
					
					$arr_set_result['objects'] = $arr_info['objects'];
					unset($arr_info['objects']);
					
					$cache_scenario->updateCache($arr_set_result);
					
					clearStatus(SiteStartEnvironment::getSessionID(true).'cache_scenario_filter');
				}
			}
			
			$arr_info = $filter->getResultInfo();

			$arr_output = [
				'total_records' => $arr_info['total'],
				'total_records_filtered' => $arr_info['total_filtered'],
				'data' => []
			];
			
			foreach ($arr as $arr_object) {
				
				$count = 0;
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:ui_view_object:show_project_type_object-'.$type_id.'_'.$arr_object['object']['object_id'].'';
				$arr_data['class'] = 'a quick';
				$arr_data['attr']['data-method'] = 'show_project_type_object';
				
				if ($arr_type_set['type']['object_name_in_overview']) {
					
					$arr_data['cell'][$count]['attr']['style'] = $arr_object['object']['object_name_style'];
					$arr_data[] = $arr_object['object']['object_name'];
					$count++;
				}
				
				foreach ($arr_object['object_definitions'] as $object_description_id => $arr_object_definition) {
					
					$arr_object_description = $arr_type_set['object_descriptions'][$object_description_id];
						
					if ($arr_object_definition['object_definition_style']) {
					
						if ($arr_object_definition['object_definition_style'] === GenerateTypeObjects::CONDITION_ACTION_HIDE) {
							
							$arr_data[] = '';
							$count++;
							
							continue;
						}
						
						$arr_data['cell'][$count]['attr']['style'] = $arr_object_definition['object_definition_style'];
					}

					$use_value = $arr_object_definition['object_definition_value'];
					if (!$arr_object_description['object_description_ref_type_id']) {
						$use_value = arrParseRecursive($use_value, ['Labels', 'parseLanguage']);
					}
					
					$arr_extra = ['has_multi' => $arr_object_description['object_description_has_multi'], 'ref_type_id' => $arr_object_description['object_description_ref_type_id'], 'limit_text' => true];
						
					$arr_data[] = FormatTypeObjects::formatToHTMLPreviewPlainValue($arr_object_description['object_description_value_type'], $use_value, $arr_object_definition['object_definition_ref_object_id'], $arr_object_description['object_description_value_type_settings'], $arr_extra);
					
					$count++;
				}
				$arr_output['data'][] = $arr_data;
			}
			
			$this->data = $arr_output;
		}
		
	}
	
	public static function getPublicInterfaceObjects($arr_type_ids = false, $arr_filter = false, $mix_types = true, $max = false, $min = false, $sort = true, $arr_options = []) {

		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');			
		$project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);		
		$arr_project = StoreCustomProject::getProjects($project_id);
		
		if (!$arr_type_ids) {
			$arr_type_ids = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id, $project_id, false);
		}
	
		$arr_objects = [];
		$arr_type_info = [];	
		
		$arr_active_filter = toolbar::getFilter();
	
		$sort_on_analysis = false;

		foreach ((array)$arr_type_ids as $type_id) {
			
			$scope_id = $arr_public_interface_settings['projects'][$project_id]['scope']['browse'][$type_id]['grid'];
			
			if ($scope_id) {
				
				$arr_scope_selection = [];
				$arr_scope = cms_nodegoat_custom_projects::getProjectTypeScopes($project_id, false, $type_id, $scope_id);
				
				if ($arr_scope['object']['types'][0][$type_id]['selection']) {
					
					foreach ($arr_scope['object']['types'][0][$type_id]['selection'] as $arr_type_scope_selection) {
						
						if ($arr_type_scope_selection['object_description_id']) {
							
							$arr_scope_selection[$arr_type_scope_selection['object_description_id']] = $arr_type_scope_selection['object_description_id'];
						}
					}
				}
			}
			
			$arr_selection = self::getTypeSelection($type_id, ['media' => true, 'arr_scope_selection' => $arr_scope_selection]);
		
			$analysis_order_id = $arr_public_interface_settings['projects'][$project_id]['sort'][$type_id];
	
			if ($analysis_order_id) {
				
				$sort_on_analysis = true;

				$arr_analysis = cms_nodegoat_custom_projects::getProjectTypeAnalyses($project_id, false, $type_id, $analysis_order_id, false);
				$arr_selection['object']['object_analysis'][] = ['analysis_id' => $arr_analysis['id'], 'user_id' => $arr_analysis['user_id']];
			}
	
			if ($arr_selection) {

				$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ALL);
				$filter->setSelection($arr_selection);
			} else {
				
				$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_NAME);
			}
			
			$filter->setScope(['types' => StoreCustomProject::getScopeTypes($project_id), 'project_id' => $project_id]);
			$filter->setConditions(GenerateTypeObjects::CONDITIONS_MODE_FULL, toolbar::getTypeConditions($type_id));
			
			$arr_use_project_ids = array_keys($arr_project['use_projects']);			
		
			if ($arr_options['override_project_filter'] && $arr_public_interface_settings['types'][$type_id]['override_filter']) { // Check if override search filter is present and if so set it
				
				$arr_type_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($project_id, false, false, $arr_public_interface_settings['types'][$type_id]['override_filter'], true, $arr_use_project_ids);				
				$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_type_filter['object']));
		
			} else if ($arr_project['types'][$type_id]['type_filter_id']) { // Check if a project filter is present and if so set it
				
				$arr_type_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($project_id, false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
				$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_type_filter['object']));
			}
				
			//Check if a current filter is present and if so set it
			if ($arr_filter) {

				$filter->setFilter($arr_filter);

			} else if (count((array)$arr_active_filter[$type_id])) { // Check if a pui filter is present and if so set it

				$filter->setFilter($arr_active_filter[$type_id]);
			}
			
			if ($sort_on_analysis) {
				
				$filter->setOrder(['object_analysis' => 'desc']);
			} else if ($sort) {
				
				$filter->setOrder(['object_name' => 'asc']);
			}

			if ($max && !$min) {
				if ($arr_options['random']) {
					$filter->limitRandom($max);
				} else {
					$filter->setLimit($max);
				}
			} else if ($max && $min) {
				$filter->setLimit([$min, ($max-$min)]);
			} else {
				$filter->setLimit(1);
			}
			
			$arr_filtered_objects = $filter->init();
			$arr_type_info[$type_id] = $filter->getResultInfo();

			foreach ($arr_filtered_objects as $object_id => $arr_object) {

				if ($arr_object['object']['object_name']) {
										
					$arr_object['object']['type_id'] = $type_id;

					$arr_objects[$object_id] = $arr_object;	
				}
			}
		}

		if (!$arr_options['no_thumbnails']) { // get a single image for each object, either via it's own ODs, or via related media types.
			
			$arr_objects = self::getObjectsThumbnail($arr_objects);
		}
		
		if ($mix_types && $sort && count((array)$arr_objects) > 1) {
			
			if ($sort_on_analysis) {
				
				usort($arr_objects, function($a, $b) { return (float)$b['object']['object_analysis'] <=> (float)$a['object']['object_analysis']; });
			} else {

				usort($arr_objects, function($a, $b) { return strcmp(strip_tags($a['object']['object_name']), strip_tags($b['object']['object_name'])); }); 
			}
		}
	
		if (!$mix_types) {
			
			foreach ($arr_objects as $object_id => $arr_object) {
				
				$type_id = $arr_object['object']['type_id'];
				$arr_type_objects[$type_id][$object_id] = $arr_object;
			}
			
			$arr_objects = $arr_type_objects;
		}

		return ($arr_options['info'] ? array('objects' => $arr_objects, 'info' => $arr_type_info) : $arr_objects);
	}
	
	public static function getObjectsThumbnail($arr_objects) {
			
		foreach ($arr_objects as $object_id => $arr_object) {
			
			$type_id = $arr_object['object']['type_id']; 
			$arr_type_set = StoreType::getTypeSet($type_id);
			$image_filename = self::getObjectImage($arr_type_set, $arr_object);
						
			if ($image_filename) { // Object has image
				
				$arr_objects[$object_id]['object_image_filename'] = $image_filename;
				$arr_objects[$object_id]['object_thumbnail'] = SiteStartEnvironment::getCacheURL('img', [false, 200], $image_filename);
				
			} else { // Object has no image, check related objects
				
				$image_filename = self::getObjectReferencedThumbnail($arr_object);
				
				if ($image_filename) {
					
					$arr_objects[$object_id]['object_image_filename'] = $image_filename;
					$arr_objects[$object_id]['object_thumbnail'] = SiteStartEnvironment::getCacheURL('img', [false, 200], $image_filename); 
				}
			}
		}
		
		return $arr_objects;
	}

	private static function getObjectReferencedThumbnail($arr_object) {

		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');			
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);			
		
		$object_id = $arr_object['object']['object_id']; 
		$type_id = $arr_object['object']['type_id']; 
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		$arr_thumbnail_objects = [];
	
		foreach ((array)$arr_object['object_definitions'] as $object_description_id => $arr_object_definition) {
			
			foreach ((array)$arr_type_set['object_descriptions'][$object_description_id]['object_description_ref_type_id'] as $object_description_ref_type_id) {

				if ($arr_object_definition['object_definition_ref_object_id'] && $arr_public_interface_settings['types'][$object_description_ref_type_id]['media']) {
			
					foreach ((array)$arr_object_definition['object_definition_ref_object_id'] as $key => $object_definition_ref_object_id) {
						
						$ref_media_type_id = $object_description_ref_type_id;
						$ref_media_object_id = $object_definition_ref_object_id;

						// make sure no loop starts
						if ($type_id == $ref_media_type_id) {

							continue;
						}

						$arr_id = explode('_', $object_definition_ref_object_id);	
						
						if ($arr_id[1]) {
							
							$ref_media_type_id = $arr_id[0];
							$ref_media_object_id = $arr_id[1];
						}
						
						if ($ref_media_type_id != $object_description_ref_type_id) {
							continue;
						}
						
						if ($object_id != $ref_media_object_id) {
						
							$arr_thumbnail_object = self::getPublicInterfaceObjects($ref_media_type_id, ['objects' => $ref_media_object_id], true, false, false, false, ['no_thumbnails' => true]);
							$arr_media_type_set = StoreType::getTypeSet($ref_media_type_id);
							$image_filename = self::getObjectImage($arr_media_type_set, $arr_thumbnail_object[$ref_media_object_id]);
				
							if ($image_filename) {
				
								return $image_filename;
								
							} else {
								
								$arr_thumbnail_objects[$ref_media_object_id] = $arr_thumbnail_object[$ref_media_object_id];
							}
						}
					}
				}
			}
		}
	
		foreach ((array)$arr_thumbnail_objects as $thumbnail_object_id => $arr_thumbnail_object) {

			$image_filename = self::getObjectReferencedThumbnail($arr_thumbnail_object);
			
			if ($image_filename) {
				
				return $image_filename;
			}	
		}
		
		return false;
	}
	
	private static function getObjectImage($arr_type_set, $arr_object) {
	
		$image_filename = false;
	
		foreach ((array)$arr_object['object_definitions'] as $object_description_id => $arr_object_definition) {

			if ($arr_object_definition['object_definition_value'] && ($arr_type_set['object_descriptions'][$object_description_id]['object_description_value_type'] == 'media' || $arr_type_set['object_descriptions'][$object_description_id]['object_description_value_type'] == 'media_external')) {

				$value = (is_array($arr_object_definition['object_definition_value']) ? $arr_object_definition['object_definition_value'][0] : $arr_object_definition['object_definition_value']);
	
				$media = new EnucleateMedia($value, DIR_HOME_TYPE_OBJECT_MEDIA, '/'.DIR_TYPE_OBJECT_MEDIA);
		
				// check if media is image
				if ($media->enucleate(EnucleateMedia::VIEW_TYPE) == 'image') {
				
					$image_filename = $media->enucleate(EnucleateMedia::VIEW_URL);
				}
			
				if ($image_filename) {
					break;
				}
			}
		}
		
		return $image_filename;
	}
		
	public static function getObjectMedia($arr_type_set, $arr_object) {
	
		$filename = false;
		
		foreach ((array)$arr_object['object_definitions'] as $object_description_id => $arr_object_definition) {

			if ($arr_object_definition['object_definition_value'] && ($arr_type_set['object_descriptions'][$object_description_id]['object_description_value_type'] == 'media' || $arr_type_set['object_descriptions'][$object_description_id]['object_description_value_type'] == 'media_external')) {

				$value = (is_array($arr_object_definition['object_definition_value']) ? $arr_object_definition['object_definition_value'][0] : $arr_object_definition['object_definition_value']);
	
				$media = new EnucleateMedia($value, DIR_HOME_TYPE_OBJECT_MEDIA, '/'.DIR_TYPE_OBJECT_MEDIA);
				$filename = $media->enucleate(EnucleateMedia::VIEW_URL);
			
				if ($filename) {
					break;
				}
			}
		}
		
		return $value;
	}
	
	public static function getTypeSelection($type_id, $arr_options) {
	
		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
	
		$arr_type_set = StoreType::getTypeSet($type_id);
	
		$arr_selection = ['object' => ['all' => true], 'object_descriptions' => [], 'object_sub_details' => []];
		
		foreach ((array)$arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			if ($arr_options['arr_scope_selection'][$object_description_id]) {

				$arr_selection['object_descriptions'][$object_description_id] = true;
				continue;				
			}
			
			if ($arr_object_description['object_description_is_identifier']) {
				
				if ($arr_options['identifier']) {
					
					$arr_selection['object_descriptions'][$object_description_id] = true;
					continue;
				}
				
			}
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view']) {
				continue;
			}
			
			if ($arr_object_description['object_description_ref_type_id']) {
				
				foreach ((array)$arr_object_description['object_description_ref_type_id'] as $object_description_ref_type_id) {
			
					if ($arr_options['media'] && $arr_public_interface_settings['types'][$object_description_ref_type_id]['media']) {
						$arr_selection['object_descriptions'][$object_description_id] = true;
						continue;
					}
					
					if ($arr_options['referencing']) {
						$arr_selection['object_descriptions'][$object_description_id] = true;
						continue;
					}
				}
				
			} else {
				
				if ($arr_options['media'] && ($arr_object_description['object_description_value_type'] == 'media' || $arr_object_description['object_description_value_type'] == 'media_external')) {
					$arr_selection['object_descriptions'][$object_description_id] = true;
					continue;
				}
				
				if ($arr_options['referencing'] && $arr_object_description['object_description_value_type'] == 'text_tags') {
					$arr_selection['object_descriptions'][$object_description_id] = true;
					continue;
				}
			}
		}

		return $arr_selection;
	}
	
	private function getObjectCombinedReferencesFilters($arr_object, $target_type_id) {
		
		$type_id = $arr_object['object']['type_id'];
		$object_id = $arr_object['object']['object_id'];
		
		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');		
		$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		
		$arr_public_interface_project_filter_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id, $public_user_interface_active_custom_project_id, true);

		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_target_type_set = StoreType::getTypeSet($target_type_id);
		
		$arr_matched_filter_types = [];
		
		// get all the related types of start type
		foreach ((array)$arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view']) {
				continue;
			}
			
			foreach ((array)$arr_object_description['object_description_ref_type_id'] as $object_description_ref_type_id) {		
					
				if ($arr_public_interface_project_filter_types[$object_description_ref_type_id]) {
					
					$arr_matched_filter_types[$object_description_ref_type_id][$type_id][] = $object_description_id;
				}
			
			}
		}
		
		// check if related types are present in target type
		foreach ((array)$arr_target_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {

			if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view']) {
				continue;
			}
			
			foreach ((array)$arr_object_description['object_description_ref_type_id'] as $object_description_ref_type_id) {	
				
				if ($arr_matched_filter_types[$object_description_ref_type_id] && $arr_public_interface_project_filter_types[$object_description_ref_type_id]) {
			
					$arr_matched_filter_types[$object_description_ref_type_id][$target_type_id][] = $object_description_id;
				}
			}
		}
		
		// create filters based on common related types
		$arr_filter_objects = [];
		$arr_object_description_object_ids = [];
		
		foreach ((array)$arr_matched_filter_types as $filter_type_id => $arr_matched_filter_type) {
			
			$arr_type_object_descriptions = $arr_matched_filter_type[$type_id];
			$arr_target_object_descriptions = $arr_matched_filter_type[$target_type_id];
			
			if (!$arr_type_object_descriptions || !$arr_target_object_descriptions) {
				
				continue;
			}
			
			foreach ((array)$arr_type_object_descriptions as $type_object_description_key => $type_object_description_id) { 

				foreach ((array)$arr_object['object_definitions'][$type_object_description_id]['object_definition_ref_object_id'] as $key => $value) {

					foreach ((array)$arr_target_object_descriptions as $type_object_description_key => $target_type_object_description_id) { 
				
						// check if filter yields any results at all, if not it can be discarded as it will not allow for any valid single or combinational filters
						$arr_object_definitions_filter = ['object_filter' => ['object_definitions' => [$target_type_object_description_id => [$value]]]];
						
						$arr_test_result = self::getTypeObjectIDs($target_type_id, $arr_object_definitions_filter, true);
						
						if ($arr_test_result['total_filtered'] == 0) {

							continue;
						}
						
						$arr_object_description_object_ids[] = $target_type_object_description_id.'-'.$value;		
						$arr_filter_objects[$target_type_object_description_id][$value] = ['type_id' => $filter_type_id, 'object_id' => $value, 'value' => (is_array($arr_object['object_definitions'][$type_object_description_id]['object_definition_value']) ? $arr_object['object_definitions'][$type_object_description_id]['object_definition_value'][$key] : $arr_object['object_definitions'][$type_object_description_id]['object_definition_value'])];
						
					}
				}
			}
		}

		$i = 1;
		$arr_object_description_ids = array_keys($arr_filter_objects);
		$arr_object_description_ids_sets = [];
		
		// create unique sets
		while ($i <= count((array)$arr_filter_objects)) {
			
			$arr_object_description_ids_sets[] = $this->createObjectDescriptionsObjectsSets($arr_object_description_object_ids, $i);
			$i++;	
		}
		
		$arr_combined_filters = [];
		
		foreach ($arr_object_description_ids_sets as $level => $arr_object_description_ids_set) {

			foreach ($arr_object_description_ids_set as $arr_object_description_object_ids) {				
				
				$arr_filter = [];
				$arr_elements = [];
				
				foreach ($arr_object_description_object_ids as $object_description_object_id) {
					
					$arr_object_description_object_id = explode('-', $object_description_object_id);
					
					$object_description_id = $arr_object_description_object_id[0];
					$object_id = $arr_object_description_object_id[1];
					
					$arr_elements[$object_id] = $arr_filter_objects[$object_description_id][$object_id];
					$arr_filter['object_filter']['object_definitions'][$object_description_id] = $object_id;
				}
				
				$arr_test_result = self::getTypeObjectIDs($target_type_id, $arr_filter, true);
				
				if ($arr_test_result['total_filtered'] > 0) {
							
					$arr_combined_filters[$level + 1][] = ['arr_elms' => $arr_elements, 'filter' => $arr_filter, 'result' => $arr_test_result['total_filtered']];
				}
			}
		}
	
		return $arr_combined_filters;	
	}
	
	private function createObjectDescriptionsObjectsSets($arr_ids, $size) {  
		
		sort($arr_ids);
		
		$arr_object_description_object_sets = [];
		
		if ($size == 1) {
			
			return array_map(function ($v) { return [$v]; }, $arr_ids);
		}
		
		foreach ($this->createObjectDescriptionsObjectsSets($arr_ids, $size - 1) as $subset) {
			
			foreach ($arr_ids as $element) {
				
				if (!in_array($element, $subset)) {
					
					$new_arr = array_merge($subset, [$element]);
					
					$arr_object_description_ids = [];
					$valid_combination = true;
					
					foreach ($new_arr as $new_element) {
						
						$arr_object_description_object_ids = explode('-', $new_element);
						
						if (!isset($arr_object_description_ids[$arr_object_description_object_ids[0]])) {
							
							$arr_object_description_ids[$arr_object_description_object_ids[0]] = true;
							
						} else {
							
							$valid_combination = false;
							
						}
					}
					
					if ($valid_combination) {
						
						sort($new_arr);
						
						if (!in_array($new_arr, $arr_object_description_object_sets)) {
							
							$arr_object_description_object_sets[] = $new_arr;
							
						}
					}
				}
			}
		}
		
		return $arr_object_description_object_sets;
		
	}
	
	public static function getTypeObjectIDs($type_id, $arr_filter, $results_only = false) {

		$use_custom_project_id = ui::checkPrimaryProjectProjectID($type_id);
	
		if ($use_custom_project_id) {
			
			$public_user_interface_active_custom_project_id = $use_custom_project_id;
		} else {
			
			$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		}
		
		$arr_project = StoreCustomProject::getProjects($public_user_interface_active_custom_project_id);
		$arr_ref_type_ids = StoreCustomProject::getScopeTypes($public_user_interface_active_custom_project_id);
		
		$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ID);
		$filter->setVersioning(GenerateTypeObjects::VERSIONING_ADDED);
		$filter->setFilter($arr_filter);
		$filter->setScope(['types' => $arr_ref_type_ids, 'project_id' => $public_user_interface_active_custom_project_id]);	
		
		//Check if a project filter is present and if so set it
		if ($arr_project['types'][$type_id]['type_filter_id']) {

			$arr_use_project_ids = array_keys($arr_project['use_projects']);			
			$arr_type_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($public_user_interface_active_custom_project_id, false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
		
			$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_type_filter['object']));
		}
	
		$arr = [];
	
		if ($results_only) {
			
			$arr = $filter->getResultInfo();
			
		} else {
			
			$arr_objects = $filter->init();
			
			foreach ((array)$arr_objects as $object_id => $arr_object) {
				$arr[$object_id]['object'] = ['type_id' => $type_id, 'object_id' => $object_id];
			}
		}
		
		return $arr;
	}
	
}
