<?php
require_once '../db/config.php';

if (isset($_GET['reference'])) {
    $reference = $_GET['reference'];
    $secret_key = 'sk_test_05874f80c16f499e989b25e1cbc1c0eb7e98a6a0'; // Replace with your secret key

    $curl = curl_init();
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $secret_key,
        "Cache-Control: no-cache",
      ),
    ));
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) {
      // handle error
      header('Location: analytics_request.php?payment=failed');
      exit();
    }
    
    $result = json_decode($response);
    if ($result->data->status == 'success') {
        $order_id = $result->data->metadata->order_id;
        $user_id = $result->data->metadata->user_id;
        $amount = $result->data->amount / 100;
        $currency = $result->data->currency;
        $status = $result->data->status;

        // Update order status
        $order_stmt = $pdo->prepare("UPDATE orders SET payment_status = 'completed', status = 'processing' WHERE id = ? AND user_id = ?");
        $order_stmt->execute([$order_id, $user_id]);

        // Insert into payments table
        $payment_stmt = $pdo->prepare("INSERT INTO payments (user_id, order_id, reference, amount, currency, status) VALUES (?, ?, ?, ?, ?, ?)");
        $payment_stmt->execute([$user_id, $order_id, $reference, $amount, $currency, $status]);
        
        header('Location: analytics_request.php?payment=success');
        exit();
    } else {
        header('Location: analytics_request.php?payment=failed');
        exit();
    }
} else {
    header('Location: analytics_request.php');
    exit();
}
?>