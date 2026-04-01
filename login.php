<?php
session_start();

// Kung may nakapag-login na, direcho na sa dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) { 
    header("Location: index.php"); 
    exit(); 
}

require 'config/db_connect.php'; 
$error = '';

// BASIC LOGIN LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Simpleng hardcoded check for demonstration (Palitan mo ng database query kung may users table kayo)
    if ($username === 'admin' && $password === 'admin123') { 
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit();
    } else {
        $error = "Incorrect username or password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Login | Finance Access Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f4f4f5; /* Subtle premium gray */
            background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
            background-size: 20px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #111827;
        }

        /* ENTRANCE ANIMATION */
        @keyframes floatUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-wrapper {
            width: 100%;
            max-width: 1000px;
            padding: 20px;
            animation: floatUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* MAIN 2-COLUMN CARD */
        .login-card {
            background-color: #ffffff;
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            display: flex;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.8);
        }

        /* LEFT SIDE: BRANDING & FEATURES */
        .card-left {
            flex: 1.2;
            padding: 60px 50px;
            background: linear-gradient(to bottom right, #ffffff, #fafafa);
            border-right: 1px solid #f3f4f6;
        }

        .portal-badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #047857; /* Deep emerald green */
            background-color: #d1fae5;
            padding: 8px 18px;
            border-radius: 30px;
            margin-bottom: 25px;
        }

        .card-left h1 {
            font-family: 'Playfair Display', serif;
            font-size: 4rem;
            line-height: 1.05;
            letter-spacing: -1.5px;
            color: #111827;
            margin: 0 0 15px 0;
        }

        .card-left p.subtitle {
            color: #6b7280;
            font-size: 1.05rem;
            line-height: 1.6;
            margin: 0 0 40px 0;
            max-width: 90%;
        }

        /* FEATURE BOXES */
        .features-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .feature-box {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            padding: 20px;
            border-radius: 16px;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }

        .feature-box:hover {
            border-color: #cbd5e1;
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.05);
        }

        .feature-box h3 {
            margin: 0 0 8px 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: #111827;
        }

        .feature-box p {
            margin: 0;
            font-size: 0.85rem;
            color: #6b7280;
            line-height: 1.5;
        }

        /* RIGHT SIDE: LOGIN FORM */
        .card-right {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
        }

        .form-group input {
            width: 100%;
            padding: 16px 18px;
            background-color: #eff6ff; /* Very light blue tint */
            border: 1px solid #dbeafe;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            color: #1e3a8a;
            box-sizing: border-box;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .form-group input:focus {
            outline: none;
            background-color: #ffffff;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        }

        /* EYE ICON */
        .toggle-password {
            position: absolute;
            right: 18px;
            top: 45px;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0;
            transition: 0.3s;
        }

        .toggle-password:hover { color: #475569; }

        .btn-submit {
            width: 100%;
            background-color: #1f2937;
            color: #ffffff;
            border: none;
            padding: 18px;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: #111827;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(17, 24, 39, 0.2);
        }

        /* SAMPLE CREDENTIALS HINT */
        .credentials-hint {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #f3f4f6;
            font-size: 0.85rem;
            color: #6b7280;
            line-height: 1.6;
        }

        .credentials-hint strong {
            color: #4b5563;
        }

        .error-msg {
            background-color: #fef2f2;
            color: #dc2626;
            padding: 15px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 25px;
            border: 1px solid #fecaca;
        }

        /* RESPONSIVE DESIGN FOR MOBILE */
        @media (max-width: 900px) {
            .login-card {
                flex-direction: column;
                border-radius: 20px;
            }
            .card-left {
                border-right: none;
                border-bottom: 1px solid #f3f4f6;
                padding: 40px 30px;
            }
            .card-right {
                padding: 40px 30px;
            }
            .card-left h1 {
                font-size: 3rem;
            }
            .features-grid {
                grid-template-columns: 1fr; /* Stack features sa mobile */
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .card-left h1 { font-size: 2.5rem; }
            .login-wrapper { padding: 15px; }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            
            <div class="card-left">
                <span class="portal-badge">FINANCE ACCESS PORTAL</span>
                <h1>Welcome<br>back</h1>
                <p class="subtitle">Use your authorized credentials to access the Group 3 finance and fee management workspace.</p>
                
                <div class="features-grid">
                    <div class="feature-box">
                        <h3>Fast record access</h3>
                        <p>Review student invoices, uploaded receipts, and clearance details in one place.</p>
                    </div>
                    <div class="feature-box">
                        <h3>Admin-ready workflow</h3>
                        <p>Process payments, grant scholarships, and keep school accounting organized.</p>
                    </div>
                </div>
            </div>

            <div class="card-right">
                <?php if (!empty($error)): ?>
                    <div class="error-msg"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="admin" required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        <button type="button" class="toggle-password" id="toggleBtn" onclick="togglePassword()">👁️</button>
                    </div>

                    <button type="submit" class="btn-submit">Sign In</button>
                </form>

                <div class="credentials-hint">
                    <strong>Sample admin username:</strong> admin<br>
                    <strong>Sample password:</strong> admin123
                </div>
            </div>

        </div>
    </div>

    <script>
        function togglePassword() {
            const passInput = document.getElementById('password');
            const btn = document.getElementById('toggleBtn');
            
            if (passInput.type === 'password') {
                passInput.type = 'text';
                btn.style.opacity = '0.5'; 
            } else {
                passInput.type = 'password';
                btn.style.opacity = '1';
            }
        }
    </script>

</body>
</html>