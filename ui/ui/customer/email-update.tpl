{include file="customer/header.tpl"}

<!-- user-phone-update -->

<div class="box box-danger">
    <div class="box-header with-border">
        <h3 class="box-title">{Lang::T('Change Email Address')}</h3>
    </div>
    <div class="box-body">
        <div class="form-horizontal">
            <div class="form-group">
                <label class="col-md-2 control-label">{Lang::T('Current Email')}</label>
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-addon" id="basic-addon1">+</span>
                        <input type="text" class="form-control" name="email"
                            value="{$_user['email']}" readonly placeholder="{Lang::T('Email')}">
                    </div>
                </div>
            </div>
            <form method="post" role="form" action="{$_url}accounts/email-update-otp">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <div class="form-group">
                    <label class="col-md-2 control-label">{Lang::T('New Email')}</label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-addon" id="basic-addon1">+</span>
                            <input type="text" class="form-control" name="email" id="email" value="{$new_email}" required
                                placeholder="{Lang::T('Input your Email')}">
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-info btn-flat">{Lang::T('Request OTP')}</button>
                            </span>
                        </div>
                    </div>
                </div>
            </form>
            <form method="post" role="form" action="{$_url}accounts/email-update-post">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <!-- Form 2 -->
                <div class="form-group">
                    <label class="col-md-2 control-label">{Lang::T('OTP')}</label>
                    <div class="col-md-6">
                        <input type="number" class="form-control" id="otp" name="otp"
                            placeholder="{Lang::T('Enter OTP that was sent to your email')}" required>
                    </div>
                </div>

                <!-- Hidden field to store the phone number value -->
                <input type="hidden" name="email" id="hidden_email">

                <center>
                        <button class="btn btn-success" type="submit"
                            onclick="return validateForm()">{Lang::T('Update')}</button>
                        Or <a href="{$_url}home">{Lang::T('Cancel')}</a>
                </center>
            </form>

            <script>
                function validateForm() {
                    var email = document.getElementById("email").value;
                    var otp = document.getElementById("otp").value;

                    if (email.trim() === "") {
                        alert("Email Address is required.");
                        return false; // Prevent form submission
                    }

                    if (otp.trim() === "") {
                        alert("OTP code is required.");
                        return false; // Prevent form submission
                    }

                    // Set the phone number value in the hidden field
                    document.getElementById("hidden_email").value = email;
                    return true; // Allow form submission
                }
            </script>
        </div>
    </div>
</div>
{include file="customer/footer.tpl"}