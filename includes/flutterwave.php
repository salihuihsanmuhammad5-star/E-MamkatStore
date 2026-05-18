<?php
// includes/flutterwave.php
// Helper class for Flutterwave inline payment

class FlutterwaveAPI {
    private string $secretKey;
    private string $publicKey;
    private string $encKey;
    private string $baseUrl;

    public function __construct() {
        $this->secretKey = FLW_SECRET_KEY;
        $this->publicKey = FLW_PUBLIC_KEY;
        $this->encKey    = FLW_ENCRYPTION_KEY;
        $this->baseUrl   = FLW_BASE_URL;
    }

    /**
     * Initiate a Flutterwave inline transaction.
     * Returns the checkout URL the customer must be redirected to.
     */
    public function initializeTransaction(array $params): ?array {
        // Required payload
        $payload = [
            'tx_ref'            => $params['tx_ref'],
            'amount'            => $params['amount'],
            'currency'          => $params['currency'] ?? 'NGN',
            'redirect_url'      => $params['redirect_url'],
            'payment_options'   => 'card,banktransfer,ussd',
            'customer'          => [
                'email' => $params['customer_email'],
                'name'  => $params['customer_name'] ?? '',
            ],
            'customizations'    => [
                'title'       => 'RedStore Payment',
                'description' => $params['description'] ?? 'Order Payment',
                'logo'        => $params['logo'] ?? BASE_URL . '/images/logo.png',
            ],
        ];

        $response = $this->sendRequest('/payments', $payload);

        if ($response && ($response['status'] ?? '') === 'success') {
            return [
                'checkout_url' => $response['data']['link'] ?? null,
            ];
        }

        error_log('Flutterwave init error: ' . json_encode($response));
        return null;
    }

    /**
     * Verify a transaction using the transaction ID
     */
    public function verifyTransaction(string $transactionId): ?array {
        $response = $this->sendRequest('/transactions/' . $transactionId . '/verify', [], 'GET');
        if ($response && ($response['status'] ?? '') === 'success') {
            return $response['data'] ?? null;
        }
        return null;
    }

    /**
     * cURL wrapper
     */
    private function sendRequest(string $endpoint, array $data, string $method = 'POST'): ?array {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/json'
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // disable in production
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('Flutterwave cURL error: ' . $error);
            return null;
        }

        return json_decode($body, true);
    }
}
