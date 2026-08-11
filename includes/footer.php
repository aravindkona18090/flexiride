<?php
// Master Modular Footer Component - FlexiRide (Compact Sleek Design)
$isSubfolder = (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin');
$navRel = $isSubfolder ? '../' : '';
?>
<footer style="background: var(--card-bg); border-top: 1px solid var(--card-border); padding: 25px 20px 15px; color: var(--text-color); font-family: 'Outfit', sans-serif; margin-top: auto;">
    <div style="max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; padding-bottom: 18px; border-bottom: 1px solid var(--card-border);">
        
        <!-- About Section -->
        <div>
            <h3 style="font-size: 18px; font-weight: 700; color: var(--text-color); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                <span style="color: var(--text-color);">Flexi</span><span style="color: var(--primary-color);">Ride</span>
                <span style="background: var(--primary-gradient); color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 800;">2.0</span>
            </h3>
            <p style="font-size: 13px; color: var(--text-muted); line-height: 1.4; margin-bottom: 12px;">
                India's premier campus bike pooling platform. Safe, sustainable commute for GITAM commuters.
            </p>
            <div style="display: flex; gap: 8px;">
                <a href="https://www.instagram.com/flexi_ride247?igsh=b212c3Bjc2xkaWo5" target="_blank" style="width: 32px; height: 32px; border-radius: 8px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-color); display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s;" onmouseover="this.style.background='#e1306c'; this.style.color='white';" onmouseout="this.style.background='var(--input-bg)'; this.style.color='var(--text-color)';">
                    <i class='bx bxl-instagram' style="font-size: 18px;"></i>
                </a>
                <a href="#" style="width: 32px; height: 32px; border-radius: 8px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-color); display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s;" onmouseover="this.style.background='#0077b5'; this.style.color='white';" onmouseout="this.style.background='var(--input-bg)'; this.style.color='var(--text-color)';">
                    <i class='bx bxl-linkedin' style="font-size: 18px;"></i>
                </a>
                <a href="#" style="width: 32px; height: 32px; border-radius: 8px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-color); display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s;" onmouseover="this.style.background='var(--primary-color)'; this.style.color='white';" onmouseout="this.style.background='var(--input-bg)'; this.style.color='var(--text-color)';">
                    <i class='bx bxl-facebook' style="font-size: 18px;"></i>
                </a>
            </div>
        </div>

        <!-- Quick Links Section -->
        <div>
            <h4 style="font-size: 14px; font-weight: 700; color: var(--text-color); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.8px;">Quick Links</h4>
            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px; font-size: 13px;">
                <li><a href="<?php echo $navRel; ?>index.php" style="color: var(--text-muted); text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='var(--primary-color)';" onmouseout="this.style.color='var(--text-muted)';">🏠 Home</a></li>
                <li><a href="<?php echo $navRel; ?>find_ride.php" style="color: var(--text-muted); text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='var(--primary-color)';" onmouseout="this.style.color='var(--text-muted)';">🔍 Find Rides</a></li>
                <li><a href="<?php echo $navRel; ?>about.php" style="color: var(--text-muted); text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='var(--primary-color)';" onmouseout="this.style.color='var(--text-muted)';">ℹ️ About Us</a></li>
                <li><a href="<?php echo $navRel; ?>privacy.php" style="color: var(--text-muted); text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='var(--primary-color)';" onmouseout="this.style.color='var(--text-muted)';">🔒 Privacy & Terms</a></li>
                <li><a href="<?php echo $navRel; ?>feedback.php" style="color: var(--text-muted); text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='var(--primary-color)';" onmouseout="this.style.color='var(--text-muted)';">💬 Feedback</a></li>
                <li><a href="<?php echo $navRel; ?>queries.php" style="color: var(--text-muted); text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='var(--primary-color)';" onmouseout="this.style.color='var(--text-muted)';">❓ Help & Queries</a></li>
            </ul>
        </div>

        <!-- Contact Section -->
        <div>
            <h4 style="font-size: 14px; font-weight: 700; color: var(--text-color); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.8px;">Contact</h4>
            <div style="display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: var(--text-muted); line-height: 1.4;">
                <p style="margin: 0; display: flex; align-items: flex-start; gap: 6px;">
                    <i class='bx bxs-map' style="color: var(--primary-color); font-size: 16px; margin-top: 1px; flex-shrink: 0;"></i>
                    <span>GITAM Campus, Doddaballapur Taluk, Bengaluru - 561203</span>
                </p>
                <p style="margin: 0; display: flex; align-items: center; gap: 6px;">
                    <i class='bx bxs-envelope' style="color: var(--primary-color); font-size: 16px; flex-shrink: 0;"></i>
                    <a href="mailto:flexiride247@gmail.com" style="color: var(--text-muted); text-decoration: none;" onmouseover="this.style.color='var(--primary-color)';" onmouseout="this.style.color='var(--text-muted)';">flexiride247@gmail.com</a>
                </p>
                <p style="margin: 0; display: flex; align-items: center; gap: 6px;">
                    <i class='bx bxs-phone' style="color: var(--primary-color); font-size: 16px; flex-shrink: 0;"></i>
                    <a href="tel:+919160397434" style="color: var(--text-muted); text-decoration: none;" onmouseover="this.style.color='var(--primary-color)';" onmouseout="this.style.color='var(--text-muted)';">+91 9160397434</a>
                </p>
            </div>
        </div>

    </div>

    <!-- Bottom Bar -->
    <div style="max-width: 1100px; margin: 12px auto 0; text-align: center; font-size: 12px; color: var(--text-muted);">
        <p style="margin: 0;">© <?php echo date('Y'); ?> <strong>FlexiRide</strong> | Built for GITAM Bengaluru Commuters.</p>
    </div>
</footer>
