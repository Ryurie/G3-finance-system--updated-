<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../../login.php"); exit(); }
require '../../config/db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['grant_scholarship_btn'])) {
    $invoice_id = intval($_POST['invoice_id']);
    $scholarship_name = $_POST['scholarship_name'];
    $discount_amount = floatval($_POST['discount_amount']);

    try {
        $stmt_grant = $conn->prepare("INSERT INTO student_scholarships (invoice_id, scholarship_name, discount_amount) VALUES (?, ?, ?)");
        $stmt_grant->bind_param("isd", $invoice_id, $scholarship_name, $discount_amount); $stmt_grant->execute();

        $method = "Scholarship ($scholarship_name)"; $ref_no = "GRANT-" . time(); 
        $stmt_pay = $conn->prepare("INSERT INTO payments (invoice_id, amount_paid, payment_method, reference_number) VALUES (?, ?, ?, ?)");
        $stmt_pay->bind_param("idss", $invoice_id, $discount_amount, $method, $ref_no); $stmt_pay->execute();

        $stmt_inv = $conn->prepare("SELECT (total_amount + penalty) as grand_total FROM invoices WHERE invoice_id = ?"); 
        $stmt_inv->bind_param("i", $invoice_id); $stmt_inv->execute(); $grand_total = $stmt_inv->get_result()->fetch_assoc()['grand_total'];
        
        $stmt_sum = $conn->prepare("SELECT SUM(amount_paid) as total_paid FROM payments WHERE invoice_id = ?"); 
        $stmt_sum->bind_param("i", $invoice_id); $stmt_sum->execute(); $total_paid = $stmt_sum->get_result()->fetch_assoc()['total_paid'];
        
        $new_status = ($total_paid >= $grand_total) ? 'Paid' : 'Partial';
        $conn->query("UPDATE invoices SET status = '$new_status' WHERE invoice_id = $invoice_id");

        $admin_name = $_SESSION['username'] ?? 'Admin';
        $desc = "Granted $scholarship_name (₱$discount_amount) to Invoice #$invoice_id.";
        $conn->query("INSERT INTO audit_logs (admin_name, action_type, description) VALUES ('$admin_name', 'Scholarship', '$desc')");

        $message = "<div class='alert-msg alert-success animate-fade-up'>✅ Success! Scholarship applied.</div>";
    } catch (mysqli_sql_exception $e) {
        $message = "<div class='alert-msg alert-error animate-fade-up'>❌ Error: " . $e->getMessage() . "</div>";
    }
}

