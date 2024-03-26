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
