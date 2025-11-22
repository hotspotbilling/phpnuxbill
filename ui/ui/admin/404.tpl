{include file="sections/header.tpl"}

<style>
    /* --- Variables --- */
    :root {
        /* Warna Gradient Utama (Ungu ke Biru - Senada Dashboard) */
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        /* Warna Background Adem (Soft Blue-Grey) */
        --bg-calm: linear-gradient(180deg, #f4f6f9 0%, #e0e6ed 100%);
    }

    .page-err {
        min-height: 85vh; /* Tinggi hampir full layar */
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg-calm); /* Background baru yang adem */
        padding: 20px;
    }

    /* --- Main Card --- */
    .err-container {
        background: #ffffff;
        border-radius: 20px;
        padding: 50px 40px;
        text-align: center;
        max-width: 550px;
        width: 100%;
        /* Shadow yang lebih halus dan bersih */
        box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
        border: 1px solid #f0f0f0;
        position: relative;
        animation: slideUp 0.5s ease-out;
    }

    /* --- 404 Typography --- */
    .m404 {
        font-size: 100px;
        font-weight: 800;
        margin: 0;
        line-height: 1;
        /* Gradasi teks tetap ada agar elegan, tapi warnanya saya lembutkan */
        background: -webkit-linear-gradient(45deg, #6a82fb, #fc5c7d);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        letter-spacing: -3px;
        margin-bottom: 10px;
    }

    /* --- Icon Animation --- */
    .icon-container {
        font-size: 50px;
        color: #9aa0ac; /* Warna abu icon */
        margin-bottom: 20px;
        height: 60px;
    }
    
    .icon-animate {
        display: inline-block;
        animation: float 3s ease-in-out infinite;
    }

    /* --- Texts --- */
    .error-title {
        font-size: 26px;
        font-weight: 700;
        color: #343a40; /* Warna teks standar dashboard */
        margin-bottom: 10px;
    }

    .error-message {
        font-size: 16px;
        color: #6c757d; /* Abu-abu lembut */
        margin-bottom: 35px;
        line-height: 1.6;
    }

    /* --- Modern Button --- */
    .error-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 35px;
        font-size: 15px;
        font-weight: 600;
        color: #ffffff !important;
        background: #4c51bf; /* Kembali ke warna solid ungu dashboard Anda agar match */
        border-radius: 50px;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(76, 81, 191, 0.25);
    }

    .error-btn:hover {
        background-color: #434190;
        transform: translateY(-2px);
        box-shadow: 0 7px 14px rgba(76, 81, 191, 0.3);
    }

    .error-btn i {
        margin-right: 8px;
    }

    /* --- Keyframes --- */
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

</style>

<div class="page page-err clearfix">
    <div class="err-container">
        
        <h1 class="m404">404</h1>

        <div class="icon-container">
            <span class="icon-animate">
                <i class="ion ion-wifi"></i>
            </span>
        </div>

        <h2 class="error-title">{Lang::T("Page Not Found")}</h2>
        
        <p class="error-message">
            {Lang::T("Sorry, the page you are looking for is not available.")} <br>
            {Lang::T("Please return to the main page.")}
        </p>

        <a href="{Text::url('dashboard')}" class="error-btn">
            <i class="ion ion-ios-arrow-left"></i> {Lang::T("Back to Dashboard")}
        </a>
    </div>
</div>

{include file="sections/footer.tpl"}
