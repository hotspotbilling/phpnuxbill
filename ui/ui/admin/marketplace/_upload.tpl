{* Params: up_title, up_hint, up_action, up_file_name, up_file_label, up_accept, up_gh (bool), up_enabled (bool, default true) *}
<section class="mp-install" id="upload-panel" style="display:none;">
    <h3><i class="fa fa-cloud-upload"></i> {$up_title}</h3>
    <p>{$up_hint}</p>
    {if isset($up_enabled) && !$up_enabled}
    <p class="mp-note">{Lang::T('PHP ZIP extension is not available.')}</p>
    {else}
    <form method="post" enctype="multipart/form-data" action="{$up_action}">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        <div class="mp-field">
            <label>{$up_file_label}</label>
            <input type="file" name="{$up_file_name}" class="mp-file" accept="{$up_accept}">
        </div>
        {if $up_gh}
        <div class="mp-field">
            <label>{Lang::T('Or GitHub Repository URL')}</label>
            <input type="url" name="gh_url" placeholder="https://github.com/username/repository">
        </div>
        {/if}
        <button type="button" class="mp-submit" {if isset($up_disabled) && $up_disabled}disabled{/if}
            onclick="return ask(this, '{Lang::T('Warning, installing unknown source can damage your server, continue?')}')">
            <i class="fa fa-download"></i> {Lang::T('Install')}
        </button>
    </form>
    {/if}
</section>
