{include file="sections/header.tpl"}

<div class="cp">
    <div class="cp-head">
        <p>{Lang::T('Keep PHPNuxBill up to date, find help from people who run it, and support the project.')}</p>
    </div>

    <div class="cp-layout">
        <div class="cp-col">

            <section class="cp-card" id="update">
                <div class="cp-card__head">
                    <h2>{Lang::T('Updates')}</h2>
                    <p>{Lang::T('The updater backs up the files it replaces, installs the new version, and then applies the database changes.')}</p>
                </div>
                <div class="cp-card__body">
                    <div class="cp-status" id="cpStatus" role="status" aria-live="polite">
                        <i class="fa fa-refresh fa-spin" aria-hidden="true"></i>
                        <div><b>{Lang::T('Checking for updates')}</b><span>&nbsp;</span></div>
                    </div>
                    <div class="cp-vers">
                        <div class="cp-ver" id="currentVersion"><small>{Lang::T('Installed version')}</small><strong>&hellip;</strong></div>
                        <div class="cp-ver" id="latestVersion"><small>{Lang::T('Latest version')}</small><strong>&hellip;</strong></div>
                    </div>

                    <div class="cp-actions cp-actions--grid">
                        {if $_admin['user_type'] eq 'SuperAdmin'}
                        <a href="./update.php" class="cp-btn cp-btn--primary" id="cpUpdateBtn"><i class="fa fa-cloud-download" aria-hidden="true"></i> {Lang::T('Install Latest Version')}</a>
                        {/if}
                        <a href="https://github.com/hotspotbilling/phpnuxbill/archive/refs/heads/master.zip" target="_blank" rel="noopener" class="cp-btn"><i class="fa fa-download" aria-hidden="true"></i> {Lang::T('Download Latest Version')}</a>
                        {if $_admin['user_type'] eq 'SuperAdmin'}
                        <a href="{Text::url('community/rollback')}" class="cp-btn"><i class="fa fa-history" aria-hidden="true"></i> {Lang::T('Select Old Version')}</a>
                        {/if}
                        <a href="./CHANGELOG.md" target="_blank" class="cp-btn"><i class="fa fa-file-text" aria-hidden="true"></i> {Lang::T('Current Changelog')}</a>
                        <a href="https://github.com/hotspotbilling/phpnuxbill/blob/master/CHANGELOG.md" target="_blank" rel="noopener" class="cp-btn"><i class="fa fa-code-fork" aria-hidden="true"></i> {Lang::T('Repo Changelog')}</a>
                        <a href="https://phpnuxbill.org/docs/using/updating-and-backups/" target="_blank" rel="noopener" class="cp-btn"><i class="fa fa-book" aria-hidden="true"></i> {Lang::T('Update guide')}</a>
                    </div>
                    {if $_admin['user_type'] neq 'SuperAdmin'}
                    <p class="cp-note" style="border-top:0;margin-top:10px;padding-top:0">{Lang::T('Only a SuperAdmin can install an update.')}</p>
                    {/if}

                    {if $_admin['user_type'] eq 'SuperAdmin'}
                    <div class="cp-note">
                        {Lang::T('If you uploaded the update files yourself, the database may need changes too. Apply them here, or run php system/cron/migrate.php on the server for a large database.')}
                        <br><a href="./update.php?step=4" class="cp-btn"><i class="fa fa-database" aria-hidden="true"></i> {Lang::T('Update Database')}</a>
                    </div>
                    {/if}
                </div>
            </section>

            <section class="cp-card">
                <div class="cp-card__head">
                    <h2>{Lang::T('Get help from the community')}</h2>
                    <p>{Lang::T('Join the discussion to find solutions and support from a community ready to help.')}</p>
                </div>
                <div class="cp-card__body">
                    <div class="cp-links">
                        <a class="cp-link" href="https://phpnuxbill.org/docs/" target="_blank" rel="noopener"><i class="fa fa-book" aria-hidden="true"></i><div><b>{Lang::T('Documentation')}</b><span>{Lang::T('Guides for setup, updating and everyday use')}</span></div></a>
                        <a class="cp-link" href="https://forum.phpnuxbill.org" target="_blank" rel="noopener"><i class="fa fa-comments" aria-hidden="true"></i><div><b>{Lang::T('Forum Discussions')}</b><span>{Lang::T('Ask questions and read answers')}</span></div></a>
                        <a class="cp-link" href="https://t.me/phpnuxbill" target="_blank" rel="noopener"><i class="fa fa-paper-plane" aria-hidden="true"></i><div><b>{Lang::T('Telegram Group')}</b><span>{Lang::T('Chat with other operators')}</span></div></a>
                        <a class="cp-link" href="https://github.com/hotspotbilling/phpnuxbill/discussions" target="_blank" rel="noopener"><i class="fa fa-code-fork" aria-hidden="true"></i><div><b>{Lang::T('Github Discussions')}</b><span>{Lang::T('Ideas, questions and show and tell')}</span></div></a>
                        <a class="cp-link" href="https://github.com/hotspotbilling/phpnuxbill/issues" target="_blank" rel="noopener"><i class="fa fa-bug" aria-hidden="true"></i><div><b>{Lang::T('Report a problem')}</b><span>{Lang::T('Open an issue on GitHub')}</span></div></a>
                        <a class="cp-link" href="https://phpnuxbill.org" target="_blank" rel="noopener"><i class="fa fa-globe" aria-hidden="true"></i><div><b>{Lang::T('Website')}</b><span>phpnuxbill.org</span></div></a>
                    </div>
                </div>
            </section>

            <section class="cp-card">
                <div class="cp-card__head">
                    <h2>{Lang::T('Support the project')}</h2>
                    <p>{Lang::T('Your donation will help support and continue the development of PHPNuxBill.')}</p>
                </div>
                <div class="cp-card__body">
                    <div class="cp-grid2">
                        <div class="cp-box">
                            <b>{Lang::T('Chat with Me — Paid Support $50')}</b>
                            <p>{Lang::T('Confirm your donation to continue this paid support. Or, ask about alternative donations available to suit your needs.')}</p>
                            <a href="https://t.me/ibnux" target="_blank" rel="noopener" class="cp-btn"><i class="fa fa-paper-plane" aria-hidden="true"></i> {Lang::T('Telegram')}</a>
                        </div>
                        <div class="cp-box">
                            <b>{Lang::T('WhatsApp Gateway and Free Telegram Bot')}</b>
                            <p>{Lang::T('Connect your PHPNuxBill to WhatsApp efficiently using WhatsApp Gateway. Also, create Telegram bots easily and practically.')}</p>
                            <a href="https://wa.nux.my.id/login" target="_blank" rel="noopener" class="cp-btn"><i class="fa fa-comment" aria-hidden="true"></i> wa.nux.my.id</a>
                        </div>
                    </div>

                    <details>
                        <summary>{Lang::T('Donations')} (Indonesia)</summary>
                        <dl>
                            <dt>BCA</dt><dd>5410-454-825</dd>
                            <dt>Mandiri</dt><dd>163-000-1855-793</dd>
                            <dt>{Lang::T('Account Name')}</dt><dd>Ibnu Maksum</dd>
                        </dl>
                        <div class="cp-actions" style="padding:0 14px 14px">
                            <a href="https://trakteer.id/ibnux" target="_blank" rel="noopener" class="cp-btn">Trakteer</a>
                            <a href="https://karyakarsa.com/ibnux/support" target="_blank" rel="noopener" class="cp-btn">Karyakarsa</a>
                        </div>
                    </details>
                    <details>
                        <summary>{Lang::T('Donations other than')} Indonesia</summary>
                        <dl>
                            <dt>Bank</dt><dd>Bank Central Asia, Jakarta</dd>
                            <dt>{Lang::T('Account Number')}</dt><dd>5410-454-825</dd>
                            <dt>SWIFT/BIC</dt><dd>CENAIDJA</dd>
                            <dt>{Lang::T('Account Name')}</dt><dd>Ibnu Maksum</dd>
                        </dl>
                        <div class="cp-actions" style="padding:0 14px 14px">
                            <a href="https://paypal.me/ibnux" target="_blank" rel="noopener" class="cp-btn">PayPal</a>
                        </div>
                    </details>
                </div>
            </section>
        </div>

        <div class="cp-col">
            <section class="cp-card cp-about">
                <div class="cp-card__head"><h2>PHPNuxBill</h2></div>
                <div class="cp-card__body">
                    <p>{Lang::T('is a Hotspot and PPPoE billing platform for Mikrotik developed using PHP. The application uses Mikrotik API to communicate with the router, ensuring efficient and easy integration. If you feel you get more benefits from this application, we would greatly appreciate your contribution through donation.')}</p>
                    <a href="https://github.com/hotspotbilling/phpnuxbill" target="_blank" rel="noopener" class="cp-btn"><i class="fa fa-code-fork" aria-hidden="true"></i> {Lang::T('Watch project')}</a>
                </div>
            </section>

            <section class="cp-card cp-contrib">
                <div class="cp-card__head"><h2>{Lang::T('Contributors')}</h2></div>
                <div class="cp-card__body">
                    <a href="https://github.com/hotspotbilling/phpnuxbill/graphs/contributors" target="_blank" rel="noopener">
                        <img src="https://contrib.rocks/image?repo=hotspotbilling/phpnuxbill&amp;columns=8" alt="{Lang::T('Contributors')}" loading="lazy">
                    </a>
                </div>
            </section>

            <section class="cp-card">
                <div class="cp-card__head"><h2>{Lang::T('Credits')}</h2></div>
                <div class="cp-card__body">
                    <div class="cp-chips">
                        <a class="cp-chip" href="https://getbootstrap.com/docs/3.4/" target="_blank" rel="noopener">Bootstrap V3</a>
                        <a class="cp-chip" href="https://adminlte.io/themes/v3/" target="_blank" rel="noopener">Admin LTE V3</a>
                        <a class="cp-chip" href="https://www.smarty.net/" target="_blank" rel="noopener">Smarty Template</a>
                        <a class="cp-chip" href="https://idiorm.readthedocs.io/" target="_blank" rel="noopener">PHP IdiORM</a>
                        <a class="cp-chip" href="https://mpdf.github.io/" target="_blank" rel="noopener">PHP mPDF</a>
                        <a class="cp-chip" href="https://phpqrcode.sourceforge.net/" target="_blank" rel="noopener">PHP QRCode</a>
                        <a class="cp-chip" href="https://github.com/pear2/Net_RouterOS" target="_blank" rel="noopener">PHP Net_RouterOS</a>
                        <a class="cp-chip" href="https://summernote.org/" target="_blank" rel="noopener">Summernote</a>
                        <a class="cp-chip" href="https://github.com/PHPMailer/PHPMailer/" target="_blank" rel="noopener">PHP Mailer</a>
                    </div>
                </div>
            </section>

            <div class="cp-banner" style="text-align:center">
                <a href="https://s.id/standwithpalestine" target="_blank" rel="noopener"><img src="https://raw.githubusercontent.com/Safouene1/support-palestine-banner/master/banner-support.svg" alt="Support Palestine"></a>
            </div>
        </div>
    </div>
