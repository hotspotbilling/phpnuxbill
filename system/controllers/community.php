<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', 'Community');
$ui->assign('_system_menu', 'community');

$action = $routes['1'];
$ui->assign('_admin', $admin);

switch ($action) {
    case 'rollback':
        // the updater is SuperAdmin only, so the list of versions is too
        if ($admin['user_type'] !== 'SuperAdmin') {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        $ui->assign('_title', Lang::T('Rollback Update'));
        $ui->assign('masters', community_commits('master'));
        $ui->assign('devs', community_commits('Development'));
        $ui->assign('current_version', (string) (json_decode((string) @file_get_contents($root_path . 'version.json'), true)['version'] ?? ''));
        $ui->display('admin/rollback.tpl');
        break;
    default:
        $ui->display('admin/community.tpl');
}

/**
 * The latest commits of a branch of the official repository, ready to show. GitHub allows few anonymous requests an hour, so the answer is
 * kept for ten minutes; when GitHub cannot be reached the last answer is used, or an empty list.
 *
 * @return array of [sha, short, date, subject, body, author]
 */
function community_commits($branch)
{
    global $CACHE_PATH;
    $file = (isset($CACHE_PATH) ? $CACHE_PATH : __DIR__ . '/../cache') . '/rollback_' . preg_replace('/[^A-Za-z0-9_-]/', '', $branch) . '.json';
    if (is_file($file) && time() - filemtime($file) < 600) {
        $cached = json_decode((string) file_get_contents($file), true);
        if (is_array($cached)) {
            return $cached;
        }
    }
    $raw = json_decode((string) Http::getData(
        'https://api.github.com/repos/hotspotbilling/phpnuxbill/commits?sha=' . rawurlencode($branch) . '&per_page=100',
        ['User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:125.0) Gecko/20100101 Firefox/125.0']
    ), true);
    $out = [];
    // an error answer (rate limit, not found) is an object with a message, not a list of commits
    foreach (is_array($raw) ? $raw : [] as $c) {
        if (!is_array($c) || empty($c['sha']) || !preg_match('/^[0-9a-f]{7,40}$/', $c['sha']) || empty($c['commit']['message'])) {
            continue;
        }
        $lines = preg_split('/\R/', trim($c['commit']['message']), 2);
        $out[] = [
            'sha' => $c['sha'],
            'short' => substr($c['sha'], 0, 7),
            'date' => str_replace(['Z', 'T'], ['', ' '], (string) ($c['commit']['author']['date'] ?? '')),
            'subject' => trim($lines[0]),
            'body' => trim($lines[1] ?? ''),
            'author' => (string) ($c['commit']['author']['name'] ?? ''),
        ];
    }
    if ($out) {
        @file_put_contents($file, json_encode($out));
        return $out;
    }
    $old = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    return is_array($old) ? $old : [];
}
