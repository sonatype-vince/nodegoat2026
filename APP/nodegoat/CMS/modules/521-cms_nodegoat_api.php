<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

DB::setTable('DEF_NODEGOAT_APIS', DB::$database_home.'.def_nodegoat_apis');
DB::setTable('DEF_NODEGOAT_API_CUSTOM_PROJECTS', DB::$database_home.'.def_nodegoat_api_custom_projects');

class cms_nodegoat_api extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	public static $num_api_mcp_timeout_status = (60 * 60 * 2);
	
	public static function jobProperties() {
		return [
			'runAPIMCPService' => [
				'label' => 'nodegoat API-MCP '.getLabel('lbl_service'),
				'service' => true,
				'options' => function($arr_options) {
					return '<fieldset><ul>
						<li><label>'.getLabel('lbl_server_host_port').'</label><input type="text" name="options[port]" value="'.$arr_options['port'].'" /></li>
					</ul></fieldset>';
				}
			]
		];
	}
		
	public static function getConfiguration($api_id) {
		
		$str_identifier = $api_id;			
		
		$cache = self::getCache($str_identifier);
		if ($cache) {
			return $cache;
		}
		
		$arr = [];
		
		$res = DB::query("
			SELECT a.*,
				ap.project_id, ap.is_default, ap.require_authentication, ap.identifier_url
					FROM ".DB::getTable('DEF_NODEGOAT_APIS')." a
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_API_CUSTOM_PROJECTS')." ap ON (ap.api_id = a.api_id".")
				WHERE a.api_id = ".(int)$api_id."
				"."
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			if (!$arr) {
				$arr = ['api' => $arr_row, 'projects' => []];
			}
			
			if ($arr_row['project_id']) {
				
				$arr_row['is_default'] = DBFunctions::unescapeAs($arr_row['is_default'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['require_authentication'] = DBFunctions::unescapeAs($arr_row['require_authentication'], DBFunctions::TYPE_BOOLEAN);
				
				$arr['projects'][$arr_row['project_id']] = $arr_row;
			}
		}
		
		self::setCache($str_identifier, $arr);

		return $arr;
	}
	
	public static function handleAPIConfiguration($api_id, $arr) {
		
		if (!$api_id || !is_array($arr)) {
			error(getLabel('msg_missing_information'));
		}
		
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_APIS')."
			(api_id".")
				VALUES
			(
				".(int)$api_id."
			)
			".DBFunctions::onConflict('api_id', false)."
		");
		
		$arr_sql_keys = [];

		if ($arr['projects']) {
			
			$arr_sql_insert = [];
			
			foreach ($arr['projects'] as $project_id) {
				
				$arr_sql_insert[] = "(".(int)$api_id.", ".(int)$project_id.")";
				$arr_sql_keys['projects'][] = (int)$project_id;
			}
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_API_CUSTOM_PROJECTS')."
				(api_id".", project_id)
					VALUES
				".implode(",", $arr_sql_insert)."
				".DBFunctions::onConflict('api_id'.', project_id', false)."
			");

			$i = 0;
			
			foreach ($arr['projects'] as $project_id) {
				
				$project_id = (int)$project_id;
				$arr_definition = $arr['projects_organise'][$project_id];
				
				$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_API_CUSTOM_PROJECTS')." SET
						is_default = ".DBFunctions::escapeAs(((int)$arr['default_project'] == $project_id), DBFunctions::TYPE_BOOLEAN).",
						require_authentication = ".DBFunctions::escapeAs($arr_definition['require_authentication'], DBFunctions::TYPE_BOOLEAN).",
						identifier_url = '".DBFunctions::strEscape($arr_definition['identifier_url'])."'
					WHERE api_id = ".(int)$api_id." AND project_id = ".(int)$project_id."
				");
			}
		}
			
		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_API_CUSTOM_PROJECTS')."
			WHERE api_id = ".(int)$api_id."
				".($arr_sql_keys['projects'] ? "AND project_id NOT IN (".implode(",", $arr_sql_keys['projects']).")" : "")."
		");
	}
	
	// API-MCP
	
	public static function runAPIMCPService($arr_options) {
				
		if (!$arr_options['port']) {
			error(getLabel('msg_missing_information'));
		}
		
		$arr_env = [
			'PORT_HTTP' => (string)$arr_options['port'],
			'COMPOSE_PROJECT_NAME' => 'mcp_'.$arr_options['port'],
			'STATS_GROUP_REGEX' => "built FastMCP for (\S+) \(base=[^,]+, name='([^']+)'\)",
			'ALLOWED_HOSTS' => '',
			'REFRESH_SECONDS' => '300',
		];
		
		$arr_service = Settings::get('api_mcp_service');

		$str_path_proxy = DIR_PROGRAMS_RUN.'docker_proxy';
		$str_path_container = rtrim($arr_service['docker'], '/').'/mcp/';
		$str_user_host = ($arr_service['user'] ?: null);
		
		if ($str_user_host) { // We're using containers: switch to the host's 1100CC docker container location
			$str_path_proxy = rtrim($arr_service['docker'], '/').'/1100CC/data/www/1100CC/PROGRAMS/RUN/docker_proxy';
		}

		$arr_command = [$str_path_proxy, 'compose', '-f', $str_path_container.'docker/docker-compose.yml', 'up'];

		$process = new ProcessProgram($arr_command, $str_user_host, $arr_env, $str_path_container.'docker');
		//$process->closeInput(); // Compose never reads stdin, but do not close, we need stdin to have proxy check for EOF
		
		$cleanup_id = Mediator::attach('cleanup', false, function() use ($process) {

			$process->close(true);
		});
		
		$num_time_status = 0;

		while (true) {
			
			Mediator::checkState(); // Check state of this service
			
			$process->checkOutput(true, true); // Check state of the process
			
			$str_error = $process->getError();
			
			if ($str_error !== '') {
				
				error(__METHOD__.' ERROR:'.PHP_EOL
					.strIndent($str_error),
				TROUBLE_NOTICE); // Make notice
			}
			
			$str_result = $process->getOutput();
			
			if ($str_result) {
				
				$str_separator = PHP_EOL;
				$str_line = strtok($str_result, $str_separator);

				while ($str_line !== false) {
					
					$arr_result = json_decode($str_line, true);
					
					if ($arr_result) { // JSON output
						
						if (isset($arr_result['error'])) {
							
							message($arr_result['error'], 'API-MCP');
						} else if (isset($arr_result['statistics'])) {
							
							$num_time = time();
							
							if (($num_time - $num_time_status) > static::$num_api_mcp_timeout_status) {
								
								$str_endpoints = '';
								
								foreach ($arr_result['statistics']['groups'] as $str_endpoint => $arr_info) {
									$str_endpoints .= $str_endpoint.': '.num2String($arr_info['total']).EOL_1100CC;
								}
								
								message('Status:'.EOL_1100CC
									.'	Jobs: total = '.num2String($arr_result['statistics']['total']).' endpoints = '.num2String(count($arr_result['statistics']['groups'])),
								'API-MCP', LOG_BOTH, ($str_endpoints ?: null)); // Provide status update and keep database connection alive

								$num_time_status = $num_time;
							}
						}
					} else {
					
						message($str_line, 'API-MCP');
					}
					
					$str_line = strtok($str_separator);
				}
			}
			
			if (!$process->isRunning(false)) {
				
				$process->close();
				
				Mediator::remove('cleanup', $cleanup_id);
				
				break;
			}
		}
	}
	
	public static function checkAPIMCPService() {
		
		$arr_job = cms_jobs::getJob('cms_nodegoat_api', 'runAPIMCPService');
		
		if ($arr_job && $arr_job['process_id']) {
			
			$arr_service = Settings::get('api_mcp_service');
			
			if ($arr_service['docker_host']) {
				$arr_job['host'] = $arr_service['docker_host'];
			} else {
				$arr_job['host'] = 'http://127.0.0.1:'.$arr_job['port'].'/';
			}
			
			return $arr_job;
		} else {
			return false;
		}	
	}
}
