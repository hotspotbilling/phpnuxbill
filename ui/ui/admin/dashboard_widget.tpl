{include file="sections/header.tpl"}

{function showWidget pos=0}
    {foreach $widgets as $w}
        {if $w['position'] == $pos}
            {$w['content']}
        {/if}
    {/foreach}
{/function}



{if $_c['dashboard'] == '1.55.1'}
    {showWidget widgets=$widgets pos=1}
    <div class="row">
        <div class="col-md-6">
            {showWidget widgets=$widgets pos=2}
        </div>
        <div class="col-md-6">
            {showWidget widgets=$widgets pos=3}
        </div>
    </div>
    {showWidget widgets=$widgets pos=4}
{elseif $_c['dashboard'] == '1.57.1'}
    {showWidget widgets=$widgets pos=1}
    <div class="row">
        <div class="col-md-5">
            {showWidget widgets=$widgets pos=2}
        </div>
        <div class="col-md-7">
            {showWidget widgets=$widgets pos=3}
        </div>
    </div>
    {showWidget widgets=$widgets pos=4}
{elseif $_c['dashboard'] == '1.1.1.1'}
    {showWidget widgets=$widgets pos=1}
    {showWidget widgets=$widgets pos=2}
    {showWidget widgets=$widgets pos=3}
    {showWidget widgets=$widgets pos=4}
{else}
    {showWidget widgets=$widgets pos=1}
    <div class="row">
        <div class="col-md-7">
            {showWidget widgets=$widgets pos=2}
        </div>
        <div class="col-md-5">
            {showWidget widgets=$widgets pos=3}
        </div>
    </div>
    {showWidget widgets=$widgets pos=4}
{/if}

{if $_c['new_version_notify'] != 'disable'}
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            $.getJSON("./version.json?" + Math.random(), function(data) {
                var localVersion = data.version;
                $('#version').html('Version: ' + localVersion);
                $.getJSON(
                    "https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/version.json?" +
                    Math
                    .random(),
                    function(data) {
                        var latestVersion = data.version;
                        if (localVersion !== latestVersion) {
                            $('#version').html('Latest Version: ' + latestVersion);
                            if (getCookie(latestVersion) != 'done') {
                                Swal.fire({
                                    icon: 'info',
                                    title: "New Version Available\nVersion: " + latestVersion,
                                    toast: true,
                                    position: 'bottom-right',
                                    showConfirmButton: true,
                                    showCloseButton: true,
                                    timer: 30000,
                                    confirmButtonText: '<a href="{Text::url('community')}#latestVersion" style="color: white;">Update Now</a>',
                                    timerProgressBar: true,
                                    didOpen: (toast) => {
                                        toast.addEventListener('mouseenter', Swal.stopTimer)
                                        toast.addEventListener('mouseleave', Swal
                                            .resumeTimer)
                                    }
                                });
                                setCookie(latestVersion, 'done', 7);
                            }
                        }
                    });
            });

        });
    </script>
{/if}

{include file="sections/footer.tpl"}