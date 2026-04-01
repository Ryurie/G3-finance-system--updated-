<?php
// I-connect ang database mo dito (Palitan ng tamang file name kung iba ang gamit niyo)
 include 'db_connect.php'; 

// DUMMY CONNECTION PARA HINDI MAG-ERROR KUNG WALA PA (Burahin ito kung may db_connect.php na kayo)
$conn = new mysqli("localhost", "root", "", "finance_db");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | Finance & Fee Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="top-header">
        <div class="header-titles">
            <span class="portal-badge">Finance & Fee Portal</span>
            <h1 class="main-title">Finance & Fee Management System</h1>
            <span class="subtitle">Welcome, admin (Admin)</span>
        </div>
        
        <button class="hamburger-btn" id="menu-toggle">☰</button>

        <nav class="mobile-menu" id="dropdown-menu">
            <a href="index.php">Dashboard</a>
            <a href="fees.php">Fees</a>
            <a href="invoices.php">Invoices</a>
            <a href="payments.php">Payments</a>
            <a href="ledger.php">Ledger</a>
            <a href="scholarships.php">Scholarships</a>
            <a href="reports.php">Reports</a>
            <a href="audit_logs.php" class="active-link">Audit Logs</a> </nav>
    </header>

    <div class="container">
        <div class="header-flex">
            <h2>System Audit Logs</h2>
            <button class="btn btn-back" onclick="window.location.href='index.php'">Back to Dashboard</button>
        </div>
        
        <p style="margin-bottom: 20px; color: #64748b;">Makikita dito ang lahat ng security at transaction records ng system.</p>

        <table>
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Date & Time</th>
                    <th>Admin User</th>
                    <th>Action Type</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Kukunin natin ang pinakabagong logs mula sa database
                $sql = "SELECT * FROM audit_logs ORDER BY date_created DESC";
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Formatting ng petsa para mas magandang basahin
                        $formatted_date = date("M d, Y - h:i A", strtotime($row['date_created']));
                        
                        // Pag-iiba ng kulay depende sa Action Type
                        $badge_class = "status-partial"; // Default (Yellow)
                        if ($row['action_type'] == 'Payment') { $badge_class = 'status-paid'; } // Green
                        if ($row['action_type'] == 'Delete' || $row['action_type'] == 'Reject') { $badge_class = 'status-unpaid'; } // Red

                        echo "<tr>
                                <td>#{$row['log_id']}</td>
                                <td style='color: #64748b; font-size: 14px;'>{$formatted_date}</td>
                                <td><b>{$row['admin_name']}</b></td>
                                <td><span class='status {$badge_class}'>{$row['action_type']}</span></td>
                                <td>{$row['description']}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align: center; color: #94a3b8;'>No system logs found yet.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        const menuToggle = document.getElementById('menu-toggle');
        const dropdownMenu = document.getElementById('dropdown-menu');

        menuToggle.addEventListener('click', () => {
            dropdownMenu.classList.toggle('active');
        });
    </script>

</body>
</html>