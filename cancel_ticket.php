<?php

session_start();
require_once 'db.php'; 
date_default_timezone_set('Europe/Istanbul');

if (!isset($_SESSION['user_id'])) 
{
    header("Location: login.php");
    exit();
}
if (!isset($_GET['ticket_id'])) 
{
    header("Location: my_tickets.php?error=missing_id");
    exit();
}
$ticket_id = $_GET['ticket_id'];
$user_id = $_SESSION['user_id'];
$sql = null; 
try
{
    $sql = "SELECT Tickets.id, Tickets.total_price, Trips.departure_time
            FROM Tickets
            JOIN Trips ON Tickets.trip_id = Trips.id
            WHERE Tickets.id = :ticket_id
              AND Tickets.user_id = :user_id
              AND Tickets.status = 'active'";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':ticket_id', $ticket_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $ticket = $stmt->fetch();
    if (!$ticket) 
    {
        header("Location: my_tickets.php?error=invalid_ticket");
        exit();
    }
    $departure_timestamp = strtotime($ticket['departure_time']); 
    $current_timestamp = time();
    $one_hour_in_seconds = 3600;
    $time_difference = $departure_timestamp - $current_timestamp;
    if ($time_difference <= $one_hour_in_seconds) 
    {
        header("Location: my_tickets.php?error=time_limit_exceeded");
        exit();
    }
    $db->beginTransaction();
    $sql_user_update = "UPDATE User SET balance = balance + :price WHERE id = :user_id";
    $stmt_user = $db->prepare($sql_user_update);
    $stmt_user->bindParam(':price', $ticket['total_price']);
    $stmt_user->bindParam(':user_id', $user_id);
    $stmt_user->execute();
    $sql_ticket_update = "UPDATE Tickets SET status = 'canceled' WHERE id = :ticket_id";
    $stmt_ticket = $db->prepare($sql_ticket_update);
    $stmt_ticket->bindParam(':ticket_id', $ticket_id);
    $stmt_ticket->execute();
    $db->commit();

    header("Location: my_tickets.php?status=cancelled");
    exit();

} 
catch (PDOException $e) 
{
    if ($db->inTransaction()) 
    {
        $db->rollBack();
    }
    die("Bilet iptal işlemi sırasında bir veritabanı hatası oluştu: " . $e->getMessage() . "<br>SQL Sorgusu (Tahmini): " . ($sql ?? 'Bilinmiyor'));
} 
catch (Exception $e) 
{
     die("Bilet iptal işlemi sırasında bir hata oluştu: " . $e->getMessage());
}
?>

