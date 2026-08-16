<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Check if the user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit();
}

$successMsg = "";
$errorMsg = "";

// Handle Verification Action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_user_id'])) {
    $target_uid = (int)$_POST['action_user_id'];
    $doc_type   = $_POST['doc_type'] ?? '';
    $action     = $_POST['action_type'] ?? '';

    $val = ($action === 'approve') ? 1 : 0;
    $col = '';

    if ($doc_type === 'aadhaar') $col = 'is_aadhaar_verified';
    elseif ($doc_type === 'dl') $col = 'is_dl_verified';
    elseif ($doc_type === 'college') $col = 'is_college_email_verified';
    elseif ($doc_type === 'upi') $col = 'is_upi_verified';

    if (!empty($col)) {
        $stmt = $conn->prepare("UPDATE users SET $col = ? WHERE id = ?");
        $stmt->bind_param("ii", $val, $target_uid);
        if ($stmt->execute()) {
            $statusTxt = ($action === 'approve') ? 'Approved & Verified' : 'Rejected';
            $nStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Identity Verification Update', 'Your $doc_type verification request has been $statusTxt by system admin.')");
            $nStmt->bind_param("i", $target_uid);
            $nStmt->execute();

            $successMsg = "User $doc_type verification status updated to {$statusTxt}!";
        } else {
            $errorMsg = "Database error: " . $conn->error;
        }
    }
}

// Fetch all users with document details
$usersStmt = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Documents Queue — Admin FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/flexiride.css">
    <style>
        .queue-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }
        .queue-table th, .queue-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-subtle);
        }
        .queue-table th {
            color: var(--text-muted);
            font-size: 11.5px;
            text-transform: uppercase;
            font-weight: 700;
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/../includes/admin_navbar.php'; ?>

<main class="page-content" style="padding: 30px 0;">
    <div class="fr-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
            <div>
                <h1 style="font-size:26px; font-weight:800; color:var(--text-main); display:flex; align-items:center; gap:8px;">
                    <i class='bx bxs-id-card' style="color:var(--primary);"></i> Trust Verification Queue
                </h1>
                <p style="font-size:14px; color:var(--text-muted); margin-top:2px;">Review Aadhaar, DL, and campus email credentials submitted by commuters.</p>
            </div>
            <a href="admin_dashboard.php" class="fr-btn fr-btn-ghost fr-btn-sm"><i class='bx bx-left-arrow-alt'></i> Operations Console</a>
        </div>

        <?php if ($successMsg): ?>
            <div style="background:var(--eco-bg); color:var(--eco); border:1px solid var(--eco-border); padding:12px 18px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:600;">
                ✅ <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div style="background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border); padding:12px 18px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:600;">
                ⚠️ <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <div class="fr-card" style="padding:0; overflow:hidden;">
            <div style="overflow-x:auto;">
                <table class="queue-table">
                    <thead>
                        <tr>
                            <th>Commuter</th>
                            <th>Aadhaar Number</th>
                            <th>Driving License</th>
                            <th>Campus Email</th>
                            <th>UPI VPA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = $usersStmt->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($u['name']); ?></div>
                                    <div style="font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($u['phone']); ?></div>
                                </td>

                                <!-- Aadhaar Cell -->
                                <td>
                                    <?php if (!empty($u['aadhaar_number'])): ?>
                                        <div style="font-family:monospace;"><?php echo htmlspecialchars($u['aadhaar_number']); ?></div>
                                        <div style="margin-top:4px;">
                                            <?php if ($u['is_aadhaar_verified'] ?? 0): ?>
                                                <span class="fr-badge fr-badge-eco" style="font-size:11px;">Verified</span>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action_user_id" value="<?php echo $u['id']; ?>">
                                                    <input type="hidden" name="doc_type" value="aadhaar">
                                                    <input type="hidden" name="action_type" value="reject">
                                                    <button type="submit" class="fr-btn fr-btn-ghost fr-btn-sm" style="padding:2px 6px; font-size:10px;">Revoke</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action_user_id" value="<?php echo $u['id']; ?>">
                                                    <input type="hidden" name="doc_type" value="aadhaar">
                                                    <input type="hidden" name="action_type" value="approve">
                                                    <button type="submit" class="fr-btn fr-btn-eco fr-btn-sm" style="padding:2px 8px; font-size:11px;">Approve</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:12px;">Not provided</span>
                                    <?php endif; ?>
                                </td>

                                <!-- DL Cell -->
                                <td>
                                    <?php if (!empty($u['dl_number'])): ?>
                                        <div style="font-family:monospace;"><?php echo htmlspecialchars($u['dl_number']); ?></div>
                                        <div style="margin-top:4px;">
                                            <?php if ($u['is_dl_verified'] ?? 0): ?>
                                                <span class="fr-badge fr-badge-eco" style="font-size:11px;">Verified</span>
                                            <?php else: ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action_user_id" value="<?php echo $u['id']; ?>">
                                                    <input type="hidden" name="doc_type" value="dl">
                                                    <input type="hidden" name="action_type" value="approve">
                                                    <button type="submit" class="fr-btn fr-btn-eco fr-btn-sm" style="padding:2px 8px; font-size:11px;">Approve</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:12px;">Not provided</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Campus Email Cell -->
                                <td>
                                    <?php if (!empty($u['college_email'])): ?>
                                        <div><?php echo htmlspecialchars($u['college_email']); ?></div>
                                        <div style="margin-top:4px;">
                                            <?php if ($u['is_college_email_verified'] ?? 0): ?>
                                                <span class="fr-badge fr-badge-eco" style="font-size:11px;">Verified</span>
                                            <?php else: ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action_user_id" value="<?php echo $u['id']; ?>">
                                                    <input type="hidden" name="doc_type" value="college">
                                                    <input type="hidden" name="action_type" value="approve">
                                                    <button type="submit" class="fr-btn fr-btn-eco fr-btn-sm" style="padding:2px 8px; font-size:11px;">Approve</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:12px;">Not provided</span>
                                    <?php endif; ?>
                                </td>

                                <!-- UPI VPA Cell -->
                                <td>
                                    <?php if (!empty($u['upi_id'])): ?>
                                        <div><?php echo htmlspecialchars($u['upi_id']); ?></div>
                                        <span class="fr-badge fr-badge-primary" style="font-size:11px; margin-top:4px;">Active VPA</span>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:12px;">Not set</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
