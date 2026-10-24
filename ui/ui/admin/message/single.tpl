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
					<div class="form-group" id="via">
						<label class="col-md-2 control-label">{Lang::T('Channel')}</label>
						<label class="col-md-1 control-label"><input type="checkbox" id="sms" name="sms" value="1">
							{Lang::T('SMS')}</label>
						<label class="col-md-1 control-label"><input type="checkbox" id="wa" name="wa" value="1">
							{Lang::T('WA')}</label>
						<label class="col-md-1 control-label"><input type="checkbox" id="email" name="email" value="1">
							{Lang::T('Email')}</label>
						<label class="col-md-1 control-label"><input type="checkbox" id="inbox" name="inbox" value="1">
							{Lang::T('Inbox')}</label>
					</div>
					<div class="form-group" id="subject" style="display: none;">
						<label class="col-md-2 control-label">{Lang::T('Subject')}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" name="subject" id="subject-content" value=""
								placeholder="{Lang::T('Enter message subject here')}">
						</div>
						<p class="help-block col-md-4">
							<small>
								{Lang::T('You can also use the below placeholders here too')}.
							</small>
						</p>
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Message')}</label>
						<div class="col-md-6">
							<textarea class="form-control" id="message" name="message"
								placeholder="{Lang::T('Compose your message...')}" rows="5"></textarea>
						</div>
						<p class="help-block col-md-4">
							<small>
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
								<b>[[payment_link]]</b> - <a
									href="{Text::url('docs')}/#Reminder%20with%20payment%20link"
									target="_blank">{Lang::T('Read documentation')}</a>.
							</small>
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
	document.addEventListener('DOMContentLoaded', function () {
		const emailCheckbox = document.getElementById('email');
		const inboxCheckbox = document.getElementById('inbox');
		const subjectDiv = document.getElementById('subject');
		const subjectInput = document.getElementById('subject-content');

		function toggleSubjectField() {
			if (emailCheckbox.checked || inboxCheckbox.checked) {
				subjectDiv.style.display = 'block';
				subjectInput.required = true;
			} else {
				subjectDiv.style.display = 'none';
				subjectInput.required = false;
				subjectInput.value = '';
			}
		}

		emailCheckbox.addEventListener('change', toggleSubjectField);
		inboxCheckbox.addEventListener('change', toggleSubjectField);
	});
</script>

{include file="sections/footer.tpl"}