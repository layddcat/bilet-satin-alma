<?php
session_start();
require_once 'db.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    $email = $_POST['email'];
    $password = $_POST['password'];

    try 
    {
        $sql = "SELECT * FROM User WHERE email = :email";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) 
        {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            if ($user['role'] == 'admin' || $user['role'] == 'company') 
            {
                header("Location: admin/index.php");
            } 
            else 
            {
                header("Location: index.php");
            }
            exit(); 

        } 
        else 
        {
            $message = "Hatalı e-posta veya şifre.";
        }

    } 
    catch (PDOException $e) 
    {
        $message = "Veritabanı hatası: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - BSAP</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: linear-gradient(120deg, #fdfbfb 0%, #ebedee 100%); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .login-container { background-color: #fff; padding: 2.5rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); width: 360px; animation: fadeInDown 0.6s ease-out; }
        .login-container h2 { text-align: center; margin-top: 0; margin-bottom: 2rem; color: #222; font-weight: 600; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #555; font-weight: 500; }
        .form-group input { width: 100%; padding: 0.85rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.3s ease, box-shadow 0.3s ease; }
        .form-group input:focus { outline: none; border-color: #007bff; box-shadow: 0 0 10px rgba(0, 123, 255, 0.2); }
        .btn { width: 100%; padding: 0.85rem; background: linear-gradient(to right, #007bff, #0056b3); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1.1rem; font-weight: 600; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .btn:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0, 123, 255, 0.3); }
        .message { margin-bottom: 1rem; padding: 0.75rem; border-radius: 6px; text-align: center; font-size: 0.95rem; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .register-link { text-align: center; margin-top: 1.5rem; color: #666; font-size: 0.95rem; }
        .register-link a { color: #007bff; text-decoration: none; font-weight: 600; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>Giriş Yap</h2>

        <?php if (!empty($message)): ?>
            <div class="message error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" novalidate>
            <div class="form-group">
                <label for="email">E-posta Adresi</label>
                <input type="email" id="email" name="email" required autocomplete="off">
            </div>
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" required autocomplete="off">
            </div>
            <button type="submit" class="btn">Giriş Yap</button>
        </form>
        
        <p class="register-link">
            Hesabınız yok mu? <a href="register.php">Kayıt Olun</a>
        </p>
    </div>

</body>
</html>

