<x-main>

<div class="container">
    <div class="row mb-5">

        <div class="col-md-2">
            <div class="box">
                <h5>Günlük Toplam Ödeme Sayısı</h5>
                <h2>{{$todayCount}}</h2>
            </div>
        </div>

        <div class="col-md-2">
            <div class="box">
                <h5>Günlük Toplam Ödeme Tutarı</h5>
                <h2>{{$todayTotalPrice}} ₺</h2>
            </div>
        </div>

        <div class="col-md-2">
            <div class="box ">
                <h5>Haftalık Toplam Ödeme Sayısı</h5>
                <h2>{{$weekCount}}</h2>
            </div>
        </div>

        <div class="col-md-2">
            <div class="box">
                <h5>Haftalık Toplam Ödeme Tutarı</h5>
                <h2>{{$weekTotalPrice}} ₺</h2>
            </div>
        </div>

        <div class="col-md-2">
            <div class="box">
                <h5>Aylık Toplam Ödeme Sayısı</h5>
                <h2>{{$monthCount}}</h2>
            </div>
        </div>

        <div class="col-md-2">
            <div class="box">
                <h5>Aylık Toplam Ödeme Tutarı</h5>
                <h2>{{$monthTotalPrice}} ₺</h2>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 mb-3">
            <div class="form-group mt-1">
                <select name="report_type" id="" class="report_type form-control">
                    <option value="">Rapor Seçiniz</option>
                    <option value="{{route('admin.today.datatables')}}">Bugün</option>
                    <option value="{{route('admin.week.datatables')}}">Bu Hafta</option>
                    <option value="{{route('admin.month.datatables')}}">Bu Ay</option>
                </select>
            </div>
        </div>
        <div class="col-12 report" style="display: none;">
            <table class="table" id="report" data-order='[[ 3, "desc" ]]'>
                <thead>
                <tr>
                    <th>Ad & Soyad</th>
                    <th>Kart Numarası</th>
                    <th>Ödenen Tutar</th>
                    <th>İşlem Zamanı</th>
                    <th>Bankadan Dönen Kod</th>
                    <th>Bankadan Dönen Hata Mesajı Detayı</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@section('css')
        <link rel="stylesheet" href="//cdn.datatables.net/1.10.7/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">

@endsection
@section('js')
        <script src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
        <script>
            $('.report_type').change(function ()
            {
                var route = $(this).val();
                var tableId = '#report';
                $('.report').slideDown('300');

                if ($.fn.dataTable.isDataTable(tableId)) {
                    $(tableId).DataTable().clear();
                    $(tableId).DataTable().destroy();
                    $(tableId).css("width","100%")
                }

                var datatables = $(tableId).DataTable({
                    dom: 'Bfrtip',
                    processing: true,
                    serverSide: true,
                    scrollX: true,
                    buttons: [
                        'pageLength','excel', 'pdf'
                    ],
                    lengthMenu: [
                        [10, 25, 50,100, -1 ],
                        ['10', '25', '50','100', 'Hepsi' ]
                    ],
                    ajax : route,
                    columns :
                        [
                            { data: 'name_surname', name: 'name_surname' },
                            { data: 'card_number', name: 'card_number' },
                            { data: 'amount', name: 'amount' },
                            { data: 'created_at', name: 'created_at' },
                            { data: 'response_code', name: 'response_code' },
                            { data: 'error_message', name: 'error_message' },
                        ],
                    language:{
                                "url":"//cdn.datatables.net/plug-ins/1.10.12/i18n/Turkish.json",
                                buttons: {
                                    pageLength: {
                                        _: " %d Göster",
                                        '-1': "Hepsi"
                                    }
                                }
                             },
                });

                datatables.destroy();
            });
        </script>
@endsection
</x-main>
