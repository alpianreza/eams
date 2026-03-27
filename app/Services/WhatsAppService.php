<?php

namespace App\Services;

use Config\Services;
use Config\WhatsApp;

class WhatsAppService
{
    protected WhatsApp $config;

    public function __construct(?WhatsApp $config = null)
    {
        $this->config = $config ?? config('WhatsApp');
    }

    public function isEnabled(): bool
    {
        return $this->config->enabled;
    }

    public function canSend(): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        if ($this->config->provider !== 'fonnte') {
            return false;
        }

        return $this->config->fonnteToken !== '';
    }

    /**
     * @return array{success: bool, status: int, response: string}
     */
    public function sendMessage(string $target, string $message): array
    {
        $target = trim($target);
        $message = trim($message);

        if ($target === '' || $message === '') {
            return [
                'success' => false,
                'status' => 0,
                'response' => 'Target atau pesan kosong.',
            ];
        }

        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'status' => 0,
                'response' => 'WhatsApp belum diaktifkan (whatsapp.enabled=false).',
            ];
        }

        if ($this->config->provider !== 'fonnte') {
            return [
                'success' => false,
                'status' => 0,
                'response' => 'Provider WhatsApp tidak didukung: ' . $this->config->provider,
            ];
        }

        if ($this->config->fonnteToken === '') {
            return [
                'success' => false,
                'status' => 0,
                'response' => 'Token Fonnte belum diisi.',
            ];
        }

        try {
            $response = Services::curlrequest([
                'timeout' => $this->config->timeout,
                'http_errors' => false,
            ])->post($this->config->fonnteEndpoint, [
                'headers' => [
                    'Authorization' => $this->config->fonnteToken,
                ],
                'form_params' => [
                    'target' => $target,
                    'message' => $message,
                ],
            ]);

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();
            $json = json_decode($body, true);

            $ok = $status >= 200 && $status < 300;
            if (is_array($json) && array_key_exists('status', $json)) {
                $ok = $ok && (bool) $json['status'];
            }

            return [
                'success' => $ok,
                'status' => $status,
                'response' => $body,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 0,
                'response' => $e->getMessage(),
            ];
        }
    }
}
