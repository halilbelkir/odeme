<x-guest>
    <form method="post" class="loginForm" action="{{route('tc.control')}}">
        <p style="font-style: italic">* Sistemde tanımlı olan T.C Kimlik numarasını giriniz.</p>
        <div class="form-floating">
            <input type="number" class="form-control" onkeyup="kisalt('tc',11);" name="tc" placeholder="T.C">
            <label for="floatingPassword">T.C</label>
        </div>
        <button class="w-100 btn btn-md btn-red mt-3" type="submit">Giriş Yap</button>
    </form>
    @section('js')
        <script src="https://www.google.com/recaptcha/enterprise.js?render=6LeAj5cgAAAAAG_HyfWQVwj11LS_A4Zw7EhVV0_v"></script>
    @endsection
</x-guest>
