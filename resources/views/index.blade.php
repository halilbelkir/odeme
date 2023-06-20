<x-guest>
    <form method="post" class="loginForm" action="{{route('tc.control')}}">
        <p style="font-style: italic">* Sistemde tanımlı olan T.C Kimlik numarasını giriniz.</p>
        <div class="form-floating">
            <input type="number" class="form-control" onkeyup="kisalt('tc',11);" name="tc" placeholder="T.C">
            <label for="floatingPassword">T.C</label>
        </div>
        <button class="w-100 btn btn-md btn-red mt-3" type="submit">Giriş Yap</button>
    </form>
    <div class="col-12" style="margin-top: 50px; text-align: left">
        <b>HESAP NUMARASI</b> <br>
        Taksit ödemelerinizi mağazaya gelmeden Iban numarası ile gerçekleştirebilirsiniz. <br>
        <b>Garanti BBVA Iban Numarası</b> <br>
        TR19 0006 2001 6060 0006 2974 46 <br>
        Ad Soyad / Ünvan : UĞURLU PERAKENDE MAĞAZACILIK İTH. İHR. SAN. VE TİC. A.Ş. <br>
        <b style="color: #dd0815;font-style: italic">Not: Açıklama kısmına borçlu isim - soyisim TC ve iletişim numarasını muhakkak belirtiniz.</b>
    </div>
    @section('js')
        <script src="https://www.google.com/recaptcha/enterprise.js?render=6LeAj5cgAAAAAG_HyfWQVwj11LS_A4Zw7EhVV0_v"></script>
        @if(1==2)
            <script>
                $('.loginFormA').on('submit', function(e)
                {

                    var tc      = $('input[name="tc"]').val();
                    var control = TCNOKontrol(tc);

                    if (tc != 12345678910)
                    {
                        if(control == 2)
                        {
                            $('.loading').fadeOut('fast');
                            toastr.error('T.C numarasını fazla girdiniz. Lütfen en fazla 11 rakam olacak şekilde giriniz.','Hata!');

                            return false;
                        }
                        else if(control == 3)
                        {
                            $('.loading').fadeOut('fast');
                            toastr.error('T.C numarasını eksik girdiniz. Lütfen en fazla 11 rakam olacak şekilde giriniz.','Hata!');

                            return false;
                        }
                        else if(!control)
                        {
                            $('.loading').fadeOut('fast');
                            toastr.error('T.C numarası formatı doğru değil. Lütfen doğru T.C numarası giriniz.','Hata!');

                            return false;
                        }
                    }


                    e.preventDefault();
                    $.ajaxSetup({ headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") } });
                    $.ajax({
                        type: $(this).attr('method'),
                        url:  $(this).attr('action'),
                        data: $(this).serialize(),
                        success: function (response)
                        {
                            $('.loading').fadeOut('fast');
                            toastr.success(response.message,response.title);

                            if (response.route != undefined)
                            {
                                location = response.route;
                                setTimeout(function() {location}, 1000);
                            }
                            else
                            {
                                setTimeout(function() {location.reload()}, 1000);
                            }

                        },
                        error : function (response)
                        {
                            $('.loading').fadeOut('fast');
                            if (response.responseJSON.result == 2)
                            {
                                $(this).addClass('was-validated');

                                $.each(response.responseJSON.message, function(i, item)
                                {
                                    $('[name="'+i+'"]').addClass('is-invalid');
                                    $('[name="'+i+'"]').closest('div.form-group').append('<div class="invalid-feedback">'+item[0]+'</div>');
                                });
                            }
                            else
                            {
                                toastr.error(response.responseJSON.message,response.responseJSON.title);
                            }
                        }
                    });
                });
            </script>
        @endif
    @endsection
</x-guest>
