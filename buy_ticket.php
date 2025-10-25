<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

date_default_timezone_set('Europe/Istanbul');

$message = '';
$message_type = '';
$coupon_code_input = ''; 
$applied_coupon = null;   
$discount_rate = 0.0;
$discounted_price = null;
$selected_seat = null; 
$show_confirmation = false; 

if (!isset($_SESSION['user_id'])) 
{
    header("Location: login.php?error=login_required");
    exit();
}
$user_id = $_SESSION['user_id'];
if (!isset($_GET['trip_id'])) 
{
    header("Location: index.php");
    exit();
}
$trip_id = $_GET['trip_id'];
try 
{
    $sql_trip = "SELECT Trips.*, Bus_Company.name AS company_name FROM Trips JOIN Bus_Company ON Trips.company_id = Bus_Company.id WHERE Trips.id = :trip_id";
    $stmt_trip = $db->prepare($sql_trip);
    $stmt_trip->bindParam(':trip_id', $trip_id);
    $stmt_trip->execute();
    $trip = $stmt_trip->fetch();
    if (!$trip) 
    {
        header("Location: index.php?error=trip_not_found");
        exit();
    }
    $departure_timestamp = strtotime($trip['departure_time']);
    $current_timestamp = time();
    if ($departure_timestamp < $current_timestamp) 
    {
        header("Location: index.php?error=trip_in_past");
        exit();
    }
    $sql_seats = "SELECT seat_number FROM Booked_Seats JOIN Tickets ON Booked_Seats.ticket_id = Tickets.id WHERE Tickets.trip_id = :trip_id";
    $stmt_seats = $db->prepare($sql_seats);
    $stmt_seats->bindParam(':trip_id', $trip_id);
    $stmt_seats->execute();
    $booked_seats = $stmt_seats->fetchAll(PDO::FETCH_COLUMN);
    $stmt_balance = $db->prepare("SELECT balance FROM User WHERE id = :user_id");
    $stmt_balance->bindParam(':user_id', $user_id);
    $stmt_balance->execute();
    $user_balance = $stmt_balance->fetchColumn();

} 
catch (PDOException $e) 
{
    die("Veritabanı hatası: " . $e->getMessage());
}

