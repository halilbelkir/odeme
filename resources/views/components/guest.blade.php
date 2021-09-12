<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{Media::getImage('assets/img/favicon.ico', 50, 62)}}">
    <title>Ödeme Ekranı</title>
    <link href="http://netdna.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.css" rel="stylesheet">
    <link href="https://getbootstrap.com/docs/5.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous" />
    <link href="{{asset('assets/css/signin.css')}}" rel="stylesheet" />
    <link href="{{asset('assets/css/join.css')}}" rel="stylesheet" />
</head>
<body class="text-center">
<main class="form-signin">
    <x-logo></x-logo>
    {{$slot}}
    <p class="mt-5 mb-3 text-muted">&copy;2021</p>
</main>
</body>

<script src="{{asset('assets/js/jquery-2.2.4.min.js')}}"></script>
<script src="{{asset('assets/js/script.js')}}"></script>
<x-alert></x-alert>
<x-loading></x-loading>
@yield('js')
</html>
