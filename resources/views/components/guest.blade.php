<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{asset('assets/img/favicon.ico')}}">
    <title>Ödeme Ekranı</title>
    <link href="https://netdna.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.css" rel="stylesheet">
    <link href="https://getbootstrap.com/docs/5.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="{{asset('assets/css/signin.css')}}" rel="stylesheet" />
    <link href="{{asset('assets/css/join.css')}}" rel="stylesheet" />
</head>
<body class="text-center">
<main class="container-fluid">
    <div class="container">
        <div class="row box justify-content-center">
            <div class="col-md-6 col-lg-7 px-0">
                <img src="{{asset('assets/img/banner.jpg')}}" class="w-100">
            </div>
            <div class="col-md-6 col-lg-5">
                {{$slot}}
            </div>
        </div>
    </div>
    <x-footerInfo></x-footerInfo>
</main>
</body>
<script src="{{asset('assets/js/jquery-2.2.4.min.js')}}"></script>
<script src="{{asset('assets/js/script.js')}}"></script>
<x-alert></x-alert>
<x-loading></x-loading>
@yield('js')
</html>
