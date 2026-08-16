<?php
include_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$successMsg = "";
$errorMsg = "";

// Handle Cancel Ride by Driver (Releases Seats & Notifies Passengers)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_ride_id'])) {
    $ride_id = (int)$_POST['cancel_ride_id'];
    
    $cStmt = $conn->prepare("UPDATE rides SET trip_status = 'cancelled' WHERE id = ? AND user_id = ?");
    $cStmt->bind_param("ii", $ride_id, $user_id);
    if ($cStmt->execute()) {
        $bUp = $conn->prepare("UPDATE bookings SET trip_status = 'Cancelled' WHERE ride_id = ?");
        $bUp->bind_param("i", $ride_id);
        $bUp->execute();

        $passengers = $conn->query("SELECT user_id FROM bookings WHERE ride_id = $ride_id");
        while ($p = $passengers->fetch_assoc()) {
            $nStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Ride Cancelled by Driver', 'The driver has cancelled this ride. Any payment will be refunded.')");
            $nStmt->bind_param("i", $p['user_id']);
            $nStmt->execute();
        }

        $successMsg = "Ride has been cancelled and booked passengers have been notified.";
    }
}

// Fetch unread notifications for Driver
$notifStmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notifStmt->bind_param("i", $user_id);
$notifStmt->execute();
$notifications = $notifStmt->get_result();

