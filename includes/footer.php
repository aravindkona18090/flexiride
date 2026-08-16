<?php
// FlexiRide Master Footer Component
$isSubfolder = (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin');
$navRel = $isSubfolder ? '../' : '';
?>
<footer class="fr-footer">
    <div class="fr-footer-grid">
        <div class="fr-footer-col">
            <div class="fr-brand" style="margin-bottom:12px;">
                <div class="brand-logo-glow" style="width:32px; height:32px; font-size:18px;"><i class='bx bxs-bolt'></i></div>
                <div>Flexi<span>Ride</span></div>
            </div>
            <p style="font-size:13.5px; color:var(--text-muted); line-height:1.6; margin-bottom:16px; max-width:320px;">
                India's premier campus & daily commuter bike-pooling platform. Save fuel, prevent carbon emissions, and travel with verified campus peers.
            </p>
            <div style="display:flex; gap:10px;">
                <span class="fr-badge fr-badge-eco"><i class='bx bxs-leaf'></i> 100% Carbon-Conscious</span>
                <span class="fr-badge fr-badge-primary"><i class='bx bxs-shield-check'></i> Verhoeff Verified</span>
            </div>
        </div>

        <div class="fr-footer-col">
            <h4>Marketplace</h4>
            <ul class="fr-footer-links">
                <li><a href="<?php echo $navRel; ?>find_ride.php"><i class='bx bx-search'></i> Find a Ride</a></li>
                <li><a href="<?php echo $navRel; ?>post_ride.php"><i class='bx bx-plus-circle'></i> Post / Offer Ride</a></li>
                <li><a href="<?php echo $navRel; ?>myrides.php"><i class='bx bxs-car'></i> My Offered Rides</a></li>
                <li><a href="<?php echo $navRel; ?>my_booked_rides.php"><i class='bx bxs-receipt'></i> My Booked Trips</a></li>
            </ul>
        </div>

        <div class="fr-footer-col">
            <h4>Trust & Safety</h4>
            <ul class="fr-footer-links">
                <li><a href="<?php echo $navRel; ?>danger.php" style="color:var(--danger);"><i class='bx bxs-alarm-exclamation'></i> Emergency SOS HUD</a></li>
                <li><a href="<?php echo $navRel; ?>edit_profile.php"><i class='bx bxs-id-card'></i> Identity Verification</a></li>
                <li><a href="<?php echo $navRel; ?>privacy.php"><i class='bx bxs-lock-alt'></i> Privacy Policy</a></li>
                <li><a href="<?php echo $navRel; ?>about.php"><i class='bx bxs-info-circle'></i> About Platform</a></li>
            </ul>
        </div>

        <div class="fr-footer-col">
            <h4>Support</h4>
            <ul class="fr-footer-links">
                <li><a href="<?php echo $navRel; ?>feedback.php"><i class='bx bxs-message-square-dots'></i> Send Feedback</a></li>
                <li><a href="<?php echo $navRel; ?>queries.php"><i class='bx bxs-help-circle'></i> Help & Queries</a></li>
                <li><a href="mailto:support@flexiride.com"><i class='bx bxs-envelope'></i> support@flexiride.com</a></li>
            </ul>
        </div>
    </div>

    <div class="fr-footer-bottom">
        <div>© <?php echo date('Y'); ?> <strong>FlexiRide</strong>. All rights reserved. Peer-to-peer non-commercial carpooling.</div>
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="width:8px; height:8px; background:#22c55e; border-radius:50%; display:inline-block; box-shadow:0 0 8px #22c55e;"></span>
            <span>All Systems Operational</span>
        </div>
    </div>
</footer>
