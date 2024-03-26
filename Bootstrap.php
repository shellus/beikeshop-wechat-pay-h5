<?php
namespace Plugin\WechatPayH5;

use Plugin\WechatPayH5\Services\Payment\Wechat;

class Bootstrap
{
    public function boot()
    {
        $this->beforeOrderPay();
    }

    public function beforeOrderPay()
    {
        add_hook_filter('service.payment.pay.data', function ($data) {
            $wechatPay = new Wechat();
            $data['native_url'] = $wechatPay->getNativeUrl($data['order']);
            $data['h5_url'] = $wechatPay->getH5Url($data['order']);
            return $data;
        });
    }
}
