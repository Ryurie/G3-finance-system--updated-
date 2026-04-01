<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../../login.php"); exit(); }
require '../../config/db_connect.php';

$message = "";

// Kunin ang ID mula sa URL (halimbawa: edit_fee.php?id=1)
$fee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Kapag pinindot ang Update Button
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_fee_btn'])) {
    $id = intval($_POST['fee_id']);
    $fee_name = $_POST['fee_name'];
    $amount = floatval($_POST['amount']);
    $description = $_POST['description'];

    try {
        // I-update ang record sa database
        // NOTE: Kung iba ang column names niyo sa table, i-adjust lang dito (hal. kung walang 'description')
        $stmt_update = $conn->prepare("UPDATE fees SET fee_name = ?, amount = ?, description = ? WHERE fee_id = ?");
        $stmt_update->bind_param("sdsi", $fee_name, $amount, $description, $id);
        
        if ($stmt_update->execute()) {
            $message = "<div class='alert-msg alert-success animate-fade-up'>✅ Success! Fee details updated successfully.</div>";
        }
    } catch (mysqli_sql_exception $e) {
        $message = "<div class='alert-msg alert-error animate-fade-up'>❌ Error: " . $e->getMessage() . "</div>";
    }
}

// Kunin ang kasalukuyang data ng fee para ilagay sa form
$fee_data = null;
if ($fee_id > 0) {
    $stmt_fetch = $conn->prepare("SELECT * FROM fees WHERE fee_id = ?");
    $stmt_fetch->bind_param("i", $fee_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    if ($result->num_rows > 0) {
        $fee_data = $result->fetch_assoc();
    } else {
        $message = "<div class='alert-msg alert-error animate-fade-up'>❌ Error: Fee record not found.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Fee - BENRU's NETWORK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #FAF9F7; --card-bg: #FFFFFF; --text-primary: #111827; --text-secondary: #6B7280; --border-color: #E5E7EB; --button-dark: #111827; --button-text: #FFFFFF; --nav-bg: rgba(250, 249, 247, 0.95); --hover-bg: #F3F4F6; --shadow-color: rgba(0,0,0,0.05); } 
        [data-theme="dark"] { --bg-color: #111827; --card-bg: #1F2937; --text-primary: #F9FAFB; --text-secondary: #9CA3AF; --border-color: #374151; --button-dark: #F9FAFB; --button-text: #111827; --nav-bg: rgba(17, 24, 39, 0.95); --hover-bg: #374151; --shadow-color: rgba(0,0,0,0.2); } 
        
        body { margin: 0; background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: var(--text-primary); transition: 0.4s; } 
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .animate-fade-up { opacity: 0; animation: fadeInUp 0.6s forwards; } .delay-1 { animation-delay: 0.1s; } 
        
        /* NAVBAR STYLES */
        .top-navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); transition: 0.4s; flex-wrap: wrap; gap: 15px; position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px); } 
        .nav-left { display: flex; flex-direction: column; flex: 1 1 auto; } .nav-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; } .nav-subtitle { font-size: 0.85rem; color: var(--text-secondary); margin: 0; } 
        .nav-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: flex-end; flex: 2 1 auto; transition: 0.3s;} .nav-btn { text-decoration: none; padding: 8px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); cursor: pointer; transition: 0.3s; white-space: nowrap; } .nav-btn.active { background-color: var(--button-dark); color: var(--button-text); border-color: var(--button-dark); } .nav-btn:hover:not(.active) { background-color: var(--hover-bg); transform: translateY(-2px); }
        .menu-toggle { display: none; background: none; border: none; font-size: 1.8rem; color: var(--text-primary); cursor: pointer; transition: 0.3s; } .menu-toggle:hover { opacity: 0.7; }
        
        /* CONTAINER & CARD STYLES */
        .container { width: 90%; max-width: 800px; margin: 40px auto; } 
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; } 
        h1 { font-family: 'Playfair Display', serif; font-size: 2.2rem; margin: 0; } 
        
        .form-card { background-color: var(--card-bg); border-radius: 20px; box-shadow: 0 4px 20px var(--shadow-color); padding: 40px; border: 1px solid var(--border-color); transition: 0.4s; } 
        
        /* FORM ELEMENTS */
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.95rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; }
        input[type="text"], input[type="number"], textarea { width: 100%; padding: 14px 16px; border: 1px solid var(--border-color); border-radius: 12px; background-color: var(--bg-color); color: var(--text-primary); font-family: 'Inter', sans-serif; font-size: 1rem; box-sizing: border-box; transition: 0.3s; }
        input:focus, textarea:focus { outline: none; border-color: #111827; box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.1); }
        
        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn-save { flex: 1; background-color: #10b981; color: white; padding: 14px; border-radius: 12px; border: none; font-size: 1rem; cursor: pointer; font-weight: 600; transition: 0.3s; }
        .btn-save:hover { background-color: #059669; transform: translateY(-2px); }
        .btn-cancel { flex: 1; text-align: center; text-decoration: none; background-color: var(--bg-color); color: var(--text-primary); padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); font-size: 1rem; font-weight: 600; transition: 0.3s; }
        .btn-cancel:hover { background-color: var(--hover-bg); }

        .alert-msg { padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem; } 
        .alert-success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); } 
        .alert-error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

        @media (max-width: 900px) { .menu-toggle { display: block; } .nav-left { flex: none; width: 80%; } .nav-right { display: none; width: 100%; flex-direction: column; background-color: var(--card-bg); padding: 20px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 10px 25px var(--shadow-color); margin-top: 10px; align-items: stretch; position: absolute; top: 100%; left: 0; right: 0; margin-left: 5%; margin-right: 5%; } .nav-right.show-menu { display: flex; animation: popDown 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; } @keyframes popDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } } .nav-btn { text-align: center; padding: 12px; font-size: 1rem; margin-bottom: 5px; } }
        @media (max-width: 768px) { .form-card { padding: 25px; } .btn-group { flex-direction: column; } }
    </style>
