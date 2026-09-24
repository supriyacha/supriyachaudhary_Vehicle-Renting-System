<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header("Location: my_bookings.php");
    exit();
}

$booking_id = (int) $_GET['booking_id'];

/*
   eSewa sends a Base64 encoded response.
*/
$encoded_response = $_GET['data'] ?? '';

$payment_verified = false;

if (!empty($encoded_response)) {

    $decoded_response = base64_decode($encoded_response, true);

    if ($decoded_response !== false) {

        $response = json_decode($decoded_response, true);

        if (is_array($response)) {

            $status = $response['status'] ?? '';
            $transaction_uuid = $response['transaction_uuid'] ?? '';
            $total_amount = $response['total_amount'] ?? '';
            $product_code = $response['product_code'] ?? '';
            $received_signature = $response['signature'] ?? '';

            /*
               Check basic payment information
            */
            if (
                $status === 'COMPLETE' &&
                $product_code === 'EPAYTEST'
            ) {

                /*
                   Verify response signature
                */
                $signed_field_names =
                    $response['signed_field_names'] ?? '';

                $fields = explode(
                    ',',
                    $signed_field_names
                );

                $message_parts = [];

                foreach ($fields as $field) {

                    if (isset($response[$field])) {

                        $message_parts[] =
                            $field . '=' . $response[$field];
                    }
                }

                $message = implode(
                    ',',
                    $message_parts
                );

                /*
                   eSewa UAT secret key
                */
                $secret_key = "8gBm/:&EnhH.1/q";

                $hash = hash_hmac(
                    'sha256',
                    $message,
                    $secret_key,
                    true
                );

                $generated_signature =
                    base64_encode($hash);

                if (
                    hash_equals(
                        $generated_signature,
                        $received_signature
                    )
                ) {
                    $payment_verified = true;
                }
            }
        }
    }
}

/*
   Update database only after verification
*/
if ($payment_verified) {

    $sql = "UPDATE booking
            SET payment_status = 'Paid'
            WHERE id = ?
            AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $booking_id,
            $user_id
        );

        mysqli_stmt_execute($stmt);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>
        <?php
        echo $payment_verified
            ? "Payment Successful"
            : "Payment Verification Failed";
        ?>
    </title>
</head>
<body>
    <div style="
        width:450px;
        margin:100px auto;
        text-align:center;
        padding:30px;
        border:1px solid #ccc;
        border-radius:10px;
    ">
    <?php if ($payment_verified) { ?>
        <h2>Payment Successful</h2>
        <p>
            Your eSewa payment has been completed successfully.
        </p>
        <p>
            Payment Status:
            <strong>Paid</strong>
        </p>
    <?php } else { ?>
        <h2>Payment Verification Failed</h2>
        <p>
            Your payment could not be verified.
        </p>
        <p>
            Please try again.
        </p>
    <?php } ?>
        <br>
        <a href="my_bookings.php">
            Go to My Bookings
        </a>
    </div>
</body>
</html>