<footer class="footer">
    <p>&copy; 2025 {$_c['CompanyName']}. {Lang::T('All rights reserved.')}</p>
    <div class="footer-links">
        <a href="{Text::url('privacy')}" class="footer-link" target="_blank" rel="noopener">{Lang::T('Privacy')}</a>
        <a href="{Text::url('terms')}" class="footer-link" target="_blank" rel="noopener">{Lang::T('T &amp; C')}</a>
    </div>
</footer>

{if $_c['tawkto'] != ''}
<!--Start of Tawk.to Script-->
<script type="text/javascript">
    var Tawk_API = Tawk_API || {},
        Tawk_LoadStart = new Date();
    (function () {
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
<script src="{$app_url}/ui/ui/scripts/public.js?2026.11.06.2"></script>
</body>

</html>