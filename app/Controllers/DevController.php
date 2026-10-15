<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Helpers;
use App\Core\View;
use App\Models\Audit;

class DevController
{
    private array $config;

    public function __construct()
    {
        $this->config = require Database::configPath();
        Auth::requireRole(['developer', 'admin']);
        if (Auth::role() !== 'developer' && Auth::role() !== 'admin') {
            exit;
        }
    }

    private function base(string $title): array
    {
        return [
            'title' => $title, 'user' => Auth::user(), 'config' => $this->config,
            'is_dev' => true,
            'flash_success' => Helpers::flash('success'),
            'flash_error' => Helpers::flash('error'),
        ];
    }

    public function health(array $p = []): void
    {
        $checks = [];
        $checks['php'] = ['ok' => true, 'detail' => PHP_VERSION];
        try {
            $v = Database::pdo()->query('SELECT VERSION()')->fetchColumn();
            $checks['database'] = ['ok' => true, 'detail' => (string)$v];
        } catch (\Throwable $e) {
            $checks['database'] = ['ok' => false, 'detail' => 'connect failed'];
        }
        $storage = $this->config['storage']['payment_proofs'];
        $checks['storage'] = ['ok' => is_dir($storage) && is_writable($storage), 'detail' => $storage];
        $checks['rajaongkir'] = ['ok' => true, 'detail' => 'mode=' . ($this->config['rajaongkir']['mode'] ?? 'mock') . ' (key redacted)'];
        $counts = [];
        foreach (['background_jobs' => null, 'integration_requests' => null] as $t => $_) {
            try {
                $counts[$t] = (int)Database::pdo()->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
            } catch (\Throwable) {
                $counts[$t] = -1;
            }
        }
        View::layout('dev/health', $this->base('System Health') + ['checks' => $checks, 'counts' => $counts]);
    }

    public function logs(array $p = []): void
    {
        $corr = trim($_GET['correlation'] ?? '');
        $sql = 'SELECT id, provider, endpoint, correlation_id, response_code, created_at FROM integration_requests';
        $params = [];
        if ($corr !== '') {
            $sql .= ' WHERE correlation_id LIKE :c1 OR endpoint LIKE :c2';
            $like = '%' . $corr . '%';
            $params[':c1'] = $like;
            $params[':c2'] = $like;
        }
        $sql .= ' ORDER BY id DESC LIMIT 100';
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        // Mask anything looking like PII in endpoint params (defense in depth; request bodies excluded by design)
        $rows = array_map(function ($r) {
            $r['endpoint'] = preg_replace('/\d{4,}/', '****', (string)$r['endpoint']);
            return $r;
        }, $st->fetchAll());
        View::layout('dev/logs', $this->base('Integration Logs') + ['rows' => $rows, 'correlation' => $corr]);
    }

    public function jobs(array $p = []): void
    {
        $rows = Database::pdo()->query(
            'SELECT id, job_type, ref_id, status, attempts, max_attempts, last_error, created_at, updated_at
             FROM background_jobs ORDER BY id DESC LIMIT 100'
        )->fetchAll();
        View::layout('dev/jobs', $this->base('Background Jobs') + ['rows' => $rows]);
    }

    public function retryJob(array $p): void
    {
        Csrf::requireValid();
        $id = (int)($p['id'] ?? 0);
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT * FROM background_jobs WHERE id = ? FOR UPDATE');
        $pdo->beginTransaction();
        try {
            $st->execute([$id]);
            $job = $st->fetch();
            if (!$job) {
                throw new \RuntimeException('Job tidak ditemukan.');
            }
            if (!in_array($job['status'], ['failed', 'dead'], true)) {
                throw new \RuntimeException('Hanya job failed/dead yang bisa di-retry.');
            }
            $pdo->prepare('UPDATE background_jobs SET status = "queued", attempts = attempts + 1, updated_at = NOW() WHERE id = ?')
                ->execute([$id]);
            $pdo->commit();
            Audit::log(Auth::id(), Auth::role(), 'job.retry', 'background_jobs', $id, ['status' => $job['status']], ['status' => 'queued']);
            Helpers::flash('success', 'Job di-retry.');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Helpers::flash('error', $e->getMessage());
        }
        Helpers::redirect('/dev/jobs');
    }

    public function auditLogs(array $p = []): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        // Mask PII in actor emails for developer role
        $logs = Audit::list($page);
        if (Auth::role() === 'developer') {
            foreach ($logs as &$l) {
                if (!empty($l['actor_email'])) {
                    $l['actor_email'] = Helpers::maskEmail($l['actor_email']);
                }
            }
            unset($l);
        }
        View::layout('dev/audit', $this->base('Audit Teknis') + ['logs' => $logs, 'page' => $page]);
    }
}
