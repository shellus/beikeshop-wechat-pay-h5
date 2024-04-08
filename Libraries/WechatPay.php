<?php
namespace Plugin\WechatPayH5\Libraries;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Plugin\WechatPayH5\WeChatPayV3\Crypto\AesGcm;
use Plugin\WechatPayH5\WeChatPayV3\Formatter;
use Plugin\WechatPayH5\WeChatPayV3\Util\PemUtil;
use Plugin\WechatPayH5\WeChatPayV3\Builder;
use Plugin\WechatPayH5\WeChatPayV3\BuilderChainable;
use Plugin\WechatPayH5\WeChatPayV3\Crypto\Rsa;

class WechatPay
{
    public const URL_NATIVEPAY = 'https://api.mch.weixin.qq.com/v3/pay/transactions/native';
    public const URL_H5PAY = 'https://api.mch.weixin.qq.com/v3/pay/transactions/h5';
    public const URL_JSPAY = 'https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi';
    public const URL_APPPAY = 'https://api.mch.weixin.qq.com/v3/pay/transactions/app';

    /**
     * 微信支付配置数组
     * appid        公众账号appid
     * mch_id       商户号
     * apikey       加密key
     * appsecret    公众号appsecret
     * cert_serial_no 商户API证书序列号
     * cert_path    证书路径
     * cert_key_path    证书密钥路径
     */
    private array $_config;

    private $privateKey;
    private BuilderChainable $client;

    /**
     * @param array $config 微信支付配置数组
     */
    public function __construct(array $config)
    {
        $this->_config = $config;

        Log::info('WechatPayH5 Libraries construct config:', $this->_config);
        // WeChatPayV3
        $merchantPrivateKeyInstance = Rsa::from('file://' . $this->_config['cert_key_path'], Rsa::KEY_TYPE_PRIVATE);
        $platformPublicKeyInstance = Rsa::from('file://' . $this->_config['cert_path'], Rsa::KEY_TYPE_PUBLIC);
        $platformCertificateSerial = PemUtil::parseCertificateSerialNo('file://' . $this->_config['cert_path']);

        $this->client = Builder::factory([
            'mchid'      => $this->_config['mch_id'],
            'serial'     => $this->_config['cert_serial_no'],
            'privateKey' => $merchantPrivateKeyInstance,
            'certs'      => [
                $platformCertificateSerial => $platformPublicKeyInstance,
            ],
        ]);

        $this->privateKey = $merchantPrivateKeyInstance;
    }
    /**
     * 二维码支付
     * @param $subject
     * @param $out_trade_no
     * @param $total_fee
     * @param $notify_url
     * @return string|null
     * @throws Exception
     */
    public function nativePay($subject, $out_trade_no, $total_fee, $notify_url): ?string
    {
        $data = [];
        $data['appid'] = $this->_config['appid'];
        $data['mchid'] = $this->_config['mch_id'];
        $data['description'] = $subject;
        $data['out_trade_no'] = $out_trade_no;
//        $data['time_expire'] = ; // 订单过期时间
//        $data['attach'] = ; // 附加数据
        $data['notify_url'] = $notify_url;
        $data['amount'] = [
            'total' => $total_fee,
            'currency' => 'CNY'
        ];
        $data['scene_info'] = [
            'payer_client_ip' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
        ];

        $resp = $this->jsonPost(self::URL_NATIVEPAY, $data);

        return $resp['code_url'] ?? null;
    }
    /**
     * APP加载微信支付SDK支付（APP支付）
     * @param $subject
     * @param $out_trade_no
     * @param $total_fee
     * @param $notify_url
     * @return string|null
     * @throws Exception
     */
    public function appScheme($subject, $out_trade_no, $total_fee, $notify_url): string
    {
        $data = [];
        $data['appid'] = $this->_config['appid'];
        $data['mchid'] = $this->_config['mch_id'];
        $data['description'] = $subject;
        $data['out_trade_no'] = $out_trade_no;
//        $data['time_expire'] = ; // 订单过期时间
//        $data['attach'] = ; // 附加数据
        $data['notify_url'] = $notify_url;
        $data['amount'] = [
            'total' => $total_fee,
            'currency' => 'CNY'
        ];
        $data['scene_info'] = [
            'payer_client_ip' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
        ];
        // 文档：https://pay.weixin.qq.com/docs/merchant/apis/in-app-payment/direct-jsons/app-prepay.html

        $resp = $this->jsonPost(self::URL_APPPAY, $data);
        // $resp = {"prepay_id":"wx22180206205223b8f0bb853c12420110000"}
        return $resp['prepay_id'];
    }
    /**
     * 微信环境内支付（JsPay）
     * @param $subject
     * @param $out_trade_no
     * @param $total_fee
     * @param $notify_url
     * @return string|null
     * @throws Exception
     */
    public function jsPay($openid, $subject, $out_trade_no, $total_fee, $notify_url): string
    {
        $data = [];
        $data['appid'] = $this->_config['appid'];
        $data['mchid'] = $this->_config['mch_id'];
        $data['description'] = $subject;
        $data['out_trade_no'] = $out_trade_no;
//        $data['time_expire'] = ; // 订单过期时间
//        $data['attach'] = ; // 附加数据
        $data['notify_url'] = $notify_url;
        $data['amount'] = [
            'total' => $total_fee,
            'currency' => 'CNY'
        ];
        $data['scene_info'] = [
            'payer_client_ip' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
        ];
        // 文档：https://developers.weixin.qq.com/doc/offiaccount/OA_Web_Apps/Wechat_webpage_authorization.html
        // JSAPI多了下面的字段
        // 少了 scene_info.h5_info
        $data['payer'] = [
            'openid' => $openid
        ];

        $resp = $this->jsonPost(self::URL_JSPAY, $data);
        // $resp = {"prepay_id":"wx22180206205223b8f0bb853c12420110000"}
        return $resp['prepay_id'];
    }
    public function getSign($appId, $prepayId) :array
    {
        $params = [
            'appId'     => $appId,
            'timeStamp' => (string)Formatter::timestamp(),
            'nonceStr'  => Formatter::nonce(),
            'package'   => 'prepay_id=' . $prepayId,
        ];
        $params += ['paySign' => Rsa::sign(
            Formatter::joinedByLineFeed(...array_values($params)),
            $this->privateKey
        ), 'signType' => 'RSA'];
        return $params;
    }
    public function genSchemeParams($appId, $prepayId, $mchId) :array
    {
        // 《简书 - iOS不用微信SDK唤起微信支付》：https://www.jianshu.com/p/8930b4496023
        $params = [
            'appId'     => $appId,
            'timeStamp' => (string)Formatter::timestamp(),
            'nonceStr'  => Formatter::nonce(),
            'prepayId'   => $prepayId,
        ];
        $sign = Rsa::sign(Formatter::joinedByLineFeed(...array_values($params)), $this->privateKey);
        $params += [
            'partnerId' => $mchId,
            'packageValue' => 'Sign=WXPay',
            'sign' => $sign,
        ];
        // 生成 URL Scheme, 没用，这个调不起来微信
//        return "weixin://app/{$params['appId']}/pay/?nonceStr={$params['nonceStr']}&package=Sign%%3DWXPay&partnerId=$mchId&prepayId=$prepayId&timeStamp={$params['timeStamp']}&sign=$sign&signType=$signType";
        return $params;
    }

