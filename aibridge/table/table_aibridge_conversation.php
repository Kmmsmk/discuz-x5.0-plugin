<?php

namespace aibridge;

use discuz_table;
use DB;

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class table_aibridge_conversation extends discuz_table
{

    public static function t() {
        static $_instance;
        if(!isset($_instance)) {
            $_instance = new self();
        }
        return $_instance;
    }

    public function __construct() {
        $this->_table = 'aibridge_conversation';
        $this->_pk    = 'id';
        parent::__construct();
    }

    public function fetch_by_uid($uid) {
        return DB::fetch_first('SELECT * FROM %t WHERE uid=%d', [$this->_table, $uid]);
    }

    public function fetch_by_id($id) {
        return DB::fetch_first('SELECT * FROM %t WHERE id=%d', [$this->_table, $id]);
    }

    public function save_conversation($uid, $platform, $messages) {
        $existing = $this->fetch_by_uid($uid);
        $messages_json = json_encode($messages, JSON_UNESCAPED_UNICODE);
        if ($existing) {
            DB::update($this->_table, [
                'platform'      => $platform,
                'messages_json' => $messages_json,
                'updated_at'    => time(),
            ], ['id=%d', intval($existing['id'])]);
            return intval($existing['id']);
        } else {
            return DB::insert($this->_table, [
                'uid'           => intval($uid),
                'platform'      => $platform,
                'messages_json' => $messages_json,
                'created_at'    => time(),
                'updated_at'    => time(),
            ], true);
        }
    }

    public function clear_conversation($uid) {
        DB::query('DELETE FROM %t WHERE uid=%d', [$this->_table, intval($uid)]);
    }
}