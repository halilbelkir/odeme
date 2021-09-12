<x-main>
    <div class="my-3 p-3 bg-body rounded shadow-sm">
        <h6 class="border-bottom pb-2"><strong>Profil</strong></h6>


        <form action="{{route('profile.edit')}}" method="post" class="row">
            @csrf
            <div class="form-floating form-group col-12 col-md-6">
                <input type="number" class="form-control" name="tc" value="{{$users->tc}}" placeholder="T.C" disabled>
                <label for="tc">T.C</label>
                <x-inputerror for="tc" class="mt-2" />
            </div>

            <div class="form-floating form-group col-12 col-md-6">
                <input type="number" class="form-control" name="customer_code" value="{{$users->customer_code}}" placeholder="Müşteri Kodu" disabled>
                <label for="customer_code">Müşteri Kodu</label>
                <x-inputerror for="customer_code" class="mt-2" />
            </div>


            <div class="form-floating form-group col-12 col-md-6">
                <input type="text" class="form-control" name="name" value="{{\App\helpers\helpers::nameAndSurname($users->name)->name}}" placeholder="Ad" required>
                <label for="name">Ad</label>
                <x-inputerror for="name" class="mt-2" />
            </div>

            <div class="form-floating form-group col-12 col-md-6">
                <input type="text" class="form-control" name="surname" value="{{\App\helpers\helpers::nameAndSurname($users->name)->surname}}" placeholder="Soyad" required>
                <label for="surname">Soyad</label>
                <x-inputerror for="surname" class="mt-2" />
            </div>

            <div class="form-floating form-group col-12 col-md-6">
                <input type="number" class="form-control" name="phone_number" value="{{\App\helpers\helpers::editPhoneNumber($users->phone_number)}}" placeholder="Telefon Numarası" required>
                <label for="phone_number">Telefon Numarası</label>
                <x-inputerror for="phone_number" class="mt-2" />
            </div>

            <div class="form-floating form-group col-12 col-md-6">
                <input type="email" class="form-control" name="email" value="{{$users->email}}" placeholder="E-mail">
                <label for="email">E-mail</label>
                <x-inputerror for="email" class="mt-2" />
            </div>

            <div class="col-12">
                <div class="form-floating form-group col-12 col-md-2">
                    <button class="w-100 btn btn-md btn-red mt-3 float-end" type="submit">Kaydet</button>
                </div>
            </div>
        </form>
    </div>
</x-main>
