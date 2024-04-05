<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{Lang::T('Register')} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

    <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="ui/ui/styles/modern-AdminLTE.min.css">
    <link rel="stylesheet" href="ui/ui/styles/sweetalert2.min.css" />
    <script src="ui/ui/scripts/sweetalert2.all.min.js"></script>



</head>

<body id="app" class="app off-canvas body-full">

    <div class="container">
        <div class="hidden-xs" style="height:150px"></div>
        <div class="form-head mb20">
            <h1 class="site-logo h2 mb5 mt5 text-center text-uppercase text-bold"
                style="text-shadow: 2px 2px 4px #757575;">{$_c['CompanyName']}</h1>
            <hr>
        </div>
        {if isset($notify)}
            <script>
                // Display SweetAlert toast notification
                Swal.fire({
                    icon: '{if $notify_t == "s"}success{else}warning{/if}',
                    title: '{$notify}',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });
            </script>
        {/if}
        <div class="row">
            <div class="col-md-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">{Lang::T('Registration Info')}</div>
                    <div class="panel-body">
                        {include file="$_path/../pages/Registration_Info.html"}
                    </div>
                </div>
            </div>
            <form class="form-horizontal" action="{$_url}register/post" method="post">
                <div class="col-md-4">
                    <div class="panel panel-primary">
                        <div class="panel-heading">1. {Lang::T('Register as Member')}</div>
                        <div class="panel-body">
                            <div class="form-container">
                                <div class="md-input-container">
                                    <label>{if $_c['country_code_phone']!= ''}{Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}</label>
                                    <div class="input-group">
                                        {if $_c['country_code_phone']!= ''}
                                            <span class="input-group-addon" id="basic-addon1"><i
                                                    class="glyphicon glyphicon-phone-alt"></i></span>
                                        {else}
                                            <span class="input-group-addon" id="basic-addon1"><i
                                                    class="glyphicon glyphicon-user"></i></span>
                                        {/if}
                                        <input type="text" class="form-control" name="username"
                                            placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}">
                                    </div>
                                </div>
                                <div class="md-input-container md-float-label">
                                    <label>{Lang::T('Full Name')}</label>
                                    <input type="text" required class="form-control" id="fullname" value="{$fullname}"
                                        name="fullname">
                                </div>
                                <div class="md-input-container md-float-label">
                                    <label>{Lang::T('Email')}</label>
                                    <input type="text" class="form-control" id="email" placeholder="xxxxxxx@xxxx.xx"
                                        value="{$email}" name="email">
                                </div>
                                <div class="md-input-container md-float-label">
                                    <label>{Lang::T('Address')}</label>
                                    <input type="text" name="address" id="address" value="{$address}"
                                        class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel panel-primary">
                        <div class="panel-heading">2. {Lang::T('Password')}</div>
                        <div class="panel-body">
                            <div class="form-container">
                                <div class="md-input-container md-float-label">
                                    <label>{Lang::T('Password')}</label>
                                    <input type="password" required class="form-control" id="password" name="password">
                                </div>
                                <div class="md-input-container md-float-label">
                                    <label>{Lang::T('Confirm Password')}</label>
                                    <input type="password" required class="form-control" id="cpassword"
                                        name="cpassword">
                                </div>
                                <br>
                                <div class="btn-group btn-group-justified mb15">
                                    <div class="btn-group">
                                        <a href="{$_url}login" class="btn btn-warning">{Lang::T('Cancel')}</a>
                                    </div>
                                    <div class="btn-group">
                                        <button class="btn btn-success" type="submit">{Lang::T('Register')}</button>
                                    </div>
                                </div>
                                <br>
                                <center>
                                    <a href="javascript:showPrivacy()">Privacy</a>
                                    &bull;
                                    <a href="javascript:showTaC()">T &amp; C</a>
                                </center>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="HTMLModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="HTMLModal_konten"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">&times;</button>
                </div>
            </div>
        </div>
    </div>
    {if $_c['tawkto'] != ''}
        <!--Start of Tawk.to Script-->
        <script type="text/javascript">
            var Tawk_API = Tawk_API || {},
                Tawk_LoadStart = new Date();
            (function() {
                var s1 = document.createElement("script"),
                    s0 = document.getElementsByTagName("script")[0];
                s1.async = true;
                s1.src='https://embed.tawk.to/{$_c['tawkto']}';
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
            })();
        </script>
        <!--End of Tawk.to Script-->
    {/if}
    <script src="ui/ui/scripts/vendors.js?v=1"></script>
</body>

</html>