<?php

function app_lab_name_match_keys($lab) {
    $lab = trim((string)$lab);
    if ($lab === '') {
        return [];
    }
    $keys = [$lab];
    if (preg_match('/^lab\s*(\d+)$/i', $lab, $m)) {
        $keys[] = $m[1];
    } elseif (preg_match('/^\d+$/', $lab)) {
        $keys[] = 'Lab ' . $lab;
        $keys[] = 'lab ' . $lab;
    }
    return array_values(array_unique(array_filter($keys, 'strlen')));
}

function app_get_setting(mysqli $conn, $key, $default = '') {
    $stmt = $conn->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1');
    if (!$stmt) {
        return $default;
    }
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ($row && $row['setting_value'] !== null && $row['setting_value'] !== '') ? $row['setting_value'] : $default;
}

function app_set_setting(mysqli $conn, $key, $value) {
    $stmt = $conn->prepare('INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $key, $value);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function app_bootstrap_schema(mysqli $conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
        setting_value TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS lab_software (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lab_name VARCHAR(50) NOT NULL,
        software_name VARCHAR(150) NOT NULL,
        version VARCHAR(50) DEFAULT NULL,
        notes VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $checkHistPc = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sitin_history' AND COLUMN_NAME = 'computer_name'");
    if ($checkHistPc && (int)$checkHistPc->fetch_assoc()['cnt'] === 0) {
        @$conn->query("ALTER TABLE sitin_history ADD COLUMN computer_name VARCHAR(50) NULL AFTER lab");
    }

    if (app_get_setting($conn, 'reservation_enabled', '') === '') {
        app_set_setting($conn, 'reservation_enabled', '1');
    }
}

function app_reservations_enabled(mysqli $conn) {
    return app_get_setting($conn, 'reservation_enabled', '1') === '1';
}

function app_format_duration_minutes($minutes) {
    $minutes = max(0, (int)$minutes);
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    if ($mins === 0) {
        return $hours . ' hr' . ($hours === 1 ? '' : 's');
    }
    return $hours . ' hr ' . $mins . ' min';
}

function app_format_hours_decimal($minutes) {
    return number_format(max(0, (int)$minutes) / 60, 1) . ' hrs';
}

function app_valid_timestamp($value) {
    if (empty($value)) {
        return false;
    }
    $ts = strtotime($value);
    return $ts !== false && $ts > 0 && date('Y', $ts) > 1970;
}

function app_session_duration_minutes($timeIn, $timeOut) {
    if (!app_valid_timestamp($timeIn) || !app_valid_timestamp($timeOut)) {
        return 0;
    }
    $start = strtotime($timeIn);
    $end = strtotime($timeOut);
    if ($end <= $start) {
        return 0;
    }
    return (int)round(($end - $start) / 60);
}

function app_build_student_summary(mysqli $conn, $idNumber) {
    $summary = [
        'session_count' => 0,
        'total_minutes' => 0,
        'avg_minutes' => 0,
        'longest_minutes' => 0,
    ];

    $stmt = $conn->prepare("SELECT time_in, time_out FROM sitin_history WHERE id_number = ? AND time_out IS NOT NULL");
    if (!$stmt) {
        return $summary;
    }
    $stmt->bind_param('s', $idNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $durations = [];
    while ($row = $result->fetch_assoc()) {
        $mins = app_session_duration_minutes($row['time_in'], $row['time_out']);
        if ($mins > 0) {
            $durations[] = $mins;
        }
    }
    $stmt->close();

    if (!empty($durations)) {
        $summary['session_count'] = count($durations);
        $summary['total_minutes'] = array_sum($durations);
        $summary['avg_minutes'] = (int)round($summary['total_minutes'] / $summary['session_count']);
        $summary['longest_minutes'] = max($durations);
    }

    return $summary;
}

function app_build_student_sessions_table(mysqli $conn, $idNumber) {
    $rows = [];

    $hist = $conn->prepare("SELECT id, purpose, lab, computer_name, time_in, time_out, status FROM sitin_history WHERE id_number = ? ORDER BY COALESCE(time_out, time_in) DESC LIMIT 50");
    if ($hist) {
        $hist->bind_param('s', $idNumber);
        $hist->execute();
        $res = $hist->get_result();
        while ($row = $res->fetch_assoc()) {
            $mins = app_session_duration_minutes($row['time_in'], $row['time_out']);
            $rows[] = [
                'date' => app_valid_timestamp($row['time_out']) ? date('M j, Y', strtotime($row['time_out'])) : (app_valid_timestamp($row['time_in']) ? date('M j, Y', strtotime($row['time_in'])) : '—'),
                'time_in' => app_valid_timestamp($row['time_in']) ? date('g:i A', strtotime($row['time_in'])) : '—',
                'time_out' => app_valid_timestamp($row['time_out']) ? date('g:i A', strtotime($row['time_out'])) : '—',
                'duration' => $mins > 0 ? app_format_duration_minutes($mins) : '—',
                'pc_no' => !empty($row['computer_name']) ? $row['computer_name'] : '—',
                'status' => ucfirst($row['status'] ?: 'completed'),
                'sort_ts' => app_valid_timestamp($row['time_out']) ? strtotime($row['time_out']) : (app_valid_timestamp($row['time_in']) ? strtotime($row['time_in']) : 0),
            ];
        }
        $hist->close();
    }

    $resv = $conn->prepare("SELECT id, purpose, lab, computer_name, time_in, `date`, status, time_out FROM sitin_reservations WHERE id_number = ? AND status IN ('pending','approved') ORDER BY created_at DESC LIMIT 20");
    if ($resv) {
        $resv->bind_param('s', $idNumber);
        $resv->execute();
        $res = $resv->get_result();
        while ($row = $res->fetch_assoc()) {
            $timeInRaw = null;
            if (!empty($row['date']) && !empty($row['time_in'])) {
                $timeInRaw = $row['date'] . ' ' . $row['time_in'];
            }
            $rows[] = [
                'date' => !empty($row['date']) ? date('M j, Y', strtotime($row['date'])) : '—',
                'time_in' => $timeInRaw && app_valid_timestamp($timeInRaw) ? date('g:i A', strtotime($timeInRaw)) : '—',
                'time_out' => app_valid_timestamp($row['time_out']) ? date('g:i A', strtotime($row['time_out'])) : '—',
                'duration' => ($row['status'] === 'approved' && app_valid_timestamp($row['time_out'])) ? app_format_duration_minutes(app_session_duration_minutes($timeInRaw, $row['time_out'])) : '—',
                'pc_no' => !empty($row['computer_name']) ? $row['computer_name'] : '—',
                'status' => ucfirst($row['status']),
                'sort_ts' => !empty($row['date']) ? strtotime($row['date'] . ' 12:00:00') : time(),
            ];
        }
        $resv->close();
    }

    usort($rows, function ($a, $b) {
        return ($b['sort_ts'] ?? 0) <=> ($a['sort_ts'] ?? 0);
    });

    return $rows;
}

function app_lab_availability(mysqli $conn) {
    $labs = ['524', '526', '528', '530', '542', '544'];
    $out = [];

    foreach ($labs as $lab) {
        $keys = app_lab_name_match_keys($lab);
        $available = 0;
        $inUse = 0;
        $maintenance = 0;
        $software = [];

        if (!empty($keys)) {
            $in = implode(',', array_fill(0, count($keys), '?'));
            $types = str_repeat('s', count($keys));
            $stmt = $conn->prepare("SELECT status, COUNT(*) AS cnt FROM computers WHERE lab_name IN ($in) GROUP BY status");
            if ($stmt) {
                $params = $keys;
                $refs = [$types];
                foreach ($params as $k => $_) {
                    $refs[] = &$params[$k];
                }
                call_user_func_array([$stmt, 'bind_param'], $refs);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) {
                    if ($r['status'] === 'available') {
                        $available = (int)$r['cnt'];
                    } elseif ($r['status'] === 'in_use') {
                        $inUse = (int)$r['cnt'];
                    } elseif ($r['status'] === 'maintenance') {
                        $maintenance = (int)$r['cnt'];
                    }
                }
                $stmt->close();
            }

            $sw = $conn->prepare("SELECT software_name, version FROM lab_software WHERE lab_name IN ($in) ORDER BY software_name");
            if ($sw) {
                $params = $keys;
                $refs = [$types];
                foreach ($params as $k => $_) {
                    $refs[] = &$params[$k];
                }
                call_user_func_array([$sw, 'bind_param'], $refs);
                $sw->execute();
                $swRes = $sw->get_result();
                while ($s = $swRes->fetch_assoc()) {
                    $software[] = $s;
                }
                $sw->close();
            }
        }

        $out[] = [
            'lab' => $lab,
            'label' => 'Lab ' . $lab,
            'available' => $available,
            'in_use' => $inUse,
            'maintenance' => $maintenance,
            'software' => $software,
        ];
    }

    return $out;
}

function app_admin_analytics(mysqli $conn) {
    $data = [
        'daily' => [],
        'by_lab' => [],
        'by_hour' => [],
        'reservation_stats' => ['pending' => 0, 'approved' => 0, 'completed' => 0, 'rejected' => 0],
    ];

    $daily = $conn->query("SELECT DATE(time_out) AS d, COUNT(*) AS cnt FROM sitin_history WHERE time_out >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(time_out) ORDER BY d");
    if ($daily) {
        while ($r = $daily->fetch_assoc()) {
            $data['daily'][$r['d']] = (int)$r['cnt'];
        }
    }
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        if (!isset($data['daily'][$d])) {
            $data['daily'][$d] = 0;
        }
    }
    ksort($data['daily']);

    $byLab = $conn->query("SELECT lab, COUNT(*) AS cnt FROM sitin_history GROUP BY lab ORDER BY cnt DESC LIMIT 8");
    if ($byLab) {
        while ($r = $byLab->fetch_assoc()) {
            $data['by_lab'][] = $r;
        }
    }

    $byHour = $conn->query("SELECT HOUR(time_out) AS hr, COUNT(*) AS cnt FROM sitin_history WHERE time_out IS NOT NULL GROUP BY HOUR(time_out) ORDER BY hr");
    if ($byHour) {
        while ($r = $byHour->fetch_assoc()) {
            $data['by_hour'][(int)$r['hr']] = (int)$r['cnt'];
        }
    }

    $resStats = $conn->query("SELECT status, COUNT(*) AS cnt FROM sitin_reservations GROUP BY status");
    if ($resStats) {
        while ($r = $resStats->fetch_assoc()) {
            $data['reservation_stats'][$r['status']] = (int)$r['cnt'];
        }
    }

    return $data;
}

function app_admin_ai_recommendations(mysqli $conn) {
    $tips = [];

    $lowSessions = $conn->query("SELECT id_number, first_name, last_name, remaining_sessions FROM students WHERE remaining_sessions <= 5 ORDER BY remaining_sessions ASC LIMIT 5");
    if ($lowSessions && $lowSessions->num_rows > 0) {
        $names = [];
        while ($s = $lowSessions->fetch_assoc()) {
            $names[] = htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) . ' (' . (int)$s['remaining_sessions'] . ' left)';
        }
        $tips[] = [
            'title' => 'Students low on sessions',
            'body' => 'Consider resetting or reminding: ' . implode(', ', $names),
            'type' => 'warning',
        ];
    }

    $busiestLab = $conn->query("SELECT lab, COUNT(*) AS cnt FROM sitin_history GROUP BY lab ORDER BY cnt DESC LIMIT 1")->fetch_assoc();
    if ($busiestLab && !empty($busiestLab['lab'])) {
        $tips[] = [
            'title' => 'Busiest lab',
            'body' => 'Lab ' . htmlspecialchars($busiestLab['lab']) . ' has the most sit-ins (' . (int)$busiestLab['cnt'] . '). Schedule extra PCs or stagger classes.',
            'type' => 'info',
        ];
    }

    $avail = app_lab_availability($conn);
    $best = null;
    foreach ($avail as $lab) {
        if ($best === null || $lab['available'] > $best['available']) {
            $best = $lab;
        }
    }
    if ($best) {
        $tips[] = [
            'title' => 'Best availability now',
            'body' => $best['label'] . ' has ' . (int)$best['available'] . ' vacant PC(s). Recommend this lab for walk-ins.',
            'type' => 'success',
        ];
    }

    $pending = (int)($conn->query("SELECT COUNT(*) AS c FROM sitin_reservations WHERE status='pending'")->fetch_assoc()['c'] ?? 0);
    if ($pending > 0) {
        $tips[] = [
            'title' => 'Pending reservations',
            'body' => $pending . ' request(s) waiting for approval. Review Reservation Requests to reduce wait time.',
            'type' => 'warning',
        ];
    }

    $peakHour = 0;
    $peakCnt = 0;
    $byHour = $conn->query("SELECT HOUR(time_out) AS hr, COUNT(*) AS cnt FROM sitin_history WHERE time_out IS NOT NULL GROUP BY HOUR(time_out) ORDER BY cnt DESC LIMIT 1");
    if ($byHour) {
        $peak = $byHour->fetch_assoc();
        if ($peak) {
            $peakHour = (int)$peak['hr'];
            $peakCnt = (int)$peak['cnt'];
        }
    }
    if ($peakCnt > 0) {
        $tips[] = [
            'title' => 'Peak sit-in hour',
            'body' => 'Most activity around ' . date('g A', strtotime($peakHour . ':00')) . ' (' . $peakCnt . ' sessions). Staff labs accordingly.',
            'type' => 'info',
        ];
    }

    if (empty($tips)) {
        $tips[] = [
            'title' => 'All clear',
            'body' => 'No urgent recommendations right now. Labs and reservations look healthy.',
            'type' => 'success',
        ];
    }

    return $tips;
}

function app_resolve_sitin_time_in(array $sitDetails) {
    if (!empty($sitDetails['time_in']) && !empty($sitDetails['date'])) {
        $candidate = $sitDetails['date'] . ' ' . $sitDetails['time_in'];
        if (app_valid_timestamp($candidate)) {
            return date('Y-m-d H:i:s', strtotime($candidate));
        }
    }
    if (app_valid_timestamp($sitDetails['time_in'] ?? null)) {
        return date('Y-m-d H:i:s', strtotime($sitDetails['time_in']));
    }
    return date('Y-m-d H:i:s');
}
