<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

require __DIR__ . '/lib.php';
pnb_guard();

$form = $_SESSION['pnb_form'] ?? [];
$error = (string) ($_GET['_error'] ?? '');
$messages = [
    'connect' => 'Unable to connect to the database. Check the host, user, password and database name, then try again.',
    'exists' => 'This database already holds PHPNuxBill tables. Installing replaces them and ERASES their data. Tick the box below only if that is what you want.',
    'password' => 'The admin password must be at least 10 characters, and both entries must match.',
    'input' => 'Please check the database fields: the name may only use letters, digits, _ and -.',
    'token' => 'The page expired. Please try again.',
    'import' => 'The database could not be set up. Nothing was written to config.php.',
];
pnb_head('Database', 3, ['bad' => $error !== '' && isset($messages[$error])]);
?>
<h1>Database and administrator</h1>
<p class="lede">Create an empty MySQL database first (your hosting panel has a button for it), then enter its login here.</p>

<?php if ($error !== '' && isset($messages[$error])) { ?>
    <div class="banner bad" role="alert"><span class="led"></span><p><?= pnb_h($messages[$error]) ?><?php if (!empty($_SESSION['pnb_detail'])) { echo '<br><span class="small">' . pnb_h($_SESSION['pnb_detail']) . '</span>'; unset($_SESSION['pnb_detail']); } ?></p></div>
<?php } ?>

<form action="step4.php" method="post" autocomplete="off" id="install-form">
    <input type="hidden" name="token" value="<?= pnb_h(pnb_token()) ?>">

    <div class="form-section">
        <h2 style="margin-top:0">Database</h2>
        <div class="grid2">
            <div class="field">
                <label for="dbhost">Host</label>
                <input type="text" id="dbhost" name="dbhost" required value="<?= pnb_h($form['dbhost'] ?? 'localhost') ?>">
            </div>
            <div class="field">
                <label for="dbname">Database name</label>
                <input type="text" id="dbname" name="dbname" required value="<?= pnb_h($form['dbname'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="dbuser">Username</label>
                <input type="text" id="dbuser" name="dbuser" required value="<?= pnb_h($form['dbuser'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="dbpass">Password</label>
                <div class="with-reveal">
                    <input type="password" id="dbpass" name="dbpass" autocomplete="new-password">
                    <button type="button" class="reveal" data-for="dbpass">Show</button>
                </div>
            </div>
        </div>

        <label class="switch">
            <input type="checkbox" name="radius" value="yes" <?= !empty($form['radius']) ? 'checked' : '' ?>>
            <span class="track"></span>
            <span class="text"><b>Install the FreeRADIUS tables</b>
                <span class="hint">Only if you use <a href="https://github.com/hotspotbilling/phpnuxbill/wiki/FreeRadius" target="_blank" rel="noopener">FreeRADIUS</a> with its own tables. Not needed for <a href="https://github.com/hotspotbilling/phpnuxbill/wiki/FreeRadius-Rest" target="_blank" rel="noopener">FreeRADIUS REST</a>.</span></span>
        </label>

        <div id="wipe-box" <?= $error === 'exists' ? '' : 'hidden' ?>>
            <label class="switch">
                <input type="checkbox" name="wipe" value="yes">
                <span class="track"></span>
                <span class="text"><b>Replace the existing PHPNuxBill tables</b>
                    <span class="hint">All their data is erased. Only tick this for a fresh start.</span></span>
            </label>
        </div>

        <div class="actions" style="margin-top:0">
            <button type="button" class="btn btn-ghost btn-small" id="test-btn">Test the connection</button>
            <span class="test-result" id="test-result" role="status" aria-live="polite"></span>
        </div>
    </div>

    <h2>Administrator</h2>
    <p class="muted small" style="margin-top:-4px">The account <b>admin</b> is created with the password you choose. Keep it somewhere safe.</p>
    <div class="grid2">
        <div class="field">
            <label for="adminpass">Admin password</label>
            <div class="with-reveal">
                <input type="password" id="adminpass" name="adminpass" required minlength="10" autocomplete="new-password">
                <button type="button" class="reveal" data-for="adminpass">Show</button>
            </div>
            <div class="meter" id="meter" data-level="0" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
            <span class="hint" id="strength-text">At least 10 characters.</span>
        </div>
        <div class="field">
            <label for="adminpass2">Repeat the password</label>
            <div class="with-reveal">
                <input type="password" id="adminpass2" name="adminpass2" required minlength="10" autocomplete="new-password">
                <button type="button" class="reveal" data-for="adminpass2">Show</button>
            </div>
            <span class="hint" id="match-text"></span>
        </div>
    </div>

    <div class="actions">
        <button type="submit" class="btn" id="install-btn">Install PHPNuxBill</button>
        <a href="step2.php" class="btn btn-ghost">Back</a>
    </div>
</form>

<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    document.querySelectorAll(".reveal").forEach(function (b) {
        b.addEventListener("click", function () {
            var i = $(b.dataset["for"]), show = i.type === "password";
            i.type = show ? "text" : "password";
            b.textContent = show ? "Hide" : "Show";
        });
    });

    var pw = $("adminpass"), pw2 = $("adminpass2"), meter = $("meter"), txt = $("strength-text"), match = $("match-text");
    function strength(v) {
        var n = 0;
        if (v.length >= 10) { n++; }
        if (v.length >= 14) { n++; }
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) { n++; }
        if (/\d/.test(v) && /[^A-Za-z0-9]/.test(v)) { n++; }
        return v.length < 10 ? Math.min(n, 1) : Math.max(n, 1);
    }
    pw.addEventListener("input", function () {
        var v = pw.value, level = v ? strength(v) : 0;
        meter.dataset.level = level;
        txt.textContent = !v ? "At least 10 characters." : (v.length < 10 ? (10 - v.length) + " more character" + (10 - v.length > 1 ? "s" : "") + " needed." : ["", "Acceptable.", "Good.", "Strong.", "Very strong."][level]);
        check();
    });
    function check() {
        if (!pw2.value) { match.textContent = ""; return; }
        match.textContent = pw.value === pw2.value ? "The passwords match." : "The passwords do not match yet.";
    }
    pw2.addEventListener("input", check);

    var result = $("test-result"), wipe = $("wipe-box");
    $("test-btn").addEventListener("click", function () {
        var f = $("install-form"), body = new URLSearchParams();
        ["token", "dbhost", "dbuser", "dbpass", "dbname"].forEach(function (n) { body.append(n, f.elements[n].value); });
        result.className = "test-result"; result.textContent = "Testing...";
        fetch("step4.php?action=test", { method: "POST", body: body, credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (!j.ok) { result.className = "test-result bad"; result.textContent = j.message; return; }
                if (j.existing) {
                    result.className = "test-result warn";
                    result.textContent = "Connected, but this database already has PHPNuxBill tables.";
                    wipe.hidden = false;
                } else {
                    result.className = "test-result ok";
                    result.textContent = "Connected to MySQL " + j.version + ". The database is ready.";
                }
            })
            .catch(function () { result.className = "test-result bad"; result.textContent = "The test could not run. Reload the page and try again."; });
    });

    $("install-form").addEventListener("submit", function () {
        var b = $("install-btn");
        b.disabled = true;
        b.textContent = "Installing, please wait...";
    });
})();
</script>
<?php pnb_foot();
