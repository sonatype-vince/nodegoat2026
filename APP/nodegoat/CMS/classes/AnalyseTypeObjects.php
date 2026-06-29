<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class AnalyseTypeObjects {
	
	protected $type_id = null;
	protected $arr_analyse = null;
	protected $arr_algorithm = null;
	protected $user_id = null;
	protected $project_id = null;
	
	protected $is_external = false; // Target for external usage
	protected $mode_resource = null;
	protected $resource = null;
	protected $str_resource_insert = null;
	protected $arr_resource_insert = null;
	protected $num_resource_insert = null;
	protected $collect = null;
	
	protected $num_nodes = null;
	protected $num_edges = null;
	protected $has_time = false;
	
	protected $arr_nodes_track = null;
	protected $arr_types_name = null;
	
	protected $arr_store = null;
	
	protected static $num_objects_stream = 100000;
	protected static $num_objects_buffer_sql = 10000;
	protected static $num_objects_buffer_csv = 10000;
	protected static $num_objects_buffer_xml = 10000;
	
	const RESOURCE_GRAPH_EDGES = 1;
	const RESOURCE_GRAPH = 2;
	const RESOURCE_TABLE = 3;
	
	const SETTING_WEIGHTED_RAW = 1;
	const SETTING_WEIGHTED_PROCESS = 2;
	
	const RESULT_ABSOLUTE = 'absolute';
	const RESULT_RELATIVE = 'relative';
	const RESULT_NORMALISED = 'normalised';
	const WEIGHTED_UNWEIGHTED = 'unweighted';
	const WEIGHTED_WEIGHTED = 'weighted';
	const WEIGHTED_CLOSENESS = 'closeness';
	const WEIGHTED_DISTANCE = 'distance';
	
	public function __construct($user_id, $project_id, $is_external = false) {

		$this->user_id = (int)$user_id;
		$this->project_id = (int)$project_id;
		
	
		$this->is_external = (bool)$is_external;
	}
	
	public function setAnalyse($type_id, $arr_analyse) {
		
		$this->type_id = $type_id;
		$this->arr_analyse = $arr_analyse;
		
		$this->arr_algorithm = $this->getAlgorithms($arr_analyse['algorithm']);
		
		$this->mode_resource = $this->arr_algorithm['resource'];
		
		if (!$this->arr_algorithm['function']) { // If no function in algorithm, it will also be used externally/export
			$this->is_external = true;
		}
		
		$this->openInputResource();
	}
    
	protected function openInputResource() {
		
		if ($this->mode_resource == static::RESOURCE_TABLE) {
			
			$is_disconnected = ($this->arr_algorithm['disconnected'] && !$this->arr_analyse['scope']['paths']);
			
			if (!$is_disconnected) {
			
				$str_sql_table_name = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.analyse_type_objects');
				
				DB::query("
					CREATE TEMPORARY TABLE ".$str_sql_table_name." (
						id INT,
						id_to INT,
						weight INT,
						PRIMARY KEY (id, id_to)
					) ".DBFunctions::tableOptions(DBFunctions::TABLE_OPTION_MEMORY)."
				");
				
				$this->resource = $str_sql_table_name;
			}
			
			return;
		}
		
		if ($this->mode_resource == static::RESOURCE_GRAPH) {
			
			$this->resource = [getStreamMemory(false), getStreamMemory(false)];
			
			$this->arr_nodes_track = [];
			$this->arr_types_name = StoreType::getTypes();
			
			foreach ($this->arr_types_name as &$arr_type) {
				$arr_type = strEscapeXML(Labels::parseTextVariables($arr_type['name']));
			}
			unset($arr_type);
			
			return;
		}
		
		$this->resource = getStreamMemory(false);
		
		$arr_header = ['key', 'from', 'to', 'weight', 'time'];
			
		fputcsv($this->resource, $arr_header, ',', '"', CSV_ESCAPE);
	}
	
	protected function writeInputResourceGraphEdge() {
		
		// Weight is stored at:
		// - Edge (start Object weight + edge weight + end Object weight (reference condition))
		
		if ($this->arr_resource_insert[4] !== null) {
			
			$str_time = '';
							
			foreach ($this->arr_resource_insert[4] as $arr_date) {
				
				if ($this->is_external) {
					$str_time .= ($str_time !== '' ? ' ' : '').FormatTypeObjects::dateInt2DateStandard($arr_date['start']).','.FormatTypeObjects::dateInt2DateStandard($arr_date['end']);
				} else {
					$str_time .= ($str_time !== '' ? ' ' : '').$arr_date['start'].','.$arr_date['end'];
				}
			}
			
			$this->arr_resource_insert[4] = $str_time;
		}
		
		fputcsv($this->resource, $this->arr_resource_insert, ',', '"', CSV_ESCAPE);
		
		/*$this->str_resource_insert .= $this->arr_resource_insert[0].','.$this->arr_resource_insert[1].','.$this->arr_resource_insert[2].','.$this->arr_resource_insert[3].','.$this->arr_resource_insert[4].EOL_1100CC;
		$this->num_resource_insert++;
		
		if ($this->num_resource_insert === static::$num_objects_buffer_csv) {
			$this->processInputResourceGraphEdge();
		}*/
	}
	
	protected function processInputResourceGraphEdge() {
		
		if ($this->num_resource_insert == 0) {
			return;
		}
		
		fwrite($this->resource, $this->str_resource_insert);
		
		$this->str_resource_insert = '';
		$this->num_resource_insert = 0;
	}
	
	protected function writeInputResourceGraph() {
		
		// Weight is stored at:
		// - Start node (start Object weight)
		// - Edge (start Object weight + edge weight + end Object weight (reference condition))
		// - Target node (end Object weight (reference condition)). Only when weight is specifically applied (reference condition) to end node.
		
		$s_arr_node =& $this->arr_nodes_track[$this->arr_resource_insert[1]];
		
		if (!isset($s_arr_node)) {
			
			$s_arr_node = [
				$this->arr_resource_insert[6]['object']['object_name'],
				$this->type_id,
				$this->arr_resource_insert[4][1] // Weight start
			];
		} else {
			
			if ($this->arr_resource_insert[4][1] !== null) { // Add starting/main node weight
				$s_arr_node[2] += $this->arr_resource_insert[4][1];
			}
		}
		
		$s_arr_node_end =& $this->arr_nodes_track[$this->arr_resource_insert[2]];
		
		if (!isset($s_arr_node_end)) {
			
			$s_arr_node_end = [
				$this->arr_resource_insert[7]['object']['object_name'],
				$this->arr_resource_insert[3],
				$this->arr_resource_insert[4][2] // Weight end
			];
		} else {
			
			if ($this->arr_resource_insert[4][2] !== null) { // Add end/relational node weight (optional)
				$s_arr_node_end[2] += $this->arr_resource_insert[4][2];
			}
		}
				
		$str_edge = '<edge source="'.$this->arr_resource_insert[1].'" target="'.$this->arr_resource_insert[2].'" weight="'.$this->arr_resource_insert[4][0].'"';
		
		if ($this->arr_resource_insert[5] !== null) {
			
			$str_edge .= '><spells>';
			
			foreach ($this->arr_resource_insert[5] as $arr_date) {
				
				if ($this->is_external) {
					$str_edge .= '<spell'.($arr_date['start'] !== FormatTypeObjectsBase::DATE_INT_MIN ? ' start="'.FormatTypeObjects::dateInt2DateStandard($arr_date['start']).'"' : '').($arr_date['end'] !== FormatTypeObjectsBase::DATE_INT_MAX ? ' end="'.FormatTypeObjects::dateInt2DateStandard($arr_date['end']).'"' : '').'/>';
				} else {
					$str_edge .= '<spell start="'.$arr_date['start'].'" end="'.$arr_date['end'].'"/>';
				}
			}
			
			$str_edge .= '</spells></edge>';
		} else {
			
			$str_edge .= '/>';
		}

		fwrite($this->resource[1], $str_edge);
	}
	
	protected function processInputResourceGraph() {
		
		$func_store = function() {
			
			if ($this->num_resource_insert == 0) {
				return;
			}
			
			Response::holdFormat(true);
		
			Response::setFormat(Response::OUTPUT_XML | Response::RENDER_XML);
			$this->str_resource_insert = Response::parse($this->str_resource_insert);
			
			Response::holdFormat();
			
			fwrite($this->resource[0], $this->str_resource_insert);
			
			$this->str_resource_insert = '';
			$this->num_resource_insert = 0;
		};

		foreach ($this->arr_nodes_track as $object_id => &$arr_track) {
			
			$this->str_resource_insert .= '<node id="'.$object_id.'" label="'.Response::addParseDelay($arr_track[0], 'strEscapeXML').'">'
				.'<attvalues>'
					.'<attvalue for="0" value="'.SERVER_NAME.'/'.GenerateTypeObjects::encodeTypeObjectID($arr_track[1], $object_id).'"/>'
					.'<attvalue for="1" value="'.$this->arr_types_name[$arr_track[1]].'"/>'
					.($arr_track[2] !== null ? '<attvalue for="2" value="'.$arr_track[2].'"/>' : '')
				.'</attvalues>'
			.'</node>';
			
			$this->num_resource_insert++;
			$arr_track = null;
			
			if ($this->num_resource_insert === static::$num_objects_buffer_xml) {
				$func_store();
			}
		}
		
		$func_store();
	}
	
	protected function writeInputResourceTable() {
		
		$this->str_resource_insert[0] = ' '; // Remove leading ,
		
		DB::query("INSERT INTO ".$this->resource."
			(id, id_to, weight)
				VALUES
			".$this->str_resource_insert."
			".DBFunctions::onConflict('id, id_to', false, 'weight = '.$this->resource.'.weight + [weight]')."
		");
		
		$this->str_resource_insert = '';
		$this->num_resource_insert = 0;
	}
	
	protected function closeInputResource() {
		
		if ($this->mode_resource == static::RESOURCE_TABLE) {
			
			DB::query("DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE ".$this->resource);
			
			return;
		}
		
		if ($this->mode_resource == static::RESOURCE_GRAPH) {
			
			fclose($this->resource[0]);
			fclose($this->resource[1]);
			
			return;
		}
		
		fclose($this->resource);
	}
	
	public function readInputResourcePackage($str_filename = 'graph') {
		
		if ($this->mode_resource == static::RESOURCE_GRAPH) {
			
			FileStore::addMIMETypes(['application/gexf+xml' => 'gexf']);
			
			Response::sendFileHeaders(null, $str_filename.'.gexf');
			
			echo '<?xml version="1.0" encoding="UTF-8"?>'
			.'<gexf xmlns="http://gexf.net/1.3" version="1.3">'
				.'<meta lastmodifieddate="'.date('c').'">'
					.'<description>nodegoat Graph</description>'
					.'<creator>nodegoat User</creator>'
					.'<generator>'.Labels::getServerVariable('user_agent').'</generator>'
				.'</meta>'
				.'<graph '.($this->has_time ? 'mode="dynamic" timeformat="date"' : 'mode="static"').' defaultedgetype="directed">'
					.'<attributes class="node">'
						.'<attribute id="0" title="nodegoat URI" type="string" />'
						.'<attribute id="1" title="Type" type="string" />'
						.'<attribute id="2" title="Weight" type="double" />'
					.'</attributes>'
					.'<nodes>';
						read($this->resource[0], true);
						ftruncate($this->resource[0], 0);
						fclose($this->resource[0]);
					echo '</nodes><edges>';
						read($this->resource[1], true);
						ftruncate($this->resource[1], 0);
						fclose($this->resource[1]);
					echo '</edges>'
				.'</graph>'
			.'</gexf>';
			
		} else if ($this->mode_resource == static::RESOURCE_GRAPH_EDGES) {
			
			FileStore::readFile($this->resource, $str_filename.'.csv');
		}

		return;
	}

	public function input($collect) {
		
		$this->collect = $collect;
		
		status(getLabel('msg_analysis_status_collect'), 'ANALYSIS', false, ['persist' => true]);
		
		$function = ($this->arr_algorithm['function_input']['pre'] ?? null);
		
		if ($function) {
			$this->$function();
		}

		$is_disconnected = ($this->arr_algorithm['disconnected'] && !$this->arr_analyse['scope']['paths']); // If the algorithm can be Scopeless
		
		if ($is_disconnected) {
			$this->inputDisconnected();
		} else {
			$this->inputGraph();
		}
		
		$function = ($this->arr_algorithm['function_input']['post'] ?? null);
		
		if ($function) {
			$this->$function();
		}
	}
		
	protected function inputGraph() {
		
		$do_weighted = ($this->arr_algorithm['weighted'] && $this->arr_analyse['settings']['weighted']['mode'] != static::WEIGHTED_UNWEIGHTED);
		
		$this->arr_resource_insert = [];
		$this->str_resource_insert = '';
		$this->num_resource_insert = 0;
		
		$this->num_nodes = 0;
		$this->num_edges = 0;
		
		$this->collect->setInitLimit(static::$num_objects_stream);
		$this->collect->setWalkMode(false, false, true);

		while ($this->collect->init()) {
			
			$arr_objects = $this->collect->getPathObjects(CollectTypesObjects::PATH_START);
			
			Mediator::checkState();
		
			foreach ($arr_objects as $start_object_id => $arr_start_object) {
				
				$this->num_nodes++;
			
				$this->collect->getWalkedObject($start_object_id, [], function &($target_object_id, $arr_collect, $source_path, $cur_path, $target_type_id, $arr_info) use ($start_object_id, $arr_start_object, $do_weighted) {
					
					// The applied Scope and this walk are connection-focussed (vs selection-focussed).
					// Meaning: the connections hold both lineage and information.
					
					$arr_collect[$cur_path] = [$target_type_id.'-'.$target_object_id, null, null];
					
					if ($do_weighted) {
						
						$arr_object = $this->collect->getPathObject($cur_path, $arr_info['in_out'], $target_object_id, $arr_info['object_id'], true);
						
						$num_weight = ($arr_object['object']['object_style']['weight'] ?? null);
						
						if ($num_weight !== null && is_array($num_weight)) {
							$num_weight = array_sum($num_weight);
						}
						
						$arr_collect[$cur_path][1] = $num_weight;

						if ($arr_info['in_out'] !== TraceTypesNetwork::PATH_START) {
							
							if (strpos($source_path, '-') === false) {
								$source_path = CollectTypesObjects::PATH_START;
							}
							
							if ($arr_info['in_out'] === TraceTypesNetwork::PATH_IN) {
								
								$arr_object_source = $arr_object;
							} else {
								
								if ($source_path === CollectTypesObjects::PATH_START) {
									
									$arr_object_source = $this->collect->getPathObject(CollectTypesObjects::PATH_START, TraceTypesNetwork::PATH_START, $arr_info['object_id'], $target_object_id, true);
								} else {
									
									// PATH_OUT: The followed references are sourced from previous incoming and outgoing objects, potentially both.
									// source_in_out states the source availablity IN/OUT/BOTH, but here there is no path-sensitivity which source Object supplies the value:
									// 1. We've already filtered (source_in_out states the relational result). 2. Conditions are not supplied path-sensitive.

									$arr_object_source = $this->collect->getPathObject($source_path, (isset($arr_info['source_in_out'][TraceTypesNetwork::PATH_IN]) ? TraceTypesNetwork::PATH_IN : TraceTypesNetwork::PATH_OUT), $arr_info['object_id'], $target_object_id, true);
								}
							}
							
							// Add weight using the source in/out reference
							
							if (isset($arr_info['object_description_id'])) {
								
								$num_weight = ($arr_object_source['object_definitions'][$arr_info['object_description_id']]['object_definition_style']['weight'] ?? null);
								
								if ($num_weight !== null) {
									
									if (is_array($num_weight)) {
										$num_weight = array_sum($num_weight);
									}
									
									// Weight applied to reference target
									
									if ($arr_info['in_out'] === TraceTypesNetwork::PATH_IN) {
										$arr_collect[$source_path][1] = ($arr_collect[$source_path][1] ?? 0) + $num_weight;
									} else {
										$arr_collect[$cur_path][1] = ($arr_collect[$cur_path][1] ?? 0) + $num_weight;
									}
								}
							}
							
							if (isset($arr_info['object_sub_id'])) {
								
								$num_weight = ($arr_object_source['object_subs'][$arr_info['object_sub_id']]['object_sub']['object_sub_style']['weight'] ?? null);
								
								if ($num_weight !== null) {
									
									if (is_array($num_weight)) {
										$num_weight = array_sum($num_weight);
									}
									
									// Weight applied to source, as we can only apply Sub-Object weight when we've used its connection
									
									if ($arr_info['in_out'] === TraceTypesNetwork::PATH_IN) {
										$arr_collect[$cur_path][1] = ($arr_collect[$cur_path][1] ?? 0) + $num_weight;
									} else {
										$arr_collect[$source_path][1] = ($arr_collect[$source_path][1] ?? 0) + $num_weight;
									}
								}
								
								if (isset($arr_info['object_sub_description_id'])) {
								
									$num_weight = ($arr_object_source['object_subs'][$arr_info['object_sub_id']]['object_sub_definitions'][$arr_info['object_sub_description_id']]['object_sub_definition_style']['weight'] ?? null);
									
									if ($num_weight !== null) {
										
										if (is_array($num_weight)) {
											$num_weight = array_sum($num_weight);
										}
										
										// Weight applied to reference target
										
										if ($arr_info['in_out'] === TraceTypesNetwork::PATH_IN) {
											$arr_collect[$source_path][1] = ($arr_collect[$source_path][1] ?? 0) + $num_weight;
										} else {
											$arr_collect[$cur_path][1] = ($arr_collect[$cur_path][1] ?? 0) + $num_weight;
										}
									}
								}
							}
						}
					}

					if (!$arr_info['arr_collapse_source'] && isset($arr_info['date'])) { // Path end, use time
						$arr_collect[$cur_path][2] = $arr_info['date'];
					}
					
					$do_collapse = ($arr_info['in_out'] === TraceTypesNetwork::PATH_START || $arr_info['arr_collapse_source'] ? true : false); // Path start or not path end

					if ($do_collapse) {
						return $arr_collect;
					}
					
					$this->num_edges++;
											
					$str_path = '';
					$num_weight = null;
					$num_weight_start = null;
					$num_weight_end = null;
					$arr_time = null;

					foreach ($arr_collect as $check_path => $arr_path) {
						
						$str_path .= ($str_path === '' ? '' : '_').$arr_path[0];
						
						if ($arr_path[1] !== null) {
							
							$num_weight = (($num_weight ?? 0) + $arr_path[1]);
							
							if ($check_path === CollectTypesObjects::PATH_START) { // Start Object
								$num_weight_start = $arr_path[1];
							} else if ($check_path === $cur_path) { // End Object
								$num_weight_end = $arr_path[1];
							}
						}
						
						if ($arr_path[2] !== null) {
							
							$arr_time = $arr_path[2];
							$this->has_time = true;
						}
					}
					
					if ($num_weight !== null) {
						$num_weight = (int)$num_weight;
					} else {
						$num_weight = 1;
					}
					
					if ($num_weight !== 0) {
						
						if ($this->mode_resource === static::RESOURCE_TABLE) {
							
							$this->str_resource_insert .= ',('.$start_object_id.','.$target_object_id.','.$num_weight.')';
							$this->num_resource_insert++;
							
							if ($this->num_resource_insert === static::$num_objects_buffer_sql) {
								$this->writeInputResourceTable();
							}
						} else if ($this->mode_resource === static::RESOURCE_GRAPH) {
							
							$this->arr_resource_insert[0] = $str_path;
							$this->arr_resource_insert[1] = $start_object_id;
							$this->arr_resource_insert[2] = $target_object_id;
							$this->arr_resource_insert[3] = $target_type_id;
							$this->arr_resource_insert[4][0] = $num_weight;
							$this->arr_resource_insert[4][1] = $num_weight_start;
							$this->arr_resource_insert[4][2] = $num_weight_end;
							$this->arr_resource_insert[5] = $arr_time;
							$this->arr_resource_insert[6] = $arr_start_object;
							$this->arr_resource_insert[7] = $this->collect->getPathObject($cur_path, $arr_info['in_out'], $target_object_id, $arr_info['object_id'], true);
							
							$this->writeInputResourceGraph();
						} else {
							
							$this->arr_resource_insert[0] = $str_path;
							$this->arr_resource_insert[1] = $this->type_id.'-'.$start_object_id;
							$this->arr_resource_insert[2] = $target_type_id.'-'.$target_object_id;
							$this->arr_resource_insert[3] = $num_weight;
							$this->arr_resource_insert[4] = $arr_time;
							
							$this->writeInputResourceGraphEdge();
						}
					}

					return $arr_collect;
				});
			}
			
			if ($this->mode_resource === static::RESOURCE_GRAPH_EDGES) {
				//$this->processInputResourceGraphEdge();
			}
		}
		
		if ($this->mode_resource === static::RESOURCE_TABLE) {
			
			if ($this->num_resource_insert != 0) {
				$this->writeInputResourceTable();
			}
		} else if ($this->mode_resource === static::RESOURCE_GRAPH) {
			
			$this->processInputResourceGraph();
			
			rewind($this->resource[0]);
			rewind($this->resource[1]);
		} else {
			
			rewind($this->resource);
		}
		
		Labels::setVariable('nodes', num2String($this->num_nodes));
		Labels::setVariable('edges', num2String($this->num_edges));
		
		status(getLabel('msg_analysis_status_collect_statistics'), 'ANALYSIS', false, ['persist' => true]);
	}
	
	protected function inputDisconnected() {
		
		$this->collect->init();
	}
	
	public function run() {
		
		if ($this->is_external) {
			return false;
		}
		
		Labels::setVariable('name', str_replace(cms_general::OPTION_GROUP_SEPARATOR, ' - ', $this->arr_algorithm['name']));
		
		status(getLabel('msg_analysis_status_run'), 'ANALYSIS', false, ['persist' => true]);
		
		$function = $this->arr_algorithm['function'];
		
		$this->$function();
		
		return true;
	}
	
	protected function graphStatistics($arr) {
		
		if (!$arr['nodes']) {
			return false;
		}
		
		Labels::setVariable('nodes', num2String($arr['nodes']));
		Labels::setVariable('edges', num2String($arr['edges']));
		if ($arr['weighted']['mode'] != static::WEIGHTED_UNWEIGHTED) {
			Labels::setVariable('weight', $arr['weighted']['min'].' - '.$arr['weighted']['max']);
			Labels::setVariable('weight_mode', ($arr['weighted']['mode'] == static::WEIGHTED_CLOSENESS ? getLabel('lbl_analysis_weighted_closeness') : getLabel('lbl_analysis_weighted_distance')));
		} else {
			Labels::setVariable('weight', getLabel('lbl_analysis_unweighted'));
			Labels::setVariable('weight_mode', 'graph');
		}
		Labels::setVariable('dense_sparse', ($arr['density'] == 'dense' ? getLabel('lbl_analysis_graph_dense') : getLabel('lbl_analysis_graph_sparse')));
		
		status(getLabel('msg_analysis_status_run_statistics'), 'ANALYSIS', false, ['persist' => true]);
	}

	public function store() {
		
		$user_id = ((int)$this->arr_analyse['user_id'] ?: 0);
		$analysis_id = (int)$this->arr_analyse['id'];
		
		$storage = new StoreTypeObjectsExtensions($this->type_id, false, $this->user_id);
		
		DB::startTransaction('analyse_type_objects');
		
		$storage->resetTypeObjectAnalysis($user_id, $analysis_id);
		
		if (!$this->arr_store) {
			
			DB::commitTransaction('analyse_type_objects');

			return false;
		}
		
		Labels::setVariable('amount', num2String(count($this->arr_store)));
		
		status(getLabel('msg_analysis_status_store'), 'ANALYSIS', false, ['persist' => true]);
		
		foreach ($this->arr_store as $object_id => $arr_value) {
			
			$storage->setObjectID($object_id, false);
			
			$storage->addTypeObjectAnalysis($user_id, $analysis_id, $arr_value[0], $arr_value[1]);
		}
		
		$this->arr_store = [];
		
		$storage->save();
		
		$storage->updateTypeObjectAnalysis($user_id, $analysis_id);
		
		DB::commitTransaction('analyse_type_objects');
		
		return true;
	}

	public function getAlgorithms($str_algorithm = null) {
		
		$arr = [
			'degree_centrality' => [
				'id' => 'degree_centrality',
				'name' => getLabel('lbl_analyses_network').cms_general::OPTION_GROUP_SEPARATOR.getLabel('lbl_analysis_degree_centrality'),
				'options' => function($type_id, $str_form_name, $arr_options = []) {

					return false;
				},
				'parse' => function($arr_settings) {
					
					return $arr_settings;
				},
				'function_input' => null,
				'function' => 'runDegreeCentrality',
				'resource' => static::RESOURCE_GRAPH_EDGES,
				'disconnected' => false, // Does it work without the Scope, a network
				'graph' => false, // Does it resolve into a (multi-dimensional) graph
				'weighted' => static::SETTING_WEIGHTED_PROCESS // Does it allow to introduce weights
			],
			'shortest_path' => [
				'id' => 'shortest_path',
				'name' => getLabel('lbl_analyses_network').cms_general::OPTION_GROUP_SEPARATOR.getLabel('lbl_analysis_shortest_path'),
				'options' => function($type_id, $str_form_name, $arr_options = []) {
				
					Labels::setVariable('application', getLabel('lbl_analysis_shortest_path'));
					$str_info_filter = getLabel('inf_application_filter', null, true);
					
					$arr_modes = [
						['id' => '', 'name' => getLabel('lbl_no')],
						['id' => static::RESULT_ABSOLUTE, 'name' => getLabel('lbl_absolute')],
						['id' => static::RESULT_RELATIVE, 'name' => getLabel('lbl_relative')],
						['id' => static::RESULT_NORMALISED, 'name' => getLabel('lbl_normalised')]
					];
					
					$arr_html = [
						getLabel('lbl_from') => '<div>'
							.'<input type="hidden" name="'.$str_form_name.'[filter_start]" value="'.strEscapeHTML(value2JSON($arr_options['filter_start'])).'" />'
							.'<button type="button" id="y:data_filter:configure_application_filter-'.$type_id.'" value="filter" title="'.$str_info_filter.'" class="data edit popup"><span>filter</span></button>'
							.'<label>'.getLabel('lbl_required').'</label>'
						.'</div>',
						getLabel('lbl_target') => '<div>'
							.'<input type="hidden" name="'.$str_form_name.'[filter_end]" value="'.strEscapeHTML(value2JSON($arr_options['filter_end'])).'" />'
							.'<button type="button" id="y:data_filter:configure_application_filter-'.$type_id.'" value="filter" title="'.$str_info_filter.'" class="data edit popup"><span>filter</span></button>'
							.'<label>'.getLabel('lbl_optional').'</label>'
						.'</div>',
						getLabel('lbl_analysis_centrality') => '<div title="'.getLabel('lbl_analysis_shortest_path_betweenness_centrality').'">
							'.cms_general::createSelectorRadio($arr_modes, $str_form_name.'[betweenness_centrality_mode]', $arr_options['betweenness_centrality_mode']).'
						</div>'
					];
					
					return $arr_html;
				},
				'parse' => function($arr_settings) {
					
					$arr_settings_parsed = [];
					
					$arr_settings_parsed['filter_start'] = ($arr_settings['filter_start'] && !is_array($arr_settings['filter_start']) ? JSON2Value($arr_settings['filter_start']) : $arr_settings['filter_start']);
								
					if (!$arr_settings_parsed['filter_start']) { // Shortest path requires settings
						return false;
					}
					
					$arr_settings_parsed['filter_end'] = ($arr_settings['filter_end'] && !is_array($arr_settings['filter_end']) ? JSON2Value($arr_settings['filter_end']) : $arr_settings['filter_end']);
					
					$arr_settings_parsed['betweenness_centrality_mode'] = $arr_settings['betweenness_centrality_mode'];

					return $arr_settings_parsed;
				},
				'function_input' => null,
				'function' => 'prepareShortestPath',
				'resource' => static::RESOURCE_GRAPH_EDGES,
				'disconnected' => false,
				'graph' => true,
				'weighted' => static::SETTING_WEIGHTED_PROCESS
			],
			'betweenness_centrality' => [
				'id' => 'betweenness_centrality',
				'name' => getLabel('lbl_analyses_network').cms_general::OPTION_GROUP_SEPARATOR.getLabel('lbl_analysis_betweenness_centrality'),
				'options' => function($type_id, $str_form_name, $arr_options = []) {

					$arr_modes = [
						['id' => static::RESULT_ABSOLUTE, 'name' => getLabel('lbl_absolute')],
						['id' => static::RESULT_RELATIVE, 'name' => getLabel('lbl_relative')],
						['id' => static::RESULT_NORMALISED, 'name' => getLabel('lbl_normalised')]
					];
					
					$arr_html = [
						getLabel('lbl_mode') => '<div>'.cms_general::createSelectorRadio($arr_modes, $str_form_name.'[mode]', ($arr_options['mode'] ?: static::RESULT_ABSOLUTE)).'</div>'
					];
					
					return $arr_html;
				},
				'parse' => function($arr_settings) {
					
					$arr_settings_parsed = [];
					
					$arr_settings_parsed['mode'] = ($arr_settings['mode'] ?: static::RESULT_ABSOLUTE);

					return $arr_settings_parsed;
				},
				'function_input' => null,
				'function' => 'runBetweennessCentrality',
				'resource' => static::RESOURCE_GRAPH_EDGES,
				'disconnected' => false,
				'graph' => true,
				'weighted' => static::SETTING_WEIGHTED_PROCESS
			],
			'closeness_centrality' => [
				'id' => 'closeness_centrality',
				'name' => getLabel('lbl_analyses_network').cms_general::OPTION_GROUP_SEPARATOR.getLabel('lbl_analysis_closeness_centrality'),
				'options' => function($type_id, $str_form_name, $arr_options = []) {

					$arr_modes = [
						['id' => static::RESULT_NORMALISED, 'name' => getLabel('lbl_normalised')]
					];
					
					$arr_html = [
						getLabel('lbl_mode') => '<div>'.cms_general::createSelectorRadio($arr_modes, $str_form_name.'[mode]', ($arr_options['mode'] ?: static::RESULT_NORMALISED)).'</div>'
					];
					
					return $arr_html;
				},
				'parse' => function($arr_settings) {
					
					$arr_settings_parsed = [];
					
					$arr_settings_parsed['mode'] = ($arr_settings['mode'] ?: static::RESULT_RELATIVE);

					return $arr_settings_parsed;
				},
				'function_input' => null,
				'function' => 'runClosenessCentrality',
				'resource' => static::RESOURCE_GRAPH_EDGES,
				'disconnected' => false,
				'graph' => true,
				'weighted' => static::SETTING_WEIGHTED_PROCESS
			],
			'closeness_eccentricity' => [
				'id' => 'closeness_eccentricity',
				'name' => getLabel('lbl_analyses_network').cms_general::OPTION_GROUP_SEPARATOR.getLabel('lbl_analysis_closeness_eccentricity'),
				'options' => function($type_id, $str_form_name, $arr_options = []) {

					$arr_modes = [
						['id' => static::RESULT_ABSOLUTE, 'name' => getLabel('lbl_absolute')]
					];
					
					$arr_html = [
						getLabel('lbl_mode') => '<div>'.cms_general::createSelectorRadio($arr_modes, $str_form_name.'[mode]', ($arr_options['mode'] ?: static::RESULT_ABSOLUTE)).'</div>'
					];
					
					return $arr_html;
				},
				'parse' => function($arr_settings) {
					
					$arr_settings_parsed = [];
					
					$arr_settings_parsed['mode'] = ($arr_settings['mode'] ?: static::RESULT_RELATIVE);

					return $arr_settings_parsed;
				},
				'function_input' => null,
				'function' => 'runClosenessEccentricity',
				'resource' => static::RESOURCE_GRAPH_EDGES,
				'disconnected' => false,
				'graph' => true,
				'weighted' => static::SETTING_WEIGHTED_PROCESS
			],
			'clustering_coefficient' => [
				'id' => 'clustering_coefficient',
				'name' => getLabel('lbl_analyses_network').cms_general::OPTION_GROUP_SEPARATOR.getLabel('lbl_analysis_clustering_coefficient'),
				'options' => function($type_id, $str_form_name, $arr_options = []) {
					
					return false;
				},
				'parse' => function($arr_settings) {

					return $arr_settings;
				},
				'function_input' => null,
				'function' => 'runClusteringCoefficient',
				'resource' => static::RESOURCE_GRAPH_EDGES,
				'disconnected' => false,
				'graph' => true,
				'weighted' => false
			],
			'pagerank' => [
				'id' => 'pagerank',
				'name' => getLabel('lbl_analyses_network').cms_general::OPTION_GROUP_SEPARATOR.getLabel('lbl_analysis_pagerank'),
				'options' => function($type_id, $str_form_name, $arr_options = []) {

					$arr_html = [
						getLabel('lbl_analysis_iterations') => '<div><input name="'.$str_form_name.'[iterations]" type="number" step="1" min="1" max="50" value="'.($arr_options['iterations'] ?: 28).'" /></div>',
						getLabel('lbl_analysis_damping') => '<div><input name="'.$str_form_name.'[damping]" type="number" step="0.01" min="0.01" max="1" value="'.($arr_options['damping'] ?: 0.85).'" /></div>'
					];
					
					return $arr_html;
				},
				'parse' => function($arr_settings) {
					
					$arr_settings_parsed = [];
					
					$num_iterations = (int)$arr_settings['iterations'];
					
					if (!($num_iterations >= 1 && $num_iterations <= 50)) {
						$num_iterations = 28;
					}
					
					$arr_settings_parsed['iterations'] = $num_iterations;
					
					$num_damping = (float)$arr_settings['damping'];
					
					if (!($num_damping >= 0.01 && $num_damping <= 1)) {
						$num_damping = 0.85;
					}
					
					$arr_settings_parsed['damping'] = $num_damping;

					return $arr_settings_parsed;
				},
				'function_input' => null,
				'function' => 'runPageRank',
				'resource' => static::RESOURCE_GRAPH_EDGES,
				'disconnected' => false,
				'graph' => true,
				'weighted' => false
			],
			'vector_distance' => [
				'id' => 'vector_distance',
				'name' => getLabel('lbl_analyses_similarity').cms_general::OPTION_GROUP_SEPARATOR.getLabel('lbl_analysis_vector_distance'),
				'options' => function($type_id, $str_form_name, $arr_options = []) {
				
					Labels::setVariable('application', getLabel('lbl_analysis_vector_distance'));
					$str_info_filter = getLabel('inf_application_filter', null, true);
					
					$arr_modes = [
						['id' => static::RESULT_ABSOLUTE, 'name' => getLabel('lbl_absolute')],
						['id' => static::RESULT_RELATIVE, 'name' => getLabel('lbl_relative')],
						['id' => static::RESULT_NORMALISED, 'name' => getLabel('lbl_normalised')]
					];
					
					$arr_object_descriptions_vector = data_model::getTypeObjectDescriptionsByValueType($type_id, 'vector');
					$arr_object_descriptions_vector_end = ($arr_options['end_type_id'] ? data_model::getTypeObjectDescriptionsByValueType($arr_options['end_type_id'], 'vector') : []);
					
					$arr_project = StoreCustomProject::getProjects($this->project_id);
					$arr_types_all = StoreType::getTypes(array_keys($arr_project['types']));
					
					$arr_html = [
						getLabel('unit_data_vector') => '<div>'
							.'<select name="'.$str_form_name.'[start_vector]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_object_descriptions_vector, $arr_options['start_vector'])).'</select>'
						.'</div>',
						getLabel('lbl_target') => '<div>'
							.'<select name="'.$str_form_name.'[end_type_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_all, $arr_options['end_type_id'], true)).'</select>'
							.'<input type="hidden" name="'.$str_form_name.'[end_filter]" value="'.strEscapeHTML(value2JSON($arr_options['end_filter'])).'" />'
							.'<button type="button" id="y:data_filter:configure_application_filter-0" value="filter" title="'.$str_info_filter.'" class="data edit popup"><span>filter</span></button>'
							.'<label>'.getLabel('lbl_optional').'</label>'
						.'</div>',
						getLabel('lbl_target').' '.getLabel('unit_data_vector') => '<div>'
							.'<select name="'.$str_form_name.'[end_vector]" id="y:data_analysis:get_value_type_descriptions-vector">'.Labels::parseTextVariables(cms_general::createDropdown($arr_object_descriptions_vector_end, $arr_options['end_vector'], true)).'</select>'
						.'</div>',
						getLabel('lbl_calculate') => '<div>'
							.'<select name="'.$str_form_name.'[operator]" title="'.Response::addParseDelay(getLabel('inf_vector_distance'), 'strEscapeHTML').'">'.cms_general::createDropdown(FilterTypeObjects::getVectorDistanceOperators(), $arr_options['operator']).'</select>'
						.'</div>',
						getLabel('lbl_mode') => '<div>'.cms_general::createSelectorRadio($arr_modes, $str_form_name.'[mode]', ($arr_options['mode'] ?: static::RESULT_ABSOLUTE)).'</div>'
					];
					
					return $arr_html;
				},
				'parse' => function($arr_settings) {
					
					$arr_settings_parsed = [];
					
					$arr_settings_parsed['start_vector'] = $arr_settings['start_vector'];
					$arr_settings_parsed['end_vector'] = $arr_settings['end_vector'];
					$arr_settings_parsed['end_type_id'] = (int)$arr_settings['end_type_id'];
					
					if (!$arr_settings_parsed['start_vector'] || !$arr_settings_parsed['end_type_id'] || !$arr_settings_parsed['end_vector']) { // Vector distance requires settings
						return false;
					}

					$arr_settings_parsed['end_filter'] = ($arr_settings['end_filter'] && !is_array($arr_settings['end_filter']) ? JSON2Value($arr_settings['end_filter']) : $arr_settings['end_filter']);
					$arr_settings_parsed['operator'] = ($arr_settings['operator'] ?: 'euclidean');
					$arr_settings_parsed['mode'] = ($arr_settings['mode'] ?: static::RESULT_ABSOLUTE);

					return $arr_settings_parsed;
				},
				'function_input' => ['pre' => 'inputVectorDistance'],
				'function' => 'runVectorDistance',
				'resource' => static::RESOURCE_TABLE,
				'disconnected' => true,
				'graph' => false,
				'weighted' => static::SETTING_WEIGHTED_PROCESS
			],
			'export_graph' => [
				'id' => 'export_graph',
				'name' => getLabel('lbl_export').cms_general::OPTION_GROUP_SEPARATOR.getLabel('lbl_graph'),
				'options' => function($type_id, $str_form_name, $arr_options = []) {
					
					$arr_formats = [
						'gexf' => ['id' => 'gexf', 'name' => 'GEXF (Graph Exchange XML Format)'],
						//'csv' => ['id' => 'csv', 'name' => 'CSV (Comma Separated Values)']
					];

					$arr_html = [
						getLabel('lbl_format') => '<div>'
							.'<select name="'.$str_form_name.'[format]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_formats, $arr_options['format'])).'</select>'
						.'</div>'
					];
					
					return $arr_html;
				},
				'parse' => function($arr_settings) {
					
					$arr_settings_parsed = [];
					
					$arr_settings_parsed['format'] = ($arr_settings['format'] ?: 'gexf');
					
					return $arr_settings;
				},
				'function_input' => null,
				'function' => null,
				'resource' => static::RESOURCE_GRAPH,
				'disconnected' => false,
				'graph' => false,
				'weighted' => static::SETTING_WEIGHTED_RAW
			]
		];
		
		return ($str_algorithm ? $arr[$str_algorithm] : $arr);
	}
	
	public static function getAlgorithmClass($str_algorithm) {
		
		switch ($str_algorithm) {
			case 'degree_centrality':
			case 'vector_distance':
			case 'export_graph':

				return 'AnalyseTypeObjectsNative';
		}
		
		return 'AnalyseTypeObjectsServer';
	}
}
