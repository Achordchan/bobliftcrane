<?php
/**
 * Where the tools write their rollback backups. Backups hold raw post content, post passwords and meta, so they never go
 * into the public theme folder: the directory is BOBLIFT_BACKUP_DIR, or "boblift-backups" next to the WordPress folder
 * (outside the web root). A directory inside the WordPress folder is refused.
 */
function boblift_tools_backup_dir(): string
{
    $configured = getenv('BOBLIFT_BACKUP_DIR');
    $dir = rtrim($configured !== false && $configured !== '' ? $configured : dirname(untrailingslashit(ABSPATH)) . '/boblift-backups', '/');
    $web_root = rtrim((string) realpath(ABSPATH), '/');
    if (!wp_mkdir_p($dir) || !is_writable($dir)) {
        fwrite(STDERR, "Cannot use the backup directory $dir; nothing was changed. Set BOBLIFT_BACKUP_DIR to a writable folder outside the web root.\n");
        exit(1);
    }
    @chmod($dir, 0700);
    $real = (string) realpath($dir);
    if ($web_root !== '' && ($real === $web_root || str_starts_with($real . '/', $web_root . '/'))) {
        fwrite(STDERR, "The backup directory $real is inside the web root; refusing to write private data there. Set BOBLIFT_BACKUP_DIR to a folder outside it.\n");
        exit(1);
    }
    return $dir;
}
