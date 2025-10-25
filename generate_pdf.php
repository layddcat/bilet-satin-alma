<?php
session_start();
require_once 'db.php';

require_once 'tfpdf/tfpdf.php';
if (!isset($_SESSION['user_id'])) 
{
    die("Bu sayfaya erişim yetkiniz yok. Lütfen giriş yapın.");
}
if (!isset($_GET['ticket_id'])) 
{
    die("Hata: Bilet ID'si belirtilmemiş.");
}
$ticket_id = $_GET['ticket_id'];
$user_id = $_SESSION['user_id'];
try 
{
    $sql = "SELECT
                Tickets.id AS ticket_id,
                Tickets.total_price,
                User.full_name AS passenger_name,
                Trips.departure_city,
                Trips.destination_city,
                Trips.departure_time,
                Bus_Company.name AS company_name,
                Booked_Seats.seat_number
            FROM Tickets
            JOIN User ON Tickets.user_id = User.id
            JOIN Trips ON Tickets.trip_id = Trips.id
            JOIN Bus_Company ON Trips.company_id = Bus_Company.id
            JOIN Booked_Seats ON Booked_Seats.ticket_id = Tickets.id
            WHERE Tickets.id = :ticket_id AND Tickets.user_id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':ticket_id', $ticket_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $ticket_details = $stmt->fetch();
    if (!$ticket_details) 
    {
        die("Hata: Bilet bulunamadı veya bu bilet size ait değil.");
    }
} 
catch (PDOException $e) 
{
    die("Veritabanı hatası: " . $e->getMessage());
}

$pdf = new tFPDF();
$pdf->AddPage();
$pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
$pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
$pdf->SetFont('DejaVu', 'B', 24);
$pdf->Cell(0, 20, 'Elektronik Otobüs Bileti', 0, 1, 'C');
$pdf->Ln(10);
$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Yolcu Adı:', 0, 0);
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(0, 10, $ticket_details['passenger_name'], 0, 1);
$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Firma:', 0, 0);
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(0, 10, $ticket_details['company_name'], 0, 1);
$pdf->Ln(5);
$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX() + 190, $pdf->GetY());
$pdf->Ln(5);
$pdf->SetFont('DejaVu', 'B', 16);
$pdf->Cell(95, 15, $ticket_details['departure_city'], 1, 0, 'C');
$pdf->Cell(0, 15, $ticket_details['destination_city'], 1, 1, 'C');
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(95, 10, 'Kalkış Yeri', 0, 0, 'C');
$pdf->Cell(0, 10, 'Varış Yeri', 0, 1, 'C');
$pdf->Ln(10);
$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Kalkış Zamanı:', 0, 0);
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(0, 10, date('d M Y, H:i', strtotime($ticket_details['departure_time'])), 0, 1);
$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Koltuk No:', 0, 0);
$pdf->SetFont('DejaVu', 'B', 16);
$pdf->Cell(0, 10, $ticket_details['seat_number'], 0, 1);
$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Fiyat:', 0, 0);
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(0, 10, $ticket_details['total_price'] . ' TL', 0, 1);
$pdf->Ln(10);
$pdf->SetFont('DejaVu', '', 10);
$pdf->Cell(0, 10, 'İyi yolculuklar dileriz!', 0, 1, 'C');
$pdf_filename = "bilet_" . $ticket_details['ticket_id'] . ".pdf";
$pdf->Output('D', $pdf_filename);
exit();
?>

