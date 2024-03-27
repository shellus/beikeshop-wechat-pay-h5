<?php
use Illuminate\Support\Facades\Route;
use Plugin\WechatPayH5\Controllers\CallbackController;

Route::post('/callback/wechat_pay/order_status', [CallbackController::class, 'orderStatus'])->name('wechat_pay_h5.order_status');
Route::post('/callback/wechat_pay/notify', [CallbackController::class, 'notify'])->name('wechat_pay_h5.notify');
Route::get('/callback/wechat_pay_h5/oauth', [CallbackController::class, 'wechatOauthCallback'])->name('wechat_pay_h5.oauth');
