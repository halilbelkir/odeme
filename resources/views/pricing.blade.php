<x-main>
    <div class="my-3 p-3 bg-body rounded shadow-sm">
        <h6 class="border-bottom pb-2 mb-0">
            <strong>Ödenecek Taksitler</strong>
            <span class="badge bg-secondary">{{count($pricing['price'])}}</span>
        </h6>
        @if(count($pricing) > 0)
            <form action="#" data-after-url="{{route('pay')}}" class="pay row" method="post">
                @csrf
                <div class="col-md-6 col-12 mt-3">
                    <div class="card-wrapper mb-5"></div>
                    <div class="form-floating form-group mt-1 col-12">
                        <input type="text" class="form-control name" name="name" data-type="name" placeholder="Ad & Soyad" required>
                        <label for="name">Ad & Soyad</label>
                        <x-inputerror for="name" class="mt-2" />
                    </div>

                    <div class="form-floating form-group mt-1 col-12">
                        <input type="text" class="form-control card_no" name="number" placeholder="Kart No" required>
                        <label for="card_no">Kart No</label>
                        <x-inputerror for="card_no" class="mt-2" />
                    </div>

                    <div class="form-floating form-group mt-1 col-12">
                        <input type="text" class="form-control card_date" name="expiry" placeholder="Son Kullanım Tarihi" data-type="expiry" required>
                        <label for="card_date">Son Kullanım Tarihi</label>
                        <x-inputerror for="card_date" class="mt-2" />
                    </div>

                    <div class="form-floating form-group mt-1 col-12">
                        <input type="password" class="form-control cvc" name="cvc" data-type="cvc" placeholder="CVC" required>
                        <label for="cvc">CVC</label>
                        <x-inputerror for="cvc" class="mt-2" />
                    </div>
                </div>

                <div class="col-md-6 col-12 order-first">
                    @for($i = 0; $i < count($pricing['price']); $i++)
                        <div class="d-flex text-muted pt-3 col-12">
                            <p class="pb-3 mb-0 small lh-sm border-bottom w-100 px-2">
                                <strong class="d-block text-gray-dark">{{$pricing['month'][$i]}} - {{$pricing['year'][$i]}}</strong>
                                {{\App\helpers\helpers::priceFormat($pricing['price'][$i])}} ₺
                            </p>
                        </div>
                        @php $amount += $pricing['price'][$i]; @endphp
                    @endfor
                    <h5 class="mt-5 text-right"> Toplam Tutar : {{\App\helpers\helpers::priceFormat($amount)}} ₺</h5>
                </div>

                <div class="col-12 d-flex">
                    <div class="form-floating form-group col-12 col-md-2">
                        <button class="w-100 btn btn-md btn-red mt-3 float-end" type="submit">Ödeme Yap</button>
                    </div>
                </div>
            </form>

        @endif
    </div>

    @section('js')
        <script src="{{asset('assets/js/card.js')}}"></script>
        <script>
            var c = new Card({
                form: document.querySelector('form.pay'),
                container: '.card-wrapper',
                maskCardNumber : '.card_no'
            });
        </script>
    @endsection
</x-main>

