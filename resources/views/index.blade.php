<x-guest>
    <form method="post" action="{{route('tc.control')}}">
        @csrf
        <div class="form-floating">
            <input type="text" class="form-control" name="tc" placeholder="T.C">
            <label for="floatingPassword">T.C</label>
        </div>
        <button class="w-100 btn btn-md btn-red mt-3" type="submit">Giriş Yap</button>
    </form>
</x-guest>
