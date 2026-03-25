// Preload overlay functionality
window.addEventListener('load', function() {
    // Hide the preload overlay with a fade effect
    const overlay = document.getElementById('preloadOverlay');
    if (overlay) {
        overlay.style.opacity = '0';
        setTimeout(() => {
            overlay.style.display = 'none';
        }, 500);
    }
});
