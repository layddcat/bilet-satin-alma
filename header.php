<?php
if (session_status() == PHP_SESSION_NONE) 
{
    session_start();
}
$path_prefix = '';
if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) 
{
    $path_prefix = '../';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BSAP - Bilet Satın Alma Platformu</title>
    <style>

        body {
           
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
           
             background-color: #eef2f7; 
            background-image: repeating-linear-gradient(
                -45deg,
                rgba(255, 255, 255, 0.05),
                rgba(255, 255, 255, 0.05) 10px,
                transparent 10px,
                transparent 20px
            );
            margin: 0;
            color: #34495e;
            padding-top: 80px;
        }

        .navbar {
            background-color: #2c3e50;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1), inset 0 -2px 0 rgba(0,0,0,0.1);
            padding: 1.2rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-sizing: border-box;
            border-bottom: 3px solid #3498db;
        }
        .navbar-left {
             display: flex;
             align-items: center;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.7rem;
            color: #ecf0f1;
            text-decoration: none;
            margin-right: 2.5rem;
        }
        .navbar-nav {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
        }
        .nav-item {
            margin-left: 1.5rem;
        }
        .nav-link {
            text-decoration: none;
            color: #bdc3c7;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 5px;
            transition: color 0.3s ease, background-color 0.3s ease;
        }
        .nav-link:hover {
            color: #ffffff;
            background-color: rgba(236, 240, 241, 0.1);
        }
        .nav-welcome {
            color: #95a5a6;
            margin-right: 1.8rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-left">
            <a href="<?php echo $path_prefix; ?>index.php" class="navbar-brand">BSAP</a>
            <ul class="navbar-nav">
                 <li class="nav-item">
                    <a href="<?php echo $path_prefix; ?>index.php" class="nav-link">Ana Sayfa</a>
                </li>
            </ul>
        </div>
        <ul class="navbar-nav">  
            <?php if (isset($_SESSION['user_id'])): ?>

                <li class="nav-item nav-welcome">Hoş geldin, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</li>

                <?php if ($_SESSION['role'] == 'company' || $_SESSION['role'] == 'admin'): ?>
                    <li class="nav-item">
                        <a href="<?php echo $path_prefix; ?>admin/index.php" class="nav-link">Yönetim Paneli</a>
                    </li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'user'): ?>
                <li class="nav-item">
                    <a href="<?php echo $path_prefix; ?>account.php" class="nav-link">Hesabım</a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $path_prefix; ?>my_tickets.php" class="nav-link">Biletlerim</a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a href="<?php echo $path_prefix; ?>logout.php" class="nav-link">Çıkış Yap</a>
                </li>

            <?php else: ?>

                <li class="nav-item">
                    <a href="<?php echo $path_prefix; ?>login.php" class="nav-link">Giriş Yap</a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $path_prefix; ?>register.php" class="nav-link">Kayıt Ol</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

