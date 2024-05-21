{include file="sections/header.tpl"}

<center><a href="https://s.id/standwithpalestine" target="_blank"><img
            src="https://raw.githubusercontent.com/Safouene1/support-palestine-banner/master/banner-support.svg"
            class="img-responsive"></a></center>
<br><br>
<div class="row">
    <div class="col-sm-6">
        <div class="box box-hovered mb20 box-primary">
            <div class="box-header">
                <h3 class="box-title">Master</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <tbody>
                        {foreach $masters as $data}
                            <tr>
                                <td>{nl2br($data['commit']['message'])}</td>
                                <td>{Lang::dateTimeFormat(str_replace(['Z','T'],'',$data['commit']['author']['date']))}</td>
                                <td>
                                    <a href="/update.php?update_url=https://github.com/hotspotbilling/phpnuxbill/archive/{$data['sha']}.zip"
                                        class="btn btn-sm btn-primary">
                                        update
                                    </a>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="box box-hovered mb20 box-primary">
            <div class="box-header">
                <h3 class="box-title">Development</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <tbody>
                        {foreach $devs as $data}
                            <tr>
                                <td>{nl2br($data['commit']['message'])}</td>
                                <td>{Lang::dateTimeFormat(str_replace(['Z','T'],'',$data['commit']['author']['date']))}</td>
                                <td>
                                    <a href="/update.php?update_url=https://github.com/hotspotbilling/phpnuxbill/archive/{$data['sha']}.zip"
                                        class="btn btn-sm btn-primary">
                                        update
                                    </a>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
{include file="sections/footer.tpl"}