<?php
// includes/monnify.php
// Helper class for Monnify (Moniepoint) payment gateway

class MonnifyAPI {
    private string $baseUrl;
    private string $apiKey;
    private string $secretKey;
    private string $contractCode;
    private ?string $accessToken = null;
    private ?int $tokenExpiresAt = null;

    public function __construct() {
        $this->baseUrl      = rtrim(MONNIFY_BASE_URL, '/');
        $this->apiKey       = MONNIFY_API_KEY;
        $this->secretKey    = MONNIFY_SECRET_KEY;
        $this->contractCode = MONNIFY_CONTRACT_CODE;
    }

    /**
     * Authenticate and cache the access token (valid ~1 hour)
     */
    public function authenticate(): bool {
        // Return cached token if still valid
        if ($this->accessToken && $this->tokenExpiresAt > time()) {
            return true;
        }

        $url = $this->baseUrl . '/api/v1/auth/login';
        $auth = base64_encode($this->apiKey . ':' . $this->secretKey);

        $response = $this->sendRequest($url, [], 'POST', [
            'Authorization: Basic ' . $auth
        ]);

        if (!$response || !($response['requestSuccessful'] ?? false)) {
            error_log('Monnify auth failed: ' . json_encode($response));
            return false;
        }

        $this->accessToken   = $response['responseBody']['accessToken'] ?? null;
        $this->tokenExpiresAt = time() + ($response['responseBody']['expiresIn'] ?? 3600) - 60;

        return (bool)$this->accessToken;
    }

    /**
     * Initialize a one‑time payment transaction
     * Returns the checkout URL that the customer must be redirected to
     */
    public function initializeTransaction(array $params): ?array {
        if (!$this->authenticate()) {
            return null;
        }

        $url = $this->baseUrl . '/api/v1/merchant/transactions/init-transaction';

        // Merge required fields with defaults
        $payload = array_merge([
            'currencyCode'      => 'NGN',
            'contractCode'      => $this->contractCode,
            'paymentMethods'    => ['CARD', 'ACCOUNT_TRANSFER', 'USSD'],
            'incomeSplitConfig' => []           // remove if you do not want split payments
        ], $params);

        // Make sure amount is numeric
        if (isset($payload['amount']) && is_numeric($payload['amount'])) {
          $payload['amount'] = round((float)$payload['amount'], 2);
        }

        return $this->sendRequest($url, $payload, 'POST', [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
    }

    /**
     * Verify the status of a transaction using the paymentReference
     */
    public function verifyTransaction(string $paymentReference): ?array {
        if (!$this->authenticate()) {
            return null;
        }

        $url = $this->baseUrl . '/api/v2/merchant/transactions/query?paymentReference=' . urlencode($paymentReference);

        return $this->sendRequest($url, [], 'GET', [
            'Authorization: Bearer ' . $this->accessToken
        ]);
    }

    /**
     * Low‑level cURL wrapper
     */
    private function sendRequest(string $url, array $data, string $method = 'GET', array $headers = []): ?array {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $method === 'GET' && !empty($data) ? $url . '?' . http_build_query($data) : $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

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
            error_log('Monnify cURL error: ' . $error);
            return null;
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Monnify JSON decode error: ' . $body);
            return null;
        }

        return $decoded;
    }
}