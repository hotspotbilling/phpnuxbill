<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

require __DIR__ . '/lib.php';
pnb_guard(true);

function pnb_copy_dir($src, $dst)
{
    if (!is_dir($dst) && !mkdir($dst, 0755, true)) {
        throw new Exception("Failed to create directory: $dst");
    }
    foreach (scandir($src) as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        if (is_dir("$src/$f")) {
            pnb_copy_dir("$src/$f", "$dst/$f");
        } elseif (!copy("$src/$f", "$dst/$f")) {
            throw new Exception("Failed to copy $src/$f");
        }
    }
}

function pnb_remove_dir($dir)
{
    if (!is_dir($dir)) {
        return true;
    }
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        is_dir("$dir/$f") && !is_link("$dir/$f") ? pnb_remove_dir("$dir/$f") : @unlink("$dir/$f");
    }
    return @rmdir($dir);
}

$root = pnb_root();
$notes = [];
$posted = $_SERVER['REQUEST_METHOD'] === 'POST' && pnb_token_ok($_POST['token'] ?? '');

// the editable pages (Terms, Privacy, ...) start as copies of pages_template
try {
    if (is_dir("$root/pages_template") && !is_dir("$root/pages")) {
        pnb_copy_dir("$root/pages_template", "$root/pages");
    } elseif (!is_dir("$root/pages")) {
        $notes[] = 'The folder pages is missing: copy pages_template to pages.';
    }
} catch (Throwable $e) {
    $notes[] = 'Could not prepare the pages folder: ' . $e->getMessage();
}

$deleted = false;
if ($posted && ($_POST['do'] ?? '') === 'delete') {
    $deleted = pnb_remove_dir(__DIR__);
    if ($deleted) {
        unset($_SESSION['pnb_done'], $_SESSION['pnb_token']);
        header('location: ../?_route=admin');
        exit;
    }
    $notes[] = 'The install folder could not be deleted from here. Please delete it by hand.';
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base = preg_replace('#/install(/step5\.php)?$#', '', dirname($_SERVER['SCRIPT_NAME']));
$appurl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim($base === '/' ? '' : $base, '/');
pnb_head('Installed', 5, ['all_done' => true]);
?>
<svg class="mark" viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30"/><path d="M19 33l9 9 17-19"/></svg>
<h1>PHPNuxBill is installed</h1>
<p class="lede">Sign in to the admin portal with the user <b>admin</b> and the password you chose.</p>
<?php foreach ($notes as $n) { ?><div class="banner warn"><span class="led"></span><p><?= pnb_h($n) ?></p></div><?php } ?>

<div class="actions" style="margin-top:0">
    <a class="btn" href="<?= pnb_h($appurl) ?>/?_route=admin">Open the admin portal</a>
</div>

<h2>Before you go</h2>
<ol class="next-list">
    <li><b>Delete the install folder.</b> It switches itself off once config.php exists, but there is no reason to keep it.
        <form action="step5.php" method="post" style="margin-top:8px" onsubmit="return confirm('Delete the install folder now?');">
            <input type="hidden" name="token" value="<?= pnb_h(pnb_token()) ?>">
            <input type="hidden" name="do" value="delete">
            <button type="submit" class="btn btn-danger btn-small">Delete it now</button>
        </form></li>
    <li><b>Set up the cron job</b> for expiry and reminders. <a href="https://github.com/hotspotbilling/phpnuxbill/wiki/Cron-Jobs" target="_blank" rel="noopener">How to</a></li>
    <li><b>Link your Mikrotik login page</b> to PHPNuxBill. <a href="https://github.com/hotspotbilling/phpnuxbill/wiki#login-page-mikrotik" target="_blank" rel="noopener">Instructions</a>
        or use the <a href="https://github.com/hotspotbilling/phpnuxbill-mikrotik-login-template" target="_blank" rel="noopener">login template</a>.</li>
    <li><b>Learn how it works.</b> <a href="https://github.com/hotspotbilling/phpnuxbill/wiki/How-It-Works---Cara-kerja" target="_blank" rel="noopener">The short guide</a></li>
</ol>
<?php pnb_foot();