    /**
     * H5支付
     * @param $subject
     * @param $out_trade_no
     * @param $total_fee
     * @param $notify_url
     * @return string|null
     * @throws Exception
     */
    public function h5Pay($subject, $out_trade_no, $total_fee, $notify_url): ?string
    {
        $data = [];
        $data['appid'] = $this->_config['appid'];
        $data['mchid'] = $this->_config['mch_id'];
        $data['description'] = $subject;
        $data['out_trade_no'] = $out_trade_no;
//        $data['time_expire'] = ; // 订单过期时间
//        $data['attach'] = ; // 附加数据
        $data['notify_url'] = $notify_url;
        $data['amount'] = [
            'total' => $total_fee,
            'currency' => 'CNY'
        ];
        $data['scene_info'] = [
            'payer_client_ip' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
            'h5_info' => [
                'type' => 'Wap',
            ]
        ];

        $resp = $this->jsonPost(self::URL_H5PAY, $data);

        return $resp['h5_url'] ?? null;
    }

    private function jsonPost($url, $data): array
    {
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        try {
            $resp = $this->client
                ->chain($url)
                ->post(['json' => $data]);
            $content = $resp->getBody()->getContents();
            Log::info('WechatPayH5 jsonPost response: '. $content);
        } catch (\Exception $e) {
            // 进行错误处理
            if ($e instanceof RequestException && $e->hasResponse()) {
                $r = $e->getResponse();
                app('log')->error('WechatPay Error', [$r->getBody()->getContents()]);
            }
            throw $e;
        }
        return json_decode($content, true);
    }


    public function getBackData($headers, $inBody): ?array
    {
        $inWechatpaySignature = Arr::first($headers['wechatpay-signature']);

        $inWechatpayTimestamp = Arr::first($headers['wechatpay-timestamp']);
        // 平台证书序列号
        $inWechatpaySerial = Arr::first($headers['wechatpay-serial']);

        $inWechatpayNonce = Arr::first($headers['wechatpay-nonce']);

        $apiv3Key = $this->_config['apikey'];
        $platformPublicKeyInstance = Rsa::from('file://' . $this->_config['cert_path'], Rsa::KEY_TYPE_PUBLIC);
        // 检查通知时间偏移量，允许5分钟之内的偏移
        $timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
        $verifiedStatus = Rsa::verify(
            Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, $inBody),
            $inWechatpaySignature,
            $platformPublicKeyInstance
        );
        if (!$timeOffsetStatus){
            throw new \Exception('wechat_pay_notify: time offset error');
        }
        if (!$verifiedStatus) {
            throw new \Exception('signature verify fail', 400);
        }
        // 转换通知的JSON文本消息为PHP Array数组
        $inBodyArray = (array)json_decode($inBody, true);
        // 使用PHP7的数据解构语法，从Array中解构并赋值变量
        ['resource' => [
            'ciphertext'      => $ciphertext,
            'nonce'           => $nonce,
            'associated_data' => $aad
        ]] = $inBodyArray;
        // 加密文本消息解密
        $inBodyResource = AesGcm::decrypt($ciphertext, $apiv3Key, $nonce, $aad);
        // 把解密后的文本转换为PHP Array数组
        return (array)json_decode($inBodyResource, true);
    }
}
