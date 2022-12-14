{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-6">
        <div class="box box-hovered mb20 box-primary">
            <div class="box-header">
                <h3 class="box-title">Discussions</h3>
            </div>
            <div class="box-body">Get help from community</div>
            <div class="box-footer">
                <a href="https://github.com/hotspotbilling/phpnuxbill/discussions" target="_blank"
                    class="btn btn-primary btn-lg btn-block"><i class="ion ion-chatboxes"></i> Chat Now</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="box box-hovered mb20 box-primary">
            <div class="box-header">
                <h3 class="box-title">Feedback</h3>
            </div>
            <div class="box-body">
                Feedback and Bug Report
            </div>
            <div class="box-footer">
                <a href="https://github.com/hotspotbilling/phpnuxbill/issues" target="_blank"
                    class="btn btn-primary btn-lg btn-block"><i class="ion ion-chatboxes"></i> Give Feedback</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-6">
        <div class="box box-hovered mb20 box-primary">
            <div class="box-header">
                <h3 class="box-title">Donasi</h3>
            </div>
            <div class="box-body">Untuk pengembangan lebih baik, donasi ke iBNuX, donasi akan membantu terus
                pengembangan aplikasi</div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <td>BCA</td>
                            <td>5410-454-825</td>
                        </tr>
                        <tr>
                            <td>Mandiri</td>
                            <td>163-000-1855-793</td>
                        </tr>
                        <tr>
                            <td>Atas nama</td>
                            <td>Ibnu Maksum</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="box-footer">
                <a href="https://trakteer.id/ibnux" target="_blank"
                    class="btn btn-primary btn-lg btn-block">Trakteer</a>
                <a href="https://karyakarsa.com/ibnux/support" target="_blank"
                    class="btn btn-primary btn-lg btn-block">karyakarsa</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="box box-hovered mb20 box-primary">
            <div class="box-header">
                <h3 class="box-title">Donations</h3>
            </div>
            <div class="box-body">
                Donations will help to continue phpnuxbill development
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <td>Bank Central Asia</td>
                            <td>5410-454-825</td>
                        </tr>
                        <tr>
                            <td>SWIFT/BIC</td>
                            <td>CENAIDJA</td>
                        </tr>
                        <tr>
                            <td>Jakarta</td>
                            <td>Indonesia</td>
                        </tr>
                        <tr>
                            <td>Account Name</td>
                            <td>Ibnu Maksum</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="box-footer">
                <a href="https://paypal.me/ibnux" target="_blank" class="btn btn-primary btn-lg btn-block">Paypal</a>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-6">
        <div class="box box-hovered mb20 box-primary">
            <div class="box-header">
                <h3 class="box-title">Chat with me</h3>
            </div>
            <div class="box-body">Paid Support?<br>donation confirmation?<br>Or ask any Donation Alternative</div>
            <div class="box-footer">
                <a href="https://t.me/ibnux" target="_blank" class="btn btn-primary btn-lg btn-block">Telegram</a>
                <a href="https://twitter.com/messages/compose?recipient_id=20653807&text=Hello+i+am+phpnuxbill+user"
                    target="_blank" class="btn btn-primary btn-lg btn-block">Twitter</a>
                <a href="https://m.me/ibnumaksum" target="_blank" class="btn btn-primary btn-lg btn-block">Facebook
                    Messenger</a>
                <a href="https://keybase.io/ibnux" target="_blank" class="btn btn-primary btn-lg btn-block">Keybase</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="box box-primary box-hovered mb20 activities">
            <div class="box-header">
                <h3 class="box-title">PHPNUXBILL</h3>
            </div>
            <div class="box-body">
                {$_L['Welcome_Text_Admin']}
            </div>
            <div class="box-footer" id="currentVersion">ver</div>
            <div class="box-footer" id="latestVersion">ver</div>
            <div class="box-footer">
                <a href="https://github.com/hotspotbilling/phpnuxbill/releases" target="_blank"
                    class="btn btn-primary btn-lg btn-block">Get Latest Version</a>
            </div>
        </div>
        <div class="box box-primary box-hovered mb20 activities">
            <div class="box-header">
                <h3 class="box-title">Free WhatsApp Gateway and Telegram Bot creater</h3>
            </div>
            <div class="box-body">
                There is a Telegram bot wizard in here
            </div>
            <div class="box-footer">
                <a href="https://wa.nux.my.id/login" target="_blank"
                    class="btn btn-primary btn-lg btn-block">wa.nux.my.id</a>
            </div>
        </div>
    </div>
</div>
<script>
    window.addEventListener('DOMContentLoaded', function() {
        $.getJSON("./version.json?" + Math.random(), function(data) {
            $('#currentVersion').html('Current Version: ' + data.version);
        });
        $.getJSON("https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/version.json?" + Math
            .random(),
            function(data) {
                $('#latestVersion').html('Latest Version: ' + data.version);
            });
    });
</script>
{include file="sections/footer.tpl"}