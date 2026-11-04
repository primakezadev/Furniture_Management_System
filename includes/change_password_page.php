<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $message = 'Your session could not be verified. Refresh the page and try again.';
        $messageType = 'error';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $message = 'Please complete all three password fields.';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Your new password and confirmation do not match.';
            $messageType = 'error';
        } elseif (strlen($newPassword) < 8 || !preg_match('/[0-9]/', $newPassword) || !preg_match('/[^a-zA-Z0-9]/', $newPassword)) {
            $message = 'Use at least 8 characters, including a number and a symbol.';
            $messageType = 'error';
        } else {
            try {
                $select = $conn->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
                $select->execute([':id' => $_SESSION['user_id']]);
                $account = $select->fetch(PDO::FETCH_ASSOC);

                if (!$account || !password_verify($currentPassword, $account['password'])) {
                    $message = 'Your current password is incorrect.';
                    $messageType = 'error';
                } elseif (password_verify($newPassword, $account['password'])) {
                    $message = 'Your new password must be different from your current password.';
                    $messageType = 'error';
                } else {
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $update = $conn->prepare('UPDATE users SET password = :password WHERE id = :id');
                    $update->execute([
                        ':password' => $newPasswordHash,
                        ':id' => $_SESSION['user_id']
                    ]);

                    $message = 'Your password was updated successfully.';
                    $messageType = 'success';
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }
            } catch (PDOException $exception) {
                error_log('Change password database error: ' . $exception->getMessage());
                $message = 'We could not update your password. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

$csrfToken = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
$escapedMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
$escapedTitle = htmlspecialchars($pageTitle ?? 'Change password', ENT_QUOTES, 'UTF-8');
$escapedAccountType = htmlspecialchars($accountType ?? 'user', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Securely update your furniture store account password.">
    <title><?= $escapedTitle ?> | Furniture Workspace</title>
    <link rel="stylesheet" href="../css/change_password.css">
    <script src="../js/change_password.js" defer></script>
</head>
<body>
    <main class="password-layout">
        <section class="story-panel" aria-label="Furniture inspiration">
    
        </section>

        <section class="form-panel">
            <div class="form-content">
                <a class="mobile-brand" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Furniture Workspace</a>
                <div class="intro">
                    <p class="small-label">ACCOUNT SETTINGS · <?= strtoupper($escapedAccountType) ?></p>
                    <h2>Change your password</h2>
                    <p class="intro-text">Change your password to keep your account protected. This works for customer and administrator accounts.</p>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>" role="status" aria-live="polite">
                        <?php if ($messageType === 'success'): ?>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9.25"></circle><path d="m8 12.2 2.6 2.6 5.5-5.7"></path></svg>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9.25"></circle><path d="M12 7.5v5.7M12 16.7h.01"></path></svg>
                        <?php endif; ?>
                        <span><?= $escapedMessage ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="password-form" id="changePasswordForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="field-group">
                        <label for="current_password">Current password</label>
                        <div class="password-input-wrap">
                            <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                            <button class="visibility-toggle" type="button" data-toggle="current_password" aria-label="Show current password" aria-pressed="false">
                                <svg class="eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.7"></circle><path class="eye-slash" d="M3 3 21 21"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="new_password">New password</label>
                        <div class="password-input-wrap">
                            <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required aria-describedby="password-help">
                            <button class="visibility-toggle" type="button" data-toggle="new_password" aria-label="Show new password" aria-pressed="false">
                                <svg class="eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.7"></circle><path class="eye-slash" d="M3 3 21 21"></path></svg>
                            </button>
                        </div>
                        <p class="field-help" id="password-help">Use 8+ characters, including a number and a symbol.</p>
                    </div>

                    <div class="field-group confirm-group">
                        <label for="confirm_password">Confirm new password</label>
                        <div class="password-input-wrap">
                            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" minlength="8" required aria-describedby="confirm-help">
                            <button class="visibility-toggle" type="button" data-toggle="confirm_password" aria-label="Show confirmation password" aria-pressed="false">
                                <svg class="eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.7"></circle><path class="eye-slash" d="M3 3 21 21"></path></svg>
                            </button>
                        </div>
                        <p class="field-help confirm-help" id="confirm-help" aria-live="polite"></p>
                    </div>

                    <div class="strength-area" aria-live="polite">
                        <div class="strength-bars" aria-hidden="true">
                            <span class="strength-bar"></span>
                            <span class="strength-bar"></span>
                            <span class="strength-bar"></span>
                            <span class="strength-bar"></span>
                        </div>
                        <p class="strength-text" id="strengthText">
                            <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m3.5 10.2 4.1 4.1 8.9-9"></path></svg>
                            <span>Strong password · Different from your current password</span>
                        </p>
                    </div>

                    <button class="submit-button" type="submit">Update password</button>
                </form>

                <a class="return-link" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">
                    <span>Return to my workspace</span>
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M7.5 4.5 13 10l-5.5 5.5"></path></svg>
                </a>
                <p class="security-note">Signed in as <?= htmlspecialchars($_SESSION['name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </section>
    </main>
</body>
</html>
