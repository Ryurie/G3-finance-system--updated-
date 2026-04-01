<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../../login.php"); exit(); }
require '../../config/db_connect.php';
$student_id = ""; $clearance_status = ""; $invoices_result = null; $payments_result = null;
if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);
    $stmt_clearance = $conn->prepare("SELECT COUNT(*) as pending FROM invoices WHERE student_id = ? AND status != 'Paid'"); $stmt_clearance->bind_param("i", $student_id); $stmt_clearance->execute(); $row_clearance = $stmt_clearance->get_result()->fetch_assoc();
    if ($row_clearance['pending'] > 0) { $clearance_status = "<span class='badge-blocked'>❌ BLOCKED / NOT CLEARED</span>"; } else { $clearance_status = "<span class='badge-cleared'>✅ CLEARED (Fully Paid)</span>"; }
    $stmt_clearance->close();
    $stmt_inv = $conn->prepare("SELECT * FROM invoices WHERE student_id = ? ORDER BY due_date DESC"); $stmt_inv->bind_param("i", $student_id); $stmt_inv->execute(); $invoices_result = $stmt_inv->get_result();
    $stmt_pay = $conn->prepare("SELECT p.*, i.invoice_id FROM payments p JOIN invoices i ON p.invoice_id = i.invoice_id WHERE i.student_id = ? ORDER BY p.payment_date DESC"); $stmt_pay->bind_param("i", $student_id); $stmt_pay->execute(); $payments_result = $stmt_pay->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Ledger - Group 3</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #FAF9F7; --card-bg: #FFFFFF; --text-primary: #111827; --text-secondary: #6B7280; --border-color: #E5E7EB; --button-dark: #111827; --button-text: #FFFFFF; --nav-bg: #FAF9F7; --hover-bg: #F3F4F6; --shadow-color: rgba(0,0,0,0.05); } 
        [data-theme="dark"] { --bg-color: #111827; --card-bg: #1F2937; --text-primary: #F9FAFB; --text-secondary: #9CA3AF; --border-color: #374151; --button-dark: #F9FAFB; --button-text: #111827; --nav-bg: #111827; --hover-bg: #374151; --shadow-color: rgba(0,0,0,0.2); } 
        body { margin: 0; background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: var(--text-primary); transition: 0.4s; } 
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .animate-fade-up { opacity: 0; animation: fadeInUp 0.6s forwards; } 
        
        .top-navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); transition: 0.4s; flex-wrap: wrap; gap: 15px; position: relative;} 
        .nav-left { display: flex; flex-direction: column; flex: 1 1 auto; } .nav-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; } .nav-subtitle { font-size: 0.85rem; color: var(--text-secondary); margin: 0; } 
        .nav-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: flex-end; flex: 2 1 auto; transition: 0.3s;} .nav-btn { text-decoration: none; padding: 8px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); cursor: pointer; transition: 0.3s; white-space: nowrap; } .nav-btn.active { background-color: var(--button-dark); color: var(--button-text); border-color: var(--button-dark); } .nav-btn:hover:not(.active) { background-color: var(--hover-bg); transform: translateY(-2px); }
        .menu-toggle { display: none; background: none; border: none; font-size: 1.8rem; color: var(--text-primary); cursor: pointer; transition: 0.3s; } .menu-toggle:hover { opacity: 0.7; }
        
        .container { width: 90%; max-width: 1000px; margin: 40px auto; } h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin: 0 0 20px 0; } 
        .search-box { display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap;} .search-input { flex: 1; padding: 15px 20px; font-size: 1rem; border-radius: 30px; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); outline: none; transition: 0.3s; min-width: 200px;} .search-input:focus { border-color: var(--text-primary); box-shadow: 0 0 0 3px rgba(17,24,39,0.1); } .btn-search { background-color: var(--button-dark); color: var(--button-text); padding: 15px 30px; border-radius: 30px; border: none; font-size: 1rem; cursor: pointer; font-weight: 600; transition: 0.3s; } .btn-search:hover { opacity: 0.8; transform: translateY(-2px); }
        .result-card { background-color: var(--card-bg); border-radius: 20px; box-shadow: 0 4px 20px var(--shadow-color); padding: 40px; border: 1px solid var(--border-color); margin-bottom: 30px; transition: 0.4s; } .status-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; } .student-title { font-size: 1.5rem; font-weight: 700; margin: 0; }
        .badge-cleared { background-color: rgba(16, 185, 129, 0.15); color: #10b981; padding: 10px 20px; border-radius: 30px; font-weight: 700; font-size: 1rem; border: 1px solid rgba(16,185,129,0.3); display: inline-block;} .badge-blocked { background-color: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 10px 20px; border-radius: 30px; font-weight: 700; font-size: 1rem; border: 1px solid rgba(239,68,68,0.3); display: inline-block;}
        
        .table-container { overflow-x: auto; }
        .table-container::-webkit-scrollbar { height: 8px; } .table-container::-webkit-scrollbar-track { background: transparent; } .table-container::-webkit-scrollbar-thumb { background: var(--text-secondary); border-radius: 10px; border: 2px solid var(--card-bg); } .table-container::-webkit-scrollbar-thumb:hover { background: var(--text-primary); }

        h3 { font-size: 1.2rem; margin-top: 30px; margin-bottom: 15px; color: var(--text-primary); } table { width: 100%; border-collapse: collapse; margin-bottom: 20px; min-width: 500px; } th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 0.95rem; } th { color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; background-color: var(--bg-color); } .amount { font-weight: 600; color: #10b981; } .status { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; } .status-paid { background-color: rgba(16,185,129,0.2); color: #10b981; } .status-unpaid { background-color: rgba(239,68,68,0.2); color: #ef4444; } .status-partial { background-color: rgba(245,158,11,0.2); color: #f59e0b; }
        
        @media (max-width: 900px) { .menu-toggle { display: block; } .nav-left { flex: none; width: 80%; } .nav-right { display: none; width: 100%; flex-direction: column; background-color: var(--card-bg); padding: 20px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 10px 25px var(--shadow-color); margin-top: 10px; align-items: stretch; } .nav-right.show-menu { display: flex; animation: popDown 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; } @keyframes popDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } } .nav-btn { text-align: center; padding: 12px; font-size: 1rem; } }
        @media (max-width: 768px) { .result-card { padding: 20px; } th, td { padding: 10px 8px; font-size: 0.85rem; } }
    </style>
