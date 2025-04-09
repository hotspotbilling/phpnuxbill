{include file="sections/header.tpl"}

<div class="row">
	<div class="col-sm-12 col-md-12">
		<div class="panel panel-primary panel-hovered panel-stacked mb30">
			<div class="panel-heading">{Lang::T('Send Personal Message')}</div>
			<div class="panel-body">
				<form class="form-horizontal" method="post" role="form" action="{Text::url('message/send-post')}">
					<div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Customer')}</label>
						<div class="col-md-6">
							<select {if $cust}{else}id="personSelect" {/if} class="form-control select2"
								name="id_customer" style="width: 100%"
								data-placeholder="{Lang::T('Select a customer')}...">
								{if $cust}
									<option value="{$cust['id']}">{$cust['username']} &bull; {$cust['fullname']} &bull;
										{$cust['email']}</option>
								{/if}
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Send Via')}</label>
						<div class="col-md-6">
							<select class="form-control" name="via" id="via">
                                <option value="all" {if $via=='all' }selected{/if}>{Lang::T('All Channels')}</option>
                                <option value="inbox" {if $via=='inbox' }selected{/if}>{Lang::T('Inbox')}</option>
                                <option value="email" {if $via=='email' }selected{/if}>{Lang::T('Email')}</option>
                                <option value="sms" {if $via=='sms' }selected{/if}>{Lang::T('SMS')}</option>
                                <option value="wa" {if $via=='wa' }selected{/if}>{Lang::T('WhatsApp')}</option>
                                <option value="both" {if $via=='both' }selected{/if}>{Lang::T('SMS and WhatsApp')}
                                </option>
                            </select>
						</div>
					</div>
					<div class="form-group" id="subject">
                        <label class="col-md-2 control-label">{Lang::T('Subject')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="subject" id="subject-content" value=""
                                placeholder="{Lang::T('Enter message subject here')}">
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('You can also use the below placeholders here too')}.
                        </p>
                    </div>
					<div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Message')}</label>
						<div class="col-md-6">
							<textarea class="form-control" id="message" name="message"
								placeholder="{Lang::T('Compose your message...')}" rows="5"></textarea>
						</div>
						<p class="help-block col-md-4">
							{Lang::T('Use placeholders:')}
							<br>
							<b>[[name]]</b> - {Lang::T('Customer Name')}
							<br>
							<b>[[user_name]]</b> - {Lang::T('Customer Username')}
							<br>
							<b>[[phone]]</b> - {Lang::T('Customer Phone')}
							<br>
							<b>[[company_name]]</b> - {Lang::T('Your Company Name')}
							<br>
							<b>[[payment_link]]</b> - <a href="{Text::url('docs')}/#Reminder%20with%20payment%20link"
								target="_blank">{Lang::T('Read documentation')}</a>.
						</p>
					</div>

					<div class="form-group">
						<div class="col-lg-offset-2 col-lg-10">
							<button class="btn btn-success"
								onclick="return ask(this, '{Lang::T('Continue the process of sending messages')}?')"
								type="submit">{Lang::T('Send Message')}</button>
							<a href="{Text::url('dashboard')}" class="btn btn-default">{Lang::T('Cancel')}</a>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<script>
    document.getElementById('via').addEventListener('change', function () {
        const via = this.value;
        const subject = document.getElementById('subject');
        const subjectField = document.getElementById('subject-content');

        subject.style.display = (via === 'all' || via === 'email' || via === 'inbox') ? 'block' : 'none';

        switch (via) {
            case 'all':
                subjectField.placeholder = 'Enter a subject for all channels';
                subjectField.required = true; 
                break;
            case 'email':
                subjectField.placeholder = 'Enter a subject for email';
                subjectField.required = true; 
                break;
            case 'inbox':
                subjectField.placeholder = 'Enter a subject for inbox';
                subjectField.required = true; 
                break;
            default:
                subjectField.placeholder = 'Enter message subject here';
                subjectField.required = false;
                break;
        }
    });
</script>

{include file="sections/footer.tpl"}