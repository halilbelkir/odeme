<x-main>
    <div class="my-3 p-3 bg-body rounded shadow-sm">
        <h6 class="border-bottom pb-2 mb-0"><strong>Kalan Taksitler</strong> <span class="badge bg-secondary">{{count($priceList)}}</span></h6>
        @if(count($priceList) > 0)
            <form action="#" data-after-url="{{route('pay')}}" class="pay row" method="post">
                @csrf
                <div class="col-md-6 col-12 mt-3">
                    <h5 class="mb-5 mt-3 text-center totalPrice"> Toplam Tutar : <span> 0₺</span></h5>
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

                    <div class="form-floating form-group mt-1 col-12">
                        <input type="text" class="form-control price" name="price" placeholder="Tutar" required>
                        <label for="cvc">Tutar</label>
                        <x-inputerror for="price" class="mt-2" />
                    </div>

                    <div class="col-12 d-flex">
                        <div class="form-floating form-group col-12 col-md-4">
                            <button class="w-100 btn btn-md btn-red mt-3 float-end" type="submit">Ödeme Yap</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-12 order-first">
                    @foreach($priceList as $order => $price)
                        <div class="d-flex text-muted pt-3 ">
                            <input type="checkbox" name="price[]" data-month="{{$price['month']}}" data-year="{{$price['year']}}" value="{{$order}}" class="bd-placeholder-img flex-shrink-0 me-2 mt-2 rounded">
                            <p class="pb-3 mb-0 small lh-sm border-bottom w-100 px-2">
                                <strong class="d-block text-gray-dark">{{$price['month']}} - {{$price['year']}}</strong>
                                {{\App\helpers\helpers::priceFormat($price['price'])}} ₺
                            </p>
                        </div>
                    @endforeach
                </div>
            </form>
        @else
            <div class="alert alert-success mt-3" role="alert">
                Ödenecek taksit bulunamadı.
            </div>
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

            $("input[name='price[]']").change(function()
            {
                calcPrice();
            });

            function calcPrice(){
                var monthYear = [];

                $("input[name='price[]']:checked").each(function() {
                    monthYear.push($(this).val());
                });

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    url: "{{route('price-calculation')}}",
                    type: "POST",
                    data: {
                        monthYear: monthYear,
                    },
                    success: function(e)
                    {
                        $('.totalPrice span').text(e+'₺');
                        $('.price').val(e);
                    },
                    complete: function (e){
                        $('.loading').fadeOut('fast');
                    }
                });
            }
        </script>
    @endsection
</x-main>
