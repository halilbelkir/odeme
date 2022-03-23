<x-guest>
    <h4>Yönetim Paneli</h4>
    <form method="post" action="{{route('admin.password.submit')}}">
        @csrf
        <div class="form-floating mt-4">
            <input type="email" class="form-control" name="email" placeholder="E-Mail">
            <label for="floatingPassword">E-Mail</label>
        </div>
        <button class="w-100 btn btn-md btn-red mt-3" type="submit">Gönder</button>
    </form>
</x-guest>
