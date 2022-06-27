$('form').submit(function ()
{
   $('.loading').fadeIn('fast');
});


$('.pricing').submit(function (event)
{
    var price = [];
    var month = [];
    var year  = [];

    $("input[name='price[]']:checked").each(function() {
        price.push($(this).val());
        month.push($(this).data('month'));
        year.push($(this).data('year'));
    });

    event.preventDefault();
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    after_url = $(this).data('after-url');
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: {
            price: price,
            month: month,
            year: year,
        },
        success: function(e)
        {
            $('.loading').fadeOut('fast');
            window.location.href = after_url;
        }
    });
});

function formats(ele,e){
    if(ele.value.length<19){
        ele.value= ele.value.replace(/\W/gi, '').replace(/(.{4})/g, '$1 ');
        return true;
    }else{
        return false;
    }
}

function numberValidation(e){
    e.target.value = e.target.value.replace(/[^\d ]/g,'');
    return false;
}

function TCNOKontrol(TCNO)
{
    var tek = 0,
        cift = 0,
        sonuc = 0,
        TCToplam = 0,
        i = 0,
        hatali = [11111111110, 22222222220, 33333333330, 44444444440, 55555555550, 66666666660, 7777777770, 88888888880, 99999999990];;

    if (TCNO.length != 11) return false;
    if (isNaN(TCNO)) return false;
    if (TCNO[0] == 0) return false;

    tek = parseInt(TCNO[0]) + parseInt(TCNO[2]) + parseInt(TCNO[4]) + parseInt(TCNO[6]) + parseInt(TCNO[8]);
    cift = parseInt(TCNO[1]) + parseInt(TCNO[3]) + parseInt(TCNO[5]) + parseInt(TCNO[7]);

    tek = tek * 7;
    sonuc = Math.abs(tek - cift);
    if (sonuc % 10 != TCNO[9]) return false;

    for (var i = 0; i < 10; i++) {
        TCToplam += parseInt(TCNO[i]);
    }

    if (TCToplam % 10 != TCNO[10]) return false;

    if (hatali.toString().indexOf(TCNO) != -1) return false;

    return true;
}

function kisalt(inputname,uzunluk)
{
    var secilen = $('input[name="'+inputname+'"]').val();
    if (secilen.length > uzunluk)
    {
        var yeni = secilen.substring(0,uzunluk);
        $('input[name="'+inputname+'"]').val(yeni);
    }
}

$('.loginForm').on('submit', function(e)
{
    var tc = $('input[name="tc"]').val();

    if(!TCNOKontrol(tc))
    {
        $('.loading').fadeOut('fast');
        toastr.error('T.C numarası formatı doğru değil. Lütfen doğru T.C numarası giriniz.','Hata!');

        return false;
    }

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
    });
});
