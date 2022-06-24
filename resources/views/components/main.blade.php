<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{asset('assets/img/favicon.ico')}}">
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
            <a class="navbar-brand" href="{{route('price.list')}}"><img src="{{asset('assets/img/logo-beyaz.png')}}" style="height: 50px"> </a>
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

                    @if(Auth::user()->login_control != 1)
                        <li class="nav-item float-right">
                            <a class="nav-link @if(request()->routeIs('price.list')) active @endif " href="{{route('price.list')}}">Anasayfa</a>
                        </li>
                    @else
                        <li class="nav-item float-right">
                            <a class="nav-link @if(request()->routeIs('admin.dashboard')) active @endif " href="{{route('admin.dashboard')}}">Anasayfa</a>
                        </li>
                    @endif
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

                </ul>

            </div>
        </div>
    </nav>
    <main class="container mt-5">
        {{$slot}}
    </main>
    <footer>
        <div class="container">
            <div class="row text-left text-md-center justify-content-center">
                <div class="col-md-6 col-xl-5 col-lg-6"><strong>Adres :</strong> Bey mahallesi Atatürk Bulvarı No: 23 Şehitkamil / Gaziantep</div>
                <div class="col-md-3 col-xl-2 col-lg-2"><strong>Telefon :</strong> <a href="tel:4440943">444 0 943</a></div>
                <div class="col-md-3 col-xl-3 col-lg-4"><strong>E-Mail :</strong> <a href="mailto:info@ugurluceyiz.com.tr">info@ugurluceyiz.com.tr</a></div>
            </div>
        </div>
    </footer>
    <x-footer></x-footer>
</body>
<script src="{{asset('assets/js/jquery-3.6.0.min.js')}}"></script>
<script src="{{asset('assets/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('assets/js/offcanvas.js')}}"></script>
<script src="{{asset('assets/js/script.js')}}"></script>
@yield('js')
<x-alert></x-alert>
<x-loading></x-loading>
</html>
