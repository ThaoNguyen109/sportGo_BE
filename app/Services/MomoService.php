<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * MomoService
 *
 * Trách nhiệm: Tạo request + xác thực signature MoMo
 * Docs: https://developers.momo.vn/v3/docs/payment/api/payment-api
 */
class MomoService
{
    private string $partnerCode;
    private string $accessKey;
    private string $secretKey;
    private string $endpoint;
    private string $redirectUrl;
    private string $ipnUrl;

    public function __construct()
    {
        $this->partnerCode  = config('momo.partner_code');
        $this->accessKey    = config('momo.access_key');
        $this->secretKey    = config('momo.secret_key');
        $this->endpoint     = config('momo.endpoint');
        $this->redirectUrl  = config('momo.redirect_url');
        $this->ipnUrl       = config('momo.ipn_url');
    }

    /**
     * Tạo link thanh toán MoMo.
     *
     * @param string $orderId    Mã đơn hàng (unique)
     * @param string $requestId  Mã request (unique)
     * @param int    $amount     Số tiền (VNĐ)
     * @param string $orderInfo  Mô tả đơn hàng hiển thị trên MoMo
     * @return array  ['payUrl' => '...', 'orderId' => '...', 'requestId' => '...']
     * @throws \Exception
     */
    public function createPayment(
        string $orderId,
        string $requestId,
        int $amount,
        string $orderInfo
    ): array {
        // Tạo chữ ký HMAC SHA256
        // Thứ tự các field PHẢI đúng theo docs MoMo (alphabetical)
        // orderExpireTime KHÔNG được gửi lên MoMo sandbox (HTTP 400 "Bad format request")
        // Field này chỉ hoạt động trên production với partner được MoMo cấp phép riêng.
        // Trên sandbox, thời hạn QR do MoMo cố định (~15 phút).

        $rawSignature = "accessKey={$this->accessKey}"
            . "&amount={$amount}"
            . "&extraData="
            . "&ipnUrl={$this->ipnUrl}"
            . "&orderId={$orderId}"
            . "&orderInfo={$orderInfo}"
            . "&partnerCode={$this->partnerCode}"
            . "&redirectUrl={$this->redirectUrl}"
            . "&requestId={$requestId}"
            . "&requestType=captureWallet";

        $signature = hash_hmac('sha256', $rawSignature, $this->secretKey);

        $payload = [
            'partnerCode' => $this->partnerCode,
            'accessKey'   => $this->accessKey,
            'requestId'   => $requestId,
            'amount'      => $amount,
            'orderId'     => $orderId,
            'orderInfo'   => $orderInfo,
            'redirectUrl' => $this->redirectUrl,
            'ipnUrl'      => $this->ipnUrl,
            'extraData'   => '',
            'requestType' => 'captureWallet',
            'signature'   => $signature,
            'lang'        => 'vi',
        ];

        $response = Http::timeout(30)
            ->post($this->endpoint, $payload);

        // Hiện lỗi thật từ MoMo để dễ debug (thay vì ẩn sau "Không thể kết nối")
        if (!$response->successful()) {
            $body = $response->json();
            $detail = $body['message'] ?? $response->body();
            throw new \Exception(
                "MoMo HTTP {$response->status()}: {$detail}",
                $response->status() >= 400 ? $response->status() : 503
            );
        }

        $data = $response->json();

        // resultCode = 0 là thành công
        if ($data['resultCode'] !== 0) {
            throw new \Exception(
                'MoMo từ chối tạo giao dịch: ' . ($data['message'] ?? 'Unknown error'),
                422
            );
        }

        return [
            'payUrl'    => $data['payUrl'],
            'orderId'   => $orderId,
            'requestId' => $requestId,
        ];
    }

    /**
     * Xác thực chữ ký từ MoMo callback (IPN hoặc redirect).
     * QUAN TRỌNG: Bước này bắt buộc để tránh giả mạo callback.
     *
     * @param array $data  Toàn bộ data MoMo gửi về
     * @return bool
     */
    public function verifySignature(array $data): bool
    {
        // Tạo lại raw signature theo đúng thứ tự MoMo quy định
        $rawSignature = "accessKey={$this->accessKey}"
            . "&amount={$data['amount']}"
            . "&extraData={$data['extraData']}"
            . "&message={$data['message']}"
            . "&orderId={$data['orderId']}"
            . "&orderInfo={$data['orderInfo']}"
            . "&orderType={$data['orderType']}"
            . "&partnerCode={$data['partnerCode']}"
            . "&payType={$data['payType']}"
            . "&requestId={$data['requestId']}"
            . "&responseTime={$data['responseTime']}"
            . "&resultCode={$data['resultCode']}"
            . "&transId={$data['transId']}";

        $expectedSignature = hash_hmac('sha256', $rawSignature, $this->secretKey);

        return hash_equals($expectedSignature, $data['signature']);
    }

    /**
     * Tạo orderId unique cho mỗi giao dịch.
     * Format: SPORTGO_{bookingId}_{timestamp}
     */
    public function generateOrderId(int $bookingId): string
    {
        return 'SPORTGO_' . $bookingId . '_' . time();
    }

    /**
     * Tạo requestId unique.
     */
    public function generateRequestId(): string
    {
        return Str::uuid()->toString();
    }
}