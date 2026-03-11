<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\BaseHandler;
use CodeIgniter\Session\Handlers\FileHandler;

class Session extends BaseConfig
{
    /**
     * Session Driver
     */
    public string $driver = FileHandler::class;

    /**
     * Session Cookie Name
     */
    public string $cookieName = 'eams_session';

    /**
     * Session Expiration
     * 28800 detik = 8 jam
     */
    public int $expiration = 28800;

    /**
     * Session Save Path
     */
    public string $savePath = WRITEPATH . 'session';

    /**
     * Match IP
     */
    public bool $matchIP = false;

    /**
     * Session Time to Update
     * Session ID regenerate tiap 1 jam
     */
    public int $timeToUpdate = 3600;

    /**
     * Destroy old session saat regenerate
     */
    public bool $regenerateDestroy = false;

    /**
     * Session Database Group
     */
    public ?string $DBGroup = null;

    /**
     * Redis Lock Retry Interval
     */
    public int $lockRetryInterval = 100_000;

    /**
     * Redis Lock Max Retries
     */
    public int $lockMaxRetries = 300;
}
