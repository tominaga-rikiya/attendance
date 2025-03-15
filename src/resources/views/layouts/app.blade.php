<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title')</title>
{{-- <script src="https://kit.fontawesome.com/42694f25bf.js" crossorigin="anonymous"></script>
<script src="https://ajaxzip3.github.io/ajaxzip3.js" charset="UTF-8"></script> --}}
<link rel="stylesheet" href="{{ asset('/css/reset.css')  }}">
<link rel="stylesheet" href="{{ asset('/css/common.css')  }}">
{{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css"> --}}
@yield('css')
</head>

<body>
@yield('content')
{{-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>


<script>
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-bottom-right",
    }

    @if(Session::has('flashSuccess'))
    toastr.success("{{ session('flashSuccess') }}");
    @endif
</script> --}}


</body>

</html>