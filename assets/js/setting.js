document.addEventListener('DOMContentLoaded', function() {
    
    document.getElementById('savePluginBtn').addEventListener('click', function() {
        const saveBtn = this;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
        saveBtn.disabled = true;

        // Collect selected attribute sligs
        const selectedPlugins = [];
        document.querySelectorAll('.plugin-check-input:checked').forEach(checkbox => {
            if (checkbox.value != null) {
                selectedPlugins.push(checkbox.value);
            }
        });
        
        // Prepare data to send
        const dataToSend = { 
            plugins: selectedPlugins
        };

        // Get base URL - more reliable method
        const currentPath = window.location.pathname;
        const basePath = currentPath.substring(0, currentPath.lastIndexOf('/'));
        const url = window.location.origin + basePath + '/includes/save_plugins.php';

        // Send to server
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dataToSend)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Plugins saved successfully!');
            } else {
                showAlert('danger', 'Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            showAlert('danger', 'Network error: ' + error.message);
        })
        .finally(() => {
            console.log('Data response:');
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Save Changes';
            saveBtn.disabled = false;
        });
        
    });
    
    // Helper function to show alerts
    function showAlert(type, message) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert-dismissible');
        existingAlerts.forEach(alert => alert.remove());
        
        // Create new alert
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 1050;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        document.body.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        }, 5000);
    }

});
