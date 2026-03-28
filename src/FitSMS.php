<?php

namespace GlobalCloudMedia\FitSMS;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

class FitSMS
{
    protected $client;
    protected $apiToken;
    protected $senderId;
    protected $v4Base = 'https://app.fitsms.lk/api/v4';

    public function __construct(string $apiToken, string $senderId)
    {
        $this->apiToken = $apiToken;
        $this->senderId = $senderId;
        $this->client = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json',
            ],
            'timeout' => 30.0,
        ]);
    }

    /**
     * Helper to extract error message from Guzzle exceptions
     */
    private function getErrorMessage(Exception $e)
    {
        if ($e instanceof RequestException && $e->hasResponse()) {
            $response = $e->getResponse();
            $body = json_decode($response->getBody()->getContents(), true);

            return $body['message'] ?? $e->getMessage();
        }

        return $e->getMessage();
    }

    /**
     * Helper to extract error message from Guzzle exceptions
     */
    private function formatNumber(string $phone)
    {
        $cleaned = preg_replace('/\D/', '', $phone);
        if (str_starts_with($cleaned, '07') && strlen($cleaned) === 10)
            $cleaned = '94' . substr($cleaned, 1);
        if (str_starts_with($cleaned, '7') && strlen($cleaned) === 9)
            $cleaned = '94' . $cleaned;

        return $cleaned;
    }

    public function send($recipients, string $message, string $type = 'plain')
    {
        // Validate Type
        if (!in_array($type, ['plain', 'unicode'])) {
            throw new Exception("FitSMS Error: Invalid type. Use 'plain' or 'unicode'.");
        }

        // Validate and Format Sri Lankan Numbers
        $numbers = is_array($recipients) ? $recipients : explode(',', $recipients);
        $validated = array_map(function ($num) {
            $phone = $this->formatNumber($num);

            if (!preg_match('/^(94)(7[01245678])\d{7}$/', $phone)) {
                throw new Exception("FitSMS Error: Invalid SL number: " . $num);
            }
            return $phone;
        }, $numbers);

        try {
            $response = $this->client->post("{$this->v4Base}/sms/send", [
                'json' => [
                    'recipient' => implode(',', $validated),
                    'sender_id' => $this->senderId,
                    'type' => $type,
                    'message' => $message,
                ]
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            throw new Exception("FitSMS Request Failed: " . $e->getMessage());
        }
    }

    /**
     * Check status of an existing SMS (v4 API)
     * @param string $ruid - The unique reference ID
     * @param string $phone - The recipient number
     */
    public function getStatus(string $ruid, string $phone)
    {
        try {
            $response = $this->client->get("{$this->v4Base}/sms/{$ruid}", [
                'query' => [
                    'recipient' => $this->formatNumber($phone)
                ]
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            $message = $this->getErrorMessage($e);
            throw new Exception("FitSMS Status Check Failed: {$message}");
        }
    }

    /**
     * Retrieve account balance and SMS units (v4 logic usually on v4 base)
     */
    public function getBalance()
    {
        try {
            $response = $this->client->get("{$this->v4Base}/balance");
            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            $message = $this->getErrorMessage($e);
            throw new Exception("FitSMS Balance Check Failed: {$message}");
        }
    }

    /**
     * Retrieve full profile information
     */
    public function getProfile()
    {
        try {
            $response = $this->client->get("{$this->v4Base}/me");
            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            $message = $this->getErrorMessage($e);
            throw new Exception("FitSMS Profile Retrieval Failed: {$message}");
        }
    }
}