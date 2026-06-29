<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class retrieval extends base_module {

	public static function moduleProperties() {
		static::$label = 'Retrieval RAG';
		static::$parent_label = 'nodegoat';
	}
	
	protected $arr_access = [
		'data_filter' => []
	];
	
	protected $form_name = 'retrieval';
	protected $num_data_limit = 1000;
	
	public function contents() {
		
		$str_html = '<h1>'.getLabel('lbl_retrieval_augmented_generation').'</h1>
		
		<form id="f:retrieval:run-0">
			
			<div class="options">
				<h2>'.getLabel('lbl_retrieval_augmented_generation_prompt').'</h2>
				
				<textarea name="'.$this->form_name.'[prompt]"></textarea>
			</div>
			
			'.$this->createSourceResource().'
			
			'.$this->createSourceTypeFilter().'
						
			'.$this->createRetrievalResource().'

			<menu class="options">
				<input type="submit" value="'.getLabel('lbl_run').'" />
			</menu>
		</form>
		
		<section class="options">
			<h2>'.getLabel('lbl_result').'</h2>
			
			<p>Please provide a prompt and configure the nodegoat <em>Retrieval-Augmented Generation</em> pipeline.</p>
			<p class="hide">Generating...</p>
			<article class="options nested"></article>
		</section>';
		
		return $str_html;
	}
	
	protected function createSourceResource($source_id = false) {
		
		$arr_sources = StoreResourceExternal::getResources();
		
		$arr_pointer_query_headings = static::getResourceResponseHeadings($source_id);
		$arr_pointer_response_headings = static::getResourceResponseHeadings($source_id);
	
		$str_html = '<div class="options">
			<fieldset><legend>'.getLabel('lbl_retrieval_augmented_generation_prompt').'</legend>
				<ul>
					<li>
						<label>'.getLabel('lbl_source').'</label>
						<div><select name="'.$this->form_name.'[source_id]">'.cms_general::createDropdown($arr_sources, $source_id, true).'</select></div>
					</li>
					<li>
						<label><span class="icon">'.getIcon('leftright-arrow-right').'</span><span>'.getLabel('lbl_retrieval_augmented_generation_prompt').'</span></label>
						<div><select id="y:retrieval:set_source_pointers-prompt" name="'.$this->form_name.'[source][query][prompt][heading]">'.cms_general::createDropdown($arr_pointer_query_headings, false, false).'</select></div>
					</li>
					<li>
						<label></label>
						<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
					</li>
					<li>
						<label><span>'.getLabel('lbl_query').'</span><span class="optional">'.getLabel('lbl_optional').'</span></label>
						<div id="y:retrieval:set_source_pointers-query">';
							
							$arr_sorter = [];
							
							$arr_pointers = [[]];
							array_unshift($arr_pointers, []); // Empty run for sorter source

							foreach ($arr_pointers as $key => $arr_pointer) {
								
								$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $this->createResourceQueryValue($source_id, 'source')];
							}

							$str_html .= cms_general::createSorter($arr_sorter, true);

						$str_html .= '</div>
					</li>
					<li>
						<label><span>'.getLabel('unit_data_vector').'</span><span class="icon">'.getIcon('leftright-arrow-right').'</span></label>
						<div><select id="y:retrieval:set_source_pointers-response" name="'.$this->form_name.'[source][response][vector][heading]">'.cms_general::createDropdown($arr_pointer_response_headings, false, false).'</select></div>
					</li>
				</ul>
			</fieldset>
		</div>';
		
		return $str_html;
	}

	protected function createSourceTypeFilter($type_id = false, $filter_id = false) {
		
		$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_types_all = StoreType::getTypes(array_keys($arr_project['types']));
		
		foreach ($arr_types_all as $cur_type_id => $arr_type) {
			
			if ($arr_type['class'] == StoreType::TYPE_CLASS_SYSTEM) {
				unset($arr_types_all[$cur_type_id]);
			}
			
		}
		
		$arr_filter_modes = [['id' => 'map', 'name' => getLabel('lbl_map')], ['id' => 'filter', 'name' => getLabel('lbl_filter').' & '.getLabel('lbl_map')], ['id' => 'endpoint', 'name' => getLabel('lbl_filter_endpoint')]];
		
		$arr_filter_map = static::getTypeFilterMap($type_id);
		
		$arr_filters_having_endpoints = static::getTypeFiltersHavingEndpoints($type_id);
		$arr_filter_endpoints = static::getTypeFilterEndpoints($type_id, $filter_id);
	
		$str_html = '<div class="options retrieval-filter">
			<fieldset><legend>'.getLabel('lbl_retrieval_augmented_generation_augment').'</legend>
				<ul>
					<li>
						<label>'.getLabel('lbl_type').'</label>
						<div><select name="'.$this->form_name.'[type_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_all, $type_id, true)).'</select></div>
					</li>
					<li>
						<label>'.getLabel('lbl_filter').' '.getLabel('lbl_mode').'</label>								
						<div>'.cms_general::createSelectorRadio($arr_filter_modes, $this->form_name.'[filter_mode]', 'map').'</div>
					</li>
					<li data-section="filter">
						<label>'.getLabel('lbl_filter').'</label>
						<div>'
							.'<input type="hidden" name="'.$this->form_name.'[filter][form]" value="" />'
							.'<button type="button" id="y:data_filter:configure_application_filter-0" value="filter" title="'.getLabel('inf_application_filter').'" class="data edit popup"><span>filter</span></button>'
						.'</div>
					</li>
					<li data-section="map">
						<label><span class="icon">'.getIcon('leftright-arrow-right').'</span><span>'.getLabel('unit_data_vector').'</span></label>
						<div><select id="y:retrieval:set_filter_map-0" name="'.$this->form_name.'[filter][map]">'.cms_general::createDropdown($arr_filter_map, false, false).'</select></div>
					</li>
					<li data-section="endpoint">
						<label>'.getLabel('lbl_filter').'</label>
						<div><select id="y:retrieval:set_filter_endpoints-0" name="'.$this->form_name.'[filter_id]">'.cms_general::createDropdown($arr_filters_having_endpoints, $filter_id, true).'</select></div>
					</li>
					<li data-section="endpoint">
						<label></label>
						<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
					</li>
					<li data-section="endpoint">
						<label><span>'.getLabel('lbl_parameter').'</span><span class="optional">'.getLabel('lbl_optional').'</span></label>
						<div id="y:retrieval:set_filter_endpoint_parameters-other">';
							
							$arr_sorter = [];
							
							$arr_endpoints = [[]];
							array_unshift($arr_endpoints, []); // Empty run for sorter source

							foreach ($arr_endpoints as $key => $arr_endpoint) {
								
								$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $this->createTypeFilterEndpoint($type_id, $filter_id)];
							}

							$str_html .= cms_general::createSorter($arr_sorter, true);

						$str_html .= '</div>
					</li>
					<li data-section="endpoint">
						<label><span class="icon">'.getIcon('leftright-arrow-right').'</span><span>'.getLabel('unit_data_vector').'</span></label>
						<div><select id="y:retrieval:set_filter_endpoint_parameters-vector" name="'.$this->form_name.'[filter][endpoint][vector][parameter]">'.cms_general::createDropdown($arr_filter_endpoints, false, false).'</select></div>
					</li>
					<li>
						<label></label>
						<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
					</li>
					<li>
						<label><span>'.getLabel('lbl_data').'</span><span class="icon">'.getIcon('leftright-arrow-right').'</span></label>
						<div id="y:retrieval:set_data-0">';
							
							$arr_sorter = [];
							
							$arr_data_map = [[]];
							array_unshift($arr_data_map, []); // Empty run for sorter source

							foreach ($arr_data_map as $key => $arr_select) {
								
								$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $this->createTypeDataMap($type_id)];
							}

							$str_html .= cms_general::createSorter($arr_sorter, true);

						$str_html .= '</div>
					</li>
					<li>
						<label>'.getLabel('lbl_limit').'</label>
						<div><input type="range" step="1" min="1" max="'.$this->num_data_limit.'" /><input type="number" name="'.$this->form_name.'[data][limit]" step="1" min="1" max="'.$this->num_data_limit.'" value="20"/></div>
					</li>
				</ul>
			</fieldset>
		</div>';
		
		return $str_html;
	}
		
	protected function createTypeFilterEndpoint($type_id, $filter_id, $arr_endpoint = []) {

		$arr_filter_endpoints = static::getTypeFilterEndpoints($type_id, $filter_id);
		
		$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
		
		$str_html_endpoint = '<select name="'.$this->form_name.'[filter][endpoint]['.$unique.'][parameter]">'.cms_general::createDropdown($arr_filter_endpoints, $arr_endpoint['parameter'], true).'</select>'
			.'<input type="text" name="'.$this->form_name.'[filter][endpoint]['.$unique.'][value]" value="'.strEscapeHTML($arr_endpoint['value']).'" />'
		;
		
		$arr_html = [];
	
		$str_html_endpoint = '<div>'.$str_html_endpoint.'</div>';
		
		$arr_html[] = $str_html_endpoint;
			
		return $arr_html;
	}
	
	protected function createTypeDataMap($type_id, $arr_select = []) {

		$arr_object_descriptions = [];
		
		if ($type_id) {
			$arr_object_descriptions = data_model::getTypeObjectDescriptionsByValueType($type_id, null, ['name' => true, 'name_plain' => true]);
		}
		
		$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
		
		$str_html = '<select name="'.$this->form_name.'[data][map]['.$unique.']">'.Labels::parseTextVariables(cms_general::createDropdown($arr_object_descriptions, $arr_select, true)).'</select>';
		
		$arr_html = [];
	
		$str_html = '<div>'.$str_html.'</div>';
		
		$arr_html[] = $str_html;
			
		return $arr_html;
	}
	
	protected function createRetrievalResource($retrieve_id = false) {
		
		$arr_sources = StoreResourceExternal::getResources();
		
		$arr_pointer_query_headings = static::getResourceQueryHeadings($retrieve_id);
		$arr_pointer_response_headings = static::getResourceResponseHeadings($retrieve_id);
	
		$str_html = '<div class="options">
			<fieldset><legend>'.getLabel('lbl_retrieval_augmented_generation_retrieve').'</legend>
				<ul>
					<li>
						<label>'.getLabel('lbl_source').'</label>
						<div><select name="'.$this->form_name.'[retrieve_id]">'.cms_general::createDropdown($arr_sources, $retrieve_id, true).'</select></div>
					</li>
					<li>
						<label><span class="icon">'.getIcon('leftright-arrow-right').'</span><span>'.getLabel('lbl_data').'</span></label>
						<div><select id="y:retrieval:set_retrieve_pointers-data" name="'.$this->form_name.'[retrieve][query][data][heading]">'.cms_general::createDropdown($arr_pointer_query_headings, false, false).'</select></div>
					</li>
					<li>
						<label></label>
						<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
					</li>
					<li>
						<label><span>'.getLabel('lbl_query').'</span><span class="optional">'.getLabel('lbl_optional').'</span></label>
						<div id="y:retrieval:set_retrieve_pointers-query">';
							
							$arr_sorter = [];
							
							$arr_pointers = [[]];
							array_unshift($arr_pointers, []); // Empty run for sorter source

							foreach ($arr_pointers as $key => $arr_pointer) {
								
								$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $this->createResourceQueryValue($retrieve_id, 'retrieve')];
							}

							$str_html .= cms_general::createSorter($arr_sorter, true);

						$str_html .= '</div>
					</li>
					<li>
						<label><span>'.getLabel('lbl_result').'</span><span class="icon">'.getIcon('leftright-arrow-right').'</span></label>
						<div><select id="y:retrieval:set_retrieve_pointers-response" name="'.$this->form_name.'[retrieve][response][result][heading]">'.cms_general::createDropdown($arr_pointer_response_headings, false, false).'</select></div>
					</li>
				</ul>
			</fieldset>
		</div>';
		
		return $str_html;
	}
	
	protected function createResourceQueryValue($resource_id, $str_name = '', $arr_pointer = []) {

		$arr_pointer_headings = static::getResourceQueryHeadings($resource_id);
		
		$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
		
		$str_html_pointer = '<select name="'.$this->form_name.'['.$str_name.'][query]['.$unique.'][heading]" title="'.getLabel('inf_data_ingest_pointer_query').'">'.cms_general::createDropdown($arr_pointer_headings, $arr_pointer['heading'], true).'</select>'
			.'<input type="text" name="'.$this->form_name.'['.$str_name.'][query]['.$unique.'][value]" title="'.getLabel('inf_ingest_query_value').'" value="'.strEscapeHTML($arr_pointer['value']).'" />'
		;
		
		$arr_html = [];
	
		$str_html_pointer = '<div>'.$str_html_pointer.'</div>';
		
		$arr_html[] = $str_html_pointer;
		
		return $arr_html;
	}
	
	public static function css() {
			
		$return = '
			.mod.retrieval > form > div > textarea { width: 100%; height: 10em; font-size: 1.4rem; }
			.mod.retrieval > form > div.retrieval-filter li > div > input[type=number] { width: 8ch; }
			.mod.retrieval > section > p { font-size: 1.4rem; margin-bottom: 0px; }
			.mod.retrieval > section > article { font-size: 1.4rem; white-space: pre-wrap; margin-top: 10px; }
			.mod.retrieval > section > article:not(:empty) + article { margin-top: 24px; }
			.mod.retrieval > section > article:empty { display: none; }
			.mod.retrieval > section > article > details { margin-top: 24px; }
			.mod.retrieval > section > article > details > p { font-size: 0.8em; font-family: var(--font-mono); }
			.mod.retrieval li > label > span.optional { font-size: 1rem; margin-left: 4px; }
			.mod.retrieval label > span + .icon,
			.mod.retrieval label > .icon + span { margin-left: 4px; }
			.mod.retrieval label > .icon svg { height: 0.8em; vertical-align: baseline; }
		';
	
		return $return;
	}
	
	public static function js() {

		$return = "
			SCRIPTER.static('.retrieval', function(elm_scripter) {
			
				const elm_form = elm_scripter.children('form');
				const elm_section = elm_scripter.children('section');
				const elm_run_info = elm_section.children('h2 + p');
				const elm_run_loading = elm_section.children('h2 + p + p');
				let elm_result = elm_section.children('article');
				
				COMMANDS.setTarget(elm_form, function(data) {
				
					elm_run_info[0].classList.add('hide');
					elm_run_loading[0].classList.add('hide');
					
					elm_result.append(data);
				});
				
				elm_form.on('command', 'input[type=submit]', function(e) {

					elm_run_info[0].classList.add('hide');
					elm_run_loading[0].classList.remove('hide');
					
					if (!elm_result.is(':empty')) {
						elm_result = $(elm_result[0].cloneNode(false)).insertBefore(elm_result);
					}
				}).on('ajaxerror', 'input[type=submit]', function() {

					elm_run_info[0].classList.remove('hide');
					elm_run_loading[0].classList.add('hide');
				}).on('change', '[name$=\"[source_id]\"], [name$=\"[retrieve_id]\"]', function(e) {

					const cur = $(this);
					const elm_fieldset = cur.closest('fieldset');
					const elm_resource_selector = elm_fieldset.find('[name$=\"[source_id]\"], [name$=\"[retrieve_id]\"]');
					
					const resource_id = elm_resource_selector[0].value;
									
					FEEDBACK.mergeRequests(true);
					
					runElementSelectorFunction(elm_fieldset, '[id^=y\\\:retrieval\\\:set_source_pointers-], [id^=y\\\:retrieval\\\:set_retrieve_pointers-]', function(elm_found) {
						
						COMMANDS.setData(elm_found, {resource_id: resource_id, form_name: FORMMANAGING.getElementNameBase(elm_found)});
						COMMANDS.quickCommand(elm_found, function(elm) {
							$(elm_found).html(elm);
						});
					});
					
					FEEDBACK.mergeRequests(false);
				}).on('change', '[name$=\"[type_id]\"]', function(e) {

					const cur = $(this);
					const elm_fieldset = cur.closest('fieldset');
					
					const type_id = this.value;

					FEEDBACK.mergeRequests(true);
										
					runElementSelectorFunction(elm_fieldset, '[id^=y\\\:retrieval\\\:set_filter_map-], [id^=y\\\:retrieval\\\:set_filter_endpoints-], [id^=y\\\:retrieval\\\:set_data-]', function(elm_found) {
						
						COMMANDS.setData(elm_found, {type_id: type_id, form_name: FORMMANAGING.getElementNameBase(elm_found)});
						COMMANDS.quickCommand(elm_found, function(elm) {
							
							$(elm_found).html(elm);
						});
					});
					
					FEEDBACK.mergeRequests(false);
				}).on('change', '[name$=\"[filter_mode]\"]', function(e) {

					const cur = $(this);
					const elm_fieldset = cur.closest('fieldset');
					
					const str_mode = this.value;
					
					const elms_map = elm_fieldset.find('li[data-section=\"map\"]');
					const elms_filter = elm_fieldset.find('li[data-section=\"filter\"]');
					const elms_endpoint = elm_fieldset.find('li[data-section=\"endpoint\"]');

					for (let i = 0, len = elms_map.length; i < len; i++) {
					
						if (str_mode == 'map' || str_mode == 'filter') {
							elms_map[i].classList.remove('hide');
						} else {
							elms_map[i].classList.add('hide');
						}
					}
					for (let i = 0, len = elms_filter.length; i < len; i++) {
					
						if (str_mode == 'filter') {
							elms_filter[i].classList.remove('hide');
						} else {
							elms_filter[i].classList.add('hide');
						}
					}
					for (let i = 0, len = elms_endpoint.length; i < len; i++) {
					
						if (str_mode == 'endpoint') {
							elms_endpoint[i].classList.remove('hide');
						} else {
							elms_endpoint[i].classList.add('hide');
						}
					}
				}).on('change', '[name$=\"[filter_id]\"]', function(e) {

					const cur = $(this);
					const elm_fieldset = cur.closest('fieldset');
					const elm_type_selector = elm_fieldset.find('[name$=\"[type_id]\"]');
					
					const type_id = elm_type_selector.val();
					const filter_id = cur.val();
									
					FEEDBACK.mergeRequests(true);
					
					runElementSelectorFunction(elm_fieldset, '[id^=y\\\:retrieval\\\:set_filter_endpoint_parameters-]', function(elm_found) {
						
						COMMANDS.setData(elm_found, {type_id: type_id, filter_id: filter_id, form_name: FORMMANAGING.getElementNameBase(elm_found)});
						COMMANDS.quickCommand(elm_found, function(elm) {
							
							$(elm_found).html(elm);
						});
					});
					
					FEEDBACK.mergeRequests(false);
				});
				
				SCRIPTER.dynamic('.retrieval > form > .retrieval-filter', 'application_filter');
				SCRIPTER.runDynamic(elm_form.children('div.retrieval-filter'));
				
				runElementSelectorFunction(elm_form, '[name$=\"[filter_mode]\"]:checked', function(elm_found) {
					SCRIPTER.triggerEvent(elm_found, 'change');
				});
			});
		";
		
		return $return;
	}
	
	public function commands($method, $id, $value = '') {
		
		if ($method == 'run') {
			
			$retrieve = new PromptRetrieveResourceExternal($_SESSION['custom_projects']['project_id']);
			
			if (Settings::get('debug', 'nodegoat_retrieval')) {
				$retrieve->debug();
			}
			
			$arr_template = $_POST['retrieval'];
			
			$type_id = (int)$arr_template['type_id'];
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_INTERACT) {
				error(getLabel('msg_not_allowed'));
			}

			if (!$type_id || !custom_projects::checkAccessType(StoreCustomProject::ACCESS_PURPOSE_VIEW, $type_id)) {
				error(getLabel('msg_type_does_not_exist'), TROUBLE_ERROR, LOG_CLIENT);
			}
			
			// Source
			
			$str_query_heading = $arr_template['source']['query']['prompt']['heading'];
			unset($arr_template['source']['query']['prompt']);
			
			$arr_query_headings_value = [];
			
			foreach ($arr_template['source']['query'] as $arr_heading_value) {
				
				if ((string)$arr_heading_value['heading'] === '' || (string)$arr_heading_value['value'] === '') {
					continue;
				}
				
				$arr_query_headings_value[$arr_heading_value['heading']] = $arr_heading_value['value'];
			}
			
			$str_response_heading = $arr_template['source']['response']['vector']['heading'];
			
			$retrieve->setResourceSource($arr_template['source_id'], $str_query_heading, $str_response_heading, $arr_query_headings_value);
			
			// Filter
			
			if ($arr_template['filter_mode'] == 'endpoint') {
				
				$str_vector_parameter = $arr_template['filter']['endpoint']['vector']['parameter'];
				unset($arr_template['filter']['endpoint']['vector']['parameter']);
				
				$arr_filter_endpoint = [];
				
				foreach ($arr_template['filter']['endpoint'] as $arr_endpoint) {
					
					if ((string)$arr_endpoint['parameter'] === '' || (string)$arr_endpoint['value'] === '') {
						continue;
					}
					
					$arr_filter_endpoint[$arr_endpoint['parameter']] = $arr_endpoint['value'];
				}
				
				$retrieve->setTypeFilterEndpoint($type_id, $arr_template['filter_id'], $str_vector_parameter, $arr_filter_endpoint);
			} else {
				
				$retrieve->setTypeFilterMap($type_id, $arr_template['filter']['map']);
				
				if ($arr_template['filter_mode'] == 'filter') {
					
					$arr_filter_additional = ($arr_template['filter']['form'] ? JSON2Value($arr_template['filter']['form']) : null);
					$retrieve->setTypeFilterAdditional($arr_filter_additional);
				}
			}
			
			// Text
			
			$retrieve->setTypeSelection($arr_template['data']['map'], $arr_template['data']['limit']);
			
			// Retrieve
			
			$str_query_heading = $arr_template['retrieve']['query']['data']['heading'];
			unset($arr_template['retrieve']['query']['data']);
			
			$arr_query_headings_value = [];
			
			foreach ($arr_template['retrieve']['query'] as $arr_heading_value) {
				
				if ((string)$arr_heading_value['heading'] === '' || (string)$arr_heading_value['value'] === '') {
					continue;
				}
				
				$arr_query_headings_value[$arr_heading_value['heading']] = $arr_heading_value['value'];
			}
			
			$str_response_heading = $arr_template['retrieve']['response']['result']['heading'];
			
			$retrieve->setResourceRetrieval($arr_template['retrieve_id'], $str_query_heading, $str_response_heading, $arr_query_headings_value);
			
			// Result output
			
			$str_html = '<p>'.$retrieve->runPrompt($arr_template['prompt']).'</p>';
			
			$str_html .= '<details class="options nested"><summary>'.getLabel('lbl_source').'</summary><p>'.$retrieve->getDataRetrieval().'</p></details>';
			
			$this->html = $str_html;
		}
		
		if ($method == 'set_source_pointers' || $method == 'set_retrieve_pointers') {
			
			$this->form_name = ($value['form_name'] ?: $this->form_name);
			
			$arr_sorter = [];
			
			if ($id == 'response') {
				
				$arr_pointer_headings = static::getResourceResponseHeadings($value['resource_id']);
				
				$this->html = cms_general::createDropdown($arr_pointer_headings, false, false);
			} else if ($id == 'prompt' || $id == 'data') {
				
				$arr_pointer_headings = static::getResourceQueryHeadings($value['resource_id']);

				$this->html = cms_general::createDropdown($arr_pointer_headings, false, false);
			} else {
				
				$arr_pointers = [[], []]; // Empty run for sorter source

				foreach ($arr_pointers as $key => $arr_pointer) {
					
					$str_html = $this->createResourceQueryValue($value['resource_id'], ($method == 'set_retrieve_pointers' ? 'retrieve' : 'source'), $arr_pointer);

					$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $str_html];
				}
			
				$this->html = cms_general::createSorter($arr_sorter, true);
			}
		}
		
		if ($method == 'set_filter_map') {

			$arr_filter_map = static::getTypeFilterMap($value['type_id']);

			$this->html = cms_general::createDropdown($arr_filter_map, false, false);
		}
		
		if ($method == 'set_filter_endpoints') {
			
			$arr_filters_having_endpoints = static::getTypeFiltersHavingEndpoints($value['type_id']);
			
			$this->html = cms_general::createDropdown($arr_filters_having_endpoints, false, true);
		}
		
		if ($method == 'set_filter_endpoint_parameters') {

			$this->form_name = ($value['form_name'] ?: $this->form_name);
							
			$arr_sorter = [];
			
			if ($id == 'vector') {
			
				$arr_filter_endpoints = static::getTypeFilterEndpoints($value['type_id'], $value['filter_id']);
				
				$this->html = cms_general::createDropdown($arr_filter_endpoints, false, false);
			} else {
				
				$arr_endpoints = [[], []]; // Empty run for sorter source

				foreach ($arr_endpoints as $key => $arr_endpoint) {
					
					$str_html = $this->createTypeFilterEndpoint($value['type_id'], $value['filter_id'], $arr_endpoint);

					$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $str_html];
				}
			
				$this->html = cms_general::createSorter($arr_sorter, true);
			}
		}
		
		if ($method == 'set_data') {

			$arr_sorter = [];
			
			$arr_data_map = [[]];
			array_unshift($arr_data_map, []); // Empty run for sorter source

			foreach ($arr_data_map as $key => $arr_select) {
				
				$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $this->createTypeDataMap($value['type_id'])];
			}

			$this->html = cms_general::createSorter($arr_sorter, true);
		}
	}
	
	public static function getResourceQueryHeadings($source_id) {
		
		if (!$source_id) {
			return [];
		}
			
		$arr_resource = StoreResourceExternal::getResources($source_id);
		$external = new ResourceExternal($arr_resource);
		
		$arr = $external->getQueryVariablesFlat();

		return $arr;
	}
	
	public static function getResourceResponseHeadings($source_id) {
		
		if (!$source_id) {
			return [];
		}
		
		$arr_resource = StoreResourceExternal::getResources($source_id);
		$external = new ResourceExternal($arr_resource);
		
		$arr_values = $external->getResponseValues(true);
		
		$arr = [];

		foreach ($arr_values as $name => $arr_value) {
			
			$arr[] = ['id' => $name, 'name'=> $name];
		}
			
		return $arr;
	}
	
	protected function getTypeFilterMap($type_id) {
		
		if (!$type_id) {
			return [];
		}

		$arr_object_descriptions = data_model::getTypeObjectDescriptionsByValueType($type_id, ['vector' => true]);
		
		return $arr_object_descriptions;
	}
		
	public static function getTypeFiltersHavingEndpoints($type_id) {
		
		if (!$type_id) {
			return [];
		}
		
		$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_types_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids);
		
		$arr = [];
		
		foreach ($arr_types_filters as $filter_id => $arr_project_filter) {
			
			try {
				$arr_description = ParseTypeFeatures::parseDescriptionTypeFilter($type_id, $arr_project_filter);
			} catch (Exception $e) {
				continue;
			}
	
			if (!$arr_description) {
				continue;
			}
			
			$str_description = Labels::parseTextVariables($arr_description['text']);
			
			$arr[$filter_id] = ['id' => $filter_id, 'name' => $arr_project_filter['name'], 'description' => $str_description];
		}

		return $arr;
	}
	
	public static function getTypeFilterEndpoints($type_id, $filter_id) {
		
		if (!$filter_id) {
			return [];
		}
		
		$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_project_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, $filter_id, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids);
		
		try {
			$arr_description = ParseTypeFeatures::parseDescriptionTypeFilter($type_id, $arr_project_filter);
		} catch (Exception $e) {
			return [];
		}
	
		if (!$arr_description) {
			return [];
		}
	
		$arr = [];
		
		foreach ($arr_description['parameters'] as $str_parameter => $arr_parameter) {
			
			$arr[$str_parameter] = ['id' => $str_parameter, 'name' => $str_parameter.': '.$arr_parameter['description']];
		}
		
		return $arr;
	}
}
