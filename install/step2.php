<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

require __DIR__ . '/lib.php';
pnb_guard();

$rows = pnb_requirements();
$blocking = [];
$warnings = 0;
foreach ($rows as $r) {
    if (!$r[2]) {
        $r[1] ? $blocking[] = $r[0] : $warnings++;
    }
}
pnb_head('Requirements', 2, ['bad' => (bool) $blocking]);

function pnb_row_list($rows, $required)
{
    echo '<ul class="rows">';
    foreach ($rows as [$label, $req, $ok, $detail]) {
        if ($req !== $required) {
            continue;
        }
        $class = $ok ? 'ok' : ($req ? 'bad' : 'warn');
        echo '<li class="row ' . $class . '"><span class="led" aria-hidden="true"></span><span>' . pnb_h($label) . '</span><span class="row-detail">' . pnb_h($ok ? ($detail === 'loaded' || $detail === 'writable' ? 'Ready' : $detail) : ($req ? 'Needs fixing: ' . $detail : $detail)) . '</span></li>';
    }
    echo '</ul>';
}
?>
<h1>Your server</h1>
<p class="lede">PHPNuxBill needs these to run. Anything marked in red must be fixed first.</p>

<?php if (!$blocking) { ?>
    <div class="banner ok"><span class="led"></span><p><b>This server can run PHPNuxBill.</b><?= $warnings ? ' ' . $warnings . ' recommended item' . ($warnings > 1 ? 's are' : ' is') . ' missing; you can continue and add them later.' : '' ?></p></div>
<?php } else { ?>
    <div class="banner bad"><span class="led"></span><p><b><?= count($blocking) ?> required check<?= count($blocking) > 1 ? 's' : '' ?> failed:</b> <?= pnb_h(implode('; ', $blocking)) ?>.<br>
        Ask your hosting provider to fix them, or ask for help on Telegram <a href="https://t.me/phpnuxbill">@phpnuxbill</a>, then check again.</p></div>
<?php } ?>

<div class="group-title">Required</div>
<?php pnb_row_list($rows, true); ?>
<div class="group-title">Recommended</div>
<?php pnb_row_list($rows, false); ?>

<div class="actions">
    <?php if (!$blocking) { ?>
        <a href="step3.php" class="btn">Continue</a>
        <a href="step2.php" class="btn btn-ghost">Check again</a>
    <?php } else { ?>
        <a href="step2.php" class="btn">Check again</a>
        <span class="btn btn-ghost is-disabled">Continue</span>
    <?php } ?>
</div>
<?php pnb_foot();
