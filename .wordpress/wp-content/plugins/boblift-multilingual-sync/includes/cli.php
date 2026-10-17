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
     * Export a translated language.
     *
     * ## OPTIONS
     *
     * <lang>
     * : Polylang language slug.
     *
     * [--file=<path>]
     * : Write JSON to a file instead of stdout.
     */
    public function export(array $args, array $assoc_args): void
    {
        $package = boblift_mls_export_language((string) ($args[0] ?? ''));
        if (is_wp_error($package)) {
            WP_CLI::error($package->get_error_message());
        }
        $json = wp_json_encode($package, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            WP_CLI::error('JSON 编码失败。');
        }
        if (!isset($assoc_args['file'])) {
            WP_CLI::line($json);
            return;
        }
        if (file_put_contents((string) $assoc_args['file'], $json . "\n") === false) {
            WP_CLI::error('无法写入导出文件。');
        }
        WP_CLI::success('已导出到 ' . $assoc_args['file']);
    }

    /**
     * Import a translation package.
     *
     * ## OPTIONS
     *
     * <file>
     * : JSON package path.
     *
     * [--dry-run]
     * : Validate and report without writes.
     *
     * [--no-overwrite]
     * : Protect existing translations and option sections.
     *
     * [--publish]
     * : Preserve package post statuses and publish the language.
     *
     * [--only=<parts>]
     * : Comma-separated posts,terms,options.
     */
    public function import(array $args, array $assoc_args): void
    {
        $file = (string) ($args[0] ?? '');
        $stream = $file !== '' ? @fopen($file, 'rb') : false;
        if ($stream === false) {
            WP_CLI::error('无法读取导入文件。');
        }
        $json = stream_get_contents($stream, BOBLIFT_MLS_TRANSPORT_MAX_BYTES + 1);
        fclose($stream);
        if ($json === false || strlen($json) > BOBLIFT_MLS_TRANSPORT_MAX_BYTES) {
            WP_CLI::error('读取失败或数据包超过 32 MB。');
        }
        $package = json_decode($json, true);
        unset($json);
        if (!is_array($package) || json_last_error() !== JSON_ERROR_NONE) {
            WP_CLI::error('导入文件不是有效 JSON 数据包。');
        }
        $options = ['dry_run' => $assoc_args['dry-run'] ?? false,
            'overwrite' => \WP_CLI\Utils\get_flag_value($assoc_args, 'overwrite', true),
            'publish' => $assoc_args['publish'] ?? false];
        if (isset($assoc_args['only'])) {
            $options['only'] = $assoc_args['only'];
        }
        $report = boblift_mls_import_language($package, $options);
        if (is_wp_error($report)) {
            WP_CLI::error($report->get_error_message());
        }
        WP_CLI::log(sprintf('%s创建 %d，更新 %d，跳过 %d，错误 %d。',
            !empty($options['dry_run']) ? '预演：' : '', $report['created'], $report['updated'], $report['skipped'], count($report['errors'])));
        foreach ($report['warnings'] as $warning) {
            WP_CLI::warning($warning);
        }
        foreach ($report['missing_sources'] as $missing) {
            WP_CLI::warning('缺少源内容：' . $missing);
        }
        foreach ($report['errors'] as $error) {
            WP_CLI::warning($error);
        }
        if ($report['errors'] !== [] || $report['missing_sources'] !== []) {
            WP_CLI::error('导入存在未完成项目。');
        }
        WP_CLI::success(!empty($options['dry_run']) ? '预演完成，未写入。' : '导入完成。');
    }

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
