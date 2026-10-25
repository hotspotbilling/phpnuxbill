{* Details drawer + license modal. Param: route *}
<div class="mp-drawer mp-wrap" id="mp-drawer" hidden>
    <div class="mp-drawer__backdrop" data-close></div>
    <aside class="mp-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="mp-d-title">
        <header class="mp-drawer__head">
            <div>
                <h3 id="mp-d-title"></h3>
                <small id="mp-d-sub"></small>
            </div>
            <button type="button" class="mp-drawer__close" data-close aria-label="{Lang::T('Close')}">&times;</button>
        </header>
        <div class="mp-drawer__body">
            <div class="mp-d-tags" id="mp-d-tags"></div>
            <p id="mp-d-desc" class="mp-d-desc"></p>
            <div id="mp-d-issues"></div>
            <dl class="mp-d-facts" id="mp-d-facts"></dl>
            <h4 id="mp-d-readme-title" hidden>README</h4>
            <div class="mp-d-readme" id="mp-d-readme" hidden></div>
        </div>
        <footer class="mp-drawer__foot" id="mp-d-actions"></footer>
    </aside>
</div>

<div class="mp-modal mp-wrap" id="mp-license" hidden>
    <div class="mp-drawer__backdrop" data-close></div>
    <form class="mp-modal__panel" method="post" action="" id="mp-license-form">
        <h3><i class="fa fa-key"></i> {Lang::T('License key')}</h3>
        <p id="mp-license-name" class="mp-muted"></p>
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        <input type="text" name="license_key" id="mp-license-key" class="form-control" autocomplete="off"
            placeholder="{Lang::T('Paste your license key')}">
        <p class="mp-muted">{Lang::T('Leave empty and save to remove the key. Extensions that publish a verification endpoint are checked online.')}</p>
        <div class="mp-modal__foot">
            <button type="button" class="mp-filter" data-close>{Lang::T('Cancel')}</button>
            <button type="submit" class="mp-filter is-active">{Lang::T('Save')}</button>
        </div>
    </form>
</div>
