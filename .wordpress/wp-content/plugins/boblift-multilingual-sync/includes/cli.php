<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

/**
 * WP-CLI runner for long translation jobs.
 */
class BOBLIFT_MLS_CLI_Command
{
    /**
     * Run a translation job outside web requests.
     *
     * ## OPTIONS
     *
     * <job_id>
     * : Translation job ID.
     *
     * [--limit=<number>]
     * : Maximum items to process in this run. Defaults to all pending items.
     *
     * [--resume]
     * : Resume a paused job before running.
     *
     * ## EXAMPLES
     *
     *     wp boblift-mls run 5 --resume
     *     wp boblift-mls run 5 --limit=10
     *
     * @param array<int,string> $args
     * @param array<string,string|bool> $assoc_args
     */
    public function run(array $args, array $assoc_args): void
    {
        $job_id = isset($args[0]) ? absint($args[0]) : 0;
        if ($job_id <= 0) {
            WP_CLI::error('缺少有效任务 ID。');
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (!empty($assoc_args['resume'])) {
            $resume = boblift_mls_resume_job($job_id);
            if (is_wp_error($resume)) {
                WP_CLI::error($resume->get_error_message());
            }
        }

        $limit = isset($assoc_args['limit']) ? max(1, absint((string) $assoc_args['limit'])) : 0;
        $processed = 0;
        WP_CLI::log('开始执行 BOB-LIFT 翻译任务 #' . $job_id . '。');

        while (true) {
            $job = boblift_mls_get_job($job_id);
            if (!$job) {
                WP_CLI::error('任务不存在。');
            }
            $status = (string) $job['status'];
            if (in_array($status, ['completed', 'completed_with_errors', 'failed'], true)) {
                WP_CLI::success($this->summary($job));
                return;
            }
            if ($status === 'paused') {
                WP_CLI::warning('任务处于暂停状态；需要继续执行请加 --resume。');
                return;
            }
            if ($limit > 0 && $processed >= $limit) {
                WP_CLI::success('已达到本次 limit；' . $this->summary($job));
                return;
            }

            $state = boblift_mls_run_job_batch($job_id, 1);
            if (is_wp_error($state)) {
                WP_CLI::error($state->get_error_message());
            }
            $processed++;
            $state = is_array($state) ? $state : boblift_mls_job_public_state($job_id);
            WP_CLI::log($this->state_line($state));
        }
    }

    /**
     * @param array<string,mixed> $state
     */
    private function state_line(array $state): string
    {
        $total = (int) ($state['total_items'] ?? 0);
        $done = (int) ($state['completed_items'] ?? 0);
        $skipped = (int) ($state['skipped_items'] ?? 0);
        $failed = (int) ($state['failed_items'] ?? 0);
        $processed = $done + $skipped + $failed;
        return sprintf(
            '状态：%s；进度：%d/%d；成功 %d，跳过 %d，失败 %d；当前：%s',
            (string) ($state['status'] ?? ''),
            $processed,
            $total,
            $done,
            $skipped,
            $failed,
            (string) ($state['current_label'] ?? '—')
        );
    }

    /**
     * @param array<string,mixed> $job
     */
    private function summary(array $job): string
    {
        return sprintf(
            '任务 #%d：%s，成功 %d，跳过 %d，失败 %d，总计 %d。',
            (int) ($job['id'] ?? 0),
            boblift_mls_job_status_label((string) ($job['status'] ?? '')),
            (int) ($job['completed_items'] ?? 0),
            (int) ($job['skipped_items'] ?? 0),
            (int) ($job['failed_items'] ?? 0),
            (int) ($job['total_items'] ?? 0)
        );
    }
}

WP_CLI::add_command('boblift-mls', 'BOBLIFT_MLS_CLI_Command');
