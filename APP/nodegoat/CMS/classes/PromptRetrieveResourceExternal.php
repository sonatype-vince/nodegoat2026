<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class PromptRetrieveResourceExternal {
	
	
	protected $arr_project = null;
	
	protected $s_str_prompt = null; // Reference
	protected $s_str_filter_vector = null; // Reference
	protected $s_str_data = null; // Reference
	
	protected $arr_type_map = null;
	protected $arr_type_selection = null;
	protected $num_type_limit = 20;
	protected $num_type_distance_test = 0.8;
	protected $external_source = null;
	protected $str_source_vector_heading = null;
	protected $arr_source_filter = null;
	protected $type_id = null;
	protected $arr_type_filter_map = null;
	protected $arr_type_filter_additional = null;
	protected $arr_type_project_filter = null;
	protected $arr_type_endpoint_input = null;
	protected $do_type_include_model = true;
	protected $external_retrieve = null;
	protected $str_retrieve_result_heading = null;
	protected $arr_retrieve_filter = null;
	
	protected $arr_type_columns_score = null;
	protected $str_instruction = null;
	protected $str_data = null;
	protected $str_result = null;
	protected $num_retrieve_timeout = 300;
	protected $do_debug = false;
	
    public function __construct($project_id) {
		
		
		$this->arr_project = StoreCustomProject::getProjects($project_id);
    }
    
    public function runPrompt($str_prompt) {

		$this->s_str_prompt = $str_prompt;
		
		return $this->run();
	}
    
    protected function run() {
		
		if ($this->s_str_prompt === null || $this->external_source === null || $this->external_retrieve === null || $this->type_id === null || $this->arr_type_map === null) {
			error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
		}
		
		$this->external_source->setFilter($this->arr_source_filter, true); // Holds reference s_str_prompt
		
		try {
			
			$this->external_source->request();
		} catch (RealTroubleThrown $e) {
		
			if ($e->getTroubleSuppress() == LOG_SYSTEM) {
				throw($e);
			}
			
			message($e->getTroubleMessage(), 'ATTENTION', LOG_CLIENT, null, null, 10000, $e);
		}
		
		if (!$this->external_source->hasResult()) {
			
			Labels::setVariable('what', getLabel('lbl_resource').' <strong>'.$this->external_source->getConfigurationOption('name').'</strong>');
			error(getLabel('msg_action_no_results'), TROUBLE_ERROR, LOG_CLIENT);
		}
		
		$arr_results = $this->external_source->getResultValues();		
		$this->s_str_filter_vector = $arr_results[0][$this->str_source_vector_heading]; // No group: grouped to 0
		
		if ($this->do_debug) {
			message('DEBUG: generate vector.', 'RETRIEVAL', LOG_SYSTEM, $this->s_str_filter_vector);
		}
		
		$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_SET_EXTERNAL);
		
		if ($this->arr_type_filter_map) {
			$this->generateTypeFilterByMap($filter);
		} else {
			$this->generateTypeFilterByEndpoint($filter);
		}

		$filter->setSelection($this->arr_type_selection);
		
		$arr_objects_set = $filter->init();
		$num_count = 0;
		
		if (!$arr_objects_set) {
			
			Labels::setVariable('what', getLabel('lbl_filter'));
			error(getLabel('msg_action_no_results'), TROUBLE_ERROR, LOG_CLIENT);
		}
		
		$arr_type_set = StoreType::getTypeSet($this->type_id);
		$arr_type_set_flat = StoreType::getTypeSetFlatMap($this->type_id, ['object_id' => true, 'object_name' => true, 'references' => true, 'reversals' => true]);
		$str_type_name = Labels::parseTextVariables($arr_type_set['type']['name']);

		$this->str_instruction = 'Question: '.$this->s_str_prompt.EOL_1100CC.EOL_1100CC; // Start with original prompt
		
		$this->str_instruction .= 'Instruction: Use the below sources to answer the above question. If the answer cannot be found using these sources, write "I could not find an answer.".'.EOL_1100CC.EOL_1100CC; // Append source instruction
		
		$this->str_data = '';
		$str_separator = '"""';

		foreach ($arr_objects_set as $use_object_id => $arr_object_set) {
			
			if ($this->do_debug && $this->arr_type_columns_score !== null) {
				
				foreach ($this->arr_type_columns_score as $str_column_name) {
					$this->str_data .= 'DEBUG-score: '.$arr_object_set['object'][$str_column_name].EOL_1100CC;
				}
			}

			$arr_object_set = GenerateTypeObjects::getTypeObjectValuesByFlatMap($this->type_id, $arr_object_set, $this->arr_type_map);
			
			$this->str_data .= 'Source text:'.EOL_1100CC.$str_separator.EOL_1100CC;
			
			foreach ($arr_object_set as $str_model_identifier => $value) {
				
				if (!$value) {
					continue;
				}
				
				if ($this->do_type_include_model) {
					
					if ($str_model_identifier == 'object-name' || $str_model_identifier == 'object-name_plain') {
						
						//$this->str_data .= $str_type_name.' '.getLabel('lbl_name').':'.EOL_1100CC;
						$this->str_data .= getLabel('lbl_name').':'.EOL_1100CC;
					} else {
						
						$this->str_data .= Labels::parseTextVariables($arr_type_set_flat[$str_model_identifier]['name']).':'.EOL_1100CC;
					}
				}

				if (is_array($value)) {
					$this->str_data .= implode(' ', $value).EOL_1100CC;
				} else {
					$this->str_data .= $value.EOL_1100CC;
				}
			}
			
			$this->str_data .= $str_separator.EOL_1100CC.EOL_1100CC;
			
			$num_count++;
			
			if ($num_count == $this->num_type_limit) {
				break;
			}
		}
		
		$this->str_data = FormatTypeObjects::clearObjectDefinitionText($this->str_data, FormatTypeObjects::TEXT_TAG_OBJECT, true);
		
		Response::holdFormat(true);
		Response::setFormat(Response::OUTPUT_TEXT);
		
		$this->str_data = Response::parse($this->str_data);
		
		Response::holdFormat();
		
		Labels::setVariable('count', $num_count);
		Labels::setVariable('type', $str_type_name);
		status(getLabel('msg_retrieval_objects_found'));
		
		$this->s_str_data = $this->str_instruction.$this->str_data;
		
		if ($this->do_debug) {
			message('DEBUG: retrieve data.', 'RETRIEVAL', LOG_SYSTEM, $this->str_instruction.$this->s_str_data);
		}

		$this->external_retrieve->setFilter($this->arr_retrieve_filter, true); // Holds reference s_str_data
		$this->external_retrieve->setTimeout($this->num_retrieve_timeout, null, false);
		
		try {
			
			$this->external_retrieve->request();
		} catch (RealTroubleThrown $e) {
		
			if ($e->getTroubleSuppress() == LOG_SYSTEM) {
				throw($e);
			}
			
			message($e->getTroubleMessage(), 'ATTENTION', LOG_CLIENT, null, null, 10000, $e);
		}
		
		if (!$this->external_retrieve->hasResult()) {
			
			Labels::setVariable('what', getLabel('lbl_resource').' <strong>'.$this->external_retrieve->getConfigurationOption('name').'</strong>');
			error(getLabel('msg_action_no_results'), TROUBLE_ERROR, LOG_CLIENT);
		}
		
		$arr_results = $this->external_retrieve->getResultValues();
		$this->str_result = $arr_results[0][$this->str_retrieve_result_heading];
		
		if ($this->do_debug) {
			message('DEBUG: result.', 'RETRIEVAL', LOG_SYSTEM, $this->str_result);
		}
		
		return $this->str_result;
	}
	
	public function getDataRetrieval() {
		
		return $this->str_data;
	}
	
	protected function generateTypeFilterByMap($filter) {
		
		$arr_type_set = StoreType::getTypeSet($this->type_id);
		
		$arr_columns = [];
		$arr_ordering = [];
		
		$arr_filter = $this->arr_type_filter_map; // Holds reference s_str_filter_vector
		
		if (isset($arr_filter['object_definitions'])) {

			foreach ($arr_filter['object_definitions'] as $object_description_id => $arr_filters_vector) {
				
				foreach ($arr_filters_vector as $arr_filter_vector) {
					
					$str_value_type = $arr_type_set['object_descriptions'][$object_description_id]['object_description_value_type'];
					
					if ($str_value_type != 'vector') {
						error(getLabel('msg_missing_information'));
					}
					
					$str_sql_field = 'nodegoat_to_vector.'.StoreType::getValueTypeValue($str_value_type, 'search');
					
					$arr_secondary = [];
					FormatTypeObjects::formatToSQLValue($str_value_type, $arr_filter_vector['value'], null, $arr_secondary);
					$str_sql_test = current($arr_secondary);
					$str_sql_test = FormatTypeObjects::formatToSQLEscape($str_value_type, $str_sql_test, true);

					if (DB::ENGINE_IS_MYSQL) {
						$str_sql_field = 'VEC_DISTANCE_COSINE('.$str_sql_field.', '.$str_sql_test.')';
					} else {
						$str_sql_field = '('.$str_sql_field.' <=> '.$str_sql_test.')';
					}
					
					$arr_columns['filter_'.$object_description_id] = "
						(SELECT ".$str_sql_field." AS num_distance
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($str_value_type, 'search')." nodegoat_to_vector
							WHERE nodegoat_to_vector.object_id = ".GenerateTypeObjects::sqlTableName().".id AND nodegoat_to_vector.object_description_id = ".(int)$object_description_id." AND ".$filter->generateVersion('record_search', 'nodegoat_to_vector', $str_value_type)."
							ORDER BY num_distance ASC
							LIMIT 1)
					";
					
					$arr_ordering['filter_'.$object_description_id] = 'asc';
				}
			}
		}
		
		if (isset($arr_filter['object_subs'])) {
			
			foreach ($arr_filter['object_subs'] as $object_sub_details_id => $arr_object_sub_details) {
				foreach ($arr_object_sub_details['object_sub_definitions'] as $object_sub_description_id => $arr_filters_vector) {
				
					foreach ($arr_filters_vector as $arr_filter_vector) {

						$str_value_type = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_value_type'];
					
						if ($str_value_type != 'vector') {
							error(getLabel('msg_missing_information'));
						}
						
						$str_sql_field = 'nodegoat_tos_vector.'.StoreType::getValueTypeValue($str_value_type, 'search');
						
						$arr_secondary = [];
						FormatTypeObjects::formatToSQLValue($str_value_type, $arr_filter_vector['value'], null, $arr_secondary);
						$str_sql_test = current($arr_secondary);
						$str_sql_test = FormatTypeObjects::formatToSQLEscape($str_value_type, $str_sql_test, true);

						if (DB::ENGINE_IS_MYSQL) {
							$str_sql_field = 'VEC_DISTANCE_COSINE('.$str_sql_field.', '.$str_sql_test.')';
						} else {
							$str_sql_field = '('.$str_sql_field.' <=> '.$str_sql_test.')';
						}
						
						$arr_columns['filter_subs_'.$object_sub_description_id] = "
							(SELECT ".$str_sql_field." AS num_distance
									FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = ".GenerateTypeObjects::sqlTableName().".id AND nodegoat_tos.object_sub_details_id = ".(int)$object_sub_details_id." AND ".$filter->generateVersion('object_sub', 'nodegoat_tos').")
									JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($str_value_type, 'search')." nodegoat_tos_vector ON (nodegoat_tos_vector.object_sub_id = nodegoat_tos.id AND nodegoat_tos_vector.object_sub_description_id = ".(int)$object_sub_description_id." AND ".$filter->generateVersion('record_search', 'nodegoat_tos_vector', $str_value_type).")
								ORDER BY num_distance ASC
								LIMIT 1)
						";
						
						$arr_ordering['filter_subs_'.$object_sub_description_id] = 'asc';
					}
				}
			}
		}
		
		if (!$arr_filter) {
			error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
		}

		$filter->addColumns($arr_columns);
		$filter->setOrder($arr_ordering);
		
		$this->arr_type_columns_score = array_keys($arr_columns);
		
		$arr_filter = ['object_filter' => $arr_filter];
		$filter->setFilter($arr_filter);
		
		$filter->setLimit($this->num_type_limit);
		
		if ($this->arr_type_filter_additional) {
			$filter->setFilter($this->arr_type_filter_additional);
		}
	}
	
	protected function generateTypeFilterByEndpoint($filter) {
	
		$arr_filter = $this->arr_type_endpoint_input; // Holds reference s_str_filter_vector
		
		try {
			$arr_description = ParseTypeFeatures::parseDescriptionTypeFilter($this->type_id, $this->arr_type_project_filter, $arr_filter); // arr_filter will be replaced with the adjusted filter form
		} catch (Exception $e) {

			if ($e->getTroubleSuppress() != LOG_SYSTEM) {
				error($e->getTroubleMessage(), TROUBLE_ERROR, LOG_CLIENT);
			}

			throw($e);
		}
		
		if (!$arr_filter) {
			error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
		}

		$arr_filter = FilterTypeObjects::convertFilterInput($arr_filter);
		$filter->setFilter($arr_filter);
		
		$filter->setLimit(200); // Currently no ranked result support, get more values
		
		if ($this->arr_type_filter_additional) {
			$filter->setFilter($this->arr_type_filter_additional);
		}
	}

	public function setResourceSource($source_id, $str_query_heading, $str_response_heading, $arr_query_headings_value = []) {
		
		if (!$source_id || !$str_query_heading) {
			error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
		}
		
		$arr_resource = StoreResourceExternal::getResources($source_id);
		
		$this->external_source = new ResourceExternal($arr_resource);
		$arr_response_values = $this->external_source->getResponseValues(true);
		
		/*if (arrHasKeysRecursive('conversion_id', $arr_response_values, true)) {
			$socket = $this->getConversionSocket();
		}
		$this->external_source->setResultConversionSocket($socket);*/
		
		if (!$arr_response_values[$str_response_heading]) {
			error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
		}
		
		$this->str_source_vector_heading = $str_response_heading;
		
		$this->arr_source_filter = [];
		
		foreach ($arr_query_headings_value as $str_heading => $value) {
			$this->arr_source_filter[$str_heading] = $value;
		}
		
		$this->arr_source_filter[$str_query_heading] = '';
		
		$this->s_str_prompt =& $this->arr_source_filter[$str_query_heading];
	}
	
	public function setResourceRetrieval($retrieve_id, $str_query_heading, $str_response_heading, $arr_query_headings_value = []) {
		
		if (!$retrieve_id || !$str_query_heading) {
			error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
		}
		
		$arr_resource = StoreResourceExternal::getResources($retrieve_id);
		
		$this->external_retrieve = new ResourceExternal($arr_resource);
		$arr_response_values = $this->external_retrieve->getResponseValues(true);
		
		/*if (arrHasKeysRecursive('conversion_id', $arr_response_values, true)) {
			$socket = $this->getConversionSocket();
		}
		$this->external_retrieve->setResultConversionSocket($socket);*/
		
		if (!$arr_response_values[$str_response_heading]) {
			error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
		}
		
		$this->str_retrieve_result_heading = $str_response_heading;
		
		$this->arr_retrieve_filter = [];
		
		foreach ($arr_query_headings_value as $str_heading => $value) {
			$this->arr_retrieve_filter[$str_heading] = $value;
		}
		
		$this->arr_retrieve_filter[$str_query_heading] = '';
		
		$this->s_str_data =& $this->arr_retrieve_filter[$str_query_heading];
	}
	
	public function setTypeFilterMap($type_id, $arr_map) {
		
		if (!$type_id || !$arr_map) {
			error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
		}
		
		$this->type_id = (int)$type_id;
		
		$this->s_str_filter_vector = '';
		
		$this->arr_type_filter_map = $this->parseTypeElementIDFilter($this->type_id, $arr_map, $this->s_str_filter_vector);
	}
	
	public function setTypeFilterEndpoint($type_id, $filter_id, $str_vector_parameter, $arr_filter_endpoints = []) {
		
		if (!$type_id || !$filter_id || !$str_vector_parameter) {
			error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
		}
		
		$this->type_id = (int)$type_id;

		$arr_use_project_ids = array_keys($this->arr_project['use_projects']);
		$this->arr_type_project_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($this->arr_project['project']['id'], false, $this->type_id, (int)$filter_id, true, $arr_use_project_ids);
		
		if (!$this->arr_type_project_filter) {
			error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
		}
		
		$this->arr_type_endpoint_input = [];

		foreach ($arr_filter_endpoints as $str_parameter => $value) {
			$this->arr_type_endpoint_input[$str_parameter] = $value;
		}
		
		$this->arr_type_endpoint_input[$str_vector_parameter] = '';
		
		$this->s_str_filter_vector =& $this->arr_type_endpoint_input[$str_vector_parameter];
	}
	
	public function setTypeFilterAdditional($arr_filter_additional) {
		
		$arr_filter_additional = ($arr_filter_additional ? FilterTypeObjects::convertFilterInput($arr_filter_additional) : null);
		
		$this->arr_type_filter_additional = $arr_filter_additional;
	}
	
	public function setTypeSelection($arr_map, $num_limit = 20) {
		
		$arr_map = array_combine($arr_map, $arr_map);
		
		$this->arr_type_map = $arr_map;
		$this->arr_type_selection = StoreType::getTypeSelectionByFlatMap($this->type_id, $arr_map);
		
		$this->num_type_limit = $num_limit;
	}
	
	protected function parseTypeElementIDFilter($type_id, $element_id, &$value, $arr_filter = []) {

		$arr_type_set = StoreType::getTypeSet($type_id);
		
		$arr_element = explode('-', $element_id);

		if ($arr_element[0] == 'object_description') {
				
			$object_description_id = $arr_element[1];
			
			$arr_filter['object_definitions'][$object_description_id][] = ['distance_operator' => 'cosine', 'equality' => '<', 'distance' => $this->num_type_distance_test, 'value' => &$value];
		} else if ($arr_element[0] == 'object_sub_details') {

			$object_sub_details_id = $arr_element[1];
			$str_element = $arr_element[2];

			//arr_filter['object_subs'][$object_sub_details_id]['object_sub'][$object_sub_details_id]['object_sub_details_id'] = $object_sub_details_id;
			
			switch ($str_element) {
				case 'object_sub_description':
				
					$object_sub_description_id = $arr_element[3];

					$arr_filter['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id][] = ['distance_operator' => 'cosine', 'equality' => '<', 'distance' => $this->num_type_distance_test, 'value' => &$value];
					
					break;
			}
		}
			
		return $arr_filter;	
	}
	
	public function debug($do_debug = true) {
		
		$this->do_debug = (bool)$do_debug;
	}
}
