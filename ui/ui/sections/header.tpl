<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{$_title} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

    <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">

    <link rel="stylesheet" href="ui/ui/fonts/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="ui/ui/styles/modern-AdminLTE.min.css">
    <link rel="stylesheet" href="ui/ui/styles/select2.min.css" />
    <link rel="stylesheet" href="ui/ui/styles/select2-bootstrap.min.css" />
    <link rel="stylesheet" href="ui/ui/styles/sweetalert2.min.css" />
    <link rel="stylesheet" href="ui/ui/styles/plugins/pace.css" />
    <link rel="stylesheet" href="ui/ui/summernote/summernote.min.css" />
    <script src="ui/ui/scripts/sweetalert2.all.min.js"></script>
    <style>
        /* New Customize Interface Start Here */
        @import url(https://fonts.googleapis.com/css?family=Open+Sans);

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
            border: 2px solid;
            border-color: rgba(221, 224, 255, .54);
            border-radius: 25px;
            -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            box-shadow: 0px 4px 30px rgba(221, 224, 255, .54);
        }

        .panel-primary>.panel-heading {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
            border-bottom-right-radius: 21px;
            border-bottom-left-radius: 21px;
        }

        .panel-success>.panel-heading {
            border-bottom-right-radius: 21px;
            border-bottom-left-radius: 21px;
        }

        .panel-cron-success>.panel-heading {
            border-bottom-right-radius: 21px;
            border-bottom-left-radius: 21px;
            color: #fff;
            background-color: #169210;
            border-color: #25e01c;

        }

        .panel-cron-warning>.panel-heading {
            border-bottom-right-radius: 21px;
            border-bottom-left-radius: 21px;
            color: #350808;
            background-color: #efeb0a;
            border-color: #efeb0a;
        }
        .panel-cron-danger>.panel-heading {
            border-bottom-right-radius: 21px;
            border-bottom-left-radius: 21px;
            color: #fff;
            background-color: #e61212;
            border-color: #df1335;
        }

        .panel-danger>.panel-heading {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }

        .panel-heading {
            padding: 10px 15px;
            border-bottom: 1px solid transparent;
            border-top-right-radius: 3px;
            border-top-left-radius: 3px;
        }

        .content .row [class*=col-] .box {
            -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            box-shadow: 0px 4px 30px rgba(221, 224, 255, .54);
            -moz-box-shadow: 0 1px 1px rgba(0, 0, 0, .1);
            -ms-box-shadow: 0 1px 1px rgba(0, 0, 0, .1);
            -webkit-border-radius: 1px !important;
            -moz-border-radius: 1px !important;
            -ms-border-radius: 1px !important;
            border-radius: 15px !important;
            border-color: rgba(221, 224, 255, .54);
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

        .box.box-solid.box-default>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .box-footer {
            border-top-left-radius: 0;
            border-top-right-radius: 0;
            border-bottom-right-radius: 25px;
            border-bottom-left-radius: 25px;
            border-top: 1px solid transparent;
            padding: 10px;
            background-color: inherit;
            border-radius: 15px;
        }

        .panel-footer {
            padding: 10px 15px;
            background-color: inherit;
            border-top: 1px solid transparent;
            border-bottom-right-radius: 25px;
            border-bottom-left-radius: 25px;
        }

        .box {
            position: relative;
            border-radius: 25px;
            background: inherit;
            border-top: 3px solid #d2d6de;
            margin-bottom: 20px;
            width: 100%;
            -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            box-shadow: 0px 4px 30px rgba(221, 224, 255, .54);
        }

        .panel-success>.panel-heading {
            color: #3c763d;
            background-color: transparent;
            border-color: #d6e9c6;
        }

        .content-header>h1 {
            font-weight: bold;
        }

         .box.box-solid.box-primary > .box-header .btn {
            color: inherit;
        }

        .box-header>.fa,
        .box-header>.glyphicon,
        .box-header>.ion,
        .box-header .box-title {
            font-weight: bold;
        }

        .modern-skin-dark .main-sidebar .sidebar .sidebar-menu li>a {
            font-weight: bold;
        }

        .main-header .logo .logo-lg {
            font-weight: bold;
        }

        /* Search Bar Start Here */
        .wrap {
            width: 30%;
            position: absolute;
            top: 50%;
            left: 47%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            text-align: center;
        }

        .search {
            padding: 10px 20px;
            border-radius: 50px;
            border: 1px solid #2e298e;
            background-color: #2e298e;
            color: white;
            cursor: pointer;
            width: 50%;
            height: 50%;
        }

        .search-overlay {
            display: none;
            /* Hidden by default */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .search-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 100%;
            max-width: 600px;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .searchTerm {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #00B4CC;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .cancelButton {
            padding: 10px;
            border-radius: 5px;
            background-color: #ff4d4d;
            color: white;
            border: none;
            cursor: pointer;
        }

        .search-results {
            max-height: 200px;
            overflow-y: auto;
        }

        .panel-heading {
            padding: 10px 15px;
            border-bottom: 0px solid transparent;
            border-top-right-radius: 21px;
            border-top-left-radius: 21px;
        }

        /* Search Bar End Here */

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

        .select2-container .select2-selection--single .select2-selection__rendered {
            margin-top: 0px !important;
        }

        @media (min-width: 768px) {
            .outer {
                height: 200px
                    /* Or whatever */
            }
        }

        th:first-child,
        td:first-child {
            position: sticky;
            background-color: #f9f9f9;
        }


        .text1line {
            display: block;
            /* or inline-block */
            text-overflow: ellipsis;
            word-wrap: break-word;
            overflow: hidden;
            max-height: 1em;
            line-height: 1em;
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

        /*
    * maintenance top-bar
    */

        .notification-top-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 40px;
            line-height: 40px;
            width: 100%;
            background: #ec2106;
            text-align: center;
            color: #FFFFFF;
            font-family: serif;
            font-weight: bolder;
            font-size: 14px;
            z-index: 9999;
            box-sizing: border-box;
            padding: 0 10px;
        }

        .notification-top-bar p {
            padding: 0;
            margin: 0;
        }

        .notification-top-bar p a {
            padding: 5px 10px;
            border-radius: 3px;
            background: #FFF;
            color: #1ABC9C;
            font-weight: bold;
            text-decoration: none;
            display: inline;
            font-size: inherit;
        }

        @media (max-width: 600px) {
            .notification-top-bar {
                font-size: 12px;
                height: auto;
                line-height: normal;
                padding: 10px;
            }

            .notification-top-bar p a {
                padding: 5px 10px;
                margin: 5px 0;
                font-size: 10px;
                display: inline-block;
            }
        }

        .bs-callout {
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #eee;
            border-left-width: 5px;
            border-radius: 3px;
        }

        .bs-callout h4 {
            margin-top: 0;
            margin-bottom: 5px
        }

        .bs-callout p:last-child {
            margin-bottom: 0
        }

        .bs-callout code {
            border-radius: 3px
        }

        .bs-callout+.bs-callout {
            margin-top: -5px
        }

        .bs-callout-danger {
            border-left-color: #ce4844
        }

        .bs-callout-danger h4 {
            color: #ce4844
        }

        .bs-callout-warning {
            border-left-color: #aa6708
        }

        .bs-callout-warning h4 {
            color: #aa6708
        }

        .bs-callout-info {
            border-left-color: #1b809e
        }

        .bs-callout-info h4 {
            color: #1b809e
        }

        /* Checkbox container */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        /* Hidden checkbox */
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        /* Slider */
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #2196F3;
        }

        input:focus+.slider {
            box-shadow: 0 0 1px #2196F3;
        }

        input:checked+.slider:before {
            -webkit-transform: translateX(26px);
            -ms-transform: translateX(26px);
            transform: translateX(26px);
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
            background-color: #1a202c;
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
            background-color: #2d3748;
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

        .toggle-container {
            cursor: pointer;
        }

        .toggle-container .toggle-icon {
            font-size: 25px;
            color: rgb(100 116 139);
            transition: color 0.5s ease;
        }

        .dark-mode .toggle-container .toggle-icon {
            color: #ffdd57;
        }

        .dark-mode th:first-child,
        .dark-mode td:first-child {
            background-color: #4a4949;
        }

        .dark-mode .panel>.table:last-child>tbody:last-child>tr:last-child td:first-child,
        .dark-mode .panel>.table:last-child>tbody:last-child>tr:last-child th:first-child,
        .dark-mode .panel>.table:last-child>tfoot:last-child>tr:last-child td:first-child,
        .dark-mode .panel>.table:last-child>tfoot:last-child>tr:last-child th:first-child,
        .dark-mode .panel>.table-responsive:last-child>.table:last-child>tbody:last-child>tr:last-child td:first-child,
        .dark-mode .panel>.table-responsive:last-child>.table:last-child>tbody:last-child>tr:last-child th:first-child,
        .dark-mode .panel>.table-responsive:last-child>.table:last-child>tfoot:last-child>tr:last-child td:first-child,
        .dark-mode .panel>.table-responsive:last-child>.table:last-child>tfoot:last-child>tr:last-child th:first-child {
            background-color: #4a4949;
            border-bottom-left-radius: 21px;
        }

        .dark-mode .table>thead>tr>td.danger,
        .dark-mode .table>thead>tr>th.danger,
        .dark-mode .table>thead>tr.danger>td,
        .dark-mode .table>thead>tr.danger>th,
        .dark-mode .table>tbody>tr>td.danger,
        .dark-mode .table>tbody>tr>th.danger,
        .dark-mode .table>tbody>tr.danger>td,
        .dark-mode .table>tbody>tr.danger>th,
        .dark-mode .table>tfoot>tr>td.danger,
        .dark-mode .table>tfoot>tr>th.danger,
        .dark-mode .table>tfoot>tr.danger>td,
        .dark-mode .table>tfoot>tr.danger>th {
            background-color: #694760;
        }

        .dark-mode .table>thead>tr>td.warning,
        .dark-mode .table>thead>tr>th.warning,
        .dark-mode .table>thead>tr.warning>td,
        .dark-mode .table>thead>tr.warning>th,
        .dark-mode .table>tbody>tr>td.warning,
        .dark-mode .table>tbody>tr>th.warning,
        .dark-mode .table>tbody>tr.warning>td,
        .dark-mode .table>tbody>tr.warning>th,
        .dark-mode .table>tfoot>tr>td.warning,
        .dark-mode .table>tfoot>tr>th.warning,
        .dark-mode .table>tfoot>tr.warning>td,
        .dark-mode .table>tfoot>tr.danger>th {
            background-color: #787c63;
            color: #ffffff;
        }

        .dark-mode .table>thead>tr>td.success,
        .dark-mode .table>thead>tr>th.success,
        .dark-mode .table>thead>tr.success>td,
        .dark-mode .table>thead>tr.success>th,
        .dark-mode .table>tbody>tr>td.success,
        .dark-mode .table>tbody>tr>th.success,
        .dark-mode .table>tbody>tr.success>td,
        .dark-mode .table>tbody>tr.success>th,
        .dark-mode .table>tfoot>tr>td.success,
        .dark-mode .table>tfoot>tr>th.success,
        .dark-mode .table>tfoot>tr.success>td,
        .dark-mode .table>tfoot>tr.success>th {
            background-color: #7dcba7;
            color: #ffffff;
        }

        .dark-mode .panel-heading {
            padding: 10px 15px;
            border-bottom: 1px solid transparent;
            border-top-right-radius: 21px;
            border-top-left-radius: 21px;
        }

        .dark-mode .table-bordered>thead>tr>th,
        .dark-mode .table-bordered>thead>tr>td,
        .dark-mode .table-bordered>tbody>tr>th,
        .dark-mode .table-bordered>tbody>tr>td,
        .dark-mode .table-bordered>tfoot>tr>th,
        .dark-mode .table-bordered>tfoot>tr>td {
            border: 1px solid;
            border-color: rgba(221, 224, 255, .54);
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

        .dark-mode .nav-stacked>li>a {
            color: inherit;
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

        .dark-mode .help-block {
            display: block;
            margin-top: 5px;
            margin-bottom: 10px;
            color: inherit;
        }

        .dark-mode .text-muted {
            color: rgba(221, 224, 255, .54);
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
            background-color: #2a2a2a;
            color: #ddd;
            border-color: #444;
        }

        .dark-mode .table th,
        .dark-mode .table td {
            background-color: #333;
            border-color: #444;
            color: #ddd;
        }

        .dark-mode .table th {
            background-color: #444;
            font-weight: bold;
        }

        .dark-mode .table-striped tbody tr:nth-of-type(odd) {
            background-color: #3a3a3a;
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

        /* Dark Mode - Select2 Dropdown start here */
        .dark-mode .select2-container--bootstrap .select2-results__option--highlighted[aria-selected] {
            background-color: rgb(96, 89, 89);
            color: #ffffff;
        }

        .dark-mode .select2-container--bootstrap .select2-results__option {
            padding: 6px 12px;
            background-color: rgb(96, 89, 89);
            color: #f8f9fa;
        }

        .dark-mode .select2-results__option[aria-selected] {
            cursor: pointer;
            background-color: inherit;
            color: #ffffff;
        }

        .dark-mode .select2-results__option {
            padding: 6px 12px;
            user-select: none;
            -webkit-user-select: none;
            background-color: #343a40;
            color: #f8f9fa;
        }

        .dark-mode .select2-dropdown {
            background-color: #343a40;
            border-color: #454d55;
        }

        .dark-mode .select2-selection--single {
            background-color: #495057;
            color: #ffffff;
            border-color: #454d55;
        }

        .dark-mode .select2-selection__rendered {
            color: #ffffff;
        }

        .dark-mode .select2-selection__arrow b {
            border-color: #ffffff transparent transparent transparent;
        }

        .dark-mode .select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
            color: inherit;
            padding: 0;
        }

        .dark-mode .main-footer {
            border-top: 1px solid transparent;
        }

        .dark-mode .list-group-item {
            position: relative;
            display: block;
            padding: 10px 15px;
            margin-bottom: -1px;
            background-color: transparent;
            border: 1px solid rgba(221, 224, 255, .54);
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

        .dark-mode .navbar-nav>.notifications-menu>.dropdown-menu>li.footer>a,
        .dark-mode .navbar-nav>.messages-menu>.dropdown-menu>li.footer>a,
        .dark-mode .navbar-nav>.tasks-menu>.dropdown-menu>li.footer>a {
            background: #1a202c !important;
            color: inherit !important;
        }

        .dark-mode .navbar-nav>.user-menu>.dropdown-menu>.user-footer {
            background-color: #1a202c;
        }

        .dark-mode .nav-tabs-custom>.tab-content {
            background-color: #1a202c;
            padding: 10px;
            border-bottom-right-radius: 15px;
            border-bottom-left-radius: 15px;
        }

        .dark-mode .nav-tabs-custom {
            margin-bottom: 20px;
            background: #1a202c;
            box-shadow: 0 1px 1px rgba(0, 0, 0, .1);
            border-radius: 15px;
        }

        .dark-mode .nav-tabs-custom>.nav-tabs>li:first-of-type.active>a {
            border-left-color: transparent;
        }

        .dark-mode .nav-tabs-custom>.nav-tabs>li.active>a {
            border-top-color: transparent;
            border-left-color: rgba(221, 224, 255, .54);
            border-right-color: rgba(221, 224, 255, .54);
            color: inherit;
            background-color: #1a202c;
        }

        .dark-mode pre {
            display: block;
            padding: 9.5px;
            margin: 0 0 10px;
            font-size: 13px;
            line-height: 1.428571429;
            word-break: break-all;
            word-wrap: break-word;
            color: inherit;
            background-color: inherit;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        /* Dark Mode - Select2 Dropdown ends here */

        /* dark mode styles start ends here */
    </style>
    {if isset($xheader)}
        {$xheader}
    {/if}

</head>

<body class="hold-transition modern-skin-dark sidebar-mini {if $_kolaps}sidebar-collapse{/if}">
    <div class="wrapper">
        <header class="main-header">
            <a href="{$_url}dashboard" class="logo">
                <span class="logo-mini"><b>N</b>uX</span>
                <span class="logo-lg">{$_c['CompanyName']}</span>
            </a>
            <nav class="navbar navbar-static-top">
                <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button" onclick="return setKolaps()">
                    <span class="sr-only">Toggle navigation</span>
                </a>
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        <div class="wrap">
                            <div class="">
                                <button id="openSearch" class="search"><i class="fa fa-search x2"></i></button>
                            </div>
                        </div>

                        <div id="searchOverlay" class="search-overlay">
                            <div class="search-container">
                                <input type="text" id="searchTerm" class="searchTerm"
                                    placeholder="{Lang::T('Search Users')}" autocomplete="off">
                                <div id="searchResults" class="search-results">
                                    <!-- Search results will be displayed here -->
                                </div>
                                <button type="button" id="closeSearch" class="cancelButton">{Lang::T('Cancel')}</button>
                            </div>
                        </div>
                        <li>
                            <a class="toggle-container" href="#">
                                <i class="toggle-icon" id="toggleIcon">🌞</i>
                            </a>
                        </li>
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <img src="{$UPLOAD_PATH}{$_admin['photo']}.thumb.jpg"
                                    onerror="this.src='{$UPLOAD_PATH}/admin.default.png'" class="user-image"
                                    alt="Avatar">
                                <span class="hidden-xs">{$_admin['fullname']}</span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="user-header">
                                    <img src="{$UPLOAD_PATH}{$_admin['photo']}.thumb.jpg"
                                        onerror="this.src='{$UPLOAD_PATH}/admin.default.png'" class="img-circle"
                                        alt="Avatar">
                                    <p>
                                        {$_admin['fullname']}
                                        <small>{Lang::T($_admin['user_type'])}</small>
                                    </p>
                                </li>
                                <li class="user-body">
                                    <div class="row">
                                        <div class="col-xs-7 text-center text-sm">
                                            <a href="{$_url}settings/change-password"><i class="ion ion-settings"></i>
                                                {Lang::T('Change Password')}</a>
                                        </div>
                                        <div class="col-xs-5 text-center text-sm">
                                            <a href="{$_url}settings/users-view/{$_admin['id']}">
                                                <i class="ion ion-person"></i> {Lang::T('My Account')}</a>
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
        <aside class="main-sidebar">
            <section class="sidebar">
                <ul class="sidebar-menu" data-widget="tree">
                    <li {if $_system_menu eq 'dashboard' }class="active" {/if}>
                        <a href="{$_url}dashboard">
                            <i class="ion ion-monitor"></i>
                            <span>{Lang::T('Dashboard')}</span>
                        </a>
                    </li>
                    {$_MENU_AFTER_DASHBOARD}
                    {if !in_array($_admin['user_type'],['Report'])}
                        <li class="{if in_array($_system_menu, ['customers', 'map'])}active{/if} treeview">
                            <a href="#">
                                <i class="fa fa-users"></i> <span>{Lang::T('Customer')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_system_menu eq 'customers' }class="active" {/if}><a
                                        href="{$_url}customers">{Lang::T('Lists')}</a></li>
                                <li {if $_system_menu eq 'map' }class="active" {/if}><a
                                        href="{$_url}map/customer">{Lang::T('Location')}</a></li>
                                {$_MENU_CUSTOMERS}
                            </ul>
                        </li>
                        {$_MENU_AFTER_CUSTOMERS}
                        <li class="{if $_system_menu eq 'plan'}active{/if} treeview">
                            <a href="#">
                                <i class="fa fa-ticket"></i> <span>{Lang::T('Services')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'list' }class="active" {/if}><a
                                        href="{$_url}plan/list">{Lang::T('Active Users')}</a></li>
                                {if $_c['disable_voucher'] != 'yes'}
                                    <li {if $_routes[1] eq 'voucher' }class="active" {/if}><a
                                            href="{$_url}plan/voucher">{Lang::T('Vouchers')}</a></li>
                                    <li {if $_routes[1] eq 'refill' }class="active" {/if}><a
                                            href="{$_url}plan/refill">{Lang::T('Refill Customer')}</a></li>
                                {/if}
                                <li {if $_routes[1] eq 'recharge' }class="active" {/if}><a
                                        href="{$_url}plan/recharge">{Lang::T('Recharge Customer')}</a></li>
                                {if $_c['enable_balance'] == 'yes'}
                                    <li {if $_routes[1] eq 'deposit' }class="active" {/if}><a
                                            href="{$_url}plan/deposit">{Lang::T('Refill Balance')}</a></li>
                                {/if}
                                {$_MENU_SERVICES}
                            </ul>
                        </li>
                    {/if}
                    {$_MENU_AFTER_SERVICES}
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li class="{if $_system_menu eq 'services'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-cube"></i> <span>{Lang::T('Internet Plan')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'hotspot' }class="active" {/if}><a
                                        href="{$_url}services/hotspot">Hotspot</a></li>
                                <li {if $_routes[1] eq 'pppoe' }class="active" {/if}><a
                                        href="{$_url}services/pppoe">PPPOE</a></li>
                                <li {if $_routes[1] eq 'vpn' }class="active" {/if}><a
                                        href="{$_url}services/vpn">VPN</a></li>
                                <li {if $_routes[1] eq 'list' }class="active" {/if}><a
                                        href="{$_url}bandwidth/list">Bandwidth</a></li>
                                {if $_c['enable_balance'] == 'yes'}
                                    <li {if $_routes[1] eq 'balance' }class="active" {/if}><a
                                            href="{$_url}services/balance">{Lang::T('Customer Balance')}</a></li>
                                {/if}
                                {$_MENU_PLANS}
                            </ul>
                        </li>
                    {/if}
                    {$_MENU_AFTER_PLANS}
                    <li class="{if $_system_menu eq 'reports'}active{/if} treeview">
                        {if in_array($_admin['user_type'],['SuperAdmin','Admin', 'Report'])}
                            <a href="#">
                                <i class="ion ion-clipboard"></i> <span>{Lang::T('Reports')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                        {/if}
                        <ul class="treeview-menu">
                            <li {if $_routes[1] eq 'reports' }class="active" {/if}><a
                                    href="{$_url}reports">{Lang::T('Daily Reports')}</a></li>
                            <li {if $_routes[1] eq 'activation' }class="active" {/if}><a
                                    href="{$_url}reports/activation">{Lang::T('Activation History')}</a></li>
                            {$_MENU_REPORTS}
                        </ul>
                    </li>
                    {$_MENU_AFTER_REPORTS}
                    <li class="{if $_system_menu eq 'message'}active{/if} treeview">
                        <a href="#">
                            <i class="ion ion-android-chat"></i> <span>{Lang::T('Send Message')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_routes[1] eq 'send' }class="active" {/if}><a
                                    href="{$_url}message/send">{Lang::T('Single Customer')}</a></li>
                            <li {if $_routes[1] eq 'send_bulk' }class="active" {/if}><a
                                    href="{$_url}message/send_bulk">{Lang::T('Bulk Customers')}</a></li>
                            {$_MENU_MESSAGE}
                        </ul>
                    </li>
                    {$_MENU_AFTER_MESSAGE}
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li class="{if $_system_menu eq 'network'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-network"></i> <span>{Lang::T('Network')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[0] eq 'routers' and $_routes[1] eq '' }class="active" {/if}><a
                                        href="{$_url}routers">Routers</a></li>
                                <li {if $_routes[0] eq 'pool' and $_routes[1] eq 'list' }class="active" {/if}><a
                                        href="{$_url}pool/list">IP Pool</a></li>
                                <li {if $_routes[0] eq 'pool' and $_routes[1] eq 'port' }class="active" {/if}><a
                                        href="{$_url}pool/port">Port Pool</a></li>
                                <li {if $_routes[0] eq 'routers' and $_routes[1] eq 'maps' }class="active" {/if}><a
                                        href="{$_url}routers/maps">{Lang::T('Routers Maps')}</a></li>
                                {$_MENU_NETWORK}
                            </ul>
                        </li>
                        {$_MENU_AFTER_NETWORKS}
                        {if $_c['radius_enable']}
                            <li class="{if $_system_menu eq 'radius'}active{/if} treeview">
                                <a href="#">
                                    <i class="fa fa-database"></i> <span>{Lang::T('Radius')}</span>
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">
                                    <li {if $_routes[0] eq 'radius' and $_routes[1] eq 'nas-list' }class="active" {/if}><a
                                            href="{$_url}radius/nas-list">{Lang::T('Radius NAS')}</a></li>
                                    {$_MENU_RADIUS}
                                </ul>
                            </li>
                        {/if}
                        {$_MENU_AFTER_RADIUS}
                        <li class="{if $_system_menu eq 'pages'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-document"></i> <span>{Lang::T("Static Pages")}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'Order_Voucher' }class="active" {/if}><a
                                        href="{$_url}pages/Order_Voucher">{Lang::T('Order Voucher')}</a></li>
                                <li {if $_routes[1] eq 'Voucher' }class="active" {/if}><a
                                        href="{$_url}pages/Voucher">{Lang::T('Theme Voucher')}</a></li>
                                <li {if $_routes[1] eq 'Announcement' }class="active" {/if}><a
                                        href="{$_url}pages/Announcement">{Lang::T('Announcement')}</a></li>
                                <li {if $_routes[1] eq 'Announcement_Customer' }class="active" {/if}><a
                                        href="{$_url}pages/Announcement_Customer">{Lang::T('Customer Announcement')}</a>
                                </li>
                                <li {if $_routes[1] eq 'Registration_Info' }class="active" {/if}><a
                                        href="{$_url}pages/Registration_Info">{Lang::T('Registration Info')}</a></li>
                                <li {if $_routes[1] eq 'Payment_Info' }class="active" {/if}><a
                                        href="{$_url}pages/Payment_Info">{Lang::T('Payment Info')}</a></li>
                                <li {if $_routes[1] eq 'Privacy_Policy' }class="active" {/if}><a
                                        href="{$_url}pages/Privacy_Policy">{Lang::T('Privacy Policy')}</a></li>
                                <li {if $_routes[1] eq 'Terms_and_Conditions' }class="active" {/if}><a
                                        href="{$_url}pages/Terms_and_Conditions">{Lang::T('Terms and Conditions')}</a></li>
                                {$_MENU_PAGES}
                            </ul>
                        </li>
                    {/if}
                    {$_MENU_AFTER_PAGES}
                    <li
                        class="{if $_system_menu eq 'settings' || $_system_menu eq 'paymentgateway' }active{/if} treeview">
                        <a href="#">
                            <i class="ion ion-gear-a"></i> <span>{Lang::T('Settings')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                <li {if $_routes[1] eq 'app' }class="active" {/if}><a
                                        href="{$_url}settings/app">{Lang::T('General Settings')}</a></li>
                                <li {if $_routes[1] eq 'localisation' }class="active" {/if}><a
                                        href="{$_url}settings/localisation">{Lang::T('Localisation')}</a></li>
                                <li {if $_routes[1] eq 'miscellaneous' }class="active" {/if}><a
                                            href="{$_url}settings/miscellaneous">{Lang::T('Miscellaneous')}</a></li>
                                <li {if $_routes[1] eq 'maintenance' }class="active" {/if}><a
                                        href="{$_url}settings/maintenance">{Lang::T('Maintenance Mode')}</a></li>
                                <li {if $_routes[1] eq 'notifications' }class="active" {/if}><a
                                        href="{$_url}settings/notifications">{Lang::T('User Notification')}</a></li>
                                <li {if $_routes[1] eq 'devices' }class="active" {/if}><a
                                        href="{$_url}settings/devices">{Lang::T('Devices')}</a></li>
                            {/if}
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin','Agent'])}
                                <li {if $_routes[1] eq 'users' }class="active" {/if}><a
                                        href="{$_url}settings/users">{Lang::T('Administrator Users')}</a></li>
                            {/if}
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                <li {if $_routes[1] eq 'dbstatus' }class="active" {/if}><a
                                        href="{$_url}settings/dbstatus">{Lang::T('Backup/Restore')}</a></li>
                                <li {if $_system_menu eq 'paymentgateway' }class="active" {/if}>
                                    <a href="{$_url}paymentgateway">
                                        <span class="text">{Lang::T('Payment Gateway')}</span>
                                    </a>
                                </li>
                                {$_MENU_SETTINGS}
                                <li {if $_routes[0] eq 'pluginmanager' }class="active" {/if}>
                                    <a href="{$_url}pluginmanager"><i class="glyphicon glyphicon-tasks"></i>
                                        {Lang::T('Plugin Manager')}</a>
                                </li>
                            {/if}
                        </ul>
                    </li>
                    {$_MENU_AFTER_SETTINGS}
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li class="{if $_system_menu eq 'logs' }active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-clock"></i> <span>{Lang::T('Logs')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'list' }class="active" {/if}><a
                                        href="{$_url}logs/phpnuxbill">PhpNuxBill</a></li>
                                {if $_c['radius_enable']}
                                    <li {if $_routes[1] eq 'radius' }class="active" {/if}><a
                                            href="{$_url}logs/radius">Radius</a>
                                    </li>
                                {/if}
                                {$_MENU_LOGS}
                            </ul>
                        </li>
                    {/if}
                    {$_MENU_AFTER_LOGS}
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li {if $_routes[1] eq 'docs' }class="active" {/if}>
                            <a href="{if $_c['docs_clicked'] != 'yes'}{$_url}settings/docs{else}./docs/{/if}">
                                <i class="ion ion-ios-bookmarks"></i>
                                <span class="text">{Lang::T('Documentation')}</span>
                                {if $_c['docs_clicked'] != 'yes'}
                                    <span class="pull-right-container"><small
                                            class="label pull-right bg-green">New</small></span>
                                {/if}
                            </a>
                        </li>
                        <li {if $_system_menu eq 'community' }class="active" {/if}>
                            <a href="{$_url}community">
                                <i class="ion ion-chatboxes"></i>
                                <span class="text">Community</span>
                            </a>
                        </li>
                    {/if}
                    {$_MENU_AFTER_COMMUNITY}
                </ul>
            </section>
        </aside>

        {if $_c['maintenance_mode'] == 1}
            <div class="notification-top-bar">
                <p>{Lang::T('The website is currently in maintenance mode, this means that some or all functionality may be
                unavailable to regular users during this time.')}<small> &nbsp;&nbsp;<a
                            href="{$_url}settings/maintenance">{Lang::T('Turn Off')}</a></small></p>
            </div>
        {/if}

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
                            icon: '{if $notify_t == "s"}success{else}error{/if}',
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
