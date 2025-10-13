class AttributeStorage {
    constructor() {
        this.terms = {};
        this.lastUpdated = 0;
        this.expiration = 0;
        this.load();
    }
    
    load() {
        try {
            const storedData = localStorage.getItem('attributeTerms');
            const timestamp = localStorage.getItem('attributeTermsTimestamp');
            const expiration = localStorage.getItem('attributeTermsExpiration');
            
            if (storedData && timestamp && expiration) {
                this.terms = JSON.parse(storedData);
                this.lastUpdated = parseInt(timestamp);
                this.expiration = parseInt(expiration);
                
                // Check if data is expired
                const currentTime = Date.now();
                if (currentTime - this.lastUpdated > this.expiration) {
                    this.terms = {};
                    this.refreshFromServer();
                }
            } else {
                this.refreshFromServer();
            }
        } catch (e) {
            console.error('Error loading attribute terms from localStorage:', e);
            this.refreshFromServer();
        }
    }
    
    refreshFromServer() {
        var currentUrl = window.location.href;
        var baseUrl = currentUrl.substring(0, currentUrl.lastIndexOf('/'));
        
        $.ajax({
            url: baseUrl + '/includes/refresh_attribute_terms.php',
            method: 'GET',
            dataType: 'json',
            success: (data) => {
                this.terms = data;
                this.lastUpdated = Date.now();
                localStorage.setItem('attributeTerms', JSON.stringify(data));
                localStorage.setItem('attributeTermsTimestamp', this.lastUpdated);
                localStorage.setItem('attributeTermsExpiration', 3600000); // 1 hour
            },
            error: () => {
                console.error('Failed to refresh attribute terms');
            }
        });
    }
    
    getTerms(taxonomy) {
        return this.terms[taxonomy] || {};
    }
    
    search(taxonomy, query) {
        const terms = this.getTerms(taxonomy);
        const results = [];
        const queryLower = query.toLowerCase();
        
        for (const [id, name] of Object.entries(terms)) {
            if (name.toLowerCase().includes(queryLower)) {
                results.push({
                    id: id,
                    name: name
                });
                
                // Limit results to 15
                if (results.length >= 15) break;
            }
        }
        
        return results;
    }
}

// Create a global instance
const attributeStorage = new AttributeStorage();

// Save field value
function saveFieldValue($field) {
    const productId = $field.data('productid');
    const field = $field.data('field');
    let value = $field.text().trim();
    const originalValue = $field.data('original');

    // For price fields, convert to number
    if ($field.hasClass('price-field')) {
        value = parseFloat(value.replace(/[^\d.]/g, ''));
        if (isNaN(value)) {
            value = '';
        }
    }

    // Visual feedback
    $field.addClass('saving');

    // Add to update queue
    addToUpdateQueue({
        product_id: productId,
        field_name: field,
        new_value: value,
        old_value: originalValue
    }).then(() => {
        $field.removeClass('saving').addClass('saved');
        setTimeout(() => $field.removeClass('saved'), 2000);

        // Update original value
        $field.data('original', value);

        // For price fields, format after save
        if ($field.hasClass('price-field') && value) {
            $field.text(wc_price(value));
        }
    }).catch(() => {
        $field.removeClass('saving').addClass('error');
        setTimeout(() => $field.removeClass('error'), 2000);

        // Revert to original value on error
        $field.text($field.data('original'));
    });
}

// Save attribute value
function saveAttributeValue($cell) {
    const $input = $cell.find('.attribute-autocomplete');
    const $valueDiv = $cell.find('.attribute-value');
    const newValue = $input.val();
    const taxonomy = $input.data('taxonomy');
    const productId = $cell.data('productid');

    // Update display
    $valueDiv.text(newValue);
    $valueDiv.show();
    $cell.find('.attribute-input').hide();

    // Visual feedback
    $cell.addClass('saving');

    // Add to update queue
    addToUpdateQueue({
        product_id: productId,
        field_name: 'attribute',
        new_value: newValue,
        taxonomy: taxonomy
    }).then(() => {
        $cell.removeClass('saving').addClass('saved');
        setTimeout(() => $cell.removeClass('saved'), 2000);
    }).catch(() => {
        $cell.removeClass('saving').addClass('error');
        setTimeout(() => $cell.removeClass('error'), 2000);
    });
}
