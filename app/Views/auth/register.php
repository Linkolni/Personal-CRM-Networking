<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm mt-5">
            <div class="card-body p-4">
                <h1 class="h4 text-center mb-4"><?= t('register.title') ?></h1>

                <?php include __DIR__ . '/../partials/flash_messages.php'; ?>

                <form method="POST" action="<?= BASE_URL ?>/index.php?page=register&action=handleRegister">
                    <div class="mb-3">
                        <label for="username" class="form-label"><?= t('register.username') ?></label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><?= t('register.password') ?></label>
                        <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label"><?= t('register.password_confirm') ?></label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" minlength="8" required>
                    </div>
                    <div class="mb-3">
                        <label for="captcha_answer" class="form-label">
                            <?= t('register.captcha_label') ?>: <?= htmlspecialchars($captchaQuestion) ?>
                        </label>
                        <input type="number" class="form-control" id="captcha_answer" name="captcha_answer" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><?= t('register.submit') ?></button>
                </form>

                <p class="text-center mt-3 mb-0">
                    <a href="<?= BASE_URL ?>/index.php?page=login"><?= t('register.login_link') ?></a>
                </p>
            </div>
        </div>
    </div>
</div>
