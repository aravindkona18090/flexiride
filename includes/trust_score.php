<?php
/**
 * FlexiRide Trust & Safety Score Engine
 * Computes a weighted Trust Score (0–100%) based on verifiable identity signals:
 *   - Base account creation:      20 pts
 *   - Mobile/SMS phone signal:    30 pts
 *   - Aadhaar identity check:     35 pts
 *   - Driving Licence check:      15 pts
 * Score is used to display trust badges on driver/passenger profiles.
 */

if (!function_exists('calculateRiderAiSafetyScore')) {
    function calculateRiderAiSafetyScore($conn, $userId) {
        $score = 20; // Base score for creating an account

        if (!$conn || !$userId) {
            return [
                'score'       => 50,
                'badge_title' => '🛡️ Trust Score: 50%',
                'badge_class' => 'shield-blue',
                'badge_color' => '#38bdf8',
                'is_trusted'  => true
            ];
        }

        try {
            $uStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            if ($uStmt) {
                $uStmt->bind_param("i", $userId);
                $uStmt->execute();
                $uRes = $uStmt->get_result()->fetch_assoc() ?: [];

                // Check phone signal
                if (!empty($uRes['is_phone_verified']) || !empty($uRes['phone_verified']) || !empty($uRes['phone'])) {
                    $score += 30; // +30 pts: Phone present/verified
                }

                // Check Aadhaar signal on user row
                if (!empty($uRes['is_aadhaar_verified']) || !empty($uRes['is_verified'])) {
                    $score += 35; // +35 pts: Aadhaar Verhoeff verified
                }

                // Check DL signal on user row
                if (!empty($uRes['is_dl_verified'])) {
                    $score += 15; // +15 pts: Driving Licence verified
                }
            }
        } catch (\Throwable $e) {
            error_log('[FlexiRide Trust] users table check error: ' . $e->getMessage());
        }

        // Also check normalized user_verifications if table exists
        try {
            $vStmt = $conn->prepare("SELECT * FROM user_verifications WHERE user_id = ?");
            if ($vStmt) {
                $vStmt->bind_param("i", $userId);
                $vStmt->execute();
                $vRes = $vStmt->get_result()->fetch_assoc();
                if ($vRes) {
                    if (!empty($vRes['is_aadhaar_verified']) && empty($uRes['is_aadhaar_verified'])) {
                        $score += 35;
                    }
                    if (!empty($vRes['is_dl_verified']) && empty($uRes['is_dl_verified'])) {
                        $score += 15;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore if normalized table is absent
        }

        $finalScore = min(100, $score);

        $badgeTitle = '🛡️ Trust Score: ' . $finalScore . '%';
        $badgeClass = ($finalScore >= 80) ? 'shield-gold' : (($finalScore >= 50) ? 'shield-blue' : 'shield-silver');
        $badgeColor = ($finalScore >= 80) ? '#22c55e' : (($finalScore >= 50) ? '#38bdf8' : '#f59e0b');

        return [
            'score'       => $finalScore,
            'badge_title' => $badgeTitle,
            'badge_class' => $badgeClass,
            'badge_color' => $badgeColor,
            'is_trusted'  => ($finalScore >= 65)
        ];
    }
}
?>
