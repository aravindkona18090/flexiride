// FlexiRide Master Dynamic Theme Switcher Engine
(function() {
    const savedTheme = localStorage.getItem('flexi_theme') || 'slate';
    document.documentElement.setAttribute('data-theme', savedTheme);

    document.addEventListener('DOMContentLoaded', function() {
        const themeSelectors = document.querySelectorAll('#themeSelector');
        themeSelectors.forEach(sel => {
            if (sel) sel.value = savedTheme;
        });
    });
})();

function changeTheme(themeName) {
    localStorage.setItem('flexi_theme', themeName);
    document.documentElement.setAttribute('data-theme', themeName);
    const themeSelectors = document.querySelectorAll('#themeSelector');
    themeSelectors.forEach(sel => {
        if (sel) sel.value = themeName;
    });
}
