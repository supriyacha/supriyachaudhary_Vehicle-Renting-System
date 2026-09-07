<?php

session_start();
include("db.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's bookings
$sql = "SELECT 
        booking.id,
        vehicles.vehicle_name,
        booking.booking_date,
        booking.return_date,
        booking.total_amount,
        booking.payment_method,
        booking.status
        FROM booking
        INNER JOIN vehicles
        ON booking.vehicle_id = vehicles.id
        WHERE booking.user_id = ?
        ORDER BY booking.id DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            margin: 0;
            padding: 0;
        }

        header {
            background: #333;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h2 {
            margin: 0;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        h1 {
            text-align: center;
            margin-top: 30px;
        }

        .table-container {
            width: 90%;
            margin: 40px auto;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        table,
        th,
        td {
            border: 1px solid gray;
        }

        th,td {
            padding: 12px;
            text-align: center;
        }

        th {
            background: #28a745;
            color: white;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        .no-booking {
            text-align: center;
            padding: 20px;
        }

    </style>

</head>

<body>
    <header>

        <h2>
            Vehicle Renting System (2-Wheelers)
        </h2>

        <nav>
            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="vehicles.php">
                Vehicles
            </a>

            <a href="logout.php" onclick="return confirmLogout();">
                Logout
            </a>

        </nav>

    </header>

    <h1>
        My Bookings
    </h1>

    <div class="table-container">
        <table>
            <tr>
                <th>
                    Vehicle
                </th>

                <th>
                    Booking Date
                </th>

                <th>
                    Return Date
                </th>

                <th>
                    Total Amount
                </th>

                <th>
                    Payment Method
                </th>

                <th>
                    Status
                </th>
            </tr>

            <?php
            // Check if bookings exist
            if (mysqli_num_rows($result) > 0) {
                // Start while loop
                while ($row = mysqli_fetch_assoc($result)) {
            ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($row['vehicle_name']);?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row['booking_date']);?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row['return_date']);?>
                        </td>

                        <td>
                            Rs.<?php echo number_format($row['total_amount'], 2);?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row['payment_method']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row['status']);?>
                        </td>
                    </tr>
            <?php
                } // End while loop
            } else {
            ?>
                <tr>
                    <td
                        colspan="5" class="no-booking">
                        You have no bookings yet.
                    </td>
                </tr>
            <?php
            }
            ?>
        </table>
    </div>
    <script src="../assets/js/project.js"></script>
    <script>
        function confirmLogout() {
            return confirm(
                "Are you sure you want to logout?"
            );

        }
    </script>
</body>
</html>