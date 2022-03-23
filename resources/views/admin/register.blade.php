<x-guest>
    <h4>Yönetim Paneli</h4>
    <p>- Kayıt Ol-</p>
    <form method="post" action="{{route('admin.register.submit')}}">
        @csrf

        <div class="form-floating mt-4">
            <input type="text" class="form-control" name="name" placeholder="Ad">
            <label for="floatingPassword">Ad</label>
        </div>

        <div class="form-floating mt-4">
            <input type="text" class="form-control" name="surname" placeholder="Soyad">
            <label for="floatingPassword">Soyad</label>
        </div>

        <div class="form-floating mt-4">
            <input type="email" class="form-control" name="email" placeholder="E-Mail">
            <label for="floatingPassword">E-Mail</label>
        </div>

        <div class="form-floating mt-4">
            <input type="password" class="form-control" name="password" placeholder="Şifre">
            <label for="floatingPassword">Şifre</label>
        </div>
        <button class="w-100 btn btn-md btn-red mt-3" type="submit">Kayıt Ol</button>
    </form>
</x-guest>
