<?php
return [
    [
        'name'      => 'app_id',
        'label_key' => 'common.app_id',
        'type'      => 'string',
        'description' => '微信开放平台中服务号的APPID',
        'required'  => true,
    ],
    [
        'name'      => 'app_secret',
        'label_key' => 'common.app_secret',
        'type'      => 'string',
        'description' => '微信开放平台中服务号的APPSECRET',
        'required'  => true,
    ],
    [
        'name'      => 'merchant_id',
        'label_key' => 'common.merchant_id',
        'type'      => 'string',
        'description' => '微信支付商户号，在微信商户后台-账户中心-商户信息-基本账户信息',
        'required'  => true,
    ],
    [
        'name'      => 'merchant_secret',
        'label_key' => 'common.merchant_secret',
        'type'      => 'string',
        'description' => '微信支付商户API密钥V3，在微信商户后台-账户中心-API安全-设置APIv3密钥',
        'required'  => true,
    ],
    [
        'name'      => 'merchant_cert_serial_no',
        'label_key' => 'common.merchant_cert_serial_no',
        'type'      => 'string',
        'description' => '商户证书序列号，在微信商户后台-账户中心-API安全-申请API证书-管理证书',
        'required'  => true,
    ],
    [
        'name'      => 'merchant_cert_key_path',
        'label_key' => 'common.merchant_cert_key_path',
        'type'      => 'string',
        'description' => '商户私钥，在微信商户后台-账户中心-API安全-申请API证书-管理证书，放置于项目storage/app/key.pem，然后这里填入storage/app/key.pem',
        'required'  => true,
    ],
    [
        // https://pay.weixin.qq.com/docs/merchant/development/interface-rules/wechatpay-certificates.html
        'name'      => 'merchant_cert_path',
        'label_key' => 'common.merchant_cert_path',
        'type'      => 'string',
        'description' => '平台公钥，在微信商户后台-账户中心-API安全-平台证书-平台证书管理，放置于项目storage/app/platform.pem，然后这里填入storage/app/platform.pem',
        'required'  => true,
    ]
];
