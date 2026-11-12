{include file="sections/header.tpl"}

<div class="ls st">
    <div class="ls-top">
        <p class="ls-sub">{count($devices)} {Lang::T('devices installed')}</p>
        <div class="ls-actions">
            <a href="{Text::url('marketplace/devices')}" class="ls-btn"><i class="fa fa-plus" aria-hidden="true"></i> {Lang::T('Get more devices')}</a>
        </div>
    </div>

    <div class="st-cards">
        {foreach $devices as $d}
        <article class="ls-card st-dev">
            <div class="st-dev__head">
                <span class="st-dev__icon"><i class="fa fa-microchip" aria-hidden="true"></i></span>
                <div>
                    <b>{$d['title']|escape}</b>
                    <small>{Lang::T('by')} {$d['author']|escape}</small>
                </div>
            </div>
            <p class="st-dev__desc">{nl2br(strip_tags($d['description']))}</p>
            <div class="st-dev__foot">
                <code class="st-chip">{$d['file']|escape}</code>
                {foreach $d['url'] as $name => $url}
                <a href="{$url|escape}" target="_blank" rel="noopener" class="st-link">{$name|escape} <i class="fa fa-external-link" aria-hidden="true"></i></a>
                {/foreach}
            </div>
        </article>
        {foreachelse}
        <div class="ls-card"><div class="ls-empty"><b>{Lang::T('No devices installed')}</b></div></div>
        {/foreach}
    </div>
</div>

{include file="sections/footer.tpl"}
