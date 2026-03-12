<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<style>
    body {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        min-height: 100vh;

        display: flex;
        align-items: center;
        justify-content: center;

        padding: 20px;
    }

    /* container */
    .login-box {
        width: 380px;
        max-width: 100%;
    }

    /* logo */
    .login-logo {
        text-align: center;
        margin-bottom: 25px;
    }

    .login-logo img {
        width: 120px;
        margin-bottom: 18px;
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

    /* card */
    .card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
    }

    .login-card-body {
        background: white;
        padding: 32px;
        border-radius: 14px;
    }

    /* input */
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

    /* button */
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

    /* ======================
   MOBILE RESPONSIVE
====================== */

    @media (max-width:576px) {

        body {
            padding: 15px;
        }

        .login-box {
            width: 100%;
        }

        .login-card-body {
            padding: 24px;
        }

        .login-logo img {
            width: 120px;
        }

        .login-logo .title {
            font-size: 22px;
        }

    }
</style>

<div class="login-box">

    <div class="login-logo mb-4 text-center">

        <img src="<?= base_url('assets/images/company/logo.png') ?>" alt="logo">
        <div style="font-size:13px;color:#cbd5e1;">
            Enterprise Asset Management System
        </div>

    </div>

</div>

<div class="card shadow-lg border-0">
    <div class="card-body login-card-body">

        <p class="text-center mb-4" style="font-size:14px;">
            Silakan login ke sistem
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

<div class="login-footer">
    © <?= date('Y') ?> PT YOUNGHYUN STAR
</div>


</div>

<?= $this->endSection() ?>