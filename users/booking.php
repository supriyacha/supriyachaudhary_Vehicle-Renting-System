<?php

session_start();
include("db.php");

/* Check login */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];


/* Check whether logged-in user exists in users table */
$user_check = mysqli_prepare(
    $conn,
    "SELECT id FROM users WHERE id = ?"
);

if (!$user_check) {
    die("Database Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $user_check,
    "i",
    $user_id
);

mysqli_stmt_execute($user_check);

$user_result = mysqli_stmt_get_result($user_check);

if (mysqli_num_rows($user_result) == 0) {

    // Invalid old session
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}


/* Check vehicle ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: vehicles.php");
    exit();
}

$vehicle_id = (int) $_GET['id'];


/* Get vehicle */
$sql = "SELECT * FROM vehicles WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $vehicle_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$vehicle = mysqli_fetch_assoc($result);

if (!$vehicle) {
    die("Vehicle not found.");
}


/* Booking process */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $booking_date = $_POST['booking_date'] ?? '';
    $return_date = $_POST['return_date'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';

    /* Check empty fields */
    if (
        empty($booking_date) ||
        empty($return_date) ||
        empty($payment_method)
    ) {

        echo "<script>
                alert('Please fill all required fields.');
              </script>";

    } else {

        $start = strtotime($booking_date);
        $end = strtotime($return_date);

        /* Check valid dates */
        if ($start === false || $end === false) {

            echo "<script>
                    alert('Invalid date.');
                  </script>";

        } else {

            /* Check past booking date */
            $today = strtotime(date("Y-m-d"));

            if ($start < $today) {

                echo "<script>
                        alert('Booking date cannot be in the past.');
                      </script>";

            } else {

                /* Calculate rental days */
                $days = ($end - $start) / (60 * 60 * 24);

                if ($days <= 0) {

                    echo "<script>
                            alert('Return date must be after booking date.');
                          </script>";

                } else {

                    /* Calculate total */
                    $total_amount =
                        $days * (float) $vehicle['price'];


                    /*
                     * Check whether required columns exist:
                     * payment_status and status
                     */

                    $insert = "INSERT INTO booking
                    (
                        user_id,
                        vehicle_id,
                        booking_date,
                        return_date,
                        total_amount,
                        payment_method,
                        payment_status,
                        status
                    )
                    VALUES
                    (?, ?, ?, ?, ?, ?, 'Pending', 'Pending')";


                    $insert_stmt =
                        mysqli_prepare($conn, $insert);


                    if (!$insert_stmt) {

                        die(
                            "Booking Database Error: " .
                            mysqli_error($conn)
                        );
                    }


                    mysqli_stmt_bind_param(
                        $insert_stmt,
                        "iissds",
                        $user_id,
                        $vehicle_id,
                        $booking_date,
                        $return_date,
                        $total_amount,
                        $payment_method
                    );


                    if (
                        mysqli_stmt_execute(
                            $insert_stmt
                        )
                    ) {

                        $booking_id =
                            mysqli_insert_id($conn);

                        echo "<script>
                                alert('Booking Successful!');
                                window.location='my_bookings.php';
                              </script>";

                        exit();

                    } else {

                        echo "Booking Error: " .
                             mysqli_stmt_error(
                                 $insert_stmt
                             );
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Vehicle</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            margin: 0;
            padding: 0;
        }

        .booking-container {
            width: 450px;
            max-width: 90%;
            margin: 40px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px gray;
        }

        img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 10px;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            background: green;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background: darkgreen;
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <h2>Book Vehicle</h2>
        <img src="../assets/images/<?php echo htmlspecialchars($vehicle['image']); ?>" alt="Vehicle">
        <h3>
            <?php
            echo htmlspecialchars(
                $vehicle['vehicle_name']
            );
            ?>
        </h3>
        <p>
            <strong>Brand:</strong>
            <?php
            echo htmlspecialchars(
                $vehicle['brand']
            );
            ?>
        </p>
        <p>
            <strong>Model:</strong>
            <?php
            echo htmlspecialchars(
                $vehicle['model']
            );
            ?>
        </p>
        <p>
            <strong>Price:</strong>
            Rs.
            <?php
            echo number_format(
                $vehicle['price'],
                2
            );
            ?>
            / Day
        </p>
        <form method="POST" onsubmit="return validateBooking();">
            <label>
                Booking Date
            </label>
            <input type="date" id="booking_date" name="booking_date" required>
            <label>
                Return Date
            </label>
            <input type="date" id="return_date" name="return_date" required>
            <label>
                Payment Method
            </label>
            <select id="payment_method" name="payment_method" required>
                <option value="">
                    Select Payment Method
                </option>
                <option value="eSewa">
                    eSewa
                </option>
            </select>
            <p>
                <strong>Total Days:</strong>
                <span id="total_days">0</span>
            </p>
            <p>
                <strong>Total Amount:</strong>
                Rs.
                <span id="total_amount">0.00</span>
            </p>
            <button type="submit" name="book">
                Confirm Booking
            </button>
        </form>
    </div>
    <script>
    const price =
        <?php echo (float)$vehicle['price']; ?>;
    const bookingDate =
        document.getElementById("booking_date");
    const returnDate =
        document.getElementById("return_date");
    const totalDays =
        document.getElementById("total_days");
    const totalAmount =
        document.getElementById("total_amount");
    function calculateTotal() {
        if (
            bookingDate.value &&
            returnDate.value
        ) {
            const start =
                new Date(bookingDate.value);
            const end =
                new Date(returnDate.value);
            const difference =
                end - start;
            const days =
                difference /
                (1000 * 60 * 60 * 24);
            if (days > 0) {
                totalDays.textContent = days;
                totalAmount.textContent =
                    (days * price).toFixed(2);
            } else {
                totalDays.textContent = "0";
                totalAmount.textContent = "0.00";
            }
        }
    }
    bookingDate.addEventListener(
        "change",
        calculateTotal
    );
    returnDate.addEventListener(
        "change",
        calculateTotal
    );
    function validateBooking() {
        if (
            !bookingDate.value ||
            !returnDate.value
        ) {
            alert(
                "Please select both dates."
            );
            return false;
        }
        const today = new Date();
        today.setHours(
            0,
            0,
            0,
            0
        );
        const start =
            new Date(
                bookingDate.value
            );
        const end =
            new Date(
                returnDate.value
            );
        if (start < today) {
            alert(
                "Booking date cannot be in the past."
            );
            return false;
        }
        if (end <= start) {
            alert(
                "Return date must be after booking date."
            );
            return false;
        }
        return true;
    }
    </script>
</body>
</html>