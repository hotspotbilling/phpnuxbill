<div class="box box-primary box-solid mb30">
    <div class="box-header">
        <h4 class="box-title">{Lang::T("Recharge a friend")}</h4>
    </div>
    <div class="box-body p-0">
        <form method="post" role="form" action="{Text::url('home')}">
            <div class="form-group">
                <div class="col-sm-10">
                    <input type="text" id="username" name="username" class="form-control" required
                        placeholder="{Lang::T('Friend username')}">
                </div>
                <div class="form-group col-sm-2" align="center">
                    <button class="btn btn-success btn-block" id="sendBtn" type="submit" name="send"
                        onclick="return ask(this, '{Lang::T(" Are You Sure?")}')" value="plan"><i
                            class="glyphicon glyphicon-send"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>
