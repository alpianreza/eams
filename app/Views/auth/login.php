<?= $this->extend('layouts/auth') ?>

<?php $title = 'Login'; ?>

<?= $this->section('styles') ?>
<style>
  body.login-page {
    min-height: 100vh;
    min-height: 100dvh;
    margin: 0;
    padding: max(20px, env(safe-area-inset-top)) max(20px, env(safe-area-inset-right)) max(20px, env(safe-area-inset-bottom)) max(20px, env(safe-area-inset-left));
    display: grid;
    place-items: center;
    overflow-x: hidden;
    font-family: var(--f-sans);
    color: var(--c-text);
    background:
      linear-gradient(120deg, rgba(15, 23, 42, .84), rgba(15, 23, 42, .58)),
      url('<?= base_url("assets/images/company/bg-login.png") ?>') center / cover fixed;
  }

  .auth-theme-toggle {
    position: fixed;
    top: max(16px, env(safe-area-inset-top));
    right: max(16px, env(safe-area-inset-right));
    z-index: 5;
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, .2);
    border-radius: var(--r-full);
    background: rgba(15, 23, 42, .56);
    color: #fff;
    backdrop-filter: blur(12px);
  }

  .auth-theme-toggle:hover,
  .auth-theme-toggle:focus-visible {
    border-color: rgba(255, 255, 255, .5);
    background: rgba(15, 23, 42, .76);
  }

  .auth-shell {
    width: min(960px, 100%);
    min-height: 590px;
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(390px, .95fr);
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, .18);
    border-radius: 18px;
    background: var(--c-surface);
    box-shadow: 0 24px 70px rgba(0, 0, 0, .34);
  }

  .auth-brand-panel {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    padding: 44px;
    color: #fff;
    background:
      radial-gradient(circle at 15% 12%, rgba(94, 159, 232, .3), transparent 38%),
      linear-gradient(155deg, rgba(15, 31, 51, .96), rgba(17, 41, 70, .94));
  }

  .auth-brand-panel::after {
    content: "";
    position: absolute;
    right: -110px;
    bottom: -120px;
    width: 330px;
    height: 330px;
    border: 1px solid rgba(255, 255, 255, .12);
    border-radius: 50%;
    box-shadow: 0 0 0 44px rgba(255, 255, 255, .025), 0 0 0 88px rgba(255, 255, 255, .018);
    pointer-events: none;
  }

  .auth-brand-content,
  .auth-brand-footer {
    position: relative;
    z-index: 1;
  }

  .auth-brand-logo {
    width: 86px;
    height: auto;
    margin-bottom: 34px;
    filter: drop-shadow(0 10px 22px rgba(0, 0, 0, .3));
  }

  .auth-brand-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    color: rgba(255, 255, 255, .72);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
  }

  .auth-brand-panel h1 {
    max-width: 480px;
    margin: 0 0 14px;
    color: #fff;
    font-size: clamp(1.8rem, 3.5vw, 2.6rem);
    font-weight: 800;
    letter-spacing: -.04em;
    line-height: 1.14;
  }

  .auth-brand-panel p {
    max-width: 470px;
    margin: 0;
    color: rgba(255, 255, 255, .68);
    font-size: 14px;
    line-height: 1.7;
  }

  .auth-benefit-list {
    display: grid;
    gap: 12px;
    margin-top: 34px;
  }

  .auth-benefit-item {
    display: flex;
    align-items: center;
    gap: 11px;
    color: rgba(255, 255, 255, .86);
    font-size: 13px;
    font-weight: 600;
  }

  .auth-benefit-item > span {
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    border: 1px solid rgba(255, 255, 255, .16);
    border-radius: 8px;
    background: rgba(255, 255, 255, .08);
  }

  .auth-brand-footer {
    color: rgba(255, 255, 255, .48);
    font-size: 11px;
  }

  .auth-form-panel {
    display: flex;
    align-items: center;
    padding: 48px;
    background: var(--c-surface);
  }

  .auth-form-wrap {
    width: 100%;
    max-width: 390px;
    margin: 0 auto;
  }

  .auth-mobile-logo {
    display: none;
    width: 72px;
    height: auto;
    margin-bottom: 24px;
  }

  .auth-form-kicker {
    display: block;
    margin-bottom: 8px;
    color: var(--c-primary);
    font-size: 12px;
    font-weight: 750;
    letter-spacing: .08em;
    text-transform: uppercase;
  }

  .auth-form-wrap h2 {
    margin: 0 0 8px;
    color: var(--c-text);
    font-size: 1.65rem;
    font-weight: 800;
    letter-spacing: -.035em;
  }

  .auth-form-subtitle {
    margin: 0 0 28px;
    color: var(--c-text-muted);
    font-size: 13px;
    line-height: 1.55;
  }

  .auth-alert {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 20px;
    padding: 12px;
    border: 1px solid var(--c-late-border);
    border-radius: var(--r-md);
    background: var(--c-late-soft);
    color: var(--c-late);
    font-size: 12px;
    line-height: 1.5;
  }

  .auth-field {
    margin-bottom: 18px;
  }

  .auth-field label {
    display: block;
    margin-bottom: 7px;
    color: var(--c-text);
    font-size: 12px;
    font-weight: 700;
  }

  .auth-input-wrap {
    position: relative;
  }

  .auth-input-icon {
    position: absolute;
    top: 50%;
    left: 14px;
    z-index: 2;
    color: var(--c-text-muted);
    transform: translateY(-50%);
    pointer-events: none;
  }

  .auth-input-wrap .form-control {
    min-height: 48px;
    padding: 10px 44px;
    border: 1px solid var(--c-border-strong);
    border-radius: var(--r-md);
    background: var(--c-surface);
    color: var(--c-text);
    font-size: 14px;
  }

  .auth-input-wrap .form-control:focus {
    border-color: var(--c-primary);
    box-shadow: 0 0 0 3px var(--c-primary-soft);
  }

  .auth-password-toggle {
    position: absolute;
    top: 50%;
    right: 4px;
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: var(--r-sm);
    background: transparent;
    color: var(--c-text-muted);
    transform: translateY(-50%);
  }

  .auth-password-toggle:hover,
  .auth-password-toggle:focus-visible {
    background: var(--c-surface-hover);
    color: var(--c-primary);
  }

  .auth-submit {
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    margin-top: 4px;
    border-radius: var(--r-md);
    font-size: 14px;
    font-weight: 750;
  }

  .auth-submit[disabled] {
    cursor: wait;
  }

  .auth-security-note {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    margin-top: 18px;
    color: var(--c-text-subtle);
    font-size: 11px;
  }

  @media (max-width: 900px) {
    body.login-page {
      padding: max(16px, env(safe-area-inset-top)) max(16px, env(safe-area-inset-right)) max(16px, env(safe-area-inset-bottom)) max(16px, env(safe-area-inset-left));
      background: var(--c-canvas);
    }

    .auth-shell {
      width: min(480px, 100%);
      min-height: auto;
      grid-template-columns: 1fr;
      border-color: var(--c-border);
      border-radius: 14px;
      box-shadow: var(--e-3);
    }

    .auth-brand-panel {
      display: none;
    }

    .auth-form-panel {
      padding: 38px 34px;
    }

    .auth-mobile-logo {
      display: block;
    }
  }

  @media (max-width: 576px) {
    body.login-page {
      place-items: start center;
      padding: max(72px, calc(env(safe-area-inset-top) + 56px)) 0 env(safe-area-inset-bottom);
      background: var(--c-surface);
    }

    .auth-theme-toggle {
      border-color: var(--c-border);
      background: var(--c-surface-sunk);
      color: var(--c-text);
      backdrop-filter: none;
    }

    .auth-shell {
      width: 100%;
      border: 0;
      border-radius: 0;
      box-shadow: none;
    }

    .auth-form-panel {
      align-items: flex-start;
      min-height: calc(100dvh - 72px - env(safe-area-inset-bottom));
      padding: 24px;
    }

    .auth-form-wrap {
      max-width: none;
    }

    .auth-mobile-logo {
      width: 64px;
      margin-bottom: 22px;
    }

    .auth-form-wrap h2 {
      font-size: 1.5rem;
    }

    .auth-input-wrap .form-control,
    .auth-submit {
      min-height: 50px;
      font-size: 16px;
    }

    body.login-page.keyboard-open .auth-mobile-logo,
    body.login-page.keyboard-open .auth-form-kicker,
    body.login-page.keyboard-open .auth-form-subtitle {
      display: none;
    }

    body.login-page.keyboard-open .auth-form-panel {
      min-height: auto;
      padding-top: 8px;
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .auth-shell *,
    .auth-theme-toggle {
      transition-duration: .01ms !important;
      animation-duration: .01ms !important;
    }
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<button id="authThemeToggle" type="button" class="auth-theme-toggle" aria-label="Ganti tema" title="Ganti tema">
  <i class="bi bi-moon-stars" aria-hidden="true"></i>
</button>

<main class="auth-shell">
  <section class="auth-brand-panel" aria-label="Tentang EAMS">
    <div class="auth-brand-content">
      <img class="auth-brand-logo" src="<?= base_url('assets/images/company/logo.png') ?>" alt="PT Younghyun Star">
      <span class="auth-brand-kicker"><i class="bi bi-shield-check"></i> Enterprise workspace</span>
      <h1>Kelola aset dan kepatuhan dengan lebih terarah.</h1>
      <p>EAMS menyatukan checklist, inventaris, temuan, dan tindak lanjut operasional dalam satu sistem.</p>

      <div class="auth-benefit-list">
        <div class="auth-benefit-item"><span><i class="bi bi-check2-square"></i></span> Checklist terjadwal dan mudah dipantau</div>
        <div class="auth-benefit-item"><span><i class="bi bi-bar-chart-line"></i></span> Ringkasan progres secara real-time</div>
        <div class="auth-benefit-item"><span><i class="bi bi-lock"></i></span> Akses aman berdasarkan peran pengguna</div>
      </div>
    </div>
    <div class="auth-brand-footer">&copy; <?= date('Y') ?> PT YOUNGHYUN STAR</div>
  </section>

  <section class="auth-form-panel">
    <div class="auth-form-wrap">
      <img class="auth-mobile-logo" src="<?= base_url('assets/images/company/logo.png') ?>" alt="PT Younghyun Star">
      <span class="auth-form-kicker">Enterprise Asset Management</span>
      <h2>Masuk ke EAMS</h2>
      <p class="auth-form-subtitle">Gunakan akun perusahaan untuk melanjutkan ke dashboard.</p>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="auth-alert" role="alert"><i class="bi bi-exclamation-circle"></i><span><?= esc(session()->getFlashdata('error')) ?></span></div>
      <?php endif; ?>

      <form id="loginForm" action="<?= base_url('login') ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="auth-field">
          <label for="username">Username</label>
          <div class="auth-input-wrap">
            <i class="bi bi-person auth-input-icon" aria-hidden="true"></i>
            <input id="username" type="text" name="username" class="form-control" value="<?= esc(old('username')) ?>" placeholder="Masukkan username" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
          </div>
        </div>

        <div class="auth-field">
          <label for="password">Password</label>
          <div class="auth-input-wrap">
            <i class="bi bi-lock auth-input-icon" aria-hidden="true"></i>
            <input id="password" type="password" name="password" class="form-control" placeholder="Masukkan password" autocomplete="current-password" required>
            <button id="passwordToggle" type="button" class="auth-password-toggle" aria-label="Tampilkan password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
          </div>
        </div>

        <button id="loginSubmit" type="submit" class="btn btn-primary w-100 auth-submit">
          <span>Masuk</span><i class="bi bi-arrow-right" aria-hidden="true"></i>
        </button>
      </form>

      <div class="auth-security-note"><i class="bi bi-shield-lock"></i> Koneksi aman untuk pengguna terotorisasi</div>
    </div>
  </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  (function () {
    var body = document.body;
    var root = document.documentElement;
    var themeToggle = document.getElementById('authThemeToggle');
    var password = document.getElementById('password');
    var passwordToggle = document.getElementById('passwordToggle');
    var loginForm = document.getElementById('loginForm');
    var loginSubmit = document.getElementById('loginSubmit');
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var order = ['light', 'dark', 'system'];
    var current = body.getAttribute('data-theme-preference') || 'light';
    var themeMeta = {
      light: { icon: 'bi-sun', label: 'Tema terang. Klik untuk tema gelap.' },
      dark: { icon: 'bi-moon-stars', label: 'Tema gelap. Klik untuk ikut sistem.' },
      system: { icon: 'bi-circle-half', label: 'Ikut sistem. Klik untuk tema terang.' }
    };

    function effectiveTheme(pref) {
      return pref === 'system' ? (media && media.matches ? 'dark' : 'light') : pref;
    }

    function applyTheme(pref, persist) {
      current = pref;
      root.setAttribute('data-bs-theme', effectiveTheme(pref));
      body.setAttribute('data-theme-preference', pref);
      themeToggle.innerHTML = '<i class="bi ' + themeMeta[pref].icon + '" aria-hidden="true"></i>';
      themeToggle.setAttribute('aria-label', themeMeta[pref].label);
      themeToggle.setAttribute('title', themeMeta[pref].label);
      if (persist) document.cookie = 'theme=' + pref + ';path=/;max-age=31536000;SameSite=Lax';
    }

    applyTheme(current, false);
    themeToggle.addEventListener('click', function () {
      var index = order.indexOf(current);
      applyTheme(order[(index + 1) % order.length], true);
    });

    if (media && media.addEventListener) {
      media.addEventListener('change', function () {
        if (current === 'system') applyTheme('system', false);
      });
    }

    passwordToggle.addEventListener('click', function () {
      var reveal = password.type === 'password';
      password.type = reveal ? 'text' : 'password';
      passwordToggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
      passwordToggle.setAttribute('aria-label', reveal ? 'Sembunyikan password' : 'Tampilkan password');
      passwordToggle.innerHTML = '<i class="bi ' + (reveal ? 'bi-eye-slash' : 'bi-eye') + '" aria-hidden="true"></i>';
      password.focus({ preventScroll: true });
    });

    loginForm.addEventListener('submit', function () {
      if (!loginForm.checkValidity()) return;
      loginSubmit.disabled = true;
      loginSubmit.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Memproses...</span>';
    });

    document.addEventListener('focusin', function (event) {
      if (window.innerWidth <= 576 && event.target.matches('input, textarea, select')) body.classList.add('keyboard-open');
    });
    document.addEventListener('focusout', function () {
      window.setTimeout(function () {
        if (!document.activeElement.matches('input, textarea, select')) body.classList.remove('keyboard-open');
      }, 120);
    });
  })();
</script>
<?= $this->endSection() ?>
