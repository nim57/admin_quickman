<?php
session_start();

// Hardcoded user credentials
$valid_users = [
    'admin1' => 'password1',
    'admin2' => 'password2'
];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate credentials
    if (isset($valid_users[$username]) && $valid_users[$username] === $password) {
        $_SESSION['isAuthenticated'] = true;
        $_SESSION['username'] = $username;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo-section">
                <div class="logo-container">
                    <div class="app-icon">Q</div>
                </div>
                <h1>Quickman Admin</h1>
                <p>Enter your credentials to continue</p>
            </div>
            
            <form id="loginForm" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Enter username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>
                
                <button type="submit" class="login-btn">Login</button>
            </form>
            
            <?php if (isset($error)): ?>
                <div id="errorMessage" class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="footer">
                <p>© 2025 Quickman Admin Panel</p>
                <p>Powered by <span>Team Quickman</span></p>
            </div>
        </div>
    </div>
    
    <script>
        // Add shake animation to error message if present
        document.addEventListener('DOMContentLoaded', function() {
            const errorEl = document.getElementById('errorMessage');
            if (errorEl) {
                errorEl.classList.add('show');
                
                setTimeout(() => {
                    errorEl.classList.remove('show');
                }, 3000);
            }
        });
    </script>
</body>
</html>