$original_price = $trip['price'];
$final_price = $original_price; 
if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    $selected_seat = $_POST['selected_seat'] ?? $_POST['selected_seat_preserved'] ?? null;
    $coupon_code_input = strtoupper(trim($_POST['coupon_code'] ?? $_POST['coupon_code_active'] ?? ''));
    if (isset($_POST['apply_coupon'])) 
    {
        if (!empty($coupon_code_input)) 
        {
             try 
             {
                $stmt_coupon = $db->prepare("SELECT * FROM Coupons WHERE code = :code");
                $stmt_coupon->bindParam(':code', $coupon_code_input);
                $stmt_coupon->execute();
                $coupon = $stmt_coupon->fetch();

                if ($coupon) 
                {
                    $is_expired = strtotime($coupon['expire_date']) < $current_timestamp;
                    $is_limit_reached = $coupon['usage_count'] >= $coupon['usage_limit'];
                    $stmt_check_usage = $db->prepare("SELECT COUNT(*) FROM User_Coupons WHERE user_id = :user_id AND coupon_id = :coupon_id");
                    $stmt_check_usage->bindParam(':user_id', $user_id);
                    $stmt_check_usage->bindParam(':coupon_id', $coupon['id']);
                    $stmt_check_usage->execute();
                    $has_user_used = $stmt_check_usage->fetchColumn() > 0;
                    if ($is_expired) 
                    { $message = "Bu kuponun süresi dolmuş."; $message_type = 'error'; $applied_coupon = null;}
                    elseif ($is_limit_reached) 
                    { $message = "Bu kupon kullanım limitine ulaşmış."; $message_type = 'error'; $applied_coupon = null;}
                    elseif ($has_user_used) 
                    { $message = "Bu kuponu daha önce kullandınız."; $message_type = 'error'; $applied_coupon = null;}
                    else 
                    {
                        $discount_rate = $coupon['discount'];
                        $discounted_price = $original_price * (1 - $discount_rate);
                        $message = "%" . ($discount_rate * 100) . " indirim uygulandı!";
                        $message_type = 'success';
                        $applied_coupon = $coupon;
                        $final_price = $discounted_price;
                    }
                } 
                else 
                {
                    $message = "Geçersiz kupon kodu."; $message_type = 'error'; $applied_coupon = null;
                }
            } 
            catch (PDOException $e) 
            {
                 $message = "Kupon kontrolü sırasında hata: " . $e->getMessage(); $message_type = 'error'; $applied_coupon = null;
            }
        } 
        else 
        {
             $message = "Lütfen bir kupon kodu girin."; $message_type = 'error'; $applied_coupon = null;
        }
        if ($selected_seat && $message_type !== 'error') 
        {
            $show_confirmation = true;
        }
    } 
    elseif (isset($_POST['confirm_purchase'])) 
    {
        $selected_seat = $_POST['selected_seat'];
        $final_price = $_POST['final_price'];
        $applied_coupon_id = $_POST['applied_coupon_id'] ?? null;
         $stmt_seats_check = $db->prepare("SELECT COUNT(*) FROM Booked_Seats JOIN Tickets ON Booked_Seats.ticket_id = Tickets.id WHERE Tickets.trip_id = :trip_id AND Booked_Seats.seat_number = :seat");
         $stmt_seats_check->bindParam(':trip_id', $trip_id);
         $stmt_seats_check->bindParam(':seat', $selected_seat);
         $stmt_seats_check->execute();
         if ($stmt_seats_check->fetchColumn() > 0) 
        {
             $message = "Üzgünüz, siz işlemi onaylarken bu koltuk başkası tarafından alındı."; $message_type = 'error'; $show_confirmation = false; // Onayı gizle
        }
         elseif ($user_balance < $final_price) 
        {
             $message = "Yetersiz bakiye!"; $message_type = 'error'; $show_confirmation = true; 
             if($applied_coupon_id) 
            {
                try 
                {
                    $stmt_c = $db->prepare("SELECT * FROM Coupons WHERE id = :id");
                    $stmt_c->bindParam(':id', $applied_coupon_id);
                    $stmt_c->execute();
                    $applied_coupon = $stmt_c->fetch();
                    if ($applied_coupon) $discount_rate = $applied_coupon['discount'];
                } 
                catch (PDOException $e) {/* Hata yönetimi... */}
             }
         } else 
         {
            try 
            {
                $db->beginTransaction();
                $new_balance = $user_balance - $final_price;
                $stmt_update_user = $db->prepare("UPDATE User SET balance = :new_balance WHERE id = :user_id");
                $stmt_update_user->bindParam(':new_balance', $new_balance);
                $stmt_update_user->bindParam(':user_id', $user_id);
                $stmt_update_user->execute();
                $ticket_id = generate_uuid();
                $stmt_insert_ticket = $db->prepare("INSERT INTO Tickets (id, trip_id, user_id, total_price) VALUES (:id, :trip_id, :user_id, :price)");
                $stmt_insert_ticket->bindParam(':id', $ticket_id);
                $stmt_insert_ticket->bindParam(':trip_id', $trip_id);
                $stmt_insert_ticket->bindParam(':user_id', $user_id);
                $stmt_insert_ticket->bindParam(':price', $final_price);
                $stmt_insert_ticket->execute();
                $booked_seat_id = generate_uuid();
                $stmt_book_seat = $db->prepare("INSERT INTO Booked_Seats (id, ticket_id, seat_number) VALUES (:id, :ticket_id, :seat_number)");
                $stmt_book_seat->bindParam(':id', $booked_seat_id);
                $stmt_book_seat->bindParam(':ticket_id', $ticket_id);
                $stmt_book_seat->bindParam(':seat_number', $selected_seat);
                $stmt_book_seat->execute();
                if ($applied_coupon_id) 
                {
                    $user_coupon_id = generate_uuid();
                    $stmt_user_coupon = $db->prepare("INSERT INTO User_Coupons (id, user_id, coupon_id) VALUES (:id, :user_id, :coupon_id)");
                    $stmt_user_coupon->bindParam(':id', $user_coupon_id);
                    $stmt_user_coupon->bindParam(':user_id', $user_id);
                    $stmt_user_coupon->bindParam(':coupon_id', $applied_coupon_id);
                    $stmt_user_coupon->execute();
                    $stmt_update_coupon = $db->prepare("UPDATE Coupons SET usage_count = usage_count + 1 WHERE id = :coupon_id");
                    $stmt_update_coupon->bindParam(':coupon_id', $applied_coupon_id);
                    $stmt_update_coupon->execute();
                }
                $db->commit();
                header("Location: my_tickets.php?status=purchase_success&seat=" . $selected_seat);
                exit();
            } catch (Exception $e) 
            {
                $db->rollBack();
                $message = "Bilet alımı sırasında bir hata oluştu: " . $e->getMessage();
                $message_type = 'error';
                 if($applied_coupon_id) 
                 {
                    try 
                    {
                        $stmt_c = $db->prepare("SELECT * FROM Coupons WHERE id = :id");
                        $stmt_c->bindParam(':id', $applied_coupon_id);
                        $stmt_c->execute();
                        $applied_coupon = $stmt_c->fetch();
                        if($applied_coupon) $discount_rate = $applied_coupon['discount'];
                    }   catch (PDOException $ex) {/* Hata yönetimi... */}
                 }
                $show_confirmation = true;
            }
         }
    }
    elseif (isset($_POST['selected_seat'])) {
        $selected_seat = $_POST['selected_seat'];
        if (!empty($coupon_code_input)) 
        {
             try
             {
                $stmt_coupon = $db->prepare("SELECT * FROM Coupons WHERE code = :code");
                $stmt_coupon->bindParam(':code', $coupon_code_input);
                $stmt_coupon->execute();
                $coupon = $stmt_coupon->fetch();
                if ($coupon) 
                {
                    $is_expired = strtotime($coupon['expire_date']) < $current_timestamp;
                    $is_limit_reached = $coupon['usage_count'] >= $coupon['usage_limit'];
                    $stmt_check_usage = $db->prepare("SELECT COUNT(*) FROM User_Coupons WHERE user_id = :user_id AND coupon_id = :coupon_id");
                    $stmt_check_usage->bindParam(':user_id', $user_id);
                    $stmt_check_usage->bindParam(':coupon_id', $coupon['id']);
                    $stmt_check_usage->execute();
                    $has_user_used = $stmt_check_usage->fetchColumn() > 0;
                    if (!$is_expired && !$is_limit_reached && !$has_user_used) 
                    {
                        $applied_coupon = $coupon;
                        $discount_rate = $coupon['discount'];
                        $final_price = $original_price * (1 - $discount_rate);
                    } 
                    else { $coupon_code_input = ''; } 
                } 
                else { $coupon_code_input = ''; } 
            } 
        catch (PDOException $e) { /* Hata yönetimi... */ }
        }
        $show_confirmation = true; 
    }

} 
else 
{ 
     $applied_coupon = null;
     $discount_rate = 0.0;
}
if ($applied_coupon) 
{
    $final_price = $original_price * (1 - $discount_rate);
} 
else
{
    $final_price = $original_price;
    $discount_rate = 0.0; 
}

