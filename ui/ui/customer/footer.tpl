</section>
</div>
<footer class="main-footer cm-footer">
    <div class="cm-footer-text">
        {if isset($_c['CompanyFooter'])}
        {$_c['CompanyFooter']}
        {else}
        PHPNuxBill by <a href="https://github.com/hotspotbilling/phpnuxbill" rel="nofollow noreferrer noopener"
            target="_blank">iBNuX</a>, Theme by <a href="https://adminlte.io/" rel="nofollow noreferrer noopener"
            target="_blank">AdminLTE</a>
        {/if}
    </div>
    <nav class="cm-footer-links" aria-label="Legal">
        <a href="javascript:showPrivacy()">Privacy</a>
        <span aria-hidden="true">&bull;</span>
        <a href="javascript:showTaC()">T &amp; C</a>
    </nav>
</footer>
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



<script src="{$app_url}/ui/ui/scripts/jquery.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/bootstrap.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/adminlte.min.js"></script>

<script src="{$app_url}/ui/ui/scripts/plugins/select2.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/custom.js?2026.11.11.1"></script>

{if isset($xfooter)}
{$xfooter}
{/if}

{if $_c['tawkto'] != ''}
<!--Start of Tawk.to Script-->
<script type="text/javascript">
    var isLoggedIn = false;
    var Tawk_API = {
        onLoad: function () {
            Tawk_API.setAttributes({
                'username': '{$_user['username']}',
                'service': '{$_user['service_type']}',
                'balance': '{$_user['balance']}',
                'account': '{$_user['account_type']}',
                'phone': '{$_user['phonenumber']}'
                }, function (error) {
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
<script>
    /* remember the menu collapsed to icons (desktop): the header reads this cookie, the same one the admin menu uses */
    (function () {
        function remember(collapsed) {
            if (window.innerWidth > 767) {
                document.cookie = 'kolaps=' + (collapsed ? 'true' : 'false') + '; max-age=' + (30 * 24 * 60 * 60) + '; path=/; SameSite=Lax';
            }
        }
        $(document).on('collapsed.pushMenu', function () { remember(true); });
        $(document).on('expanded.pushMenu', function () { remember(false); });
    })();
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
        if (currentMode === 'system') {
            toggleIcon.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-brightness"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 3.34a10 10 0 1 1 -15 8.66l.005 -.324a10 10 0 0 1 14.995 -8.336m-9 1.732a8 8 0 0 0 4.001 14.928l-.001 -16a8 8 0 0 0 -4 1.072" /></svg>`;
        } else if (currentMode === 'dark') {
            toggleIcon.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" 
                     stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-moon">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"/>
                </svg>`;
        } else {
            toggleIcon.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" 
                     stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-sun">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/>
                    <path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"/>
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
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
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


{literal}
<script>
    var listAtts = document.querySelectorAll(`[api-get-text]`);
    listAtts.forEach(function (el) {
        el.classList.add('api-skeleton');
        $.get(el.getAttribute('api-get-text'), function (data) {
            el.innerHTML = data;
        }).always(function () {
            el.classList.remove('api-skeleton');
        });
    });
    $(document).ready(function () {
        var listAtts = document.querySelectorAll(`button[type="submit"]`);
        listAtts.forEach(function (el) {
            if (el.addEventListener) { // all browsers except IE before version 9
                el.addEventListener("click", function () {
                    $(this).html(
                        `<span class="loading"></span>`
                    );
                    setTimeout(() => {
                        $(this).prop("disabled", true);
                    }, 100);
                }, false);
            } else {
                if (el.attachEvent) { // IE before version 9
                    el.attachEvent("click", function () {
                        $(this).html(
                            `<span class="loading"></span>`
                        );
                        setTimeout(() => {
                            $(this).prop("disabled", true);
                        }, 100);
                    });
                }
            }
            $(function () {
                $('[data-toggle="tooltip"]').tooltip()
            })
        });
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

<script>
    const publicVapidKey = '{$_c['push_public_key']}';
    // set once the customer presses "Yes": closing the modal after that is not a refusal
    let pushChoiceMade = false;
    const pushDeclineMs = 30 * 24 * 60 * 60 * 1000;

    async function subscribeUser() {
        try {
            // 1. Check secure context and browser capability
            if (!window.isSecureContext) {
                throw new Error('Push notifications require a secure connection (HTTPS). Please ensure SSL is enabled on your cloud server.');
            }
            if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
                throw new Error('Push notifications are not supported on this browser.');
            }
            if (!publicVapidKey || publicVapidKey.trim() === '') {
                throw new Error('Push notification service is not configured (missing VAPID key).');
            }

            console.log('Starting subscription process...');

            // 2. Request Notification Permission explicitly (required by modern browsers)
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                if (permission === 'denied') {
                    throw new Error('Notification permission was blocked in your browser settings.');
                } else {
                    throw new Error('Notification permission was dismissed or not granted.');
                }
            }

            // 3. Register Service Worker with safe protocol
            const rawSwUrl = '{$app_url}/ui/ui/scripts/sw.js';
            const swUrl = rawSwUrl.replace(/^http:\/\//i, window.location.protocol + '//');
            const registration = await navigator.serviceWorker.register(swUrl);
            console.log('Service worker registered:', registration);

            // Wait until service worker is active
            await navigator.serviceWorker.ready;

            const existingSubscription = await registration.pushManager.getSubscription();
            if (existingSubscription) {
                console.log('Existing subscription found, unsubscribing...');
                await existingSubscription.unsubscribe();
                console.log('Unsubscribed existing subscription');
            }

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(publicVapidKey),
            });
            console.log('Subscribed:', subscription);

            const subscriptionData = subscription.toJSON();
            subscriptionData.customer_id = '{$_user['id']}';
            console.log('Subscription data to send:', subscriptionData);

            const response = await fetch('?_route=push_subscribe', {
                method: 'POST',
                body: JSON.stringify(subscriptionData),
                headers: { 'Content-Type': 'application/json' }
            });

            console.log('Fetch response:', response);

            if (response.ok) {
                const result = await response.json();
                localStorage.setItem('push_subscribed', 'yes');
                localStorage.removeItem('push_subscribed_timestamp');
                Swal.fire({
                    icon: 'success',
                    title: result.title || 'Subscribed!',
                    text: result.message || 'Subscribed successfully!',
                    confirmButtonColor: '#4CAF50',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    $('#subscribeModal').modal('hide');
                });
            } else {
                const result = await response.json().catch(() => ({}));
                throw new Error(result.message || 'Failed to save subscription on server');
            }
        } catch (error) {
            console.error('Subscription error:', error);
            // Permission refused or dismissed: ask again in 30 days. A technical failure (no HTTPS, server error, offline) is not a
            // refusal, so ask again tomorrow.
            const refused = ('Notification' in window) && Notification.permission !== 'granted';
            const stamp = refused ? Date.now() : Date.now() - (pushDeclineMs - 24 * 60 * 60 * 1000);
            localStorage.setItem('push_subscribed', 'no');
            localStorage.setItem('push_subscribed_timestamp', stamp.toString());

            Swal.fire({
                icon: 'error',
                title: error.title || 'Oops!',
                text: error.message,
                confirmButtonText: 'OK',
                confirmButtonColor: '#f44336'
            });
        }
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Do not prompt if environment does not support Web Push or is not HTTPS
        if (!window.isSecureContext || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            console.info('Push notifications not supported or not running on HTTPS.');
            return;
        }

        // Do not prompt if VAPID key is missing
        if (!publicVapidKey || publicVapidKey.trim() === '') {
            console.info('Push notifications disabled: VAPID public key is not configured.');
            return;
        }

        // Do not prompt if user has already permanently blocked notifications
        if (Notification.permission === 'denied') {
            console.info('Push notifications permission is denied.');
            return;
        }

        const status = localStorage.getItem('push_subscribed');
        const declineTimestamp = localStorage.getItem('push_subscribed_timestamp');
        const oneMonthInMs = 30 * 24 * 60 * 60 * 1000; // 30 days in milliseconds

        function showModal() {
            $('#subscribeModal').modal({
                backdrop: 'static',
                keyboard: false
            });

            $('#confirmSubscribe').off('click').on('click', () => {
                pushChoiceMade = true;
                $('#subscribeModal').modal('hide');
                subscribeUser();
            });

            $('#subscribeModal').off('hidden.bs.modal').on('hidden.bs.modal', () => {
                if (!pushChoiceMade && localStorage.getItem('push_subscribed') !== 'yes') {
                    localStorage.setItem('push_subscribed', 'no');
                    localStorage.setItem('push_subscribed_timestamp', Date.now().toString());
                }
            });
        }

        if (status === null || status === undefined) {
            showModal();
        } else if (status === 'yes') {
            console.log('Push subscription already handled:', status);
        } else if (status === 'no') {
            const currentTime = Date.now();
            const declineTime = declineTimestamp ? parseInt(declineTimestamp, 10) : 0;
            const timeSinceDecline = currentTime - declineTime;

            if (timeSinceDecline >= oneMonthInMs) {
                localStorage.removeItem('push_subscribed');
                localStorage.removeItem('push_subscribed_timestamp');
                showModal();
            } else {
                console.log('Push subscription declined, will prompt again in:', (oneMonthInMs - timeSinceDecline) / (24 * 60 * 60 * 1000), 'days');
            }
        }
    });
</script>

</body>

</html>