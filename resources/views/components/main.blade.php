<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{Media::getImage('assets/img/favicon.ico', 50, 62)}}">
    <link href="https://netdna.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.css" rel="stylesheet">
    <link href="https://getbootstrap.com/docs/5.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous" />
    <link href="{{asset('assets/css/main.css')}}" rel="stylesheet" />
    <link href="{{asset('assets/css/join.css')}}" rel="stylesheet" />
    @yield('css')
    <title>Uğurlu Çeyiz</title>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{route('price.list')}}">{!! Media::createTag('assets/img/logo-beyaz.png',['width' =>[107], 'height' => [30]]) !!}</a>
            <button class="navbar-toggler p-0 border-0" type="button" id="navbarSideCollapse" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="navbar-collapse offcanvas-collapse" id="navbarsExampleDefault">
                <ul class="navbar-nav m-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">
                            Sayın <b>{{Auth::user()->name}} {{Auth::user()->surname}}</b>
                        </a>
                    </li>
                    @if(Auth::user()->login_control != 1)
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="#">
                                Toplam Borç: {{\App\helpers\helpers::priceFormat($totalPrice)}} ₺
                            </a>
                        </li>
                    @endif
                    <li class="nav-item float-right">
                        <a class="nav-link @if(request()->routeIs('price.list')) active @endif " href="{{route('price.list')}}">Anasayfa</a>
                    </li>
                    <li class="nav-item float-right">
                        <a class="nav-link @if(request()->routeIs('profile')) active @endif " href="{{route('profile')}}">Profil</a>
                    </li>
                    <li class="nav-item float-right">
                        <form action="@if(Auth::user()->login_control != 1) {{route('logout')}} @else{{route('admin.logout')}}@endif" method="post">
                            @csrf
                            <button type="submit"  style="display: none" class="logout">Çıkış Yap</button>
                        </form>
                        <a class="nav-link" href="#" onclick="$('.logout').trigger('click')">
                            Çıkış Yap
                        </a>
                    </li>


                    <!--
                    <li class="nav-item">
                        <a class="nav-link" href="#">Geçmiş Taksitler</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="dropdown01" data-bs-toggle="dropdown" aria-expanded="false">Settings</a>
                        <ul class="dropdown-menu" aria-labelledby="dropdown01">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                        </ul>
                    </li><li class="nav-item dropdown float">
                        <a class="nav-link dropdown-toggle" href="#" id="dropdown01" data-bs-toggle="dropdown" aria-expanded="false">
                            {!! Media::createTag('assets/img/user.png',['width' =>[30], 'height' => [30]],['class' => 'rounded-circle']) !!}

                    </a>
                    <ul class="dropdown-menu" aria-labelledby="dropdown01">
                        <li><a class="dropdown-item" href="{{route('profile')}}">Profil</a></li>
                            <li>
                                <form action="{{route('logout')}}" method="post">
                                    @csrf
                    <button type="submit"  style="display: none" class="logout">Çıkış Yap</button>
                </form>
                <a class="dropdown-item" href="#" onclick="$('.logout').trigger('click')">Çıkış Yap</a>
            </li>
        </ul>
    </li>
-->

                </ul>

            </div>
        </div>
    </nav>
    <main class="container mt-5">
        {{$slot}}
    </main>
    <footer>
        <ul>
            <li> <strong>Adres :</strong> Bey mahallesi Atatürk Bulvarı No: 23 Şehitkamil / Gaziantep</li>
            <li> <strong>Telefon :</strong> 444 0 943</li>
            <li> <strong>E-Mail :</strong> info@ugurluceyiz.com.tr</li>
        </ul>
    </footer>
</body>
<script src="{{asset('assets/js/jquery-2.2.4.min.js')}}"></script>
<script src="{{asset('assets/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('assets/js/offcanvas.js')}}"></script>
<script src="{{asset('assets/js/script.js')}}"></script>
@yield('js')
<x-alert></x-alert>
<x-loading></x-loading>
</html>
<style>
    footer ul
    {
        display: flex;
        justify-content: center;
        margin-bottom: 0;
    }
    footer ul li
    {
        float: left;
        margin-right: 20px;
        list-style: none;
        text-align: center;
    }
</style>
