<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CsrfAssetFilter
 *
 * Menyisipkan meta CSRF + bootstrap JS ke setiap response HTML.
 *
 * Tujuannya supaya proteksi CSRF bisa diaktifkan global tanpa harus
 * menyunting puluhan view lama: script yang disuntik akan otomatis
 * melampirkan token ke request fetch/XHR yang mutating dan menambahkan
 * hidden input ke form POST yang belum memakai csrf_field().
 *
 * Kalau nanti semua view sudah rapi memakai csrf_field() / csrf_header(),
 * filter ini bisa dilepas dari Config\Filters::$globals.
 */
class CsrfAssetFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Hanya untuk dokumen HTML penuh.
        if (method_exists($request, 'isAJAX') && $request->isAJAX()) {
            return;
        }

        $contentType = strtolower($response->getHeaderLine('Content-Type'));
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return;
        }

        $body = (string) $response->getBody();
        if ($body === '' || stripos($body, '</head>') === false) {
            return;
        }

        // Sudah pernah disuntik (mis. sub-request), jangan dobel.
        if (str_contains($body, 'name="csrf-token"')) {
            return;
        }

        $meta = '<meta name="csrf-header" content="' . esc(csrf_header(), 'attr') . '">'
            . '<meta name="csrf-name" content="' . esc(csrf_token(), 'attr') . '">'
            . '<meta name="csrf-token" content="' . esc(csrf_hash(), 'attr') . '">';

        $body = preg_replace('#</head>#i', $meta . "\n</head>", $body, 1);

        $script = '<script>' . $this->bootstrapScript() . '</script>';

        if (stripos($body, '</body>') !== false) {
            $body = preg_replace('#</body>#i', $script . "\n</body>", $body, 1);
        } else {
            $body .= $script;
        }

        $response->setBody($body);
    }

    private function bootstrapScript(): string
    {
        return <<<'JS'
(function () {
  function metaValue(name) {
    var el = document.querySelector('meta[name="' + name + '"]');
    return el ? el.getAttribute('content') : '';
  }

  var headerName = metaValue('csrf-header');
  var tokenName = metaValue('csrf-name');
  var tokenHash = metaValue('csrf-token');

  if (!headerName || !tokenName || !tokenHash) {
    return;
  }

  window.EAMS_CSRF = { header: headerName, name: tokenName, hash: tokenHash };

  var mutating = /^(POST|PUT|PATCH|DELETE)$/i;

  // --- fetch ---
  if (typeof window.fetch === 'function') {
    var originalFetch = window.fetch;
    window.fetch = function (resource, options) {
      options = options || {};
      var method = options.method || (resource && resource.method) || 'GET';

      if (mutating.test(method)) {
        var headers = new Headers(options.headers || (resource && resource.headers) || {});
        if (!headers.has(headerName)) {
          headers.set(headerName, tokenHash);
        }
        options = Object.assign({}, options, { headers: headers });
        if (!options.credentials) {
          options.credentials = 'same-origin';
        }
      }

      return originalFetch.call(this, resource, options);
    };
  }

  // --- XMLHttpRequest (termasuk jQuery.ajax) ---
  var originalOpen = XMLHttpRequest.prototype.open;
  var originalSend = XMLHttpRequest.prototype.send;

  XMLHttpRequest.prototype.open = function (method) {
    this.__eamsMethod = method;
    return originalOpen.apply(this, arguments);
  };

  XMLHttpRequest.prototype.send = function () {
    if (mutating.test(this.__eamsMethod || 'GET')) {
      try {
        this.setRequestHeader(headerName, tokenHash);
      } catch (e) {
        /* header sudah diset manual, abaikan */
      }
    }
    return originalSend.apply(this, arguments);
  };

  // --- form POST tanpa csrf_field() ---
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement)) {
      return;
    }
    if (!mutating.test(form.getAttribute('method') || 'GET')) {
      return;
    }
    if (form.querySelector('input[name="' + tokenName + '"]')) {
      return;
    }

    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = tokenName;
    input.value = tokenHash;
    form.appendChild(input);
  }, true);
})();
JS;
    }
}
