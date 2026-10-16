<?php
/**
 * Where the tools write their rollback backups. Backups hold raw post content, post passwords and meta, so they must never
 * be web-readable. The folder has to be given explicitly in BOBLIFT_BACKUP_DIR (a location the operator knows is private,
 * outside every web root): the WordPress parent folder is not assumed to be private. A folder inside the WordPress folder or
 * inside the web server's document root (when the CLI knows it) is refused before anything is created or changed.
 */
function boblift_tools_backup_dir(): string
{
    $configured = getenv('BOBLIFT_BACKUP_DIR');
    if ($configured === false || trim($configured) === '') {
        fwrite(STDERR, "Set BOBLIFT_BACKUP_DIR to a private, writable folder outside the web root (for example /var/backups/boblift); nothing was changed.\n");
        exit(1);
    }
    $dir = rtrim(trim($configured), '/');
    // Resolve the location (the nearest existing parent plus the missing tail) before creating or changing anything.
    $existing = $dir;
    $tail = '';
    while ($existing !== '' && $existing !== '/' && !file_exists($existing)) {
        $tail = '/' . basename($existing) . $tail;
        $existing = dirname($existing);
    }
    $resolved = rtrim((string) realpath($existing), '/') . $tail;
    $public_roots = array_filter([realpath(ABSPATH), isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false, realpath(WP_CONTENT_DIR)]);
    foreach ($public_roots as $root) {
        $root = rtrim((string) $root, '/');
        if ($root !== '' && ($resolved === $root || str_starts_with($resolved . '/', $root . '/'))) {
            fwrite(STDERR, "The backup folder $resolved is inside $root, which the web server may serve; refusing to write private data there. Choose a folder outside it; nothing was changed.\n");
            exit(1);
        }
    }
    $created = !is_dir($dir);
    if (!wp_mkdir_p($dir) || !is_writable($dir)) {
        fwrite(STDERR, "Cannot use the backup folder $dir; nothing was changed.\n");
        exit(1);
    }
    if ($created) {
        @chmod($dir, 0700); // only a folder created here is restricted; an existing one keeps its permissions
    }
    return $dir;
}
