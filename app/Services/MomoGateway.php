<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Services\HttpFacade;
use Illuminate\Support\Str;

/**
 * MomoGateway
 *
 * Pattern: Adapter (Concrete Adapter) + Factory Method (Concrete Product)
 * Reason: Chuyển đổi (Adapt) API không tương thích của MoMo thành
 *         interface PaymentGatewayInterface mà PaymentService kỳ vọng.
 *         Tương đương MomoService cũ nhưng tuân thủ interface chung.
 * Uses:   HttpFacade (Facade Pattern) để gửi request HTTP.
 */
class MomoGateway implements PaymentGatewayInterface
{
    private string $partnerCode;
    private string $accessKey;
    private string $secretKey;
    private string $endpoint;
    private string $redirectUrl;
    private string $ipnUrl;

    public function __construct(private HttpFacade $http)
    {
        $this->partnerCode = config('momo.partner_code');
        $this->accessKey   = config('momo.access_key');
        $this->secretKey   = config('momo.secret_key');
        $this->endpoint    = config('momo.endpoint');
        $this->redirectUrl = config('momo.redirect_url');
        $this->ipnUrl      = config('momo.ipn_url');
    }

    /**
     * Tạo link thanh toán MoMo.
     *
     * {@inheritDoc}
     */
    public function createPayment(string $orderId, string $requestId, int $amount, string $info): array
    {
        // Tạo chữ ký HMAC SHA256 theo thứ tự field quy định bởi MoMo docs
        // orderExpireTime KHÔNG được gửi lên MoMo sandbox (HTTP 400 "Bad format request")
        $rawSignature = "accessKey={$this->accessKey}"
            . "&amount={$amount}"
            . "&extraData="
            . "&ipnUrl={$this->ipnUrl}"
            . "&orderId={$orderId}"
            . "&orderInfo={$info}"
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
            'orderInfo'   => $info,
            'redirectUrl' => $this->redirectUrl,
            'ipnUrl'      => $this->ipnUrl,
            'extraData'   => '',
            'requestType' => 'captureWallet',
            'signature'   => $signature,
            'lang'        => 'vi',
        ];

        // Sử dụng HttpFacade (Facade Pattern) thay vì gọi Http::post() trực tiếp
        $response = $this->http->request($this->endpoint, $payload);

        if (!$response->successful()) {
            $body   = $response->json();
            $detail = $body['message'] ?? $response->body();
            throw new \Exception(
                "MoMo HTTP {$response->status()}: {$detail}",
                $response->status() >= 400 ? $response->status() : 503
            );
        }

        $data = $response->json();

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
     * QUAN TRỌNG: Bắt buộc để tránh giả mạo callback.
     *
     * {@inheritDoc}
     */
    public function verifySignature(array $data): bool
    {
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
     * Sinh orderId duy nhất.
     * Format: SPORTGO_{bookingId}_{timestamp}
     *
     * {@inheritDoc}
     */
    public function generateOrderId(int $bookingId): string
    {
        return 'SPORTGO_' . $bookingId . '_' . time();
    }

    /**
     * Sinh requestId UUID duy nhất.
     *
     * {@inheritDoc}
     */
    public function generateRequestId(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Định danh chuỗi của cổng thanh toán.
     *
     * {@inheritDoc}
     */
    public function process(): string
    {
        return 'momo';
    }
}
