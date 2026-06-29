<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ResourceExternal {
	
	
	protected $arr_resource = [];
	protected $identifier = false;
	protected $view = false;
	
	const PROTOCOL_API = 'api';
	const PROTOCOL_SPARQL = 'sparql';
	const PROTOCOL_STATIC = 'static';
	
	const METHOD_GET = 0;
	const METHOD_POST = 1;
	
	const PARSE_DEFAULT = 'json'; // JSON
	const PARSE_TEXT = 'text';
	const PARSE_XML = 'xml';
	const PARSE_YAML = 'yaml';
	
	protected $num_requests = 0;
	protected $arr_requests = [];
	protected $str_result = '';
	protected $arr_result_values = null;
	protected $mode_result_parse = self::PARSE_DEFAULT;
	
	protected $arr_filter = null;
	protected $arr_limit = [0, 100];
	protected $arr_order = [];
	
	protected $str_uri_template_begin = null;
	protected $str_uri_template_end = null;

	protected $socket_conversion = null;
	
	protected $num_timeout = 45; // Seconds
	protected $do_timeout_retry = true;
	protected $num_timeout_conversion = 30; // Seconds
	protected $do_iteration = false;
	protected $do_debug = false;
	
	protected $arr_cache_request_values = [];
	
	const VIEW_PLAIN = 1;
	
	const TAGCODE_PARSE_QUERY_OPEN = '\[query=([\w]+)\]';
	const TAGCODE_PARSE_QUERY_CAPTURE = '((?>(?:(?>[^\[]+)|\[(?!\/query\]))*))';
	const TAGCODE_PARSE_QUERY_CLOSE = '\[\/query\]';
	const TAGCODE_PARSE_QUERY = self::TAGCODE_PARSE_QUERY_OPEN.self::TAGCODE_PARSE_QUERY_CAPTURE.self::TAGCODE_PARSE_QUERY_CLOSE; // \[query=([\w]+)\](.+?)\[\/query\]
	const TAGCODE_PARSE_VARIABLE_OPEN = '\[variable(?:=([\w]+)(?:\:([\w\-;,]+))?)?\]';
	const TAGCODE_PARSE_VARIABLE_CAPTURE = '((?>(?:(?>[^\[]+)|\[(?!\/variable\]))*))';
	const TAGCODE_PARSE_VARIABLE_CLOSE = '\[\/variable\]';
	const TAGCODE_PARSE_VARIABLE = self::TAGCODE_PARSE_VARIABLE_OPEN.self::TAGCODE_PARSE_VARIABLE_CAPTURE.self::TAGCODE_PARSE_VARIABLE_CLOSE; // \[variable(?:=([\w]+)(?:\:([\w\-;,]+))?)?\](.+?)\[\/variable\]
	const TAGCODE_PARSE_VALUE = '\[parse=([\w\-;,]+)\]((?:(?>[^\[]+)|(?R)|\[(?!\/?parse\]))*)\[\/parse\]';
	const TAGCODE_PARSE_LIMIT = '\[\[limit(?:=([\d]*))?\]\]';
	const TAGCODE_PARSE_OFFSET = '\[\[offset(?:=([\d]*))?\]\]';
		
	public function __construct($arr_resource, $do_iteration = false) {
		
		
		$this->arr_resource = $arr_resource;
		$this->do_iteration = (bool)$do_iteration; // Mass-operation/iteration
		
		if ($this->do_iteration) {
			$this->do_timeout_retry = false; // By default, only follow-up on timeout doing a single call
		}
	}
	
	public function request($str_identifier = null) {

		// [query=name]...[/query]
		// [variable(=name(:type))]...[/variable]
		
		$this->num_requests++;
		$this->str_uri_template_begin = null;
		$this->str_uri_template_end = null;
		
		$str_query = $this->arr_resource['query'];
		
		if ($this->arr_filter !== null) {
			
			$str_uri_template = $this->arr_resource['response_uri_template'];
			
			if ($str_uri_template) {
			
				$pos_identifier = strpos($str_uri_template, '[[identifier]]');
				if ($pos_identifier !== false) {
					$this->str_uri_template_begin = substr($str_uri_template, 0, $pos_identifier);
					$this->str_uri_template_end = substr($str_uri_template, $pos_identifier + 14);
				} else {
					$this->str_uri_template_begin = $str_uri_template;
				}
			}

			$str_query = preg_replace_callback(
				'/'.static::TAGCODE_PARSE_QUERY.'/s',
				function($arr_matches_query) {
					
					$str_name_query = $arr_matches_query[1];
					$arr_filter_variables = ($this->arr_filter[$str_name_query] ?? null);
					
					if (!$arr_filter_variables) {
						return '';
					}

					$str_query_variables = $arr_matches_query[2];
					$num_count = 1;
					
					$str_query_variables = preg_replace_callback(
						'/'.static::TAGCODE_PARSE_VARIABLE.'/s',
						function($arr_matches_variable) use ($arr_filter_variables, &$num_count) {
							
							$str_name_variable = $arr_matches_variable[1];
							$str_type_variable = $arr_matches_variable[2];
							
							if (!$str_name_variable) {
								
								$str_name_variable = $num_count;
								$num_count++;
							}

							$str_value = '';
							
							if (!is_array($arr_filter_variables)) {
								
								$str_value = $arr_filter_variables;
							} else if (isset($arr_filter_variables[$str_name_variable])) {
								
								$arr_filter_variable = $arr_filter_variables[$str_name_variable];
								
								if (is_array($arr_filter_variable)) {
																
									foreach ($arr_filter_variable as $key => $value) {
										
										$str_value_check = $value;
										
										if ($this->str_uri_template_begin) { // Try to find a match for the current URI template, in a possible list with other identifiers
											
											if (strpos($str_value_check, $this->str_uri_template_begin) !== false && (!$this->str_uri_template_end || strpos($str_value_check, $this->str_uri_template_end) !== false)) {
												
												$str_value = $str_value_check;
												break;
											} else if (!$str_value) {
												
												$str_value = $str_value_check;
											}
										} else {
											
											$str_value = $str_value_check;
											break;
										}
									}
								} else {
									
									$str_value = $arr_filter_variable;
								}
							}
							
							$str_value = trim($str_value);
							
							if ($str_value === '') {
								return $str_value;
							}
								
							if ($str_type_variable == 'uri-identifier' && $this->str_uri_template_begin) {
								
								if (strpos($str_value, $this->str_uri_template_begin) !== false) {
									
									$str_value = substr($str_value, strlen($this->str_uri_template_begin));
									
									if ($this->str_uri_template_end) {
										
										$num_pos_end = strrpos($str_value, $this->str_uri_template_end);
										
										if ($num_pos_end !== false) {
											$str_value = substr($str_value, 0, $num_pos_end);
										}
									}
								}
							}
							
							if ($str_type_variable == 'strip-object') {
								$str_value = FormatTypeObjects::clearObjectDefinitionText($str_value, FormatTypeObjects::TEXT_TAG_OBJECT, true);
							}
							
							if ($this->arr_resource['protocol'] == static::PROTOCOL_API) { // Encode only the values when relevant for the protocol
								$str_value = $this->prepareRequestValue($str_value, $str_type_variable);
							}

							return $str_value;
						},
						$str_query_variables
					);
					
					return $str_query_variables;
				},
				$str_query
			);
		} else {
			
			$str_query = preg_replace('/'.static::TAGCODE_PARSE_QUERY_OPEN.'/s', '', $str_query);
			$str_query = preg_replace('/'.static::TAGCODE_PARSE_QUERY_CLOSE.'/s', '', $str_query);
			
			if ($this->arr_resource['protocol'] == static::PROTOCOL_API) { // Encode only the values when relevant for the protocol
				
				$str_query = preg_replace_callback(
					'/'.static::TAGCODE_PARSE_VARIABLE.'/s',
					function($arr_matches_variable) {
						
						return $this->prepareRequestValue($arr_matches_variable[3], $arr_matches_variable[2]);
					},
					$str_query
				);
			} else {
				
				$str_query = preg_replace('/'.static::TAGCODE_PARSE_VARIABLE_OPEN.'/s', '', $str_query);
				$str_query = preg_replace('/'.static::TAGCODE_PARSE_VARIABLE_CLOSE.'/s', '', $str_query);
			}
		}
		
		if ($this->arr_resource['protocol'] == static::PROTOCOL_API) { // Encode only the values when relevant for the protocol, supports nested tags!
			
			$str_query = preg_replace_callback('/'.static::TAGCODE_PARSE_VALUE.'/s', [$this, 'parseTagcodeRequestValue'], $str_query);
		}
		
		if ($this->arr_resource['protocol'] == static::PROTOCOL_SPARQL) {
			
			$num_pos = stripos($str_query, 'SELECT');
			$str_before = trim(substr($str_query, 0, $num_pos));
			$str_after = trim(substr($str_query, $num_pos + strlen('SELECT')));
			
			$str_query = ($str_before ? $str_before.' ' : '').'SELECT';
			
			if (stripos($str_after, 'DISTINCT') !== 0) {
				$str_query .= ' DISTINCT';
			}
			
			$str_query .= ' '.$str_after;
		} else {
			
			$str_query = trim($str_query);
		}
		
		Labels::setVariable('resource_name', $this->arr_resource['name']);
		Labels::setVariable('seconds', $this->num_timeout);
		if ($this->do_iteration) {
			Labels::setVariable('request_info', '#'.$this->num_requests);
			$str_label = getLabel('msg_external_resource_running_iterate');
		} else {
			Labels::setVariable('request_info', ($this->arr_limit[0] + 1).' - '.($this->arr_limit[0] + $this->arr_limit[1]));
			$str_label = getLabel('msg_external_resource_running');
		}
		status($str_label, null, null, [
			'identifier' => 'r_e_'.$this->arr_resource['id'], 'duration' => 1000, 'persist' => true,
			'clear' => ['identifier' => 'r_e_'.$this->arr_resource['id'], 'timeout' => 0]
		]);
		
		$arr_request = $this->prepareRequest($str_query, ['offset' => $this->arr_limit[0], 'limit' => $this->arr_limit[1]]);
		$arr_request_settings = [
			'timeout' => $this->num_timeout,
			'headers' => (($this->arr_resource['url_headers'] ?: []) + ['Accept' => 'application/json, application/ld+json, */*;q=0.1']),
			'secrets' => Settings::get('nodegoat_external_resource_secrets', null, [$arr_request['url']]),
			'header_callback' => function($str_header) use ($str_identifier) {
				
				if (stripos($str_header, 'content-type:') !== false) {
					$this->arr_requests[$str_identifier]['settings']['response']['content_type'] = trim(str_replace('content-type:', '', strtolower($str_header)));
				}
			},
			'post' => $arr_request['body'],
			'response' => ['content_type' => '']
		];
		
		$data = new FileGet($arr_request['url'], $arr_request_settings, ($this->do_iteration ? FileGet::ASYNC_CUSTOM : FileGet::ASYNC_USER));
		
		$this->arr_requests[$str_identifier] = ['data' => $data, 'request' => $arr_request, 'settings' => &$arr_request_settings, 'query' => $str_query];

		if ($this->do_iteration) { // Do queue
			return;
		}
		
		return $this->getRequestResult($str_identifier);
	}
	
	public function getRequestResult($str_identifier = null) {
		
		$this->str_result = '';
		$this->arr_result_values = null;
		
		if (!isset($this->arr_requests[$str_identifier])) {
			error('Missing request.');
		}
		
		['data' => $data, 'request' => $arr_request, 'settings' => &$arr_request_settings, 'query' => $str_query] = $this->arr_requests[$str_identifier];

		$str_result = $data->get();
		
		if ($str_result === null) { // If asynchronous, check state
			return null; // Not ready yet
		}
		
		unset($this->arr_requests[$str_identifier]);

		if ($this->do_debug) {
			message('DEBUG: request.', 'EXTERNAL RESOURCE', LOG_SYSTEM, 'REQUEST:'.EOL_1100CC.EOL_1100CC.print_r($data->getRequest(), true).EOL_1100CC.'BODY:'.EOL_1100CC.EOL_1100CC.$arr_request['body']);
		}

		if (!$str_result) {
			
			$str_error = $data->getError();

			if ($str_error == 'timeout' && $this->do_timeout_retry) {
				
				status(getLabel('msg_external_resource_timeout_retry'), null, null, 5000);
				
				$str_msg_found_records = getLabel('msg_external_resource_timeout_found_records');
				
				$num_timer = time();
				$num_limit = 1;
				
				while ((time() - $num_timer) < $this->num_timeout) {

					$arr_request = $this->prepareRequest($str_query, ['offset' => $this->arr_limit[0], 'limit' => $num_limit]);
					$arr_request_settings['timeout'] = ($this->num_timeout / 2);
					
					$data = new FileGet($arr_request['url'], $arr_request_settings, FileGet::ASYNC_USER);
					$str_result_test = $data->get();
					
					if (!$str_result_test) {
						
						if (!$str_result && $data->getError() == 'timeout') {
							error(getLabel('msg_external_resource_timeout_stop'));
						}
						
						break;
					}
					
					usleep(500000); // Do not pressure, 0.5 seconds
					
					Labels::setVariable('count', $num_limit);
					status(Labels::parseTextVariables($str_msg_found_records), null, null, 2000);
					
					$str_result = $str_result_test;
					$num_limit++;
				}
			} else if ($str_error) {
				
				if ($str_error == 'timeout') {
					status(getLabel('msg_external_resource_timeout'), null, null, 5000);
				}
				
				Labels::setVariable('response', $data->getErrorResponse());
				Labels::setVariable('debug_url', $arr_request['url']);
				Labels::setVariable('debug_query', $arr_request['query']);
				
				error(getLabel('msg_external_resource_error'));
			}
		}
		
		if ($str_result) {
			
			$this->parseContentType($arr_request_settings['response']['content_type'], $arr_request['url']);
			
			if ($this->mode_result_parse == static::PARSE_TEXT) {
				
				$str_result = value2JSON(['text' => $str_result]);
			} else if ($this->mode_result_parse == static::PARSE_XML) {
				
				$parse = new ParseXML2JSON($str_result);
				$parse->setMode(ParseXML2JSON::MODE_COMPACT);
				$str_result = $parse->get();
			} else if ($this->mode_result_parse == static::PARSE_YAML) {
				
				$str_result = YAML2Value($str_result);
				$str_result = value2JSON($str_result);
			}

			$this->str_result = $str_result;
		}

		return (bool)$this->str_result;
	}
	
	protected function parseTagcodeRequestValue($arr_match) {
		
		$str_text = preg_replace_callback('/'.static::TAGCODE_PARSE_VALUE.'/s', [$this, 'parseTagcodeRequestValue'], $arr_match[2]);

		return $this->prepareRequestValue($str_text, $arr_match[1], true);
	}
	
	protected function prepareRequestValue($str_value, $str_type_variable = null, $do_cache = false) {
		
		// Parse single values
		
		$arr_type_variables = [];
		$str_cache_identifier = null;
		
		if ($str_type_variable) {
			
			if ($do_cache) {
				
				$str_cache_identifier = $str_type_variable.$str_value;
				
				if (isset($this->arr_cache_request_values[$str_cache_identifier])) {
					return $this->arr_cache_request_values[$str_cache_identifier];
				}
			}
			
			if (strpos($str_type_variable, ';') !== false) {
				
				foreach (explode(';', $str_type_variable) as $v) {
					
					$v = explode(',', $v);
					$arr_type_variables[$v[0]] = ($v[1] ?? true);
				}
			} else {
				
				$arr_type_variables = explode(',', $str_type_variable);
				$arr_type_variables = [$arr_type_variables[0] => ($arr_type_variables[1] ?? true)];
			}
		}
		
		if (isset($arr_type_variables['data-url']) || isset($arr_type_variables['data-base64'])) {
			
			try {
				
				$media = new EnucleateMedia($str_value, DIR_HOME_TYPE_OBJECT_MEDIA);
				
				if (isset($arr_type_variables['resize'])) {
					
					$num_size = (is_numeric($arr_type_variables['resize']) ? $arr_type_variables['resize'] : 1000);
					$media->setSizing($num_size, $num_size, ['type' => 'jpg']);
				}
				
				$str_value = $media->enucleate((isset($arr_type_variables['data-base64']) ? EnucleateMedia::VIEW_DATA_BASE64 : EnucleateMedia::VIEW_DATA_URL));
			} catch (Exception $e) {
				
				Labels::setVariable('parse_name', 'Media File');
				error(getLabel('msg_external_resource_error_parse_value'), TROUBLE_ERROR, LOG_BOTH, $str_value, $e);
			}
		}
		
		// Parse output
		
		if (isset($arr_type_variables['convert-json'])) {
			
			$str_value = strSerial2Value($str_value);
			$str_value = value2JSON($str_value);
		} else if (isset($arr_type_variables['escape-json'])) {
			
			$str_value = StreamJSONOutput::parse($str_value);
		} else if ($this->arr_resource['protocol_method'] == static::METHOD_POST) {
			
			$str_value = StreamJSONOutput::parse($str_value);
		} else {
			
			$str_value = rawurlencode($str_value);
		}
		
		if ($str_cache_identifier !== null) {
			$this->arr_cache_request_values[$str_cache_identifier] = $str_value;
		}
		
		return $str_value;
	}
	
	protected function prepareRequest($str_query, $arr_options) {
		
		$str_url = '';
		$str_body = null;
		$str_url_options = $this->arr_resource['url_options'];
		
		preg_match('/'.static::TAGCODE_PARSE_LIMIT.'/', $str_query, $arr_match_limit);

		if ($this->arr_resource['protocol'] == static::PROTOCOL_SPARQL && !$arr_match_limit) {
			
			$str_query .= ' OFFSET '.$arr_options['offset'].' LIMIT '.$arr_options['limit'];
		} else {
			
			$num_limit = $arr_options['limit'];
			$num_offset = $arr_options['offset'];
			$num_limit_max = null;
			
			$s_str_target = null;

			if ($arr_match_limit) {
				
				$num_limit_max = (int)$arr_match_limit[1];
				$s_str_target =& $str_query;
			} else { // Non-SPARQL requests may also have limit/offset in URL
				
				preg_match('/'.static::TAGCODE_PARSE_LIMIT.'/', $str_url_options, $arr_match_limit);
				
				if ($arr_match_limit) {
					
					$num_limit_max = (int)$arr_match_limit[1];
					$s_str_target =& $str_url_options;
				}
			}
			
			if ($s_str_target) {
				
				if ($num_limit_max && $num_limit > $num_limit_max) {
					
					$num_offset = floor($num_offset / $num_limit) * $num_limit_max;
					$num_limit = $num_limit_max;
				}
				
				$s_str_target = preg_replace('/'.static::TAGCODE_PARSE_LIMIT.'/', $num_limit, $s_str_target);
				$s_str_target = preg_replace_callback(
					'/'.static::TAGCODE_PARSE_OFFSET.'/',
					function($arr_matches) use ($num_offset) {
						
						$num_start = (int)$arr_matches[1];
						
						return ($num_start + $num_offset);
					},
					$s_str_target
				);
			}
			
			unset($s_str_target);
		}

		if ($this->arr_resource['protocol'] == static::PROTOCOL_SPARQL) {
			
			$str_url = rawurlencode($str_query);
			$str_url = $this->arr_resource['url'].$str_url.$str_url_options; // Encode the full query to be passed verbatim
		} else {
			
			if ($this->arr_resource['protocol_method'] == static::METHOD_POST) {
				
				$str_body = $str_query;
				
				$str_url = $this->arr_resource['url'].$str_url_options;
			} else {
				
				$str_url = str_replace(["\r", "\n"], '', $str_query); // Allow for line breaks in the query, but do clean it before running it
				$str_url = str_replace(' ', '%20', $str_url); // Preserve spaces
				$str_url = $this->arr_resource['url'].$str_url.$str_url_options;
			}
		}
		
		//$str_url .= (!strpos($str_url, '?') ? '?' : '&').'timeout='.(($this->num_timeout * 1000) - (5 * 1000)); // Try to indicate a endpoint timeout is milliseconds, get possible results gracefully
		
		return ['url' => $str_url, 'body' => $str_body, 'query' => $str_query];
	}
	
	protected function parseContentType($str_content_type, $str_url) {
		
		$str_content_type = explode(';', $str_content_type); // E.g. 'application/json; charset=utf-8'
		$str_content_type = $str_content_type[0];
		
		$this->mode_result_parse = static::PARSE_DEFAULT;
		
		if ($str_content_type) {
			
			if (strEndsWith($str_content_type, static::PARSE_DEFAULT)) {
				
				return;
			} else if (strEndsWith($str_content_type, static::PARSE_TEXT)) {
				
				$this->mode_result_parse = static::PARSE_TEXT;
				return;
			} else if (strEndsWith($str_content_type, static::PARSE_XML)) {
				
				$this->mode_result_parse = static::PARSE_XML;
				return;
			} else if (strEndsWith($str_content_type, static::PARSE_YAML)) {
				
				$this->mode_result_parse = static::PARSE_YAML;
				return;
			}
		}
		
		$arr_url = parse_url($str_url);
		
		if ($arr_url['query']) {
			
			if (strEndsWith($arr_url['query'], static::PARSE_DEFAULT)) {
				
				return;
			} else if (strEndsWith($arr_url['query'], static::PARSE_TEXT)) {
				
				$this->mode_result_parse = static::PARSE_TEXT;
				return;
			} else if (strEndsWith($arr_url['query'], static::PARSE_XML)) {
				
				$this->mode_result_parse = static::PARSE_XML;
				return;
			} else if (strEndsWith($arr_url['query'], static::PARSE_YAML)) {
				
				$this->mode_result_parse = static::PARSE_YAML;
				return;
			}
		}
		
		if ($arr_url['path']) {
			
			if (strEndsWith($arr_url['path'], static::PARSE_DEFAULT)) {
				
			} else if (strEndsWith($arr_url['path'], static::PARSE_TEXT)) {
				
				$this->mode_result_parse = static::PARSE_TEXT;
			} else if (strEndsWith($arr_url['path'], static::PARSE_XML)) {
				
				$this->mode_result_parse = static::PARSE_XML;
			} else if (strEndsWith($arr_url['path'], static::PARSE_YAML)) {
				
				$this->mode_result_parse = static::PARSE_YAML;
			}
		}
	}
		
	public function getRequestVariables() {
		
		$arr = [
			'offset' => false,
			'limit' => false
		];
		
		$str_request_check = $this->arr_resource['query'];
		
		if ($this->arr_resource['protocol'] != static::PROTOCOL_SPARQL) { // Non-SPARQL requests may also have limit/offet in URL
			$str_request_check .= $this->arr_resource['url_options'];
		}

		$has_match = preg_match('/'.static::TAGCODE_PARSE_LIMIT.'/', $str_request_check, $arr_match);
		
		if ($has_match) {
			
			$arr['limit'] = ($arr_match[1] ? (int)$arr_match[1] : true);
			
			$has_match = preg_match('/'.static::TAGCODE_PARSE_OFFSET.'/', $str_request_check, $arr_match);
			
			if ($has_match) {
				$arr['offset'] = ($arr_match[1] ? (int)$arr_match[1] : true);
			}
		} else {
			
			if ($this->arr_resource['protocol'] == static::PROTOCOL_SPARQL) {
				
				$arr['limit'] = true;
				$arr['offset'] = true;
			}
		}
				
		return $arr;	
	}
	
	public function getQueryVariables($name = false) {
		
		$arr = [];
		
		$str_query = $this->arr_resource['query'];
		
		preg_match_all('/\[query=('.($name ?: '[\w]+').')\]'.static::TAGCODE_PARSE_QUERY_CAPTURE.static::TAGCODE_PARSE_QUERY_CLOSE.'/s', $str_query, $arr_matches_queries, PREG_SET_ORDER);
		
		foreach ($arr_matches_queries as $arr_matches_query) {
			
			$str_name_query = $arr_matches_query[1];
			$str_value_query = $arr_matches_query[2];
			$num_count = 1;
			
			preg_match_all('/'.static::TAGCODE_PARSE_VARIABLE.'/s', $str_value_query, $arr_matches_variables, PREG_SET_ORDER);
			
			foreach ($arr_matches_variables as $arr_matches_variable) {
				
				$str_name_variable = $arr_matches_variable[1];
				$str_type_variable = $arr_matches_variable[2];
				$str_value_variable = $arr_matches_variable[3];
				
				if (!$str_name_variable) {
					
					$str_name_variable = $num_count;
					$num_count++;
				}

				$arr[$str_name_query][$str_name_variable] = ['type' => $str_type_variable, 'value' => $str_value_variable];
			}
		}
			
		return $arr;
	}
	
	public function getQueryVariablesFlat($name = false) {
		
		$arr_query_variables = $this->getQueryVariables($name);
		
		$arr = [];
		
		foreach ($arr_query_variables as $str_name_query => $arr_variables) {
			foreach ($arr_variables as $str_name_variable => $arr_variable) {
				
				$str_identifier = 'query_'.str2Label($str_name_query).'_variable_'.str2Label($str_name_variable);
				$str_name = $str_name_query.': '.$str_name_variable.($arr_variable['type'] ? ' ('.$arr_variable['type'].')' : '');
				
				$arr[] = ['id' => $str_identifier, 'name' => $str_name];
			}
		}
		
		return $arr;
	}
	
	public function getResponseValues($include_default = false) {
			
		$arr = [];
		
		if ($include_default) {
			
			$arr['uri'] = $this->arr_resource['response_uri'];
			$arr['label'] = $this->arr_resource['response_label'];
		}
		
		$arr += ($this->arr_resource['response_values'] ?: []);
					
		return $arr;
	}
	
	public function hasResult() {
		
		$arr_result = json_decode($this->str_result, true);
		
		if (!$arr_result) {
			return false;
		}
		
		return true;
	}
	
	public function getResultRaw() {
		
		return $this->str_result;
	}
	
	public function getResultValuesCount() {
		
		if ($this->arr_result_values === null) {
			$this->getResultValues(false);
		}
		
		return count($this->arr_result_values);
	}
	
	public function setResultConversionSocket($socket) {
		
		$this->socket_conversion = $socket;
	}
	
	public function getResultValues($do_flat = true) {
		
		if ($this->arr_result_values !== null) {
			return $this->processResultValues($do_flat);
		}
		
		$arr_result = json_decode($this->str_result, true);
		$this->arr_result_values = [];
		
		if (!$arr_result) {
			return $this->arr_result_values;
		}
		
		if ($this->socket_conversion) { // Prepare conversion process

			$arr_result_value = [];
			$arr_result_value_options = [];
			$is_processing = false;
			
			$this->socket_conversion->process = function($str) use (&$arr_result_value_options, &$arr_result_value, &$is_processing) {
				
				$arr_conversions_output = json_decode($str, true);
				$arr_conversions_output = $arr_conversions_output[WebServiceTaskIngestSource::$name];
				
				foreach ($arr_result_value_options as $name => $arr_options) {
					
					$value = $arr_conversions_output[$arr_options['identifier']]['output'];
					
					if ($arr_options['output_identifier']) {
						$value = $value[$arr_options['output_identifier']];
					}
					
					$arr_result_value[$name] = $value;
				}
				
				$this->arr_result_values[] = $arr_result_value;
				
				$is_processing = false;
			};
		}

		// Parse Result
		 
		$arr_response_values = $this->getResponseValues(true);

		if ($this->arr_resource['response_uri']['value'] || $this->arr_resource['response_label']['value']) {
			
			try {
				
				$traverse = new TraverseJSON($this->arr_resource['response_uri']['value'], null);
				$traverse->set($arr_result);
				$arr_uri = $traverse->get(true);
			} catch (Exception $e) {
				
				Labels::setVariable('parse_name', 'Response URI');
				error(getLabel('msg_external_resource_error_parse_value'), TROUBLE_ERROR, LOG_BOTH, $this->arr_resource['response_uri']['value'], $e);
			}
			$has_multi = $traverse->hasGroups();
			
			try {
				
				$traverse = new TraverseJSON($this->arr_resource['response_label']['value'], $has_multi);
				$traverse->set($arr_result);
				$arr_label = $traverse->get(true);
			} catch (Exception $e) {
				
				Labels::setVariable('parse_name', 'Response Label');
				error(getLabel('msg_external_resource_error_parse_value'), TROUBLE_ERROR, LOG_BOTH, $this->arr_resource['response_label']['value'], $e);
			}
			$arr_values = [];
				
			foreach ($this->arr_resource['response_values'] as $name => $arr_response_value) {
				
				try {
					
					$traverse = new TraverseJSON($arr_response_value['value'], $has_multi);
					$traverse->set($arr_result);
					$arr_values[$name] = $traverse->get(true);
				} catch (Exception $e) {
				
					Labels::setVariable('parse_name', 'Response Value \''.strEscapeHTML($name).'\'');
					error(getLabel('msg_external_resource_error_parse_value'), TROUBLE_ERROR, LOG_BOTH, $arr_response_value['value'], $e);
				}
			}
			
			foreach ($arr_uri as $key_group => $str_uri) {
				
				$str_label = $arr_label[$key_group];

				$arr_result_value = ['uri' => $str_uri, 'label' => $str_label];

				foreach ($this->arr_resource['response_values'] as $name => $arr_response_value) {
					
					$str = $arr_values[$name][$key_group];
					$arr_result_value[$name] = $str;
				}
				
				if ($this->socket_conversion) {
					
					$arr_result_value_options = [];
					$arr_conversions = [];

					foreach ($arr_response_values as $name => $arr_response_value) {
						
						if (!$arr_response_value['conversion_id']) {
							continue;
						}
							
						$value = $arr_result_value[$name];
						$str_identifier = ($value ? value2Hash($value) : '').'_'.$arr_response_value['conversion_id'];
										
						$arr_conversions[$str_identifier] = [
							'identifier' => $str_identifier,
							'script' => $arr_response_value['conversion_script'],
							'input' => $value
						];
						
						$arr_result_value_options[$name] = ['identifier' => $str_identifier, 'output_identifier' => $arr_response_value['conversion_output_identifier']];
						
						$arr_result_value[$name] = '';
					}
					
					if ($arr_conversions) {

						$this->socket_conversion->send(value2JSON([
							'arr_tasks' => [
								WebServiceTaskIngestSource::$name => [
									'arr_data' => $arr_conversions
								]
							]
						]));
						
						$is_processing = true;
						$num_time_conversion = microtime(true);

						while ($is_processing) {
							
							$this->socket_conversion->run();
							
							if ((microtime(true) - $num_time_conversion) > $this->num_timeout_conversion) {
								error(getLabel('msg_socket_client_timeout'));
							}
						}
					} else {
						
						$this->arr_result_values[] = $arr_result_value;
					}
				} else {
					
					$this->arr_result_values[] = $arr_result_value;
				}
			}
		}
		
		return $this->processResultValues($do_flat);
	}
	
	protected function processResultValues($do_flat) {
		
		$str_uri_template = $this->arr_resource['response_uri_template'];
			
		if ($str_uri_template) {
			
			$pos_identifier = strpos($str_uri_template, '[[identifier]]');
			$str_uri_template_start = ($pos_identifier !== false ? substr($str_uri_template, 0, $pos_identifier) : $str_uri_template);
		}
		
		foreach ($this->arr_result_values as &$arr_result_value) {
			
			foreach ($arr_result_value as $name => &$value) {
				
				if ($name === 'uri') {
						
					$value = (is_array($value) ? current($value) : $value); // Get first URI when array
					
					if ($str_uri_template && strpos($value, $str_uri_template_start) === false) {

						if ($pos_identifier !== false) {
							$value = str_replace('[[identifier]]', $value, $str_uri_template);
						} else {
							$value = $str_uri_template.$value;
						}
					}
					
					continue;
				}
				
				if ($do_flat) {
					$value = (is_array($value) ? implode(', ', $value) : $value);
				}
			}
		}
		
		return $this->arr_result_values;
	}

	public function setFilter($arr_filter, $is_flat = false) {
		
		if (!is_array($arr_filter)) {
			
			$arr_filter = null;
		} else {
			
			if ($is_flat) {
			
				$arr_query_variables = $this->getQueryVariables();
				$arr_filter_collect = [];
				
				foreach ($arr_filter as $key => $value) {
				
					foreach ($arr_query_variables as $name_query => $arr_variables) {
						foreach ($arr_variables as $name_variable => $arr_variable) {
							
							$str_identifier = 'query_'.str2Label($name_query).'_variable_'.str2Label($name_variable);
							
							if ($str_identifier == $key) {
								$arr_filter_collect[$name_query][$name_variable] = $value;
							}
						}
					}
				}
				
				$arr_filter = $arr_filter_collect;
			} else {
				
				foreach ($arr_filter as $name_query => &$arr_variables) {
					
					if (!is_array($arr_variables)) {
						continue;
					}
						
					foreach ($arr_variables as $name_variable => &$arr_values) {

						if (!is_array($arr_values)) {
							continue;
						}
						
						foreach ($arr_values as $key => &$value) {
							$value = (isset($value['value']) ? $value['value'] : $value); // Form key is 'value'
						}
					}
				}
			}
		}
		
		$this->arr_filter = $arr_filter;
	}
	
	public function setLimit($arr_limit) {
		
		// $arr_limit = 100, array(200, 100) (from 200 to 300)
		
		$arr_limit = (is_array($arr_limit) ? $arr_limit : [0, (int)$arr_limit]);
		
		$this->arr_limit = $arr_limit;
	}
	
	public function setOrder($arr_order) {
		
		// $arr_order = array('name' => "asc/desc", value => "asc/desc")
				
		$this->arr_order = $arr_order;
	}
	
	public function setTimeout($num_timeout, $num_timeout_conversion = null, $do_timeout_retry = null) {
		
		if ($num_timeout !== null) {
			$this->num_timeout = (int)$num_timeout;
		}
		
		if ($num_timeout_conversion !== null) {
			$this->num_timeout_conversion = (int)$num_timeout_conversion;
		}
		
		if ($do_timeout_retry !== null) {
			$this->do_timeout_retry = (bool)$do_timeout_retry;
		}
	}
		
	public static function formatToFormValueFilter($type, $value, $name, $arr_type_options = false) {
	
		switch ($type) {
			case 'integer':
				$value = (is_array($value) ? $value : ['value' => $value]);
				$format = '<input type="number" name="'.$name.'[value]" value="'.$value['value'].'" />';
				break;
			case 'text':
			case '':
			default:
				$value = (is_array($value) ? $value : ['value' => $value]);
				$format = '<input type="text" name="'.$name.'[value]" value="'.$value['value'].'" />';
				break;
		}
		
		return $format;
	}
	
	public function cleanupFilterForm($arr_filter) {
		
		$arr_query_variables = $this->getQueryVariables();

		foreach ($arr_filter as $name_query => &$arr_filter_variables) {
				
			foreach ($arr_filter_variables as $name_variable => &$arr_filter_variable) {
				
				if (!$arr_query_variables[$name_query][$name_variable]) {
					unset($arr_filter[$name_query][$name_variable]);
					continue;
				}
				
				$arr_filter_variable = self::cleanupFilterFormTypeValuesArr($arr_query_variables[$name_query][$name_variable]['type'], $arr_filter_variable);
				
				if (!$arr_filter[$name_query][$name_variable]) {
					unset($arr_filter[$name_query][$name_variable]);
				}
			}
			
			if (!$arr_filter[$name_query]) {
				unset($arr_filter[$name_query]);
			}
		}
		
		return $arr_filter;
	}
	
	private static function cleanupFilterFormTypeValuesArr($type, $arr_values) {
		
		foreach ($arr_values as $key => $value) {
		
			$use_value = (is_array($value) ? $value['value'] : $value); // Account for complex filter values (i.e. using equality)
						
			if ($type == 'boolean') {
				continue;
			} else if ($type == 'integer' && (int)$use_value) {
				continue;
			} else if ($type == 'numeric' && (float)$use_value) {
				continue;
			} else if ($type == 'date') {
				if ($value['value_now']) {
					$value['value'] = $use_value = 'now';
				}
				if ($value['range_now']) {
					$value['range'] = 'now';
				}
				unset($value['value_now'], $value['range_now']);
				$arr_values[$key] = $value;
				if (FormatTypeObjects::date2Integer($use_value)) {
					continue;
				}
			} else if ($use_value) {
				continue;
			}
			unset($arr_values[$key]);
		}
		
		return $arr_values;
	}
	
	public function getURL($str_identifier, $mode_view = self::VIEW_PLAIN) {
		
		if ($this->arr_resource['protocol'] == static::PROTOCOL_STATIC) {
			$str_url = $this->arr_resource['url'].$str_identifier.$this->arr_resource['url_options'];
		} else {
			$str_url = $str_identifier;
		}
		
		if ($mode_view == self::VIEW_PLAIN) {
			$str_url = strEscapeHTML($str_url);
			$return = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
		}
		
		return $return;
	}
	
	public function getConfigurationOption($str_option) {
		
		return ($this->arr_resource[$str_option] ?? null);
	}
	
	public function debug($do_debug = true) {
		
		$this->do_debug = (bool)$do_debug;
	}
	
	public static function getProtocols() {
	
		return [
			['id' => static::PROTOCOL_SPARQL, 'name' => 'SPARQL'],
			['id' => static::PROTOCOL_API, 'name' => 'API'],
			['id' => static::PROTOCOL_STATIC, 'name' => getLabel('inf_external_resource_static')]
		];
	}
	
	public static function getProtocolMethods() {
	
		return [
			['id' => static::METHOD_GET, 'name' => 'GET'],
			['id' => static::METHOD_POST, 'name' => 'POST']
		];
	}
	
	public static function getReferenceTypes() {
		
		$arr = [['id' => '', 'name' => 'URL', 'value' => 'url']];
		
		return $arr;
	}
}