// Fetch rides offered by this driver
$stmt = $conn->prepare("SELECT r.*, 
    (SELECT COUNT(*) FROM bookings b WHERE b.ride_id = r.id AND b.trip_status != 'Cancelled') as booked_count 
    FROM rides r WHERE r.user_id = ? ORDER BY r.ride_date DESC, r.ride_time DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_rides = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Offered Rides — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
    <style>
        .trip-mgmt-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 16px;
            display: grid;
            grid-template-columns: 1fr 240px;
            gap: 20px;
            align-items: center;
            transition: all 0.25s ease;
        }
        .trip-mgmt-card:hover {
            border-color: var(--border-strong);
            box-shadow: var(--shadow-md);
        }

        @media (max-width: 768px) {
            .trip-mgmt-card {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .trip-mgmt-card .action-box {
                border-top: 1px solid var(--border-subtle);
                padding-top: 14px;
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 30px 0;">
    <div class="fr-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:14px;">
            <div>
                <h1 style="font-size:26px; font-weight:800; color:var(--text-main); display:flex; align-items:center; gap:8px;">
                    <i class='bx bxs-car-garage' style="color:var(--primary);"></i> Driver Garage: Offered Rides
                </h1>
                <p style="font-size:14px; color:var(--text-muted); margin-top:2px;">Manage your published trips and passenger bookings.</p>
            </div>
            <a href="post_ride.php" class="fr-btn fr-btn-primary"><i class='bx bx-plus-circle'></i> Offer New Commute</a>
        </div>

        <?php if ($successMsg): ?>
            <div style="background:var(--eco-bg); color:var(--eco); border:1px solid var(--eco-border); padding:12px 18px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:600; font-size:14px;">
                ✅ <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Bar -->
        <div class="fr-card" style="padding:16px 20px; margin-bottom:24px;">
            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <div style="flex:1; min-width:240px; position:relative;">
                    <input type="text" id="tripSearch" class="fr-input" placeholder="Search by city, date or destination..." onkeyup="filterTrips()">
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="fr-btn fr-btn-primary fr-btn-sm filter-btn active" onclick="setFilter('all', this)">All</button>
                    <button type="button" class="fr-btn fr-btn-ghost fr-btn-sm filter-btn" onclick="setFilter('active', this)">Active</button>
                    <button type="button" class="fr-btn fr-btn-ghost fr-btn-sm filter-btn" onclick="setFilter('completed', this)">Completed</button>
                    <button type="button" class="fr-btn fr-btn-ghost fr-btn-sm filter-btn" onclick="setFilter('cancelled', this)">Cancelled</button>
                </div>
            </div>
        </div>

        <!-- Rides List -->
        <?php if ($my_rides->num_rows > 0): ?>
            <div id="ridesList">
                <?php while ($ride = $my_rides->fetch_assoc()): ?>
                    <?php 
                        $offeredSeats = max(1, (int)$ride['seats_available']);
                        $bookedSeats = (int)$ride['booked_count'];
                        
                        $rideDateTime = strtotime($ride['ride_date'] . ' ' . $ride['ride_time']);
                        $isPassed = ($rideDateTime < time());
                        $rawStatus = strtolower($ride['trip_status'] ?? 'active');
                        if ($rawStatus === 'cancelled') {
                            $computedStatus = 'cancelled';
                        } elseif ($isPassed || $rawStatus === 'completed') {
                            $computedStatus = 'completed';
                        } else {
                            $computedStatus = 'active';
                        }

                        $waText = "FlexiRide Trip Offer\n" .
                                  "Pickup: " . $ride['origin'] . "\n" .
                                  "Drop: " . $ride['destination'] . "\n" .
                                  "Date: " . $ride['ride_date'] . " @ " . date('h:i A', strtotime($ride['ride_time'])) . "\n" .
                                  "Fare: Rs. " . number_format($ride['price'], 2) . "/seat";
                        $waUrl = "https://api.whatsapp.com/send?text=" . urlencode($waText);
                    ?>
                    <div class="trip-mgmt-card" data-status="<?php echo $computedStatus; ?>">
                        <div>
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                                <?php if ($computedStatus === 'cancelled'): ?>
                                    <span class="fr-badge fr-badge-danger">Cancelled</span>
                                <?php elseif ($computedStatus === 'completed'): ?>
                                    <span class="fr-badge" style="background:var(--bg-input); color:var(--text-muted); border:1px solid var(--border-subtle);">Completed</span>
                                <?php else: ?>
                                    <span class="fr-badge fr-badge-eco">Active Trip</span>
                                <?php endif; ?>
                                <span style="font-size:13px; color:var(--text-muted);"><i class='bx bx-calendar'></i> <?php echo $ride['ride_date']; ?> at <?php echo date('h:i A', strtotime($ride['ride_time'])); ?></span>
                            </div>

                            <!-- Wayfinder Route Track -->
                            <div class="wayfinder-route" style="margin:10px 0;">
                                <div class="route-stop origin">
                                    <div class="stop-beacon"></div>
                                    <div class="stop-name"><?php echo htmlspecialchars($ride['origin']); ?></div>
                                </div>
                                <div class="route-stop destination">
                                    <div class="stop-beacon"></div>
                                    <div class="stop-name"><?php echo htmlspecialchars($ride['destination']); ?></div>
                                </div>
                            </div>

                            <div style="font-size:13px; color:var(--text-muted); display:flex; align-items:center; gap:12px; margin-top:8px;">
                                <span><i class='bx bxs-car'></i> <?php echo htmlspecialchars($ride['vehicle_model'] ?: $ride['vehicle_category']); ?></span>
                                <span>•</span>
                                <span style="color:var(--primary); font-weight:700;"><i class='bx bxs-user-check'></i> <?php echo $bookedSeats; ?> booked / <?php echo $offeredSeats; ?> offered</span>
                            </div>
                        </div>

                        <div class="action-box" style="text-align:right;">
                            <div style="font-size:24px; font-weight:800; color:var(--eco); margin-bottom:8px;">₹<?php echo number_format($ride['price'], 2); ?></div>
                            
                            <div style="display:flex; flex-wrap:wrap; gap:6px; justify-content:flex-end;">
                                <a href="ride_details.php?id=<?php echo $ride['id']; ?>" class="fr-btn fr-btn-ghost fr-btn-sm">
                                    <i class='bx bx-file-find'></i> Details
                                </a>

                                <?php if ($computedStatus === 'active'): ?>
                                    <a href="<?php echo $waUrl; ?>" target="_blank" class="fr-btn fr-btn-eco fr-btn-sm" title="Share WhatsApp">
                                        <i class='bx bxl-whatsapp'></i> Share
                                    </a>
                                    <?php if ($ride['booked_count'] > 0): ?>
                                        <a href="chat.php?ride_id=<?php echo $ride['id']; ?>" class="fr-btn fr-btn-primary fr-btn-sm">
                                            <i class='bx bx-message-rounded-dots'></i> Chat
                                        </a>
                                    <?php endif; ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this offered ride? Passengers will be refunded.');">
                                        <input type="hidden" name="cancel_ride_id" value="<?php echo $ride['id']; ?>">
                                        <button type="submit" class="fr-btn fr-btn-danger fr-btn-sm"><i class='bx bx-trash'></i> Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="fr-card" style="text-align:center; padding:50px 20px;">
                <i class='bx bxs-car' style="font-size:48px; color:var(--primary); margin-bottom:14px;"></i>
                <h3 style="font-size:20px; font-weight:700; color:var(--text-main); margin-bottom:6px;">No Offered Rides Yet</h3>
                <p style="font-size:14px; color:var(--text-muted); max-width:400px; margin:0 auto 20px;">Share empty pillion or car seats on your daily commute to save petrol costs.</p>
                <a href="post_ride.php" class="fr-btn fr-btn-primary">Offer Your First Ride Now</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    let currentFilter = 'all';
    function setFilter(status, btn) {
        currentFilter = status;
        document.querySelectorAll('.filter-btn').forEach(b => {
            b.classList.remove('fr-btn-primary', 'active');
            b.classList.add('fr-btn-ghost');
        });
        btn.classList.add('fr-btn-primary', 'active');
        btn.classList.remove('fr-btn-ghost');
        filterTrips();
    }

    function filterTrips() {
        const q = document.getElementById('tripSearch').value.toLowerCase();
        const cards = document.querySelectorAll('.trip-mgmt-card');
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            const status = card.getAttribute('data-status');
            const matchesSearch = text.includes(q);
            const matchesStatus = (currentFilter === 'all') || (status === currentFilter);
            card.style.display = (matchesSearch && matchesStatus) ? 'grid' : 'none';
        });
    }
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
