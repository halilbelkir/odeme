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
