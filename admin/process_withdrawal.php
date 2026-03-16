<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $amount = (float)($_POST['withdrawal_amount'] ?? 0);

    // --- Validation ---

    // 1. Check if it's Friday
    if (date('N') != 4) { // 5 is for Friday
        $_SESSION['withdrawal_error'] = "Withdrawals are only allowed on Fridays.";
        header('Location: wallet.php');
        exit();
    }

    // 2. Check minimum amount
    if ($amount < 2000) {
        $_SESSION['withdrawal_error'] = "Minimum withdrawal amount is ₦2000.";
        header('Location: wallet.php');
        exit();
    }

    // 3. Check sufficient balance
    $previous_balance = get_user_wallet_balance($user_id);
    
    if ($previous_balance < $amount) {
        $_SESSION['withdrawal_error'] = "Insufficient balance. You need ₦" . number_format($amount, 2) . " to withdraw that amount.";
        header('Location: wallet.php');
        exit();
    }

    // --- Process Withdrawal ---
    $pdo->beginTransaction();
    try {
        $reference = 'WTHD-' . strtoupper(uniqid());
        $withdrawal_fee = $amount * 0.10;
        $amount_received = $amount - $withdrawal_fee;

        // 1. Record the withdrawal
        $new_balance = $previous_balance - $amount;
        $stmt_withdraw = $pdo->prepare(
            "INSERT INTO wallet_transactions (user_id, transaction_type, amount, previous_balance, new_balance, reference, description, status) 
             VALUES (?, 'withdrawal', ?, ?, ?, ?, ?, 'completed')"
        );
        $description = "Withdrawal of ₦" . number_format($amount, 2) . ". Fee: ₦" . number_format($withdrawal_fee, 2) . ". Amount received: ₦" . number_format($amount_received, 2);
        $stmt_withdraw->execute([$user_id, $amount, $previous_balance, $new_balance, $reference, $description]);

        // 2. Update the main wallet balance
        $stmt_update = $pdo->prepare("UPDATE user_wallet SET balance = ? WHERE user_id = ?");
        $stmt_update->execute([$new_balance, $user_id]);

        $pdo->commit();

        $_SESSION['withdrawal_success'] = "Your withdrawal request of ₦" . number_format($amount, 2) . " has been submitted successfully. You will receive ₦" . number_format($amount_received, 2) . ".";

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['withdrawal_error'] = "An error occurred while processing your request. Please try again.";
        error_log($e->getMessage()); // Log the actual error
    }

} else {
    // Not a POST request
    $_SESSION['withdrawal_error'] = "Invalid request.";
}

header('Location: wallet.php');
exit();