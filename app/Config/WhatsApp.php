<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class WhatsApp extends BaseConfig
{
    /**
     * Aktifkan pengiriman WhatsApp.
     */
    public bool $enabled = false;

    /**
     * Provider WhatsApp yang dipakai.
     * Saat ini didukung: fonnte
     */
    public string $provider = 'fonnte';

    /**
     * Endpoint API provider.
     */
    public string $fonnteEndpoint = 'https://api.fonnte.com/send';

    /**
     * Token API provider.
     */
    public string $fonnteToken = '';

    /**
     * Timeout request API (detik).
     */
    public int $timeout = 20;

    /**
     * Fallback mapping nama user ke nomor WA.
     * Format: "NAMA A:62812...,NAMA B:62813..."
     */
    public string $namePhoneMap = '';

    public function __construct()
    {
        parent::__construct();

        $this->enabled = filter_var(env('whatsapp.enabled', false), FILTER_VALIDATE_BOOLEAN);
        $this->provider = strtolower((string) env('whatsapp.provider', $this->provider));
        $this->fonnteEndpoint = trim((string) env('whatsapp.fonnteEndpoint', $this->fonnteEndpoint));
        $this->fonnteToken = trim((string) env('whatsapp.fonnteToken', $this->fonnteToken));

        $timeout = (int) env('whatsapp.timeout', $this->timeout);
        $this->timeout = $timeout > 0 ? $timeout : 20;

        $this->namePhoneMap = trim((string) env('whatsapp.namePhoneMap', $this->namePhoneMap));
    }
}
