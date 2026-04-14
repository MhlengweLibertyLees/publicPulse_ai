<?php
/**
 * PublicPulse AI — Analytics Model v2.0
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

class Analytics
{
    public static function getKPIs(): array
    {
        $cached = getCached('kpi_overview');
        if ($cached) return $cached;

        $total    = (int)Database::fetchScalar('SELECT COUNT(*) FROM complaints');
        $open     = (int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE status NOT IN ('resolved','closed')");
        $resolved = (int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE status='resolved'");
        $critical = (int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE priority='critical' AND status NOT IN ('resolved','closed')");
        $today    = (int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE DATE(created_at)=CURDATE()");
        $resRate  = $total > 0 ? round(($resolved / $total) * 100, 1) : 0;
        $avgTime  = Database::fetchScalar("
            SELECT AVG(TIMESTAMPDIFF(HOUR,c.created_at,sl.created_at))
            FROM complaints c
            JOIN status_logs sl ON sl.complaint_id=c.id AND sl.new_status='resolved'
        ");

        $kpis = [
            'total'    => $total,
            'open'     => $open,
            'resolved' => $resolved,
            'critical' => $critical,
            'today'    => $today,
            'resRate'  => $resRate,
            'avg_resolution_hours' => $avgTime ? round((float)$avgTime, 1) : 0,
        ];

        setCache('kpi_overview', $kpis, 120);
        return $kpis;
    }

    public static function byCategory(): array
    {
        return Database::fetchAll("
            SELECT cat.name AS label, cat.color AS color, cat.icon AS icon, COUNT(c.id) AS value
            FROM categories cat
            LEFT JOIN complaints c ON c.category_id=cat.id
            WHERE cat.is_active=1
            GROUP BY cat.id ORDER BY value DESC
        ");
    }

    public static function byStatus(): array
    {
        return Database::fetchAll("
            SELECT status AS label, COUNT(*) AS value FROM complaints
            GROUP BY status
            ORDER BY FIELD(status,'submitted','in_review','in_progress','resolved','closed')
        ");
    }

    public static function monthlyTrend(int $months = 6): array
    {
        return Database::fetchAll("
            SELECT DATE_FORMAT(created_at,'%b %Y') AS label,
                   COUNT(*) AS total,
                   SUM(CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END) AS resolved
            FROM complaints
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(created_at,'%Y-%m')
            ORDER BY MIN(created_at)
        ", [$months]);
    }

    public static function byWard(): array
    {
        return Database::fetchAll("
            SELECT ward AS label, COUNT(*) AS total,
                   SUM(CASE WHEN priority='critical' THEN 1 ELSE 0 END) AS critical_count,
                   SUM(CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END) AS resolved_count
            FROM complaints WHERE ward IS NOT NULL
            GROUP BY ward ORDER BY total DESC LIMIT 15
        ");
    }

    public static function dailyVolume(int $days = 30): array
    {
        return Database::fetchAll("
            SELECT DATE_FORMAT(created_at,'%d %b') AS label, COUNT(*) AS value
            FROM complaints
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at)
        ", [$days]);
    }

    public static function byPriority(): array
    {
        return Database::fetchAll("
            SELECT priority AS label, COUNT(*) AS value FROM complaints
            GROUP BY priority
            ORDER BY FIELD(priority,'critical','high','medium','low')
        ");
    }

    public static function recentComplaints(int $limit = 10): array
    {
        return Database::fetchAll("
            SELECT c.id,c.reference_no,c.title,c.status,c.priority,c.ward,c.created_at,c.ai_score,
                   u.name AS citizen_name,cat.name AS category_name,cat.color AS category_color
            FROM complaints c
            JOIN users u ON u.id=c.user_id
            JOIN categories cat ON cat.id=c.category_id
            ORDER BY c.created_at DESC LIMIT ?
        ", [$limit]);
    }

    public static function categoryComparison(): array
    {
        return Database::fetchAll("
            SELECT cat.name AS category,
                   SUM(CASE WHEN c.created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS current_period,
                   SUM(CASE WHEN c.created_at < DATE_SUB(NOW(),INTERVAL 30 DAY) AND c.created_at >= DATE_SUB(NOW(),INTERVAL 60 DAY) THEN 1 ELSE 0 END) AS prev_period
            FROM categories cat
            LEFT JOIN complaints c ON c.category_id=cat.id
            WHERE cat.is_active=1
            GROUP BY cat.id ORDER BY current_period DESC
        ");
    }

    public static function resolutionRateOverTime(int $months = 6): array
    {
        return Database::fetchAll("
            SELECT DATE_FORMAT(created_at,'%b %Y') AS label,
                   COUNT(*) AS total,
                   ROUND(100*SUM(CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END)/COUNT(*),1) AS rate
            FROM complaints
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(created_at,'%Y-%m')
            ORDER BY MIN(created_at)
        ", [$months]);
    }

    public static function generateReportSummary(string $from, string $to): array
    {
        $p = [':f' => $from, ':t' => $to];
        return [
            'period'      => ['from' => $from, 'to' => $to],
            'total'       => (int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE DATE(created_at) BETWEEN :f AND :t", $p),
            'critical'    => (int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE priority='critical' AND DATE(created_at) BETWEEN :f AND :t", $p),
            'resolved'    => (int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE status='resolved' AND DATE(created_at) BETWEEN :f AND :t", $p),
            'by_category' => Database::fetchAll("SELECT cat.name,COUNT(*) AS total FROM complaints c JOIN categories cat ON cat.id=c.category_id WHERE DATE(c.created_at) BETWEEN :f AND :t GROUP BY cat.id ORDER BY total DESC", $p),
            'by_status'   => Database::fetchAll("SELECT status,COUNT(*) AS total FROM complaints WHERE DATE(created_at) BETWEEN :f AND :t GROUP BY status", $p),
            'by_ward'     => Database::fetchAll("SELECT ward,COUNT(*) AS total FROM complaints WHERE ward IS NOT NULL AND DATE(created_at) BETWEEN :f AND :t GROUP BY ward ORDER BY total DESC LIMIT 10", $p),
        ];
    }

    public static function getLocationData(): array
    {
        return Database::fetchAll("
            SELECT c.id, c.reference_no, c.title, c.status, c.priority,
                   c.latitude, c.longitude, c.location, c.ward,
                   cat.name AS category_name, cat.color AS category_color
            FROM complaints c
            JOIN categories cat ON cat.id=c.category_id
            WHERE c.latitude IS NOT NULL AND c.longitude IS NOT NULL
            ORDER BY c.created_at DESC
        ");
    }

    public static function getWardSummary(): array
    {
        return Database::fetchAll("
            SELECT ward,
                   COUNT(*) AS total,
                   SUM(priority='critical') AS critical,
                   SUM(status IN ('resolved','closed')) AS resolved,
                   SUM(status NOT IN ('resolved','closed')) AS open,
                   ROUND(100*SUM(status IN ('resolved','closed'))/COUNT(*),1) AS resolution_rate
            FROM complaints
            WHERE ward IS NOT NULL
            GROUP BY ward
            ORDER BY total DESC
        ");
    }
}
