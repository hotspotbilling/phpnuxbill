        </section>
    </div>
    <footer class="main-footer">
        <div class="pull-right hidden-xs" id="version" onclick="location.href = '{$_url}community#latestVersion';"></div>
        PHPNuxBill by <a href="https://github.com/hotspotbilling/phpnuxbill" rel="nofollow noreferrer noopener"
            target="_blank">iBNuX</a>, Theme by <a href="https://adminlte.io/" rel="nofollow noreferrer noopener"
            target="_blank">AdminLTE</a>
    </footer>
</div>

<script src="ui/ui/scripts/jquery.min.js"></script>
<script src="ui/ui/scripts/bootstrap.min.js"></script>
<script src="ui/ui/scripts/adminlte.min.js"></script>
<script src="ui/ui/scripts/plugins/select2.min.js"></script>
<script src="ui/ui/scripts/custom.js"></script>

{if isset($xfooter)}
    {$xfooter}
{/if}
{literal}
<script>
$(document).ready(function() {
    $('.select2').select2({theme: "bootstrap"});
});
</script>
{/literal}

</body>

</html>