<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: login.php"); exit(); }
require 'config/db_connect.php'; 

$q_collected = $conn->query("SELECT SUM(amount_paid) as collected FROM payments"); $collected = $q_collected->fetch_assoc()['collected'] ?? 0;
$q_invoiced = $conn->query("SELECT SUM(total_amount + penalty) as expected FROM invoices"); $expected = $q_invoiced->fetch_assoc()['expected'] ?? 0;
$pending = $expected - $collected; if ($pending < 0) $pending = 0; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Dashboard - Group 3</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --bg-color: #FAF9F7; --card-bg: #FFFFFF; --text-primary: #111827; --text-secondary: #6B7280; --border-color: #E5E7EB; --button-dark: #111827; --button-text: #FFFFFF; --nav-bg: rgba(250, 249, 247, 0.95); --hover-bg: #F3F4F6; --shadow-color: rgba(0, 0, 0, 0.05); --shadow-hover: rgba(0, 0, 0, 0.1); }
        [data-theme="dark"] { --bg-color: #111827; --card-bg: #1F2937; --text-primary: #F9FAFB; --text-secondary: #9CA3AF; --border-color: #374151; --button-dark: #F9FAFB; --button-text: #111827; --nav-bg: rgba(17, 24, 39, 0.95); --hover-bg: #374151; --shadow-color: rgba(0, 0, 0, 0.2); --shadow-hover: rgba(0, 0, 0, 0.4); }

        body { margin: 0; padding: 0; background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: var(--text-primary); min-height: 100vh; transition: background-color 0.4s ease, color 0.4s ease; }
        
        /* ANIMATIONS */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } } 
        .animate-fade-up { opacity: 0; animation: fadeInUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards; } 
        .delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; } .delay-3 { animation-delay: 0.3s; }

        /* STICKY NAVBAR */
        .top-navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); flex-wrap: wrap; gap: 15px; transition: background-color 0.4s ease, border-color 0.4s ease; position: sticky; top: 0; z-index: 9999; backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .nav-left { display: flex; flex-direction: column; flex: 1 1 auto; } 
        .nav-badge { font-size: 0.65rem; font-weight: 600; text-transform: uppercase; color: var(--text-secondary); border: 1px solid var(--border-color); border-radius: 20px; padding: 4px 12px; display: inline-block; align-self: flex-start; margin-bottom: 8px; background-color: var(--card-bg); letter-spacing: 0.05em; transition: all 0.4s ease; } 
        .nav-title { font-size: 1.3rem; font-weight: 700; margin: 0 0 4px 0; color: var(--text-primary); letter-spacing: -0.02em; } 
        .nav-subtitle { font-size: 0.85rem; color: var(--text-secondary); margin: 0; font-weight: 400; }
        
        .nav-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: flex-end; flex: 2 1 auto; transition: 0.3s; } 
        .nav-btn { text-decoration: none; padding: 8px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; transition: all 0.3s ease; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); cursor: pointer; white-space: nowrap; } 
        .nav-btn.active { background-color: var(--button-dark); color: var(--button-text); border-color: var(--button-dark); } 
        .nav-btn:hover:not(.active) { background-color: var(--hover-bg); transform: translateY(-2px); }
        .menu-toggle { display: none; background: none; border: none; font-size: 1.8rem; color: var(--text-primary); cursor: pointer; transition: 0.3s; } 
        .menu-toggle:hover { opacity: 0.7; }

        .dashboard-container { width: 90%; max-width: 1300px; margin: 0 auto; padding: 40px 0; } 
        .header-section h1 { font-family: 'Playfair Display', serif; font-size: 2.8rem; color: var(--text-primary); margin: 0 0 10px 0; } 
        .header-section p { font-size: 1.05rem; color: var(--text-secondary); margin: 0 0 40px 0; max-width: 600px; line-height: 1.6; }
        
        .analytics-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; margin-bottom: 40px; } 
        .stat-card { background-color: var(--card-bg); padding: 30px; border-radius: 24px; box-shadow: 0 4px 20px var(--shadow-color); border: 1px solid var(--border-color); transition: 0.4s; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; } 
        .stat-card h3 { margin: 0 0 10px 0; font-size: 1rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; } 
        .stat-card .amount-lg { font-size: 2.5rem; font-weight: 700; color: var(--text-primary); font-family: 'Playfair Display', serif; margin: 0; } 
        .stat-card .amount-success { color: #10b981; } .stat-card .amount-danger { color: #ef4444; } 
        .chart-container { position: relative; height: 200px; width: 100%; display: flex; justify-content: center; margin-top: 15px;}
        
        .module-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; } 
        .module-card { background-color: var(--card-bg); padding: 40px; border-radius: 24px; box-shadow: 0 4px 20px var(--shadow-color); display: flex; flex-direction: column; justify-content: space-between; border: 1px solid var(--border-color); transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.4s ease; } 
        .module-card:hover { transform: translateY(-8px) scale(1.02); box-shadow: 0 15px 35px var(--shadow-hover); z-index: 10;} 
        .card-details h2 { font-size: 1.5rem; font-weight: 600; margin: 0 0 10px 0; color: var(--text-primary); } 
        .card-details p { margin: 0 0 30px 0; color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; } 
        .card-icon { font-size: 2.5rem; margin-bottom: 25px; display: inline-block; transition: transform 0.3s ease; } 
        .module-card:hover .card-icon { transform: scale(1.1) rotate(5deg); } 
        .btn-dark { background-color: var(--button-dark); color: var(--button-text); border: none; padding: 12px 24px; border-radius: 30px; font-size: 0.95rem; font-weight: 500; text-decoration: none; display: inline-block; text-align: center; align-self: flex-start; transition: all 0.3s ease; } 
        .btn-dark:hover { opacity: 0.8; transform: translateX(5px); }

        /* =========================================
           MOBILE RESPONSIVE FIXES (Smoother UI)
        ========================================= */
        @media (max-width: 900px) { 
            .menu-toggle { display: block; } 
            .nav-left { flex: none; width: 80%; } 
            
            /* DITO NATIN INAYOS YUNG HINDI PANTAY AT LUMALAGPAS */
            .nav-right { 
                display: none; 
                flex-direction: column; 
                background-color: var(--card-bg); 
                padding: 15px; 
                border-radius: 16px; 
                border: 1px solid var(--border-color); 
                box-shadow: 0 15px 30px var(--shadow-color); 
                margin-top: 15px; 
                align-items: stretch; 
                position: absolute; 
                top: 100%; 
                /* Tinanggal natin yung width: 100% at margins para hindi sumobra */
                left: 5%; 
                right: 5%; 
                z-index: 10000; 
                box-sizing: border-box; /* Ito ang pipigil sa extra padding */
                gap: 5px; /* Saktong espasyo sa pagitan ng buttons */
            } 
            .nav-right.show-menu { display: flex; animation: popDown 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; } 
            @keyframes popDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } } 
            
            /* INAYOS ANG MGA BUTTONS PARA PROFESSIONAL TIGNAN SA MOBILE */
            .nav-btn { 
                text-align: left; /* Naka-align na sa kaliwa imbes na sa gitna */
                padding: 12px 20px; 
                font-size: 1rem; 
                margin: 0; 
                border-radius: 8px; /* Tinanggal natin yung pagiging sobrang bilog (pill) */
                width: 100%; 
                box-sizing: border-box;
            } 
            
            .analytics-grid { grid-template-columns: 1fr; gap: 15px; }
        }

        @media (max-width: 768px) { 
            .top-navbar { padding: 15px 5%; }
            .nav-title { font-size: 1.15rem; }
            .nav-subtitle { font-size: 0.8rem; }
            .nav-badge { font-size: 0.6rem; padding: 3px 10px; }

            .dashboard-container { width: 92%; padding: 20px 0; } 
            .header-section h1 { font-size: 2.2rem; } 
            .header-section p { font-size: 0.95rem; margin-bottom: 25px; }
            
            .stat-card { padding: 20px; border-radius: 16px; }
            .stat-card .amount-lg { font-size: 2.2rem; }
            .chart-container { height: 180px; }

            .module-grid { grid-template-columns: 1fr; gap: 15px; } 
            .module-card { padding: 25px; border-radius: 16px; }
            .card-icon { font-size: 2rem; margin-bottom: 15px; }
            .card-details h2 { font-size: 1.3rem; }
            .card-details p { font-size: 0.9rem; margin-bottom: 20px; }
            .btn-dark { width: 100%; padding: 12px; box-sizing: border-box; } 
        }
    </style>
