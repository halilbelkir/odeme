<x-guest>
    <form method="post" class="loginForm" action="{{route('tc.control')}}">
        <p style="font-style: italic">* Sistemde tanımlı olan T.C Kimlik numarasını giriniz.</p>
        <div class="form-floating">
            <input type="text" class="form-control" name="tc" placeholder="T.C">
            <label for="floatingPassword">T.C</label>
        </div>
        <button class="w-100 btn btn-md btn-red mt-3" type="submit">Giriş Yap</button>
    </form>
    @section('js')
        <script src="https://www.google.com/recaptcha/enterprise.js?render=6LeAj5cgAAAAAG_HyfWQVwj11LS_A4Zw7EhVV0_v"></script>
        <script>
            $('.loginForm').on('submit', function(e)
            {
                e.preventDefault();
                grecaptcha.enterprise.ready(function() {
                    grecaptcha.enterprise.execute('6LeAj5cgAAAAAG_HyfWQVwj11LS_A4Zw7EhVV0_v', {action: 'login'}).then(function(token)
                    {
                        $.ajaxSetup({ headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") } });
                        $.ajax({
                            type: $('.loginForm').attr('method'),
                            url:  $('.loginForm').attr('action'),
                            data: $('.loginForm').serialize(),
                            success: function (response)
                            {
                                $('.loading').fadeOut('fast');
                                toastr.success(response.title,response.message);

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
                                    toastr.error(response.title,response.message);
                                }
                            }
                        });
                    });
                });
            });

        </script>
    @endsection
</x-guest>
