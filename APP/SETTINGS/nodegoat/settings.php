<?php
		
	define('DATABASE_NODEGOAT_CONTENT', 'nodegoat_content');
	define('DATABASE_NODEGOAT_TEMP', 'nodegoat_temp');
	DB::$database_cms = 'nodegoat_cms';
	DB::$database_home = 'nodegoat_home';
	
	DB::setConnectionDetails('localhost', '1100CC_cms', './database_cms.pass', DB::CONNECT_CMS);
	DB::setConnectionDetails('localhost', '1100CC_home', './database_home.pass', DB::CONNECT_HOME);
	
	Settings::set('graph_analysis_service', [
		'name' => '1100CC',
		'host' => 'service',
		'token' => 'none'
	]);
	
	Settings::set('visual_settings_map_layers', [[
		//'url' => '//maps.wikimedia.org/osm-intl/{z}/{x}/{y}.png',
		//'attribution' => 'Map data ©'.date('Y').' OpenStreetMap',
		'url' => 'YOUR_URL',
		'attribution' => 'YOUR_ATTRIBUTION',
		'opacity' => 1,
	]]);
	
	//Settings::set('value_type_vector', ['support' => true, 'versioning' => null]);

	/*Settings::set('information_retrieval_service', [
		'name' => '1100CC',
		'host' => 'service',
		'token' => 'none'
	]);*/
	
	//Settings::set('data_model_path_limiting_references', ['search' => 8]);
	//Settings::set('data_model_path_limiting_sequence', ['search' => 500]);
	
	//Settings::set('nodegoat_api', ['context' => $arr_context, 'schema' => $arr_schema]);
	
	/*Settings::set('value_type_module_filecard', function(&$arr_fields) {
		
		$arr_fields = [
			'name' => ['name' => 'Name', 'type' => '', 'path' => '$.name', 'mode' => EnucleateValueTypeModule::FIELD_MODE_VIEW | EnucleateValueTypeModule::FIELD_MODE_FILTER | EnucleateValueTypeModule::FIELD_MODE_INPUT],
		];
	});*/
	
	/*Settings::set('nodegoat_external_resource_secrets', function($str_url) {
	
		static $arr_lookup = [
			'URL_SERVICE' => ['IDENTIFIER' => 'TOKEN'],
		];
		
		if (!$str_url) {
			return;
		}

		foreach ($arr_lookup as $str_url_check => $arr_secrets) {
			
			if (strStartsWith($str_url, $str_url_check)) {
				return $arr_secrets;
			}
		}
	});*/
