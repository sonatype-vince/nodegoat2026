<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ui_view_object extends base_module {
	
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
		'ui_view_objects' => [],
		'ui_visualise' => [],
		'ui_selection' => []
	];
			
	public static function createViewTypeObject($type_id = false, $object_id = false, $print = false) {
			
		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
		$project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id);
		$_SESSION['custom_projects']['project_id'] = $project_id;
		$arr_type_set = StoreType::getTypeSet($type_id);
		$exploration_types = false;
		
		foreach ((array)$arr_public_interface_settings['types'] as $exploration_type_id => $arr_type_settings) {
			
			if ($arr_type_settings['explore']) {
				
				$exploration_types = true;
			}
		}
		
		if (!$arr_public_interface_project_types[$type_id] && !$arr_public_interface_settings['types'][$type_id]['view'] && !$arr_public_interface_settings['types'][$type_id]['media']) {

			return ui_view_objects::handleTypeObjectIds($type_id.'_'.$object_id);
		}
		
		$use_primary_project_id = ui::checkPrimaryProjectProjectID($type_id);
	
		if ($use_primary_project_id) {
			
			$project_id = $use_primary_project_id;
		}
		
		$show_explore_visualisations = ($arr_public_interface_settings['projects'][$project_id]['show_explore_visualisations'] && $arr_public_interface_settings['projects'][$project_id]['show_object_fullscreen']); // show explore visalisations together with object data
		
		$arr_object = self::getPublicInterfaceObject($type_id, $object_id);		
		$location_id = $type_id.'-'.$object_id;	

	
		$arr_object['object']['object_name_stripped'] = Response::addParseDelay('', function($foo) use ($arr_object) {
			$name = $arr_object['object']['object_name'];
			$name = GenerateTypeObjects::printSharedTypeObjectNames($name);
			$name = strEscapeHTML(strip_tags($name));
			return $name;
		});	
		
		$arr_object['object']['object_name_parsed'] = Response::addParseDelay('', function($foo) use ($arr_object) {
			$name = $arr_object['object']['object_name'];
			$name = GenerateTypeObjects::printSharedTypeObjectNames($name);
			$name = FormatTags::parse($name);
			return $name;
		});	
		
		SiteEndEnvironment::addTitle($arr_object['object']['object_name']);

		$elm_object = self::createViewTypeObjectElm($arr_object, $print, $project_id);
	
		if ($print) {
			
			if ($print === 'pdf') {
		
				return $elm_object;
				
			} else {
				
				return '<div>
						<h1>'.parseBody($arr_object['object']['object_name']).'</h1>
						'.$elm_object.'
					</div>';
			}		
	
		} else {
				
			$arr_object_view_tabs = ['links' => [], 'content' => []];
			
			$arr_object_view_tabs['links'][] = '<li><a href="#"><span>'.($arr_public_interface_settings['labels']['type'][$type_id]['singular'] ? Labels::parseLanguage($arr_public_interface_settings['labels']['type'][$type_id]['singular']) : getLabel('lbl_object')).'</span></a></li>';
			$arr_object_view_tabs['content'][] = '<div>'.$elm_object.'</div>';
			
			if ($show_explore_visualisations) {

				$arr_explore_visualisations_tabs = ['links' => [], 'content' => []];				
			}
			
			if ($arr_public_interface_settings['object_geo']) {
				
				$elm_link = '<li><a href="#">'.($arr_public_interface_settings['labels']['explore_geo'] ? '<span>'.Labels::parseLanguage($arr_public_interface_settings['labels']['explore_geo']).'</span>' : '<span class="icon">'.getIcon('globe').'</span>').'</a></li>';
				$elm_content = '<div id="y:ui_visualise:visualise_explore_object_geo-'.$type_id.'_'.$object_id.'" class="explore-object"></div>';
				
				if ($show_explore_visualisations) {
					
					$arr_explore_visualisations_tabs['links'][] = $elm_link;
					$arr_explore_visualisations_tabs['content'][] = $elm_content;
					
				} else {
					
					$arr_object_view_tabs['links'][] = $elm_link;
					$arr_object_view_tabs['content'][] = $elm_content;
				}
				
			}
			
			if ($arr_public_interface_settings['object_soc']) {
				
				$elm_link = '<li><a href="#">'.($arr_public_interface_settings['labels']['explore_soc'] ? '<span>'.Labels::parseLanguage($arr_public_interface_settings['labels']['explore_soc']).'</span>' : '<span class="icon">'.getIcon('graph').'</span>').'</a></li>';
				$elm_content = '<div id="y:ui_visualise:visualise_explore_object_soc-'.$type_id.'_'.$object_id.'" class="explore-object"></div>';
				
				if ($show_explore_visualisations) {
					
					$arr_explore_visualisations_tabs['links'][] = $elm_link;
					$arr_explore_visualisations_tabs['content'][] = $elm_content;
					
				} else {
					
					$arr_object_view_tabs['links'][] = $elm_link;
					$arr_object_view_tabs['content'][] = $elm_content;
				}
			}
			
			if ($arr_public_interface_settings['object_time']) {
				
				$elm_link = '<li><a href="#">'.($arr_public_interface_settings['labels']['explore_time'] ? '<span>'.Labels::parseLanguage($arr_public_interface_settings['labels']['explore_time']).'</span>' : '<span class="icon">'.getIcon('chart-bar').'</span>').'</a></li>';
				$elm_content = '<div id="y:ui_visualise:visualise_explore_object_time-'.$type_id.'_'.$object_id.'" class="explore-object"></div>';
				
				if ($show_explore_visualisations) {
					
					$arr_explore_visualisations_tabs['links'][] = $elm_link;
					$arr_explore_visualisations_tabs['content'][] = $elm_content;
					
				} else {
					
					$arr_object_view_tabs['links'][] = $elm_link;
					$arr_object_view_tabs['content'][] = $elm_content;
				}
			}

			foreach ((array)$arr_public_interface_settings['types'] as $exploration_type_id => $arr_type_settings) {
				
				if (!$arr_type_settings['explore']) {
					
					continue;
				}
				
				$arr_type_explore_set = StoreType::getTypeSet($exploration_type_id);
				$arr_object_view_tabs['links'][] = '<li class="'.($arr_object['object_referenced'][$exploration_type_id] ? '' : 'no-data').'">
														<a href="#" class="type-'.$exploration_type_id.'">
															<span>'.($arr_public_interface_settings['labels']['type'][$exploration_type_id]['plural'] ? Labels::parseLanguage($arr_public_interface_settings['labels']['type'][$exploration_type_id]['plural']) : Labels::parseTextVariables($arr_type_explore_set['type']['name'])).'</span>
															'.($arr_object['object_referenced'][$exploration_type_id] ? '<span class="amount">'.count((array)$arr_object['object_referenced'][$exploration_type_id]).'</span>' : '').'
														</a>
													</li>';
				$arr_object_view_tabs['content'][] = '<div id="y:ui_view_objects:list_cross_referenced_objects-'.$exploration_type_id.'_'.$type_id.'_'.$object_id.'"></div>';
			}
				
			if (count($arr_object_view_tabs['links']) > 1) {
							
				$elm_object = '<div class="tabs object-view">
					<ul>'.implode('', $arr_object_view_tabs['links']).'</ul>'.
					implode('', $arr_object_view_tabs['content']).'</div>';
			}
			
			if ($arr_explore_visualisations_tabs) {
							
				$elm_explore_visualisations_tabs = '<div class="tabs object-view">
					<ul>'.implode('', $arr_explore_visualisations_tabs['links']).'</ul>'.
					implode('', $arr_explore_visualisations_tabs['content']).'</div>';
			}
			
			if ($arr_object['object']['object_style']['color']) {
				
				$str_color = $arr_object['object']['object_style']['color'];
				$str_color = (is_array($str_color) ? end($str_color) : $str_color);
				
				$elm_color = '<span style="background-color: '.$str_color.'"></span>';
			}
			
			foreach ((array)$arr_object['object']['object_style']['conditions'] as $str_identifier => $num_condition) {
				$classes .= $str_identifier.' ';
			}

			$return = '<div 
							class="'.$classes.' '.($show_explore_visualisations ? 'has-explore-visualisations' : '').'"
							data-method="view_object_new" 
							data-location="'.Response::addParseDelay(SiteEndEnvironment::getModuleLocation(0, [$public_user_interface_id, $project_id, 'object', $location_id], true, true, true), 'strEscapeHTML').'" 
							data-type_id="'.$type_id.'" 
							data-object_id="'.$object_id.'" 
							data-nodegoat_id="'.GenerateTypeObjects::encodeTypeObjectID($type_id, $object_id).'"
						>'.
				$elm_explore_visualisations_tabs.
				'<div>
					<div class="head">
						'.($arr_object['object_thumbnail'] ? '<div class="object-thumbnail-image" style="background-image: url('.$arr_object['object_thumbnail'].');"></div>' : '').'
						<h1>'.$arr_object['object']['object_name_parsed'].'</h1>
						<div class="navigation-buttons">
							<button class="prev" type="button">
								<span class="icon">'.getIcon('prev').'</span>
							</button><button class="next" type="button">
								<span class="icon">'.getIcon('next').'</span>
							</button><button class="close" type="button">
								<span class="icon">'.getIcon('close').'</span>
							</button>
						</div>
					</div>
					'.$elm_color.'
					'.$elm_object.'
				</div>
			</div>';
			
			return $return;
		}
	}
	
	public static function createTypeObjectLink($type_id, $object_id, $value = null) {
		
		if (!$object_id) {
			return '';
		}
		
		$str_html = '<span class="a type-'.$type_id.'" id="y:ui_view_object:show_project_type_object-'.$type_id.'_'.$object_id.'" data-type_id="'.$type_id.'" data-object_id="'.$object_id.'" >';
		
		if ($value === null) { // Only open tag
			return $str_html;
		}
		
		$str_html .= $value.'</span>';
		
		return $str_html;
	}
	
	public static function createTypeObjectLinkTag($str_identifiers, $value = null) {
		
		if (!$str_identifiers) {
			return '';
		}
		
		$str_html = '<span class="a tag" id="y:ui_view_object:handle_tags-'.$str_identifiers.'" data-ids="'.$str_identifiers.'">';
		
		if ($value === null) { // Only open tag
			return $str_html;
		}
		
		$str_html .= $value.'</span>';
		
		return $str_html;
	}
	
	private static function createViewTypeObjectElmRelatedMedia($arr_object_description, $arr_object_definition, $arr_public_interface_settings) {
		
		$arr_pdf_value_images = [];
		
		// First collect all references to see if Media Objects have been linked to
		if ($arr_object_description['object_description_ref_type_id']) {
			
			$arr_ref_type_objects = [];
			
			if ($arr_object_description['object_description_value_type'] == 'reversed_collection_resource_path') {
				
				return false;
				
			} else if ($arr_object_description['object_description_is_dynamic']) {
				
				$arr_reference = (!$arr_object_description['object_description_has_multi'] ? [$arr_object_definition['object_definition_ref_object_id']] : $arr_object_definition['object_definition_ref_object_id']);
				
				foreach ($arr_reference as $key => $arr_reference_type_objects) {
				
					foreach ($arr_reference_type_objects as $ref_type_id => $arr_ref_objects) {
					
						foreach ($arr_ref_objects as $cur_object_id => $arr_reference) {
							
							$arr_ref_type_objects[] = ['type_id' => $ref_type_id, 'object_id' => $cur_object_id, 'value' => $arr_reference['object_definition_ref_object_name']];
						}
					}
				}
				
			} else {
				
				foreach ((array)$arr_object_definition['object_definition_ref_object_id'] as $key => $value) {
					
					$arr_id = explode('_', $value);	
					
					if ($arr_id[1]) {
						
						$type_id = $arr_id[0];
						$object_id = $arr_id[1];
						
					} else {
						
						$type_id = $arr_object_description['object_description_ref_type_id'];
						$object_id = $value;
					}
				
					$arr_ref_type_objects[] = ['type_id' => $type_id, 'object_id' => $object_id, 'value' => $arr_object_definition['object_definition_value'][$key]];
				}
			}
			
			$str_html_image_figures = '';
			$str_html_media_figures = '';
			
			// Then iterate over found objects to display media
			foreach ($arr_ref_type_objects as $key => $arr_ref_type_object) {
				
				if ($arr_public_interface_settings['types'][$arr_ref_type_object['type_id']]['media']) {
		
					$arr_media_object = current(ui_view_objects::getPublicInterfaceObjects($arr_ref_type_object['type_id'], ['objects' => $arr_ref_type_object['object_id']], true, 1));
					$object_image_thumbnail = $arr_media_object['object_thumbnail'];
					$object_image_filename = $arr_media_object['object_image_filename'];
		
					$arr_media_object['object']['object_name_parsed'] = Response::addParseDelay('', function($foo) use ($arr_media_object) {
						return FormatTags::parse(GenerateTypeObjects::printSharedTypeObjectNames($arr_media_object['object']['object_name']));
					});	
					
					$arr_pdf_value_images[] = ['cache_url' => $object_image_thumbnail, 'caption' => $arr_ref_type_object['value']];		
				
					if ($arr_public_interface_settings['show_media_thumbnails']) {
					
						if ($object_image_thumbnail) {
		
							$elm_related_media_object_descriptions .= '<div class="a" style="background-image: url('.$object_image_thumbnail.');" data-type_id="'.$arr_ref_type_object['type_id'].'" data-object_id="'.$arr_ref_type_object['object_id'].'" id="y:ui_view_object:show_project_type_object-'.$arr_ref_type_object['type_id'].'_'.$arr_ref_type_object['object_id'].'" title="'.strEscapeHTML(GenerateTypeObjects::printSharedTypeObjectNames($arr_ref_type_object['value'])).'"></div>';
							
						} else {
		
							$icon = ($arr_public_interface_settings['icons']['type'][$arr_ref_type_object['type_id']] ? $arr_public_interface_settings['icons']['type'][$arr_ref_type_object['type_id']] : 'image');
							$elm_related_media_object_descriptions .= '<div class="a" data-type_id="'.$arr_ref_type_object['type_id'].'" data-object_id="'.$arr_ref_type_object['object_id'].'" id="y:ui_view_object:show_project_type_object-'.$arr_ref_type_object['type_id'].'_'.$arr_ref_type_object['object_id'].'" title="'.strEscapeHTML(GenerateTypeObjects::printSharedTypeObjectNames($arr_ref_type_object['value'])).'">
								<span class="icon" data-category="full">'.getIcon($icon).'</span>
							</div>';
						}
						
					} else {
						
						if ($object_image_filename) {
							
							$str_html_image_figures .= '<figure>
								<div class="image">
									<img src="'.$object_image_filename.'"  />
								</div>
								<figurecaption>'.$arr_media_object['object']['object_name_parsed'].'</figurecaption>
							</figure>';
							
						} else {
							
							$arr_ref_type_set = StoreCustomProject::getTypeSetReferenced($arr_ref_type_object['type_id'], $arr_project['types'][$arr_ref_type_object['type_id']], StoreCustomProject::ACCESS_PURPOSE_VIEW);
							$media_value = ui_view_objects::getObjectMedia($arr_ref_type_set, $arr_media_object);
							$media = new EnucleateMedia($media_value, DIR_HOME_TYPE_OBJECT_MEDIA, '/'.DIR_TYPE_OBJECT_MEDIA);
							$str_html_media_figures .= '<figure>'.$media->enucleate().'<figurecaption>'.$arr_media_object['object']['object_name_parsed'].'</figurecaption></figure>';
						}
					}
				}
			}
		} 
		
		return ['thumb_elms' => $elm_related_media_object_descriptions, 'album_elms' => $str_html_image_figures, 'non_image_media_elms' => $str_html_media_figures, 'pdf_elms' => $arr_pdf_value_images];
	}
		
	private static function createViewTypeObjectElm($arr_object, $print, $project_id) {

		$type_id = $arr_object['object']['type_id'];
		$object_id = $arr_object['object']['object_id'];
		
		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		
		$arr_public_interface_project_filter_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id, $project_id, true);
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		
		$arr_project = StoreCustomProject::getProjects($project_id);		
		$arr_types = StoreType::getTypes(array_keys($arr_project['types']));
		
		$arr_type_set = StoreCustomProject::getTypeSetReferenced($type_id, $arr_project['types'][$type_id], StoreCustomProject::ACCESS_PURPOSE_VIEW);
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id);
		$arr_source_types = $arr_object['object']['object_sources'];
		
		$arr_html_object_descriptions = [];

		$arr_cite_as_values = [];
		$arr_pdf_values = [];
		
		$arr_pdf_values['name'] = $arr_object['object']['object_name'];
		
		$arr_media = [];
		
		FormatTypeObjects::setInteractionCreateLink('ui_view_object::createTypeObjectLink');
		FormatTypeObjects::setInteractionCreateLinkTag('ui_view_object::createTypeObjectLinkTag');
		FormatTypeObjects::setInteractionCommandHover('y:ui_view_object:hover_object-0');
		
		foreach ((array)$arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			$arr_object_definition = $arr_object['object_definitions'][$object_description_id];
			$arr_configuration = $arr_project['types'][$type_id]['configuration']['object_descriptions'][$object_description_id];
			
			if ((($arr_object_definition['object_definition_value'] === null || $arr_object_definition['object_definition_value'] === '' || $arr_object_definition['object_definition_value'] === []) && !$arr_object_definition['object_definition_ref_object_id']) || !data_model::checkClearanceTypeConfiguration(StoreType::CLEARANCE_PURPOSE_VIEW, $arr_type_set, $object_description_id) || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, $object_description_id) || $arr_object_definition['object_definition_style'] === GenerateTypeObjects::CONDITION_ACTION_HIDE) {
				continue;
			}
	
			$use_value = $arr_object_definition['object_definition_value'];
			if (!$arr_object_description['object_description_ref_type_id']) {
				$use_value = arrParseRecursive($use_value, ['Labels', 'parseLanguage']);
			}

			$arr_extra = ['has_multi' => $arr_object_description['object_description_has_multi'], 'ref_type_id' => $arr_object_description['object_description_ref_type_id'], 'style' => $arr_object_definition['object_definition_style']];
			$arr_value_type_settings = $arr_object_description['object_description_value_type_settings'];
			
			if ($arr_object_description['object_description_value_type_base'] == 'text_tags') {
					
				$arr_value_type_settings['marginalia'] = false;
				$arr_value_type_settings['list'] = false;
			}
	
			$str_html_value = FormatTypeObjects::formatToHTMLValue($arr_object_description['object_description_value_type'], $use_value, $arr_object_definition['object_definition_ref_object_id'], $arr_value_type_settings, $arr_extra);
			
			$str_name = strEscapeHTML(Labels::parseTextVariables($arr_object_description['object_description_name']));
					
			$arr_cite_as_values['object_description_'.$object_description_id][] = $str_html_value;
			$arr_pdf_values['object_descriptions'][$object_description_id][] = $str_html_value;	
	
			if ($arr_public_interface_settings['types'][$type_id]['meta_description'] == $str_id) {
					
				$meta_description = strEscapeHTML($str_html_value);
				SiteEndEnvironment::addDescription($str_html_value);
			}
			
			$arr_media = self::createViewTypeObjectElmRelatedMedia($arr_object_description, $arr_object_definition, $arr_public_interface_settings);
			
			if ($arr_media['thumb_elms']) {
				
				$elm_related_media_object_descriptions .= $arr_media['thumb_elms'];
				$arr_pdf_values['images'] = array_merge((array)$arr_pdf_values['images'], (array)$arr_media['pdf_elms']);
				continue;
			}

			if ($arr_media['album_elms']) {
							
				$str_html_value = '<div class="album">'
							.$arr_media['album_elms']
						.'</div>'
						.$arr_media['non_image_media_elms'];
				$arr_pdf_values['images'] = array_merge((array)$arr_pdf_values['images'], (array)$arr_media['pdf_elms']);

			}
			
			if ($arr_object_description['object_description_value_type_base'] == 'media' || $arr_object_description['object_description_value_type_base'] == 'media_external') {

				foreach ((array)$arr_object_definition['object_definition_value'] as $media_value) {
				
					$media = new EnucleateMedia($media_value, DIR_HOME_TYPE_OBJECT_MEDIA, '/'.DIR_TYPE_OBJECT_MEDIA);
					$url = $media->enucleate(EnucleateMedia::VIEW_URL);
					$type = $media->enucleate(EnucleateMedia::VIEW_TYPE);	
								
					if ($type == 'image') {
						
						if ($arr_public_interface_settings['types'][$type_id]['media']) {
						
							$arr_pdf_values['images_full'][] = ['url' => $url];
						
						} else {
							
							$arr_pdf_values['images'][] = ['cache_url' => $url, 'caption' => ''];
						}
					}													
				}
				
				if ($arr_public_interface_settings['types'][$type_id]['media']) {
					
					$elm_media_object_descriptions .= '<span>'.$str_html_value.'</span>';
					
					continue;
				}	
			}	
		
			if ($arr_public_interface_settings['show_keyword_buttons'] && array_intersect((array)$arr_object_description['object_description_ref_type_id'], (array)$arr_public_interface_project_filter_types)) {
						
				$elm_keyword_object_descriptions .= $str_html_value;
				
				continue;
			}
				
			$arr_html_object_descriptions[] = [
				'attributes' => 'data-object_description_id="'.$object_description_id.'" class="'
					.($arr_public_interface_settings['show_object_descriptions_in_object_view'] ? 'object-description ' : '')
					.strtolower(preg_replace('/[^A-Za-z]/', '', $str_name))
					.' '.$arr_object_description['object_description_value_type_base']
					.($arr_object_description['object_description_value_type'] != $arr_object_description['object_description_value_type_base'] ? ' '.$arr_object_description['object_description_value_type'] : '')
				.'"',
				'label' => $str_name.($arr_configuration['information'] ? '<span title="'.parseBody($arr_configuration['information'], ['function' => 'strEscapeHTML']).'" class="icon a info">'.getIcon('info-point').'</span>' : ''),
				'content' => $str_html_value
			];	

		}
			
		$str_html_object_subs = '';

		if ($arr_public_interface_settings['projects'][$project_id]['show_object_subs'][$type_id] && $arr_object['object_subs_info']) {
			
			$arr_object_sub_tabs = ['links' => [], 'content' => []];
			
			foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				if (!$arr_object['object_subs_info'][$object_sub_details_id] || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, false, $object_sub_details_id)) {
					continue;
				}
				
				$arr_object_sub_tabs['links'][] = '<li><a href="#"><span>'.($arr_object_sub_details['object_sub_details']['object_sub_details_type_id'] ?
						'<span class="icon" data-category="direction" title="'.getLabel('lbl_referenced').'">'.getIcon('leftright-right').'</span>'
						.'<span>'.Labels::parseTextVariables($arr_types[$arr_object_sub_details['object_sub_details']['object_sub_details_type_id']]['name']).'</span> '
					: '').
					'<span class="sub-name">'.strEscapeHTML(Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name'])).'</span>'
				.'</span></a></li>';	
				
				$arr_columns = [];
				$num_column = 0;
				
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_has_date']) {
					
					$arr_columns[] = '<th class="date" data-sort="asc-0"><span>'.getLabel('lbl_date_start').'</span></th><th class="date"><span>'.getLabel('lbl_date_end').'</span></th>';
					$num_column += 2;
				}
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_has_location']) {
					
					$arr_columns[] = '<th class="limit disable-sort"></th><th class="max limit disable-sort"><span>'.getLabel('lbl_location').'</span></th>';
					$num_column += 2;
				}
								

				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					if (!$arr_object_sub_description['object_sub_description_in_overview'] || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
						continue;
					}
					
					$str_name = Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name']);
							
					$arr_columns[] = '<th class="limit'.($num_column == 0 ? ' max' : '').($arr_object_sub_description['object_sub_description_value_type'] == 'date' ? ' date' : '').'">'.($arr_object_sub_description['object_sub_description_is_referenced'] ? '<span>'
						.'<span class="icon" data-category="direction" title="'.getLabel('lbl_referenced').'">'.getIcon('leftright-right').'</span>'
						.'<span>'.$str_name.'</span>
					</span>' : '<span>'.$str_name.'</span>').'</th>';
					$num_column++;
				}			
						
				$arr_pdf_values['object_subs'][] = $object_sub_details_id;
						
				$return_content = '<div>
					<table class="display" id="d:data_view:data_object_sub_details-'.$type_id.'_'.$object_id.'_'.$object_sub_details_id.'_0_0" data-pause="1" data-filter="0" data-search="0">
						<thead><tr>'
							.implode('', $arr_columns)
						.'</tr></thead>
						<tbody>
							<tr>
								<td colspan="'.($num_column).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
							</tr>
						</tbody>
					</table>
				</div>';
				
				$arr_object_sub_tabs['content'][] = $return_content;
			}
			
			if (count((array)$arr_object_sub_tabs['links']) > 1 && !$arr_public_interface_settings['hide_object_subs_overview']) { // Show combined only if there are multiple subobjects to be shown
				
				array_unshift($arr_object_sub_tabs['links'], '<li><a href="#">'.getLabel('lbl_object_subs').': '.getLabel('lbl_overview').'</a></li>');
				
				$return_content = '<div>
					<table class="display" id="d:data_view:data_object_sub_details-'.$type_id.'_'.$object_id.'_all_0_0" data-pause="0" data-filter="0" data-search="0">
						<thead><tr><th class="limit"></th><th class="date" data-sort="asc-0"><span>'.getLabel('lbl_date_start').'</span></th><th class="date"><span>'.getLabel('lbl_date_end').'</span></th><th class="limit disable-sort"></th><th class="max limit disable-sort"><span>'.getLabel('lbl_location').'</span></th></tr></thead>
						<tbody>
							<tr>
								<td colspan="5" class="empty">'.getLabel('msg_loading_server_data').'</td>
							</tr>
						</tbody>
					</table>
				</div>';

				array_unshift($arr_object_sub_tabs['content'], $return_content);
			}
			
			$str_html_object_subs .= '<div class="tabs object-subs">
				<ul>
					'.($arr_object_sub_tabs ? implode('', $arr_object_sub_tabs['links']) : '').'
				</ul>';
			
				$str_html_object_subs .= ($arr_object_sub_tabs ? implode('', $arr_object_sub_tabs['content']) : '');
			
			$str_html_object_subs .= '</div>';
		}
		
		$str_html_object_sources = '';

		if ($arr_source_types) {

			$str_html_object_sources = Response::addParseDelay('', function($foo) use ($arr_source_types) {
			
				$arr_collect_type_objects = [];
			
				foreach ((array)$arr_source_types as $ref_type_id => $arr_source_objects) {
					
					$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($ref_type_id, arrValuesRecursive('object_source_ref_object_id', $arr_source_objects), GenerateTypeObjects::CONDITIONS_MODE_STYLE_INCLUDE);
					
					foreach ($arr_source_objects as $arr_source_object) {

						$arr_collect_type_objects[] = ['name' => GenerateTypeObjects::printSharedTypeObjectNames($arr_type_object_names[$arr_source_object['object_source_ref_object_id']]), 'type_id' => $ref_type_id, 'object_id' => $arr_source_object['object_source_ref_object_id'], 'object_source_link' => $arr_source_object['object_source_link']];
					}
					
				}
		
				usort($arr_collect_type_objects, function($a, $b) { return strcmp(strip_tags($a['name']), strip_tags($b['name'])); });  

				$str_html_object_sources = '<div>'; 
				
				foreach ($arr_collect_type_objects as $arr_source_object) {
								
					$str_html_object_sources .= '<p><span class="a" data-type_id="'.$arr_source_object['type_id'].'" data-object_id="'.$arr_source_object['object_id'].'" id="y:ui_view_object:show_project_type_object-'.$arr_source_object['type_id'].'_'.$arr_source_object['object_id'].'">'.$arr_source_object['name'].($arr_source_object['object_source_link'] ? ' - '.$arr_source_object['object_source_link'] : '').'</span></p>';
				}
				
				$str_html_object_sources .= '</div>';
				
				return $str_html_object_sources;
			});
					
			$arr_pdf_values['sources'] = $arr_source_types ;
			
		}
		
		if ($arr_public_interface_settings['cite_as'][$type_id]) {
	
			$citation_elm = self::createCitationElm($arr_object, $arr_cite_as_values, $arr_public_interface_settings['cite_as'][$type_id]); 
		}
	
		if ($arr_public_interface_settings['show_references_in_object_view']) {
		
			foreach ((array)$arr_object['object_referenced'] as $referenced_type_id => $arr_referneced_objects) {

				if (!$arr_public_interface_settings['types'][$referenced_type_id]['primary']) { // only list types that are set as primary types

					continue;
				}
										
				foreach ((array)$arr_referneced_objects as $arr_referenced_object_id => $arr_referenced_object) {
			
					$arr_type_objects[$arr_referenced_object_id] = $arr_referenced_object;
				}							
			}
			
			foreach ((array)$arr_object['object_references'] as $reference_type_id => $arr_reference_objects) {
										
				if (!isset($arr_public_interface_project_types[$reference_type_id])) { // only list types that are used in the PUI
			
					continue;
				}
				
				if (!$arr_public_interface_settings['types'][$reference_type_id]['primary']) { // only list types that are set as primary types

					continue;
				}
				
				foreach ((array)$arr_reference_objects as $arr_reference_object_id => $arr_reference_object) {
			
					$arr_type_objects[$arr_reference_object_id] = $arr_reference_object;
				}							
			}
		
			$i = 0;
			foreach ((array)$arr_type_objects as $arr_object) {

				 $elm_references .= self::createViewTypeObjectThumbnail($arr_object); 
				 $i++;
				 
				 if ($i > 100) {
					 break;
				 }
			}
		}
		
		$str_html_object = '';
		
		if (!$print) {
						
			$str_html_object = '<menu class="buttons">
				<button class="selection-add-elm '.($arr_public_interface_settings['selection'] ? '' : 'hide').' " value="" type="button" data-elm_id="'.$type_id.'_'.$object_id.'" data-elm_type="object" data-elm_name="'.$arr_object['object']['object_name_stripped'].'" data-elm_thumbnail="'.$arr_object['object_thumbnail'].'">
					<span class="icon">'.getIcon('download').'</span>
				</button><button class="selection-pdf-elm '.($arr_public_interface_settings['pdf_object'] ? '' : 'hide').'" value="" title="PDF '.$arr_object['object']['object_name_stripped'].'" id="y:ui_selection:get_object_data-'.$type_id.'_'.$object_id.'" type="button" data-elm_id="'.$type_id.'_'.$object_id.'" data-elm_type="object">
					<span class="icon">'.getIcon('print').'</span>
				</button><button class="url quick '.($arr_public_interface_settings['show_object_url'] ? '' : 'hide').'" id="y:ui_view_object:object_url-get_'.$type_id.'_'.$object_id.'" value="" title="'.getLabel('lbl_show').' '.getLabel('lbl_URL').'" type="button">
					<span class="icon">'.getIcon('link').'</span>
				</button><button class="share quick '.($arr_public_interface_settings['share_object_url'] ? '' : 'hide').'" id="y:ui_view_object:object_url-share_'.$type_id.'_'.$object_id.'" value="" title="'.getLabel('lbl_share').' '.$arr_object['object']['object_name_stripped'].'" type="button">
					<span class="icon">'.getIcon('users').'</span>
				</button>
			</menu>';
		}
		
		$str_html_object .= '<ul>';
			
		if ($meta_description) {
			$str_html_object .= '<li class="meta-description hide">'.$meta_description.'</li>';
		}
		
		if ($elm_media_object_descriptions) {
			$str_html_object .= '<li class="media">'.$elm_media_object_descriptions.'</li>';
		}
		
		if ($elm_related_media_object_descriptions) {
			$str_html_object .= '<li class="related-media">'.$elm_related_media_object_descriptions.'</li>';
		}
		
		if ($elm_keyword_object_descriptions) {
			$str_html_object .= '<li class="keywords">'.$elm_keyword_object_descriptions.'</li>';
		}
		
		if ($arr_public_interface_settings['show_object_descriptions_in_object_view']) {

			$str_html_object_descriptions = '';
			
			foreach ($arr_html_object_descriptions as $arr_html_object_description) {
				
				$str_html_object_descriptions .= '<li '.$arr_html_object_description['attributes'].'>'
					.'<label>'.$arr_html_object_description['label'].'</label>'
					.'<div>'.$arr_html_object_description['content'].'</div>'
				.'</li>';
			}
			
			$str_html_object .= $str_html_object_descriptions;
		} else {
			
			$str_html_object_descriptions = '';
			
			foreach ($arr_html_object_descriptions as $arr_html_object_description) {
				
				$str_html_object_descriptions .= '<div '.$arr_html_object_description['attributes'].'>'
					.'<dt>'.$arr_html_object_description['label'].'</dt>'
					.'<dd>'.$arr_html_object_description['content'].'</dd>'
				.'</div>';
			}
			
			$str_html_object .= '<li class="object-descriptions">'
				.'<dl>'.$str_html_object_descriptions.'</dl>'
			.'</li>';
		}
			
		$str_html_object .= '<li class="object-subs">
				'.$str_html_object_subs.'
			</li>
			<li class="sources">
				'.$str_html_object_sources.'
			</li>
			<li class="cite-as">
				'.$citation_elm.'
			</li>
			<li class="references">
				'.$elm_references.'
			</li>
		</ul>';

		return ($print === 'pdf' ? $arr_pdf_values : $str_html_object);
	}
	
	private static function createCitationElm($arr_object, $arr_cite_as_values, $arr_citation_parts) {
		
		$type_id = $arr_object['object']['type_id'];
		$object_id = $arr_object['object']['object_id'];
		$date_modified = strtotime($arr_object['object']['object_dating']);
		
		foreach ($arr_citation_parts as $arr_cite_as) {
							
			switch ($arr_cite_as['citation_elm']) {
				case 'value':
				
					if (is_array($arr_cite_as_values[$arr_cite_as['value']])) {
						
						$arr_values = $arr_cite_as_values[$arr_cite_as['value']];
						$value = Response::addParseDelay('', function($foo) use ($arr_values) {
					
							foreach ($arr_values as $key => $value) {
								$arr_values[$key] = GenerateTypeObjects::printSharedTypeObjectNames($value);
							}
							
							usort($arr_values, function($a, $b) { return strcmp($a, $b); });
							
							$return = false;
							
							foreach ($arr_values as $key => $value) {
								
								$return .= ($return ? ', ' : '') . trim($value);
							}				
							
							return $return;
						});	
											
					} else {
						
						$value = $arr_cite_as_values[$arr_cite_as['value']];
					}
				
					$citation_elm .= $value;
					break;
				case 'string':
					$citation_elm .= strUnescapeHTML(Labels::parseTextVariables($arr_cite_as['string']));
					break;
				case 'object_name':
					$citation_elm .= $arr_object['object']['object_name_parsed'];
					break;
				case 'access_date':
					$citation_elm .= date('d-m-Y');
					break;
				case 'access_day':
					$citation_elm .= date('d');
					break;
				case 'access_month':
					$citation_elm .= date('m');
					break;
				case 'access_year':
					$citation_elm .= date('Y');
					break;
				case 'modify_date':
					$citation_elm .= date('d-m-Y', $date_modified);
					break;
				case 'modify_day':
					$citation_elm .= date('d', $date_modified);
					break;
				case 'modify_month':
					$citation_elm .= date('m', $date_modified);
					break;
				case 'modify_year':
					$citation_elm .= date('Y', $date_modified);
					break;
				case 'url':
					$citation_elm .= self::getObjectURL($type_id, $object_id);
					break;
				case 'object_id':
					$citation_elm .= $object_id;
					break;
				case 'type_id':
					$citation_elm .= $type_id;
					break;
				case 'nodegoat_id':
					$citation_elm .= GenerateTypeObjects::encodeTypeObjectID($type_id, $object_id);
					break;
				case 'line_break':
					$citation_elm .= '<br />';
					break;
			}
		}
		
		return $citation_elm;
	}
	
	public static function createViewKeywords($filter_type_id, $value, $filter_id = false) {

		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');		
		$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		
		$arr_public_interface_project_filter_types = ($filter_type_id ? [$filter_type_id] : cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id, $public_user_interface_active_custom_project_id, true));

		$arr_keywords = [];
		$num_max_keywords = 75;
		
		$arr_filter = [];
		$arr_filter['search'] = $value;
		
		if ($filter_id) {
			
			$num_max_keywords = 25;
			
			$arr_filter_id = explode('_', $filter_id);		
			
			$target_type_id = $arr_filter_id[0];
			$element = $arr_filter_id[1];
			
			if ($element == 'OD') {
				
				$object_description_id = $arr_filter_id[2];

				$arr_filter['object_filter'] = [['referenced_types' => [$target_type_id => ['object_definitions' => [$object_description_id => ['objects' => ['relationality' => ['equality' => '≥', 'value' => 1, 'range' => '']]]]]]]];
			
			} else if ($element == 'SOD') {
				
				$arr_type_set = StoreType::getTypeSet($target_type_id);			
				$object_description_id = $arr_filter_id[2];
				
				foreach ((array)$arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
					foreach ((array)$arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
						if ($object_sub_description_id != $object_description_id) {
							
							continue;
						}
						
						$arr_filter['object_filter'] = [['referenced_types' => [$target_type_id => ['object_subs' => [$object_sub_details_id => ['object_sub_definitions' => [$object_sub_description_id => ['objects' => ['relationality' => ['equality' => '≥', 'value' => 1, 'range' => '']]]]]]]]]];
					}
				}
			}
		}
		
		$arr_total_keywords = [];
		
		foreach ((array)$arr_public_interface_project_filter_types as $type_id) {

			$arr_objects_info = ui_view_objects::getPublicInterfaceObjects($type_id, $arr_filter, true, $num_max_keywords, false, true, ['no_thumbnails'=> true, 'info' => true]);
			$arr_objects = $arr_objects_info['objects'];
			$arr_types_info = $arr_objects_info['info'];
			
			if (!$arr_objects || !count($arr_objects)) {
				continue;
			}
			
			$arr_total_keywords[$type_id] = $arr_types_info[$type_id]['total_filtered'];
			
			foreach ($arr_objects as $arr_object) {
				$arr_keywords[$type_id][] = $arr_object;
			}
		}
		
		foreach ($arr_keywords as $type_id => $arr_type_keywords) {
			
			$result .= '<li>
				<ul>';
			
				$num_count_keywords = 0;
				
				foreach ((array)$arr_type_keywords as $arr_keyword) {
					
					$result .= '<li data-type_id="'.$arr_keyword['object']['type_id'].'" data-object_id="'.$arr_keyword['object']['object_id'].'" class="keyword type-'.$arr_keyword['object']['type_id'].'">'.$arr_keyword['object']['object_name'].'</li><li class="separator"></li>';
				
					$num_count_keywords++;
					
					if ($num_count_keywords == $num_max_keywords) {
						break;
					}
				}
				
				$num_total_keywords = $arr_total_keywords[$type_id];
								
				Labels::setVariable('keywords_amount', $num_count_keywords);
				Labels::setVariable('keywords_total', $num_total_keywords);
			
				if ($num_total_keywords > $num_max_keywords) {
					
					$result .= '<li class="info"><span>'.getLabel('lbl_public_interface_amount_keywords_refine').'</span><span class="hide-options">'.getLabel('lbl_public_interface_hide_keywords').'</span></li>';
				} else {
					
					$result .= '<li class="info"><span>'.getLabel('lbl_public_interface_amount_keywords').'</span><span class="hide-options">'.getLabel('lbl_public_interface_hide_keywords').'</span></li>';
				}
				
				$result .= '</ul>
			</li>';
				
		}

		return (count($arr_keywords) ? '<ul class="keywords">'.$result.'</ul>' : '');
	}
	
	public static function createViewTypeObjectThumbnail($arr_object, $show_ref_counts = false) {
	
		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');		
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		
		$arr_primary_types = [];
		foreach ((array)$arr_public_interface_settings['types'] as $setting_type_id => $arr_type_settings) {
			
			if ($arr_type_settings['primary']) {
				
				$arr_primary_types[$setting_type_id] = $setting_type_id;
			}
		}
		
		$type_id = $arr_object['object']['type_id'];
		$object_id = $arr_object['object']['object_id'];

		if (!$arr_object['object']['object_name']) {

			$arr_object = current(ui_view_objects::getPublicInterfaceObjects($type_id, ['objects' => $object_id], true, 1, false, false, ['override_project_filter' => true]));

			if (!$arr_object) {
				
				//$arr_object['object']['object_name'] = getLabel('msg_not_available');
				//$no_name_parse = true;
				
				return '';
			}
		}
		
		if ($show_ref_counts) {
			
			$arr_ref = self::getObjectReferences($type_id, $object_id, 'both', (count($arr_primary_types) ? $arr_primary_types : false));
			
			$elm_ref_count = '<div class="ref-count">
				<div>
					<span class="arrow">→</span>
					<span class="count">'.$arr_ref['count']['referenced'].'</span>
				</div>
				<div>
					<span class="count">'.$arr_ref['count']['references'].'</span>
					<span class="arrow">→</span>
				</div>
			</div>';
		}

		$scope_id = $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['scope']['browse'][$type_id]['grid'];
		$elm_defs = '';
		
		if ($scope_id) {
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			$arr_scope = cms_nodegoat_custom_projects::getProjectTypeScopes($public_user_interface_active_custom_project_id, false, $type_id, $scope_id);
			
			if ($arr_scope['object']['types'][0][$type_id]['selection']) {
				
				foreach ((array)$arr_scope['object']['types'][0][$type_id]['selection'] as $arr_type_scope_selection) {
							
					if ($arr_type_scope_selection['object_description_id']) {
						
						$object_description_id = $arr_type_scope_selection['object_description_id'];
						$str_id = 'object_description_'.$object_description_id;
						$str_name = strEscapeHTML(Labels::parseTextVariables($arr_type_set['object_descriptions'][$object_description_id]['object_description_name']));

						$elm_defs .= '<div class="OD'.$object_description_id.' '.strtolower(preg_replace('/[^A-Za-z]/', '', $str_name)).'">';
						
						if ($arr_type_set['object_descriptions'][$object_description_id]['object_description_ref_type_id']) {

							if ($arr_type_set['object_descriptions'][$object_description_id]['object_description_is_dynamic']) {
								
								foreach ((array)$arr_object['object_definitions'][$object_description_id]['object_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
								
									foreach ((array)$arr_ref_objects as $cur_object_id => $arr_reference) {
										
										$elm_defs .= '<span>'.$arr_reference['object_definition_ref_object_name'].'</span>';
									}
								}
							} else if ($arr_type_set['object_descriptions'][$object_description_id]['object_description_has_multi']) {

								foreach ((array)$arr_object['object_definitions'][$object_description_id]['object_definition_ref_object_id'] as $key => $value) {

									$elm_defs .= '<span>'.$arr_object['object_definitions'][$object_description_id]['object_definition_value'][$key].'</span>';
								}
							} else {
								
								$elm_defs .= '<span>'.$arr_object['object_definitions'][$object_description_id]['object_definition_value'].'</span>';
							}
						} else {
							
							$html_value = FormatTypeObjects::formatToHTMLValue($arr_type_set['object_descriptions'][$object_description_id]['object_description_value_type'], $arr_object['object_definitions'][$object_description_id]['object_definition_value'], $arr_object['object_definitions'][$object_description_id]['object_definition_ref_object_id']);
							
							$elm_defs .= '<span>'.$html_value.'</span>';
						}
						
						$elm_defs .= '</div>';
					}
				}
			}
		}


		$object_name_parsed = Response::addParseDelay('', function($foo) use ($arr_object) {
			$name = $arr_object['object']['object_name'];
			$name = GenerateTypeObjects::printSharedTypeObjectNames($name);
			$name = FormatTags::parse($name);
			return $name;
		});	
		
		$first_char = Response::addParsePost(trim(strip_tags($arr_object['object']['object_name'])), array('limit' => 1));
	
		if (!$first_char) {
			$first_char = '·';
		}
		
		if ($arr_object['object']['object_style']['color']) {
			
			$str_color = $arr_object['object']['object_style']['color'];
			$str_color = (is_array($str_color) ? end($str_color) : $str_color);
			
			$elm_color = '<span style="background-color: '.$str_color.'"></span>';
		}
		
		foreach ((array)$arr_object['object']['object_style']['conditions'] as $str_identifier => $num_condition) {
			$classes .= $str_identifier.' ';
		}		
		
		$return = '<div class="object-thumbnail a '.$classes.'" id="y:ui_view_object:show_project_type_object-'.$type_id.'_'.$object_id.'"><div>'
			.$elm_color
			.'<div class="image" '.($arr_object['object_thumbnail'] ? 'style="background-image: url('.$arr_object['object_thumbnail'].');"' : '').'>'.($arr_object['object_thumbnail'] ? '' : '<span>'.$first_char.'</span>').'</div>'
			.'<div class="name"><span>'.$object_name_parsed.'</span></div>'
			.'<div class="object-definitions">'.$elm_defs.'</div>'
			.$elm_ref_count
		.'</div></div>'; 
		
		return $return;
	}

			
	public static function css() {
		
		$return = '	
					.ui > div.beta.projects > div.project .tabs.list-view > ul > li { background-color: #f5f5f5; border: 0; border-radius: 0; padding: 5px 10px; background-image: none; clip-path: none; -webkit-clip-path: none;  }
					.ui > div.beta.projects > div.project .tabs.list-view > ul > li.selected { background-color: #ddd; }
					.ui > div.beta.projects > div.project .tabs.list-view > ul > li a { color: #444; }
					.ui > div.beta.projects > div.project .tabs.list-view  { position: relative; }	
					.ui > div.beta.projects > div.project .tabs.list-view > div { position: relative; padding: 0; border: 0; }	
					.ui > div.beta.projects > div.project .tabs.list-view > div > div { position: relative;  }	
					.ui > div.beta.projects > div.project .tabs.list-view > div > div div.options { background-color: #ddd; }	
		
					.ui > div.beta.projects > div.project > .data > .object { background-color: #eee; }
					.ui > div.beta.projects > div.project > .data > .object > div { position: relative; background-color: #eee; padding-bottom: 20px; height: auto; }
					
					.ui > div.beta.projects > div.project > .data > .object.draggable > div { max-width: 50vw; max-height: 90vh; }
					.ui > div.beta.projects > div.project > .data > .object.draggable > div .head > h1 { cursor: move; }
					.ui > div.beta.projects > div.project > .data > .object.draggable > div > div > .head > div.navigation-buttons button.prev, 
					.ui > div.beta.projects > div.project > .data > .object.draggable > div > div > .head > div.navigation-buttons button.next { display: none; }
					.ui > div.beta.projects > div.project > .data > .object.draggable > div > div > .object-view { max-height: 80vh; overflow-y: auto; margin-top: 0px; padding-top: 12px; }
					.ui > div.beta.projects > div.project > .data > .object.draggable > div { box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.1);  }
					.ui > div.beta.projects > div.project > .data > .object.draggable > div.top { z-index: 3; }
					
					.ui > div.beta.projects > div.project > .data.fullscreen-object > .object.show-explore-visualisations { padding: 0px; }
					.ui > div.beta.projects > div.project > .data > .object.show-explore-visualisations > div.has-explore-visualisations { display: flex; }
					.ui > div.beta.projects > div.project > .data > .object.show-explore-visualisations > div.has-explore-visualisations > div { position: relative; flex: 2 1 100%; box-sizing: border-box;  }
					.ui > div.beta.projects > div.project > .data > .object.show-explore-visualisations > div.has-explore-visualisations > div:first-child { margin: 0px; }
					.ui > div.beta.projects > div.project > .data > .objects:empty + .object.show-explore-visualisations { border: 0px; }
					
					.ui > div.beta.projects > div.project > .data > .object > div > .tabs { margin: 15px; }
					.ui > div.beta.projects > div.project > .data > .object > div > div:not(.tabs) > ul { padding: 12px;  }
					
					.ui .head { margin: 0px; position: relative; background-color: #777; display: flex; justify-content: space-between; width: 100%; }	
					.ui .head > .object-thumbnail-image { display: block; margin: 0; padding: 0; height: 60px; width: 60px; min-width: 60px; background-repeat: no-repeat; background-position: center 10%; background-size: cover;  }	
					.ui .head > h1 { position: relative; flex-grow: 2; color: #efefef; margin: 0; min-height: 60px; line-height: 35px; font-size: 20px; padding: 12px 20px; box-sizing: border-box; }	
					.ui .head > .navigation-buttons { position: relative; white-space: nowrap; }
					.ui .head > .navigation-buttons > button { border: 0; width: 60px; height: 60px; border-radius: 0; margin: 0; padding: 0; background-color: #555; display: inline-block;}
					.ui .head > .navigation-buttons > button > span { color: #fff; }	
	
					.ui > div.beta.projects > div.project > .data > .object > div menu.buttons { width: auto; display: block; position: absolute; right: 20px; z-index: 1; }	
					.ui > div.beta.projects > div.project > .data > .object > div > div > menu.buttons { top: 80px; }	
					
					.ui > div.beta.projects > div.project > .data > .object .combined-filters { position: relative; display: flex; flex-wrap: wrap; align-content: flex-start; }	
					.ui > div.beta.projects > div.project > .data > .object .combined-filters > div { position: relative;  display: flex; flex-wrap: nowrap; padding: 5px; margin: 0 15px 10px 0; background-color: #fff; }	
					.ui > div.beta.projects > div.project > .data > .object .combined-filters > div > input { margin-right: 5px; }	
					.ui > div.beta.projects > div.project > .data > .object .combined-filters > div > * { padding: 5px; }	

					.ui > div.beta.projects > div.project > .data > .object .tabs.object-view > ul > li { background-color: transparent; border: 0; background-image: none; border-radius: 0; padding: 0; clip-path: none; -webkit-clip-path: none; }
					.ui > div.beta.projects > div.project > .data > .object .tabs.object-view > ul > li.selected { border: 0; }
					.ui > div.beta.projects > div.project > .data > .object .tabs.object-view > ul > li.selected a { background-color: rgba(255,255,255,0.7);}
					.ui > div.beta.projects > div.project > .data > .object .tabs.object-view > ul > li.no-data a { background-color: rgba(255,255,255,0.35); pointer-events: none; }
					.ui > div.beta.projects > div.project > .data > .object .tabs.object-view > ul > li > a { position: relative; font-size: 14px; padding: 10px; background-color: #aaa; margin-right: 10px; }
					.ui > div.beta.projects > div.project > .data > .object .tabs.object-view > ul.big > li > a { margin-bottom: 8px; }
					.ui > div.beta.projects > div.project > .data > .object .tabs.object-view > ul > li > a > span { line-height: 14px; }
					.ui > div.beta.projects > div.project > .data > .object .tabs.object-view > ul > li > a > span.amount {  position: absolute; top: -10px; right: -15px; display: block; padding: 0 5px; height: 15px; min-width: 20px; border-radius: 8px; background-color: #0096e4; text-align: center; font-size: 10px; line-height: 15px; color: #fff; }
					.ui > div.beta.projects > div.project > .data > .object .tabs.object-view > div { margin-top: 1px; background-color: rgba(255,255,255,0.7); border: 0; }

					.ui > div.beta.projects > div.project > .data > .object .tabs.object-view > div > ul::after,
					.ui > div.beta.projects > div.project > .data > .object > div > div > ul::after { content: " "; display: block; height: 0; clear: both; }
					
					.ui > div.beta.projects > div.project > .data > .object ul > li.object-descriptions { position: relative; }
					
					.ui > div.beta.projects > div.project > .data > .object ul > li.media > span { margin: 0 10px 10px 0; box-sizing: border-box; display: inline-block; }
					.ui > div.beta.projects > div.project > .data > .object ul > li.media > span object,
					.ui > div.beta.projects > div.project > .data > .object ul > li.media > span iframe { width: 600px; height: 600px; }
					.ui > div.beta.projects > div.project > .data > .object ul > li.media > span img,
					.ui > div.beta.projects > div.project > .data > .object ul > li.media > span video,
					.ui > div.beta.projects > div.project > .data > .object ul > li.media > span object,
					.ui > div.beta.projects > div.project > .data > .object ul > li.media > span iframe { max-height: 40vh; max-width: 100%; }
					.ui > div.beta.projects > div.project > .data > .object ul > li.related-media { display: flex; flex-wrap: wrap; }
					.ui > div.beta.projects > div.project > .data.fullscreen-object > .object ul > li.related-media { float: right; clear: left; margin-top: 50px; width: 340px; padding: 10px 0 0 10px; justify-content: flex-end;}
					.ui > div.beta.projects > div.project > .data.fullscreen-object > .object ul > li.related-media ~ li { max-width: calc(100% - 350px); }
					.ui > div.beta.projects > div.project > .data > .object ul > li.related-media > div { width: 150px; height: 150px; display: inline-block; margin: 0 10px 10px 0; background-repeat: no-repeat; background-position: center 10%; background-size: cover; background-color: #bbb; }
					.ui > div.beta.projects > div.project > .data > .object ul > li.related-media > div > span { width: 100%; text-align: center; color: #fff; }
					.ui > div.beta.projects > div.project > .data > .object ul > li.related-media > div > span > svg { height: 25%; }
					.ui > div.beta.projects > div.project > .data > .object ul > li.keywords span { display: inline-block; padding: 10px; margin: 0 10px 10px 0;}
					.ui > div.beta.projects > div.project > .data > .object ul > li.keywords span:hover { color: #fff; background-color: #0096e4; text-decoration: none; }

					.ui > div.beta.projects > div.project > .data > .object ul li.object-descriptions dl { display: table; border-spacing: 0px 8px; }
					.ui > div.beta.projects > div.project > .data > .object ul li.object-descriptions dl > div,
					.ui > div.beta.projects > div.project > .data > .object ul li.object-description { display: table-row; }
					.ui > div.beta.projects > div.project > .data > .object ul li.object-descriptions dt,
					.ui > div.beta.projects > div.project > .data > .object ul li.object-description > label { display: table-cell; padding-right: 10px; font-family: var(--font-mono); vertical-align: middle; }
					.ui > div.beta.projects > div.project > .data > .object ul li.object-description > label { padding: 4px 10px 4px 0px; }
					.ui > div.beta.projects > div.project > .data > .object ul li.object-description > div { padding: 4px 0px 4px 0px; }
					.ui > div.beta.projects > div.project > .data > .object ul li:not(.object-description) + li.object-description > label,
					.ui > div.beta.projects > div.project > .data > .object ul li:not(.object-description) + li.object-description > div { padding-top: 8px; }
					.ui > div.beta.projects > div.project > .data > .object ul li.object-description:has(+ li:not(.object-description)) > label,
					.ui > div.beta.projects > div.project > .data > .object ul li.object-description:has(+ li:not(.object-description)) > div { padding-bottom: 8px; }
					.ui > div.beta.projects > div.project > .data > .object ul li.object-descriptions dt > span,
					.ui > div.beta.projects > div.project > .data > .object ul li.object-description > label > span { margin-left: 5px; }
					.ui > div.beta.projects > div.project > .data > .object ul li.object-descriptions dd,
					.ui > div.beta.projects > div.project > .data > .object ul li.object-description > div { display: table-cell; }
					.ui > div.beta.projects > div.project > .data > .object ul li.object-descriptions dd > span.a,
					.ui > div.beta.projects > div.project > .data > .object ul li.object-description > div > span.a { display: inline-block; border-bottom: 1px #444 dashed; padding: 2px 3px; margin-bottom: 3px; } 
					.ui > div.beta.projects > div.project > .data > .object ul li.object-descriptions dd > span.a + span.a,
					.ui > div.beta.projects > div.project > .data > .object ul li.object-description > div > span.a + span.a { margin-left: 10px;} 
					.ui > div.beta.projects > div.project > .data > .object ul li.object-descriptions dd > span.a:hover,
					.ui > div.beta.projects > div.project > .data > .object ul li.object-description > div > span.a:hover { text-decoration: none; background-color: #0096e4; color: #fff; }

					.ui > div.beta.projects > div.project > .data > .object ul li.text_tags dd div + p,
					.ui > div.beta.projects > div.project > .data > .object ul li.text_tags > div div + p { display: none;}
					.ui > div.beta.projects > div.project > .data > .object ul li.text_tags dd > div,
					.ui > div.beta.projects > div.project > .data > .object ul li.text_tags > div > div { background-color: #fff; padding: 10px; }
					.ui > div.beta.projects > div.project > .data > .object ul li.text_tags dd > div > ul,
					.ui > div.beta.projects > div.project > .data > .object ul li.text_tags > div > div > ul { display: none;}
					.ui > div.beta.projects > div.project > .data > .object ul li.text_tags dd > div.tabs > div,
					.ui > div.beta.projects > div.project > .data > .object ul li.text_tags > div > div.tabs > div { padding: 0px; background-color: #fff; border: 0px;}
					.ui > div.beta.projects > div.project > .data > .object ul li.text_tags dd span.tag,
					.ui > div.beta.projects > div.project > .data > .object ul li.text_tags > div span.tag { white-space: nowrap; }
					.ui > div.beta.projects > div.project > .data > .object ul li.external dd,
					.ui > div.beta.projects > div.project > .data > .object ul li.external > div { max-width: 80%; }
					.ui > div.beta.projects > div.project > .data > .object ul li.external dd > a,
					.ui > div.beta.projects > div.project > .data > .object ul li.external > div > a { display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; padding: 4px 40px 4px 10px; margin: 0 3px 3px 0; font-size: 1.4rem; color: #444444; background: url("/CMS/css/images/icons/linked.svg") no-repeat scroll right 15px center / 10px 10px #efefef;}
					.ui > div.beta.projects > div.project > .data > .object ul li.external dd > a:hover,
					.ui > div.beta.projects > div.project > .data > .object ul li.external > div > a:hover { color: #fff; text-decoration: none; background-color: #0096e4; }
					.ui > div.beta.projects > div.project > .data > .object ul li.reversed_collection_resource_path dd > span.a,
					.ui > div.beta.projects > div.project > .data > .object ul li.reversed_collection_resource_path dd > span.a + span.a,
					.ui > div.beta.projects > div.project > .data > .object ul li.reversed_collection_resource_path > div > span.a,
					.ui > div.beta.projects > div.project > .data > .object ul li.reversed_collection_resource_path > div > span.a + span.a { margin: 0; }
					
					.ui > div.beta.projects > div.project > .data > .object ul li dd .album,
					.ui > div.beta.projects > div.project > .data > .object ul li > div .album { }
					.ui > div.beta.projects > div.project > .data > .object ul li dd .album > figure,
					.ui > div.beta.projects > div.project > .data > .object ul li > div .album > figure { display: inline-block; margin: 16px 24px 0 0; padding: 0; width: 235px; height: 147px; }
					.ui > div.beta.projects > div.project > .data > .object ul li dd .album > figure div > img,
					.ui > div.beta.projects > div.project > .data > .object ul li > div .album > figure div > img { width: 235px; height: 147px; object-fit: cover; }
					.ui > div.beta.projects > div.project > .data > .object ul li dd .album > figure > figurecaption,
					.ui > div.beta.projects > div.project > .data > .object ul li > div .album > figure > figurecaption { display: none; }
					
					.ui > div.beta.projects > div.project > .data > .object ul > li.object-subs .tabs { max-width: 35vw; }
					
					.ui > div.beta.projects > div.project > .data.fullscreen-object > .object ul > li.object-subs .tabs,
					.ui > div.beta.projects > div.project > .data > .objects:empty + .object ul > li.object-subs .tabs { max-width: 80vw; }
					
					.ui > div.beta.projects > div.project > .data > .object ul > li.object-subs > .tabs { max-width: 35vw; margin: 10px 0;}
					.ui > div.beta.projects > div.project > .data > .object ul > li.object-subs > .tabs > ul > li,
					.ui > div.beta.projects > div.project > .data > .object ul > li.object-subs > .tabs > ul > li.selected { clip-path: none; -webkit-clip-path: none; background-image: none; border: 0; }
					.ui > div.beta.projects > div.project > .data > .object ul > li.object-subs > .tabs > ul > li { background-color: #aaa; }
					.ui > div.beta.projects > div.project > .data > .object ul > li.object-subs > .tabs > ul > li.selected { background-color: #fff; }
					.ui > div.beta.projects > div.project > .data > .object ul > li.object-subs > .tabs > div { border: 0; }
					.ui > div.beta.projects > div.project > .data > .object ul > li.object-subs > .tabs table.display td { /* white-space: normal; overflow: auto; text-overflow: unset; max-width: 35vw; */ }

					.ui > div.beta.projects > div.project > .data .object .object-thumbnail { width: calc(100% - 30px); height: 50px; background-color: #fff; margin: 15px; box-sizing: border-box; }
					.ui > div.beta.projects > div.project > .data .object-thumbnail-container .object-thumbnail > div,
					.ui > div.beta.projects > div.project > .data .object .object-thumbnail > div { display: flex; height: 100%; overflow: hidden; }
					.ui > div.beta.projects > div.project > .data .object-thumbnail-container .image,
					.ui > div.beta.projects > div.project > .data .object .object-thumbnail .image { display: inline-block; width: 50px; height: 100%; background-repeat: no-repeat; background-position: center 10%; background-size: cover; background-color: #bbb; }
					.ui > div.beta.projects > div.project > .data .object-thumbnail-container .image span,
					.ui > div.beta.projects > div.project > .data .object .object-thumbnail .image  span { height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3em; font-family: serif; }
					.ui > div.beta.projects > div.project > .data .object-thumbnail-container .name,
					.ui > div.beta.projects > div.project > .data .object .object-thumbnail .name { width: calc(100% - 50px); max-width: 500px; height: 100%; color: #000; display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 16px; vertical-align: middle; margin: 0; padding: 0; padding-left: 20px; box-sizing: border-box;}
					.ui > div.beta.projects > div.project > .data .object-thumbnail-container .name span,					
					.ui > div.beta.projects > div.project > .data .object .object-thumbnail .name span { line-height: 50px; }
					.ui > div.beta.projects > div.project > .data .object-thumbnail:hover,
					.ui > div.beta.projects > div.project > .data .object .object-thumbnail:hover { text-decoration: none; color: #fff; }
					.ui > div.beta.projects > div.project > .data .object-thumbnail .object-definitions,
					.ui > div.beta.projects > div.project > .data .object .object-thumbnail .object-definitions { display: none; }
					
					.ui > div.beta.projects > div.project > .data > .object .explore-object { background-color: #efefef; padding: 20px; } 
					.ui > div.beta.projects > div.project > .data > .object .explore-object > div,
					.ui > div.beta.projects > div.project > .data > .object .explore-object > div > div,
					.ui > div.beta.projects > div.project > .data > .object .explore-object > div > div > div { height: calc(var(--view-height) - 300px);  } 
					.ui > div.beta.projects > div.project > .data > .object .explore-object > div .labmap > .controls .timeline .buttons { display: none; } 
					
					';
	
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.dynamic('[data-method=view_object_new]', function(elm_scripter) {

					var elm_ui = elm_scripter.closest('.ui');	
					
					if (!elm_scripter.is('[data-method=view_object_new]')) {
						
						var elm_scripter = elm_ui.find('[data-method=view_object_new]');
						elm_scripter.data({module: 'ui_view_object'});
					}
						
					var object_name = elm_scripter.find('.head > h1').text();
					
					if (object_name) {
					
						var str_title = document.title;
						var arr_title = str_title.split(' | ');
						
						if (arr_title.length > 2 || arr_title.length == 1) {
							str_title = object_name + ' | ' + arr_title[0];
						} else if (arr_title.length == 2) {
							str_title = object_name + ' | ' + arr_title[1];
						} 
						
						document.title = str_title;
						elm_ui.closest('html').find('meta[property=og\\\:title]').attr('content', str_title);
					}
					
					var str_meta_description = elm_scripter.find('.meta-description').text();
					elm_ui.closest('html').find('meta[name=description]').attr('content', str_meta_description);
					elm_ui.closest('html').find('meta[property=og\\\:description]').attr('content', str_meta_description);
					
					if (!elm_scripter.children().length) {
						return;
					}
										
					
					const elm_container = elm_scripter.parent();
					let draggable = false;
					
					if (elm_container.hasClass('draggable')) {
					
						draggable = true;
					} 
					
					// Only change view if Active Object is part of layout
					if (!draggable) {
					
						elm_ui.find('.project').attr('data-object_active', true);
					}
											
					if (elm_container.children().length) {
						if (draggable) {
							elm_scripter.siblings().removeClass('top');
							elm_scripter.addClass('top');
						} else {
							elm_scripter.siblings().addClass('hide');
						}
					}
					
					LOCATION.attach(elm_scripter[0], elm_scripter.attr('data-location'), true);	

					elm_scripter.on('open', '.tabs > div', function(e) {
		
						if (e.target != e.currentTarget) {
							return;
						}
												
						const elm_tab = this;
						const elm_table = $(this).find('[id^=d\\\:]').first();
					
						if (elm_table.length) {
							
							const elm_table_container = elm_table.parent();
							if (elm_table_container.attr('data-object_ids')) {
							
								var arr_value = {use_object_ids: true};
								
								arr_object_ids = JSON.parse(elm_table_container.attr('data-object_ids'));
								arr_value.object_ids = arr_object_ids;
									
								COMMANDS.setData(elm_table[0], arr_value);
								
							}
								
							COMMANDS.dataTableContinue(elm_table);
							return;
						}
						
						if (!elm_tab.getAttribute('id')) {
							return;
						}
						
						COMMANDS.quickCommand(elm_tab, elm_tab);
						elm_tab.removeAttribute('id');	
										
					}).on('click', 'button.close', function() {
						
						if (elm_container.children().length > 1) {
						
							// Show previous object view
							const elm_previous_object = elm_scripter.prev();
							const elm_next_object = elm_scripter.next();
							let elm_closest_sibling = false;
							
							if (draggable) {
								if (elm_previous_object.length) {
	
									elm_closest_sibling = elm_previous_object;
								} else {

									elm_closest_sibling = elm_next_object;
								}
							} else {
								elm_closest_sibling = elm_previous_object;
							}
							
							elm_closest_sibling.removeClass('hide');						
						
							LOCATION.attach(elm_closest_sibling[0], elm_closest_sibling.attr('data-location'), true);	
							
						} else {

							if (elm_ui.find('.data .objects div.hide').length == 3) { // after a direct link, no other data is present, so reload project to start over
	
								var elm_set_project = (elm_ui.find('[id^=y\\\:ui\\\:run_project-].active').length ? elm_ui.find('[id^=y\\\:ui\\\:run_project-].active') : elm_ui.find('[id=y\\\:ui\\\:run_project-0]'));
								elm_set_project.trigger('click');
								elm_ui.find('.project').attr('data-object_active', false);
								
							} else if (!elm_scripter.siblings().length) { // after clicks from interface
						
								elm_ui.find('.project').attr('data-object_active', false);
							}
							
							LOCATION.attach(elm_ui[0], null, true);
						}
												
						elm_scripter.remove();
						
					}).on('command', '[id^=y\\\:ui_view_object\\\:handle_tags-]', function() {
		
						COMMANDS.setTarget(this, elm_scripter.parent());
						COMMANDS.setOptions(this, {'html': 'append'});
					});
					
					elm_scripter.find('[id^=y\\\:ui_view_object\\\:object_url-]').each(function() {
						COMMANDS.setTarget($(this), elm_ui.find('div.fixed-view-container'));
					});
												
					var elm_object = elm_scripter.closest('[id=y\\\:ui_view_object\\\:show_project_type_object-0]');
					var elm_object_thumbnail = elm_ui.find('[id=y\\\:ui_view_object\\\:show_project_type_object_thumbnail-0]');
					
					var elm_prevnext = elm_object[0].elm_prevnext;
					
					if (elm_prevnext) {
			
						elm_scripter[0].cur_elm_prevnext = elm_prevnext;
						
						elm_scripter.on('click', '.navigation-buttons > button.next, .navigation-buttons > button.prev', function() {
						
							var cur_elm_prevnext = elm_scripter[0].cur_elm_prevnext;
							var elm = $(this);
							
							if (elm.hasClass('prev')) {
								var target = cur_elm_prevnext.closest('tr, li').prev();
								var next_prev = 'prev';
							} else if (elm.hasClass('next')) {
								var target = cur_elm_prevnext.closest('tr, li').next();
								var next_prev = 'next';
							} else {
								return;
							}
							
							var call_view = function() {
								target.trigger('click');
								elm_scripter.remove();
							};
							
							if (target.length) {
								call_view();
							} else {
								
								var table = cur_elm_prevnext.closest('[id^=d:], .datatable');
								
								if (table.length) {
									
									table.trigger(next_prev);
									
									if (table.is('[id^=d:]')) {
										
										table.one('commandfinished', function() {
											target = table.find('[data-method]');
											target = (next_prev == 'prev' ? target.last() : target.first());
											call_view();
										});
									} else {
										
										target = table.find('[data-method]');
										target = (next_prev == 'prev' ? target.last() : target.first());
										call_view();
									}
								}
							}							
						});
						
						elm_scripter.on('keyup', function(e) {
							
							if (e.which == 37) {
								elm_scripter.find('.navigation-buttons > button.prev').trigger('click');
							} else if (e.which == 39) {
								elm_scripter.find('.navigation-buttons > button.next').trigger('click');
							} else {
								return;
							}
						});
					} else {
						elm_scripter.find('.navigation-buttons > button.next, .navigation-buttons > button.prev').addClass('hide');
					}

					elm_scripter.find('[id^=d\\\:].display').each(function() {

						var elm_table = $(this);
						elm_table.on('commandfinished', function() {
						
							elm_table.find('.popup').each(function() {
								
								const elm_popup = $(this);
							
								if (!elm_popup.is('tr') && elm_popup.closest('.object-subs').length) {
								
									elm_popup.removeClass('popup a');
								} else if (!elm_popup.is('tr') || elm_popup.attr('data-method') == 'view_type_object') {
								
									elm_popup.removeClass('popup').addClass('a quick');
								}
							});
						});
					});
					
					runElementSelectorFunction(elm_scripter, 'div.text_tags, div.reversal', function(elm_found) {
					
						runElementSelectorFunction(elm_found, '.tag', function(elm_tag) {

							if (POSITION.hasTouch()) {
							
								var elm_tag = $(elm_tag);
								
								elm_tag.on('touchend', function() {
								
									var arr_type_object_ids = {};
									var ids = elm_tag.attr('data-ids');
									var arr_ids = ids.split('|');
									
									for (var i = 0; i < arr_ids.length; i++) {
								
										var arr_tag_type_object_ids = arr_ids[i].split('_');

										if (!arr_type_object_ids[arr_tag_type_object_ids[0]]) {
											arr_type_object_ids[arr_tag_type_object_ids[0]] = {};
										}
										
										arr_type_object_ids[arr_tag_type_object_ids[0]][arr_tag_type_object_ids[1]] = arr_tag_type_object_ids[1];
									}
				
									COMMANDS.setData(elm_object[0], {arr_type_object_ids: arr_type_object_ids}, true);								
									COMMANDS.setData(elm_object_thumbnail[0], arr_type_object_ids, true);
							
									elm_object_thumbnail.quickCommand(elm_object_thumbnail);
									
									elm_object_thumbnail.on('click', 'div.a', function() {
									
										elm_object.quickCommand(elm_object, {html: 'append'});
										elm_object_thumbnail.html('');
										
									}).on('click', 'button', function() {
									
										elm_object_thumbnail.html('');
									});
								});
							} else { // Hover

								elm_tag.classList.add('quick');
							}
						});
						
						new TextTags(elm_found, {command_hover: (elm_found.dataset.command_hover ? elm_found.dataset.command_hover : '')});
					});

					var selection_elms = elm_scripter.find('[class^=selection-]');
					
					if (selection_elms.length) {
					
						selection_elms.each(function() {
						
							UISELECTION.handleElement($(this));
						});
					}
					
					if (draggable) {
					
						const handle = elm_scripter.find('.head > h1');
						
						handle.on('mousedown', function(e) {
						
							e.preventDefault();
						
							$(this).addClass('dragging');
							LOCATION.attach(elm_scripter[0], elm_scripter.attr('data-location'), true);	
							
							elm_scripter.siblings().removeClass('top');
							elm_scripter.addClass('top');
							
							const elm = elm_scripter[0];
							const pos_offset_x = e.pageX - elm.offsetLeft;
							const pos_offset_y = e.pageY - elm.offsetTop;
							
							$(this).attr('data-drag_start_offset_x', pos_offset_x);
							$(this).attr('data-drag_start_offset_y', pos_offset_y);
							
							$(document).on('mousemove.drag', func_do_drag);
							$(document).on('mouseup.drag', func_end_drag);
						});	
						
						const func_do_drag = function(e) {
						
							const handle = elm_scripter.find('.head > h1.dragging');
							
							if (handle.length === 0) {
								return;
							}

							e.preventDefault();

							const pos_offset_x = handle.attr('data-drag_start_offset_x');
							const pos_offset_y = handle.attr('data-drag_start_offset_y');

							   
							const x = e.pageX - pos_offset_x;
							const y = e.pageY - pos_offset_y;
							
							if (x > 1 && y > 1) {

								elm_scripter[0].style.left = x +'px';
								elm_scripter[0].style.top = y +'px';
							}

						}
						
						const func_end_drag = function(e) {
						
							const handle = elm_scripter.find('.head > h1.dragging');
							handle.removeClass('dragging');
							
							$(document).off('mousemove.drag');
							$(document).off('mouseup.drag');
						}
					}
					
					
				});
				
				SCRIPTER.dynamic('[data-method=run_project]', '[data-method=view_object_new]');
				
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT 

		if ($method == "show_project_type_object") {
	
			if ($id) {
				
				$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
				$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
				
				$arr_id = explode('_', $id);
				$type_id = (int)$arr_id[0];
				$object_id = (int)$arr_id[1];
				
				self::checkObjectProject($type_id, $object_id);
			}
			
			$this->html = ui_view_objects::handleTypeObjectIds($id, $value);
		}
		
		if ($method == "handle_tags") {

			$this->html = ui_view_objects::handleTypeObjectIds($id);
		}
		
		if ($method == "hover_object") {
			
			$arr_id = explode('|', $id);
			$arr_type_objects = [];
			
			foreach ($arr_id as $type_object_tag) {
				
				$arr_type_object_tag = explode('_', $type_object_tag);
				$type_id = (int)$arr_type_object_tag[0];
				$object_id = (int)$arr_type_object_tag[1];
				
				$arr_type_objects[$object_id]['object'] = ['type_id' => $type_id, 'object_id' => $object_id];
			}

			if (count((array)$arr_type_objects) == 1) {
				
				$arr_object = current($arr_type_objects);
				
				$return = self::createViewTypeObjectThumbnail($arr_object, true); 
				
			} else {
				
				$return = '<div><div class="image"></div><div class="name">'.count((array)$arr_type_objects).' '.getLabel('lbl_objects').'</div></div>';
			}

			$this->html = $return;
			
		}
		
		if ($method == "show_project_type_object_thumbnail") {

			$arr_type_objects = [];
			$count = 0;
			$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
			$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
			$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id);

			if ($value) {

				foreach ($value as $type_id => $arr_objects) {
					
					// only show objects of types in any PUI project (perhaps offer as option?)
					if (!$arr_public_interface_project_types[$type_id]) {
						continue;
					}
						
					foreach ($arr_objects as $object_id) {
						
						$object_id = (int)$object_id;
						
						$arr_type_objects[$object_id]['object'] = ['type_id' => $type_id, 'object_id' => $object_id];
						
						$count++;
					}
					
				}
				
			} else {

				$arr_id = explode('_', $id);
				$type_id = (int)$arr_id[0];
				$object_id = (int)$arr_id[1];
				
				$arr_type_objects[$object_id]['object'] = ['type_id' => $type_id, 'object_id' => $object_id];
			}
			
			if ($count == 0) {
				
				$return = '<div class="a"><div class="image"><span>N</span></div><div class="name"><span>'.getLabel('msg_not_available').'</span></div></div>';
				
			} else if ($count > 1) {
				
				$return = '<div class="a"><div class="name"><span>';
				
				foreach ($value as $type_id => $arr_objects) {
					
					if (!$arr_public_interface_project_types[$type_id]) {
						continue;
					}

					$arr_type_set = StoreType::getTypeSet($type_id);
					
					if (count((array)$arr_objects) > 1) {
					
						$return .= count((array)$arr_objects).' '.($arr_public_interface_settings['labels']['type'][$type_id]['plural'] ? Labels::parseTextVariables($arr_public_interface_settings['labels']['type'][$type_id]['plural']) : Labels::parseTextVariables($arr_type_set['type']['name'])).'. ';
					
					} else {
					
						$return .= count((array)$arr_objects).' '.($arr_public_interface_settings['labels']['type'][$type_id]['singular'] ? Labels::parseTextVariables($arr_public_interface_settings['labels']['type'][$type_id]['singular']) : Labels::parseTextVariables($arr_type_set['type']['name'])).'. ';
					
					}
				}
				
				$return .= '</span></div></div>';
				
			} else {
				
				$return = self::createViewTypeObjectThumbnail(current($arr_type_objects));
			}
			
			$return .= '<button class="a"><span class="icon">'.getIcon('close').'</span></button>';
			
			$this->html = $return;
		}
		
		if ($method == "object_url") {
			
			$arr_id = explode('_', $id);
			$action = $arr_id[0];
			$type_id = (int)$arr_id[1];
			$object_id = (int)$arr_id[2];
			
			$url = self::getObjectURL($type_id, $object_id);
			
			if ($action == 'get') {

				$return = '<h1>'.getLabel('lbl_url').'</h1><input type="text" value="'.$url.'">';	
						
			} else if ($action == 'share') {
				
				$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
				$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);

				$public_interface_name = Labels::parseTextVariables($arr_public_user_interface['interface']['name']);
				$arr_name = FilterTypeObjects::getTypeObjectNames($type_id, $object_id);
				$object_name = $arr_name[$object_id];

				$arr_shares = cms_nodegoat_public_interfaces::getPublicInterfaceShareOptions($public_interface_name.' - '.$object_name);
				
				$return = '<h1>'.getLabel('lbl_share').' '.$object_name.':</h1>';
				
				foreach ($arr_shares as $arr_share) {
					$return .= '<button class="share" type="button" data-href="'.$arr_share['share_url'].$url.'" title="'.$arr_share['share_name'].'">
						'.($arr_share['icon_class'] ? '<span class="'.$arr_share['icon_class'].'"></span>' : '<span class="icon">'.getIcon($arr_share['icon']).'</span>').'						
					</button>';
				}
							
			}
			
			$this->html = ui::createViewElm('<div class="url">'.$return.'</div>');
			
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
				
				$response_format = Response::getFormat(); // Response could have changed in the following steps; store it
				
				try {
					
					$has_package = $export->createPackage($arr_export_settings['format']['settings'][$str_format_type]);
				} catch (Exception $e) {
					
					Response::setFormat($response_format);
					
					throw($e);
				}
				
				if (!$has_package) {
					
					Response::setFormat($response_format);
					
					$this->message = getLabel('msg_export_not_available');
					return;
				}

				$export->readPackage('export');
				
				die;
			} else {
				 
				$this->do_download = true;
			}			
		}
	}
	
	public static function checkObjectProject($type_id, $object_id) {
		
		$project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		$arr_project = StoreCustomProject::getProjects($project_id);
		
		$arr_objects = [];
		
		// Check if Object is available in current project. If not, try other projects that have been enabled in the public user interface
		if ($arr_project['types'][$type_id]['type_filter_id'])  {
		
			$arr_ref_type_ids = StoreCustomProject::getScopeTypes($project_id);
			$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ID, false);			
			$filter->setVersioning(GenerateTypeObjects::VERSIONING_ADDED);
			$filter->setScope(['types' => $arr_ref_type_ids, 'project_id' => $project_id]);
			$filter->setFilter(['objects' => $object_id]);
		
			$arr_use_project_ids = array_keys($arr_project['use_projects']);
			$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($project_id, false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
				
			$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_project_filters['object']));
			
			$arr_objects = $filter->init();
		
			if (!count($arr_objects)) {
				
				$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
				$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);
				
				foreach ($arr_public_user_interface['project_types'] as $ref_project_id => $arr_ref_project_type_ids) {
					
					if ($ref_project_id == $project_id) { // only check other projects
						
						continue;
					}
					
					if ($arr_ref_project_type_ids[$type_id]) {
						
						$arr_project = StoreCustomProject::getProjects($ref_project_id);
						
						if (!$arr_project['types'][$type_id]['type_filter_id']) {
							
							$arr_objects = [$id];
							
						} else {
						
							$arr_ref_type_ids = StoreCustomProject::getScopeTypes($ref_project_id);
							$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ID, false);			
							$filter->setVersioning(GenerateTypeObjects::VERSIONING_ADDED);
							$filter->setScope(['types' => $arr_ref_type_ids, 'project_id' => $ref_project_id]);
							$filter->setFilter(['objects' => $object_id]);
						
							$arr_use_project_ids = array_keys($arr_project['use_projects']);
							$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($ref_project_id, false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
								
							$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_project_filters['object']));
							
							$arr_objects = $filter->init();
						}
						
						if (count($arr_objects)) { // go to other project
						
							$url = SiteStartEnvironment::getBasePath(0, false).SiteStartEnvironment::getPage('name').'.p/'.$public_user_interface_id.'/'.$ref_project_id.'/object/'.$type_id.'-'.$object_id;
							Response::location($url);
						}
					}
				}
			}
		}		
	}
	
	private static function getPublicInterfaceObject($type_id, $object_id) {

		if (!(int)$type_id || !(int)$object_id) {
			return false;
		}

		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
		
		$use_custom_project_id = ui::checkPrimaryProjectProjectID($type_id);
	
		if ($use_custom_project_id) {
			
			$public_user_interface_active_custom_project_id = $use_custom_project_id;
			
		} else {
			
			$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');	
		}

		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		
		$arr_project = StoreCustomProject::getProjects($public_user_interface_active_custom_project_id);
		
		$arr_type_set = StoreCustomProject::getTypeSetReferenced($type_id, $arr_project['types'][$type_id], StoreCustomProject::ACCESS_PURPOSE_VIEW);
		$arr_ref_type_ids = StoreCustomProject::getScopeTypes($public_user_interface_active_custom_project_id);

		$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ALL, false, $arr_type_set);			
		$filter->setVersioning(GenerateTypeObjects::VERSIONING_ADDED);
		$filter->setScope(['types' => $arr_ref_type_ids, 'project_id' => $public_user_interface_active_custom_project_id]);
		$filter->setSelection(['object_sub_details' => []]);
		
		$filter->setFilter(['objects' => $object_id]);
		
		//if ($arr_project['types'][$type_id]['type_filter_id']) {
		//	
		//	$arr_use_project_ids = array_keys($arr_project['use_projects']);
		//	$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($public_user_interface_active_custom_project_id, false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
		//		
		//	$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_project_filters['object']));
		//}

		$filter->setConditions(GenerateTypeObjects::CONDITIONS_MODE_STYLE_INCLUDE, toolbar::getTypeConditions($type_id));

		$arr_object = current($filter->init());

		if ($arr_object) {
			
			$object_available = true;
		} else {
			
			$arr_name = FilterTypeObjects::getTypeObjectNames($type_id, $object_id);
			$object_name = $arr_name[$object_id];
			
			$arr_object['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id, 'object_name' => $object_name];			
		}
		
		// all in en out refs and store them!
		$arr_filter = ['referenced_object' => ['object_id' => [$object_id], 'type_id' => $type_id, 'options' => ['sources' => true]]];
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id);		
		
		foreach ((array)$arr_public_interface_settings['types'] as $type_setting_id => $arr_type_settings) {
			
			if ($arr_type_settings['explore'] || $arr_type_settings['primary']) {
				$arr_public_interface_project_types[$type_setting_id] = $type_setting_id;
			}
		}

		foreach ((array)$arr_public_interface_project_types as $ref_type_id) {
			
			$arr_object['object_referenced'][$ref_type_id] = ui_view_objects::getTypeObjectIDs($ref_type_id, $arr_filter, false);

			if ((array)$arr_public_interface_settings['types'][$ref_type_id]['explore']) {
			
				$arr_object['object_explore_referenced_references'][$ref_type_id] = $arr_object['object_referenced'][$ref_type_id];
			}
		}
			
		if ($object_available) {
						
			$arr_object['object']['type_id'] = $type_id;
			
			if ($arr_type_set['object_sub_details']) {
				$arr_object_subs_info = $filter->getInfoObjectSubs();			
				$arr_object['object_subs_info'] = $arr_object_subs_info[$object_id];
			}
			
			$object_thumbnail = ui_view_objects::getObjectsThumbnail([$object_id => $arr_object]);
			$arr_object['object_thumbnail'] = $object_thumbnail[$object_id]['object_thumbnail'];
			
			foreach ((array)$arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				$arr_object_definition = $arr_object['object_definitions'][$object_description_id];
				
				if ((!$arr_object_definition['object_definition_value'] && !$arr_object_definition['object_definition_ref_object_id']) || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, $object_description_id) || $arr_object_definition['object_definition_style'] === GenerateTypeObjects::CONDITION_ACTION_HIDE) {
					continue;
				}
				
				if ($arr_object_definition['object_definition_ref_object_id']) {
					
					if ($arr_object_description['object_description_ref_type_id']) {
						
						foreach ((array)$arr_object_definition['object_definition_ref_object_id'] as $ref_object_id) {
							
							if (is_array($ref_object_id)) {

								continue;
							}
							
							$ref_type_id = $arr_object_description['object_description_ref_type_id'];
							
							$arr_ids = explode('_', $ref_object_id);
							
							if ($arr_ids[1]) {
								$ref_object_id = $arr_ids[0];
								$ref_type_id = $arr_ids[1];
							}
							
							$arr_object['object_references'][$ref_type_id][$ref_object_id]['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id];
						}
							
					} else { 
				
						foreach ((array)$arr_object_definition['object_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
							
							foreach ((array)$arr_ref_objects as $arr_ref_object) {
								
								$ref_object_id = $arr_ref_object['object_definition_ref_object_id'];
								
								$arr_object['object_references'][$ref_type_id][$ref_object_id]['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id];
							}	
						}
					}
					
					foreach ((array)$arr_object['object_references'] as $ref_type_id => $arr_object_references) {
						
						if ($arr_public_interface_settings['types'][$ref_type_id]['explore']) {
							
							foreach ((array)$arr_object_references as $ref_object_id => $arr_object_reference) {
								
								if (!$arr_object['object_explore_referenced_references'][$ref_type_id][$ref_object_id]) {
									
									$arr_object['object_explore_referenced_references'][$ref_type_id][$ref_object_id]['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id];
								
								}
							}
						}
					}
				}	
			}
		}

		return $arr_object;
	}
	
	public static function getObjectReferences($type_id, $object_id, $direction = 'both', $arr_reference_type_ids = false, $do_merge = false) {
		
		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
			
		if (!$arr_reference_type_ids) {
						
			$arr_reference_type_ids = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id);		
		
			foreach ((array)$arr_public_interface_settings['types'] as $type_setting_id => $arr_type_settings) {
				
				if ($arr_type_settings['explore'] || $arr_type_settings['primary']) {
					$arr_reference_type_ids[$type_setting_id] = $type_setting_id;
				}
			}	
		}
		
		if ($do_merge) {
			
			$arr = [];
		} else {
			
			$arr = ['object_referenced' => [], 'object_references' => [], 'count' => ['referenced' => 0, 'references' => 0]];
		}
		
		if ($direction == 'in' || $direction == 'both') {
			
			$arr_filter = ['referenced_object' => ['object_id' => [$object_id], 'type_id' => $type_id, 'options' => ['sources' => true]]];
			
			foreach ((array)$arr_reference_type_ids as $ref_type_id) {
				
				if ($do_merge) {
					
					$arr = $arr + ui_view_objects::getTypeObjectIDs($ref_type_id, $arr_filter, false);
				} else {
				
					$arr['object_referenced'][$ref_type_id] = ui_view_objects::getTypeObjectIDs($ref_type_id, $arr_filter, false);
					$arr['count']['referenced'] += count((array)$arr['object_referenced'][$ref_type_id]);
				}
			}			
		}
		
		if ($direction == 'out' || $direction == 'both') {
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			$arr_selection = ui_view_objects::getTypeSelection($type_id, ['referencing' => true]);
			$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ALL);			
			$filter->setVersioning(GenerateTypeObjects::VERSIONING_ADDED);
			$filter->setSelection($arr_selection);
			$filter->setFilter(['objects' => $object_id]);	
			$arr_object = current($filter->init());
			
			foreach ((array)$arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				$arr_object_definition = $arr_object['object_definitions'][$object_description_id];
				
				if ((!$arr_object_definition['object_definition_value'] && !$arr_object_definition['object_definition_ref_object_id']) || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, $object_description_id) || $arr_object_definition['object_definition_style'] === GenerateTypeObjects::CONDITION_ACTION_HIDE) {
					continue;
				}
				
				if ($arr_object_definition['object_definition_ref_object_id']) {
					
					if ($arr_object_description['object_description_ref_type_id']) {
						
						foreach ((array)$arr_object_definition['object_definition_ref_object_id'] as $ref_object_id) {
							
							if (is_array($ref_object_id)) {

								continue;
							}
							
							$ref_type_id = $arr_object_description['object_description_ref_type_id'];
							
							if (in_array($ref_type_id, $arr_reference_type_ids)) {
								
								if ($do_merge) {
									
									$arr[$ref_object_id]['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id];
									
								} else {
									
									$arr['object_references'][$ref_type_id][$ref_object_id]['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id];
									$arr['count']['references']++;
								}
							}
						}
							
					} else { 
				
						foreach ((array)$arr_object_definition['object_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
							
							foreach ((array)$arr_ref_objects as $arr_ref_object) {
								
								$ref_object_id = $arr_ref_object['object_definition_ref_object_id'];
								
								if (in_array($ref_type_id, $arr_reference_type_ids)) {
									
									if ($do_merge) {
										
										$arr[$ref_object_id]['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id];
										
									} else {
										
										$arr['object_references'][$ref_type_id][$ref_object_id]['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id];
										$arr['count']['references']++;
									}
								}
							}	
						}
					}
				}	
			}
		}
		
		return $arr;
	}

	private static function getObjectURL($type_id, $object_id) {
		
		$public_user_interface_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = (int)SiteStartEnvironment::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		
		if (!$arr_public_interface_settings['uri_nodegoat_id']) {
		
			$arr_selection = ui_view_objects::getTypeSelection($type_id, ['identifier' => true]);
			
			$identifier = false;
				
			if ($arr_selection) {
				
				$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ALL);			
				$filter->setVersioning(GenerateTypeObjects::VERSIONING_ADDED);
				$filter->setSelection($arr_selection);
				$filter->setFilter(['objects' => $object_id]);
				$arr_object = current($filter->init());

				foreach ((array)$arr_object['object_definitions'] as $arr_object_definition) {
				
					$identifier = $arr_object_definition['object_definition_value'];
					break;
				}
			}
		}
		
		if (!$identifier) {
			
			$identifier = GenerateTypeObjects::encodeTypeObjectID($type_id, $object_id);
			
		}

		if ($identifier && $arr_public_interface_settings['short_url_host']) {

				$url = $arr_public_interface_settings['short_url_host'].'/'.$identifier;
				
		}
		
		if (!$url) {	
			
			$url = SiteStartEnvironment::getBasePath(0, false).SiteStartEnvironment::getPage('name').'.p/'.$public_user_interface_id.'/'.$public_user_interface_active_custom_project_id.'/object/'.$type_id.'-'.$object_id;
				
		}
		
		return $url;
	}
}