</head>
<body>
    <header class="top-navbar animate-fade-up">
        <div class="nav-left"><h1 class="nav-title">Finance & Fee System</h1><p class="nav-subtitle">Ledger & Clearance Checker</p></div>
        <button id="mobile-menu-btn" class="menu-toggle">☰</button>
        <div class="nav-right" id="nav-menu">
            <a href="../../index.php" class="nav-btn">Dashboard</a> <a href="../fees/index.php" class="nav-btn">Fees</a> <a href="../invoices/index.php" class="nav-btn">Invoices</a> <a href="../payments/index.php" class="nav-btn">Payments</a> <a href="index.php" class="nav-btn active">Ledger</a> <a href="../scholarships/index.php" class="nav-btn">Scholarships</a> <button id="theme-toggle" class="nav-btn">🌙 Mode</button> <a href="../../logout.php" class="nav-btn" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Logout</a>
        </div>
    </header>

    <div class="container animate-fade-up">
        <h1>🔍 Check Clearance & Ledger</h1>
        <form method="GET" action="" class="search-box">
            <input type="number" name="student_id" class="search-input" placeholder="Enter Student ID (e.g. 2026001)" value="<?php echo htmlspecialchars($student_id); ?>" required>
            <button type="submit" class="btn-search">Search Student</button>
        </form>
        <?php if ($student_id != ""): ?>
            <div class="result-card animate-fade-up">
                <div class="status-header">
                    <div><p style="margin:0; color:var(--text-secondary); font-size: 0.9rem;">Student ID:</p><h2 class="student-title"><?php echo htmlspecialchars($student_id); ?></h2></div>
                    <div><?php echo $clearance_status; ?></div>
                </div>
                <h3>📄 Statement of Account (Invoices)</h3>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Invoice No.</th><th>Total Amount</th><th>Due Date</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php
                            if ($invoices_result && $invoices_result->num_rows > 0) {
                                while($inv = $invoices_result->fetch_assoc()) {
                                    $stat_class = ($inv['status'] == 'Paid') ? 'status-paid' : (($inv['status'] == 'Unpaid') ? 'status-unpaid' : 'status-partial');
                                    echo "<tr><td>INV-" . str_pad($inv['invoice_id'], 4, '0', STR_PAD_LEFT) . "</td><td>₱ " . number_format($inv['total_amount'], 2) . "</td><td>" . date("M d, Y", strtotime($inv['due_date'])) . "</td><td><span class='status $stat_class'>" . $inv['status'] . "</span></td></tr>";
                                }
                            } else { echo "<tr><td colspan='4'>Walang nakitang invoice record.</td></tr>"; }
                            ?>
                        </tbody>
                    </table>
                </div>
                <h3>💰 Personal Payment History</h3>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Receipt No.</th><th>Amount Paid</th><th>Method</th><th>Date Paid</th></tr></thead>
                        <tbody>
                            <?php
                            if ($payments_result && $payments_result->num_rows > 0) {
                                while($pay = $payments_result->fetch_assoc()) { echo "<tr><td>REC-" . str_pad($pay['payment_id'], 4, '0', STR_PAD_LEFT) . "</td><td class='amount'>₱ " . number_format($pay['amount_paid'], 2) . "</td><td>" . htmlspecialchars($pay['payment_method']) . "</td><td>" . date("M d, Y", strtotime($pay['payment_date'])) . "</td></tr>"; }
                            } else { echo "<tr><td colspan='4'>Wala pang bayad na nare-record.</td></tr>"; }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeBtn = document.getElementById('theme-toggle'); if (localStorage.getItem('theme') === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); themeBtn.innerText = '☀️ Mode'; }
            themeBtn.addEventListener('click', () => { if (document.documentElement.getAttribute('data-theme') === 'dark') { document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme', 'light'); themeBtn.innerText = '🌙 Mode'; } else { document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); themeBtn.innerText = '☀️ Mode'; } });
            const mobileBtn = document.getElementById('mobile-menu-btn'); const navMenu = document.getElementById('nav-menu'); if(mobileBtn) { mobileBtn.addEventListener('click', () => { navMenu.classList.toggle('show-menu'); }); }
        });
    </script>
</body>
</html>