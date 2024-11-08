</section>
</div>
{if isset($_c['CompanyFooter'])}
    <footer class="main-footer">
        {$_c['CompanyFooter']}
        <div class="pull-right">
            <a href="javascript:showPrivacy()">Privacy</a>
            &bull;
            <a href="javascript:showTaC()">T &amp; C</a>
        </div>
    </footer>
{else}
    <footer class="main-footer">
        PHPNuxBill by <a href="https://github.com/hotspotbilling/phpnuxbill" rel="nofollow noreferrer noopener"
            target="_blank">iBNuX</a>, Theme by <a href="https://adminlte.io/" rel="nofollow noreferrer noopener"
            target="_blank">AdminLTE</a>
        <div class="pull-right">
            <a href="javascript:showPrivacy()">Privacy</a>
            &bull;
            <a href="javascript:showTaC()">T &amp; C</a>
        </div>
    </footer>
{/if}
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



<script src="ui/ui/scripts/jquery.min.js"></script>
<script src="ui/ui/scripts/bootstrap.min.js"></script>
<script src="ui/ui/scripts/adminlte.min.js"></script>

<script src="ui/ui/scripts/plugins/select2.min.js"></script>
<script src="ui/ui/scripts/custom.js?v=2"></script>

{if isset($xfooter)}
    {$xfooter}
{/if}

{if $_c['tawkto'] != ''}
    <!--Start of Tawk.to Script-->
    <script type="text/javascript">
        var isLoggedIn = false;
        var Tawk_API = {
            onLoad: function() {
                Tawk_API.setAttributes({
                    'username'    : '{$_user['username']}',
                    'service'    : '{$_user['service_type']}',
                    'balance'    : '{$_user['balance']}',
                    'account'    : '{$_user['account_type']}',
                    'phone'    : '{$_user['phonenumber']}'
                }, function(error) {
                    console.log(error)
                });

                }
            };
            var Tawk_LoadStart = new Date();
            Tawk_API.visitor = {
                name: '{$_user['fullname']}',
                email: '{$_user['email']}',
                phone: '{$_user['phonenumber']}'
            };
            (function() {
                var s1 = document.createElement("script"),
                    s0 = document.getElementsByTagName("script")[0];
                s1.async = true;
                s1.src = 'https://embed.tawk.to/{$_c['tawkto']}';
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
            })();
        </script>
        <!--End of Tawk.to Script-->
    {/if}

    <script>
        const toggleIcon = document.getElementById('toggleIcon');
        const body = document.body;
        const savedMode = localStorage.getItem('mode');
        if (savedMode === 'dark') {
            body.classList.add('dark-mode');
            toggleIcon.textContent = '🌜';
        }

        function setMode(mode) {
            if (mode === 'dark') {
                body.classList.add('dark-mode');
                toggleIcon.textContent = '🌜';
            } else {
                body.classList.remove('dark-mode');
                toggleIcon.textContent = '🌞';
            }
        }

        toggleIcon.addEventListener('click', () => {
            if (body.classList.contains('dark-mode')) {
                setMode('light');
                localStorage.setItem('mode', 'light');
            } else {
                setMode('dark');
                localStorage.setItem('mode', 'dark');
            }
        });
    </script>


{literal}
    <script>
        var listAtts = document.querySelectorAll(`[api-get-text]`);
        listAtts.forEach(function(el) {
            $.get(el.getAttribute('api-get-text'), function(data) {
                el.innerHTML = data;
            });
        });
        $(document).ready(function() {
            var listAtts = document.querySelectorAll(`button[type="submit"]`);
            listAtts.forEach(function(el) {
                if (el.addEventListener) { // all browsers except IE before version 9
                    el.addEventListener("click", function() {
                        $(this).html(
                            `<span class="loading"></span>`
                        );
                        setTimeout(() => {
                            $(this).prop("disabled", true);
                        }, 100);
                    }, false);
                } else {
                    if (el.attachEvent) { // IE before version 9
                        el.attachEvent("click", function() {
                            $(this).html(
                                `<span class="loading"></span>`
                            );
                            setTimeout(() => {
                                $(this).prop("disabled", true);
                            }, 100);
                        });
                    }
                }
                $(function() {
                    $('[data-toggle="tooltip"]').tooltip()
                })
            });
        });

        function ask(field, text){
            if (confirm(text)) {
                setTimeout(() => {
                    field.innerHTML = field.innerHTML.replace(`<span class="loading"></span>`, '');
                    field.removeAttribute("disabled");
                }, 5000);
                return true;
            } else {
                setTimeout(() => {
                    field.innerHTML = field.innerHTML.replace(`<span class="loading"></span>`, '');
                    field.removeAttribute("disabled");
                }, 500);
                return false;
            }
        }

        function setCookie(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "") + expires + "; path=/";
        }

        function getCookie(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }
    </script>
{/literal}
<script>
setCookie('user_language', '{$user_language}', 365);
</script>
</body>

</html>