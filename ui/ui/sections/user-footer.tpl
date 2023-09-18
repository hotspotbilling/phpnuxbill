        </section>
        </div>
        {if isset($_c['CompanyFooter'])}
            <footer class="main-footer">
                {$_c['CompanyFooter']}
                <div class="pull-right hidden-xs">
                    <a href="./pages/Privacy_Policy.html" target="_blank">Privacy</a>
                    &bull;
                    <a href="./pages/Terms_of_Conditions.html" target="_blank">ToC</a>
                </div>
            </footer>
        {else}
            <footer class="main-footer">
                PHPNuxBill by <a href="https://github.com/hotspotbilling/phpnuxbill" rel="nofollow noreferrer noopener"
                    target="_blank">iBNuX</a>, Theme by <a href="https://adminlte.io/" rel="nofollow noreferrer noopener"
                    target="_blank">AdminLTE</a>
                    <div class="pull-right hidden-xs">
                        <a href="./pages/Privacy_Policy.html" target="_blank">Privacy</a>
                        &bull;
                        <a href="./pages/Terms_of_Conditions.html" target="_blank">ToC</a>
                    </div>
            </footer>
        {/if}
        </div>

        <script src="ui/ui/scripts/jquery.min.js"></script>
        <script src="ui/ui/scripts/bootstrap.min.js"></script>
        <script src="ui/ui/scripts/adminlte.min.js"></script>

        <script src="ui/ui/scripts/plugins/select2.min.js"></script>
        <script src="ui/ui/scripts/custom.js"></script>

        {if isset($xfooter)}
            {$xfooter}
        {/if}

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

        {literal}
            <script>
                var listAtts = document.querySelectorAll(`[api-get-text]`);
                listAtts.forEach(function(el) {
                    $.get(el.getAttribute('api-get-text'), function(data) {
                        el.innerHTML = data;
                    });
                });
            </script>
        {/literal}

        </body>

</html>