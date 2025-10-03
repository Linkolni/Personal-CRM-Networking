<?php
// templates/_header.php
$username = $_SESSION['username'] ?? 'Gast';
?>
    <header class="p-3" style="background-color: <?= COMPANY_BACKGROUNDCOLOR ?>; color: <?= COMPANY_COLOR ?>;">
        <div class="container-fluid d-flex justify-content-between align-items-center">

            <!-- Linke Kopfzeile: Logo und App-Name -->
            <a href="index.php" class="d-flex align-items-center text-decoration-none"
                style="color: <?= COMPANY_COLOR ?>;">
                <!-- Firmenlogo (fixierte Höhe, Breite automatisch!) -->
                <img src="<?= COMPANY_LOGO ?>" alt="Logo" class="me-2"
                    style="height: 35px; width: auto; max-width: 80px;">
                <!-- Anwendungsname -->
                <span class="h3 mb-0"><?= COMPANY_NAME ?></span>
            </a>

            <!-- Rechte Kopfzeile: Login-Status und Logout-Link, Admins mit Admin-Link -->
            <div>
                <span>Angemeldet als: <?= htmlspecialchars($username) ?></span>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php" class="btn btn-warning btn-sm ms-2">Admin</a>
                <?php endif; ?>

                <a href="profile.php" class="btn btn-sm ms-3"
                    style="border-color: <?= COMPANY_COLOR ?>; color: <?= COMPANY_COLOR ?>;">
                    Profil
                </a>


                <a href="logout.php" class="btn btn-sm ms-3"
                    style="border-color: <?= COMPANY_COLOR ?>; color: <?= COMPANY_COLOR ?>;">
                    Logout
                </a>
            </div>
        </div>
    </header>
