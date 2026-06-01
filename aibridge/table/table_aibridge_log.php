<?php

namespace aibridge;

use discuz_table;
use DB;

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class table_aibridge_log extends discuz_table
{

    public static function t() {
        static $_instance;
        if(!isset($_instance)) {
            $_instance = new self();
        }
        return $_instance;
    }

    public function __construct() {
        $this->_table = 'aibridge_log';
        $this->_pk    = 'id';
        parent::__construct();
    }

    public function insert_log($data) {
        return DB::insert($this->_table, $data, true);
    }

    public function count_by_uid_today($uid) {
        $today_start = strtotime(date('Y-m-d'));
        return DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d AND created_at>=%d', [$this->_table, $uid, $today_start]);
    }

    public function get_stats($start_time = 0, $end_time = 0) {
        $where  = '1=1';
        $params = [$this->_table];
        if ($start_time > 0) {
            $where   .= ' AND created_at>=%d';
            $params[] = intval($start_time);
        }
        if ($end_time > 0) {
            $where   .= ' AND created_at<=%d';
            $params[] = intval($end_time);
        }
        return DB::fetch_all(
            "SELECT platform, COUNT(*) AS total, COALESCE(SUM(prompt_tokens),0) AS total_prompt, COALESCE(SUM(completion_tokens),0) AS total_completion FROM %t WHERE $where GROUP BY platform ORDER BY total DESC",
            $params
        );
    }

    public function get_total_count($start_time = 0, $end_time = 0) {
        $where  = '1=1';
        $params = [$this->_table];
        if ($start_time > 0) {
            $where   .= ' AND created_at>=%d';
            $params[] = intval($start_time);
        }
        if ($end_time > 0) {
            $where   .= ' AND created_at<=%d';
            $params[] = intval($end_time);
        }
        return intval(DB::result_first("SELECT COUNT(*) FROM %t WHERE $where", $params));
    }
}