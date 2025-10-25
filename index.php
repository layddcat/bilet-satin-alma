<?php

require_once 'header.php';
require_once 'db.php';

date_default_timezone_set('Europe/Istanbul');

$trips = [];
try 
{
    $sql = "SELECT Trips.*, Bus_Company.name AS company_name
            FROM Trips
            JOIN Bus_Company ON Trips.company_id = Bus_Company.id
            WHERE Trips.departure_time > datetime('now', 'localtime')
            ORDER BY Trips.departure_time ASC";

    $stmt = $db->query($sql);
    $trips = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Seferler listelenirken hata oluştu: " . $e->getMessage());
}

?>
<style>
    .main-container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
    .main-container h1 { margin-bottom: 2rem; border-bottom: 1px solid #eee; padding-bottom: 1rem;}
    .message { padding: 1rem; margin-bottom: 1.5rem; border-radius: 6px; border: 1px solid transparent; }
    .message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    .no-trips { text-align: center; font-size: 1.1rem; color: #888; padding: 3rem; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); }

    .trip-list { list-style: none; padding: 0; }
    .trip-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .trip-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .trip-details { flex-grow: 1; margin-right: 1rem; }
    .trip-route { font-size: 1.3rem; font-weight: 600; margin: 0 0 0.5rem 0; }
    .trip-company, .trip-time { color: #555; margin-bottom: 0.3rem; font-size: 0.95rem; }
    .trip-price { font-size: 1.2rem; font-weight: bold; color: #007bff; margin-right: 1.5rem; white-space: nowrap; }
    .btn-details { background-color: #17a2b8; color: white; padding: 0.6rem 1.2rem; text-decoration: none; border-radius: 5px; font-weight: 500; white-space: nowrap; }

    @media (max-width: 600px) {
        .trip-card { flex-direction: column; align-items: flex-start; }
        .trip-price { margin-right: 0; margin-top: 1rem; }
        .btn-details { margin-top: 1rem; width: 100%; text-align: center; }
        .trip-actions { display: flex; justify-content: space-between; align-items: center; width: 100%;}
    }

</style>

<div class="main-container">
    <h1>Aktif Seferler</h1>

    <?php if (isset($_GET['error']) && $_GET['error'] == 'trip_in_past'): ?>
        <div class="message error">Hata: Geçmiş tarihli bir sefer için bilet alamazsınız.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] == 'trip_not_found'): ?>
        <div class="message error">Hata: Belirtilen sefer bulunamadı veya artık aktif değil.</div>
    <?php endif; ?>

    <?php if (empty($trips)): ?>
        <div class="no-trips"> Şu anda aktif sefer bulunmamaktadır.</div>
    <?php else: ?>
        <ul class="trip-list">
            <?php foreach ($trips as $trip): ?>
                <li class="trip-card">
                    <div class="trip-details">
                        <div class="trip-route">
                            <?php echo htmlspecialchars($trip['departure_city']); ?> &rarr; <?php echo htmlspecialchars($trip['destination_city']); ?>
                        </div>
                        <div class="trip-company">
                            <?php echo htmlspecialchars($trip['company_name']); ?>
                        </div>
                        <div class="trip-time">
                            <?php echo date('d M Y, H:i', strtotime($trip['departure_time'])); ?>
                        </div>
                    </div>
                    <div class="trip-actions">
                         <span class="trip-price"><?php echo htmlspecialchars($trip['price']); ?> TL</span>
                         <a href="buy_ticket.php?trip_id=<?php echo $trip['id']; ?>" class="btn-details">Bileti İncele</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

</body>
</html>

