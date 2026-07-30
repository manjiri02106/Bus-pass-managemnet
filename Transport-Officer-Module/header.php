<?php
// header.php - Main header component
require_once __DIR__ . '/auth_helpers.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Pass Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Load custom original style for tables/cards -->
    <link rel="stylesheet" href="style.css">
    
    <!-- Load Staradmin theme wrapper -->
    <link rel="stylesheet" href="assets/staradmin.css">
    
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header>
        <div class="header-brand">
            <div class="logo-icon">BP</div>
            <div class="header-title">
                <h1>Bus Pass Portal</h1>
                <span>Student Transportation Services</span>
            </div>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="header-user">
                <div class="user-badge">
                    <i class="fa-regular fa-user"></i>
                    <span><strong>
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </strong></span>
                    <span class="user-role">
                        <?php echo htmlspecialchars($_SESSION['user_role']); ?>
                    </span>
                </div>
                <form action="logout.php" method="POST" style="margin: 0;">
                    <button type="submit" class="logout-btn">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </header>
    <div class="app-container">