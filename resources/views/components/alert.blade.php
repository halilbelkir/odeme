<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>


@if (session('status'))
    <script>
        toastr.success('{{ session('status') }}','');
    </script>
@endif

@if ($errors->any())
    <script>
        @foreach ($errors->all() as $error)
            toastr.error('','{{ $error }}');
        @endforeach
    </script>
@endif

@if (session('message'))
    @if(session('message')[2] == 'info')
        <script>
            toastr.info('{{ session('message')[1] }}','{{ session('message')[0] }}');
        </script>
    @endif
    @if(session('message')[2] == 'success')
        <script>
            toastr.success('{{ session('message')[1] }}','{{ session('message')[0] }}');
        </script>
    @endif
    @if(session('message')[2] == 'warning')
        <script>
            toastr.warning('{{ session('message')[1] }}','{{ session('message')[0] }}');
        </script>
    @endif
    @if(session('message')[2] == 'error')
        <script>
            toastr.error('{{ session('message')[1] }}','{{ session('message')[0] }}');
        </script>
    @endif
@endif
