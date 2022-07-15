<x-main>
    <div class="my-3 p-3 bg-body rounded shadow-sm">
        <h6 class="border-bottom pb-2 mb-0"><strong>Kalan Taksitler</strong> <span class="badge bg-secondary">{{is_array($priceList) ? count($priceList) : 0}}</span></h6>
        @if (Session::has('flash_message'))
            <div class="d-flex justify-content-center">
                <div class="col-md-6">
                    @if(Session::get('flash_message')['Transaction']['Response']['Code'] == 0)
                        <div class="alert alert-success mt-3" role="alert">
                            Ödeme Başarılı Olmuştur. Dekontu görmek için <a href="{{Session::get('flash_message')['link']}}" target="_blank">tıklayınız.</a>
                        </div>
                    @else
                        <div class="alert alert-danger mt-3 text-left" role="alert">
                            <h6 class="alert-heading fw-bold">{{Session::get('flash_message')['Transaction']['Response']['ErrorMsg']}}</h6>
                            <p class="mb-0">{{Session::get('flash_message')['Transaction']['Response']['SysErrMsg']}}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        @if(count($priceList) > 0)
            <form action="@if(env('APP_TEST')) {{route('pay.test')}} @else {{route('pay')}} @endif" onsubmit="totalCalc();" class="pay row" method="post" autocomplete="off">
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
                        <input type="text" class="form-control price" name="total" placeholder="Tutar" required>
                        <label for="price">Tutar</label>
                        <x-inputerror for="price" class="mt-2" />
                    </div>

                    <div class="col-12 d-flex">
                        <div class="form-floating form-group col-12 col-md-4">
                            <button class="w-100 btn btn-md btn-red mt-3 float-end" type="submit">Ödeme Yap</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-12 order-first">
                    @php $row = 0; @endphp
                    @foreach($priceList as $order => $price)
                        <div class="d-flex text-muted pt-3 ">
                            <input type="checkbox" @if($row != 0) disabled @endif  name="price[]" data-order="{{$row}}" data-month="{{$price['month']}}" data-year="{{$price['year']}}" value="{{$order}}" class="bd-placeholder-img flex-shrink-0 me-2 mt-2 rounded">
                            <p class="pb-3 mb-0 small lh-sm border-bottom w-100 px-2">
                                <strong class="d-block text-gray-dark">{{$price['month']}} - {{$price['year']}}</strong>
                                {{\App\helpers\helpers::priceFormat($price['price'])}} ₺
                            </p>
                        </div>
                        @php $row++; @endphp
                    @endforeach

                    <div class="row" style="margin-top: 20px;border-top: 1px solid #cecece; padding-top: 20px;">
                        <div class="col-5 col-md-3 fw-bold">Toplam Borç </div>
                        <div class="col-7 col-md-9 text-start"> : {{\App\helpers\helpers::priceFormat($totalPrice)}} ₺</div>
                        <div class="col-5 col-md-3 fw-bold">Kalan Borç </div>
                        <div class="col-7 col-md-9 text-start remainingDept"> : {{\App\helpers\helpers::priceFormat($totalPrice)}} ₺</div>
                    </div>
                </div>
            </form>
        @else
            <div class="alert alert-success mt-3" role="alert">
                Ödenecek taksit bulunamadı.
            </div>
        @endif
    </div>

    <div class="pay-show" style="display: none"></div>
    @section('js')
        <script src="{{asset('assets/js/card.js')}}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.13.4/jquery.mask.min.js"></script>
        <script>
            @isset (Session::has('flash_message')['link'])
                window.open('{{Session::get('flash_message')['link']}}', '_blank');
            @endisset

            var c = new Card({
                form: document.querySelector('form.pay'),
                container: '.card-wrapper',
                maskCardNumber : '.card_no',
            });

            $('.card_date').mask("99/99");

            $("input[name='total']").on('blur',function()
            {
                totalCalc();
                /*
                var code = event.keyCode ? event.keyCode : 0;
                var displayFunc = [8,9,37,38,39,40];

                if(jQuery.inArray( code, displayFunc ) == -1)
                {
                    totalCalc();
                }
                 */
            });

            function totalCalc()
            {
                $("input[name='price[]']").attr('disabled',true);
                $('[data-order="0"]').prop('checked',false);
                $('[data-order="0"]').attr('disabled',false);
                calcPrice(2,$("input[name='total']").val());
            }

            $("input[name='price[]']").change(function()
            {
                var order   = $(this).data('order');
                var checked = $(this).prop('checked');

                if (checked == false && order != 0)
                {
                    $('[data-order="'+(order + 1)+'"]').attr('disabled',true);
                    $('[data-order="'+(order + 1)+'"]').prop('checked',false);
                }
                else if(checked == false && order == 0)
                {
                    $("input[name='price[]']").attr('disabled',true);
                    $(this).attr('disabled',false);
                    $("input[name='price[]']").prop('checked',false);
                }
                else
                {
                    $('[data-order="'+(order + 1)+'"]').attr('disabled',false);
                }

                calcPrice(1);
            });

            function calcPrice(status,price = 0)
            {
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
                        status   : status,
                        price   : price,
                    },
                    success: function(e)
                    {
                        var response      = JSON.parse(e);
                        var moreMoneyWarn = response.moreMoneyWarn;

                        $('.totalPrice span').text(response.totalPrice+' ₺');
                        $('.remainingDept').text(': '+response.remainingDept+' ₺');
                        $('.price').val(response.totalPrice == 0 ? null : response.totalPrice);

                        if(moreMoneyWarn != null)
                        {
                            toastr.error(moreMoneyWarn[1],moreMoneyWarn[0]);
                        }
                    },
                    complete: function (e){
                        $('.loading').fadeOut('fast');
                    }
                });
            }
            @if(!env('APP_TEST'))
                function isJson(str)
                {
                    try
                    {
                        return JSON.parse(str);
                    }
                    catch (e)
                    {
                        return false;
                    }
                }

                $('.pay')
                .submit(function (e)
                {
                    e.preventDefault();
                    totalCalc();
                    $.ajaxSetup({ headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") } });
                    $.ajax({
                        type: $(this).attr('method'),
                        url:  $(this).attr('action'),
                        data: $(this).serialize(),
                        success: function (e)
                        {
                            $('.loading').fadeOut('fast');

                            if(isJson(e))
                            {
                                var response = JSON.parse(e);
                                var message  = response.message;
                                toastr.error(message,'Uyarı!');
                            }
                            else
                            {
                                $('.pay-show').html(e);
                                setTimeout(function() {
                                    $('.form-pay-send').trigger('submit');
                                }, 100);
                            }
                        },
                        error : function (e)
                        {
                            $('.loading').fadeOut('fast');
                            toastr.error(e.responseJSON.message,'Uyarı!');
                        }
                    });

                });
            @endif
        </script>
    @endsection
</x-main>
