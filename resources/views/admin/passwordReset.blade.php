<x-guest>
    <h4>Yönetim Paneli</h4>
    <form method="post" action="{{route('admin.password.reset.submit')}}">
        @csrf
        <div class="form-floating mt-4">
            <input type="password" class="form-control" name="password" placeholder="Yeni Şifre">
            <label for="floatingPassword">Yeni Şifre</label>
        </div>
        <input type="hidden" name="email" value="{{request()->email}}">
        <input type="hidden" name="token" value="{{ request()->route('token') }}">
        <button class="w-100 btn btn-md btn-red mt-3" type="submit">Şifreyi Sıfırla</button>
    </form>
</x-guest>
