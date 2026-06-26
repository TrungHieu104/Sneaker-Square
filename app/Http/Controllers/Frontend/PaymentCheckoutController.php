<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Models\OrderModel as Order;


class PaymentCheckoutController extends Controller
{
    public function execPostRequest($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            )
        );
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        //execute post
        $result = curl_exec($ch);
        //close connection
        curl_close($ch);
        return $result;
    }

    public function paymentCheckout(Request $request)
    {
        if (isset($_POST['payUrl'])) {
            $endpoint = config('services.momo.endpoint');
            $partnerCode = config('services.momo.partner_code');
            $accessKey = config('services.momo.access_key');
            $secretKey = config('services.momo.secret_key');
            $amount = $request->thanhtien;
            $orderId = $request->order_code;
            $orderInfo = "Thanh toán hóa đơn " . $orderId . " qua MoMo ";
            $redirectUrl = env('APP_URL') . "/kiem-tra-trang-thai-dat-hang";
            $ipnUrl = env('APP_URL') . "/kiem-tra-trang-thai-dat-hang";
            $extraData = "";
            $serectkey = $secretKey;
            $requestId = time() . "";
            $requestType = "payWithATM";
            $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
            $signature = hash_hmac("sha256", $rawHash, $serectkey);
            $data = array(
                'partnerCode' => $partnerCode,
                'partnerName' => "Test",
                "storeId" => "MomoTestStore",
                'requestId' => $requestId,
                'amount' => $amount,
                'orderId' => $orderId,
                'orderInfo' => $orderInfo,
                'redirectUrl' => $redirectUrl,
                'ipnUrl' => $ipnUrl,
                'lang' => 'vi',
                'extraData' => $extraData,
                'requestType' => $requestType,
                'signature' => $signature
            );
            $result = $this->execPostRequest($endpoint, json_encode($data));
            $jsonResult = json_decode($result, true); // decode json

            if (!isset($jsonResult['payUrl'])) {
                $errorMsg = isset($jsonResult['message']) ? $jsonResult['message'] : 'Không thể khởi tạo thanh toán MoMo.';
                if (isset($jsonResult['subErrors']) && is_array($jsonResult['subErrors'])) {
                    $errorDetails = [];
                    foreach ($jsonResult['subErrors'] as $subError) {
                        $errorDetails[] = $subError['field'] . ': ' . $subError['message'];
                    }
                    $errorMsg .= ' (' . implode(', ', $errorDetails) . ')';
                }
                return redirect()->back()
                    ->with('message', 'Lỗi thanh toán MoMo')
                    ->with('text', $errorMsg)
                    ->with('iconMessage', 'error');
            }

            //save URL payment before return url
            $order = Order::where('order_code', $orderId)->first();
            if ($order) {
                $order->order_payment_url = $jsonResult['payUrl'];
                $order->save();
            }
            return redirect()->to($jsonResult['payUrl']);
        } else if (isset($_POST['redirect'])) {
            $vnp_Url = config('services.vnpay.url');
            $vnp_Returnurl = env('APP_URL') . "/kiem-tra-trang-thai-dat-hang";
            $vnp_TmnCode = config('services.vnpay.tmn_code'); //Mã website tại VNPAY
            $vnp_HashSecret = config('services.vnpay.hash_secret'); //Chuỗi bí mật
            $vnp_TxnRef = $request->order_code; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này sang VNPAY
            $vnp_OrderInfo = "Thanh toán hóa đơn " . $request->order_code . " qua VNPay ";
            $vnp_OrderType = 'billpayment';
            $vnp_Amount = $request->thanhtien * 100;
            $vnp_Locale = 'vn';
            $vnp_BankCode = 'NCB';
            $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
            $inputData = array(
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => $vnp_OrderType,
                "vnp_ReturnUrl" => $vnp_Returnurl,
                "vnp_TxnRef" => $vnp_TxnRef,
            );

            if (isset($vnp_BankCode) && $vnp_BankCode != "") {
                $inputData['vnp_BankCode'] = $vnp_BankCode;
            }
            ksort($inputData);
            $query = "";
            $i = 0;
            $hashdata = "";
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }

            $vnp_Url = $vnp_Url . "?" . $query;
            if (isset($vnp_HashSecret)) {
                $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret); //  
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }
            $returnData = array(
                'code' => '00'
                ,
                'message' => 'success'
                ,
                'data' => $vnp_Url
            );
            
            $order = Order::where('order_code', $vnp_TxnRef)->first();
            $order->order_payment_url = $vnp_Url;
            $order->save();
            if (isset($_POST['redirect'])) {
                header('Location: ' . $vnp_Url);
                die();
            } else {
                echo json_encode($returnData);
            }
        }
    }
}