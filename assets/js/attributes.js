document.addEventListener('DOMContentLoaded', function() {
    
    document.getElementById('saveAttributesBtn').addEventListener('click', function() {
        const saveBtn = this;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
        saveBtn.disabled = true;

        // Collect selected attribute sligs
        const selectedTaxonomies = [];
        document.querySelectorAll('.attribute-check-input:checked').forEach(checkbox => {
            if (checkbox.value != null) {
                selectedTaxonomies.push(checkbox.value);
            }
        });
        
        // Collect selected term IDs
        const selectedTerms = [];
        document.querySelectorAll('.form-check-input:checked').forEach(checkbox => {
            if(checkbox.value != null){
                selectedTerms.push({
                    term_cate: checkbox.name,
                    term_id: checkbox.value
                });
            }
        });

        // Prepare data to send
        const dataToSend = { 
            terms: selectedTerms,
            taxonomies: selectedTaxonomies 
        };

        // Get base URL - more reliable method
        const currentPath = window.location.pathname;
        const basePath = currentPath.substring(0, currentPath.lastIndexOf('/'));
        const url = window.location.origin + basePath + '/includes/save_attributes.php';

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
                showAlert('success', 'Attributes saved successfully!');
            } else {
                showAlert('danger', 'Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            showAlert('danger', 'Network error: ' + error.message);
        })
        .finally(() => {
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

    // Attribute checkbox selects all terms
    document.querySelectorAll('.accordion-header .attribute-check-input').forEach(checkbox => {
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
