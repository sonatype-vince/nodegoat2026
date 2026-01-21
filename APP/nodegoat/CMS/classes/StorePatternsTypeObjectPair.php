<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class StorePatternsTypeObjectPair extends PatternEntity {
	
	const PATTERN_NO_REFERENCE = 0;
	const PATTERN_IGNORE = null;
	const PATTERN_STR_NO_REFERENCE = 'no-reference';
	const PATTERN_STR_IGNORE = 'ignore';
	
	
			
	protected $arr_pairs = [];
	protected $stmt_select_pattern_type_object = null;
	
	protected $arr_sql_pairs = [];
	
	public function __construct() {

		
	}
	
	public function getPatternTypeObjectID($type_id, $str_identifier) {

		if (isset($this->arr_pairs[$type_id]) && array_key_exists($str_identifier, $this->arr_pairs[$type_id])) {
			return $this->arr_pairs[$type_id][$str_identifier];
		}
		
		if ($this->stmt_select_pattern_type_object === null) {
			
			$this->stmt_select_pattern_type_object = DB::prepare("SELECT
				nodegoat_ptop.object_id
					FROM ".DB::getTable('DEF_NODEGOAT_PATTERN_TYPE_OBJECT_PAIRS')." nodegoat_ptop
				WHERE nodegoat_ptop.type_id = ".DBStatement::assign('type_id', 'i')."
					AND nodegoat_ptop.identifier = ".DBFunctions::convertTo(DBStatement::assign('identifier', 's'), DBFunctions::TYPE_BINARY, DBFunctions::TYPE_STRING, DBFunctions::FORMAT_STRING_HEX)."
					"."
			");
		}
		
		$this->stmt_select_pattern_type_object->bindParameters(['type_id' => $type_id, 'identifier' => $str_identifier]);
		$res = $this->stmt_select_pattern_type_object->execute();
		
		$object_id = false;
		
		if ($res->getRowCount()) {
		
			$object_id = (int)$res->fetchRow()[0];
		
			$this->arr_pairs[$type_id][$str_identifier] = $object_id;
		}
		
		return $object_id;
	}
	
	public function checkPatternsTypeObject($type_id, $arr_identifiers, $is_ignorable = false, $num_composition = null) {
			
		$this->cleanupTypeObjectPairs($type_id);
		
		$arr_identifiers_new = [];
		$arr_identifiers_stored = [];
		$arr_identifiers_ignorable = [];
		
		DB::startTransaction('check_patterns');
		
		$str_sql_table_name = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_identifiers');
		
		DB::queryMulti("
			DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE IF EXISTS ".$str_sql_table_name.";
			
			CREATE TEMPORARY TABLE ".$str_sql_table_name." (
				".DBFunctions::castColumnAs('identifier', DBFunctions::CAST_TYPE_BINARY)." NOT NULL,
				PRIMARY KEY (identifier)
			) ".DBFunctions::tableOptions(DBFunctions::TABLE_OPTION_MEMORY).";
		");
		
		$stmt_insert = DB::prepare("INSERT INTO ".$str_sql_table_name." (identifier) VALUES (".DBFunctions::convertTo(DBStatement::assign('identifier', 's'), DBFunctions::TYPE_BINARY, DBFunctions::TYPE_STRING, DBFunctions::FORMAT_STRING_HEX).")");

		foreach ($arr_identifiers as $str_identifier) {
			
			$stmt_insert->bindParameters(['identifier' => $str_identifier]);
			$stmt_insert->execute();
			
			$arr_identifiers_new[$str_identifier] = $str_identifier; // To be checked
		}
		unset($arr_identifiers);
		
		DB::commitTransaction('check_patterns');

		$res = DB::query("SELECT
			LOWER(".DBFunctions::convertTo('nodegoat_ptop.identifier', DBFunctions::TYPE_STRING, DBFunctions::TYPE_BINARY, DBFunctions::FORMAT_STRING_HEX)."),
			nodegoat_ptop.object_id
				FROM ".DB::getTable('DEF_NODEGOAT_PATTERN_TYPE_OBJECT_PAIRS')." nodegoat_ptop
				JOIN ".$str_sql_table_name." nodegoat_identifiers ON (nodegoat_identifiers.identifier = nodegoat_ptop.identifier)
			WHERE nodegoat_ptop.type_id = ".(int)$type_id."
				".($num_composition !== null ? "AND nodegoat_ptop.composition = ".(int)$num_composition : "")."
				"."
		");
		
		while ($arr_row = $res->fetchRow()) {
			
			$str_identifier = $arr_row[0];
			
			if ($arr_row[1] === static::PATTERN_IGNORE) {
				
				$arr_identifiers_ignorable[$str_identifier] = true;
				
				if (!$is_ignorable) { // Not ignorable, so do not mark this ignored hash as stored/found
					continue;
				}
			}
			
			unset($arr_identifiers_new[$str_identifier]);
			$arr_identifiers_stored[$str_identifier] = $str_identifier;
		}

		return [
			'stored' => $arr_identifiers_stored,
			'new' => $arr_identifiers_new,
			'ignorable' => $arr_identifiers_ignorable
		];
	}
		
	public function storeTypeObjectPair($type_id, $str_identifier, $object_id, $arr_pattern_value, $num_composition = null) {
		
		// $str_identifier: Internal identifiers for appliations to lookup their patterns.
		// $arr_pattern_value: Store the values and mapping that are central to the pattern, these can be applied again when needed.
		// $num_composition: Indicate the pattern's purpose, i.e. is it for simple string matching or more specific model mapping and matching.
		
		$str_pattern_value = value2JSON($arr_pattern_value);

		if ($num_composition === null) {
			
			$pattern = new PatternEntity($arr_pattern_value);
			$num_composition = $pattern->getPatternComposition();
		}
				
		$this->arr_pairs[$type_id][$str_identifier] = ($object_id === static::PATTERN_IGNORE ? null : (int)$object_id);

		$this->arr_sql_pairs[] = "(".DBFunctions::convertTo("'".DBFunctions::strEscape($str_identifier)."'", DBFunctions::TYPE_BINARY, DBFunctions::TYPE_STRING, DBFunctions::FORMAT_STRING_HEX).", ".(int)$type_id.", ".($object_id === static::PATTERN_IGNORE ? 'NULL' : (int)$object_id).", '".DBFunctions::strEscape($str_pattern_value)."', ".(int)$num_composition.")";						
	}
	
	public function delTypeObjectPair($type_id, $str_identifier) {
		
		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_PATTERN_TYPE_OBJECT_PAIRS')."
			WHERE identifier = ".DBFunctions::convertTo("'".DBFunctions::strEscape($str_identifier)."'", DBFunctions::TYPE_BINARY, DBFunctions::TYPE_STRING, DBFunctions::FORMAT_STRING_HEX)."
				"."
				AND type_id = ".(int)$type_id."
		");
		
		unset($this->arr_pairs[$type_id][$str_identifier]);
	}
	
	public function updateTypeObjectPair($type_id, $str_identifier, $object_id) {
		
		$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_PATTERN_TYPE_OBJECT_PAIRS')."
				SET object_id = ".($object_id === static::PATTERN_IGNORE ? 'NULL' : (int)$object_id)."
			WHERE identifier = ".DBFunctions::convertTo("'".DBFunctions::strEscape($str_identifier)."'", DBFunctions::TYPE_BINARY, DBFunctions::TYPE_STRING, DBFunctions::FORMAT_STRING_HEX)."
				"."
				AND type_id = ".(int)$type_id."
		");
		
		$this->arr_pairs[$type_id][$str_identifier] = ($object_id === static::PATTERN_IGNORE ? null : (int)$object_id);
	}
	
	public function commitPairs() {
		
		if (!$this->arr_sql_pairs) {
			return false;
		}
			
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PATTERN_TYPE_OBJECT_PAIRS')." 
			(identifier".", type_id, object_id, pattern_value, composition) 
				VALUES
			".implode(',', $this->arr_sql_pairs)."
			".DBFunctions::onConflict('identifier'.', type_id', ['object_id'])."
		");
		
		$this->arr_sql_pairs = [];
		
		return true;
	}
	
	public function cleanupTypeObjectPairs($type_id) {
		
		// Remove Pairs where the Object has been removed
		$res = DB::query("
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_PATTERN_TYPE_OBJECT_PAIRS')."
				WHERE type_id = ".(int)$type_id."
					AND object_id != 0
					AND NOT EXISTS (SELECT TRUE
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
						WHERE nodegoat_to.id = ".DB::getTable('DEF_NODEGOAT_PATTERN_TYPE_OBJECT_PAIRS').".object_id
							AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ANY, 'object', 'nodegoat_to')."
					)
					"."
		");
	}

	public static function getTypeObjectPairs($type_id, $str_identifier = false, $arr_objects = [], $num_composition = null) {
		
		$arr = [];
		
		$sql_table_name_objects = '';
		$sql_object_ids = '';
		$sql_composition = '';
		
		if ($arr_objects) {
			
			if (isset($arr_objects['table'])) {
				$sql_table_name_objects = $arr_objects['table'];
			} else {
				$sql_object_ids = implode(',', arrParseRecursive($arr_objects));
			}
		}
				
		if ($num_composition !== null) {
			
			$sql_composition = (is_array($num_composition) ? 'IN ('.implode(',', arrParseRecursive($num_composition)).')' : '= '.(int)$num_composition);
		}
		
		$res = DB::query("SELECT
			nodegoat_ptop.*,
			LOWER(".DBFunctions::convertTo('nodegoat_ptop.identifier', DBFunctions::TYPE_STRING, DBFunctions::TYPE_BINARY, DBFunctions::FORMAT_STRING_HEX).") AS identifier
				FROM ".DB::getTable('DEF_NODEGOAT_PATTERN_TYPE_OBJECT_PAIRS')." AS nodegoat_ptop
				".($sql_table_name_objects ? "JOIN ".$sql_table_name_objects." AS nodegoat_to ON (nodegoat_to.id = nodegoat_ptop.object_id)" : '')."
			WHERE nodegoat_ptop.type_id = ".(int)$type_id."
				".($str_identifier ? "AND nodegoat_ptop.identifier = ".DBFunctions::convertTo("'".DBFunctions::strEscape($str_identifier)."'", DBFunctions::TYPE_BINARY, DBFunctions::TYPE_STRING, DBFunctions::FORMAT_STRING_HEX) : '')."
				".($sql_object_ids ? "AND nodegoat_ptop.object_id IN (".$sql_object_ids.")" : '')."
				".($sql_composition ? "AND nodegoat_ptop.composition ".$sql_composition : '')."
				"."
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['object_id'] = (int)$arr_row['object_id'];
			$arr_row['pattern_value'] = JSON2Value($arr_row['pattern_value']);
			
			$arr[$arr_row['identifier']] = $arr_row;
		}
		
		return ($str_identifier ? current($arr) : $arr);
	}
		
	public static function getPatternIdentifier($arr_pattern) {
		
		$str_identifier = value2HashExchange($arr_pattern);
				
		return $str_identifier;
	}
}
