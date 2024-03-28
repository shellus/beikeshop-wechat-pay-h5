<?php
namespace Plugin\WechatPayH5\Services\Payment;

use Beike\Models\Order;
use Beike\Services\CurrencyService;
use Illuminate\Support\Facades\Log;
use Plugin\WechatPayH5\Libraries\WechatPay;

class Wechat
{
    private string $notifyUrl = '';

    private string $mchId;

    private string $apiKey = '';

    private string $appId = '';

    private string $appSecret = '';

    private string $certSerialNo = '';

    private string $certPath = '';

    private string $certKeyPath = '';

    public WechatPay $payment;

    public function __construct()
    {
        $this->appId     = plugin_setting('wechat_pay_h5.app_id');
        $this->appSecret = plugin_setting('wechat_pay_h5.app_secret');
        $this->mchId     = plugin_setting('wechat_pay_h5.merchant_id');
        $this->certSerialNo    = plugin_setting('wechat_pay_h5.merchant_cert_serial_no');
        $this->certPath    = plugin_setting('wechat_pay_h5.merchant_cert_path');
        $this->certKeyPath    = plugin_setting('wechat_pay_h5.merchant_cert_key_path');
        $this->apiKey    = plugin_setting('wechat_pay_h5.merchant_secret');


        $this->notifyUrl = config('app.url') . '/callback/wechat_pay/notify';


        $this->payment = new WechatPay([
            'appid'       => $this->appId,
            'mch_id'      => $this->mchId,
            'apikey'      => $this->apiKey,
            'appsecret'   => $this->appSecret,
            'cert_serial_no'   => $this->certSerialNo,
            'cert_path'   => base_path($this->certPath),
            'cert_key_path'   => base_path($this->certKeyPath),
        ]);
    }

    public function getOrderAttributes($order)
    {
        $total   = CurrencyService::getInstance()->convert($order->total, $order->currency, 'CNY');
        $total   = round($total, 2);
        $subject = $order->orderProducts()->first()->name;

        $attributes = [
            'subject'      => $subject,
            'out_trade_no' => $this->getUniqueOrderId($order),
            'total_fee'    => $total * 100,
        ];
        Log::info('Attributes: ', $attributes);

        return $attributes;
    }

    protected function getUniqueOrderId($order): string
    {
        sleep(1);
        return $order->number . '-' . time();
    }

    public function getOriginalOrderId($outTradeNo)
    {
        $orderIds = explode('-', $outTradeNo);
        if (is_array($orderIds) && isset($orderIds[0])) {
            return $orderIds[0];
        }

        return 0;
    }

    /**
     * 获取支付链接
     * @param Order $order
     * @return string
     * @throws \Exception
     */
    public function getNativeUrl(Order $order): string
    {
        $attributes = $this->getOrderAttributes($order);
        $payUrl    = $this->payment->nativePay($attributes['subject'], $attributes['out_trade_no'],
            $attributes['total_fee'], $this->notifyUrl);

        Log::info("NotifyUrl: {$this->notifyUrl}");
        Log::info("PayUrl: {$payUrl}");

        return $payUrl;
    }
    public function getOauthUrl($order)
    {
        // 去这个地址获取授权，然后它会跳回来，就得到了openid
        https://open.weixin.qq.com/connect/oauth2/authorize?
        //appid=wx520c15f417810387&
        //redirect_uri=https%3A%2F%2Fchong.qq.com%2Fphp%2Findex.php%3Fd%3D%26c%3DwxAdapter%26m%3DmobileDeal%26showwxpaytitle%3D1%26vb2ctag%3D4_2030_5_1194_60&
        //response_type=code&
        //scope=snsapi_base&
        //state=123&
        //connect_redirect=1#wechat_redirect

        // $back 这个url其实就是下面的 openIDByCode 方法，用code换取openid
        $back = rawurlencode(config('app.url') . '/callback/wechat_pay_h5/oauth');
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?scope=snsapi_base&redirect_uri={$back}&appid={$this->appId}&response_type=code&state={$order->number}#wechat_redirect";
        return $url;
    }
    public function openIDByCode($code)
    {
        // 文档：https://developers.weixin.qq.com/doc/offiaccount/OA_Web_Apps/Wechat_webpage_authorization.html
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->appId}&secret={$this->appSecret}&code={$code}&grant_type=authorization_code";
        $response = (new \GuzzleHttp\Client)->get($url);
        $content = $response->getBody()->getContents();
        Log::info('WechatPayH5 openIDByCode $url: '. $url . '; $content: ' . $content);
        $data = json_decode($content, true);
        return $data['openid'];
    }
    /**
     * 获取支付链接
     * @param Order $order
     * @throws \Exception
     */
    public function getJsApi($openid, Order $order): array
    {
        $attributes = $this->getOrderAttributes($order);
        $prepay_id    = $this->payment->jsPay($openid, $attributes['subject'], $attributes['out_trade_no'],
            $attributes['total_fee'], $this->notifyUrl);
        // js支付是需要一组参数(含有签名），而不是一个url
        // 文档：https://pay.weixin.qq.com/docs/merchant/apis/jsapi-payment/jsapi-transfer-payment.html
        return $this->payment->getSign($this->appId, $prepay_id);
    }
    /**
     * 获取支付链接
     * @param Order $order
     * @return string
     * @throws \Exception
     */
    public function getH5Url(Order $order): string
    {
        $attributes = $this->getOrderAttributes($order);
        $payUrl    = $this->payment->h5Pay($attributes['subject'], $attributes['out_trade_no'],
            $attributes['total_fee'], $this->notifyUrl);

        Log::info("NotifyUrl: {$this->notifyUrl}");
        Log::info("PayUrl: {$payUrl}");

        return $payUrl;
    }
}
