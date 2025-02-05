{include file="sections/header.tpl"}
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">


<div class="row">
	<div class="col-sm-12 col-md-12">
		{if $page>0 && $totalCustomers>0}
			<div class="alert alert-info" role="alert"><span class="loading"></span> {Lang::T("Sending message in progress. Don't close this page.")}</div>
		{/if}
		<div class="panel panel-primary panel-hovered panel-stacked mb30 {if $page>0 && $totalCustomers >0}hidden{/if}">
			<div class="panel-heading">{Lang::T('Send Bulk Message')}</div>
			<div class="panel-body">
				<form class="form-horizontal" method="get" role="form" id="bulkMessageForm" action="">
					<input type="hidden" name="page" value="{if $page>0 && $totalCustomers==0}-1{else}{$page}{/if}">
					<div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Group')}</label>
						<div class="col-md-6">
							<select class="form-control" name="group" id="group">
								<option value="all" {if $group == 'all'}selected{/if}>{Lang::T('All Customers')}
								</option>
								<option value="new" {if $group == 'new'}selected{/if}>{Lang::T('New Customers')}
								</option>
								<option value="expired" {if $group == 'expired'}selected{/if}>
									{Lang::T('Expired Customers')}</option>
								<option value="active" {if $group == 'active'}selected{/if}>
									{Lang::T('Active Customers')}</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Send Via')}</label>
						<div class="col-md-6">
							<select class="form-control" name="via" id="via">
								<option value="sms" {if $via == 'sms'}selected{/if}>{Lang::T('SMS')}</option>
								<option value="wa" {if $via == 'wa'}selected{/if}>{Lang::T('WhatsApp')}</option>
								<option value="both" {if $via == 'both'}selected{/if}>{Lang::T('SMS and WhatsApp')}
								</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Message per time')}</label>
						<div class="col-md-6">
							<select class="form-control" name="batch" id="batch">
								<option value="5" {if $batch == '5'}selected{/if}>{Lang::T('5 Messages')}</option>
								<option value="10" {if $batch == '10'}selected{/if}>{Lang::T('10 Messages')}</option>
								<option value="15" {if $batch == '15'}selected{/if}>{Lang::T('15 Messages')}</option>
								<option value="20" {if $batch == '20'}selected{/if}>{Lang::T('20 Messages')}</option>
								<option value="30" {if $batch == '30'}selected{/if}>{Lang::T('30 Messages')}</option>
								<option value="40" {if $batch == '40'}selected{/if}>{Lang::T('40 Messages')}</option>
								<option value="50" {if $batch == '50'}selected{/if}>{Lang::T('50 Messages')}</option>
								<option value="60" {if $batch == '60'}selected{/if}>{Lang::T('60 Messages')}</option>
							</select>{Lang::T('Use 20 and above if you are sending to all customers to avoid server time out')}
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Delay')}</label>
						<div class="col-md-6">
							<select class="form-control" name="delay" id="delay">
								<option value="1" {if $delay == '1'}selected{/if}>{Lang::T('No Delay')}</option>
								<option value="5" {if $delay == '5'}selected{/if}>{Lang::T('5 Seconds')}</option>
								<option value="10" {if $delay == '10'}selected{/if}>{Lang::T('10 Seconds')}</option>
								<option value="15" {if $delay == '15'}selected{/if}>{Lang::T('15 Seconds')}</option>
								<option value="20" {if $delay == '20'}selected{/if}>{Lang::T('20 Seconds')}</option>
							</select>{Lang::T('Use at least 5 secs if you are sending to all customers to avoid being banned by your message provider')}
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Message')}</label>
						<div class="col-md-6">
							<textarea class="form-control" id="message" name="message" required
								placeholder="{Lang::T('Compose your message...')}" rows="5">{$message}</textarea>
							<input name="test" type="checkbox">
							{Lang::T('Testing [if checked no real message is sent]')}
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
						</p>
					</div>
					<div class="form-group">
						<div class="col-lg-offset-2 col-lg-10">
							{if $page >= 0}
								<button class="btn btn-success" id="submit" type="submit" name=send value=now>
									{Lang::T('Send Message')}</button>
							{else}
								<button class="btn btn-success"
									onclick="return ask(this, 'Continue the process of sending mass messages?')"
									type="submit" name=send value=now>
									{Lang::T('Send Message')}</button>
							{/if}
							<a href="{Text::url('dashboard')}" class="btn btn-default">{Lang::T('Cancel')}</a>
						</div>
					</div>
				</form>

			</div>
		</div>
	</div>
</div>

{if $batchStatus}
	<p><span class="label label-success">{Lang::T('Total SMS Sent')}: {$totalSMSSent}</span> <span
			class="label label-danger">{Lang::T('Total SMS
		Failed')}: {$totalSMSFailed}</span> <span class="label label-success">{Lang::T('Total WhatsApp Sent')}:
			{$totalWhatsappSent}</span> <span class="label label-danger">{Lang::T('Total WhatsApp Failed')}:
			{$totalWhatsappFailed}</span></p>
{/if}
<div class="box">
	<div class="box-header">
		<h3 class="box-title">{Lang::T('Message Results')}</h3>
	</div>
	<!-- /.box-header -->
	<div class="box-body">
		<table id="messageResultsTable" class="table table-bordered table-striped table-condensed">
			<thead>
				<tr>
					<th>{Lang::T('Name')}</th>
					<th>{Lang::T('Phone')}</th>
					<th>{Lang::T('Message')}</th>
					<th>{Lang::T('Status')}</th>
				</tr>
			</thead>
			<tbody>
				{foreach $batchStatus as $customer}
					<tr>
						<td>{$customer.name}</td>
						<td>{$customer.phone}</td>
						<td>{$customer.message}</td>
						<td>{$customer.status}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
	<!-- /.box-body -->
</div>
<!-- /.box -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script>
	var $j = jQuery.noConflict();

	$j(document).ready(function() {
		$j('#messageResultsTable').DataTable();
	});

	{if $page>0 && $totalCustomers >0}
		setTimeout(() => {
			document.getElementById('submit').click();
		}, {$delay}000);
	{/if}
	{if $page>0 && $totalCustomers==0}
		Swal.fire({
			icon: 'success',
			title: 'Bulk Send Done',
			position: 'top-end',
			showConfirmButton: false,
			timer: 5000,
			timerProgressBar: true,
			didOpen: (toast) => {
				toast.addEventListener('mouseenter', Swal.stopTimer)
				toast.addEventListener('mouseleave', Swal.resumeTimer)
			}
		});
	{/if}
</script>




{include file="sections/footer.tpl"}