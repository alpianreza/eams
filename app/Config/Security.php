<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * CSRF Protection Method
     * --------------------------------------------------------------------------
     *
     * @var string 'cookie' or 'session'
     */
    public string $csrfProtection = 'session';

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Randomization
     * --------------------------------------------------------------------------
     *
     * Token di-mask ulang tiap render untuk mitigasi BREACH.
     * Token diambil client dari meta tag yang disuntik CsrfAssetFilter,
     * jadi randomisasi aman dipakai.
     */
    public bool $tokenRandomize = true;

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Name
     * --------------------------------------------------------------------------
     *
     * Diganti dari default framework (csrf_test_name).
     */
    public string $tokenName = 'eams_csrf_token';

    /**
     * --------------------------------------------------------------------------
     * CSRF Header Name
     * --------------------------------------------------------------------------
     */
    public string $headerName = 'X-CSRF-TOKEN';

    /**
     * --------------------------------------------------------------------------
     * CSRF Cookie Name
     * --------------------------------------------------------------------------
     *
     * Diganti dari default framework (csrf_cookie_name).
     */
    public string $cookieName = 'eams_csrf_cookie';

    /**
     * --------------------------------------------------------------------------
     * CSRF Expires
     * --------------------------------------------------------------------------
     */
    public int $expires = 7200;

    /**
     * --------------------------------------------------------------------------
     * CSRF Regenerate
     * --------------------------------------------------------------------------
     *
     * Sengaja false: aplikasi ini banyak memakai AJAX paralel (polling agent,
     * grid checklist). Rotasi token tiap request akan membuat request yang
     * sedang in-flight memakai token basi dan gagal 403.
     */
    public bool $regenerate = false;

    /**
     * --------------------------------------------------------------------------
     * CSRF Redirect
     * --------------------------------------------------------------------------
     */
    public bool $redirect = (ENVIRONMENT === 'production');
}
