<script src="{{ asset('vendor/qrcode/qrcode.min.js') }}"></script>

<div>

  <div class="text-center my-2 fs-4">{{ __('WechatPayH5::common.scan_cod_pay') }}</div>
  <div id="wx-qrcode" class="mt-3 d-flex justify-content-center"></div>
  <div class="mt-3 d-flex justify-content-center align-items-center">
    <a href="{{ $h5_url }}" class="btn btn-primary btn-sm nowrap mb-2">{{ __('shop/account/order_info.to_pay') }}</a>
  </div>

</div>

<script type="text/javascript">
  new QRCode(document.getElementById("wx-qrcode"), {
    text: '{{ $native_url }}',
    width : 260,
    height : 260,
    correctLevel : QRCode.CorrectLevel.M
  });

  const timer = window.setInterval(() => {
    setTimeout(chekOrderStatus(), 0);
  }, 1000)

  function chekOrderStatus() {
    $http.post('{{ shop_route('wechat_pay_h5.order_status', ['number' => $order['number']]) }}', null, {hload: true}).then((res) => {
      if (res.data.status == 'paid') {
        window.clearInterval(timer)
        layer.msg('{{ __('admin/marketing.pay_success_title') }}');
        window.location.href = '{{ shop_route('account.order.show', ['number' => $order['number']]) }}'
      }
    })
  }
</script>