</head>
<body>
    <header class="top-navbar animate-fade-up">
        <div class="nav-left">
            <h1 class="nav-title">BENRU's NETWORK</h1>
            <p class="nav-subtitle">Fee Management Module</p>
        </div>
        <button id="mobile-menu-btn" class="menu-toggle">☰</button>
        <div class="nav-right" id="nav-menu">
            <a href="../../index.php" class="nav-btn">Dashboard</a> 
            <a href="index.php" class="nav-btn active">Fees</a> <a href="../invoices/index.php" class="nav-btn">Invoices</a> 
            <a href="../payments/index.php" class="nav-btn">Payments</a> 
            <a href="../ledger/index.php" class="nav-btn">Ledger</a> 
            <a href="../scholarships/index.php" class="nav-btn">Scholarships</a> 
            <button id="theme-toggle" class="nav-btn">🌙 Mode</button> 
            <a href="../../logout.php" class="nav-btn" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Logout</a>
        </div>
    </header>

    <div class="container animate-fade-up delay-1">
        <div class="header-flex">
            <h1>✏️ Edit Fee Details</h1>
        </div>
        
        <?php echo $message; ?>

        <div class="form-card">
            <?php if ($fee_data): ?>
            <form method="POST" action="">
                <input type="hidden" name="fee_id" value="<?php echo $fee_data['fee_id']; ?>">
                
                <div class="form-group">
                    <label for="fee_name">Fee Name / Title</label>
                    <input type="text" id="fee_name" name="fee_name" value="<?php echo htmlspecialchars($fee_data['fee_name']); ?>" required placeholder="e.g. Tuition Fee, Miscellaneous, etc.">
                </div>

                <div class="form-group">
                    <label for="amount">Amount (₱)</label>
                    <input type="number" id="amount" name="amount" step="0.01" value="<?php echo htmlspecialchars($fee_data['amount']); ?>" required placeholder="0.00">
                </div>

                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <textarea id="description" name="description" rows="3" placeholder="Brief details about this fee..."><?php echo htmlspecialchars($fee_data['description'] ?? ''); ?></textarea>
                </div>

                <div class="btn-group">
                    <button type="submit" name="update_fee_btn" class="btn-save">Save Changes</button>
                    <a href="index.php" class="btn-cancel">Cancel & Go Back</a>
                </div>
            </form>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-secondary);">No data to edit. Please select a valid fee from the list.</p>
                <div class="btn-group" style="justify-content: center; margin-top: 20px;">
                    <a href="index.php" class="btn-cancel" style="max-width: 200px;">Go Back</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Dark Mode Logic
            const themeBtn = document.getElementById('theme-toggle'); 
            if (localStorage.getItem('theme') === 'dark') { 
                document.documentElement.setAttribute('data-theme', 'dark'); 
                themeBtn.innerText = '☀️ Mode'; 
            }
            themeBtn.addEventListener('click', () => { 
                if (document.documentElement.getAttribute('data-theme') === 'dark') { 
                    document.documentElement.removeAttribute('data-theme'); 
                    localStorage.setItem('theme', 'light'); 
                    themeBtn.innerText = '🌙 Mode'; 
                } else { 
                    document.documentElement.setAttribute('data-theme', 'dark'); 
                    localStorage.setItem('theme', 'dark'); 
                    themeBtn.innerText = '☀️ Mode'; 
                } 
            });

            // Mobile Menu Logic
            const mobileBtn = document.getElementById('mobile-menu-btn'); 
            const navMenu = document.getElementById('nav-menu'); 
            if(mobileBtn) { 
                mobileBtn.addEventListener('click', () => { 
                    navMenu.classList.toggle('show-menu'); 
                }); 
            }
        });
    </script>
</body>
</html>