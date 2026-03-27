<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<style>
    body.login-page {
        background:
            linear-gradient(rgba(90, 92, 97, 0.85), rgba(15, 23, 42, 0.95)),
            url('<?= base_url("assets/images/company/bg-login.png") ?>');

        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;

        min-height: 100vh;

        display: flex;
        align-items: center;
        justify-content: center;

        padding: 20px;
    }

    .login-box {
        width: 380px;
        max-width: 100%;
        display: flex;
        flex-direction: column;
    }

    .login-logo {
        text-align: center;
        margin-bottom: 25px;
        margin-top: -20px;
    }

    .login-logo img:hover {
        transform: translateY(-2px) scale(1.03);
        filter: drop-shadow(0 12px 25px rgba(0, 0, 0, 0.8));
    }

    .login-logo img {
        width: 120px;
        margin-bottom: 18px;
        filter: drop-shadow(0 8px 20px rgba(0, 0, 0, 0.6)) drop-shadow(0 0 10px rgba(59, 130, 246, 0.3));
    }

    .login-logo .title {
        font-size: 26px;
        font-weight: 600;
        color: white;
    }

    .login-logo .subtitle {
        font-size: 13px;
        color: #cbd5e1;
    }

    .card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
    }

    .login-card-body {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(6px);
        padding: 32px;
        border-radius: 14px;
    }

    .input-group {
        border-radius: 8px;
        overflow: hidden;
    }

    .input-group-text {
        background: #f8fafc;
    }

    .form-control {
        border-left: none;
    }

    .btn-login {
        border-radius: 8px;
        padding: 10px;
        font-weight: 600;
    }

    .btn-login:hover {
        transform: translateY(-1px);
    }

    .login-footer {
        text-align: center;
        color: #94a3b8;
        font-size: 12px;
        margin-top: 18px;
    }

    @media (max-width:576px) {
        body.login-page {
            padding: 16px 14px;
            justify-content: flex-start;
            align-items: flex-start;
            min-height: 100dvh;
            transition: padding 0.2s ease, align-items 0.2s ease, justify-content 0.2s ease;
        }

        body.login-page.keyboard-open {
            justify-content: flex-start;
            align-items: flex-start;
            padding-top: 10px;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            min-height: calc(95dvh - 32px);
            transition: transform 0.2s ease;
        }

        .login-main {
            margin-top: auto;
            margin-bottom: auto;
        }

        .login-logo {
            margin-top: 35px;
            margin-bottom: 10px;
        }

        .login-logo img {
            width: 120px;
        }

        .login-card-body {
            padding: 22px;
        }

        .login-logo .title {
            font-size: 22px;
        }

        .login-footer {
            margin-top: 12px;
        }

        body.login-page.keyboard-open .login-box {
            min-height: auto;
        }

        body.login-page.keyboard-open .login-main {
            margin-top: 0;
            margin-bottom: 0;
        }
    }
</style>

<div class="login-box">

    <div class="login-logo mb-4 text-center">
        <img src="<?= base_url('assets/images/company/logo.png') ?>" alt="logo">
    </div>

    <div class="login-main">
        <div class="card shadow-lg border-0">
            <div class="card-body login-card-body">

                <p class="text-center mb-4" style="font-size:14px;">
                    Enterprise Asset Management System
                </p>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger py-2">
                        <?= session()->getFlashdata('error') ?>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('login') ?>" method="post">

                    <div class="input-group mb-3">
                        <span class="input-group-text">
                            <i class="bi bi-person"></i>
                        </span>

                        <input type="text"
                            name="username"
                            class="form-control"
                            placeholder="Username"
                            required>
                    </div>

                    <div class="input-group mb-4">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>

                        <input type="password"
                            name="password"
                            class="form-control"
                            placeholder="Password"
                            required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-login">
                        Login
                    </button>

                </form>

            </div>
        </div>
    </div>

    <div class="login-footer">
        &copy; <?= date('Y') ?> PT YOUNGHYUN STAR
    </div>

</div>

<script>
    (() => {
        const body = document.body;
        const keyboardClass = "keyboard-open";
        const isMobile = () => window.matchMedia("(max-width: 576px)").matches;
        const isField = (el) =>
            !!el &&
            (el.tagName === "INPUT" || el.tagName === "TEXTAREA" || el.tagName === "SELECT");

        let blurTimer = null;
        let maxViewportHeight = window.visualViewport ? window.visualViewport.height : window.innerHeight;

        const setKeyboardState = (open) => {
            if (!isMobile()) {
                body.classList.remove(keyboardClass);
                return;
            }

            body.classList.toggle(keyboardClass, open);
        };

        document.addEventListener("focusin", (e) => {
            if (!isMobile()) return;
            if (!isField(e.target)) return;

            clearTimeout(blurTimer);
            setKeyboardState(true);
        });

        document.addEventListener("focusout", () => {
            if (!isMobile()) return;

            clearTimeout(blurTimer);
            blurTimer = setTimeout(() => {
                if (!isField(document.activeElement)) {
                    setKeyboardState(false);
                }
            }, 120);
        });

        if (window.visualViewport) {
            const onViewportResize = () => {
                if (!isMobile()) {
                    setKeyboardState(false);
                    return;
                }

                const vv = window.visualViewport;
                maxViewportHeight = Math.max(maxViewportHeight, vv.height);
                const keyboardVisible = (maxViewportHeight - vv.height) > 120;

                if (keyboardVisible) {
                    setKeyboardState(true);
                } else if (!isField(document.activeElement)) {
                    setKeyboardState(false);
                }
            };

            window.visualViewport.addEventListener("resize", onViewportResize);

            window.addEventListener("orientationchange", () => {
                maxViewportHeight = window.visualViewport.height;
                if (!isField(document.activeElement)) {
                    setKeyboardState(false);
                }
            });
        }

        window.addEventListener("resize", () => {
            if (!isMobile()) {
                setKeyboardState(false);
                maxViewportHeight = window.visualViewport ? window.visualViewport.height : window.innerHeight;
            }
        });
    })();
</script>

<?= $this->endSection() ?>