require_once 'header.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Satın Al - BSAP</title>
    <style>
        .main-container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .trip-info, .bus-container, .coupon-section, .confirmation-section { background-color: #fff; padding: 1.5rem 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); margin-bottom: 2rem; }
        .trip-info h1 { margin-top: 0; font-size: 1.8rem; border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem; }
        .trip-info h2 { font-size: 1.4rem; margin-bottom: 0.5rem;}
        .trip-info p { margin: 0.5rem 0; color: #555; }
        .price-display { font-size: 1.3rem; font-weight: bold; margin-top: 1rem; }
        .original-price { text-decoration: line-through; color: #999; margin-right: 0.5rem; font-size: 1.1rem;}
        .discounted-price { color: #28a745; }

        .coupon-section h3 { margin-top: 0; }
        .coupon-form { display: flex; gap: 10px; align-items: center; }
        .coupon-form input[type="text"] { flex-grow: 1; padding: 0.75rem; border: 1px solid #ccc; border-radius: 5px; font-size: 1rem; text-transform: uppercase;}
        .btn-apply-coupon { background-color: #ffc107; color: #333; padding: 0.75rem 1rem; border: none; border-radius: 5px; cursor: pointer; font-weight: 500;}

        .bus { border: 2px solid #ccc; border-radius: 20px 20px 10px 10px; padding: 20px; max-width: 320px; margin: 2rem auto; background-color: #f7f7f7; }
        .driver-cabin { text-align: right; padding-right: 10px; margin-bottom: 20px; }
        .driver-cabin svg { width: 40px; height: 40px; fill: #888; }
        .seat-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; }
        .seat-label { position: relative; width: 50px; height: 50px; }
        .seat-radio { opacity: 0; position: absolute; width: 100%; height: 100%; cursor: pointer; }
        .seat-display { width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; border-radius: 7px; font-weight: bold; transition: all 0.2s ease; border: 2px solid #aaa; background-color: #fff; color: #333; }
        .seat-booked { background-color: #e0e0e0; color: #9e9e9e; border-color: #bdbdbd; cursor: not-allowed !important; }
        .seat-radio:hover:not(:disabled) + .seat-display { background-color: #e9f5ff; border-color: #007bff;}
       
        .seat-selected { transform: scale(1.1); box-shadow: 0 0 15px rgba(40, 167, 69, 0.6); border-color: #28a745; background-color: #28a745; color: white; }
        .seat-radio:disabled + .seat-display { cursor: not-allowed; }
        .aisle { grid-column: 3; }

        .confirmation-section h3 { margin-top: 0; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1.5rem;}
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 0.8rem; font-size: 1.1rem; }
        .summary-item strong { color: #333; }
        .summary-item.total { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee; font-weight: bold; font-size: 1.3rem; color: #28a745;}
        .summary-item.balance { margin-top: 0.5rem; font-size: 1rem; color: #666;}
        .confirm-button-container { text-align: center; margin-top: 2rem; }
        .btn-confirm { background-color: #28a745; color: white; padding: 0.8rem 2.5rem; border-radius: 5px; font-size: 1.2rem; border: none; cursor: pointer; }

        .message { padding: 1rem; margin-bottom: 1.5rem; border-radius: 6px; border: 1px solid transparent; text-align: center; }
        .message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    </style>
</head>
<body>
<?php  ?>

<div class="main-container">
   
    <div class="trip-info">
        <h1>Bilet Satın Al</h1>
        <h2><?php echo htmlspecialchars($trip['departure_city']); ?> &rarr; <?php echo htmlspecialchars($trip['destination_city']); ?></h2>
        <p><strong>Firma:</strong> <?php echo htmlspecialchars($trip['company_name']); ?></p>
        <p><strong>Kalkış Zamanı:</strong> <?php echo date('d M Y, H:i', strtotime($trip['departure_time'])); ?></p>
        <div class="price-display">
            <?php if ($discount_rate > 0 && $applied_coupon):  ?>
                <span class="original-price"><?php echo htmlspecialchars($original_price); ?> TL</span>
                <span class="discounted-price"><?php echo htmlspecialchars(number_format($final_price, 2)); ?> TL</span>
                (<?php echo "%" . ($discount_rate * 100); ?> İndirimli)
            <?php else: ?>
                <span><?php echo htmlspecialchars($original_price); ?> TL</span>
            <?php endif; ?>
        </div>
    </div>

  
    <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

  
    <div class="coupon-section">
        <h3>İndirim Kuponu</h3>
        <form action="buy_ticket.php?trip_id=<?php echo htmlspecialchars($trip_id); ?>" method="POST" class="coupon-form">
           
            <input type="hidden" name="selected_seat_preserved" value="<?php echo htmlspecialchars($selected_seat); ?>">
            <input type="text" name="coupon_code" placeholder="Kupon Kodunuzu Girin" value="<?php echo htmlspecialchars($coupon_code_input); ?>">
            <button type="submit" name="apply_coupon" class="btn-apply-coupon">Uygula</button>
        </form>
    </div>

  
    <div class="bus-container">
        <h3 style="text-align:center;">Koltuk Seçimi</h3>
        
        <form id="seat-selection-form" action="buy_ticket.php?trip_id=<?php echo htmlspecialchars($trip_id); ?>" method="POST">
        
            <input type="hidden" name="coupon_code_active" value="<?php echo htmlspecialchars($coupon_code_input); ?>">
            <div class="bus">
                <div class="driver-cabin">
                    <svg viewBox="0 0 24 24"><path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z" /></svg>
                </div>
                <div class="seat-grid">
                    <?php for ($i = 1; $i <= $trip['capacity']; $i++): ?>
                        <?php
                            $is_booked = in_array($i, $booked_seats);
                            $seat_class = $is_booked ? 'seat-booked' : 'seat-empty';
                            if ($i % 4 == 3) { echo '<div class="aisle"></div>'; }
                            $is_checked = ($i == $selected_seat);
                            if($is_checked && !$is_booked) $seat_class .= ' seat-selected';
                        ?>
                        <label class="seat-label">
                            <input type="radio" name="selected_seat" value="<?php echo $i; ?>" class="seat-radio"
                                   <?php if ($is_booked) echo 'disabled'; ?>
                                   <?php if ($is_checked) echo 'checked'; ?>
                                   onchange="document.getElementById('seat-selection-form').submit();" >
                            <div class="seat-display <?php echo $seat_class; ?>"><?php echo $i; ?></div>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>
        
        </form>
    </div>

    <?php if ($show_confirmation && !empty($selected_seat) && !in_array($selected_seat, $booked_seats)): ?>
        <div class="confirmation-section">
            <h3>Ödeme Özeti</h3>
            <div class="summary-item">
                <span>Seçilen Koltuk:</span>
                <strong><?php echo htmlspecialchars($selected_seat); ?></strong>
            </div>
            <div class="summary-item">
                <span>Bilet Fiyatı:</span>
                <span><?php echo htmlspecialchars(number_format($original_price, 2)); ?> TL</span>
            </div>
            <?php if ($applied_coupon): ?>
                <div class="summary-item">
                    <span>Kupon İndirimi (<?php echo htmlspecialchars($applied_coupon['code']); ?>):</span>
                    <strong>- <?php echo htmlspecialchars(number_format($original_price - $final_price, 2)); ?> TL</strong>
                </div>
            <?php endif; ?>
             <div class="summary-item total">
                <span>Ödenecek Tutar:</span>
                <strong><?php echo htmlspecialchars(number_format($final_price, 2)); ?> TL</strong>
            </div>
            <div class="summary-item balance">
                <span>Mevcut Bakiyeniz:</span>
                <span><?php echo htmlspecialchars(number_format($user_balance, 2)); ?> TL</span>
            </div>
             <div class="summary-item balance">
                <span>Kalan Bakiyeniz:</span>
                <strong><?php echo htmlspecialchars(number_format($user_balance - $final_price, 2)); ?> TL</strong>
            </div>

            <?php if ($user_balance >= $final_price): ?>
                <div class="confirm-button-container">
                    <form action="buy_ticket.php?trip_id=<?php echo htmlspecialchars($trip_id); ?>" method="POST">
                        <input type="hidden" name="selected_seat" value="<?php echo htmlspecialchars($selected_seat); ?>">
                        <input type="hidden" name="final_price" value="<?php echo htmlspecialchars($final_price); ?>">
                        <?php if ($applied_coupon): ?>
                            <input type="hidden" name="applied_coupon_id" value="<?php echo htmlspecialchars($applied_coupon['id']); ?>">
                        <?php endif; ?>
                        <button type="submit" name="confirm_purchase" class="btn-confirm">Onayla ve Satın Al</button>
                    </form>
                </div>
            <?php else: ?>
                 <div class="message error" style="margin-top: 2rem;">Yetersiz Bakiye! Bu bileti satın alamazsınız.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>

