<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm mt-5">
            <div class="card-body p-4">
                <h1 class="h4 text-center mb-4"><?= htmlspecialchars(APP_NAME) ?></h1>

                <?php include __DIR__ . '/../partials/flash_messages.php'; ?>

                <form method="POST" action="<?= BASE_URL ?>/index.php?page=login&action=handleLogin">
                    <div class="mb-3">
                        <label for="username" class="form-label"><?= t('login.username') ?></label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><?= t('login.password') ?></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><?= t('login.submit') ?></button>
                </form>

                <p class="text-center mt-3 mb-0">
                    <a href="<?= BASE_URL ?>/index.php?page=register"><?= t('login.register_link') ?></a>
                </p>
            </div>
        </div>
    </div>
</div>
