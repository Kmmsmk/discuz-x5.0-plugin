<?php

namespace aibridge;

use discuz_table;
use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_aibridge_prompt extends discuz_table
{

	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {
		$this->_table = 'aibridge_prompt';
		$this->_pk    = 'id';
		parent::__construct();
	}

	// 获取所有模板
	public function fetch_all() {
		return DB::fetch_all('SELECT * FROM %t ORDER BY is_default DESC, id ASC', [$this->_table]);
	}

	// 获取默认模板
	public function fetch_default() {
		return DB::fetch_first('SELECT * FROM %t WHERE is_default=1 LIMIT 1', [$this->_table]);
	}

	// 获取指定 ID 的模板
	public function fetch_by_id($id) {
		return DB::fetch_first('SELECT * FROM %t WHERE id=%d', [$this->_table, intval($id)]);
	}

	// 插入新模板（若设为默认则清除其他默认标记）
	public function insert_prompt($name, $system_prompt, $is_default = 0) {
		if ($is_default) {
			DB::update($this->_table, ['is_default' => 0], '1=1');
		}
		return DB::insert($this->_table, [
			'name'          => $name,
			'system_prompt' => $system_prompt,
			'is_default'    => intval($is_default),
			'created_at'    => time(),
		], true);
	}

	// 更新模板
	public function update_prompt($id, $name, $system_prompt, $is_default = 0) {
		if ($is_default) {
			DB::update($this->_table, ['is_default' => 0], '1=1');
		}
		return DB::update($this->_table, [
			'name'          => $name,
			'system_prompt' => $system_prompt,
			'is_default'    => intval($is_default),
		], ['id=%d', intval($id)]);
	}

	// 删除模板（默认模板不允许删除）
	public function delete_prompt($id) {
		$row = $this->fetch_by_id($id);
		if (!$row || $row['is_default']) {
			return false;
		}
		DB::query('DELETE FROM %t WHERE id=%d', [$this->_table, intval($id)]);
		return true;
	}
}
