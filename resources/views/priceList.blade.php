<x-main>
    <div class="my-3 p-3 bg-body rounded shadow-sm">
        <h6 class="border-bottom pb-2 mb-0"><strong>Kalan Taksitler</strong> <span class="badge bg-secondary">{{count($remainder)}}</span></h6>
        @if(count($remainder) > 0)
            <form action="{{route('pricing')}}" data-after-url="{{route('pay')}}" class="pricing" method="post">
                @csrf
                @foreach($remainder as $order => $price)
                    <div class="d-flex text-muted pt-3 ">
                        <input type="checkbox" name="price[]" data-month="{{$price->AYAD1}}" data-year="{{$price->YIL}}" value="{{$price->RemainingInstallment}}" class="bd-placeholder-img flex-shrink-0 me-2 mt-2 rounded">
                        <p class="pb-3 mb-0 small lh-sm border-bottom w-100 px-2">
                            <strong class="d-block text-gray-dark">{{$price->AYAD1}} - {{$price->YIL}}</strong>
                            {{\App\helpers\helpers::priceFormat($price->RemainingInstallment)}} ₺
                        </p>
                    </div>
                @endforeach

                <div class="col-12 d-flex">
                    <div class="form-floating form-group col-12 col-md-2">
                        <button class="w-100 btn btn-md btn-red mt-3 float-end" type="submit">Ödeme Yap</button>
                    </div>
                </div>
            </form>
        @else
            <div class="alert alert-success mt-3" role="alert">
                Ödenecek taksit bulunamadı.
            </div>
        @endif
    </div>
</x-main>
