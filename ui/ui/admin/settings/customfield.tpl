{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{Text::url('')}customfield/save">
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-success panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('New Field')}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T("Sequence")}</label>
                        <div class="col-md-8">
                            <input type="number" class="form-control" name="order[]" style="width: 100%" value="99" placeholder="99">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T("Name")}</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="name[]" style="width: 100%" placeholder="Your Salary">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">Placeholder</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="placeholder[]" style="width: 100%" placeholder="this is placeholder">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T("Type")}</label>
                        <div class="col-md-8">
                            <select class="form-control" name="type[]" style="width: 100%">
                                <option value="text">{Lang::T("Text")}</option>
                                <option value="date">{Lang::T("Date")}</option>
                                <option value="time">{Lang::T("Time")}</option>
                                <option value="number">{Lang::T("Number")}</option>
                                <option value="option">{Lang::T("Option")}</option>
                                <!-- <option value="image">{Lang::T("Image")}</option> -->
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T("Option Values")}</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="value[]" style="width: 100%" placeholder="Male,Female">
                            <span class="help-block">{Lang::T("Use comma separated values ​​for this option.")}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T("Registration page")}</label>
                        <div class="col-md-8">
                            <select class="form-control" name="register[]" style="width: 100%">
                                <option value="1">show</option>
                                <option value="0">hide</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T("Required")}</label>
                        <div class="col-md-8">
                            <select class="form-control" name="required[]" style="width: 100%">
                                <option value="1">{Lang::T("Yes")}</option>
                                <option value="0">{Lang::T("No")}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-8 col-md-offset-3">
                            <button class="btn btn-success btn-sm btn-block" type="submit">{Lang::T('Add')}</button>
                            <span class="help-block">{Lang::T("To delete, empty the name")}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            {foreach $fields as $field}
                <div class="panel panel-primary">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="col-md-4 control-label">{Lang::T("Sequence")}</label>
                            <div class="col-md-8">
                                <input type="number" class="form-control" name="order[]" style="width: 100%" value="{$field['order']}" placeholder="99">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">{Lang::T("Name")}</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="name[]" style="width: 100%" placeholder="Your Salary" value="{$field['name']}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Placeholder</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="placeholder[]" style="width: 100%" placeholder="this is placeholder" value="{$field['placeholder']}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">{Lang::T("Type")}</label>
                            <div class="col-md-8">
                                <select class="form-control" name="type[]" style="width: 100%">
                                    <option value="text" {if $field['type'] == 'text'}selected{/if}>{Lang::T("Text")}</option>
                                    <option value="date" {if $field['type'] == 'date'}selected{/if}>{Lang::T("Date")}</option>
                                    <option value="time" {if $field['type'] == 'time'}selected{/if}>{Lang::T("Time")}</option>
                                    <option value="number" {if $field['type'] == 'number'}selected{/if}>{Lang::T("Number")}</option>
                                    <option value="option" {if $field['type'] == 'option'}selected{/if}>{Lang::T("Option")}</option>
                                    <!-- <option value="image" {if $field['type'] == 'image'}selected{/if}>{Lang::T("Image")}</option> -->
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">{Lang::T("Option Values")}</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="value[]" style="width: 100%" placeholder="Male,Female" value="{$field['value']}">
                                <span class="help-block">{Lang::T("Use comma separated values for this option.")}</span>
                            </div>
                        </div>
                        <div class="form-group {if $field['register'] == 1}has-success{/if}">
                            <label class="col-md-4 control-label">{Lang::T("Registration page")}</label>
                            <div class="col-md-8">
                                <select class="form-control" name="register[]" style="width: 100%">
                                    <option value="1" {if $field['register'] == 1}selected{/if}>show</option>
                                    <option value="0" {if $field['register'] != 1}selected{/if}>hide</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group {if $field['required'] == 1}has-error{/if}">
                            <label class="col-md-4 control-label">{Lang::T("Required")}</label>
                            <div class="col-md-8">
                                <select class="form-control" name="required[]" style="width: 100%">
                                    <option value="1" {if $field['required'] == 1}selected{/if}>{Lang::T("Yes")}</option>
                                    <option value="0" {if $field['required'] != 1}selected{/if}>{Lang::T("No")}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            {/foreach}
            <button class="btn btn-success btn-sm btn-block" type="submit">{Lang::T('Save')}</button>
        </div>
    </div>
</form>

{include file="sections/footer.tpl"}
