{if $register}
    <div class="form-group">
        <label>{ucwords(str_replace('_',' ', $field['name']))}</label>
        {if $field['type'] == 'option'}
            <select class="form-control" {if $field['required'] == 1} required{/if} name="{$field['name']}" style="width: 100%">
                {assign var="opts" value=explode(',', $field['value'])}
                {foreach $opts as $opt}
                    <option value="{$opt}">{$opt}</option>
                {/foreach}
            </select>
        {elseif $field['image'] == 'image'}
            <input type="file" class="form-control" {if $field['required'] == 1} required{/if} name="{$field['name']}"
                style="width: 100%" placeholder="{$field['placeholder']}" accept="image/*">
        {else}
            <input type="{$field['type']}" class="form-control" {if $field['required'] == 1} required{/if}
                name="{$field['name']}" value="{$field['value']}" style="width: 100%" placeholder="{$field['placeholder']}">
        {/if}
    </div>
{else}
    <div class="form-group">
        <label class="col-md-3 control-label">{ucwords(str_replace('_',' ', $field['name']))}</label>
        <div class="col-md-9">
            {if $field['type'] == 'option'}
                <select class="form-control" {if $field['required'] == 1} required{/if} name="{$field['name']}"
                    style="width: 100%">
                    {assign var="opts" value=explode(',', $field['value'])}
                    {foreach $opts as $opt}
                        <option value="{$opt}" {if $attrs[$field['name']] == $opt}selected{/if}>{$opt}</option>
                    {/foreach}
                </select>
            {elseif $field['image'] == 'image'}
                <input type="file" class="form-control" {if $field['required'] == 1} required{/if} name="{$field['name']}"
                    style="width: 100%" placeholder="{$field['placeholder']}" accept="image/*">
            {else}
                <input type="{$field['type']}" class="form-control" {if $field['required'] == 1} required{/if}
                    name="{$field['name']}" value="{$attrs[$field['name']]}" style="width: 100%" placeholder="{$field['placeholder']}">
            {/if}
        </div>
    </div>
{/if}