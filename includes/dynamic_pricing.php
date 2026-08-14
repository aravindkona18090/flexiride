<?php
/**
 * FlexiRide Dynamic Pricing Engine
 * Implements tiered marginal pricing (cost per km decreases for longer trips)
 * with peak-hour surge multipliers tailored for campus commute patterns
 * (morning entry 8–10 AM, evening exit 4–7 PM).
 * Approach inspired by transportation economics literature.
 */

if (!function_exists('calculateAiSmartPrice')) {
    function calculateAiSmartPrice($distanceKm, $vehicleType = 'bike', $isEv = false, $departureTime = null) {
        $distanceKm = max(1.0, (float)$distanceKm);
        
        $baseFare = ($vehicleType === 'bike') ? 10.00 : 20.00;
        $distanceCost = 0.0;

        if ($vehicleType === 'bike') {
            // 🏍️ Bike Tiered Marginal Pricing (longer km = lower per-km rate)
            if ($distanceKm <= 10) {
                $distanceCost = $distanceKm * 3.00;
            } elseif ($distanceKm <= 30) {
                $distanceCost = (10 * 3.00) + (($distanceKm - 10) * 2.00);
            } elseif ($distanceKm <= 60) {
                $distanceCost = (10 * 3.00) + (20 * 2.00) + (($distanceKm - 30) * 1.50);
            } else {
                $distanceCost = (10 * 3.00) + (20 * 2.00) + (30 * 1.50) + (($distanceKm - 60) * 1.20);
            }
        } else {
            // 🚗 Car Tiered Marginal Pricing
            if ($distanceKm <= 10) {
                $distanceCost = $distanceKm * 5.50;
            } elseif ($distanceKm <= 30) {
                $distanceCost = (10 * 5.50) + (($distanceKm - 10) * 4.00);
            } elseif ($distanceKm <= 60) {
                $distanceCost = (10 * 5.50) + (20 * 4.00) + (($distanceKm - 30) * 3.00);
            } else {
                $distanceCost = (10 * 5.50) + (20 * 4.00) + (30 * 3.00) + (($distanceKm - 60) * 2.20);
            }
        }

        // Peak Hour Surge Multiplier (Campus entry: 8–10 AM, Campus exit: 4–7 PM)
        $hour = $departureTime ? (int)date('H', strtotime($departureTime)) : (int)date('H');
        $surgeMultiplier = 1.0;
        if (($hour >= 8 && $hour <= 10) || ($hour >= 16 && $hour <= 19)) {
            $surgeMultiplier = 1.25; // 25% peak demand surge
        } elseif ($hour >= 22 || $hour <= 5) {
            $surgeMultiplier = 1.15; // Night safety margin
        }

        // EV Eco-Discount (electric vehicles get 10% pricing advantage)
        $ecoMultiplier = $isEv ? 0.90 : 1.00;

        // Calculate suggested fare and round to nearest ₹5 for clean transactions
        $predictedPrice = ($baseFare + $distanceCost) * $surgeMultiplier * $ecoMultiplier;
        $suggestedPrice = ceil($predictedPrice / 5) * 5;

        return [
            'suggested_price'  => (float)$suggestedPrice,
            'surge_multiplier' => $surgeMultiplier,
            'is_peak_hour'     => ($surgeMultiplier > 1.0),
            'eco_discount'     => $isEv ? '10% Eco Discount Applied' : 'Standard Fuel Rate',
            'explanation'      => "Suggested ₹{$suggestedPrice} based on {$distanceKm}km, "
                                . ($surgeMultiplier > 1.0 ? "Peak Hour Surge (x{$surgeMultiplier}), " : "Normal Traffic, ")
                                . ($isEv ? "EV Eco-Benefit." : "Fuel Rate.")
        ];
    }
}
?>
