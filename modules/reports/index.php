<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../../login.php"); exit(); }
require '../../config/db_connect.php';

$report_date = isset($_GET['report_date']) ? $_GET['report_date'] : date('Y-m-d');

$stmt_total = $conn->prepare("SELECT SUM(amount_paid) as total_collected FROM payments WHERE DATE(payment_date) = ?");
$stmt_total->bind_param("s", $report_date); $stmt_total->execute();
$grand_total = $stmt_total->get_result()->fetch_assoc()['total_collected'] ?? 0;

$stmt_methods = $conn->prepare("SELECT payment_method, SUM(amount_paid) as method_total FROM payments WHERE DATE(payment_date) = ? GROUP BY payment_method");
$stmt_methods->bind_param("s", $report_date); $stmt_methods->execute(); $methods_result = $stmt_methods->get_result();

$stmt_details = $conn->prepare("SELECT p.payment_id, p.amount_paid, p.payment_method, p.reference_number, p.payment_date, i.student_id FROM payments p JOIN invoices i ON p.invoice_id = i.invoice_id WHERE DATE(p.payment_date) = ? ORDER BY p.payment_date DESC");
$stmt_details->bind_param("s", $report_date); $stmt_details->execute(); $details_result = $stmt_details->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Reports - Finance & Fee System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #FAF9F7; --card-bg: #FFFFFF; --text-primary: #111827; --text-secondary: #6B7280; --border-color: #E5E7EB; --button-dark: #111827; --button-text: #FFFFFF; --nav-bg: rgba(250, 249, 247, 0.95); --hover-bg: #F3F4F6; --shadow-color: rgba(0,0,0,0.05); } 
        [data-theme="dark"] { --bg-color: #111827; --card-bg: #1F2937; --text-primary: #F9FAFB; --text-secondary: #9CA3AF; --border-color: #374151; --button-dark: #F9FAFB; --button-text: #111827; --nav-bg: rgba(17, 24, 39, 0.95); --hover-bg: #374151; --shadow-color: rgba(0,0,0,0.2); } 
        body { margin: 0; background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: var(--text-primary); transition: background-color 0.4s, color 0.4s; } 
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .animate-fade-up { opacity: 0; animation: fadeInUp 0.6s forwards; } .delay-1 { animation-delay: 0.1s; } 
        .top-navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px); } 
        .nav-left { display: flex; flex-direction: column; } .nav-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; } .nav-subtitle { font-size: 0.85rem; color: var(--text-secondary); margin: 0; } 
        .nav-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: flex-end;} .nav-btn { text-decoration: none; padding: 8px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); } .nav-btn.active { background-color: var(--button-dark); color: var(--button-text); border-color: var(--button-dark); } 
        .menu-toggle { display: none; background: none; border: none; font-size: 1.8rem; color: var(--text-primary); cursor: pointer; } 
        .container { width: 90%; max-width: 1200px; margin: 40px auto; } .page-header { text-align: center; margin-bottom: 40px; } .page-header h1 { font-family: 'Playfair Display', serif; font-size: 2.8rem; margin: 0; }
        .filter-card { background-color: var(--card-bg); border-radius: 16px; padding: 20px; border: 1px solid var(--border-color); display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 30px; box-shadow: 0 4px 15px var(--shadow-color); flex-wrap: wrap;} .filter-card input[type="date"] { padding: 10px 15px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-color); color: var(--text-primary); } .btn-generate { background-color: var(--button-dark); color: var(--button-text); border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; } .stat-card { background-color: var(--card-bg); border-radius: 20px; padding: 30px; text-align: center; border: 1px solid var(--border-color); box-shadow: 0 4px 20px var(--shadow-color); } .stat-card h3 { font-size: 0.9rem; color: var(--text-secondary); text-transform: uppercase; } .stat-card .amount { font-family: 'Playfair Display', serif; font-size: 2.5rem; font-weight: 700; color: var(--text-primary); margin: 0; } .amount-green { color: #10b981 !important; }
        .breakdown-list { list-style: none; padding: 0; margin: 15px 0 0 0; text-align: left; } .breakdown-list li { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed var(--border-color); font-size: 0.95rem; color: var(--text-secondary); } .breakdown-list li strong { color: var(--text-primary); }
        .table-card { background-color: var(--card-bg); border-radius: 20px; box-shadow: 0 4px 20px var(--shadow-color); padding: 30px; border: 1px solid var(--border-color); overflow-x: auto; } table { width: 100%; border-collapse: collapse; min-width: 700px; margin-top: 15px; } th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); } th { color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; background-color: rgba(0,0,0,0.02); } [data-theme="dark"] th { background-color: rgba(255,255,255,0.02); } tr:hover td { background-color: var(--hover-bg); } .table-amount { font-weight: 600; color: #10b981; } 
        
        /* MOBILE RESPONSIVE MAGIC */
        @media (max-width: 900px) { .menu-toggle { display: block; } .nav-left { width: 80%; } .nav-right { display: none; width: 100%; flex-direction: column; background-color: var(--card-bg); padding: 20px; border-radius: 16px; border: 1px solid var(--border-color); position: absolute; top: 100%; left: 0; right: 0; margin: 10px 5%; } .nav-right.show-menu { display: flex; } .nav-btn { text-align: center; padding: 12px; margin-bottom: 5px; } .summary-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .page-header h1 { font-size: 2rem; }
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
        <div class="nav-left"><h1 class="nav-title">Finance & Fee System</h1><p class="nav-subtitle">End-of-Day Reports</p></div>
        <button id="mobile-menu-btn" class="menu-toggle">☰</button>
        <div class="nav-right" id="nav-menu">
            <a href="../../index.php" class="nav-btn">Dashboard</a> <a href="../fees/index.php" class="nav-btn">Fees</a> <a href="../invoices/index.php" class="nav-btn">Invoices</a> <a href="../payments/index.php" class="nav-btn">Payments</a> <a href="../ledger/index.php" class="nav-btn">Ledger</a> <a href="../scholarships/index.php" class="nav-btn">Scholarships</a> <a href="index.php" class="nav-btn active">Reports</a> <button id="theme-toggle" class="nav-btn">🌙 Mode</button> <a href="../../logout.php" class="nav-btn" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="page-header animate-fade-up"><h1>📅 Daily Collection Report</h1></div>
        <form method="GET" action="" class="filter-card animate-fade-up delay-1">
            <label for="report_date">Select Report Date:</label>
            <input type="date" id="report_date" name="report_date" value="<?php echo $report_date; ?>" required>
            <button type="submit" class="btn-generate">Generate Report</button>
        </form>

        <div class="summary-grid animate-fade-up delay-1">
            <div class="stat-card">
                <h3>Grand Total (<?php echo strtoupper(date("M d", strtotime($report_date))); ?>)</h3>
                <p class="amount amount-green">₱ <?php echo number_format($grand_total, 2); ?></p>
            </div>
            <div class="stat-card">
                <h3>Collection Breakdown</h3>
                <?php if ($methods_result->num_rows > 0): ?>
                    <ul class="breakdown-list">
                        <?php while($row = $methods_result->fetch_assoc()): ?>
                            <li><span><?php echo htmlspecialchars($row['payment_method']); ?></span><strong>₱ <?php echo number_format($row['method_total'], 2); ?></strong></li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?><p style="color: var(--text-secondary); margin-top: 20px;">No collections recorded.</p><?php endif; ?>
            </div>
        </div>

        <div class="table-card animate-fade-up delay-1">
            <h2>🧾 Detailed Transactions</h2>
            <table>
                <thead><tr><th>Time</th><th>Receipt No.</th><th>Student ID</th><th>Method</th><th>Ref No.</th><th>Amount</th></tr></thead>
                <tbody>
                    <?php
                    if ($details_result->num_rows > 0) {
                        while($row = $details_result->fetch_assoc()) { 
                            echo "<tr>
                                    <td data-label='Time' style='color: var(--text-secondary);'>" . date("h:i A", strtotime($row['payment_date'])) . "</td>
                                    <td data-label='Receipt No.'><strong>REC-" . str_pad($row['payment_id'], 4, '0', STR_PAD_LEFT) . "</strong></td>
                                    <td data-label='Student ID'>" . htmlspecialchars($row['student_id']) . "</td>
                                    <td data-label='Method'>" . htmlspecialchars($row['payment_method']) . "</td>
                                    <td data-label='Ref No.'>" . ($row['reference_number'] ? htmlspecialchars($row['reference_number']) : 'N/A') . "</td>
                                    <td data-label='Amount' class='table-amount'>₱ " . number_format($row['amount_paid'], 2) . "</td>
                                  </tr>"; 
                        }
                    } else { echo "<tr><td colspan='6' style='text-align:center; padding: 40px; color:var(--text-secondary);'>Walang pumasok na pera sa petsang ito.</td></tr>"; }
                    ?>
                </tbody>
            </table>
        </div>
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