</div>

{literal}
<script>
window.addEventListener('DOMContentLoaded', function () {
    var status = document.getElementById('cpStatus');
    var cur = document.getElementById('currentVersion');
    var lat = document.getElementById('latestVersion');
    var current = null, latest = null;

    function setVer(box, v) { box.querySelector('strong').textContent = v || '?'; }
    function cmp(a, b) {
        var x = String(a).split('.'), y = String(b).split('.');
        for (var i = 0; i < Math.max(x.length, y.length); i++) {
            var d = (parseInt(x[i], 10) || 0) - (parseInt(y[i], 10) || 0);
            if (d) { return d < 0 ? -1 : 1; }
        }
        return 0;
    }
    function show(kind, icon, title, text) {
        status.className = 'cp-status' + (kind ? ' is-' + kind : '');
        status.innerHTML = '<i class="fa ' + icon + '" aria-hidden="true"></i><div><b></b><span></span></div>';
        status.querySelector('b').textContent = title;
        status.querySelector('span').textContent = text;
    }
    function decide() {
        if (current === null || latest === null) { return; }
        if (current === false) { return; }
        if (latest === false) {
            show('', 'fa-question-circle', 'Could not check for updates', 'The latest version could not be read from GitHub. Try again later.');
            return;
        }
        var c = cmp(current, latest);
        if (c < 0) {
            show('new', 'fa-arrow-circle-up', 'An update is available', current + ' to ' + latest);
        } else if (c === 0) {
            show('ok', 'fa-check-circle', 'PHPNuxBill is up to date', 'You are running ' + current + '.');
        } else {
            show('ok', 'fa-check-circle', 'You are ahead of the public release', 'This build (' + current + ') is newer than ' + latest + '.');
        }
    }

    fetch('./version.json?' + Date.now()).then(function (r) { return r.json(); }).then(function (d) {
        current = d.version; setVer(cur, current); decide();
    }).catch(function () { current = false; setVer(cur, '?'); status.querySelector('b').textContent = 'Could not read the installed version'; });

    fetch('https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/version.json?' + Date.now()).then(function (r) { return r.json(); }).then(function (d) {
        latest = d.version; setVer(lat, latest); decide();
    }).catch(function () { latest = false; setVer(lat, '?'); decide(); });
});
</script>
{/literal}
{include file="sections/footer.tpl"}
