<?php
session_start();
// Siguraduhing Admin lang ang makakapasok dito
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: login.php"); exit(); }
require 'config/db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_reset'])) {
    try {
        // 1. Patayin muna ang Foreign Key Checks para hindi mag-error pag nag-reset ng magkaka-konektang tables
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");

        // 2. I-TRUNCATE (Burahin lahat at i-reset sa 1 ang ID) ang mga transaction tables
        $conn->query("TRUNCATE TABLE payments");
        $conn->query("TRUNCATE TABLE online_payments");
        $conn->query("TRUNCATE TABLE student_scholarships");
        $conn->query("TRUNCATE TABLE invoices");
        
        // I-reset din ang audit logs para malinis
        $conn->query("TRUNCATE TABLE audit_logs");

        // NOTE: HINDI KO SINAMA ANG 'fees' table. 
        // Kadalasan kasi, hindi binubura ang master list ng fees (like Tuition, Misc).
        // Kung gusto mo burahin pati fees, tanggalin ang comment (//) sa susunod na linya:
        // $conn->query("TRUNCATE TABLE fees");

        // 3. I-on ulit ang Foreign Key Checks (SOBRANG IMPORTANTE NITO)
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");

        // 4. Mag-iwan ng unang log na nag-reset ang Admin
        $admin_name = $_SESSION['username'] ?? 'Admin';
        $conn->query("INSERT INTO audit_logs (admin_name, action_type, description) VALUES ('$admin_name', 'SYSTEM RESET', 'System was completely wiped clean.')");

        $message = "<div class='alert-msg alert-success animate-fade-up'>✅ SYSTEM RESET SUCCESSFUL! All transactions have been permanently deleted. Database is now clean.</div>";
    } catch (Exception $e) {
        $message = "<div class='alert-msg alert-error animate-fade-up'>❌ ERROR: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mass Reset - BENRU's NETWORK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #111827; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .reset-card { background: #1f2937; padding: 40px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); text-align: center; max-width: 500px; width: 90%; border: 2px solid #ef4444; position: relative; overflow: hidden; }
        
        /* Danger stripes design */
        .reset-card::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 10px; background: repeating-linear-gradient(45deg, #ef4444, #ef4444 10px, #b91c1c 10px, #b91c1c 20px); }
        
        h1 { color: #ef4444; font-weight: 900; font-size: 2rem; margin-top: 10px; text-transform: uppercase; letter-spacing: 2px;}
        p { color: #9ca3af; font-size: 1rem; line-height: 1.5; margin-bottom: 30px; }
        .warning-text { color: #fca5a5; font-weight: bold; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px; font-size: 0.9rem;}
        
        .btn-nuke { background-color: #ef4444; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer; text-transform: uppercase; width: 100%; transition: 0.3s; margin-bottom: 15px; }
        .btn-nuke:hover { background-color: #dc2626; transform: scale(1.02); }
        
        .btn-back { display: block; background: transparent; border: 1px solid #4b5563; color: #d1d5db; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .btn-back:hover { background: #374151; color: white; }

        .alert-msg { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; font-size: 1rem; }
        .alert-success { background-color: #064e3b; color: #34d399; border: 1px solid #059669; }
        .alert-error { background-color: #7f1d1d; color: #fca5a5; border: 1px solid #b91c1c; }
        
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } 
        .animate-fade-up { animation: fadeInUp 0.5s forwards; }
    </style>
</head>
<body>
    <div class="reset-card">
        <h1>⚠️ Danger Zone</h1>
        <?php echo $message; ?>
        
        <p>You are about to execute a <strong>MASS RESET</strong>. This will permanently wipe out all Invoices, Payments, Scholarships, and Audit Logs.</p>
        <div class="warning-text">This action cannot be undone. All transaction ID numbers will be reset back to 0001.</div>
        
        <form method="POST" action="" onsubmit="return confirm('FINAL WARNING: Are you absolutely sure you want to delete ALL data? This cannot be undone!');">
            <button type="submit" name="confirm_reset" class="btn-nuke">🔥 Yes, Wipe Everything Clean</button>
        </form>
        
        <a href="index.php" class="btn-back">Cancel & Return to Dashboard</a>
    </div>
</body>
</html>