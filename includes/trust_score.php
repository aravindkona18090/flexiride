<?php
/**
 * FlexiRide Trust & Safety Score Engine
 * Computes a weighted Trust Score (0–100%) based on verifiable identity signals:
 *   - Base account creation:     20 pts
 *   - SMS OTP phone verification: 30 pts
 *   - Aadhaar Verhoeff checksum:  35 pts
 *   - Driving Licence audit:      15 pts
 * Score is used to display trust badges on driver/passenger profiles.
 */

if (!function_exists('calculateRiderAiSafetyScore')) {
    function calculateRiderAiSafetyScore($conn, $userId) {
        $score = 20; // Base score for creating an account

        $uStmt = $conn->prepare("SELECT phone_verified FROM users WHERE id = ?");
        if ($uStmt) {
            $uStmt->bind_param("i", $userId);
            $uStmt->execute();
            $uRes = $uStmt->get_result()->fetch_assoc();
            if (!empty($uRes['phone_verified']) && $uRes['phone_verified'] == 1) {
                $score += 30; // +30 pts: SMS OTP phone verification
            }
        }

        $vStmt = $conn->prepare("SELECT is_aadhaar_verified, is_dl_verified FROM user_verifications WHERE user_id = ?");
        if ($vStmt) {
            $vStmt->bind_param("i", $userId);
            $vStmt->execute();
            $vRes = $vStmt->get_result()->fetch_assoc();
            if (!empty($vRes['is_aadhaar_verified']) && $vRes['is_aadhaar_verified'] == 1) {
                $score += 35; // +35 pts: Aadhaar Verhoeff checksum verified
            }
            if (!empty($vRes['is_dl_verified']) && $vRes['is_dl_verified'] == 1) {
                $score += 15; // +15 pts: Driving Licence admin-reviewed
            }
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
