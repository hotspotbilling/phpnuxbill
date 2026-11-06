<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

// The old table-by-table migration that used to live here ran without any sign-in and is no longer needed:
// the schema is brought up to date by the updater in the site folder (update.php, SuperAdmin only).
require __DIR__ . '/lib.php';
http_response_code(410);
pnb_head('Updater moved', 0);
?>
<h1>This updater has moved</h1>
<p class="lede">Sign in to the admin portal as <b>SuperAdmin</b>, then open <a href="../update.php">update.php</a>. It downloads the new version, keeps a backup and updates the database. This old page no longer changes anything.</p>
<div class="actions"><a href="../update.php" class="btn">Open the updater</a></div>
<?php pnb_foot();
