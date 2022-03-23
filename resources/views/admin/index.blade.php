<x-guest>
    <h4>Yönetim Paneli</h4>
    <form method="post" action="{{route('admin.login')}}">
        @csrf
        <div class="form-floating mt-4">
            <input type="email" class="form-control" name="email" placeholder="E-Mail">
            <label for="floatingPassword">E-Mail</label>
        </div>

        <div class="form-floating mt-4">
            <input type="password" class="form-control" name="password" placeholder="Şifre">
            <label for="floatingPassword">Şifre</label>
        </div>
        <div class="col-12 text-end">
            <a href="{{route('admin.password')}}" style="color: #000;font-size: 13px" class="text-decoration-none">Şifremi Unuttum</a>
        </div>
        <button class="w-100 btn btn-md btn-red mt-3" type="submit">Giriş Yap</button>
    </form>
</x-guest>
