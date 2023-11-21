<!DOCTYPE html>
<html lang='en'>

<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>

    <title>Admin</title>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
<script src='https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js'></script>
<script src='https://oss.maxcdn.com/respond/1.4.2/respond.min.js'></script>
<![endif]-->
    <base href='{{ asset('/') }}'>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href='{{ asset('admin-view/css/admin/custom.css') }}' rel='stylesheet'>
    <link rel='stylesheet' href='{{ asset('admin-view/css/admin/bootstrap.css') }}'>
    <link rel='stylesheet' href='{{ asset('admin-view/css/admin/style.css') }}'>
    <link rel='stylesheet' href='{{ asset('admin-view/css/admin/toastDemo.css') }}'>
    <link rel='stylesheet' href='{{ asset('admin-view/js/datepicker/css/bootstrap-datepicker3.min.css') }}'>
    <link rel='stylesheet' href='{{ asset('admin-view/css/admin/tinytoggle.min.css') }}' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
    <link rel='stylesheet'
        href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.7.5/css/bootstrap-select.min.css'>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-glyphicons.css">
    <link href="{{ asset('admin-view/css/icon-font.css') }}" rel="stylesheet" />

    <style>
        .navbar .nav > li > a{
            padding: 15px 8px;
            font-size: 13px
        }
        .card-box.card {
            padding-top: 10px;
        }
        .card-header-alt{
            margin-bottom: 10px
        }
        .card-box__inner:after {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            left: 0;
            right: 0;
            display: block;
            z-index: 1;
            top: 0;
            background: rgba(98, 89, 202, .9);
        }

        .page-header__content {
            position: relative;
            border: 1px solid rgba(0, 0, 0, .1);
            background-color: #fff;
            background-image: url({{ asset('admin-view/img/bee.dcaaccb4.svg') }});
            background-repeat: repeat;
            width: 90%;
            left: 0;
            right: 0;
            margin: 0 auto;
            bottom: -25px;
            z-index: 10;
            padding: 20px;
            border-radius: 4px;

        }

        .align-items-center {
            align-items: center;
            display: flex;
        }

        .text-danger {
            color: #6259ca !important;
        }

        .c-order__btn--trash {
            background-color: #f44236 !important;
            color: #fff !important;
        }

        .c-order__btn {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px !important;
            border: 0;
        }

        .card-box__inner {
            color: #fff;
            background-image: url({{ asset('admin-view/img/4046534.fce3e766.784e3486.jpg') }});
            background-position: 50%;
            background-repeat: no-repeat;
            background-size: cover;
            position: relative;
            padding: 30px;
            border-top-right-radius: 8px;
            border-top-left-radius: 8px;
        }

        .first-pagination .d-flex.justify-content-between.flex-fill.d-sm-none,
        .last-pagination .d-flex.justify-content-between.flex-fill.d-sm-none {
            display: none
        }

        .first-pagination nav>div:nth-child(2)>div:nth-child(1) {
            display: none
        }
    </style>
</head>

<body class=''>
    <nav class='navbar navbar-fixed-top navbar-default'>
        <div class='container-fluid'>
            <div class='navbar-header'>
                <button type='button' class='navbar-toggle collapsed' data-toggle='collapse'
                    data-target='#bs-navbar-collapse'>
                    <span class='sr-only'>Toggle navigation</span>
                    <span class='icon-bar'></span>
                    <span class='icon-bar'></span>
                    <span class='icon-bar'></span>
                </button>
            </div>
            <div class='collapse navbar-collapse' data-nav='navbar-priority' id='bs-navbar-collapse'>
                <ul id='navResponsive' class='nav navbar-nav navbar-left-block'>
                    @php
                        $router = \Route::currentRouteName();
                        $current = URL::current();
                        $user = Auth::user();
                    @endphp
                    <li class="{{ $router == 'admin.logs' ? 'active' : '' }}"><a href='{{ route('admin.logs') }}'>
                            Logs
                        </a></li>
                </ul>
                <ul id='w4' class='nav navbar-nav navbar-right'>
                    <li class=''><a href='/'></a></li>
                    <li><a href="">Logout </a></li>
                </ul>
            </div>
        </div>
    </nav>
