<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../../login.php"); exit(); }
require '../../config/db_connect.php';

$message = "";

// KAPAG GUMAWA NG BAGONG FEE (ADD FEE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_fee_btn'])) {
    $fee_name = $_POST['fee_name'];
    $amount = floatval($_POST['amount']);
    $description = $_POST['description'];

    try {
        $stmt = $conn->prepare("INSERT INTO fees (fee_name, amount, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $fee_name, $amount, $description);
        
        if ($stmt->execute()) {
            $message = "<div class='alert-msg alert-success animate-fade-up'>✅ Success! New fee successfully added.</div>";
        }
    } catch (mysqli_sql_exception $e) {
        $message = "<div class='alert-msg alert-error animate-fade-up'>❌ Error: " . $e->getMessage() . "</div>";
    }
}

// KAPAG NAG-DELETE NG FEE
if (isset($_GET['delete'])) {
    $id_to_delete = intval($_GET['delete']);
    try {
        $conn->query("DELETE FROM fees WHERE fee_id = $id_to_delete");
        header("Location: index.php");
        exit();
    } catch (mysqli_sql_exception $e) {
        $message = "<div class='alert-msg alert-error animate-fade-up'>❌ Cannot delete this fee. It might be linked to existing student invoices.</div>";
    }
}

// Kukunin lahat ng fees para ilagay sa table
$result = $conn->query("SELECT * FROM fees ORDER BY fee_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Management - Finance & Fee System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #FAF9F7; --card-bg: #FFFFFF; --text-primary: #111827; --text-secondary: #6B7280; --border-color: #E5E7EB; --button-dark: #111827; --button-text: #FFFFFF; --nav-bg: rgba(250, 249, 247, 0.95); --hover-bg: #F3F4F6; --shadow-color: rgba(0,0,0,0.05); } 
        [data-theme="dark"] { --bg-color: #111827; --card-bg: #1F2937; --text-primary: #F9FAFB; --text-secondary: #9CA3AF; --border-color: #374151; --button-dark: #F9FAFB; --button-text: #111827; --nav-bg: rgba(17, 24, 39, 0.95); --hover-bg: #374151; --shadow-color: rgba(0,0,0,0.2); } 
        
        body { margin: 0; background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: var(--text-primary); transition: 0.4s; } 
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .animate-fade-up { opacity: 0; animation: fadeInUp 0.6s forwards; } .delay-1 { animation-delay: 0.1s; } 
        
        /* NAVBAR */
        .top-navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); transition: 0.4s; flex-wrap: wrap; gap: 15px; position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px); } 
        .nav-left { display: flex; flex-direction: column; flex: 1 1 auto; } .nav-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; } .nav-subtitle { font-size: 0.85rem; color: var(--text-secondary); margin: 0; } 
        .nav-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: flex-end; flex: 2 1 auto; transition: 0.3s;} .nav-btn { text-decoration: none; padding: 8px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); cursor: pointer; transition: 0.3s; white-space: nowrap; } .nav-btn.active { background-color: var(--button-dark); color: var(--button-text); border-color: var(--button-dark); } .nav-btn:hover:not(.active) { background-color: var(--hover-bg); transform: translateY(-2px); }
        .menu-toggle { display: none; background: none; border: none; font-size: 1.8rem; color: var(--text-primary); cursor: pointer; transition: 0.3s; } .menu-toggle:hover { opacity: 0.7; }
        
        /* CONTAINER & HEADER */
        .container { width: 90%; max-width: 1200px; margin: 40px auto; } 
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; } 
        h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin: 0; } 
        .btn-add { background-color: var(--button-dark); color: var(--button-text); padding: 12px 24px; border-radius: 30px; border: none; font-size: 0.95rem; cursor: pointer; font-weight: 600; transition: 0.3s; box-shadow: 0 4px 10px var(--shadow-color);} 
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.1); } 
        
        /* DESKTOP TABLE STYLES */
        .table-card { background-color: var(--card-bg); border-radius: 20px; box-shadow: 0 4px 20px var(--shadow-color); padding: 30px; border: 1px solid var(--border-color); transition: 0.4s; overflow: hidden; } 
        table { width: 100%; border-collapse: collapse; } 
        th, td { padding: 18px 15px; text-align: left; border-bottom: 1px solid var(--border-color); } 
        th { color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; } 
        tr:hover td { background-color: var(--hover-bg); } 
        .amount { font-weight: 700; color: #10b981; } 
        
        /* BUTTONS */
        .btn-edit { background: #3b82f6; color: white; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 600; margin-right: 5px; transition: 0.2s;} .btn-edit:hover { background: #2563eb; }
        .btn-delete { background: #ef4444; color: white; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: 0.2s;} .btn-delete:hover { background: #dc2626; }

        .alert-msg { padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; font-weight: 500; font-size: 0.95rem; } .alert-success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); } .alert-error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
        
        /* MODAL STYLES */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 2000; justify-content: center; align-items: center; } 
        .modal-box { background-color: var(--card-bg); padding: 40px; border-radius: 24px; width: 90%; max-width: 500px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2); position: relative; animation: popIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); border: 1px solid var(--border-color); } @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } } 
        .close-btn { position: absolute; top: 20px; right: 25px; font-size: 1.8rem; cursor: pointer; color: var(--text-secondary); transition: 0.2s; line-height: 1; } .close-btn:hover { color: var(--text-primary); transform: scale(1.1); } 
        .modal-box h2 { margin-top: 0; font-family: 'Playfair Display', serif; color: var(--text-primary); font-size: 1.8rem; } 
        .modal-box label { display: block; margin-top: 15px; font-size: 0.9rem; font-weight: 600; color: var(--text-secondary); } 
        .modal-box input, .modal-box textarea { width: 100%; padding: 14px 16px; margin-top: 8px; border: 1px solid var(--border-color); border-radius: 12px; background-color: var(--bg-color); color: var(--text-primary); font-family: 'Inter', sans-serif; box-sizing: border-box; transition: 0.3s; } 
        .modal-box input:focus, .modal-box textarea:focus { outline: none; border-color: #111827; box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.1); } 
        .modal-submit { width: 100%; margin-top: 25px; padding: 16px; border-radius: 12px; background: #10b981; color: white; border: none; font-size: 1rem; font-weight: bold; cursor: pointer; transition: 0.3s; } .modal-submit:hover { background: #059669; }

        /* =======================================================
           🔥🔥🔥 MOBILE RESPONSIVE MAGIC (KIKILABUTAN EFFECT) 🔥🔥🔥 
           ======================================================= */
        @media (max-width: 900px) { 
            .menu-toggle { display: block; } .nav-left { flex: none; width: 80%; } 
            .nav-right { display: none; width: 100%; flex-direction: column; background-color: var(--card-bg); padding: 20px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 10px 25px var(--shadow-color); margin-top: 10px; align-items: stretch; position: absolute; top: 100%; left: 0; right: 0; margin-left: 5%; margin-right: 5%; } 
            .nav-right.show-menu { display: flex; animation: popDown 0.3s forwards; } 
            .nav-btn { text-align: center; padding: 12px; margin-bottom: 5px; } 
        }

        @media (max-width: 768px) {
            .header-flex { flex-direction: column; align-items: flex-start; gap: 15px; }
            h1 { font-size: 2rem; }
            .btn-add { width: 100%; text-align: center; }
            
            /* Tinatanggal yung white box ng buong table para maging transparent */
            .table-card { background: transparent; padding: 0; box-shadow: none; border: none; }
            
            /* Tinatago yung matandang "Headers" */
            table thead { display: none; }
            
            /* Yung ROW nagiging mismong CARD na may shadow */
            table tbody tr { 
                display: block; 
                background-color: var(--card-bg); 
                margin-bottom: 20px; 
                border-radius: 16px; 
                box-shadow: 0 5px 15px var(--shadow-color); 
                border: 1px solid var(--border-color);
                overflow: hidden;
            }
            
            /* Yung bawat CELL nagiging magkabilaan (Label sa kaliwa, Value sa kanan) */
            table tbody td { 
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
                padding: 14px 20px; 
                border-bottom: 1px solid var(--border-color);
                text-align: right;
                font-size: 0.95rem;
            }
            
            table tbody td:last-child { border-bottom: none; background-color: var(--hover-bg); justify-content: flex-end; gap: 10px; }
            
            /* Dito galing yung labels na idadagdag natin sa HTML mamaya */
            table tbody td::before { 
                content: attr(data-label); 
                font-weight: 700; 
                color: var(--text-secondary); 
                font-size: 0.75rem; 
                text-transform: uppercase; 
                letter-spacing: 0.05em;
                margin-right: 15px;
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <header class="top-navbar animate-fade-up">
        <div class="nav-left"><h1 class="nav-title">Finance & Fee System</h1><p class="nav-subtitle">Fee Management Module</p></div>
        <button id="mobile-menu-btn" class="menu-toggle">☰</button>
        <div class="nav-right" id="nav-menu">
            <a href="../../index.php" class="nav-btn">Dashboard</a> 
            <a href="index.php" class="nav-btn active">Fees</a> 
            <a href="../invoices/index.php" class="nav-btn">Invoices</a> 
            <a href="../payments/index.php" class="nav-btn">Payments</a> 
            <a href="../ledger/index.php" class="nav-btn">Ledger</a> 
            <a href="../scholarships/index.php" class="nav-btn">Scholarships</a> 
            <button id="theme-toggle" class="nav-btn">🌙 Mode</button> 
            <a href="../../logout.php" class="nav-btn" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Logout</a>
        </div>
    </header>

    <div class="container animate-fade-up delay-1">
        <div class="header-flex">
            <h1>📚 Fee Structures</h1>
            <button onclick="openModal()" class="btn-add">+ Add New Fee</button>
        </div>
        
        <?php echo $message; ?>
        
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Fee ID</th>
                        <th>Fee Name / Title</th>
                        <th>Amount (₱)</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) { 
                            $desc_display = !empty($row['description']) ? htmlspecialchars($row['description']) : '<span style="color:#9ca3af; font-style:italic;">No description</span>';
                            
                            // NILAGYAN KO NG data-label ANG BAWAT <td> PARA SA MOBILE MAGIC
                            echo "<tr>
                                    <td data-label='Fee ID'><strong>FEE-" . str_pad($row['fee_id'], 4, '0', STR_PAD_LEFT) . "</strong></td>
                                    <td data-label='Fee Name / Title'>" . htmlspecialchars($row['fee_name']) . "</td>
                                    <td data-label='Amount (₱)' class='amount'>₱ " . number_format($row['amount'], 2) . "</td>
                                    <td data-label='Description' style='max-width: 250px; font-size: 14px; color: var(--text-secondary);'>" . $desc_display . "</td>
                                    <td data-label='Actions'>
                                        <a href='edit_fee.php?id=" . $row['fee_id'] . "' class='btn-edit'>Edit</a>
                                        <a href='index.php?delete=" . $row['fee_id'] . "' class='btn-delete' onclick='return confirm(\"Are you sure you want to delete this fee?\");'>Delete</a>
                                    </td>
                                  </tr>"; 
                        }
                    } else { 
                        echo "<tr><td colspan='5' style='text-align:center; color:var(--text-secondary);'>Walang naka-set na fees.</td></tr>"; 
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="addFeeModal" class="modal-overlay">
        <div class="modal-box">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2>Add New Fee</h2>
            <form method="POST" action="">
                <label for="fee_name">Fee Name / Title:</label> 
                <input type="text" id="fee_name" name="fee_name" required placeholder="e.g. Laboratory Fee">
                
                <label for="amount">Amount (₱):</label> 
                <input type="number" id="amount" name="amount" step="0.01" required placeholder="0.00">
                
                <label for="description">Description (Optional):</label> 
                <textarea id="description" name="description" rows="3" placeholder="Brief details about this fee..."></textarea>
                
                <button type="submit" name="add_fee_btn" class="modal-submit">Save Fee</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeBtn = document.getElementById('theme-toggle'); 
            if (localStorage.getItem('theme') === 'dark') { 
                document.documentElement.setAttribute('data-theme', 'dark'); themeBtn.innerText = '☀️ Mode'; 
            }
            themeBtn.addEventListener('click', () => { 
                if (document.documentElement.getAttribute('data-theme') === 'dark') { 
                    document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme', 'light'); themeBtn.innerText = '🌙 Mode'; 
                } else { 
                    document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); themeBtn.innerText = '☀️ Mode'; 
                } 
            });
            const mobileBtn = document.getElementById('mobile-menu-btn'); const navMenu = document.getElementById('nav-menu'); 
            if(mobileBtn) { mobileBtn.addEventListener('click', () => { navMenu.classList.toggle('show-menu'); }); }
        });
        
        const modal = document.getElementById('addFeeModal'); 
        function openModal() { modal.style.display = 'flex'; } 
        function closeModal() { modal.style.display = 'none'; } 
        window.onclick = function(event) { if (event.target == modal) { closeModal(); } }
    </script>
</body>
</html>