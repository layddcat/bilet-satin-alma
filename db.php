<?php
$db_file = __DIR__ . '/bsap.db';
try 
{
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} 
catch (PDOException $e) 
{
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

?>