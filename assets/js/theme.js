// FlexiRide Master Dynamic Theme Switcher Engine
(function() {
    const savedTheme = localStorage.getItem('flexi_theme') || 'slate';
    document.documentElement.setAttribute('data-theme', savedTheme);

    document.addEventListener('DOMContentLoaded', function() {
        const themeSelectors = document.querySelectorAll('#themeSelector, .theme-selector, .theme-selector-mobile, .admin-select-theme');
        themeSelectors.forEach(sel => {
            if (sel) sel.value = savedTheme;
        });
    });
})();

function changeTheme(themeName) {
    localStorage.setItem('flexi_theme', themeName);
    document.documentElement.setAttribute('data-theme', themeName);
    const themeSelectors = document.querySelectorAll('#themeSelector, .theme-selector, .theme-selector-mobile, .admin-select-theme');
    themeSelectors.forEach(sel => {
        if (sel) sel.value = themeName;
    });
}

// 🌐 Global Network Offline & Online Event Listener
document.addEventListener('DOMContentLoaded', function() {
    // Create Offline Alert Banner element
    const netBanner = document.createElement('div');
    netBanner.id = 'globalNetworkBanner';
    netBanner.style.cssText = 'display:none; position:fixed; top:0; left:0; right:0; z-index:99999; padding:12px 20px; text-align:center; font-weight:700; font-size:14px; box-shadow:0 4px 15px rgba(0,0,0,0.4); transition:all 0.3s ease;';
    document.body.prepend(netBanner);

    function updateNetworkStatus() {
        if (!navigator.onLine) {
            netBanner.style.background = '#ef4444';
            netBanner.style.color = '#ffffff';
            netBanner.innerHTML = '⚠️ <strong>Network Disconnected:</strong> You are currently offline. Check your internet connection.';
            netBanner.style.display = 'block';
        } else if (netBanner.style.display === 'block') {
            netBanner.style.background = '#22c55e';
            netBanner.style.color = '#ffffff';
            netBanner.innerHTML = '⚡ <strong>Back Online!</strong> Reconnected to FlexiRide.';
            setTimeout(() => {
                netBanner.style.display = 'none';
            }, 3000);
        }
    }

    window.addEventListener('online', updateNetworkStatus);
    window.addEventListener('offline', updateNetworkStatus);
    updateNetworkStatus();

    // 📱 Register PWA Service Worker for Offline Capability & Push Notifications
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(err => console.log('SW Registration fallback:', err));
    }
});
