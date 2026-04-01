<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../../login.php"); exit(); }
require '../../config/db_connect.php';

$message = "";

// KAPAG GUMAWA NG BAGONG INVOICE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_invoice_btn'])) {
    $student_id = $_POST['student_id'];
    $total_amount = floatval($_POST['total_amount']);
    $penalty = isset($_POST['penalty']) ? floatval($_POST['penalty']) : 0;
    $status = 'Unpaid'; // Default status

    try {
        $stmt = $conn->prepare("INSERT INTO invoices (student_id, total_amount, penalty, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdds", $student_id, $total_amount, $penalty, $status);
        
        if ($stmt->execute()) {
            $message = "<div class='alert-msg alert-success animate-fade-up'>✅ Success! New invoice created successfully.</div>";
        }
    } catch (mysqli_sql_exception $e) {
        $message = "<div class='alert-msg alert-error animate-fade-up'>❌ Error: " . $e->getMessage() . "</div>";
    }
}

// Kukunin ang listahan ng invoices
$result = $conn->query("SELECT invoice_id, student_id, total_amount, penalty, status FROM invoices ORDER BY invoice_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - Finance & Fee System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #FAF9F7; --card-bg: #FFFFFF; --text-primary: #111827; --text-secondary: #6B7280; --border-color: #E5E7EB; --button-dark: #111827; --button-text: #FFFFFF; --nav-bg: rgba(250, 249, 247, 0.95); --hover-bg: #F3F4F6; --shadow-color: rgba(0,0,0,0.05); } 
        [data-theme="dark"] { --bg-color: #111827; --card-bg: #1F2937; --text-primary: #F9FAFB; --text-secondary: #9CA3AF; --border-color: #374151; --button-dark: #F9FAFB; --button-text: #111827; --nav-bg: rgba(17, 24, 39, 0.95); --hover-bg: #374151; --shadow-color: rgba(0,0,0,0.2); } 
        body { margin: 0; background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: var(--text-primary); transition: 0.4s; } 
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .animate-fade-up { opacity: 0; animation: fadeInUp 0.6s forwards; } .delay-1 { animation-delay: 0.1s; } 
        
        .top-navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px); } 
        .nav-left { display: flex; flex-direction: column; } .nav-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; } .nav-subtitle { font-size: 0.85rem; color: var(--text-secondary); margin: 0; } 
        .nav-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: flex-end;} .nav-btn { text-decoration: none; padding: 8px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); cursor: pointer; transition: 0.3s;} .nav-btn.active { background-color: var(--button-dark); color: var(--button-text); border-color: var(--button-dark); } 
        .menu-toggle { display: none; background: none; border: none; font-size: 1.8rem; color: var(--text-primary); cursor: pointer; } 
        
        .container { width: 90%; max-width: 1200px; margin: 40px auto; } 
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; } 
        h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin: 0; }
        
        /* THE MISSING BUTTON STYLE */
        .btn-add { background-color: #3b82f6; color: white; padding: 10px 20px; border-radius: 30px; border: none; font-size: 1rem; cursor: pointer; font-weight: 500; transition: 0.3s;} 
        .btn-add:hover { background-color: #2563eb; transform: translateY(-2px); } 
        
        .table-card { background-color: var(--card-bg); border-radius: 20px; box-shadow: 0 4px 20px var(--shadow-color); padding: 30px; border: 1px solid var(--border-color); overflow-x: auto; } 
        table { width: 100%; border-collapse: collapse; min-width: 600px; } 
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); } 
        th { color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; } 
        tr:hover td { background-color: var(--hover-bg); } .amount { font-weight: 600; color: #10b981; } 
        
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; }
        .status-paid { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-partial { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-unpaid { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        .alert-msg { padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem; } .alert-success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); } .alert-error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
        
        /* MODAL STYLES */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 2000; justify-content: center; align-items: center; } 
        .modal-box { background-color: var(--card-bg); padding: 40px; border-radius: 24px; width: 90%; max-width: 450px; position: relative; border: 1px solid var(--border-color); } 
        .close-btn { position: absolute; top: 20px; right: 25px; font-size: 1.8rem; cursor: pointer; color: var(--text-secondary); } 
        .modal-box h2 { margin-top: 0; font-family: 'Playfair Display', serif; font-size: 1.8rem; } 
        .modal-box label { display: block; margin-top: 15px; font-size: 0.9rem; font-weight: 500; color: var(--text-secondary); } 
        .modal-box input { width: 100%; padding: 12px 15px; margin-top: 8px; border: 1px solid var(--border-color); border-radius: 12px; background-color: var(--bg-color); color: var(--text-primary); font-family: 'Inter', sans-serif; box-sizing: border-box; } 
        .modal-submit { width: 100%; margin-top: 25px; padding: 14px; border-radius: 30px; background: #3b82f6; color: white; border: none; font-weight: bold; cursor: pointer; } 

        /* MOBILE RESPONSIVE MAGIC */
        @media (max-width: 900px) { .menu-toggle { display: block; } .nav-left { width: 80%; } .nav-right { display: none; width: 100%; flex-direction: column; background-color: var(--card-bg); padding: 20px; border-radius: 16px; border: 1px solid var(--border-color); position: absolute; top: 100%; left: 0; right: 0; margin: 10px 5%; } .nav-right.show-menu { display: flex; } .nav-btn { text-align: center; padding: 12px; margin-bottom: 5px; } }
        @media (max-width: 768px) {
            .header-flex { flex-direction: column; align-items: flex-start; gap: 15px; } h1 { font-size: 2rem; } .btn-add { width: 100%; }
            .table-card { background: transparent; padding: 0; box-shadow: none; border: none; } table thead { display: none; }
            table tbody tr { display: block; background-color: var(--card-bg); margin-bottom: 20px; border-radius: 16px; box-shadow: 0 5px 15px var(--shadow-color); border: 1px solid var(--border-color); overflow: hidden; }
            table tbody td { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid var(--border-color); text-align: right; font-size: 0.95rem; }
            table tbody td:last-child { border-bottom: none; background-color: var(--hover-bg); justify-content: flex-end; gap: 10px; }
            table tbody td::before { content: attr(data-label); font-weight: 700; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; margin-right: 15px; text-align: left; }
        }
    </style>
</head>
<body>
    <header class="top-navbar animate-fade-up">
        <div class="nav-left"><h1 class="nav-title">Finance & Fee System</h1><p class="nav-subtitle">Invoice Management Module</p></div>
        <button id="mobile-menu-btn" class="menu-toggle">☰</button>
        <div class="nav-right" id="nav-menu">
            <a href="../../index.php" class="nav-btn">Dashboard</a> <a href="../fees/index.php" class="nav-btn">Fees</a> <a href="index.php" class="nav-btn active">Invoices</a> <a href="../payments/index.php" class="nav-btn">Payments</a> <a href="../ledger/index.php" class="nav-btn">Ledger</a> <a href="../scholarships/index.php" class="nav-btn">Scholarships</a> <a href="../reports/index.php" class="nav-btn">Reports</a> <button id="theme-toggle" class="nav-btn">🌙 Mode</button> <a href="../../logout.php" class="nav-btn" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Logout</a>
        </div>
    </header>

    <div class="container animate-fade-up delay-1">
        
        <div class="header-flex">
            <h1>📑 Student Invoices</h1>
            <button onclick="openModal()" class="btn-add">+ Create Invoice</button>
        </div>
        
        <?php echo $message; ?>

        <div class="table-card">
            <table>
                <thead><tr><th>Invoice ID</th><th>Student ID</th><th>Total Amount</th><th>Penalty</th><th>Grand Total</th><th>Status</th></tr></thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) { 
                            $grand = $row['total_amount'] + $row['penalty'];
                            
                            $status_class = 'status-unpaid';
                            if ($row['status'] == 'Paid') $status_class = 'status-paid';
                            if ($row['status'] == 'Partial') $status_class = 'status-partial';

                            echo "<tr>
                                    <td data-label='Invoice ID'><strong>INV-" . str_pad($row['invoice_id'], 4, '0', STR_PAD_LEFT) . "</strong></td>
                                    <td data-label='Student ID'>" . htmlspecialchars($row['student_id']) . "</td>
                                    <td data-label='Total Amount'>₱ " . number_format($row['total_amount'], 2) . "</td>
                                    <td data-label='Penalty' style='color: #ef4444;'>₱ " . number_format($row['penalty'], 2) . "</td>
                                    <td data-label='Grand Total' class='amount'>₱ " . number_format($grand, 2) . "</td>
                                    <td data-label='Status'><span class='status-badge {$status_class}'>" . htmlspecialchars($row['status']) . "</span></td>
                                  </tr>"; 
                        }
                    } else { echo "<tr><td colspan='6' style='text-align:center; color:var(--text-secondary);'>Walang naka-record na invoices.</td></tr>"; }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="addInvoiceModal" class="modal-overlay">
        <div class="modal-box">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2>Create Invoice</h2>
            <form method="POST" action="">
                <label for="student_id">Student ID / Full Name:</label> 
                <input type="text" id="student_id" name="student_id" required placeholder="e.g. 2026-0001 or Juan Dela Cruz">
                
                <label for="total_amount">Total Amount (₱):</label> 
                <input type="number" id="total_amount" name="total_amount" step="0.01" required placeholder="0.00">
                
                <label for="penalty">Penalty / Surcharge (₱) - Optional:</label> 
                <input type="number" id="penalty" name="penalty" step="0.01" value="0">
                
                <button type="submit" name="add_invoice_btn" class="modal-submit">Save Invoice</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeBtn = document.getElementById('theme-toggle'); if (localStorage.getItem('theme') === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); themeBtn.innerText = '☀️ Mode'; }
            themeBtn.addEventListener('click', () => { if (document.documentElement.getAttribute('data-theme') === 'dark') { document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme', 'light'); themeBtn.innerText = '🌙 Mode'; } else { document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); themeBtn.innerText = '☀️ Mode'; } });
            const mobileBtn = document.getElementById('mobile-menu-btn'); const navMenu = document.getElementById('nav-menu'); if(mobileBtn) { mobileBtn.addEventListener('click', () => { navMenu.classList.toggle('show-menu'); }); }
        });
        
        const modal = document.getElementById('addInvoiceModal'); 
        function openModal() { modal.style.display = 'flex'; } 
        function closeModal() { modal.style.display = 'none'; } 
        window.onclick = function(event) { if (event.target == modal) { closeModal(); } }
    </script>
</body>
</html>