</head>
<body>

    <header class="top-navbar">
        <div class="nav-left">
            <span class="nav-badge">Finance & Fee Portal</span>
            <h1 class="nav-title">Finance & Fee Management System</h1>
            <span class="subtitle">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?> (Admin)</span>
        </div>
        <button id="mobile-menu-btn" class="menu-toggle">☰</button>
        <div class="nav-right" id="nav-menu">
            <a href="index.php" class="nav-btn active">Dashboard</a>
            <a href="modules/fees/index.php" class="nav-btn">Fees</a>
            <a href="modules/invoices/index.php" class="nav-btn">Invoices</a>
            <a href="modules/payments/index.php" class="nav-btn">Payments</a>
            <a href="modules/ledger/index.php" class="nav-btn">Ledger</a>
            <a href="modules/scholarships/index.php" class="nav-btn">Scholarships</a>
            <a href="modules/reports/index.php" class="nav-btn">Reports</a>
            <button id="theme-toggle" class="nav-btn">🌙 Mode</button>
            <a href="logout.php" class="nav-btn" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Logout</a>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="header-section animate-fade-up delay-1">
            <h1>Dashboard Overview</h1>
            <p>Manage tuition payments, set up fee structures, and view financial analytics.</p>
        </div>
        
        <div class="analytics-grid animate-fade-up delay-1">
            <div class="stat-card">
                <h3>Total Collected</h3>
                <p class="amount-lg amount-success">₱<?php echo number_format($collected, 2); ?></p>
                <h3 style="margin-top: 20px;">Pending Balance</h3>
                <p class="amount-lg amount-danger">₱<?php echo number_format($pending, 2); ?></p>
            </div>
            <div class="stat-card" style="align-items: center; justify-content: flex-start;">
                <h3>Collection Status Overview</h3>
                <div class="chart-container"><canvas id="financeChart"></canvas></div>
            </div>
        </div>

        <div class="module-grid">
            <div class="module-card animate-fade-up delay-2"><div class="card-details"><span class="card-icon">📚</span><h2>Fee Structure</h2><p>Configure tuition rates, miscellaneous fees, and academic program costs.</p></div><a href="modules/fees/index.php" class="btn-dark">Manage Fees</a></div>
            <div class="module-card animate-fade-up delay-2"><div class="card-details"><span class="card-icon">📄</span><h2>Invoices</h2><p>Generate new student invoices and track clearance status.</p></div><a href="modules/invoices/index.php" class="btn-dark">Manage Invoices</a></div>
            <div class="module-card animate-fade-up delay-3"><div class="card-details"><span class="card-icon">💰</span><h2>Payments</h2><p>Record student payments and view complete transaction history.</p></div><a href="modules/payments/index.php" class="btn-dark">Manage Payments</a></div>
            <div class="module-card animate-fade-up delay-3"><div class="card-details"><span class="card-icon">🔍</span><h2>Ledger & Clearance</h2><p>Check individual student clearance status, unpaid balances, and personal payment history.</p></div><a href="modules/ledger/index.php" class="btn-dark">Check Status</a></div>
            <div class="module-card animate-fade-up delay-4"><div class="card-details"><span class="card-icon">🎓</span><h2>Scholarships</h2><p>Manage student scholarship grants and automatic tuition discounts.</p></div><a href="modules/scholarships/index.php" class="btn-dark">Manage Scholarships</a></div>
            <div class="module-card animate-fade-up delay-4"><div class="card-details"><span class="card-icon">📅</span><h2>Daily Reports</h2><p>Generate end-of-day collection reports and view daily revenue breakdown.</p></div><a href="modules/reports/index.php" class="btn-dark">View Reports</a></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggleBtn = document.getElementById('theme-toggle'); const currentTheme = localStorage.getItem('theme');
            if (currentTheme) { document.documentElement.setAttribute('data-theme', currentTheme); if (currentTheme === 'dark') { themeToggleBtn.innerText = '☀️ Mode'; } }
            themeToggleBtn.addEventListener('click', () => {
                let theme = document.documentElement.getAttribute('data-theme');
                if (theme === 'dark') { document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme', 'light'); themeToggleBtn.innerText = '🌙 Mode'; } 
                else { document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); themeToggleBtn.innerText = '☀️ Mode'; }
            });
            const mobileBtn = document.getElementById('mobile-menu-btn'); const navMenu = document.getElementById('nav-menu');
            if(mobileBtn) { mobileBtn.addEventListener('click', () => { navMenu.classList.toggle('show-menu'); }); }
            
            const ctx = document.getElementById('financeChart').getContext('2d');
            new Chart(ctx, { type: 'doughnut', data: { labels: ['Total Collected (₱)', 'Pending Balance (₱)'], datasets: [{ data: [<?php echo $collected; ?>, <?php echo $pending; ?>], backgroundColor: ['#10b981', '#ef4444'], hoverOffset: 4, borderWidth: 0 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#6B7280', font: { family: 'Inter' } } } }, cutout: '70%' } });
        });
    </script>
</body>
</html>