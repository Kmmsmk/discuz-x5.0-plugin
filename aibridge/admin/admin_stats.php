<?php

namespace aibridge\admin;

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

class admin_stats {

    public static function run() {
        $range = isset($_GET['range']) ? trim($_GET['range']) : 'today';
        $end_time = time();

        switch ($range) {
            case 'today':
                $start_time = strtotime(date('Y-m-d'));
                $range_label = '今日';
                break;
            case 'week':
                $start_time = strtotime('monday this week');
                $range_label = '本周';
                break;
            case 'month':
                $start_time = strtotime(date('Y-m-01'));
                $range_label = '本月';
                break;
            case 'all':
                $start_time = 0;
                $end_time = 0;
                $range_label = '全部';
                break;
            default:
                $start_time = strtotime(date('Y-m-d'));
                $range_label = '今日';
        }

        // 总调用次数
        $total = \aibridge\table_aibridge_log::t()->get_total_count($start_time, $end_time);

        // 成功次数
        $total_ok_params = [['aibridge_log']];
        $where_ok = '1=1';
        $params_ok = ['aibridge_log'];
        if ($start_time > 0) { $where_ok .= ' AND created_at>=%d'; $params_ok[] = $start_time; }
        if ($end_time > 0)   { $where_ok .= ' AND created_at<=%d'; $params_ok[] = $end_time; }
        $where_ok .= ' AND status=1';
        $total_ok = DB::result_first('SELECT COUNT(*) FROM %t WHERE ' . $where_ok, $params_ok);
        $total_fail = $total - $total_ok;

        // 各平台统计
        $stats = \aibridge\table_aibridge_log::t()->get_stats($start_time, $end_time);

        // 最近 20 条调用记录
        $recent_params = ['aibridge_log'];
        $recent_where = '1=1';
        if ($start_time > 0) { $recent_where .= ' AND l.created_at>=%d'; $recent_params[] = $start_time; }
        if ($end_time > 0)   { $recent_where .= ' AND l.created_at<=%d'; $recent_params[] = $end_time; }
        $recent_logs = DB::fetch_all(
            'SELECT l.uid, l.platform, l.model, l.prompt_tokens, l.completion_tokens, l.created_at, l.status, l.error_msg, m.username
             FROM %t l LEFT JOIN ' . DB::table('members') . ' m ON l.uid=m.uid
             WHERE ' . $recent_where . ' ORDER BY l.id DESC LIMIT 20',
            $recent_params
        );

        echo '<style>
        .aibridge-stats-cards { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:20px; }
        .aibridge-stat-card { flex:1; min-width:130px; padding:16px 20px; background:#fff; border:1px solid #e2e8f0; border-radius:4px; }
        .aibridge-stat-card .num { font-size:28px; font-weight:700; color:#0F766E; }
        .aibridge-stat-card .lbl { font-size:12px; color:#64748b; margin-top:4px; }
        .aibridge-stat-card.warn .num { color:#D97706; }
        .aibridge-range-tabs { display:flex; gap:8px; margin-bottom:16px; }
        .aibridge-range-tab { padding:5px 14px; border:1px solid #e2e8f0; border-radius:3px; font-size:13px; text-decoration:none; color:#334155; background:#fff; }
        .aibridge-range-tab.active { background:#0F766E; color:#fff; border-color:#0F766E; }
        .aibridge-range-tab:hover:not(.active) { background:#f1f5f9; }
        .aibridge-stats-table { width:100%; border-collapse:collapse; font-size:13px; }
        .aibridge-stats-table th { padding:10px 12px; background:#f8fafc; border-bottom:2px solid #e2e8f0; text-align:left; color:#334155; font-weight:600; }
        .aibridge-stats-table td { padding:9px 12px; border-bottom:1px solid #f1f5f9; color:#0f172a; }
        .aibridge-stats-table tr:hover td { background:#f8fafc; }
        .aibridge-status-ok { display:inline-block; padding:2px 7px; border-radius:10px; font-size:11px; background:#dcfce7; color:#16a34a; }
        .aibridge-status-fail { display:inline-block; padding:2px 7px; border-radius:10px; font-size:11px; background:#fee2e2; color:#dc2626; }
        .aibridge-section-title { font-size:14px; font-weight:600; color:#0f172a; margin:20px 0 10px; padding-bottom:6px; border-bottom:2px solid #0F766E; display:inline-block; }
        </style>';

        // 导航
        echo '<div style="margin-bottom:16px;">';
        echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=config" style="margin-right:12px;font-size:13px;color:#334155;">平台配置</a>';
        echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=general" style="margin-right:12px;font-size:13px;color:#334155;">通用设置</a>';
        echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=prompt" style="margin-right:12px;font-size:13px;color:#334155;">Prompt模板</a>';
        echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=stats" style="margin-right:12px;font-size:13px;color:#0F766E;font-weight:600;">用量统计</a>';
        echo '</div>';

        showtableheader('AI 用量统计', 'fixpadding');

        echo '<tr><td colspan="2">';

        // 时间筛选标签
        $base_url = ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=stats';
        echo '<div class="aibridge-range-tabs">';
        foreach (['today' => '今日', 'week' => '本周', 'month' => '本月', 'all' => '全部'] as $r => $label) {
            $active = ($range === $r) ? ' active' : '';
            echo '<a href="' . $base_url . '&range=' . $r . '" class="aibridge-range-tab' . $active . '">' . $label . '</a>';
        }
        echo '</div>';

        // 统计卡片
        $total_tokens = 0;
        foreach ($stats as $s) {
            $total_tokens += intval($s['total_prompt']) + intval($s['total_completion']);
        }
        echo '<div class="aibridge-stats-cards">';
        echo '<div class="aibridge-stat-card"><div class="num">' . number_format($total) . '</div><div class="lbl">' . $range_label . '总调用次数</div></div>';
        echo '<div class="aibridge-stat-card"><div class="num">' . number_format($total_ok) . '</div><div class="lbl">成功次数</div></div>';
        echo '<div class="aibridge-stat-card warn"><div class="num">' . number_format($total_fail) . '</div><div class="lbl">失败次数</div></div>';
        echo '<div class="aibridge-stat-card"><div class="num">' . number_format($total_tokens) . '</div><div class="lbl">消耗 Token 总量</div></div>';
        echo '</div>';

        // 各平台统计
        echo '<div class="aibridge-section-title">各平台统计</div>';
        if (!empty($stats)) {
            echo '<table class="aibridge-stats-table">';
            echo '<thead><tr><th>平台</th><th>调用次数</th><th>输入 Token</th><th>输出 Token</th><th>合计 Token</th></tr></thead><tbody>';
            foreach ($stats as $s) {
                $name = \aibridge\lib_aibridge::$platform_names[$s['platform']] ?? $s['platform'];
                $total_tok = intval($s['total_prompt']) + intval($s['total_completion']);
                echo '<tr>';
                echo '<td>' . htmlspecialchars($name) . '</td>';
                echo '<td>' . number_format($s['total']) . '</td>';
                echo '<td>' . number_format($s['total_prompt']) . '</td>';
                echo '<td>' . number_format($s['total_completion']) . '</td>';
                echo '<td><b>' . number_format($total_tok) . '</b></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p style="color:#94a3b8;font-size:13px;padding:16px 0;">暂无统计数据</p>';
        }

        // 最近调用记录
        echo '<div class="aibridge-section-title" style="margin-top:24px;">最近调用记录</div>';
        if (!empty($recent_logs)) {
            echo '<table class="aibridge-stats-table">';
            echo '<thead><tr><th>时间</th><th>用户</th><th>平台</th><th>模型</th><th>Token</th><th>状态</th><th>错误信息</th></tr></thead><tbody>';
            foreach ($recent_logs as $log) {
                $pname = \aibridge\lib_aibridge::$platform_names[$log['platform']] ?? $log['platform'];
                $tokens = intval($log['prompt_tokens']) + intval($log['completion_tokens']);
                $status_html = $log['status'] ? '<span class="aibridge-status-ok">成功</span>' : '<span class="aibridge-status-fail">失败</span>';
                echo '<tr>';
                echo '<td>' . date('m-d H:i', $log['created_at']) . '</td>';
                echo '<td>' . htmlspecialchars($log['username'] ?: ('UID:' . $log['uid'])) . '</td>';
                echo '<td>' . htmlspecialchars($pname) . '</td>';
                echo '<td style="color:#64748b;font-size:12px;">' . htmlspecialchars($log['model']) . '</td>';
                echo '<td>' . number_format($tokens) . '</td>';
                echo '<td>' . $status_html . '</td>';
                echo '<td style="color:#dc2626;font-size:12px;">' . htmlspecialchars(mb_substr($log['error_msg'] ?? '', 0, 50)) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p style="color:#94a3b8;font-size:13px;padding:16px 0;">暂无调用记录</p>';
        }

        echo '</td></tr>';
        showtablefooter();
    }
}