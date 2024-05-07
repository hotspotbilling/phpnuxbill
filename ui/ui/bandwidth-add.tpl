{include file="sections/header.tpl"}
<div class="row">
  <div class="col-sm-12 col-md-12">
    <div class="panel panel-primary panel-hovered panel-stacked mb30">
      <div class="panel-heading">{Lang::T('Add New Bandwidth')}</div>
      <div class="panel-body">
        <form class="form-horizontal" method="post" role="form" action="{$_url}bandwidth/add-post">
          <div class="form-group">
            <label class="col-md-2 control-label">{Lang::T('Bandwidth Name')}</label>
            <div class="col-md-6">
              <input type="text" class="form-control" id="name" name="name">
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-2 control-label">{Lang::T('Rate Download')}</label>
            <div class="col-md-4">
              <input type="text" class="form-control" id="rate_down" name="rate_down">
            </div>
            <div class="col-md-2">
              <select class="form-control" id="rate_down_unit" name="rate_down_unit">
                <option value="Kbps">Kbps</option>
                <option value="Mbps">Mbps</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-2 control-label">{Lang::T('Rate Upload')}</label>
            <div class="col-md-4">
              <input type="text" class="form-control" id="rate_up" name="rate_up">
            </div>
            <div class="col-md-2">
              <select class="form-control" id="rate_up_unit" name="rate_up_unit">
                <option value="Kbps">Kbps</option>
                <option value="Mbps">Mbps</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-2 control-label">Burst Limit Upload</label>
            <div class="col-md-4">
              <input type="text" class="form-control" name="burst_limit_up" placeholder="Upload">
            </div>
            <div class="col-md-2">
              <select class="form-control" name="burst_limit_up_unit">
                <option value="Kbps">Kbps</option>
                <option value="Mbps">Mbps</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-2 control-label">Burst Limit Download</label>
            <div class="col-md-4">
              <input type="text" class="form-control" name="burst_limit_down" placeholder="Download">
            </div>
            <div class="col-md-2">
              <select class="form-control" name="burst_limit_down_unit">
                <option value="Kbps">Kbps</option>
                <option value="Mbps">Mbps</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-2 control-label">Burst Threshold Upload</label>
            <div class="col-md-4">
              <input type="text" class="form-control" name="burst_threshold_up" placeholder="Upload">
            </div>
            <div class="col-md-2">
              <select class="form-control" name="burst_threshold_up_unit">
                <option value="Kbps">Kbps</option>
                <option value="Mbps">Mbps</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-2 control-label">Burst Threshold Download</label>
            <div class="col-md-4">
              <input type="text" class="form-control" name="burst_threshold_down" placeholder="Download">
            </div>
            <div class="col-md-2">
              <select class="form-control" name="burst_threshold_down_unit">
                <option value="Kbps">Kbps</option>
                <option value="Mbps">Mbps</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-2 control-label">Burst Time</label>
            <div class="col-md-6">
              <input type="text" class="form-control" name="burst_time" placeholder="Burst Time">
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-2 control-label">Priority</label>
            <div class="col-md-6">
              <input type="number" class="form-control" name="priority" placeholder="Priority">
            </div>
          </div>
          <div class="form-group">
            <div class="col-lg-offset-2 col-lg-10">
              <button class="btn btn-primary" type="submit">{Lang::T('Submit')}</button>
              Or <a href="{$_url}bandwidth/list">{Lang::T('Cancel')}</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
{include file="sections/footer.tpl"}
