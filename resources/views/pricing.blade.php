<x-main>
    <form action="https://sanalposprov.garanti.com.tr/servlet/gt3dengine" method="post" class="form-pay-send">
        <input name="secure3dsecuritylevel" value="{{$data['secure3dsecuritylevel']}}" type="text" />
        <input name="cardnumber" value="{{$data['cardnumber']}}" type="text" />
        <input name="cardexpiredatemonth" value="{{$data['cardexpiredatemonth']}}" type="text" />
        <input name="cardexpiredateyear" value="{{$data['cardexpiredateyear']}}" type="text" />
        <input name="cardcvv2" value="{{$data['cardcvv2']}}" type="text" />
        <input type="hidden" name="mode" value="{{$data['mode']}}" />
        <input type="hidden" name="apiversion" value="{{$data['apiversion']}}" />
        <input type="hidden" name="terminalprovuserid" value="{{$data['terminalprovuserid']}}" />
        <input type="hidden" name="terminaluserid" value="{{$data['terminaluserid']}}" />
        <input type="hidden" name="terminalmerchantid" value="{{$data['terminalmerchantid']}}" />
        <input type="hidden" name="txntype" value="{{$data['txntype']}}" />
        <input type="hidden" name="txnamount" value="{{$data['txnamount']}}" />
        <input type="hidden" name="txncurrencycode" value="{{$data['txncurrencycode']}}" />
        <input type="hidden" name="txninstallmentcount" value="{{$data['txninstallmentcount']}}" />
        <input type="hidden" name="orderid" value="{{$data['orderid']}}" />
        <input type="hidden" name="terminalid" value="{{$data['terminalid']}}" />
        <input type="hidden" name="successurl" value="{{$data['successurl']}}" />
        <input type="hidden" name="errorurl" value="{{$data['errorurl']}}" />
        <input type="hidden" name="customeremailaddress" value="{{$data['customeremailaddress']}}" />
        <input type="hidden" name="customeripaddress" value="{{$data['customeripaddress']}}" />
        <input type="hidden" name="secure3dhash" value="{{$data['secure3dhash']}}" />
        <button type="submit">Gönder</button>
    </form>
</x-main>
