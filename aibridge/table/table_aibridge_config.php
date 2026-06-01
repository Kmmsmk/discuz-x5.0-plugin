<?php

namespace aibridge;

use discuz_table;
use DB;

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class table_aibridge_config extends discuz_table
{

    public static function t() {
        static $_instance;
        if(!isset($_instance)) {
            $_instance = new self();
        }
        return $_instance;
    }

    public function __construct() {
        $this->_table = 'aibridge_config';
        $this->_pk    = 'id';
        parent::__construct();
    }

    public function fetch_all_platforms() {
        return DB::fetch_all('SELECT * FROM %t ORDER BY id ASC', [$this->_table]);
    }

    public function fetch_by_platform($platform) {
        return DB::fetch_first('SELECT * FROM %t WHERE platform=%s', [$this->_table, $platform]);
    }

    public function update_platform($platform, $data) {
        return DB::update($this->_table, $data, ['platform=%s', $platform]);
    }

    public function fetch_enabled_platforms() {
        return DB::fetch_all('SELECT * FROM %t WHERE enabled=1 ORDER BY id ASC', [$this->_table]);
    }
}