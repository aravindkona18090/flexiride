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
    $action     = $_POST['action_type'] ?? ''; // approve or reject

    $val = ($action === 'approve') ? 1 : 0;
    $col = '';

    if ($doc_type === 'aadhaar') $col = 'is_aadhaar_verified';
    elseif ($doc_type === 'dl') $col = 'is_dl_verified';
    elseif ($doc_type === 'college') $col = 'is_college_email_verified';
    elseif ($doc_type === 'upi') $col = 'is_upi_verified';

    if (!empty($col)) {
        safeAddColumn($conn, 'users', $col, "TINYINT(1) NOT NULL DEFAULT 0");
        $stmt = $conn->prepare("UPDATE users SET $col = ? WHERE id = ?");
        $stmt->bind_param("ii", $val, $target_uid);
        if ($stmt->execute()) {
            // Notify user
            $statusTxt = ($action === 'approve') ? 'Approved & Verified' : 'Rejected';
            $nStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Identity Verification Update', 'Your $doc_type verification request has been $statusTxt by admin.')");
            $nStmt->bind_param("i", $target_uid);
            $nStmt->execute();

            $successMsg = "User $doc_type verification updated successfully!";
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
    <title>Document Approval & Identity Queue - Admin FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 1100px; margin: 35px auto; padding: 0 20px; width: 100%; }

        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .header-box h2 { font-size: 26px; color: var(--text-color); display: flex; align-items: center; gap: 10px; }
        .btn-back { background: var(--input-bg); color: var(--text-color); border: 1px solid var(--card-border); padding: 10px 18px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 14px; }

        .card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }

        .table-responsive { overflow-x: auto; }
        .queue-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        .queue-table th, .queue-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--card-border); }
        .queue-table th { color: var(--text-muted); font-size: 12px; text-transform: uppercase; font-weight: 700; }

        .badge-status { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
        .badge-verified { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); }
        .badge-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid #f59e0b; }

        .btn-act-approve { background: var(--success-color); color: white; border: none; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; }
        .btn-act-reject { background: var(--danger-color); color: white; border: none; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; }

        .alert-success { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .alert-error { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container">
    <div class="header-box">
        <h2>🛡️ Document Approval & Identity Verification Queue</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href='../index.php' class="btn-back"><i class='bx bx-home-alt'></i> 🏠 Home</a>
            <a href="admin_dashboard.php" class="btn-back"><i class='bx bx-left-arrow-alt'></i> Admin Dashboard</a>
        </div>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="alert-error"><?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="font-size:18px; margin-bottom:15px; color:var(--primary-color);">📋 User Verification Approval List</h3>

        <div class="table-responsive">
            <table class="queue-table">
                <thead>
                    <tr>
                        <th>User Name & Contact</th>
                        <th>Aadhaar Number</th>
                        <th>Driving License (DL)</th>
                        <th>Campus Email</th>
                        <th>UPI VPA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usersStmt && $usersStmt->num_rows > 0): ?>
                        <?php while ($u = $usersStmt->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($u['name']); ?></strong><br>
                                    <span style="font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($u['email']); ?></span>
                                </td>

                                <!-- Aadhaar Column -->
                                <td>
                                    <?php if (!empty($u['aadhaar_number'])): ?>
                                        <div style="margin-bottom:6px; font-weight:600;">XXXX-XXXX-<?php echo substr($u['aadhaar_number'], -4); ?></div>
                                        <?php if ($u['is_aadhaar_verified']): ?>
                                            <span class="badge-status badge-verified"><i class='bx bxs-check-circle'></i> Verified</span>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action_user_id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="doc_type" value="aadhaar">
                                                <button type="submit" name="action_type" value="approve" class="btn-act-approve">Approve</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:12px;">Not Submitted</span>
                                    <?php endif; ?>
                                </td>

                                <!-- DL Column -->
                                <td>
                                    <?php if (!empty($u['dl_number'])): ?>
                                        <div style="margin-bottom:6px; font-weight:600;"><?php echo htmlspecialchars($u['dl_number']); ?></div>
                                        <?php if ($u['is_dl_verified']): ?>
                                            <span class="badge-status badge-verified"><i class='bx bxs-check-circle'></i> Verified</span>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action_user_id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="doc_type" value="dl">
                                                <button type="submit" name="action_type" value="approve" class="btn-act-approve">Approve</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:12px;">Not Submitted</span>
                                    <?php endif; ?>
                                </td>

                                <!-- College Email Column -->
                                <td>
                                    <?php if (!empty($u['college_email'])): ?>
                                        <div style="margin-bottom:6px; font-weight:600;"><?php echo htmlspecialchars($u['college_email']); ?></div>
                                        <?php if ($u['is_college_email_verified']): ?>
                                            <span class="badge-status badge-verified"><i class='bx bxs-check-circle'></i> Verified</span>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action_user_id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="doc_type" value="college">
                                                <button type="submit" name="action_type" value="approve" class="btn-act-approve">Approve</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:12px;">Not Set</span>
                                    <?php endif; ?>
                                </td>

                                <!-- UPI Column -->
                                <td>
                                    <?php if (!empty($u['upi_id'])): ?>
                                        <div style="margin-bottom:6px; font-weight:600;"><?php echo htmlspecialchars($u['upi_id']); ?></div>
                                        <?php if ($u['is_upi_verified']): ?>
                                            <span class="badge-status badge-verified"><i class='bx bxs-check-circle'></i> Verified</span>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action_user_id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="doc_type" value="upi">
                                                <button type="submit" name="action_type" value="approve" class="btn-act-approve">Approve</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:12px;">Not Set</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
