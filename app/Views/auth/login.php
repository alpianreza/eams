<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<div class="login-box">
    <div class="login-logo mb-3">
        <b>EAMS</b>
    </div>

    <div class="card shadow-sm">
        <div class="card-body login-card-body">

            <p class="login-box-msg">Silakan login</p>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger py-2">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('login') ?>" method="post">

                <div class="input-group mb-3">
                    <input type="text"
                        name="username"
                        class="form-control"
                        placeholder="Username"
                        required>
                    <span class="input-group-text">
                        <i class="bi bi-person"></i>
                    </span>
                </div>

                <div class="input-group mb-3">
                    <input type="password"
                        name="password"
                        class="form-control"
                        placeholder="Password"
                        required>
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Login
                </button>
            </form>

        </div>
    </div>
</div>

<?= $this->endSection() ?>