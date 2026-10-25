{* Memuat header halaman admin PHPNuxBill *}
{include file="sections/header.tpl"}

<style>
    /* 1. Mencegah halaman utama agar tidak bisa digeser ke kanan-kiri (scroll horizontal) */
    .table-responsive {
        overflow-x: hidden !important;
        border: none !important;
        width: 100% !important;
        margin-bottom: 0 !important;
    }
    .box {
        overflow: hidden;
        word-wrap: break-word;
    }
    .table {
        table-layout: fixed;
        width: 100% !important;
        max-width: 100% !important;
    }

    /* 2. Pengaturan khusus tampilan perangkat seluler (HP / Responsive) */
    @media (max-width: 767px) {
        /* Mengubah baris tabel menjadi blok penuh ke bawah */
        .table > tbody > tr > td {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            border: none !important;
            padding: 8px 10px !important;
            box-sizing: border-box !important;
        }
        /* Memberikan jarak dan garis pemisah antar item commit */
        .table > tbody > tr {
            display: block !important;
            width: 100% !important;
            margin-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
            box-sizing: border-box !important;
        }
        .table > tbody > tr:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        /* Tata letak konten di dalam setiap baris commit */
        .commit-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        /* Styling bagian Tanggal Commit */
        .col-date { 
            font-weight: 600; 
            color: #1e293b; 
            font-size: 13px;
        }
        /* Pembungkus agar kotak deskripsi bisa digeser ke kanan-kiri secara independen */
        .msg-scroll-box {
            width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            box-sizing: border-box !important;
        }
        /* Styling kotak pesan/deskripsi (multi-baris, ada aksen garis biru di kiri) */
        .col-msg { 
            font-size: 12px; 
            color: #475569; 
            background: #f8fafc; 
            padding: 8px 10px; 
            border-radius: 4px; 
            border-left: 3px solid #3b82f6; 
            word-break: break-word;
            overflow-wrap: break-word;
            white-space: normal !important;
            min-width: 280px; 
            box-sizing: border-box !important;
        }
        /* Pengaturan posisi tombol install agar berada di tengah dan tidak terlalu lebar */
        .col-btn { 
            width: 100% !important; 
            display: flex;
            justify-content: center;
            margin-top: 4px;
            box-sizing: border-box !important;
        }
        .col-btn .btn { 
            width: 75% !important; 
            display: inline-block; 
            text-align: center;
            border-radius: 6px;
            padding: 6px 10px;
            font-weight: 600;
            font-size: 13px;
        }
    }
</style>

{* Banner dukungan Palestina di bagian atas *}
<center><a href="https://s.id/standwithpalestine" target="_blank"><img
            src="https://raw.githubusercontent.com/Safouene1/support-palestine-banner/master/banner-support.svg"
            class="img-responsive"></a></center>
<br><br>

<div class="row">
    <!-- Kolom 1: Branch Master (Stabil) -->
    <div class="col-sm-6">
        <div class="box box-hovered mb20 box-primary">
            <div class="box-header">
                <h3 class="box-title">Master</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" style="margin-bottom: 0;">
                    <tbody>
                        {* Perulangan untuk mengambil data commit Master (dibatasi maksimal 10 data) *}
                        {foreach $masters as $i => $data}
                            {if $i < 10}
                                <tr>
                                    <td colspan="3">
                                        <div class="commit-container">
                                            <!-- Menampilkan tanggal dan waktu commit -->
                                            <div class="col-date">
                                                <i class="fa fa-clock-o"></i> {Lang::dateTimeFormat(str_replace(['Z','T'],'',$data['commit']['author']['date']))}
                                            </div>
                                            <!-- Menampilkan isi pesan/deskripsi commit dalam kotak yang bisa digeser -->
                                            <div class="msg-scroll-box">
                                                <div class="col-msg">
                                                    {nl2br($data['commit']['message'])}
                                                </div>
                                            </div>
                                            <!-- Tombol untuk mengunduh dan menginstal versi commit terkait -->
                                            <div class="col-btn">
                                                <a href="/update.php?update_url=https://github.com/hotspotbilling/phpnuxbill/archive/{$data['sha']}.zip"
                                                    class="btn btn-sm btn-primary" onclick="return confirm('Yakin ingin install versi Master ini?')">
                                                    <i class="fa fa-download"></i> Install
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            {/if}
                        {/foreach}
                    </tbody>
                </table>
            </div>
            {* Menyertakan template navigasi pagination bawaan PHPNuxBill *}
            {include file="pagination.tpl"}
        </div>
    </div>

    <!-- Kolom 2: Branch Development -->
    <div class="col-sm-6">
        <div class="box box-hovered mb20 box-primary">
            <div class="box-header">
                <h3 class="box-title">Development</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" style="margin-bottom: 0;">
                    <tbody>
                        {* Perulangan untuk mengambil data commit Development (dibatasi maksimal 10 data) *}
                        {foreach $devs as $i => $data}
                            {if $i < 10}
                                <tr>
                                    <td colspan="3">
                                        <div class="commit-container">
                                            <!-- Menampilkan tanggal dan waktu commit -->
                                            <div class="col-date">
                                                <i class="fa fa-clock-o"></i> {Lang::dateTimeFormat(str_replace(['Z','T'],'',$data['commit']['author']['date']))}
                                            </div>
                                            <!-- Menampilkan isi pesan/deskripsi commit dalam kotak yang bisa digeser -->
                                            <div class="msg-scroll-box">
                                                <div class="col-msg">
                                                    {nl2br($data['commit']['message'])}
                                                </div>
                                            </div>
                                            <!-- Tombol untuk mengunduh dan menginstal versi commit terkait -->
                                            <div class="col-btn">
                                                <a href="/update.php?update_url=https://github.com/hotspotbilling/phpnuxbill/archive/{$data['sha']}.zip"
                                                    class="btn btn-sm btn-primary" onclick="return confirm('Yakin ingin install versi Development ini?')">
                                                    <i class="fa fa-download"></i> Install
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            {/if}
                        {/foreach}
                    </tbody>
                </table>
            </div>
            {* Menyertakan template navigasi pagination bawaan PHPNuxBill *}
            {include file="pagination.tpl"}
        </div>
    </div>
</div>

{* Memuat footer halaman admin PHPNuxBill[span_0](start_span)[span_0](end_span)[span_1](start_span)[span_1](end_span) *}
{include file="sections/footer.tpl"}
