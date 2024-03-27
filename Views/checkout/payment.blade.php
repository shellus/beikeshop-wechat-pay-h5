<script src="{{ asset('vendor/qrcode/qrcode.min.js') }}"></script>

<div>
  <div class="text-center my-2 fs-4">{{ __('WechatPayH5::common.scan_cod_pay') }}</div>
  <div id="wx-qrcode" class="mt-3 d-flex justify-content-center"></div>
</div>

<script type="text/javascript">
  // 微信环境内支付
  // 1. oauth获取openid
  var oauth_url = '{!! $oauth_url ?? '' !!}';
  if (oauth_url) {
    window.location.href = oauth_url;
  }
  // 2. JSAPI支付地址跳转
  var js_url = '{!! $js_url ?? '' !!}';
  if (js_url) {
    window.location.href = js_url;
  }
  // 手机浏览器支付
  var h5_url = '{!! $h5_url ?? '' !!}';
  if (h5_url) {
    window.location.href = h5_url;
  }
  // PC端二维码支付
  var native_url = '{!! $native_url ?? '' !!}';
  if (native_url) {
    new QRCode(document.getElementById("wx-qrcode"), {
      text: native_url,
      width : 260,
      height : 260,
      correctLevel : QRCode.CorrectLevel.M
    });
  }

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
