<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>@yield('title')</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @yield('styles')

</head>
<body>


<div class="container">
    @yield('content')
</div>

<hr>

<footer>
    <p>&copy; Michael Drennen {{date('Y')}}</p>
</footer>
</div> <!-- /container -->
@yield('scripts')
</body>
</html>
