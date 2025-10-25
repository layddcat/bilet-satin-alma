<?php
require_once 'header.php';
require_once 'db.php';

if (!isset($_SESSION['user_id'])) 
{
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$tickets = [];
try 
{
    $sql = "SELECT
                Tickets.id AS ticket_id,
                Tickets.status,
                Trips.departure_city,
                Trips.destination_city,
                Trips.departure_time,
                Bus_Company.name AS company_name,
                Booked_Seats.seat_number,
                Tickets.total_price
            FROM Tickets
            JOIN Trips ON Tickets.trip_id = Trips.id
            JOIN Bus_Company ON Trips.company_id = Bus_Company.id
            JOIN Booked_Seats ON Booked_Seats.ticket_id = Tickets.id
            WHERE Tickets.user_id = :user_id
            ORDER BY Trips.departure_time DESC";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $tickets = $stmt->fetchAll();

} 
catch (PDOException $e) 
{
    die("Veritabanı hatası: Biletleriniz getirilemedi. " . $e->getMessage());
}

?>
<style>
    .main-container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
    .main-container h1 { margin-bottom: 2rem; border-bottom: 2px solid #eee; padding-bottom: 0.5rem; }
    .ticket-card {
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
        display: flex;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .ticket-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .ticket-info { padding: 1.5rem; flex-grow: 1; }
    .ticket-route { font-size: 1.5rem; font-weight: 600; margin: 0 0 0.5rem 0; }
    .ticket-details { color: #555; margin-bottom: 1rem; }
    .ticket-actions {
        background-color: #f7f7f7;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-width: 180px;
        border-left: 1px solid #eee;
    }
    .ticket-seat { font-size: 2rem; font-weight: bold; color: #007bff; }
    .ticket-seat span { font-size: 0.9rem; font-weight: normal; color: #666; display: block; }
    .btn-action {
        display: inline-block;
        text-decoration: none;
        padding: 0.5rem 1rem;
        margin-top: 0.5rem;
        border-radius: 5px;
        color: white;
        text-align: center;
        width: 100%;
    }
    .btn-cancel { background-color: #dc3545; }
    .btn-pdf { background-color: #17a2b8; }
    .btn-disabled { background-color: #6c757d; cursor: not-allowed; opacity: 0.7; }
    .no-tickets { text-align: center; font-size: 1.2rem; color: #888; padding: 3rem; background-color: #fff; border-radius: 12px; }

    .status-badge {
        display: inline-block;
        padding: 0.25em 0.6em;
        font-size: 75%;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
        color: #fff;
    }
    .status-active { background-color: #28a745; }
    .status-canceled { background-color: #dc3545; }

    .message { padding: 1rem; margin-bottom: 1.5rem; border-radius: 6px; border: 1px solid transparent; }
    .message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
    .message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }

</style>

<div class="main-container">
    <h1>Biletlerim</h1>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'cancelled'): ?>
        <div class="message success">Biletiniz başarıyla iptal edildi ve ücret iadesi yapıldı.</div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] == 'time_limit_exceeded'): ?>
        <div class="message error">Hata: Seferin kalkışına 1 saatten az kaldığı için bilet iptal edilemez.</div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] == 'invalid_ticket'): ?>
        <div class="message error">Hata: Geçersiz veya size ait olmayan bir bilet işlemi denendi.</div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] == 'missing_id'): ?>
        <div class="message error">Hata: İşlem için gerekli bilet ID'si bulunamadı.</div>
    <?php endif; ?>

    <?php if (empty($tickets)): ?>
        <div class="no-tickets">
            <p>Henüz satın alınmış biletiniz bulunmuyor.</p>
            <a href="index.php" class="btn-action" style="background-color: #007bff; width: auto;">Hemen Bilet Al</a>
        </div>
    <?php else: ?>
        <?php foreach ($tickets as $ticket): ?>
            <div class="ticket-card">
                <div class="ticket-info">
                    <h2 class="ticket-route">
                        <?php echo htmlspecialchars($ticket['departure_city']); ?> &rarr; <?php echo htmlspecialchars($ticket['destination_city']); ?>
                    </h2>
                    <p class="ticket-details">
                        <strong>Firma:</strong> <?php echo htmlspecialchars($ticket['company_name']); ?><br>
                        <strong>Kalkış Zamanı:</strong> <?php echo date('d M Y, H:i', strtotime($ticket['departure_time'])); ?><br>
                        <strong>Ödenen Tutar:</strong> <?php echo htmlspecialchars($ticket['total_price']); ?> TL
                    </p>
                    <?php if ($ticket['status'] == 'active'): ?>
                        <span class="status-badge status-active">Aktif</span>
                    <?php elseif ($ticket['status'] == 'canceled'): ?>
                        <span class="status-badge status-canceled">İptal Edilmiş</span>
                    <?php endif; ?>
                </div>
                <div class="ticket-actions">
                    <div class="ticket-seat">
                        <?php echo htmlspecialchars($ticket['seat_number']); ?>
                        <span>Koltuk No</span>
                    </div>
                    
                    <?php
                        $can_cancel = false;
                        if ($ticket['status'] == 'active') {
                            $departure_timestamp = strtotime($ticket['departure_time']);
                            date_default_timezone_set('Europe/Istanbul');
                            $current_timestamp = time();
                            if (($departure_timestamp - $current_timestamp) > 3600) { 
                                $can_cancel = true;
                            }
                        }
                    ?>

                    <?php if ($can_cancel): ?>
                        <a href="cancel_ticket.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn-action btn-cancel" onclick="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz?');">İptal Et</a>
                    <?php else: ?>
                        <a href="#" class="btn-action btn-disabled">İptal Edilemez</a>
                    <?php endif; ?>

                    <a href="generate_pdf.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn-action btn-pdf">PDF İndir</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>

