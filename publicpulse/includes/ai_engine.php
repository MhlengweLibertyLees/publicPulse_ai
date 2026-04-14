<?php
/**
 * PublicPulse AI — AI Engine v2.0
 * Rule-based: hotspot detection, trend analysis, predictions, anomaly detection, risk scoring
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

class AIEngine
{
    public static function runFullAnalysis(): array
    {
        return [
            'hotspots'    => self::detectHotspots(),
            'trends'      => self::detectTrends(),
            'predictions' => self::generatePredictions(),
            'anomalies'   => self::detectAnomalies(),
            'scored'      => self::scoreOpenComplaints(),
        ];
    }

    public static function detectHotspots(): array
    {
        $rows = Database::fetchAll("
            SELECT c.ward, c.category_id, cat.name AS category_name, COUNT(*) AS complaint_count
            FROM complaints c JOIN categories cat ON cat.id=c.category_id
            WHERE c.created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) AND c.ward IS NOT NULL
            GROUP BY c.ward, c.category_id
            HAVING complaint_count >= ?
            ORDER BY complaint_count DESC
        ", [AI_HOTSPOT_THRESHOLD]);

        foreach ($rows as $row) {
            $exists = Database::fetchOne(
                'SELECT id FROM ai_insights WHERE type="hotspot" AND ward=? AND category_id=? AND created_at >= DATE_SUB(NOW(),INTERVAL 1 DAY)',
                [$row['ward'], $row['category_id']]
            );
            if (!$exists) {
                $sev = $row['complaint_count'] >= 6 ? 'critical' : 'warning';
                Database::execute("INSERT INTO ai_insights (type,title,description,category_id,ward,severity,data_json) VALUES (?,?,?,?,?,?,?)", [
                    'hotspot',
                    "{$row['category_name']} Hotspot: {$row['ward']}",
                    "{$row['ward']} has {$row['complaint_count']} {$row['category_name']} complaints in 30 days — ".AI_HOTSPOT_THRESHOLD."× above threshold.",
                    $row['category_id'], $row['ward'], $sev,
                    json_encode(['complaint_count' => $row['complaint_count'], 'threshold' => AI_HOTSPOT_THRESHOLD])
                ]);
            }
        }
        return $rows;
    }

    public static function detectTrends(): array
    {
        $rows = Database::fetchAll("
            SELECT cat.id AS category_id, cat.name AS category_name,
                   SUM(CASE WHEN c.created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS recent,
                   SUM(CASE WHEN c.created_at < DATE_SUB(NOW(),INTERVAL 30 DAY) AND c.created_at >= DATE_SUB(NOW(),INTERVAL 60 DAY) THEN 1 ELSE 0 END) AS previous
            FROM complaints c JOIN categories cat ON cat.id=c.category_id
            GROUP BY c.category_id HAVING previous > 0
        ");

        $trends = [];
        foreach ($rows as $row) {
            $growth = ($row['recent'] - $row['previous']) / $row['previous'];
            if (abs($growth) >= AI_TREND_THRESHOLD) {
                $pct = round(abs($growth) * 100);
                $dir = $growth > 0 ? 'Increasing' : 'Decreasing';
                $trends[] = ['category' => $row['category_name'], 'growth_pct' => $pct, 'direction' => $dir];

                $exists = Database::fetchOne(
                    'SELECT id FROM ai_insights WHERE type="trend" AND category_id=? AND created_at >= DATE_SUB(NOW(),INTERVAL 1 DAY)',
                    [$row['category_id']]
                );
                if (!$exists) {
                    Database::execute("INSERT INTO ai_insights (type,title,description,category_id,severity,data_json) VALUES (?,?,?,?,?,?)", [
                        'trend',
                        "{$row['category_name']} Complaints {$dir} {$pct}%",
                        "{$row['category_name']} went from {$row['previous']} to {$row['recent']} complaints — a {$pct}% ".strtolower($dir)." trend.",
                        $row['category_id'], $pct >= 50 ? 'critical' : 'warning',
                        json_encode(['growth_rate' => round($growth, 3), 'recent' => $row['recent'], 'previous' => $row['previous']])
                    ]);
                }
            }
        }
        return $trends;
    }

    public static function generatePredictions(): array
    {
        $rows = Database::fetchAll("
            SELECT c.ward, c.category_id, cat.name AS category_name,
                   YEARWEEK(c.created_at,1) AS yr_week, COUNT(*) AS weekly_count
            FROM complaints c JOIN categories cat ON cat.id=c.category_id
            WHERE c.created_at >= DATE_SUB(NOW(),INTERVAL 90 DAY) AND c.ward IS NOT NULL
            GROUP BY c.ward, c.category_id, yr_week
        ");

        $grouped = [];
        foreach ($rows as $row) {
            $k = $row['ward'] . '::' . $row['category_id'];
            $grouped[$k]['category_name'] = $row['category_name'];
            $grouped[$k]['ward']          = $row['ward'];
            $grouped[$k]['category_id']   = (int)$row['category_id'];
            $grouped[$k]['weeks'][]       = $row['yr_week'];
        }

        $predictions = [];
        foreach ($grouped as $item) {
            $weekCount  = count($item['weeks']);
            $confidence = min(0.95, 0.55 + ($weekCount * 0.08));
            if ($weekCount >= 3 && $confidence >= AI_PREDICTION_CONF) {
                $predictions[]   = $item;
                $predictedDate   = date('Y-m-d', strtotime('+7 days'));
                $exists = Database::fetchOne(
                    'SELECT id FROM ai_insights WHERE type="prediction" AND ward=? AND category_id=? AND created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)',
                    [$item['ward'], $item['category_id']]
                );
                if (!$exists) {
                    Database::execute("INSERT INTO ai_insights (type,title,description,category_id,ward,severity,data_json) VALUES (?,?,?,?,?,'critical',?)", [
                        'prediction',
                        "Predicted: {$item['category_name']} Issue in {$item['ward']}",
                        "Based on {$weekCount} weeks of recurring {$item['category_name']} complaints in {$item['ward']}, another incident is predicted by {$predictedDate}.",
                        $item['category_id'], $item['ward'],
                        json_encode(['confidence' => round($confidence, 2), 'week_count' => $weekCount, 'predicted_date' => $predictedDate])
                    ]);
                }
            }
        }
        return $predictions;
    }

    public static function detectAnomalies(): array
    {
        $avg    = (float)(Database::fetchScalar("SELECT COUNT(*)/30 FROM complaints WHERE priority IN ('high','critical') AND created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)") ?: 1);
        $recent = (int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE priority IN ('high','critical') AND created_at >= DATE_SUB(NOW(),INTERVAL 48 HOUR)");

        $anomalies = [];
        if ($avg > 0 && $recent > $avg * 3) {
            $anomalies[] = ['count' => $recent, 'avg' => round($avg, 1), 'ratio' => round($recent / $avg, 1)];
            $exists = Database::fetchOne('SELECT id FROM ai_insights WHERE type="anomaly" AND created_at >= DATE_SUB(NOW(),INTERVAL 6 HOUR)');
            if (!$exists) {
                Database::execute("INSERT INTO ai_insights (type,title,description,severity,data_json) VALUES (?,?,?,'critical',?)", [
                    'anomaly',
                    'Critical Complaint Spike Detected',
                    "{$recent} high/critical complaints in 48 hours — ".round($recent/$avg,1)."× the 30-day daily average. Possible infrastructure emergency.",
                    json_encode(['spike_count' => $recent, 'avg_per_day' => round($avg, 1), 'window' => '48h'])
                ]);
            }
        }
        return $anomalies;
    }

    public static function scoreOpenComplaints(): int
    {
        $complaints = Database::fetchAll("
            SELECT c.id,c.category_id,c.priority,c.ward,c.created_at,cat.name AS category_name
            FROM complaints c JOIN categories cat ON cat.id=c.category_id
            WHERE c.status NOT IN ('resolved','closed') AND c.ai_score IS NULL
        ");
        $scored = 0;
        foreach ($complaints as $c) {
            $score = self::calcRiskScore($c);
            Database::execute('UPDATE complaints SET ai_score=? WHERE id=?', [$score, $c['id']]);
            $scored++;
        }
        return $scored;
    }

    private static function calcRiskScore(array $c): int
    {
        $score = 0;
        $score += match($c['priority']) { 'critical'=>40, 'high'=>25, 'medium'=>10, 'low'=>5, default=>0 };
        $score += match($c['category_name'] ?? '') {
            'Water & Sanitation' => 20, 'Public Safety' => 20, 'Health Services' => 18,
            'Electricity' => 15, 'Roads & Transport' => 12, default => 8
        };
        $ageDays = (time() - strtotime($c['created_at'])) / 86400;
        $score  += min(25, (int)($ageDays * 1.5));
        $wardCount = (int)Database::fetchScalar(
            "SELECT COUNT(*) FROM complaints WHERE ward=? AND created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)",
            [$c['ward'] ?? '']
        );
        if ($wardCount >= AI_HOTSPOT_THRESHOLD) $score += 15;
        return min(100, $score);
    }

    public static function getDashboardInsights(): array
    {
        $cached = getCached('ai_dashboard_insights');
        if ($cached) return $cached;

        $insights = Database::fetchAll("
            SELECT ai.*, cat.name AS category_name, cat.icon AS cat_icon
            FROM ai_insights ai LEFT JOIN categories cat ON cat.id=ai.category_id
            WHERE ai.is_active=1
            ORDER BY FIELD(ai.severity,'critical','warning','info'), ai.created_at DESC
            LIMIT 10
        ");

        setCache('ai_dashboard_insights', $insights, 300);
        return $insights;
    }
}
