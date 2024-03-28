<?php
namespace Plugin\WechatPayH5;

use Illuminate\Support\Facades\Log;
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
            // 不同的支付类型的说明、文档、用法、请参阅文档《微信支付流程整理-CZDJ版.xmind》
            $wechatPay = new Wechat();
            if (strpos(request()->header('User-Agent'), 'MicroMessenger') !== false) {
                // 微信环境内需要使用jsAPI支付
                $openid = request()->get('openid');
                if (empty($openid)) {
                    // 文档：https://developers.weixin.qq.com/doc/offiaccount/OA_Web_Apps/Wechat_webpage_authorization.html
                    $data['oauth_url'] = $wechatPay->getOauthUrl($data['order']);
                } else {
                    // 上面的if里面经过 getOauthUrl->openIDByCode 之后就有openid了然后就跳回这里了
                    // 文档：https://pay.weixin.qq.com/docs/merchant/apis/jsapi-payment/jsapi-transfer-payment.html
                    $data['js_api'] = json_encode($wechatPay->getJsApi($openid, $data['order']));
                }
            } elseif(is_mobile()) {
                // 手机浏览器使用h5支付
                $data['h5_url'] = $wechatPay->getH5Url($data['order']);
            } else {
                $data['native_url'] = $wechatPay->getNativeUrl($data['order']);
            }
            Log::info('WechatPayH5 Bootstrap $data:', $data);
            return $data;
        });
    }
}
