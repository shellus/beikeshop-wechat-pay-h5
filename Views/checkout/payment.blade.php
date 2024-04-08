<script src="{{ asset('vendor/qrcode/qrcode.min.js') }}"></script>

<div>
  @if (!empty($native_url))
  <div class="text-center my-2 fs-4">{{ __('WechatPayH5::common.scan_qrcode') }}</div>
  <div id="wx-qrcode" class="mt-3 d-flex justify-content-center"></div>
  @else
  <div class="text-center my-2 fs-4">{{ __('WechatPayH5::common.pay_loading') }}</div>
  @endif
</div>

<script type="text/javascript">
  // 微信环境内支付
  // 1. oauth获取openid
  var oauth_url = '{!! $oauth_url ?? '' !!}';
  if (oauth_url) {
    window.location.href = oauth_url;
  }
  // 2. JSAPI支付地址跳转
  var js_api = {!! $js_api ?? 'null' !!};
  if (js_api) {
    // 下方代码来自：https://pay.weixin.qq.com/docs/merchant/apis/jsapi-payment/jsapi-transfer-payment.html
    function onBridgeReady() {
      // alert("支付参数：" + JSON.stringify(js_api));return;
      WeixinJSBridge.invoke('getBrandWCPayRequest', js_api,
        function(res) {
          if (res.err_msg === "get_brand_wcpay_request:ok") {
            // 使用以上方式判断前端返回,微信团队郑重提示：
            //res.err_msg将在用户支付成功后返回ok，但并不保证它绝对可靠。
          }
        });
    }
    if (typeof WeixinJSBridge == "undefined") {
      if (document.addEventListener) {
        document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
      } else if (document.attachEvent) {
        document.attachEvent('WeixinJSBridgeReady', onBridgeReady);
        document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
      }
    } else {
      onBridgeReady();
    }
  }
  // 3. APP支付，需要和APP通信
  var app_params = {!! $app_params ?? 'null' !!};
  if (app_params) {
    if (typeof FlutterBridge == "undefined") {
      alert('FlutterBridge 对象不存在，无法调用APP支付');
    } else {
      FlutterBridge.postMessage(JSON.stringify({
        action: 'WechatPayParams',
        data: app_params,
        callFun: 'AppWechatPayCallback'
      }))
    }
  }
  function AppWechatPayCallback() {
    console.log('AppWechatPayCallback !')
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
