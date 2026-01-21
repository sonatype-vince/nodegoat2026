<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class data_api extends api_io {

	protected static $arr_output_modes_data = [
		'raw' => 'raw',
		'default' => 'default'
	];
	protected static $arr_output_modes_data_model = [
		'template' => 'template',
		'default' => 'default'
	];
	
	protected static $num_objects_stream = 5000;
	protected static $num_objects_scope_stream = 1000;
	protected static $num_store_objects_buffer = 1000;
	
	protected $arr_settings = [];
	protected $arr_request_variables = [];
	protected $is_user = false;
	protected $is_administrator = false;
	protected $versioning = true;
	
	protected $project_id;
	protected $type_id;
	protected $arr_client_update_objects;
	protected $arr_client_add_objects;
	protected $count_objects_updated;
	protected $count_objects_added;
	
	public function __construct() {
		
		$this->project_id = $_SESSION['custom_projects']['project_id']; // Checked and set based on the request in custom_projects
		
		$this->is_user = ($_SESSION['USER_ID'] && $_SESSION['NODEGOAT_CLEARANCE'] >= NODEGOAT_CLEARANCE_UNDER_REVIEW);
		$this->is_administrator = ($_SESSION['USER_ID'] && $_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN);
	}
		
	public function api() {

		$arr_request_variables = SiteStartEnvironment::getRequestVariables();
		
		if ($arr_request_variables && !end($arr_request_variables)) { // Remove the last empty request variable to allow for a final '/'
			unset($arr_request_variables[key($arr_request_variables)]);
		}
				
		$num_request_vars = count($arr_request_variables);

		if ($num_request_vars == 1 && $arr_request_variables[0] != 'reconcile') {
						
			$this->apiIdentifier($arr_request_variables[0]);
			return;
		} else if (($num_request_vars == 2 || $num_request_vars == 3) && $arr_request_variables[0] == 'project' && !variableHasValue($arr_request_variables[2], 'data', 'model', 'graph', 'reconcile')) {
						
			$this->apiIdentifier($arr_request_variables[2]);
			return;
		} else if (!$num_request_vars && SiteStartEnvironment::getRequestValue('id')) {
						
			$this->apiIdentifier(SiteStartEnvironment::getRequestValue('id'));
			return;
		} else if (!$num_request_vars) {
			
			return;
		}
		
		$setting = false;
		
		foreach ($arr_request_variables as $value) {
			
			if (variableHasValue($value, 'data', 'model', 'graph', 'reconcile')) {
				
				$this->arr_settings['mode'] = $value;
			} else if (variableHasValue($value, 'type', 'scope', 'filter', 'condition', 'object', 'analysis')) {
				
				$setting = $value;
				$this->arr_settings[$setting] = false;
			} else if ($setting) {
				
				if ($setting == 'object') {
					
					$value = explode(',', $value);
					
					foreach ($value as &$object_id) {
						$object_id = GenerateTypeObjects::parseTypeObjectID($object_id);
					}
					unset($object_id);
				}
				
				$this->arr_settings[$setting] = $value;
			}
		}
		
		$this->arr_request_variables = $arr_request_variables;
				
		if ($this->arr_settings['mode'] == 'data') {
			
			if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
				$this->apiDataStore();
			} else {
				$this->apiData();
			}
		} else if ($this->arr_settings['mode'] == 'model') {
			
			if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
				$this->apiDataModelStore();
			} else {
				$this->apiDataModel();
			}
		} else if ($this->arr_settings['mode'] == 'graph') {
			
			if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
				$this->apiDataGraphStore();
			} else {
				$this->apiDataGraph();
			}
		} else if ($this->arr_settings['mode'] == 'reconcile') {
			
			if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
				// Not possible
			} else {
				$this->apiDataReconcile();
			}
		} else {
			
			$this->errorInput('No mode specified');
		}
	}
	
	protected function apiIdentifier($str_identifier) {
		
		$type_id = false;
		$arr_object_ids = [];
		
		if ($str_identifier) {
			
			$arr_id = GenerateTypeObjects::decodeTypeObjectID($str_identifier);
			
			if ($arr_id) { // nodegoat ID
				
				$type_id = $arr_id['type_id'];
				$arr_object_ids[$arr_id['object_id']] = $arr_id['object_id'];
			} else {
				
				$arr_project = StoreCustomProject::getProjects($this->project_id);
				
				$arr_type_ids = array_keys($arr_project['types']);
				
				if (!$arr_type_ids) {
					return false;
				}
				
				$arr_type_object_descriptions = StoreType::getTypesObjectIdentifierDescriptions($arr_type_ids);
				
				$arr_type_objects = [];
				
				if ($arr_type_object_descriptions) {
					$arr_type_objects = FilterTypeObjects::getTypesObjectsByObjectDescriptions($str_identifier, $arr_type_object_descriptions);
				}
				
				if (!$arr_type_objects) {
					$arr_type_objects = FilterTypeObjects::getObjectsTypeObjects($str_identifier);
				}
				
				if (!$arr_type_objects) {
					return false;
				}

				if (count($arr_type_objects) > 1) { // Result contains multiple Types
					
					// Get result for the first sorted Type
					foreach ($arr_type_ids as $type_id) {
						
						if ($arr_type_objects[$type_id]) {
							
							$type_id = $type_id;
							$arr_object_ids = $arr_type_objects[$type_id];
							break;
						}
					}
				} else {
					
					$type_id = key($arr_type_objects);
					$arr_object_ids = current($arr_type_objects);
				}
			}
			
			if (!$arr_object_ids) {
				return false;
			}
		}
		
		$arr_api_configuration = cms_nodegoat_api::getConfiguration(SiteStartEnvironment::getAPI('id'));
		$str_url = $arr_api_configuration['projects'][$this->project_id]['identifier_url'];
		
		if ($str_url && SiteStartEnvironment::getRequestOutputFormat(['application/json', 'application/ld+json'])) {
			$str_url = false;
		}
		
		if ($str_url) {
			
			$func_parse_type_object = function($str, $object_id) use ($type_id) {
				
				$str = str_replace('[[type]]', $type_id, $str);
				$str = str_replace('[[object]]', $object_id, $str);
				
				return $str;
			};
			
			if (strpos($str_url, '[/multi]') === false) {
				
				$object_id = current($arr_object_ids);
				
				$str_url = $func_parse_type_object($str_url, $object_id);
			} else {
				
				$str_url = preg_replace_callback(
					'/\[multi(?:=(.+?))?\](.+?)\[\/multi\]/i',
					function($arr_matches) use ($arr_object_ids, $func_parse_type_object) {
						
						$arr_str = [];
						
						foreach ($arr_object_ids as $object_id) {
							
							$arr_str[] = $func_parse_type_object($arr_matches[2], $object_id);
						}
						
						return implode($arr_matches[1], $arr_str);
					},
					$str_url
				);
			}
			
			$str_url = Labels::parseTextVariables($str_url);
			
			Response::location($str_url);
			return true;
		}
			
		if (!$arr_object_ids) {
			return false;
		}
		
		$this->arr_settings['mode'] = 'data';
		$this->arr_settings['type'] = $type_id;
		$this->arr_settings['object'] = $arr_object_ids;
			
		$this->apiData();
	}
	
	// Get Data
		
	protected function apiData() {
		
		if (!$this->arr_settings['type'] || !isset($this->arr_settings['object'])) {
			$this->errorInput('No Type/Object specified');
		}
		
		$arr_project = StoreCustomProject::getProjects($this->project_id);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$type_id = (int)$this->arr_settings['type'];
		
		if (!$arr_project['types'][$type_id]) {
			$this->errorInput('No valid Type specified');
		}
		
		$arr_scope = [];
		
		if (SiteStartEnvironment::getRequestValue('scope') && !is_numeric(SiteStartEnvironment::getRequestValue('scope'))) { // Scope form

			$arr_scope = JSON2Value(SiteStartEnvironment::getRequestValue('scope'));
			
			$arr_scope = data_model::parseTypeNetwork($arr_scope);
		} else if (SiteStartEnvironment::getRequestValue('scope') || (int)$this->arr_settings['scope']) { // Scope ID
				
			$scope_id = (int)(SiteStartEnvironment::getRequestValue('scope') ?: $this->arr_settings['scope']);
			
			$arr_scope = cms_nodegoat_custom_projects::getProjectTypeScopes($this->project_id, $_SESSION['USER_ID'], $type_id, $scope_id, $arr_use_project_ids);
			$arr_scope = $arr_scope['object'];
			
			$arr_scope = data_model::parseTypeNetwork($arr_scope);
		}
		
		SiteEndEnvironment::setFeedback('condition_id', false, true);
		
		$condition_id = (int)(SiteStartEnvironment::getRequestValue('condition') ?: $this->arr_settings['condition']);
		
		if ($condition_id) {
			SiteEndEnvironment::setFeedback('condition_id', $condition_id, true);
		}

		$arr_filters = $this->getRequestTypeFilters();
	
		$arr_limit = [];
		$arr_order = [];
		
		if (SiteStartEnvironment::getRequestValue('limit') || SiteStartEnvironment::getRequestValue('offset')) {
			
			$arr_limit = [(int)SiteStartEnvironment::getRequestValue('offset'), (int)SiteStartEnvironment::getRequestValue('limit')];
		}
		if (SiteStartEnvironment::getRequestValue('order')) {
			
			$arr_order = JSON2Value(SiteStartEnvironment::getRequestValue('order'));
			
			if (!is_array($arr_order)) {
				
				$arr_order = explode(':', SiteStartEnvironment::getRequestValue('order'));
				$arr_order = [$arr_order[0] => (strtoupper(($arr_order[1] ?? '')) == 'DESC' ? 'DESC' : 'ASC')];
			}
		}
		
		$output_mode = (self::$arr_output_modes_data[SiteStartEnvironment::getRequestValue('output')] ?: 'default');
		
		$arr_ref_type_ids = StoreCustomProject::getScopeTypes($this->project_id);

		if ($arr_scope['paths']) {
						
			$trace = new TraceTypesNetwork(array_keys($arr_project['types']), true, true);
			$trace->filterTypesNetwork($arr_scope['paths']);
			$trace->run($type_id, false, cms_nodegoat_details::$num_network_trace_depth);
			$arr_type_network_paths = $trace->getTypeNetworkPaths(true);
		} else {
			$arr_type_network_paths = ['start' => [$type_id => ['path' => [0]]]];
		}
		
		$collect = new CollectTypesObjects($arr_type_network_paths, ($output_mode == 'raw' ? GenerateTypeObjects::VIEW_STORAGE : GenerateTypeObjects::VIEW_SET_EXTERNAL));
		$collect->setScope(['users' => $_SESSION['USER_ID'], 'types' => $arr_ref_type_ids, 'project_id' => $this->project_id]);
		
		if ($output_mode != 'raw') {
			
			$collect->setConditions(GenerateTypeObjects::CONDITIONS_MODE_FULL, function($type_id) {
				return toolbar::getTypeConditions($type_id);
			});
		}
		$collect->setGenerateCallback(function($generate, $cur_type_id) {
			$generate->setFormatMode(FormatTypeObjects::FORMAT_DATE_YMD | FormatTypeObjects::FORMAT_DATA_TYPE);
		});
		
		$collect->setFilter($arr_filters);
		$collect->init(false);
			
		$arr_collect_info = $collect->getResultInfo();
		
		$arr_type_sets = [];
		
		foreach ($arr_collect_info['types'] as $cur_type_id => $arr_paths) {
			
			if (!$arr_type_sets[$cur_type_id]) {
				$arr_type_sets[$cur_type_id] = StoreType::getTypeSet($cur_type_id);
			}
			
			$arr_type_set = $arr_type_sets[$cur_type_id];
			
			if ($arr_project['types'][$cur_type_id]['type_filter_id']) {
				
				$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($this->project_id, false, false, $arr_project['types'][$cur_type_id]['type_filter_id'], true, $arr_use_project_ids);
				$collect->addLimitTypeFilters($cur_type_id, FilterTypeObjects::convertFilterInput($arr_project_filters['object']), $arr_project['types'][$cur_type_id]['type_filter_object_subs']);
			}
			
			foreach ($arr_paths as $path) {
				
				$source_path = $path;
				
				if ($source_path) { // path includes the target type id, remove it
					
					$source_path = explode('-', $source_path);
					array_pop($source_path);
					$source_path = implode('-', $source_path);
				}
				
				$arr_settings = $arr_scope['types'][$source_path][$cur_type_id];
				
				$arr_filtering = [];
				if ($arr_settings['filter']) {
					$arr_filtering = ['all' => true];
				}
				
				$collapse = $arr_settings['collapse'];
				$arr_in_selection = ($arr_settings['selection'] ?: []);
				
				$arr_selection = ['object' => true, 'object_descriptions' => [], 'object_sub_details' => []];
				
				if ($arr_in_selection || $arr_settings['object_only']) {
					
					foreach ($arr_in_selection as $id => $arr_selected) {
						
						$object_description_id = $arr_selected['object_description_id'];
						
						if ($object_description_id) {
							
							if (!data_model::checkClearanceTypeConfiguration(StoreType::CLEARANCE_PURPOSE_VIEW, $arr_type_set, $object_description_id) || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, $object_description_id)) {
								continue;
							}
							
							$s_arr =& $arr_selection['object_descriptions'][$object_description_id];
							$s_arr['object_description_id'] = true;
							
							if ($arr_selected['use_value']) {
								$s_arr['object_description_value'] = true;
							}
							if ($arr_selected['use_reference']) {
								$s_arr['object_description_reference'] = $arr_selected['use_reference'];
								if ($arr_selected['use_reference_value']) {
									$s_arr['object_description_reference_value'] = $arr_selected['use_reference_value'];
								}
							}
						}
						
						$object_sub_details_id = $arr_selected['object_sub_details_id'];
						
						if ($object_sub_details_id) {

							if (!data_model::checkClearanceTypeConfiguration(StoreType::CLEARANCE_PURPOSE_VIEW, $arr_type_set, false, $object_sub_details_id) || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, false, $object_sub_details_id)) {
								continue;
							}
							
							if (!isset($arr_selection['object_sub_details'][$object_sub_details_id])) {
								
								$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'] = ['all' => true];
								$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'] = []; // Set default empty selection on sub object descriptions as there could be none selected
							}

							$object_sub_description_id = $arr_selected['object_sub_description_id'];
							
							if ($object_sub_description_id) {

								if (!data_model::checkClearanceTypeConfiguration(StoreType::CLEARANCE_PURPOSE_VIEW, $arr_type_set, false, $object_sub_details_id, $object_sub_description_id) || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
									continue;
								}
								
								$s_arr =& $arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
								$s_arr['object_sub_description_id'] = true;
								
								if ($arr_selected['use_value']) {
									$s_arr['object_sub_description_value'] = true;
								}
								if ($arr_selected['use_reference']) {
									$s_arr['object_sub_description_reference'] = $arr_selected['use_reference'];
									if ($arr_selected['use_reference_value']) {
										$s_arr['object_sub_description_reference_value'] = $arr_selected['use_reference_value'];
									}
								}
							}
						}
					}
					unset($s_arr);
				} else { // Nothing selected, use default

					foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
						
						if (!data_model::checkClearanceTypeConfiguration(StoreType::CLEARANCE_PURPOSE_VIEW, $arr_type_set, $object_description_id) || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, $object_description_id)) {
							continue;
						}
						
						$arr_selection['object_descriptions'][$object_description_id] = $object_description_id;
					}
								
					foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
						
						if (!data_model::checkClearanceTypeConfiguration(StoreType::CLEARANCE_PURPOSE_VIEW, $arr_type_set, false, $object_sub_details_id) || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, false, $object_sub_details_id)) {
							continue;
						}

						$arr_selection['object_sub_details'][$object_sub_details_id] = ['object_sub_details' => true, 'object_sub_descriptions' => []];

						foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
													
							if (!data_model::checkClearanceTypeConfiguration(StoreType::CLEARANCE_PURPOSE_VIEW, $arr_type_set, false, $object_sub_details_id, $object_sub_description_id) || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
								continue;
							}
								
							$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = $object_sub_description_id;
						}
					}
				}
				
				$arr_options = [
					'arr_selection' => $arr_selection,
					'arr_filtering' => $arr_filtering,
					'collapse' => $collapse
				];
				
				if ($path == 0) {
					if ($arr_limit) {
						$arr_options['limit'] = $arr_limit;
					}
					if ($arr_order) {
						$arr_options['order'] = $arr_order;
					}
				}
				
				$collect->setPathOptions([$path => $arr_options]);
			}
		}

		$str_format = SiteStartEnvironment::getRequestOutputFormat(['application/json', 'application/ld+json']);
		$arr_type_schemas = [];
		
		$obj_response = Response::getObject();
				
		if ($str_format == 'application/ld+json') {
			
			Response::setFormat(Response::getFormat() | Response::RENDER_LINKED_DATA);
			
			$request_id = URL_BASE.implode('/', $this->arr_request_variables);
			$arr_modifier_variables = SiteStartEnvironment::getModifierVariables();
			if ($arr_modifier_variables) {
				$request_id = $request_id.'?'.rawurldecode(http_build_query($arr_modifier_variables)); // Or use SiteStartEnvironment::getRequestValue for all variables
			}
			
			$schema = SERVER_SCHEME.SERVER_NAME_1100CC.'/model/type/';
			
			// Target and output to the Response object directly
			
			$this->data = null;
			
			$arr_schema_context = (Settings::get('nodegoat_api', 'context') ?: []);
			$arr_type_schemas = (Settings::get('nodegoat_api', 'schema') ?: []);
			
			foreach ($arr_type_sets as $cur_type_id => $arr_type_set) {
				
				foreach ($arr_type_set['definitions'] as $arr_definition) {
					
					$arr_label = Labels::parseNamespace($arr_definition['definition_name']);
					
					if ($arr_label === null || $arr_label['namespace'] != StoreType::TYPE_NAMESPACE_SCHEMA || !$arr_definition['definition_text']) {
						continue;
					}
					
					try {
						$arr_type_schema = strSerial2Value($arr_definition['definition_text']);
					} catch (Exception $e) {
						continue;
					}
					
					if (isset($arr_type_schema['context']) && is_array($arr_type_schema['context'])) {
						$arr_schema_context = arrMerge($arr_schema_context, $arr_type_schema['context']);
					}
					
					$arr_type_schema = (isset($arr_type_schema['schema']) ? $arr_type_schema['schema'] : (!isset($arr_type_schema['context']) ? $arr_type_schema : null));
					
					if (is_array($arr_type_schema)) {
						$arr_type_schemas[$cur_type_id] = arrMerge(($arr_type_schemas[$cur_type_id] ?? []), $arr_type_schema);
					}
				}	
			}
			
			$obj_response->{'@context'} = [
				'nodegoat' => $schema,
				'prov' => 'http://www.w3.org/ns/prov#',
				'schema' => 'http://schema.org/',
				'dc' => 'http://purl.org/dc/terms/',
				'modified' => [
					'@id' => 'dc:modified',
					'@type' => 'schema:dateTime'
				],
				'generated' => [
					'@id' => 'prov:generatedAtTime',
					'@type' => 'schema:dateTime'
				]
			];
			
			$obj_response->{'@context'} += $arr_schema_context;
			
			$obj_response->{'@id'} = $request_id;
			$obj_response->{'generated'} = $obj_response->timestamp;
			
			$obj_response->{'@graph'} = Response::getStream('[', ']');
		} else {
			
			$this->data['objects'] = Response::getStream('{', '}');
		}
		
		Response::openStream(false, $obj_response);

		$output_objects = new CreateTypesObjectsPackage($arr_type_sets, $arr_type_schemas);
		if ($output_mode == 'raw') {
			$output_objects->setMode(CreateTypesObjectsPackage::MODE_RAW);
		}
		
		Mediator::checkState();

		$arr_nodegoat_details = cms_nodegoat_details::getDetails();
		if ($_SESSION['USER_ID'] && $arr_nodegoat_details['processing_time']) {
			timeLimit($arr_nodegoat_details['processing_time']);
		}
		if ($arr_nodegoat_details['processing_memory']) {
			memoryBoost($arr_nodegoat_details['processing_memory']);
		}
		
		$num_stream = self::$num_objects_stream;
		
		if ($arr_collect_info['connections']) {
			$num_stream = self::$num_objects_scope_stream;
		}
		
		$collect->setInitLimit($num_stream);
		
		while ($collect->init()) {
			
			$arr_objects = $collect->getPathObjects(CollectTypesObjects::PATH_START);
			
			Mediator::checkState();
			
			if ($arr_collect_info['connections']) {
				$arr_objects = $output_objects->initPath($collect, $arr_objects);
			} else {
				$arr_objects = $output_objects->init($type_id, $arr_objects);
			}
			
			Mediator::checkState();
			
			if (count($arr_objects) > 20) { // Do not pretty print above a certain limit, use normal JSON
				Response::setFormat(Response::getFormat() & ~Response::PARSE_PRETTY);
			}
			
			Response::stream($arr_objects);
		}
	}
	
	// Store Data
	
	protected function apiDataStore() {
		
		if (!$_SESSION['USER_ID']) {
			error(getLabel('msg_access_denied'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}
		
		if (!$this->is_user) {
			error(getLabel('msg_not_allowed'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}
		
		if (!$this->arr_settings['type']) {
			$this->errorInput('No Type specified');
		}
		
		$arr_project = StoreCustomProject::getProjects($this->project_id);
		
		$type_id = (int)$this->arr_settings['type'];
		
		if (!$arr_project['types'][$type_id]) {
			$this->errorInput('No valid Type specified');
		}
		
		$this->type_id = $type_id;
		
		if (SiteStartEnvironment::getRequestValue('versioning') !== null && $this->is_administrator) {
			$this->versioning = (SiteStartEnvironment::getRequestValue('versioning') ? true : false);
		}
		
		$this->arr_client_update_objects = [];
		$this->arr_client_add_objects = [];
		$this->count_objects_updated = 0;
		$this->count_objects_added = 0;
		
		$arr_nodegoat_details = cms_nodegoat_details::getDetails();
		if ($arr_nodegoat_details['processing_time']) {
			timeLimit($arr_nodegoat_details['processing_time'] * 10);
		}
		if ($arr_nodegoat_details['processing_memory']) {
			memoryBoost($arr_nodegoat_details['processing_memory']);
		}
		
		if (!$this->arr_settings['object']) { // Get Object IDs from the data
			
			$input = fopen('php://input', 'r');
			$resource = getStreamMemory();
			
			stream_copy_to_stream($input, $resource);
			rewind($resource);
			fclose($input);
			
			$stream = new StreamJSONInput($resource);

			if ($_SERVER['REQUEST_METHOD'] === 'PATCH' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
				
				Mediator::checkState();

				$count = 0;
				
				$stream->init('{', function($str) use (&$count) {
								
					$arr_object = json_decode($str, true);
					
					if (!$arr_object) {
						return;
					}
					
					$object_id = GenerateTypeObjects::parseTypeObjectID(key($arr_object));

					$this->arr_client_update_objects[$object_id] = current($arr_object);
					
					$count++;
					
					if ($count == self::$num_store_objects_buffer) {
						
						$count = 0;

						$this->apiDataStoreUpdateTypeObjects();
						Mediator::checkState();
					}
				});
				
				if ($this->arr_client_update_objects) {
					$this->apiDataStoreUpdateTypeObjects();
				}
				
				if ($this->count_objects_updated) {
					
					Labels::setVariable('count', $this->count_objects_updated);
					msg(getLabel(($_SERVER['REQUEST_METHOD'] === 'DELETE' ? 'msg_object_deleted' : 'msg_object_updated')), false, LOG_CLIENT);
				}
			} else {
				
				Mediator::checkState();
								
				$count = 0;
				
				$stream->init('{"add":[', function($str) use (&$count) {
					
					$arr_object = json_decode($str, true);
					
					if (!$arr_object) {
						return;
					}
					
					$this->arr_client_add_objects[] = current($arr_object);
					
					$count++;
					
					if ($count == self::$num_store_objects_buffer) {
						
						$count = 0;

						$this->apiDataStoreAddTypeObjects();
						Mediator::checkState();
					}
				});
				
				if ($this->arr_client_add_objects) {
					$this->apiDataStoreAddTypeObjects();
				}
				
				if ($this->count_objects_added) {
					
					Labels::setVariable('count', $this->count_objects_added);
					msg(getLabel('msg_object_added'), false, LOG_CLIENT);
				}
				
				Mediator::checkState();
				
				$count = 0;
				
				$stream->init('{"update":{', function($str) use (&$count) {
			
					$arr_object = json_decode($str, true);
					
					if (!$arr_object) {
						return;
					}
					
					$object_id = GenerateTypeObjects::parseTypeObjectID(key($arr_object));
					
					$this->arr_client_update_objects[$object_id] = current($arr_object);
					
					$count++;
					
					if ($count == self::$num_store_objects_buffer) {
						
						$count = 0;

						$this->apiDataStoreUpdateTypeObjects();	
						Mediator::checkState();
					}
				});
				
				if ($this->arr_client_update_objects) {
					$this->apiDataStoreUpdateTypeObjects();
				}
				
				if ($this->count_objects_updated) {
					
					Labels::setVariable('count', $this->count_objects_updated);
					msg(getLabel('msg_object_updated'), false, LOG_CLIENT);
				}
				
				if (!$this->count_objects_updated && !$this->count_objects_added) {
					
					Mediator::checkState();
					
					$count = 0;
					
					$stream->init('[', function($str) use (&$count) {
					
						$arr_object = json_decode($str, true);
						
						if (!$arr_object) {
							return;
						}
						
						$this->arr_client_add_objects[] = current($arr_object);
						
						$count++;
						
						if ($count == self::$num_store_objects_buffer) {
						
							$count = 0;

							$this->apiDataStoreAddTypeObjects();			
							Mediator::checkState();
						}
					});
					
					if ($this->arr_client_add_objects) {
						$this->apiDataStoreAddTypeObjects();
					}
					
					if ($this->count_objects_added) {
						
						Labels::setVariable('count', $this->count_objects_added);
						msg(getLabel('msg_object_added'), false, LOG_CLIENT);
					}
				}
			}
			
			fclose($resource);
		} else {
			
			if (count($this->arr_settings['object']) > 1) { // There should be one Object ID provided
				$this->errorInput('No Object specified');
			}
			
			$str_client = file_get_contents('php://input');
			
			$arr_object = null;
			if ($str_client) {
				$arr_object = json_decode($str_client, true);
			}
			
			if ($arr_object) {
				
				$this->arr_client_update_objects[$this->arr_settings['object'][0]] = $arr_object;
				
				$this->apiDataStoreUpdateTypeObjects();
				
				if ($this->count_objects_updated) {
					
					Labels::setVariable('count', $this->count_objects_updated);
					msg(getLabel(($_SERVER['REQUEST_METHOD'] === 'DELETE' ? 'msg_object_deleted' : 'msg_object_updated')), false, LOG_CLIENT);
				}
			}
		}

		if (!$this->count_objects_updated && !$this->count_objects_added) {
			$this->errorInput('No (valid) data provided');
		}
	}
	
	protected function apiDataStoreUpdateTypeObjects() {
					
		$arr_filters = [];
		
		foreach ($this->arr_client_update_objects as $object_id => $arr_client_object) {
			
			$arr_filters['objects'][$object_id] = $object_id;
		}
		
		$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_ID);
		$filter->setVersioning();
		$filter->setFilter($arr_filters);

		$arr_objects = $filter->init();
		
		if (!$arr_objects) {
			
			$this->arr_client_update_objects = [];
			return;
		}

		$storage_lock = new StoreTypeObjects($this->type_id, false, $_SESSION['USER_ID'], 'lock');
		
		foreach ($arr_objects as $object_id => $arr_object) {
			$storage_lock->setObjectID($object_id);
		}
		
		$arr_locked = false;
		
		try {
			$storage_lock->handleLockObject();
		} catch (Exception $e) {
			$arr_locked = $storage_lock->getLockErrors();
		}
		
		if ($arr_locked) {
			
			$storage_lock->removeLockObject(); // Remove locks from all possible successful ones
			
			Labels::setVariable('total', count($arr_locked));
			
			$str_locked = '<ul><li>'.implode('</li><li>', $arr_locked).'</li></ul>';
			
			error(getLabel('msg_object_locked_multi').PHP_EOL
				.$str_locked
			, TROUBLE_ERROR, LOG_CLIENT);
		}
		
		$storage_lock->upgradeLockObject(); // Apply permanent lock
		
		$storage = new StoreTypeObjects($this->type_id, false, $_SESSION['USER_ID']);
		$storage->setVersioning($this->versioning);
		
		if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
			
			$storage->setMode(($_SERVER['REQUEST_METHOD'] === 'PUT' ? StoreTypeObjects::MODE_OVERWRITE : StoreTypeObjects::MODE_UPDATE), false);

			GenerateTypeObjects::dropResults(); // Cleanup possible leftover tables: clean transaction
			
			DB::startTransaction('data_api_store');
			
			try {
				
				$object_id_processing = 0;
				
				foreach ($arr_objects as $object_id => $arr_object) {
					
					$object_id_processing = $object_id;
				
					$storage->setObjectID($object_id);
					
					$storage->store((array)$this->arr_client_update_objects[$object_id]['object'], (array)$this->arr_client_update_objects[$object_id]['object_definitions'], (array)$this->arr_client_update_objects[$object_id]['object_subs']);
				}
					
				$storage->save();
					
				$storage->commit(($_SESSION['NODEGOAT_CLEARANCE'] >= NODEGOAT_CLEARANCE_USER));
			} catch (Exception $e) {
				
				Labels::setVariable('object', 'ID '.$object_id_processing);
				msg(getLabel('msg_object_error'), false, LOG_CLIENT, false, false, null, $e);

				DB::rollbackTransaction('data_api_store');
				throw($e);
			}
			
			$storage->touch(); // Make sure the objects get a status update as late as possible

			DB::commitTransaction('data_api_store');

			if (!$this->count_objects_updated) {
				
				$this->data['objects']['updated'] = Response::getStream('[', ']');
		
				Response::openStream(false, Response::getObject());
			}
			
			Response::stream(array_keys($arr_objects));
						
		} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
			
			foreach ($arr_objects as $object_id => $arr_object) {
				
				if ($this->arr_client_update_objects[$object_id] !== true) {
					
					unset($arr_objects[$object_id]);
					continue;
				}
			
				$storage->setObjectID($object_id);
			}
		
			$storage->delTypeObject(($_SESSION['NODEGOAT_CLEARANCE'] >= NODEGOAT_CLEARANCE_USER));
			
			$this->data['objects']['deleted'] = array_merge((array)$this->data['objects']['deleted'], array_keys($arr_objects));
		}
		
		$storage_lock->removeLockObject();
		
		$this->count_objects_updated += count($arr_objects);
		
		$this->arr_client_update_objects = [];
	}
	
	protected function apiDataStoreAddTypeObjects() {
				
		$storage = new StoreTypeObjects($this->type_id, false, $_SESSION['USER_ID']);
		
		$storage->setVersioning($this->versioning);
		$storage->setMode(null, false);

		DB::startTransaction('data_api_store');
		
		$arr_object_ids = [];
		
		try {
			
			$object_id_processing = 0;
			
			foreach ($this->arr_client_add_objects as $arr_client_object) {
								
				$storage->setObjectID(false);
				
				$object_id_processing = $storage->store((array)$arr_client_object['object'], (array)$arr_client_object['object_definitions'], (array)$arr_client_object['object_subs']);
				
				$arr_object_ids[] = $object_id_processing;
			}
				
			$storage->save();
				
			$storage->commit(($_SESSION['NODEGOAT_CLEARANCE'] >= NODEGOAT_CLEARANCE_USER));
		} catch (Exception $e) {
			
			msg('An error occured after processing a new Object with the Object ID '.$object_id_processing.'.', false, LOG_CLIENT, false, false, null, $e);

			DB::rollbackTransaction('data_api_store');
			throw($e);
		}
		
		$storage->touch(); // Make sure the objects get a status update as late as possible
		
		DB::commitTransaction('data_api_store');
		
		if (!$this->count_objects_added) {
			
			$this->data['objects']['added'] = Response::getStream('[', ']');
		
			Response::openStream(false, Response::getObject());
		}
		
		Response::stream($arr_object_ids);

		$this->count_objects_added += count($arr_object_ids);
		
		$this->arr_client_add_objects = [];
	}
	
	// Get Data Model
	
	protected function apiDataModel() {
		
		if (!isset($this->arr_settings['type'])) {
			$this->errorInput('No Type specified');
		}
				
		if (!$this->project_id) { // Possible for administrator only, determined in preload custom_projects
			
			$arr_types = StoreType::getTypes();
		} else {
			
			$arr_project = StoreCustomProject::getProjects($this->project_id);
			$arr_types = $arr_project['types'];
		}
		
		if ($this->arr_settings['type']) {
			
			$arr_type_ids = explode(',', $this->arr_settings['type']);
			$arr_type_ids = arrParseRecursive($arr_type_ids, TYPE_INTEGER);
		
			foreach ($arr_type_ids as $type_id) {
				
				if (!$arr_types[$type_id]) {
					$this->errorInput('No valid or Project-accessible Type specified');
				}
			}
		} else {
			
			$arr_type_ids = array_keys($arr_types);
		}
		
		$output_mode = (self::$arr_output_modes_data_model[SiteStartEnvironment::getRequestValue('output')] ?: 'default');
		
		$output_types = new CreateTypesPackage($_SESSION['USER_ID'], $this->project_id, $this->is_administrator);
		if ($output_mode == 'template') {
			$output_types->setMode(CreateTypesPackage::MODE_TEMPLATE);
		}
		
		$arr_data['types'] = $output_types->parseTypes($arr_type_ids, $_SESSION['NODEGOAT_CLEARANCE']);
				
		$this->data = $arr_data;
	}
	
	// Store Data Model
	
	protected function apiDataModelStore() {
		
		if (!$_SESSION['USER_ID']) {
			error(getLabel('msg_access_denied'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}
		
		if (!$this->is_administrator) {
			error(getLabel('msg_not_allowed'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}

		$arr_client = file_get_contents('php://input');
		if ($arr_client) {
			$arr_client = json_decode($arr_client, true);
		}
		
		if (!$arr_client) {
			$this->errorInput('No data provided');
		}
		
		$arr_client_update_types = [];
		$arr_client_add_types = [];
	
		if (!$this->arr_settings['type']) {
			
			if ($_SERVER['REQUEST_METHOD'] === 'PATCH' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
				
				$arr_client_update_types = $arr_client;
			} else {
				
				if ($arr_client['update'] || $arr_client['add']) {
					
					$arr_client_update_types = $arr_client['update'];
					$arr_client_add_types = $arr_client['add'];
				} else {
					
					$arr_client_add_types = $arr_client;
				}
			}
		} else {
			
			if (count($this->arr_settings['type']) > 1) { // There should be one Type ID provided
				$this->errorInput('No Type specified');
			}
			
			$arr_client_update_types[$this->arr_settings['type']] = $arr_client;
		}
		
		if ($arr_client_add_types) {

			DB::startTransaction('data_model_api_store');
			
			$arr_type_ids = [];
			
			try {
				
				$arr_client_resolve_types = [];
								
				foreach ($arr_client_add_types as $arr_client_type) {
					
					$store_type = new StoreType(false, $_SESSION['USER_ID']);
					
					$type_id = $store_type->store((array)$arr_client_type['type'], (array)$arr_client_type['definitions'], (array)$arr_client_type['object_descriptions'], (array)$arr_client_type['object_sub_details']);
					
					$arr_type_ids[] = $type_id;					
					
					if ($store_type->hasUnresolvedIDs()) {
						$arr_client_resolve_types[$type_id] = $arr_client_type;
					}
				}
				
				foreach ($arr_client_resolve_types as $type_id => $arr_client_type) {
					
					$store_type = new StoreType($type_id, $_SESSION['USER_ID']);
					$store_type->setMode(StoreType::MODE_UPDATE);
					
					$store_type->store((array)$arr_client_type['type'], (array)$arr_client_type['definitions'], (array)$arr_client_type['object_descriptions'], (array)$arr_client_type['object_sub_details']);
				}
				
				StoreType::setTypesObjectPaths();
			} catch (Exception $e) {

				DB::rollbackTransaction('data_model_api_store');
				throw($e);
			}

			DB::commitTransaction('data_model_api_store');
			
			Labels::setVariable('count', count($arr_type_ids));
			msg(getLabel('msg_type_added'), false, LOG_CLIENT);
			
			$this->data['types']['added'] = $arr_type_ids;
			
			if ($this->project_id) { 
							
				$custom_project = new StoreCustomProject($this->project_id);
				$custom_project->addTypes($arr_type_ids);
			}
		}
		
		if ($arr_client_update_types) {
			
			$store_type = new StoreType(false, $_SESSION['USER_ID']);
			
			$arr_project = null;
			if ($this->project_id) {
				$arr_project = StoreCustomProject::getProjects($this->project_id);
			}
			
			foreach ($arr_client_update_types as $type_id => $arr_client_type) {
				
				if (!$type_id || !$store_type->getTypeID($type_id)) {
					$this->errorInput('No valid Type specified');
				}
				
				if ($this->project_id && !$arr_project['types'][$type_id]) {
					$this->errorInput('No valid Project-accessible Type specified');
				}
			}

			if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
				
				$store_mode = ($_SERVER['REQUEST_METHOD'] === 'PUT' ? StoreType::MODE_OVERWRITE : StoreType::MODE_UPDATE);
				
				DB::startTransaction('data_model_api_store');

				try {
					
					$arr_client_resolve_types = [];
						
					foreach ($arr_client_update_types as $type_id => $arr_client_type) {
						
						$store_type = new StoreType($type_id, $_SESSION['USER_ID']);
						$store_type->setMode($store_mode);
						
						$store_type->store((array)$arr_client_type['type'], (array)$arr_client_type['definitions'], (array)$arr_client_type['object_descriptions'], (array)$arr_client_type['object_sub_details']);
						
						if ($store_type->hasUnresolvedIDs()) {
							$arr_client_resolve_types[$type_id] = $arr_client_type;
						}
					}
					
					foreach ($arr_client_resolve_types as $type_id => $arr_client_type) {
					
						$store_type = new StoreType($type_id, $_SESSION['USER_ID']);
						$store_type->setMode(StoreType::MODE_UPDATE);
						
						$store_type->store((array)$arr_client_type['type'], (array)$arr_client_type['definitions'], (array)$arr_client_type['object_descriptions'], (array)$arr_client_type['object_sub_details']);
					}
					
					StoreType::setTypesObjectPaths();
				} catch (Exception $e) {

					DB::rollbackTransaction('data_model_api_store');
					throw($e);
				}
				
				DB::commitTransaction('data_model_api_store');
				
				Labels::setVariable('count', count($arr_client_update_types));
				msg(getLabel('msg_type_updated'), false, LOG_CLIENT);
					
				$this->data['types']['updated'] = array_keys($arr_client_update_types);
			} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
				
				foreach ($arr_client_update_types as $type_id => $arr_client_type) {
					
					if ($arr_client_type !== true) {
						
						unset($arr_client_update_types[$type_id]);
						continue;
					}
					
					$store_type = new StoreType($type_id, $_SESSION['USER_ID']);
					
					$store_type->delType();
				}
				
				Labels::setVariable('count', count($arr_client_update_types));
				msg(getLabel('msg_type_deleted'), false, LOG_CLIENT);
				
				$this->data['types']['deleted'] = array_keys($arr_client_update_types);
			}
		}
	}
	
	// Get Data Analysis
		
	protected function apiDataGraph() {
		
		if (!$this->arr_settings['type']) {
			$this->errorInput('No Type specified');
		}
		
		$arr_project = StoreCustomProject::getProjects($this->project_id);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$type_id = (int)$this->arr_settings['type'];
		
		if (!$arr_project['types'][$type_id]) {
			$this->errorInput('No valid Type specified');
		}
		
		$analysis_id = (int)$this->arr_settings['analysis'];
		$arr_analysis = false;
		
		if ($analysis_id) {
			
			$arr_analysis = cms_nodegoat_custom_projects::getProjectTypeAnalyses($this->project_id, false, false, $analysis_id, $arr_use_project_ids);
			$arr_analysis = data_analysis::parseTypeAnalysis($type_id, $arr_analysis);
		}
				
		if (!$arr_analysis) {
			$this->errorInput('No valid Analysis ID specified');
		}
		
		$arr_filters = $this->getRequestTypeFilters();
		$arr_scope = $arr_analysis['scope'];
		
		SiteEndEnvironment::setFeedback('condition_id', false, true);
		
		$condition_id = (int)(SiteStartEnvironment::getRequestValue('condition') ?: $this->arr_settings['condition']);
		
		if ($condition_id) {
			SiteEndEnvironment::setFeedback('condition_id', $condition_id, true);
		}
		
		Response::setOutputUpdates(false); // Do not output anything that could mess up the export headers
		
		$collect = data_analysis::getTypeAnalysisCollector($type_id, $arr_analysis, $arr_filters, $arr_scope);
		
		$analyse = AnalyseTypeObjects::getAlgorithmClass($arr_analysis['algorithm']);
		$analyse = new $analyse($_SESSION['USER_ID'], $this->project_id, true);
		
		$analyse->setAnalyse($type_id, $arr_analysis);
		
		$analyse->input($collect);
		
		$analyse->readInputResourcePackage();
		
		exit;
	}
	
	protected function apiDataGraphStore() {
		
		if (!$this->arr_settings['type'] || !isset($this->arr_settings['object'])) {
			$this->errorInput('No Type/Object specified');
		}
		
		$arr_project = StoreCustomProject::getProjects($this->project_id);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$type_id = (int)$this->arr_settings['type'];
		
		if (!$arr_project['types'][$type_id]) {
			$this->errorInput('No valid Type specified');
		}
		
		$analysis_id = (int)$this->arr_settings['analysis'];
		$arr_analysis = false;
		
		if ($analysis_id) {
			
			$arr_analysis = cms_nodegoat_custom_projects::getProjectTypeAnalyses($this->project_id, false, false, $analysis_id, $arr_use_project_ids);
			$arr_analysis = data_analysis::parseTypeAnalysis($type_id, $arr_analysis);
		}
				
		if (!$arr_analysis) {
			$this->errorInput('No valid Analysis ID specified');
		}
		
		$storage = new StoreTypeObjectsExtensions($type_id, false, $_SESSION['USER_ID']);
		
		if (!$this->arr_settings['object']) { // Get Object IDs from the data
			
			$file_client = fopen('php://input', 'r');
		
			DB::startTransaction('data_api_store_analyse');
			
			if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
				$storage->resetTypeObjectAnalysis(0, $analysis_id);
			}
			
			try {
				
				 while (($arr_row = fgetcsv($file_client, null, ',', '"', CSV_ESCAPE)) !== false) {

					$object_id_processing = GenerateTypeObjects::parseTypeObjectID($arr_row[0]);
				
					$storage->setObjectID($object_id_processing);
					
					$number = ($_SERVER['REQUEST_METHOD'] === 'DELETE' ? 0 : $arr_row[1]);
				
					$storage->addTypeObjectAnalysis(0, $analysis_id, $number);
					
					$this->count_objects_updated++;
				}
				
				$storage->save();
			} catch (Exception $e) {
				
				Labels::setVariable('object', 'ID '.$object_id_processing);
				msg(getLabel('msg_object_error'), false, LOG_CLIENT, false, false, null, $e);

				DB::rollbackTransaction('data_api_store_analyse');
				throw($e);
			}
			
			fclose($file_client);

			DB::commitTransaction('data_api_store_analyse');			
		} else {
			
			if (count($this->arr_settings['object']) > 1) { // There should be one Object ID provided
				$this->errorInput('No Object specified');
			}
			
			$str_client = file_get_contents('php://input');
		
			DB::startTransaction('data_api_store_analyse');

			try {
				
				$object_id_processing = $this->arr_settings['object'][0];
				
				$storage->setObjectID($object_id_processing);
				
				$number = ($_SERVER['REQUEST_METHOD'] === 'DELETE' ? 0 : $str_client);
						
				$storage->addTypeObjectAnalysis(0, $analysis_id, $number);
				
				$this->count_objects_updated++;
				
				$storage->save();
			} catch (Exception $e) {

				Labels::setVariable('object', 'ID '.$object_id_processing);
				msg(getLabel('msg_object_error'), false, LOG_CLIENT, false, false, null, $e);
				
				DB::rollbackTransaction('data_api_store_analyse');
				throw($e);
			}
			
			DB::commitTransaction('data_api_store_analyse');
		}

		if ($this->count_objects_updated) {
			
			Labels::setVariable('count', $this->count_objects_updated);
			msg(getLabel('msg_object_updated'), false, LOG_CLIENT);
		} else {
			
			$this->errorInput('No (valid) data provided');
		}
	}
	
	// Get Data Reconcile
		
	protected function apiDataReconcile() {
		
		$arr_project = StoreCustomProject::getProjects($this->project_id);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$use_type_id = (int)$this->arr_settings['type'];
		
		if ($use_type_id && !$arr_project['types'][$use_type_id]) {
			$this->errorInput('No valid Type specified');
		}
		
		$arr_types_all = StoreType::getTypes(($use_type_id ? [$use_type_id] : array_keys($arr_project['types'])));
			
		$arr_types = [];
		foreach ($arr_types_all as $type_id => $arr_type) {
			$arr_types[$type_id] = ['id' => (string)$type_id, 'name' => Labels::parseTextVariables($arr_type['name'])];
		}

		if (SiteStartEnvironment::getRequestValue('queries')) {
			
			$arr_queries = json_decode(SiteStartEnvironment::getRequestValue('queries'), true);
			
			$arr_type_reconcile = [];
			
			foreach ($arr_queries as $key => $arr_query) {
				
				/*$arr_query['properties'][] = [
					'p' => 'object_description-5',
					'v' => 'hello'
				];*/
				
				$type_id = (int)($arr_query['type'] ?: $use_type_id);
				
				if (!$type_id) {
					continue;
				}
				
				$arr_type_reconcile[$type_id][$key] = $arr_query['query'];
			}
			
			unset($arr_queries);
			
			foreach ($arr_type_reconcile as $type_id => $arr_reconcile) {
					
				if (!$arr_project['types'][$type_id]) {
					$this->errorInput('No valid Type specified');
				}
				
				$arr_map_text = data_reconcile::getTypeObjectDescriptionsText($type_id);
				$arr_map_text = array_fill_keys(array_keys($arr_map_text), true);
				
				$reconcile = new ReconcileTypeObjectsValues($type_id);
				$reconcile->addTest(false, $arr_map_text);
				$reconcile->setSourceValues($arr_reconcile);
				
				$reconcile->init();
				
				$arr_results = $reconcile->getResult();
				
				$arr_target_object_ids = arrValuesRecursive('object_id', $arr_results);
				$arr_target_object_names = GenerateTypeObjects::getTypeObjectNames($type_id, $arr_target_object_ids, GenerateTypeObjects::CONDITIONS_MODE_TEXT);
				
				// Target and output to the Response object directly
				
				$this->data = null;
				$obj_response = Response::getObject();
						
				foreach ($arr_reconcile as $key => $arr_value) {
					
					if (!$arr_results[$key]['objects']) {
						
						$obj_response->{$key}['result'] = [];
						continue;
					}

					foreach ($arr_results[$key]['objects'] as $arr_result_objects) {
						
						$obj_response->{$key}['result'][] = [
							'id' => GenerateTypeObjects::encodeTypeObjectID($type_id, $arr_result_objects['object_id']),
							'name' => $arr_target_object_names[$arr_result_objects['object_id']],
							'type' => [$arr_types[$type_id]],
							'score' => $arr_result_objects['score'],
							'match' => false
						];
					}
				}
			}
		} else {
						
			$space_identifier = SiteStartEnvironment::getAPI('documentation_url');
			$space_schema = URL_BASE.'model/type/';
			
			// Target and output to the Response object directly
			
			$this->data = null;
			$obj_response = Response::getObject();
			
			$obj_response->name = 'nodegoat '.SiteStartEnvironment::getAPI('name').' Reconciliation Service'.($use_type_id ? ' ('.$arr_types[$use_type_id]['name'].')' : '');
			$obj_response->identifierSpace = $space_identifier;
			$obj_response->schemaSpace = $space_schema;
			$obj_response->defaultTypes = array_values($arr_types);
			$obj_response->view = [
				'url' => URL_BASE.'{{id}}'
			];
		}
		
		Response::setFormat(Response::getFormat() | Response::OUTPUT_JSONP);
	}
	
	protected function getRequestTypeFilters() {
		
		$arr_project = StoreCustomProject::getProjects($this->project_id);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$type_id = (int)$this->arr_settings['type'];
		
		$arr_filters = [];
		
		if ($this->arr_settings['filter']) { // Filter ID
				
			$filter_id = (int)$this->arr_settings['filter'];
			
			$arr_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($this->project_id, false, $type_id, $filter_id, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids);
			$arr_filter = FilterTypeObjects::convertFilterInput($arr_filter['object']);
			
			$arr_filters[] = $arr_filter;
		}
		
		if (SiteStartEnvironment::getRequestValue('filter')) { // Filter (additional)
			
			if (is_numeric(SiteStartEnvironment::getRequestValue('filter'))) { // Filter ID
				
				$filter_id = (int)SiteStartEnvironment::getRequestValue('filter');
			
				$arr_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($this->project_id, false, $type_id, $filter_id, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids);
				$arr_filter = FilterTypeObjects::convertFilterInput($arr_filter['object']);
				
				$arr_filters[] = $arr_filter;
			} else { // Filter form
				
				$arr_filter = JSON2Value(SiteStartEnvironment::getRequestValue('filter'));
				
				if ($arr_filter['filter_id']) { // Filter ID in form
					
					$filter_id = (int)$arr_filter['filter_id'];
					
					$arr_project_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($this->project_id, false, $type_id, $filter_id, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids);
	
					try {
						$arr_description = ParseTypeFeatures::parseDescriptionTypeFilter($type_id, $arr_project_filter, $arr_filter); // arr_filter will be replaced with the adjusted filter form
					} catch (Exception $e) {

						if ($e->getTroubleSuppress() != LOG_SYSTEM) {
							error($e->getTroubleMessage(), TROUBLE_ERROR, LOG_CLIENT);
						}

						throw($e);
					}
				}
				
				$arr_filter = FilterTypeObjects::convertFilterInput($arr_filter);
					
				$arr_filters[] = $arr_filter;
			}
		}
		
		if (SiteStartEnvironment::getRequestValue('object_id')) {
			
			$arr_filters['objects'] = explode(',', SiteStartEnvironment::getRequestValue('object_id'));
			
			foreach ($arr_filters['objects'] as &$object_id) {
				$object_id = GenerateTypeObjects::parseTypeObjectID($object_id);
			}
			unset($object_id);
			
		} else if ($this->arr_settings['object']) {
			
			$arr_filters['objects'] = $this->arr_settings['object'];
		}
		
		if (SiteStartEnvironment::getRequestValue('search')) {
			$arr_filters['search'] = SiteStartEnvironment::getRequestValue('search');
		}
		
		return $arr_filters;
	}
	
	protected function errorInput($msg) {
		
		error(getLabel('msg_missing_information').' '.$msg.'.', TROUBLE_INVALID_REQUEST, LOG_CLIENT);
	}
	
	public function getEndpointURL() {
		
		return URL_BASE.'project/'.$this->project_id.'/';
	}
	
	protected function extendEndpointDescriptionOpenAPI(&$arr_config) {
		
		$arr_project = StoreCustomProject::getProjects($this->project_id);
		$arr_types = $arr_project['types'];
		
		$arr_type_ids = array_keys($arr_types);
		
		$arr_config['info']['title'] = Labels::parseTextVariables($arr_project['project']['name']);
		$arr_config['info']['description'] = 'API description for Project "'.$arr_config['info']['title'].'"';
		

		$output_types = new CreateTypesPackage($_SESSION['USER_ID'], $this->project_id, $this->is_administrator);
		
		// Schema
		
		$output_types->setConfigCollect($arr_config, $arr_collect);
		$output_types->parseTypesOpenAPI($arr_type_ids, $_SESSION['NODEGOAT_CLEARANCE']);
		
		$arr_config['components']['schemas']['data.nodegoat_id'] = ['type' => 'string', 'title' => 'nodegoat ID', 'description' => 'Unique identifier, for URI and external use. Can also be used for object_id.'];
		$arr_config['components']['schemas']['data.object_id'] = ['type' => 'integer', 'title' => 'Object ID'];
		$arr_config['components']['schemas']['data.object_sub_id'] = ['type' => 'integer', 'title' => 'Sub-Object ID'];
		
		$arr_config['components']['schemas']['data.object_sub_date_single'] = ['type' => 'string', 'title' => 'Sub-Object single date'];
		$arr_config['components']['schemas']['data.object_sub_date_period_start'] = ['type' => 'string', 'title' => 'Sub-Object date period start'];
		$arr_config['components']['schemas']['data.object_sub_date_period_end'] = ['type' => 'string', 'title' => 'Sub-Object date period end'];
		$arr_config['components']['schemas']['data.object_sub_date_chronology'] = ['type' => 'string', 'title' => 'Sub-Object ChronoJSON'];
		$arr_config['components']['schemas']['data.object_sub_location_ref_object_id'] = ['allOf' => [['$ref' => '#/components/schemas/data.object_id']], 'title' => 'Sub-Object location Object ID', 'description' => 'ID of the Object that is used for the Sub-Object\'s location.'];
		$arr_config['components']['schemas']['data.object_sub_location_ref_object_name'] = ['type' => 'string', 'title' => 'Sub-Object location Object name', 'description' => 'Name of the Object that is used for the Sub-Object\'s location.'];
		$arr_config['components']['schemas']['data.object_sub_location_ref_type_id'] = ['allOf' => [['$ref' => '#/components/schemas/model.type_id']], 'title' => 'Sub-Object location Type ID', 'description' => 'ID of the Type that is used to lookup the Object used for the Sub-Object\'s location.'];
		$arr_config['components']['schemas']['data.object_sub_location_geometry'] = ['type' => 'string', 'title' => 'Sub-Object GeoJSON.'];
				
		$arr_config['components']['schemas']['data.type_objects.json'] = [
			'type' => 'object',
			'title' => 'Data Objects output',
			'properties' => [
				'data' => [
					'type' => 'object',
					'properties' => [
						'objects' => [
							'type' => 'array',
							'items' => [
								'oneOf' => $arr_collect['type_X_object']
							]
						]
					]
				]
			]
		];
		$arr_config['components']['schemas']['data.type_objects.jsonld'] = [
			'type' => 'object',
			'title' => 'Data Objects output in JSON-LD',
		];
		
		$arr_config['components']['schemas']['model.types'] = [
			'type' => 'object',
			'title' => 'Data Model output',
			'properties' => [
				'data' => [
					'type' => 'object',
					'properties' => [
						'types' => [
							'type' => 'array',
							'items' => ['$ref' => '#/components/schemas/model.type']
						]
					]
				]
			]
		];
		
		$arr_config['components']['schemas']['model.output'] = [
			'oneOf' => [
				['const' => 'default', 'description' => 'Use IDs, default.'],
				['const' => 'template', 'description' => 'Use names (identifiers) instead of IDs for any ID found in the data Model. A name-based data Model can be resolved automatically, i.e. when storing a new data Model.']
			],
			'title' => 'Data Model output mode',
			'description' => 'Select the ouput mode for the data Model.'
		];
		
		$arr_config['components']['schemas']['other.search'] = ['type' => 'string', 'title' => 'Search field', 'description' => 'Free text field.'];
		$arr_config['components']['schemas']['other.filter'] = ['type' => 'object', 'title' => 'Filter form', 'description' => 'Form with a full Filter description.'];
		$arr_config['components']['schemas']['other.scope'] = ['type' => 'object', 'title' => 'Scope form', 'description' => 'Form with a full Scope description.'];
		$arr_config['components']['schemas']['other.limit'] = ['type' => 'integer', 'title' => 'Limit results', 'description' => 'Limit the results to a maximum amount of Objects.'];
		$arr_config['components']['schemas']['other.offset'] = ['type' => 'integer', 'title' => 'Offset results', 'description' => 'Offset the results with a specific amount of Objects.'];
		
		$func_generate_schema = function($str_data_model, $str_action, $str_type_object) use (&$arr_config) {
		
			$arr_config['components']['schemas'][$str_data_model.'.reponse.'.$str_action] = [
				'type' => 'object',
				'title' => 'Response action output',
				'properties' => [
					'data' => [
						'type' => 'object',
						'properties' => [
							$str_type_object.'s' => [
								'type' => 'object',
								'properties' => [
									$str_action => [
										'type' => 'array',
										'items' => ['$ref' => '#/components/schemas/'.$str_data_model.'.'.$str_type_object.'_id']
									]
								]
							]
						]
					]
				]
			];
		};
		$func_generate_schema('data', 'added', 'object'); // '#/components/schemas/data.reponse.added'
		$func_generate_schema('data', 'updated', 'object'); // '#/components/schemas/data.reponse.updated'
		$func_generate_schema('data', 'deleted', 'object'); // '#/components/schemas/data.reponse.deleted'
		$func_generate_schema('model', 'added', 'type'); // '#/components/schemas/model.reponse.added'
		$func_generate_schema('model', 'updated', 'type'); // '#/components/schemas/model.reponse.updated'
		$func_generate_schema('model', 'deleted', 'type'); // '#/components/schemas/model.reponse.deleted'

		// Parameters
		
		$arr_config['components']['parameters']['data.identifier.path'] = [
			'name' => 'id',
			'in' => 'path',
			'required' => true,
			'schema' => [
				'oneOf' => [
					['$ref' => '#/components/schemas/data.nodegoat_id'],
					['$ref' => '#/components/schemas/data.object_id'],
					['type' => 'string']
				]
			],
			'description' => 'Identifier can be a nodegoat ID, Object ID, or an identifier matching an Object Definition from one of the designated Object Descriptions.'
		];
		
		$arr_config['components']['parameters']['data.identifier.query'] = [
			'name' => 'id',
			'in' => 'query',
			'required' => true,
			'schema' => [
				'oneOf' => [
					['$ref' => '#/components/schemas/data.nodegoat_id'],
					['$ref' => '#/components/schemas/data.object_id'],
					['type' => 'string']
				]
			],
			'description' => 'Identifier can be a nodegoat ID, Object ID, or an identifier matching an Object Definition from one of the designated Object Descriptions.'
		];
		
		$arr_config['components']['parameters']['data.object_id'] = [
			'name' => 'object_id',
			'in' => 'path',
			'required' => true,
			'schema' => [
				'oneOf' => [
					['$ref' => '#/components/schemas/data.nodegoat_id'],
					['$ref' => '#/components/schemas/data.object_id']
				]
			],
			'description' => 'Object ID that belongs to a certain Type.'
		];
		
		$arr_config['components']['parameters']['data.object_id.get'] = [
			'name' => 'object_id',
			'in' => 'path',
			'required' => true,
			'style' => 'simple',
			'explode' => false,
			'schema' => [
				'type' => 'array',
				'items' => [
					'anyOf' => [
						['$ref' => '#/components/schemas/data.nodegoat_id'],
						['$ref' => '#/components/schemas/data.object_id']
					]
				]
			],
			'description' => 'Object ID(s) that belong to a certain Type.'
		];
						
		$arr_config['components']['parameters']['other.search'] = [
			'name' => 'search',
			'in' => 'query',
			'required' => false,
			'schema' => ['$ref' => '#/components/schemas/other.search'],
			'description' => 'Text to quickly query the selected Type.'
		];
		
		$arr_config['components']['parameters']['other.filter'] = [
			'name' => 'filter',
			'in' => 'query',
			'required' => false,
			'content' => [
				'application/json' => [
					'schema' => [
						'oneOf' => [
							['$ref' => '#/components/schemas/other.filter'],
							...$arr_collect['filter_X_id']
						]
					]
				]
			],
			'description' => 'Filters query the Object Definitions and Sub-Objects using the Object Descriptions and Sub-Object Details from the data Model. Filter ID corresponds to one of the stored Filters, and belongs to a specific Type.'
		];
		
		$arr_config['components']['parameters']['other.scope'] = [
			'name' => 'scope',
			'in' => 'query',
			'required' => false,
			'content' => [
				'application/json' => [
					'schema' => [
						'oneOf' => [
							['$ref' => '#/components/schemas/other.scope'],
							...$arr_collect['scope_X_id']
						]
					]
				]
			],
			'description' => 'Scopes limit or expand what Object Descriptions and Sub-Object Details are selected from the data Model. Scope ID corresponds to one of the stored Scopes, and belongs to a specific Type.'
		];
		
		$arr_config['components']['parameters']['other.condition'] = [
			'name' => 'condition',
			'in' => 'query',
			'required' => false,
			'schema' => [
				'oneOf' => $arr_collect['condition_X_id']
			],
			'description' => 'Conditions apply conditional formatting and weighting rules to anything in the data. Condition ID corresponds to one of the stored Conditions, and belongs to a specific Type.'
		];
		
		$arr_config['components']['parameters']['model.type_id'] = [
			'name' => 'type_id',
			'in' => 'path',
			'required' => true,
			'schema' => [
				'oneOf' => $arr_collect['type_X_id']
			],
			'description' => 'Type ID corresponds to one of the Type data Models.'
		];
		
		$arr_config['components']['parameters']['model.type_id.get'] = [
			'name' => 'type_id',
			'in' => 'path',
			'required' => true,
			'style' => 'simple',
			'explode' => false,
			'schema' => [
				'type' => 'array',
				'items' => [
					'anyOf' => $arr_collect['type_X_id']
				]
			],
			'description' => 'Type ID corresponds to one of the Type data Models.'
		];
		
		$arr_config['components']['parameters']['model.output'] = [
			'name' => 'output',
			'in' => 'query',
			'required' => false,
			'schema' => ['$ref' => '#/components/schemas/model.output'],
			'description' => 'Select the ouput mode for the Type data Model.'
		];
		
		$arr_config['components']['parameters']['other.limit'] = [
			'name' => 'limit',
			'in' => 'query',
			'required' => false,
			'schema' => ['$ref' => '#/components/schemas/other.limit'],
			'description' => 'Limit the results to a maximum amount of Objects.'
		];
		$arr_config['components']['parameters']['other.offset'] = [
			'name' => 'offset',
			'in' => 'query',
			'required' => false,
			'schema' => ['$ref' => '#/components/schemas/other.offset'],
			'description' => 'Offset the results with a specific amount of Objects.'
		];

		$arr_config['components']['parameters']['other.analysis_id'] = [
			'name' => 'analysis_id',
			'in' => 'path',
			'required' => true,
			'schema' => [
				'type' => 'integer'
			],
			'description' => 'Analysis ID corresponds to one of the stored Analyses, and belongs to a specific Type.'
		];

		// Requests
		
		$arr_config['components']['requestBodies']['data.type_objects'] = [
			'required' => false,
			'content' => [
				'application/x-www-form-urlencoded' => [
					'schema' => [
						'type' => 'object',
						'properties' => [
							'search' => ['$ref' => '#/components/schemas/other.search'],
							'limit' => ['$ref' => '#/components/schemas/other.limit'],
							'offset' => ['$ref' => '#/components/schemas/other.offset'],
							'filter' => [
								'oneOf' => [
									['$ref' => '#/components/schemas/other.filter'],
									...$arr_collect['filter_X_id']
								],
								'description' => 'Filters query the Object Definitions and Sub-Objects using the Object Descriptions and Sub-Object Details from the data Model. Filter ID corresponds to one of the stored Filters, and belongs to a specific Type.'
							],
							'scope' => [
								'oneOf' => [
									['$ref' => '#/components/schemas/other.scope'],
									...$arr_collect['scope_X_id']
								],
								'description' => 'Scopes limit or expand what Object Descriptions and Sub-Object Details are selected from the data Model. Scope ID corresponds to one of the stored Scopes, and belongs to a specific Type.'
							],
							'condition' => [
								'oneOf' => $arr_collect['condition_X_id'],
								'description' => 'Conditions apply conditional formatting and weighting rules to anything in the data. Condition ID corresponds to one of the stored Conditions, and belongs to a specific Type.'
							]
						]
					],
					'encoding' => [
						'filter' => ['contentType' => 'application/json'],
						'scope' => ['contentType' => 'application/json']
					]
				]
			]
		];
		
		$arr_config['components']['requestBodies']['model.types'] = [
			'required' => false,
			'content' => [
				'application/x-www-form-urlencoded' => [
					'schema' => [
						'type' => 'object',
						'properties' => [
							'output' => ['$ref' => '#/components/schemas/model.output']
						]
					]
				]
			]
		];

		// Paths
		
		$arr_config['paths']['/{id}'] = [
			'get' => [
				'summary' => 'Get Objects based on an identifier.',
				'parameters' => [
					['$ref' => '#/components/parameters/data.identifier.path']
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => ['schema' => ['$ref' => '#/components/schemas/data.type_objects.json']],
							'application/ld+json' => ['schema' => ['$ref' => '#/components/schemas/data.type_objects.jsonld']]
						],
						'description' => 'List of one or more Objects that match the given identifier.'
					]
				]
			]
		];
		$arr_config['paths']['/'] = [
			'get' => [
				'summary' => 'Get Objects based on an identifier.',
				'parameters' => [
					['$ref' => '#/components/parameters/data.identifier.query']
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => ['schema' => ['$ref' => '#/components/schemas/data.type_objects.json']],
							'application/ld+json' => ['schema' => ['$ref' => '#/components/schemas/data.type_objects.jsonld']]
						],
						'description' => 'List of one or more Objects that match the given identifier.'
					]
				]
			]
		];
		
		$arr_config['paths']['/data/type/{type_id}/object/'] = [
			'get' => [
				'summary' => 'Get Objects from a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id'],
					['$ref' => '#/components/parameters/other.search'],
					['$ref' => '#/components/parameters/other.limit'],
					['$ref' => '#/components/parameters/other.offset'],
					['$ref' => '#/components/parameters/other.filter'],
					['$ref' => '#/components/parameters/other.scope'],
					['$ref' => '#/components/parameters/other.condition']
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => ['schema' => ['$ref' => '#/components/schemas/data.type_objects.json']],
							'application/ld+json' => ['schema' => ['$ref' => '#/components/schemas/data.type_objects.jsonld']]
						],
						'description' => 'List of Objects for the given Type ID and the query parameters.'
					]
				]
			],
			'post' => [
				'summary' => 'Get Objects from a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id']
				],
				'requestBody' => ['$ref' => '#/components/requestBodies/data.type_objects'],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => ['schema' => ['$ref' => '#/components/schemas/data.type_objects.json']],
							'application/ld+json' => ['schema' => ['$ref' => '#/components/schemas/data.type_objects.jsonld']]
						],
						'description' => 'List of Objects for the given Type ID and the query parameters.',
					]
				]
			],
			'put' => [
				'summary' => 'Add or update (fully overwrite) Objects from a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id']
				],
				'requestBody' => [
					'required' => true,
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'add' => [
										'type' => 'array',
										'items' => [
											'oneOf' => $arr_collect['type_X_object']
										]
									],
									'update' => [
										'type' => 'object',
										'propertyNames' => [
											'pattern' => '^([0-9]+|ng[a-zA-Z0-9]+)$',
											'description' => 'Object ID or nodegoat ID.'
										],
										'additionalProperties' => [
											'oneOf' => $arr_collect['type_X_object']
										],
										'description' => 'The Object ID has to be set as dictionary key.'
									]
								]
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => [
									'allOf' => [
										['$ref' => '#/components/schemas/data.reponse.added'],
										['$ref' => '#/components/schemas/data.reponse.updated']
									]
								]
							]
						],
						'description' => 'List of Object IDs that have been added or updated.'
					]
				]
			],
			'patch' => [
				'summary' => 'Update (parts of) Objects from a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id']
				],
				'requestBody' => [
					'required' => true,
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'propertyNames' => [
									'pattern' => '^([0-9]+|ng[a-zA-Z0-9]+)$',
									'description' => 'Object ID or nodegoat ID.'
								],
								'additionalProperties' => [
									'oneOf' => $arr_collect['type_X_object']
								],
								'description' => 'The Object ID has to be set as dictionary key.'
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => '#/components/schemas/data.reponse.updated']
							]
						],
						'description' => 'List of Object IDs that have been updated.'
					]
				]
			],
			'delete' => [
				'summary' => 'Delete Objects from a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id']
				],
				'requestBody' => [
					'required' => true,
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'propertyNames' => [
									'pattern' => '^([0-9]+|ng[a-zA-Z0-9]+)$',
									'description' => 'Object ID or nodegoat ID.'
								],
								'additionalProperties' => [
									'type' => 'boolean',
									'description' => 'Set to true to delete an Object.'
								],
								'description' => 'The Object ID has to be set as dictionary key.'
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => '#/components/schemas/data.reponse.deleted']
							]
						],
						'description' => 'List of Object IDs that have been deleted.'
					]
				]
			]
		];
		
		$arr_config['paths']['/data/type/{type_id}/object/{object_id}'] = [
			'get' => [
				'summary' => 'Get specific Object(s) from a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id'],
					['$ref' => '#/components/parameters/data.object_id.get'],
					['$ref' => '#/components/parameters/other.search'],
					['$ref' => '#/components/parameters/other.limit'],
					['$ref' => '#/components/parameters/other.offset'],
					['$ref' => '#/components/parameters/other.filter'],
					['$ref' => '#/components/parameters/other.scope'],
					['$ref' => '#/components/parameters/other.condition']
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => ['schema' => ['$ref' => '#/components/schemas/data.type_objects.json']],
							'application/ld+json' => ['schema' => ['$ref' => '#/components/schemas/data.type_objects.jsonld']]
						],
						'description' => 'List of Objects for the given Object IDs and the query parameters.'
					]
				]
			],
			'post' => [
				'summary' => 'Get specific Object(s) from a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id'],
					['$ref' => '#/components/parameters/data.object_id.get']
				],
				'requestBody' => ['$ref' => '#/components/requestBodies/data.type_objects'],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => ['schema' => ['$ref' => '#/components/schemas/data.type_objects.json']],
							'application/ld+json' => ['schema' => ['$ref' => '#/components/schemas/data.type_objects.jsonld']]
						],
						'description' => 'List of Objects for the given Object IDs and the query parameters.'
					]
				]
			],
			'put' => [
				'summary' => 'Update (fully overwrite) an Object from a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id'],
					['$ref' => '#/components/parameters/data.object_id']
				],
				'requestBody' => [
					'required' => true,
					'content' => [
						'application/json' => [
							'schema' => [
								'oneOf' => $arr_collect['type_X_object']
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => '#/components/schemas/data.reponse.updated']
							]
						],
						'description' => 'Lists the Object ID that has been updated.'
					]
				]
			],
			'patch' => [
				'summary' => 'Update (parts of) an Object from a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id'],
					['$ref' => '#/components/parameters/data.object_id']
				],
				'requestBody' => [
					'required' => true,
					'content' => [
						'application/json' => [
							'schema' => [
								'oneOf' => $arr_collect['type_X_object']
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => '#/components/schemas/data.reponse.updated']
							]
						],
						'description' => 'Lists the Object ID that has been updated.'
					]
				]
			],
			'delete' => [
				'summary' => 'Delete an Object from a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id'],
					['$ref' => '#/components/parameters/data.object_id']
				],
				'requestBody' => [
					'required' => true,
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'boolean',
								'description' => 'Set to true to delete an Object.'
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => '#/components/schemas/data.reponse.deleted']
							]
						],
						'description' => 'Lists the Object ID that has been deleted.'
					]
				]
			]
		];
		
		// Model
		
		$arr_config['paths']['/model/type'] = [
			'get' => [
				'summary' => 'Get the data Model for all Types.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.output']
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => '#/components/schemas/model.types']
							]
						],
						'description' => 'Data Model for all Types.'
					]
				]
			],
			'post' => [
				'summary' => 'Get the data Model for all Types.',
				'parameters' => [],
				'requestBody' => ['$ref' => '#/components/requestBodies/model.types'],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => '#/components/schemas/model.types']
							]
						],
						'description' => 'Data Model for all Types.'
					]
				]
			],
			'put' => [
				'summary' => 'Add or update (fully overwrite) the data Model for the listed Types.',
				'parameters' => [],
				'requestBody' => [
					'required' => true,
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'add' => [
										'type' => 'array',
										'items' => ['$ref' => '#/components/schemas/model.type']
									],
									'update' => [
										'type' => 'object',
										'additionalProperties' => ['$ref' => '#/components/schemas/model.type'],
										'description' => 'The Type ID / identifier has to be set as dictionary key.'
									]
								]
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => [
									'allOf' => [
										['$ref' => '#/components/schemas/model.reponse.added'],
										['$ref' => '#/components/schemas/model.reponse.updated']
									]
								]
							]
						],
						'description' => 'List of Type IDs that have been added or updated.'
					]
				]
			],
			'patch' => [
				'summary' => 'Update (parts of) the data Model for the listed Types.',
				'parameters' => [],
				'requestBody' => [
					'required' => true,
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'additionalProperties' => ['$ref' => '#/components/schemas/model.type'],
								'description' => 'The Type ID / identifier has to be set as dictionary key.'
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => '#/components/schemas/model.reponse.updated']
							]
						],
						'description' => 'List of Type IDs that have been updated.'
					]
				]
			],
			'delete' => [
				'summary' => 'Delete the data Model for the listed Types.',
				'parameters' => [],
				'requestBody' => [
					'required' => true,
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'additionalProperties' => [
									'type' => 'boolean',
									'description' => 'Set to true to delete a Type.'
								],
								'description' => 'The Type ID / identifier has to be set as dictionary key.'
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => '#/components/schemas/model.reponse.deleted']
							]
						],
						'description' => 'List of Type IDs that have been deleted.'
					]
				]
			]
		];
		
		$arr_config['paths']['/model/type/{type_id}'] = [
			'get' => [
				'summary' => 'Get the data Model for a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id.get'],
					['$ref' => '#/components/parameters/model.output']
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => '#/components/schemas/model.types']
							]
						],
						'description' => 'Data Model for the specified Type(s).'
					]
				]
			],
			'post' => [
				'summary' => 'Get the data Model for a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id.get']
				],
				'requestBody' => ['$ref' => '#/components/requestBodies/model.types'],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => '#/components/schemas/model.types']
							]
						],
						'description' => 'Data Model for the specified Type(s).'
					]
				]
			],
			'put' => [
				'summary' => 'Update (fully overwrite) the data Model for a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id']
				],
				'requestBody' => [
					'required' => true,
					'content' => [
						'application/json' => [
							'schema' => ['$ref' => '#/components/schemas/model.type']
						]
					]
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => '#/components/schemas/model.reponse.updated']
							]
						],
						'description' => 'Lists the Type ID that has been updated.'
					]
				]
			],
			'patch' => [
				'summary' => 'Update (parts of) the data Model for a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id']
				],
				'requestBody' => [
					'required' => true,
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => ['$ref' => '#/components/schemas/model.type']
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => '#/components/schemas/model.reponse.updated']
							]
						],
						'description' => 'Lists the Type ID that has been updated.'
					]
				]
			],
			'delete' => [
				'summary' => 'Delete the data Model for a Type.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id']
				],
				'requestBody' => [
					'required' => true,
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'boolean',
								'description' => 'Set to true to delete a Type.'
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => '#/components/schemas/model.reponse.deleted']
							]
						],
						'description' => 'Lists the Type ID that has been deleted.'
					]
				]
			]
		];
		
		// Graph
		
		$arr_config['paths']['/graph/type/{type_id}/analysis/{analysis_id}'] = [
			'get' => [
				'summary' => 'Get a list with all edges, their related nodes, weight, and time from a stored Analysis.',
				'parameters' => [
					['$ref' => '#/components/parameters/model.type_id'],
					['$ref' => '#/components/parameters/other.analysis_id'],
					['$ref' => '#/components/parameters/other.condition']
				],
				'responses' => [
					'200' => [
						'content' => [
							'text/csv' => [
								'schema' => ['type' => 'string']
							]
						],
						'description' => 'Edge list including weight and time in CSV-format.'
					]
				]
			]
		];
	}
}
