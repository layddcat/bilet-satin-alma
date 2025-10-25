<?php
require_once 'header.php'; 
require_once 'db.php';

if (!isset($_SESSION['user_id'])) 
{
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] != 'user') 
{
    header("Location: admin/index.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$user_info = null;
try 
{
    $sql = "SELECT full_name, email, balance FROM User WHERE id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_info = $stmt->fetch();
    if (!$user_info) 
    {
        session_destroy();
        header("Location: login.php?error=user_not_found");
        exit();
    }
} 
catch (PDOException $e) 
{
    die("Kullanıcı bilgileri getirilirken veritabanı hatası oluştu: " . $e->getMessage());
}

?>
<style>
    .main-container { max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
    .account-info 
    {
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        padding: 2rem;
    }
    .account-info h1 {
        margin-top: 0;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid #eee;
        padding-bottom: 1rem;
        font-size: 1.8rem;
    }
    .info-item {
        margin-bottom: 1rem;
        font-size: 1.1rem;
        color: #555;
    }
    .info-item strong {
        color: #333;
        min-width: 120px; 
        display: inline-block;
    }
    .balance-highlight {
        font-size: 1.5rem;
        font-weight: bold;
        color: #28a745; 
    }
</style>

<div class="main-container">
    <div class="account-info">
        <h1>Hesabım</h1>

        <div class="info-item">
            <strong>Ad Soyad:</strong> <?php echo htmlspecialchars($user_info['full_name']); ?>
        </div>
        <div class="info-item">
            <strong>E-posta:</strong> <?php echo htmlspecialchars($user_info['email']); ?>
        </div>
        <div class="info-item">
            <strong>Güncel Bakiye:</strong>
            <span class="balance-highlight"><?php echo htmlspecialchars(number_format($user_info['balance'], 2)); ?> TL</span>
        </div>
    </div>
</div>

</body>
</html>
