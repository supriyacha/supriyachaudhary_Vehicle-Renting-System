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

/* Get booking */
$sql = "SELECT 
            booking.id,
            booking.user_id,
            booking.total_amount,
            booking.payment_status,
            vehicles.vehicle_name
        FROM booking
        INNER JOIN vehicles 
        ON booking.vehicle_id = vehicles.id
        WHERE booking.id = ?
        AND booking.user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "ii", $booking_id, $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    die("Booking not found.");
}

if ($booking['payment_status'] == 'Paid') {
    die("This booking has already been paid.");
}

/* Amount */
$amount = (float)$booking['total_amount'];
if($amount <=0){
    die("Invalid booking amount: Rs. " . $amount);
}
$tax_amount = "0";
$product_service_charge = "500";
$product_delivery_charge = "0";

$total_amount = $amount 
                + $tax_amount 
                + $product_service_charge 
                + $product_delivery_charge;

/* Format for eSewa */
$amount = number_format($amount, 2, '.', '');
$tax_amount = number_format($tax_amount, 2, '.', '');
$product_service_charge = number_format($product_service_charge, 2, '.', '');
$product_delivery_charge = number_format($product_delivery_charge, 2, '.', '');
$total_amount = number_format($total_amount, 2, '.', '');

/* eSewa details */
$product_code = "EPAYTEST";

/*
   Unique transaction ID
*/
$transaction_uuid = "BOOK-" . $booking_id . "-" . time();

/* Signature */
$signed_field_names = "total_amount,transaction_uuid,product_code";

$message = "total_amount=" . $total_amount .
           ",transaction_uuid=" . $transaction_uuid .
           ",product_code=" . $product_code;

$secret_key = "8gBm/:&EnhH.1/q";

$hash = hash_hmac(
    'sha256',
    $message,
    $secret_key,
    true
);

$signature = base64_encode($hash);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirecting to eSewa</title>
</head>
<body>
    <h3 style="text-align:center;">
        Redirecting to eSewa...
    </h3>
    <form id="esewaForm" action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST">
        <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
        <input type="hidden" name="tax_amount" value="<?php echo htmlspecialchars($tax_amount); ?>">
        <input type="hidden" name="total_amount" value="<?php echo htmlspecialchars($total_amount); ?>">
        <input type="hidden" name="transaction_uuid" value="<?php echo htmlspecialchars($transaction_uuid); ?>">
        <input type="hidden" name="product_code" value="<?php echo htmlspecialchars($product_code); ?>">
        <input type="hidden" name="product_service_charge" value="<?php echo htmlspecialchars($product_service_charge); ?>">
        <input type="hidden" name="product_delivery_charge" value="<?php echo htmlspecialchars($product_delivery_charge); ?>">
        <input type="hidden" name="success_url" value="http://localhost/vehicle_Rental/users/esewa_success.php?booking_id=<?php echo $booking_id; ?>">
        <input type="hidden" name="failure_url" value="http://localhost/vehicle_Rental/users/esewa_failure.php?booking_id=<?php echo $booking_id; ?>">
        <input type="hidden" name="signed_field_names" value="<?php echo $signed_field_names; ?>">
        <input type="hidden" name="signature" value="<?php echo $signature; ?>">
    </form>
    <script>
        document.getElementById("esewaForm").submit();
    </script>
</body>
</html>