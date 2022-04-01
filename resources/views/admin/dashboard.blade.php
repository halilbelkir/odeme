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
        <form action="{{route('admin.datatables')}}" class="row" id="reportForm" method="POST">
            <div id="multipleDate" class="row col-8">
                <div class="col-6 mb-3">
                    <div class="form-group mt-1">
                        <input type="text" name="startDate" class="form-control date" value="{{Carbon\Carbon::now()->format('d-m-Y')}}" placeholder="Başlangıç Tarihi ">
                    </div>
                </div>
                <div class="col-6 mb-3">
                    <div class="form-group mt-1">
                        <input type="text" name="endDate" class="form-control date" value="{{Carbon\Carbon::tomorrow()->format('d-m-Y')}}" placeholder="Bitiş Tarihi">
                    </div>
                </div>
            </div>

            <div class="col-4 mb-3">
                <div class="form-group mt-1">
                    <button class="w-100 btn btn-md btn-red" type="submit">Raporla</button>
                </div>
            </div>
        </form>

        <div class="col-12 report" style="display: none;">
            <table class="table" id="report" data-order='[[ 3, "desc" ]]'>
                <thead>
                    <tr>
                        <th>Müşteri Numarası</th>
                        <th>Ad & Soyad</th>
                        <th>Kart Numarası</th>
                        <th>Ödenen Tutar</th>
                        <th>İşlem Zamanı</th>
                        <th>Bankadan Dönen Kod</th>
                        <th>Bankadan Dönen Hata Mesajı Detayı</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-end">Toplam</th>
                        <th colspan="1" class="totalAmount text-start"></th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@section('css')
        <link rel="stylesheet" href="//cdn.datatables.net/1.10.7/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" integrity="sha512-mSYUmp1HYZDFaVKK//63EcZq4iFWFjxSL+Z3T/aCt4IO9Cejm03q3NKKYN6pFQzY0SBOr8h+eCIAZHPXcpZaNw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
@endsection
@section('js')
        <script src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js" integrity="sha512-T/tUfKSV1bihCnd+MxKD0Hm1uBBroVYBOYSk1knyvQ9VyZJpc/ALb4P0r6ubwVPSGB2GvjeoMAJJImBG12TiaQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>

            function getDatatables(route)
            {
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
                    data : {
                        url: $(this).attr('action'),
                        type: "POST",
                        data: $(this).serialize(),
                    },
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
            }
            (function($){
                $.fn.datepicker.dates['tr'] = {
                    days: ["Pazar", "Pazartesi", "Salı", "Çarşamba", "Perşembe", "Cuma", "Cumartesi", "Pazar"],
                    daysShort: ["Pz", "Pzt", "Sal", "Çrş", "Prş", "Cu", "Cts", "Pz"],
                    daysMin: ["Pz", "Pzt", "Sa", "Çr", "Pr", "Cu", "Ct", "Pz"],
                    months: ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"],
                    monthsShort: ["Oca", "Şub", "Mar", "Nis", "May", "Haz", "Tem", "Ağu", "Eyl", "Eki", "Kas", "Ara"],
                    today: "Bugün",
                    format: "dd.mm.yyyy"
                };
            }(jQuery));

            $('#multipleDate').datepicker({
                inputs: $('.date'),
                toggleActive: true,
                todayBtn: 'linked',
                todayHighlight: true,
                weekStart:1,
                language:'tr',
                format:'dd-mm-yyyy',
                autoclose : true
            });



            $('#reportForm').submit(function (e)
            {
                e.preventDefault();
                //getDatatable($(this).attr('action'));
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
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
                    ajax : {
                        url: $(this).attr('action'),
                        type: "POST",
                        data: function (d)
                        {
                            d.startDate = $('input[name=startDate]').val();
                            d.endDate = $('input[name=endDate]').val();
                        }
                    },
                    columns :
                        [
                            { data: 'customer_code', name: 'customer_code' },
                            { data: 'name_surname', name: 'name_surname' },
                            { data: 'card_number', name: 'card_number' },
                            { data: 'amount', name: 'amount',footer:'amount' },
                            { data: 'created_at', name: 'created_at' },
                            { data: 'response_code', name: 'response_code' },
                            { data: 'error_message', name: 'error_message' },
                        ],
                    language:{
                        "url":"{{route('admin.datatables.turkish')}}",
                        buttons: {
                            pageLength: {
                                _: " %d Göster",
                                '-1': "Hepsi"
                            }
                        }
                    },
                    drawCallback:function(data)
                    {
                        console.log(data.json.total);
                        $('.dataTables_scrollFoot .totalAmount').text(data.json.total);
                    }
                });
                $('.loading').fadeOut('fast');
            });
        </script>
@endsection
</x-main>
