document.addEventListener('DOMContentLoaded', function() {
    
    // Save Attributes Button Handler
    const saveBtn = document.getElementById('saveAttributesBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            // Show loading state
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
            saveBtn.disabled = true;
            
            // Collect all selected term IDs
            const selectedTerms = [];
            document.querySelectorAll('.term-checkbox:checked').forEach(checkbox => {
                selectedTerms.push(checkbox.value);
            });
            
            // Send data to server
            fetch('save_attributes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    terms: selectedTerms
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Attributes saved successfully!');
                } else {
                    showAlert('danger', 'Error saving attributes: ' + data.message);
                }
            })
            .catch(error => {
                showAlert('danger', 'Network error: ' + error.message);
            })
            .finally(() => {
                // Restore button state
                saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Save Changes';
                saveBtn.disabled = false;
            });
        });
    }
    
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

    // Attribute checkbox selects all terms
    document.querySelectorAll('.accordion-header .form-check-input').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const collapseId = this.closest('.accordion-header').querySelector('button').getAttribute('data-bs-target');
            const collapseEl = document.querySelector(collapseId);
            const termCheckboxes = collapseEl.querySelectorAll('.term-item .form-check-input');
                    
            termCheckboxes.forEach(termCb => {
                termCb.checked = this.checked;
            });
        });
    });

    // Simple search functionality
    document.querySelector('.search-box input').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const attributeCards = document.querySelectorAll('.accordion-item');
                
        attributeCards.forEach(card => {
            const attributeName = card.querySelector('.accordion-button strong').textContent.toLowerCase();
            const termItems = card.querySelectorAll('.term-item');
            let hasVisibleTerms = false;
                    
            termItems.forEach(item => {
                const termName = item.querySelector('.form-check-label').textContent.toLowerCase();
                if (termName.includes(searchTerm) || attributeName.includes(searchTerm)) {
                    item.style.display = 'flex';
                    hasVisibleTerms = true;
                } else {
                    item.style.display = 'none';
                }
            });
                    
            // Show/hide attribute based on search results
            if (attributeName.includes(searchTerm) || hasVisibleTerms) {
                card.style.display = 'block';
                        
                // Automatically open matching attributes
                const collapseId = card.querySelector('.accordion-button').getAttribute('data-bs-target');
                const collapseEl = document.querySelector(collapseId);
                new bootstrap.Collapse(collapseEl, {toggle: true});
            } else {
                    card.style.display = 'none';
            }
        });
    });
});
