<!DOCTYPE html>
<html lang="en" class="has-aside-left has-aside-mobile-transition has-navbar-fixed-top has-aside-expanded">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$_title} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

    <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">

    <link rel="stylesheet" href="ui/ui/fonts/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="ui/ui/styles/modern-AdminLTE.min.css">
    <link rel="stylesheet" href="ui/ui/styles/sweetalert2.min.css" />
    <script src="ui/ui/scripts/sweetalert2.all.min.js"></script>


    <style>
        /* New Customize Interface Start Here */
        body {
            position: relative;
            z-index: 1;
            background-color: rgb(241 245 249);
            font-family: Satoshi, sans-serif;
            font-size: 1rem;
            line-height: 1.5rem;
            font-weight: 400;
            color: rgb(100 116 139);
        }

        .modern-skin-dark .main-header .logo {
            background-color: rgb(28 36 52);
            color: #fff;
        }

        .modern-skin-dark .main-header .navbar {
            background: rgb(28 36 52);
        }

        .modern-skin-dark .main-sidebar .sidebar {
            background-color: rgb(28 36 52);
            bottom: 0;
        }

        .modern-skin-dark .main-sidebar {
            background-color: rgb(28 36 52);
            box-shadow: 0 0 5px rgba(0, 0, 0, .3);
        }

        .modern-skin-dark .main-header .navbar>a:focus,
        .modern-skin-dark .main-header .navbar>a:active,
        .modern-skin-dark .main-header .navbar>a:visited,
        .modern-skin-dark .main-header .navbar>a:hover {
            background-color: rgb(28 36 52);
        }

        .sidebar-menu li>a {
            position: relative;
            background-color: rgb(28 36 52);
        }

        .sidebar-menu li:focus,
        .sidebar-menu li :hover {
            color: #10d435;

        }

        .modern-skin-dark .main-sidebar .sidebar .sidebar-menu li.active a {
            background-color: #2e298e;
            border-radius: 5px;
            margin: 10px;

        }

        .modern-skin-dark .main-sidebar .sidebar .sidebar-menu {
            background-color: rgb(28 36 52);
        }

        .modern-skin-dark .main-sidebar .sidebar .sidebar-menu li .treeview-menu li.active a {
            background-color: transparent !important;
            color: rgb(84, 131, 227);
        }

        .modern-skin-dark .main-sidebar .sidebar .sidebar-menu li .treeview-menu li>a {
            background-color: transparent !important;
            padding: 10px 5px 5px 15px;
        }

        .modern-skin-dark .main-sidebar .sidebar .sidebar-menu li .treeview-menu {
            padding-left: 0;
            border-left: 3px solid #10d435;
        }

        .content-header {
            list-style-type: none;
            padding: 15px;
            background-color: #f6f9fc;

        }

        @media (max-width: 767px) {
            .content {
                padding: 0 15px !important;
                background-color: #f6f9fc;
            }
        }

        .content {
            padding: 25px !important;
            background-color: #f6f9fc;

        }

        .content-wrapper,
        .right-side {
            min-height: 100%;
            background-color: #f6f9fc;
            z-index: 800;
        }

        .main-footer {
            background: rgb(28 36 52);
            padding: 15px;
            color: rgb(100 116 139);
            border-top: 1px solid #d2d6de;
        }

        .panel-primary {
            border-color: #333;
        }

        .panel {
            margin-bottom: 20px;
            background-color: #fff;
            border: 0px solid transparent;
            border-radius: 21px;
            -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            box-shadow: 0px 4px 30px rgba(221, 224, 255, .54);
        }

        .panel-primary>.panel-heading {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .panel-primary>.panel-heading {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .panel-heading {
            padding: 10px 15px;
            border-bottom: 1px solid transparent;
            border-top-right-radius: 3px;
            border-top-left-radius: 3px;
        }

        .box.box-solid.box-primary>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .box-body {
            border-radius: 21px;
            padding: 10px;
        }

        .box.box-solid.box-primary {
            background-color: #fff;
            border: 0px solid transparent;
            border-radius: 21px;
            -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            box-shadow: 0px 4px 30px rgba(221, 224, 255, .54);
        }

        .content .row [class*=col-] .box {
            -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            box-shadow: 4px 4px 30px rgba(221, 224, 255, .54);
            -moz-box-shadow: 0 1px 1px rgba(0, 0, 0, .1);
            -ms-box-shadow: 0 1px 1px rgba(0, 0, 0, .1);
            -webkit-border-radius: 1px !important;
            -moz-border-radius: 1px !important;
            -ms-border-radius: 1px !important;
            border-radius: 15px !important;
            border-color: rgba(221, 224, 255, .54);
        }

        .box.box-solid.box-info>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .box-header {
            color: inherit;
            display: block;
            padding: 10px;
            position: relative;
            border-color: transparent;
            border-radius: 25px;
        }

        .box.box-solid.box-default>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .box.box-solid.box-success>.box-header {
            color: inherit;
            background: transparent;
            background-color: transparent;
        }

        .box.box-solid.box-primary>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .box.box-solid.box-info>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .box.box-solid.box-danger>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .box.box-solid.box-warning>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .box {
            position: relative;
            border-radius: 15px;
            margin-bottom: 20px;
            width: 100%;
            -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            box-shadow: 0px 4px 30px rgba(221, 224, 255, .54);
        }

        .modern-skin-dark .main-sidebar .sidebar .sidebar-menu li>a {
            font-weight: bold;
        }

        .content-header>h1 {
            font-weight: bold;
        }

        .box-header>.fa,
        .box-header>.glyphicon,
        .box-header>.ion,
        .box-header .box-title {
            font-weight: bold;
        }

        .main-header .logo .logo-lg {
            font-weight: bold;
        }

        /* New Customize Interface End Here */

        ::-moz-selection {
            /* Code for Firefox */
            color: red;
            background: yellow;
        }

        ::selection {
            color: red;
            background: yellow;
        }

        .content-wrapper {
            margin-top: 50px;
        }

        @media (max-width: 767px) {
            .content-wrapper {
                margin-top: 100px;
            }
        }


        .loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .loading::after {
            content: "";
            display: inline-block;
            width: 16px;
            height: 16px;
            vertical-align: middle;
            margin-left: 10px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s infinite linear;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .dropdown-menu .dropdown-item {
            margin-bottom: 5px;
        }

        .dropdown-menu .dropdown-item button {
            margin: 0;
            padding: 10px;
        }




        .toggle-container {
            cursor: pointer;
        }

        .toggle-container .toggle-icon {
            font-size: 25px;
            color: rgb(100 116 139);
            transition: color 0.5s ease;
        }

        @media (max-width: 600px) {

            .toggle-container .toggle-icon {
                font-size: 20px;
                color: rgb(100 116 139);
                transition: color 0.5s ease;
            }
        }


        /* dark mode styles start here */
        .dark-mode {
            background-color: #1a202c;
            color: #cbd5e0;
        }

        .dark-mode .main-header .logo,
        .dark-mode .main-header .navbar,
        .dark-mode .main-sidebar,
        .dark-mode .main-sidebar .sidebar,
        .dark-mode .sidebar-menu li>a {
            background-color: #0e1219;
            color: #cbd5e0;
        }

        .dark-mode .sidebar-menu li:hover,
        .dark-mode .sidebar-menu li:focus {
            color: #10d435;
        }

        .dark-mode .main-sidebar .sidebar .sidebar-menu li.active a {
            background-color: #2e298e;
        }

        .dark-mode .content,
        .dark-mode .content-header,
        .dark-mode .content-wrapper,
        .dark-mode .right-side {
            background-color: #0e1219;
        }

        .dark-mode .main-footer {
            background-color: #1a202c;
            color: #cbd5e0;
        }

        .dark-mode .panel,
        .dark-mode .box {
            background-color: #2d3748;
            border-color: #4a5568;
            box-shadow: none;
        }

        .dark-mode .panel-heading,
        .dark-mode .box-header {
            background-color: transparent;
            color: #cbd5e0;
        }

        .dark-mode .box-footer,
        .dark-mode .panel-footer {
            background-color: #2d3748;
        }

        .dark-mode .search-container {
            background-color: #2d3748;
            color: #cbd5e0;
        }

        .dark-mode .searchTerm {
            background-color: #4a5568;
            color: #cbd5e0;
        }

        .dark-mode .cancelButton {
            background-color: #e53e3e;
        }

        .dark-mode .notification-top-bar {
            background-color: #742a2a;
        }

        .dark-mode .bs-callout {
            background-color: #2d3748;
            border-color: #4a5568;
            color: #cbd5e0;
        }

        .dark-mode .bs-callout h4 {
            color: #cbd5e0;
        }

        .dark-mode .bg-gray {
            background-color: inherit !important;
        }

        .dark-mode .breadcrumb {
            padding: 8px 15px;
            margin-bottom: 20px;
            list-style: none;
            background-color: rgba(221, 224, 255, .54);
            border-radius: 4px;
        }

        .dark-mode .pagination>.disabled>span,
        .dark-mode .pagination>.disabled>span:hover,
        .dark-mode .pagination>.disabled>span:focus,
        .dark-mode .pagination>.disabled>a,
        .dark-mode .pagination>.disabled>a:hover,
        .dark-mode .pagination>.disabled>a:focus {
            color: inherit;
            background-color: rgba(221, 224, 255, .54);
            border-color: rgba(221, 224, 255, .54);
            cursor: not-allowed;
        }

        .dark-mode .pagination>.active>a,
        .dark-mode .pagination>.active>a:hover,
        .dark-mode .pagination>.active>a:focus,
        .dark-mode .pagination>.active>span,
        .dark-mode .pagination>.active>span:hover,
        .dark-mode .pagination>.active>span:focus {
            z-index: 2;
            color: #fff;
            background-color: #435ebe;
            border-color: rgba(221, 224, 255, .54);
            box-shadow: 0 2px 5px rgba(67, 94, 190, .3);
            cursor: default;
        }

        .dark-mode .pagination>li>a {
            background: inherit;
            color: inherit;
            border: 1px solid;
            border-color: rgba(221, 224, 255, .54);
        }

        .dark-mode .table {
            background-color: inherit;
            color: #ddd;
            border-color: #444;
        }

        .dark-mode .table th,
        .dark-mode .table td {
            background-color: inherit;
            border-color: inherit;
            color: #ddd;
        }

        .dark-mode .table th {
            background-color: inherit;
            font-weight: bold;
        }

        .dark-mode .table-striped tbody tr:nth-of-type(odd) {
            background-color: inherit;
        }

        .dark-mode .table-bordered {
            border: 1px solid #444;
        }

        .dark-mode .table-hover tbody tr:hover {
            background-color: #555;
            color: #fff;
        }

        .dark-mode .table-condensed th,
        .dark-mode .table-condensed td {
            padding: 8px;
        }

        .dark-mode .panel>.table:last-child,
        .dark-mode .panel>.table-responsive:last-child>.table:last-child {
            border-bottom-right-radius: 21px;
            border-bottom-left-radius: 21px;
        }

        .dark-mode .panel>.table:last-child>tbody:last-child>tr:last-child,
        .dark-mode .panel>.table:last-child>tfoot:last-child>tr:last-child,
        .dark-mode .panel>.table-responsive:last-child>.table:last-child>tbody:last-child>tr:last-child,
        .dark-mode .panel>.table-responsive:last-child>.table:last-child>tfoot:last-child>tr:last-child {
            border-bottom-right-radius: 21px;
            border-bottom-left-radius: 21px;
        }

        .dark-mode .panel>.table:last-child>tbody:last-child>tr:last-child td:last-child,
        .dark-mode .panel>.table:last-child>tbody:last-child>tr:last-child th:last-child,
        .dark-mode .panel>.table:last-child>tfoot:last-child>tr:last-child td:last-child,
        .dark-mode .panel>.table:last-child>tfoot:last-child>tr:last-child th:last-child,
        .dark-mode .panel>.table-responsive:last-child>.table:last-child>tbody:last-child>tr:last-child td:last-child,
        .dark-mode .panel>.table-responsive:last-child>.table:last-child>tbody:last-child>tr:last-child th:last-child,
        .dark-mode .panel>.table-responsive:last-child>.table:last-child>tfoot:last-child>tr:last-child td:last-child,
        .dark-mode .panel>.table-responsive:last-child>.table:last-child>tfoot:last-child>tr:last-child th:last-child {
            border-bottom-right-radius: 21px;
        }

        .dark-mode .help-block {
            display: block;
            margin-top: 5px;
            margin-bottom: 10px;
            color: inherit;
        }

        .dark-mode .text-muted {
            color: rgba(221, 224, 255, .54);
        }

        .dark-mode .form-control {
            display: block;
            width: 100%;
            padding: 6px 12px;
            font-size: 14px;
            line-height: 1.428571429;
            color: inherit;
            background-color: transparent;
            background-image: none;
            border: 1px solid;
            border-color: rgba(221, 224, 255, .54);
            border-radius: 4px;
            -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
            box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
            -webkit-transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
            -o-transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
            transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
        }

        .dark-mode .main-footer {
            border-top: 1px solid transparent;
        }

        .dark-mode .box.box-solid.box-primary>.box-header {
            color: #fff;
            background-color: inherit;
            border-color: rgba(221, 224, 255, .54);
            border-top-left-radius: 45px;
            border-top-right-radius: 45px;
        }

        .dark-mode .box-body {
            border-radius: 0px;
            padding: 10px;
        }

        .dark-mode .box-header {
            display: block;
            padding: 10px;
            position: relative;
            border-color: transparent;
            border-radius: 0px;
        }

        .dark-mode .nav-stacked>li>a {
            color: inherit;
        }

        .dark-mode .list-group-item {
            position: relative;
            display: block;
            padding: 10px 15px;
            margin-bottom: -1px;
            background-color: transparent;
            border: 1px solid rgba(221, 224, 255, .54);
        }

        .dark-mode .panel-footer {
            padding: 10px 15px;
            border-top: 1px rgba(221, 224, 255, .54);
            border-bottom-right-radius: 3px;
            border-bottom-left-radius: 3px;
        }


        .dark-mode .content .row [class*=col-] .box {
            -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            box-shadow: 4px 4px 30px rgba(221, 224, 255, .54);
            -moz-box-shadow: 0 1px 1px rgba(0, 0, 0, .1);
            -ms-box-shadow: 0 1px 1px rgba(0, 0, 0, .1);
            -webkit-border-radius: 1px !important;
            -moz-border-radius: 1px !important;
            -ms-border-radius: 1px !important;
            border-radius: 15px !important;
            border-color: inherit;
            background-color: inherit;
        }

        /* Dark Mode - Input Fields */
        .dark-mode input:not(#filterNavigateMenu),
        .dark-mode textarea:not(#filterNavigateMenu),
        .dark-mode select:not(#filterNavigateMenu),
        .dark-mode .select2-selection:not(#filterNavigateMenu) {
            color: inherit;
            transition: all .5s ease-in-out;
        }

        .dark-mode input:focus:not(#filterNavigateMenu),
        .dark-mode textarea:focus:not(#filterNavigateMenu),
        .dark-mode select:focus:not(#filterNavigateMenu),
        .dark-mode .select2-selection:focus:not(#filterNavigateMenu) {
            color: #1f201f;
            outline: none;
        }

        .dark-mode .input-group .form-control {
            position: relative;
            z-index: 2;
            float: left;
            width: 100%;
            margin-bottom: 0;
            color: inherit;
            border-color: rgba(221, 224, 255, .54);
            background-color: inherit;
        }

        .dark-mode .input-group .input-group-addon {
            border-top-left-radius: 0;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            color: inherit;
            border-bottom-left-radius: 0;
            border-color: rgba(221, 224, 255, .54);
            background-color: transparent;
        }

        .dark-mode .input-group .form-control:last-child,
        .dark-mode .input-group-addon:last-child,
        .dark-mode .input-group-btn:last-child>.btn,
        .dark-mode .input-group-btn:last-child>.btn-group>.btn,
        .dark-mode .input-group-btn:last-child>.dropdown-toggle,
        .dark-mode .input-group-btn:first-child>.btn:not(:first-child),
        .dark-mode .input-group-btn:first-child>.btn-group:not(:first-child)>.btn {
            color: inherit;
        }

        .dark-mode input:not(#filterNavigateMenu),
        textarea:not(#filterNavigateMenu),
        optgroup:not(#filterNavigateMenu),
        select:not(#filterNavigateMenu),
        .dark-mode .select2-selection:not(#filterNavigateMenu) {
            -moz-transition: all .5s ease-in-out;
            -o-transition: all .5s ease-in-out;
            -webkit-transition: all .5s ease-in-out;
            transition: all .5s ease-in-out;
        }

        .dark-mode .modern-skin-dark .main-sidebar .sidebar .sidebar-menu li>a {
            font-weight: bold;
        }

        .dark-mode .content-header>h1 {
            font-weight: bold;
        }

        .dark-mode .box-header>.fa,
        .dark-mode .box-header>.glyphicon,
        .dark-mode .box-header>.ion,
        .dark-mode .box-header .box-title {
            font-weight: bold;
        }

        .dark-mode .content-header>h2 {
            font-weight: bold;
        }

        .dark-mode .main-header .logo .logo-lg {
            font-weight: bold;
        }


        .dark-mode .modal-content {
            border-top-left-radius: 0;
            border-top-right-radius: 0;
            border-bottom-right-radius: 15px;
            border-bottom-left-radius: 15px;
            -webkit-box-shadow: 0 2px 3px rgba(0, 0, 0, .125);
            box-shadow: 0 2px 3px rgba(0, 0, 0, .125);
            border: 0;
            background: #1a202c;
        }

        .dark-mode .modal-header {
            padding: 15px;
            border-bottom: 1px solid rgba(221, 224, 255, .54);
            min-height: 16.428571429px;
            background-color: #1a202c;
            color: inherit;
        }


        .dark-mode .navbar-nav>.notifications-menu>.dropdown-menu>li .menu>li>a,
        .dark-mode .navbar-nav>.messages-menu>.dropdown-menu>li .menu>li>a,
        .dark-mode .navbar-nav>.tasks-menu>.dropdown-menu>li .menu>li>a {
            display: block;
            white-space: nowrap;
            border-bottom: 1px solid rgba(221, 224, 255, .54);
            background: #1a202c;
            color: inherit;
        }

        .dark-mode .navbar-nav>.notifications-menu>.dropdown-menu>li.footer>a,
        .dark-mode .navbar-nav>.messages-menu>.dropdown-menu>li.footer>a,
        .dark-mode .navbar-nav>.tasks-menu>.dropdown-menu>li.footer>a {
            background: #1a202c !important;
            color: inherit !important;
        }

        .dark-mode .navbar-nav>.user-menu>.dropdown-menu>.user-footer {
            background-color: #1a202c;
        }

        .dark-mode .ticket-container {
            background-color: #222020;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #ddd;
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .dark-mode .ticket-label {
            flex: 0 0 150px;
            font-weight: bold;
            color: inherit;
            margin-right: -59px;
        }
    </style>

    {if isset($xheader)}
        {$xheader}
    {/if}

</head>

<body class="hold-transition modern-skin-dark sidebar-mini">
    <div class="wrapper">
        <header class="main-header" style="position:fixed; width: 100%">
            <a href="{$_url}home" class="logo">
                <span class="logo-mini"><b>N</b>uX</span>
                <span class="logo-lg">{$_c['CompanyName']}</span>
            </a>
            <nav class="navbar navbar-static-top">
                <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                    <span class="sr-only">Toggle navigation</span>
                </a>
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        <li>
                            <a class="toggle-container" href="#">
                                <i class="toggle-icon" id="toggleIcon">🌞</i>
                            </a>
                        </li>
                        <li class="dropdown tasks-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                                <i class="fa fa-flag-o"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <!-- inner menu: contains the actual data -->
                                    <ul class="menu" api-get-text="{$_url}autoload_user/language&select={$user_language}"></ul>
                                </li>
                            </ul>
                        </li>
                        <li class="dropdown notifications-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <i class="fa fa-envelope-o"></i>
                                <span class="label label-warning"
                                    api-get-text="{$_url}autoload_user/inbox_unread"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <!-- inner menu: contains the actual data -->
                                    <ul class="menu" api-get-text="{$_url}autoload_user/inbox"></ul>
                                </li>
                                <li class="footer"><a href="{$_url}mail">{Lang::T('Inbox')}</a></li>
                            </ul>
                        </li>
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                {if $_c['enable_balance'] == 'yes'}
                                    <span
                                        style="color: whitesmoke;">&nbsp;{Lang::moneyFormat($_user['balance'])}&nbsp;</span>
                                {else}
                                    <span>{$_user['fullname']}</span>
                                {/if}
                                <img src="{$UPLOAD_PATH}{$_user['photo']}.thumb.jpg"
                                    onerror="this.src='{$UPLOAD_PATH}/user.default.jpg'" class="user-image"
                                    alt="User Image">
                            </a>
                            <ul class="dropdown-menu">
                                <li class="user-header">
                                    <img src="{$UPLOAD_PATH}{$_user['photo']}.thumb.jpg"
                                        onerror="this.src='{$UPLOAD_PATH}/user.default.jpg'" class="img-circle"
                                        alt="User Image">

                                    <p>
                                        {$_user['fullname']}
                                        <small>{$_user['phonenumber']}<br>
                                            {$_user['email']}</small>
                                    </p>
                                </li>
                                <li class="user-body">
                                    <div class="row">
                                        <div class="col-xs-7 text-center text-sm">
                                            <a href="{$_url}accounts/change-password"><i class="ion ion-settings"></i>
                                                {Lang::T('Change Password')}</a>
                                        </div>
                                        <div class="col-xs-5 text-center text-sm">
                                            <a href="{$_url}accounts/profile"><i class="ion ion-person"></i>
                                                {Lang::T('My Account')}</a>
                                        </div>
                                    </div>
                                </li>
                                <li class="user-footer">
                                    <div class="pull-right">
                                        <a href="{$_url}logout" class="btn btn-default btn-flat"><i
                                                class="ion ion-power"></i> {Lang::T('Logout')}</a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>

        <aside class="main-sidebar" style="position:fixed;">
            <section class="sidebar">
                <ul class="sidebar-menu" data-widget="tree">
                    <li {if $_system_menu eq 'home'}class="active" {/if}>
                        <a href="{$_url}home">
                            <i class="ion ion-monitor"></i>
                            <span>{Lang::T('Dashboard')}</span>
                        </a>
                    </li>
                    {$_MENU_AFTER_DASHBOARD}
                    <li {if $_system_menu eq 'inbox'}class="active" {/if}>
                        <a href="{$_url}mail">
                            <i class="fa fa-envelope"></i>
                            <span>{Lang::T('Inbox')}</span>
                        </a>
                    </li>
                    {$_MENU_AFTER_INBOX}
                    {if $_c['disable_voucher'] != 'yes'}
                        <li {if $_system_menu eq 'voucher'}class="active" {/if}>
                            <a href="{$_url}voucher/activation">
                                <i class="fa fa-ticket"></i>
                                <span>Voucher</span>
                            </a>
                        </li>
                    {/if}
                    {if $_c['payment_gateway'] != 'none' or $_c['payment_gateway'] == '' }
                        {if $_c['enable_balance'] == 'yes'}
                            <li {if $_system_menu eq 'balance'}class="active" {/if}>
                                <a href="{$_url}order/balance">
                                    <i class="ion ion-ios-cart"></i>
                                    <span>{Lang::T('Buy Balance')}</span>
                                </a>
                            </li>
                        {/if}
                        <li {if $_system_menu eq 'package'}class="active" {/if}>
                            <a href="{$_url}order/package">
                                <i class="ion ion-ios-cart"></i>
                                <span>{Lang::T('Buy Package')}</span>
                            </a>
                        </li>
                        <li {if $_system_menu eq 'history'}class="active" {/if}>
                            <a href="{$_url}order/history">
                                <i class="fa fa-file-text"></i>
                                <span>{Lang::T('Order History')}</span>
                            </a>
                        </li>
                    {/if}
                    {$_MENU_AFTER_ORDER}
                    <li {if $_system_menu eq 'list-activated'}class="active" {/if}>
                        <a href="{$_url}voucher/list-activated">
                            <i class="fa fa-list-alt"></i>
                            <span>{Lang::T('Activation History')}</span>
                        </a>
                    </li>
                    {$_MENU_AFTER_HISTORY}
                </ul>
            </section>
        </aside>

        <div class="content-wrapper">
            <section class="content-header">
                <h1>
                    {$_title}
                </h1>
            </section>
            <section class="content">


                {if isset($notify)}
                    <script>
                        // Display SweetAlert toast notification
                        Swal.fire({
                            icon: '{if $notify_t == "s"}success{else}warning{/if}',
                            title: '{$notify}',
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });
                    </script>
{/if}
