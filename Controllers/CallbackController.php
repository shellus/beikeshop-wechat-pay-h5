<?php
namespace Plugin\WechatPayH5\Controllers;

use App\Http\Controllers\Controller;
use Beike\Models\Order;
use Beike\Services\StateMachineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Plugin\WechatPayH5\Services\Payment\Wechat;

class CallbackController extends Controller
{
    /**
     * 微信扫码支付轮训订单状态接口
     */
    public function orderStatus(Request $request)
    {
        $orderNumber = $request->get('number');
        $order       = Order::query()->where('number', $orderNumber)->firstOrFail();
        $data        = [
            'number' => $orderNumber,
            'status' => $order->status ?? 'unknown',
        ];

        return json_success('', $data);
    }

    /**
     * 微信支付异步回调
     * 支付成功后会从微信支付服务器通知到该地址
     *
     * @throws \Exception|\Throwable
     */
    public function notify(Request $request)
    {
        Log::info('wechat_pay_notify: begin.');
        $wechatPay = new Wechat();
        $payment   = $wechatPay->payment;

        try {
            $headers = $request->headers->all();
            $body = $request->getContent();
            Log::info('getBackData headers.', $headers);
            Log::info('getBackData body: ' . $body);
            $backData = $payment->getBackData($headers, $body);
            Log::info('wechat_pay_notify: backData:', $backData);

            $orderId = $wechatPay->getOriginalOrderId($backData['out_trade_no']);
            $order   = Order::query()->where('number', $orderId)->first();
            if (! $order) {
                throw new \Exception('wechat_pay_notify: order not exist!');
            }

            if ($order->status == 'paid') {
                throw new \Exception('wechat_pay_notify: order status id has been changed');
            }

            $successful = $backData['trade_state'] === 'SUCCESS';
            if (!$successful) {
                throw new \Exception('wechat_pay_notify: result_code is not success');
            }
            StateMachineService::getInstance($order)->changeStatus('paid', '订单已支付', true);

            Log::info('wechat_pay_notify: success.', ['order' => $order->number]);
            return new JsonResponse(['code'=> 'SUCCESS', 'message' => 'wechat_pay_notify: changed to paid now']);

        } catch (\Exception $e) {
            $message = 'wechat_pay_notify: exception:' . $e->getMessage();
            Log::error($message);
            return new JsonResponse(['code'=> 'FAIL', 'message' => $message], 400);
        }
    }
}
