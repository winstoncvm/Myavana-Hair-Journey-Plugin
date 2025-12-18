function toggleOffcanvas() {
    const offcanvas = document.getElementById('hairDiaryOffcanvas');
    offcanvas.classList.toggle('offcanvas-open');
}
function toggleEditOffcanvas() {
    const offcanvas = document.getElementById('entryModal');
    offcanvas.classList.toggle('offcanvas-open');
}

document.addEventListener('DOMContentLoaded', function() {
   

    // Close offcanvas when clicking outside
    document.addEventListener('click', function(e) {
        const offcanvas = document.getElementById('hairDiaryOffcanvas');
        const toggleBtn = document.querySelector('.offcanvas-toggle-btn');
        if (!offcanvas.contains(e.target)  && offcanvas.classList.contains('offcanvas-open')) {
            toggleOffcanvas();
        }
    });

    // Keyboard shortcut
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const offcanvas = document.getElementById('hairDiaryOffcanvas');
            if (offcanvas.classList.contains('offcanvas-open')) {
                toggleOffcanvas();
            }
        }
    });

    function showNotification(message, isError = false) {
        const notification = document.createElement('div');
        notification.className = `notification ${isError ? 'notification-error' : ''}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('notification-show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('notification-show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
});