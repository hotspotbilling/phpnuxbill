        </section>
        </div>
        <footer class="main-footer">
            <div class="pull-right" id="version" onclick="location.href = '{$_url}community#latestVersion';"></div>
            PHPNuxBill by <a href="https://github.com/hotspotbilling/phpnuxbill" rel="nofollow noreferrer noopener"
                target="_blank">iBNuX</a>, Theme by <a href="https://adminlte.io/" rel="nofollow noreferrer noopener"
                target="_blank">AdminLTE</a>
        </footer>
        </div>
        <script src="ui/ui/scripts/jquery.min.js"></script>
        <script src="ui/ui/scripts/bootstrap.min.js"></script>
        <script src="ui/ui/scripts/adminlte.min.js"></script>
        <script src="ui/ui/scripts/plugins/select2.min.js"></script>
        <script src="ui/ui/scripts/pace.min.js"></script>
        <script src="ui/ui/scripts/custom.js"></script>

        {if isset($xfooter)}
            {$xfooter}
        {/if}
        {literal}
            <script>
                $(document).ready(function() {
                    $('.select2').select2({theme: "bootstrap"});
                    $('.select2tag').select2({theme: "bootstrap", tags: true});
                    var listAtts = document.querySelectorAll(`button[type="submit"]`);
                    listAtts.forEach(function(el) {
                        if (el.addEventListener) { // all browsers except IE before version 9
                            el.addEventListener("click", function() {
                                $(this).html(
                                    `<span class="loading"></span>`
                                );
                                // setTimeout(() => {
                                //     $(this).prop("disabled", true);
                                // }, 100);
                            }, false);
                        } else {
                            if (el.attachEvent) { // IE before version 9
                                el.attachEvent("click", function() {
                                    $(this).html(
                                        `<span class="loading"></span>`
                                    );
                                    // setTimeout(() => {
                                    //     $(this).prop("disabled", true);
                                    // }, 100);
                                });
                            }
                        }

                    });
                });

                var listAtts = document.querySelectorAll(`[api-get-text]`);
                listAtts.forEach(function(el) {
                    $.get(el.getAttribute('api-get-text'), function(data) {
                        el.innerHTML = data;
                    });
                });

                function setKolaps() {
                    var kolaps = getCookie('kolaps');
                    if (kolaps) {
                        setCookie('kolaps', false, 30);
                    } else {
                        setCookie('kolaps', true, 30);
                    }
                    return true;
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

        </body>

</html>