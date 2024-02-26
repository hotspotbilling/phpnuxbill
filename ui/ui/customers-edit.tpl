{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Edit Contact')}</div>
            <div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{$_url}customers/edit-post">
                    <input type="hidden" name="id" value="{$d['id']}">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Username')}</label>
                        <div class="col-md-6">
                            <div class="input-group">
                                {if $_c['country_code_phone']!= ''}
                                <span class="input-group-addon" id="basic-addon1">+</span>
                                {else}
                                <span class="input-group-addon" id="basic-addon1"><i
                                        class="glyphicon glyphicon-phone-alt"></i></span>
                                {/if}
                                <input type="text" class="form-control" name="username" value="{$d['username']}"
                                    required
                                    placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']}{/if} {Lang::T('Phone Number')}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Full Name')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="fullname" name="fullname"
                                value="{$d['fullname']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Email')}</label>
                        <div class="col-md-6">
                            <input type="email" class="form-control" id="email" name="email" value="{$d['email']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Phone Number')}</label>
                        <div class="col-md-6">
                            <div class="input-group">
                                {if $_c['country_code_phone']!= ''}
                                <span class="input-group-addon" id="basic-addon1">+</span>
                                {else}
                                <span class="input-group-addon" id="basic-addon1"><i
                                        class="glyphicon glyphicon-phone-alt"></i></span>
                                {/if}
                                <input type="text" class="form-control" name="phonenumber" value="{$d['phonenumber']}"
                                    placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']}{/if} {Lang::T('Phone Number')}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Password')}</label>
                        <div class="col-md-6">
                            <input type="password" autocomplete="off" class="form-control" id="password" name="password"
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'"
                                value="{$d['password']}">
                            <span class="help-block">{Lang::T('Keep Blank to do not change Password')}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('PPPOE Password')}</label>
                        <div class="col-md-6">
                            <input type="password" autocomplete="off" class="form-control" id="pppoe_password"
                                name="pppoe_password" value="{$d['pppoe_password']}"
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                            <span class="help-block">
                                {Lang::T('User Cannot change this, only admin. if it Empty it will use user password')}
                            </span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Address')}</label>
                        <div class="col-md-6">
                            <textarea name="address" id="address" class="form-control">{$d['address']}</textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Service Type')}</label>
                        <div class="col-md-6">
                            <select class="form-control" id="service_type" name="service_type">
                                <option value="Hotspot" {if $d['service_type'] eq 'Hotspot' }selected{/if}>Hotspot
                                </option>
                                <option value="PPPoE" {if $d['service_type'] eq 'PPPoE' }selected{/if}>PPPoE</option>
                                <option value="Others" {if $d['service_type'] eq 'Others' }selected{/if}>Others</option>
                            </select>
                        </div>
                    </div>
                    <!--Customers Attributes edit start -->
                    {if $customFields}
                    {foreach $customFields as $customField}
                    <div class="form-group">
                        <label class="col-md-2 control-label"
                            for="{$customField.field_name}">{$customField.field_name}</label>
                        <div class="col-md-6">
                            <input class="form-control" type="text" name="custom_fields[{$customField.field_name}]"
                                id="{$customField.field_name}" value="{$customField.field_value}">
                        </div>
                        <input type="checkbox" name="delete_custom_fields[]" value="{$customField.field_name}"> Delete
                    </div>
                    {/foreach}
                    {/if}
                    <!--Customers Attributes edit end -->
                    <!--Customers Attributes add start -->
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Attributes')}</label>
                        <div id="custom-fields-container" class="col-md-6">
                            <button class="btn btn-success btn-sm" type="button"
                                id="add-custom-field">+</button>
                        </div>
                    </div>
                    <!--Customers Attributes add end -->
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary" type="submit">
                                {Lang::T('Save Changes')}
                            </button>
                            Or <a href="{$_url}customers/list">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

{literal}
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function () {
        var customFieldsContainer = document.getElementById('custom-fields-container');
        var addCustomFieldButton = document.getElementById('add-custom-field');

        addCustomFieldButton.addEventListener('click', function () {
            var fieldIndex = customFieldsContainer.children.length;
            var newField = document.createElement('div');
            newField.className = 'form-group';
            newField.innerHTML = `
                <label class="col-md-2 control-label">Name:</label>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="custom_field_name[]" placeholder="Name">
                </div>
                <label class="col-md-2 control-label">Value:</label>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="custom_field_value[]" placeholder="Value">
                </div>
                <div class="col-md-2">
                    <button type="button" class="remove-custom-field btn btn-danger btn-sm">-</button>
                </div>
            `;
            customFieldsContainer.appendChild(newField);
        });

        customFieldsContainer.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-custom-field')) {
                var fieldContainer = event.target.parentNode.parentNode;
                fieldContainer.parentNode.removeChild(fieldContainer);
            }
        });
    });
</script>
{/literal}

{include file="sections/footer.tpl"}