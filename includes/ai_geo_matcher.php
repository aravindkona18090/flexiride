<?php
/**
 * FlexiRide Geospatial AI Haversine Matching Engine
 * Uses the Haversine Trigonometric Formula to calculate exact spatial distance (km)
 * between GPS coordinates (latitude, longitude) without relying on expensive Google APIs.
 */

if (!function_exists('calculateHaversineDistance')) {
    function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // Earth radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return round($distance, 2); // Returns distance in kilometers
    }
}

if (!function_exists('calculateMatchConfidence')) {
    function calculateMatchConfidence($pickupMatch, $dropMatch, $timeDiffMinutes = 0) {
        $score = 100;
        
        // Penalize spatial distance deviation
        if (!$pickupMatch) $score -= 30;
        if (!$dropMatch) $score -= 30;
        
        // Penalize time deviation
        if ($timeDiffMinutes > 60) {
            $score -= 25;
        } elseif ($timeDiffMinutes > 30) {
            $score -= 15;
        } elseif ($timeDiffMinutes > 15) {
            $score -= 5;
        }
        
        $finalScore = max(10, min(100, $score));
        
        return [
            'match_score' => $finalScore,
            'badge'       => ($finalScore >= 85) ? '🎯 95%+ High AI Match' : (($finalScore >= 60) ? '⚡ Good Match' : '🔍 Partial Match'),
            'color'       => ($finalScore >= 85) ? '#22c55e' : (($finalScore >= 60) ? '#38bdf8' : '#f59e0b')
        ];
    }
}
?>
