{*
    Top-bar language switch (admin): included by admin/header.tpl and the shadcn theme's header.
    $ls_size icon size in px, $ls_wrap true to wrap the icon in <i class="toggle-icon"> as the default theme's other icons are.
    Choosing a language goes to language/set/<name> (system/controllers/language.php).
*}
{$ls_langs = Lang::available()}
{$ls_iso = Lang::getIsoLang()}
{capture assign=ls_svg}<svg xmlns="http://www.w3.org/2000/svg" width="{$ls_size|default:22}" height="{$ls_size|default:22}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-language"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 5h7"/><path d="M9 3v2c0 4.418 -2.239 8 -5 8"/><path d="M5 9c0 2.144 2.952 3.908 6.7 4"/><path d="M12 20l4 -9l4 9"/><path d="M19.1 18h-6.2"/></svg>{/capture}
<li class="dropdown ls-lang">
    <a class="toggle-container" href="#" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" title="{Lang::T('Language')}" aria-label="{Lang::T('Language')}">
        {if $ls_wrap|default:false}<i class="toggle-icon">{$ls_svg nofilter}</i>{else}{$ls_svg nofilter}{/if}
    </a>
    <ul class="dropdown-menu ls-lang__menu">
        <li class="ls-lang__head">{Lang::T('Language')}</li>
        {if ($ls_langs|count) > 8}
        <li class="ls-lang__find"><input type="search" class="ls-lang__q" autocomplete="off" placeholder="{Lang::T('Search')}..." aria-label="{Lang::T('Search')}"></li>
        {/if}
        <li class="ls-lang__list">
            <ul>
                {foreach $ls_langs as $lg}
                {$ls_label = $lg|replace:'-':' '|replace:'_':' '|capitalize}
                <li{if $_c['language'] == $lg} class="is-current"{/if} data-name="{$ls_label|lower|escape}">
                    <a href="{Text::url('language/set/', $lg)}"{if $_c['language'] == $lg} aria-current="true"{/if}>
                        <span class="ls-lang__code">{($ls_iso[$lg]|default:substr($lg, 0, 2))|upper|escape}</span>
                        <span class="ls-lang__name">{$ls_label|escape}</span>
                        {if $_c['language'] == $lg}<i class="fa fa-check-circle ls-lang__tick" aria-hidden="true"></i>{/if}
                    </a>
                </li>
                {/foreach}
            </ul>
        </li>
    </ul>
</li>
<style>
    /* the menu colours itself (the framework's dropdown is white, whatever the page's mode) */
    .navbar-nav > li.ls-lang > .ls-lang__menu, .navbar-nav .open > .ls-lang__menu { min-width: 230px; padding: 6px; left: auto !important; right: 0 !important; margin-top: 6px; background: #fff !important; color: #1f2937 !important; border: 1px solid rgba(128, 128, 128, .28) !important; border-radius: var(--radius, 12px) !important; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, .18) !important; }
    .dark-mode .navbar-nav > li.ls-lang > .ls-lang__menu, .dark-mode .navbar-nav .open > .ls-lang__menu, .dark .navbar-nav > li.ls-lang > .ls-lang__menu, html[data-theme="dark"] .navbar-nav > li.ls-lang > .ls-lang__menu { background: #141a26 !important; color: #e5e9f0 !important; border-color: rgba(255, 255, 255, .12) !important; box-shadow: 0 10px 30px rgba(0, 0, 0, .5) !important; }
    /* on a phone the framework turns the menu into a static, full-width block: it stays a floating menu */
    @media (max-width: 767px) { .navbar-nav .open > .ls-lang__menu { position: absolute; float: none; width: auto; max-width: calc(100vw - 24px); } .navbar-nav > li.ls-lang { position: static; } .navbar-nav .open > .ls-lang__menu { right: 12px !important; } }
    .ls-lang__menu a, .ls-lang__menu a:hover, .ls-lang__menu a:focus { color: inherit; }
    .ls-lang > .toggle-container { border-radius: var(--radius, 12px); }
    .ls-lang__head { padding: 6px 10px 8px; font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; opacity: .6; }
    .ls-lang__find { padding: 0 4px 6px; }
    .ls-lang__q { width: 100%; box-sizing: border-box; padding: 6px 10px; font-size: 13px; color: inherit; background: transparent; border: 1px solid rgba(128, 128, 128, .35); border-radius: calc(var(--radius, 12px) - 4px); outline: 0; }
    .ls-lang__q:focus { border-color: rgba(128, 128, 128, .8); }
    .ls-lang__list { max-height: 300px; overflow-y: auto; }
    .ls-lang__list ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 2px; }
    .ls-lang__list li { margin: 0; padding: 0; }
    .ls-lang__list a { display: flex; align-items: center; gap: 10px; margin: 0; padding: 7px 10px; border-radius: calc(var(--radius, 12px) - 4px) !important; white-space: nowrap; }
    .ls-lang__list a:hover, .ls-lang__list a:focus { background: rgba(128, 128, 128, .16); color: inherit; text-decoration: none; }
    .ls-lang__list .is-current a { font-weight: 600; background: rgba(128, 128, 128, .12); }
    .ls-lang__code { min-width: 34px; padding: 1px 6px; text-align: center; font-size: 11px; font-weight: 600; line-height: 18px; border-radius: calc(var(--radius, 12px) - 8px); background: rgba(128, 128, 128, .2); }
    .ls-lang__name { flex: 1; }
    .ls-lang__tick { font-size: 16px; opacity: .85; }
    .ls-lang__list li[hidden] { display: none; }
</style>
<script>
    (function () {
        var box = document.currentScript.previousElementSibling.previousElementSibling; // the <li class="ls-lang">
        var q = box && box.querySelector('.ls-lang__q');
        if (!q) return;
        q.addEventListener('click', function (e) { e.stopPropagation(); }); // typing must not close the menu
        q.addEventListener('input', function () {
            var v = q.value.trim().toLowerCase();
            box.querySelectorAll('.ls-lang__list li').forEach(function (li) {
                li.hidden = v !== '' && (li.getAttribute('data-name') || '').indexOf(v) === -1;
            });
        });
    })();
</script>
