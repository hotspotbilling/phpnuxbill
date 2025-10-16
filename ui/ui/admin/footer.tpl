</section>
</div>
<footer class="main-footer">
    <div class="pull-right" id="version" onclick="location.href = '{Text::url('community')}#latestVersion';"></div>
   &copy; 2025. PHPNuxBill by <a href="https://github.com/hotspotbilling/phpnuxbill" rel="nofollow noreferrer noopener"
    target="_blank">iBNuX</a>   All Rights Reserved. 
</footer>
</div>
<script src="{$app_url}/ui/ui/scripts/jquery.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/bootstrap.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/adminlte.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/plugins/select2.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/pace.min.js"></script>
<script src="{$app_url}/ui/ui/summernote/summernote.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/custom.js?2025.2.5"></script> 

<script>
    document.getElementById('searchTerm').addEventListener('keyup', function () {
        let query = this.value;
        $.ajax({
            url: '{Text::url('search_user')}',
            type: 'GET',
            data: { query: query },
            success: function (data) {
                if (data.trim() !== '') {
                    $('#searchResults').html(data).show();
                } else {
                    $('#searchResults').html('').hide();
                }
            }
        });
    });
</script>

<script>
    const toggleIcon = document.getElementById('toggleIcon');
    const body = document.body;
    let currentMode = localStorage.getItem('mode') || 'system';

    // Function to get system preference
    function getSystemPreference() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    // Function to apply the theme
    function applyTheme(theme) {
        body.classList.remove('dark-mode');
        if (theme === 'dark') {
            body.classList.add('dark-mode');
        }
    }

    // Function to update the icon based on current mode
    function updateIcon() {
        const effectiveTheme = currentMode === 'system' ? getSystemPreference() : currentMode;

        if (currentMode === 'system') {
            toggleIcon.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-brightness"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 3.34a10 10 0 1 1 -15 8.66l.005 -.324a10 10 0 0 1 14.995 -8.336m-9 1.732a8 8 0 0 0 4.001 14.928l-.001 -16a8 8 0 0 0 -4 1.072" /></svg>`;
        } else if (effectiveTheme === 'dark') {
            toggleIcon.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" 
                     stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-sun">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/>
                    <path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"/>
                </svg>`;
        } else {
            toggleIcon.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" 
                     stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-moon">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"/>
                </svg>`;
        }
    }

    // Function to set mode and apply changes
    function setMode(mode) {
        currentMode = mode;
        localStorage.setItem('mode', mode);

        const effectiveTheme = mode === 'system' ? getSystemPreference() : mode;
        applyTheme(effectiveTheme);
        updateIcon();
    }

    // Initialize theme on page load
    function initializeTheme() {
        const effectiveTheme = currentMode === 'system' ? getSystemPreference() : currentMode;
        applyTheme(effectiveTheme);
        updateIcon();
    }

    // Listen for system theme changes
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
            if (currentMode === 'system') {
                const newTheme = getSystemPreference();
                applyTheme(newTheme);
            }
        });
    }

    // Toggle mode on click (cycle through: system -> light -> dark -> system)
    toggleIcon.addEventListener('click', () => {
        switch (currentMode) {
            case 'system':
                setMode('light');
                break;
            case 'light':
                setMode('dark');
                break;
            case 'dark':
                setMode('system');
                break;
            default:
                setMode('system');
        }
    });

    // Initialize theme when page loads
    initializeTheme();
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const skeleton = document.querySelector('.dashboard-skeleton');
        const content = document.querySelector('#dashboard-content');

        skeleton.style.display = 'block';
        content.style.display = 'none';

        setTimeout(() => {
            skeleton.style.display = 'none';
            content.style.display = 'block';
        }, 2000);
    });
</script>

{if isset($xfooter)}
{$xfooter}
{/if}
{literal}
<script>
    var listAttApi;
    var posAttApi = 0;
    $(document).ready(function () {
        $('.select2').select2({ theme: "bootstrap" });
        $('.select2tag').select2({ theme: "bootstrap", tags: true });
        var listAtts = document.querySelectorAll(`button[type="submit"]`);
        listAtts.forEach(function (el) {
            if (el.addEventListener) { // all browsers except IE before version 9
                el.addEventListener("click", function () {
                    var txt = $(this).html();
                    $(this).html(
                        `<span class="loading"></span>`
                    );
                    setTimeout(() => {
                        $(this).prop("disabled", true);
                    }, 100);
                    setTimeout(() => {
                        $(this).html(txt);
                        $(this).prop("disabled", false);
                    }, 5000);
                }, false);
            } else {
                if (el.attachEvent) { // IE before version 9
                    el.attachEvent("click", function () {
                        var txt = $(this).html();
                        $(this).html(
                            `<span class="loading"></span>`
                        );
                        setTimeout(() => {
                            $(this).prop("disabled", true);
                        }, 100);
                        setTimeout(() => {
                            $(this).html(txt);
                            $(this).prop("disabled", false);
                        }, 5000);
                    });
                }
            }

        });
        setTimeout(() => {
            listAttApi = document.querySelectorAll(`[api-get-text]`);
            apiGetText();
        }, 500);
    });

    function ask(field, text) {
        const txt = field.innerHTML;

        field.innerHTML = `<span class="loading"></span>`;
        field.setAttribute("disabled", true);

        Swal.fire({
            title: 'Are you sure?',
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            let delay = result.isConfirmed ? 400 : 500;

            setTimeout(() => {
                field.innerHTML = txt;
                field.removeAttribute("disabled");

                if (result.isConfirmed) {
                    const form = field.closest('form');
                    if (form) {
                        form.submit(); // manually submit the form
                    } else {
                        //fallback if not in a form
                        const href = field.getAttribute("href") || field.dataset.href;
                        if (href) window.location.href = href;
                    }
                }
            }, delay);
        });

        return false;
    }

    function apiGetText() {
        var el = listAttApi[posAttApi];
        if (el != undefined) {
            $.get(el.getAttribute('api-get-text'), function (data) {
                el.innerHTML = data;
                posAttApi++;
                if (posAttApi < listAttApi.length) {
                    apiGetText();
                }
            });
        }
    }

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

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
    $("[data-toggle=popover]").popover();
</script>
{/literal}

</body>

</html>