$result = $conn->query("SELECT s.*, i.student_id FROM student_scholarships s JOIN invoices i ON s.invoice_id = i.invoice_id ORDER BY s.date_granted DESC");
$invoices_list = $conn->query("SELECT invoice_id, student_id, status FROM invoices WHERE status != 'Paid' ORDER BY invoice_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarships - Finance & Fee System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #FAF9F7; --card-bg: #FFFFFF; --text-primary: #111827; --text-secondary: #6B7280; --border-color: #E5E7EB; --button-dark: #111827; --button-text: #FFFFFF; --nav-bg: rgba(250, 249, 247, 0.95); --hover-bg: #F3F4F6; --shadow-color: rgba(0,0,0,0.05); } 
        [data-theme="dark"] { --bg-color: #111827; --card-bg: #1F2937; --text-primary: #F9FAFB; --text-secondary: #9CA3AF; --border-color: #374151; --button-dark: #F9FAFB; --button-text: #111827; --nav-bg: rgba(17, 24, 39, 0.95); --hover-bg: #374151; --shadow-color: rgba(0,0,0,0.2); } 
        body { margin: 0; background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: var(--text-primary); transition: 0.4s; } 
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .animate-fade-up { opacity: 0; animation: fadeInUp 0.6s forwards; } .delay-1 { animation-delay: 0.1s; } 
        .top-navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px); } 
        .nav-left { display: flex; flex-direction: column; } .nav-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; } .nav-subtitle { font-size: 0.85rem; color: var(--text-secondary); margin: 0; } 
        .nav-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: flex-end;} .nav-btn { text-decoration: none; padding: 8px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); cursor: pointer; transition: 0.3s; } .nav-btn.active { background-color: var(--button-dark); color: var(--button-text); border-color: var(--button-dark); } 
        .menu-toggle { display: none; background: none; border: none; font-size: 1.8rem; color: var(--text-primary); cursor: pointer; } 
        .container { width: 90%; max-width: 1200px; margin: 40px auto; } .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; } h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin: 0; } .btn-add { background-color: #3b82f6; color: white; padding: 10px 20px; border-radius: 30px; border: none; font-size: 1rem; cursor: pointer; font-weight: 500; transition: 0.3s;} .btn-add:hover { opacity: 0.8; transform: translateY(-2px); } 
        .table-card { background-color: var(--card-bg); border-radius: 20px; box-shadow: 0 4px 20px var(--shadow-color); padding: 30px; border: 1px solid var(--border-color); transition: 0.4s; overflow-x: auto; } table { width: 100%; border-collapse: collapse; min-width: 600px; } th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); } th { color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; } tr:hover td { background-color: var(--hover-bg); } .amount { font-weight: 600; color: #3b82f6; } 
        .alert-msg { padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem; } .alert-success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); } .alert-error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 2000; justify-content: center; align-items: center; } .modal-box { background-color: var(--card-bg); padding: 40px; border-radius: 24px; width: 90%; max-width: 450px; position: relative; border: 1px solid var(--border-color); } .close-btn { position: absolute; top: 20px; right: 25px; font-size: 1.8rem; cursor: pointer; color: var(--text-secondary); } .modal-box h2 { margin-top: 0; font-family: 'Playfair Display', serif; font-size: 1.8rem; } .modal-box label { display: block; margin-top: 15px; font-size: 0.9rem; font-weight: 500; color: var(--text-secondary); } .modal-box input, .modal-box select { width: 100%; padding: 12px 15px; margin-top: 8px; border: 1px solid var(--border-color); border-radius: 12px; background-color: var(--bg-color); color: var(--text-primary); font-family: 'Inter', sans-serif; box-sizing: border-box; } .modal-submit { width: 100%; margin-top: 25px; padding: 14px; border-radius: 30px; background: #3b82f6; color: white; border: none; font-weight: bold; cursor: pointer; } 
        
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
        <div class="nav-left"><h1 class="nav-title">Finance & Fee System</h1><p class="nav-subtitle">Scholarship Management Module</p></div>
        <button id="mobile-menu-btn" class="menu-toggle">☰</button>
        <div class="nav-right" id="nav-menu">
            <a href="../../index.php" class="nav-btn">Dashboard</a> <a href="../fees/index.php" class="nav-btn">Fees</a> <a href="../invoices/index.php" class="nav-btn">Invoices</a> <a href="../payments/index.php" class="nav-btn">Payments</a> <a href="../ledger/index.php" class="nav-btn">Ledger</a> <a href="index.php" class="nav-btn active">Scholarships</a> <a href="../reports/index.php" class="nav-btn">Reports</a> <button id="theme-toggle" class="nav-btn">🌙 Mode</button> <a href="../../logout.php" class="nav-btn" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Logout</a>
        </div>
    </header>

    <div class="container animate-fade-up delay-1">
        <div class="header-flex"><h1>🎓 Scholarships & Grants</h1><button onclick="openModal()" class="btn-add">+ Grant Scholarship</button></div>
        <?php echo $message; ?>
        <div class="table-card">
            <table>
                <thead><tr><th>Grant ID</th><th>Student ID</th><th>Invoice Ref</th><th>Scholarship Type</th><th>Discount Amount</th><th>Date Granted</th></tr></thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) { 
                            echo "<tr>
                                    <td data-label='Grant ID'><strong>GRNT-" . str_pad($row['grant_id'], 4, '0', STR_PAD_LEFT) . "</strong></td>
                                    <td data-label='Student ID'>" . htmlspecialchars($row['student_id']) . "</td>
                                    <td data-label='Invoice Ref'>INV-" . str_pad($row['invoice_id'], 4, '0', STR_PAD_LEFT) . "</td>
                                    <td data-label='Scholarship Type'>" . htmlspecialchars($row['scholarship_name']) . "</td>
                                    <td data-label='Discount Amount' class='amount'>₱ " . number_format($row['discount_amount'], 2) . "</td>
                                    <td data-label='Date Granted'>" . date("M d, Y", strtotime($row['date_granted'])) . "</td>
                                  </tr>"; 
                        }
                    } else { echo "<tr><td colspan='6' style='text-align:center; color:var(--text-secondary);'>No scholarships granted yet.</td></tr>"; }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="addGrantModal" class="modal-overlay">
        <div class="modal-box">
            <span class="close-btn" onclick="closeModal()">&times;</span><h2>Grant Scholarship</h2>
            <form method="POST" action="">
                <label for="invoice_id">Select Invoice / Student:</label> 
                <select id="invoice_id" name="invoice_id" required>
                    <option value="" disabled selected>Pumili ng Invoice...</option>
                    <?php
                    if ($invoices_list && $invoices_list->num_rows > 0) {
                        while($inv = $invoices_list->fetch_assoc()) {
                            echo "<option value='{$inv['invoice_id']}'>INV-" . str_pad($inv['invoice_id'], 4, '0', STR_PAD_LEFT) . " | Student: {$inv['student_id']} ({$inv['status']})</option>";
                        }
                    }
                    ?>
                </select>
                <label for="scholarship_name">Scholarship Name / Type:</label>
                <input type="text" id="scholarship_name" name="scholarship_name" required placeholder="e.g. LGU Scholar, Varsity, etc.">
                <label for="discount_amount">Discount Amount (₱):</label> 
                <input type="number" id="discount_amount" name="discount_amount" step="0.01" required>
                <button type="submit" name="grant_scholarship_btn" class="modal-submit">Apply Scholarship</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeBtn = document.getElementById('theme-toggle'); if (localStorage.getItem('theme') === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); themeBtn.innerText = '☀️ Mode'; }
            themeBtn.addEventListener('click', () => { if (document.documentElement.getAttribute('data-theme') === 'dark') { document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme', 'light'); themeBtn.innerText = '🌙 Mode'; } else { document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); themeBtn.innerText = '☀️ Mode'; } });
            const mobileBtn = document.getElementById('mobile-menu-btn'); const navMenu = document.getElementById('nav-menu'); if(mobileBtn) { mobileBtn.addEventListener('click', () => { navMenu.classList.toggle('show-menu'); }); }
        });
        const modal = document.getElementById('addGrantModal'); function openModal() { modal.style.display = 'flex'; } function closeModal() { modal.style.display = 'none'; } window.onclick = function(event) { if (event.target == modal) { closeModal(); } }
    </script>
</body>
</html>