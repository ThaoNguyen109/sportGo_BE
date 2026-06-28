<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * HttpFacade
 *
 * Pattern: Facade
 * Reason: Bao bọc thư viện HTTP thô của Laravel thành một giao diện đơn giản,
 *         thống nhất cho tất cả các Gateway sử dụng.
 */
class HttpFacade
{
    /**
     * Gửi POST request đến một URL với dữ liệu JSON.
     *
     * @param string $url   URL endpoint đích
     * @param array  $data  Payload JSON cần gửi
     * @return Response
     */
    public function request(string $url, array $data): Response
    {
        return Http::timeout(30)->post($url, $data);
    }
}
