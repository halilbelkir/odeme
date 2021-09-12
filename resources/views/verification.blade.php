<x-guest>
    <div id="timer" class="col-12">
        <div class="clock-wrapper">
            <span class="hours">00</span>
            <span class="dots">:</span>
            <span class="minutes">00</span>
            <span class="dots">:</span>
            <span class="seconds">00</span>
        </div>
    </div>

    <form method="post" action="{{route('verification.control')}}">
        @csrf
        <div class="form-floating mb-2">
            <input type="text" class="form-control" name="customer_code" placeholder="Müşteri Kodu" value="{{$customerCode}}" disabled>
            <label for="floatingPassword">Müşteri Kodu</label>
        </div>
        <div class="form-floating mb-2">
            <input type="text" class="form-control" name="tc" placeholder="T.C" value="{{$tc}}" disabled>
            <label for="floatingPassword">T.C</label>
        </div>
        <div class="form-floating mb-2">
            <input type="number" class="form-control" name="verification_code" placeholder="Doğrulama Kodu">
            <label for="floatingPassword">Doğrulama Kodu</label>
        </div>
        <button class="w-100 btn btn-md btn-red mt-3" type="submit">Doğrula</button>
    </form>

    <div class="sendSms">
        <form action="{{route('repeat.sms')}}" method="post">
            @csrf
            <input type="hidden" name="tc" value="{{$tc}}">
            <button type="submit" class="w-100 text-right btn btn-sm color-red mt-2" style="text-align: right !important;">Tekrar Sms Gönder</button>
        </form>
    </div>

    @section('js')
        <script>
            var ammount = 60;
            var clockType = 'countdown';
            var timer = $('#timer');
            var s = $(timer).find('.seconds');
            var m = $(timer).find('.minutes');
            var h = $(timer).find('.hours');

            $(document).ready(function(){
                startClock();
            });

            function pad(d)
            {
                return (d < 10) ? '0' + d.toString() : d.toString()
            }

            function startClock() {

                hasStarted = false
                hasEnded = false

                seconds = 0
                minutes = 0
                hours = 0

                if (ammount > 3599) {
                    let hou = Math.floor(ammount / 3600)
                    hours = hou
                    let min = Math.floor((ammount - (hou * 3600)) / 60)
                    minutes = min;
                    let sec = (ammount - (hou * 3600)) - (min * 60)
                    seconds = sec
                }
                else if (ammount > 59) {
                    let min = Math.floor(ammount / 60)
                    minutes = min
                    let sec = ammount - (min * 60)
                    seconds = sec
                }
                else {
                    seconds = ammount
                }

                if (seconds <= 10 && clockType == 'countdown' && minutes == 0 && hours == 0) {
                    $(timer).find('span').addClass('red')
                }

                refreshClock()

                switch (clockType) {
                    case 'countdown':
                        countdown()
                        break
                    case 'cronometer':
                        cronometer()
                        break
                    default:
                        break;
                }
            }
            function countdown() {
                hasStarted = true
                interval = setInterval(() => {
                    if(hasEnded == false) {
                        if (seconds <= 11 && minutes == 0 && hours == 0) {
                            $(timer).find('span').addClass('red')
                        }

                        if(seconds == 0 && minutes == 0 || (hours > 0  && minutes == 0 && seconds == 0)) {
                            hours--
                            minutes = 59
                            seconds = 60
                            refreshClock()
                        }

                        if(seconds > 0) {
                            seconds--
                            refreshClock()
                        }
                        else if (seconds == 0) {
                            minutes--
                            seconds = 59
                            refreshClock()
                        }
                    }

                }, 1000)
            }
            function cronometer() {
                hasStarted = true
                interval = setInterval(() => {
                    if (seconds < 59) {
                        seconds++
                        refreshClock()
                    }
                    else if (seconds == 59) {
                        minutes++
                        seconds = 0
                        refreshClock()
                    }

                    if (minutes == 60) {
                        hours++
                        minutes = 0
                        seconds = 0
                        refreshClock()
                    }

                }, 1000)
            }
            function refreshClock() {
                $(s).text(pad(seconds))
                $(m).text(pad(minutes))
                if (hours < 0) {
                    $(s).text('00')
                    $(m).text('00')
                    $(h).text('00')
                } else {
                    $(h).text(pad(hours))
                }

                if (hours == 0 && minutes == 0 && seconds == 0 && hasStarted == true)
                {
                    hasEnded   = true
                    var confirm = window.confirm('Süre doldu.Kodu tekrar göndermek istiyor musunuz?');
                    if (confirm == true)
                    {
                        $.ajaxSetup({ headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") } });
                        $.ajax(
                            {
                                url: "{{route('tc.control')}}",
                                type: "POST",
                                data:
                                    {
                                        tc: {{$tc}},
                                    },
                                success : function(e)
                                {
                                    startClock();
                                }
                            });
                    }
                    else
                    {
                        window.location.href = "{{route('index')}}";
                    }
                }
            }
        </script>
    @endsection
</x-guest>
