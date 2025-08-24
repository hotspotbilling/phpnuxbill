
{if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes'}
    <div class="box box-primary box-solid mb30">
        <div class="box-header">
            <h4 class="box-title">{Lang::T("Transfer Balance")}</h4>
        </div>
        <div class="box-body p-0">
            <form method="post" onsubmit="return askConfirm()" role="form" action="{Text::url('home')}">
                <div class="form-group">
                    <div class="col-sm-5">
                        <input type="text" id="username" name="username" class="form-control" required
                            placeholder="{Lang::T('Friend Usernames')}">
                    </div>
                    <div class="col-sm-5">
                        <input type="number" id="balance" name="balance" autocomplete="off" class="form-control"
                            required placeholder="{Lang::T('Balance Amount')}">
                    </div>
                    <div class="form-group col-sm-2" align="center">
                        <button class="btn btn-success btn-block" id="sendBtn" type="submit" name="send"
                            onclick="return ask(this, '{Lang::T(" Are You Sure?")}')" value="balance"><i
                                class="glyphicon glyphicon-send"></i></button>
                    </div>
                </div>
            </form>
            <script>
                function askConfirm() {
                    if (confirm('{Lang::T('Send yours balance ? ')}')) {
                    setTimeout(() => {
                        document.getElementById('sendBtn').setAttribute('disabled', '');
                    }, 1000);
                    return true;
                }
                return false;
                }
            </script>
        </div>
    </div>
{/if}