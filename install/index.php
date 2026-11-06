<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

require __DIR__ . '/lib.php';
pnb_guard();
pnb_head('Welcome', 1);
?>
<h1>Welcome to PHPNuxBill</h1>
<p class="lede">Billing for your Mikrotik hotspot and PPPoE customers. Setting it up takes a few minutes: you only need a MySQL database and its login.</p>

<ol class="next-list">
    <li><b>Check the server.</b> We make sure PHP and the folders are ready.</li>
    <li><b>Connect the database.</b> Host, user, password and name.</li>
    <li><b>Choose the admin password.</b> There is no default password.</li>
    <li><b>Done.</b> Sign in and add your router.</li>
</ol>

<h2>Terms of use</h2>
<details>
    <summary>Read the terms</summary>
    <div class="body">

                <p><strong>Informasi Aplikasi</strong><br>
                    Application Name: PHPNuxBill <br>
                    Release Date: 30/10/2015<br>
                    By: PHPNuxBill [ <a href="https://github.com/hotspotbilling/phpnuxbill" target="_blank">https://github.com/hotspotbilling/phpnuxbill</a> ]<br>
                    Donasi Paypal: <b>me@ibnux.net</b><br>
                    <br>
                    <strong>Syarat Penggunaan:</strong><br>
                    Syarat Penggunaan ini berlaku untuk semua versi.<br><br>
                <ul>
                    <li>Silahkan Anda menggunakan aplikasi ini dengan bijak, Anda dapat mendesain ulang script maupun tampilan pada
                        aplikasi ini sesuai dengan kebutuhan anda, memperbayak jumlah copy atau mendistribusikan aplikasi ini.
                        Dengan catatan tidak menghapus link developer.</li>
                    <li>Tidak ada garansi dari kami jika anda mengalami error atau merasa rugi ketika menggunakan aplikasi ini,
                        Anda hanya dapat memberikan feedback yang berisi laporan error, dengan syarat dan ketentuan yang berlaku.</li>
                    <li>Semua yang terkait biaya atau donasi apapun versi-nya, Anda dapat update seumur hidup atau selama aplikasi
                        ini masih dikembangkan. Mohon jangan salah pengertian bahwa kami tim pengembang mengkomersilkan produk ini
                        dan anda membeli produk kami.</li>
                    <li>Aplikasi ini bersifat sosial untuk dapat dikembangkan bersama. Karena itu kami juga mengundang relawan-relawan
                        yang mau menjadi pengembangkan aplikasi ini.</li>
                    <li>Penulis berhak setiap saat untuk mengubah ketentuan Syarat Penggunaan tanpa pemberitahuan sebelumnya.</li>
                </ul>

    </div>
</details>

<div class="actions">
    <a href="step2.php" class="btn">Accept the terms and continue</a>
</div>
<?php pnb_foot();
