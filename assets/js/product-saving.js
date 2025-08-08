$(document).ready(function() {
    // Initialize DataTable
    const table = $('#productsTable').DataTable({
        paging: true,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        dom: '<"top"f>rt<"bottom"lip><"clear">',
        language: {
            search: "",
            searchPlaceholder: "Search products..."
        },
        columnDefs: [
            { targets: [0, 2, 3, 4, 5, 6, 7, 8], orderable: true },
            { targets: [1], orderable: false }
        ],
        initComplete: function() {
            $('.dataTables_filter input').addClass('form-control');
        }
    });
    
    // Row selection
    $('#productsTable tbody').on('click', 'tr', function(e) {
        if ($(e.target).is('button') || $(e.target).is('input')) {
            return;
        }
        
        $(this).toggleClass('selected');
        updateSelectedCount();
    });
    
    // Update selected count
    function updateSelectedCount() {
        const count = table.rows('.selected').count();
        $('#selectedCount').text(count);
        
        // Show/hide bulk edit panel
        if (count > 0) {
            $('#bulkEditPanel').addClass('active');
        } else {
            $('#bulkEditPanel').removeClass('active');
        }
    }
    
    // Toggle bulk edit panel
    $('#bulkEditBtn').click(function() {
        if ($('#bulkEditPanel').hasClass('active')) {
            $('#bulkEditPanel').removeClass('active');
            table.$('tr.selected').removeClass('selected');
            updateSelectedCount();
        } else {
            $('#bulkEditPanel').addClass('active');
        }
    });
    
    // Cancel bulk edit
    $('#cancelBulkEdit').click(function() {
        table.$('tr.selected').removeClass('selected');
        $('#bulkEditPanel').removeClass('active');
        updateSelectedCount();
    });
    
    //Initialize field editing
    $('#productsTable').on('focus', '[contenteditable]', function() {
        const $cell = $(this);
        $cell.data('original', $cell.text());

        // For price fields, remove currency formatting
        if ($cell.hasClass('price-field')) {
            const priceValue = parseFloat($cell.text().replace(/[^\d.]/g, ''));
            if (!isNaN(priceValue)) {
                $cell.text(priceValue.toFixed(2));
            }
        }
    }).on('blur', '[contenteditable]', function() {
        const $cell = $(this);
        const original = $cell.data('original');
        const newValue = $cell.text().trim();
        const field = $cell.data('field');
        const productId = $cell.closest('tr').data('id');
        
        // For price fields, add currency formatting
        if ($cell.hasClass('price-field')) {
            const numValue = parseFloat(newValue.replace(/[^\d.]/g, ''));
            if (!isNaN(numValue)) {
                $cell.text(wc_price(numValue));
            }
        }

        if (newValue !== original) {
            // Visual feedback
            $cell.addClass('text-warning');

            if($cell.hasClass('glint-product-step')){
                console.log('step field triggerred');
                updateQuantityStep($cell)
            }else{
                saveFieldValue($cell);
            }  
        }
        
        // Handle Enter and Escape keys
        $('.editable-field').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(this).blur();
            } else if (e.key === 'Escape') {
                const $field = $(this);
                $field.text($field.data('original'));
                $field.blur();
            }
        });
    });
    
    // Price formatting
    $('#productsTable').on('blur', '.price-cell[contenteditable]', function() {
        const $cell = $(this);
        let value = $cell.text().trim();
        
        // Remove currency symbols and commas
        value = value.replace(/[^\d.]/g, '');
        
        // Parse as float and format
        const num = parseFloat(value);
        if (!isNaN(num)) {
            $cell.text('$' + num.toFixed(2));
        }
    });
    
    // Apply bulk changes
    $('#applyBulkEdit').click(function() {
        const regularPrice = $('#bulkPrice').val();
        const salePrice = $('#bulkSalePrice').val();
        const stockStatus = $('#bulkStockStatus').val();
        
        const changes = {};
        if (regularPrice) changes.regular_price = regularPrice;
        if (salePrice) changes.sale_price = salePrice;
        if (stockStatus) changes.stock_status = stockStatus;
        
        if (Object.keys(changes).length === 0) {
            alert('Please make at least one change');
            return;
        }
        
        const productIds = [];
        table.rows('.selected').every(function() {
            productIds.push($(this.node()).data('id'));
        });
        
        // Show loading
        const btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Applying...');
        btn.prop('disabled', true);
        
        // Simulate bulk save (in real app, AJAX call)
        setTimeout(() => {
            console.log(`Applying changes to ${productIds.length} products:`, changes);
            alert(`Changes applied to ${productIds.length} products!`);
            
            btn.html('Apply Changes');
            btn.prop('disabled', false);
            table.$('tr.selected').removeClass('selected');
            $('#bulkEditPanel').removeClass('active');
            updateSelectedCount();
            
            // Reset form
            $('#bulkPrice, #bulkSalePrice').val('');
            $('#bulkStockStatus').val('');
        }, 1000);
    });
    
    // Bulk delete
    $('#bulkDelete').click(function() {
        const productIds = [];
        table.rows('.selected').every(function() {
            productIds.push($(this.node()).data('id'));
        });
        
        if (productIds.length === 0) {
            alert('Please select at least one product');
            return;
        }
        
        if (!confirm(`Are you sure you want to delete ${productIds.length} products? This cannot be undone.`)) {
            return;
        }
        
        // Show loading
        const btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Deleting...');
        btn.prop('disabled', true);
        
        // Simulate delete (in real app, AJAX call)
        setTimeout(() => {
            console.log(`Deleting ${productIds.length} products:`, productIds);
            alert(`Deleted ${productIds.length} products!`);
            
            btn.html('<i class="fas fa-trash me-1"></i> Delete Selected');
            btn.prop('disabled', false);
            table.rows('.selected').remove().draw();
            $('#bulkEditPanel').removeClass('active');
            updateSelectedCount();
        }, 1000);
    });
    
    // Refresh button
    $('#refreshBtn').click(function() {
        const btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Refreshing...');
        btn.prop('disabled', true);
        
        setTimeout(() => {
            location.reload();
        }, 500);
    });

    // Attribute cell editing
    $('#productsTable').on('click', '.attribute-cell', function(e) {
        if ($(e.target).is('input') || $(e.target).hasClass('autocomplete-results')) {
            return;
        }
        
        const $cell = $(this);
        const $valueDiv = $cell.find('.attribute-value');
        const $inputDiv = $cell.find('.attribute-input');
        
        // Show input, hide value
        $valueDiv.hide();
        $inputDiv.show();
        $inputDiv.find('input').focus();
    });

    //Autocomplete function 
    $('#productsTable').on('input', '.attribute-autocomplete', function() {
        const $input = $(this);
        const taxonomy = $input.data('taxonomy');
        const searchTerm = $input.val().trim();
        const $results = $input.siblings('.autocomplete-results');
        
        if (searchTerm.length < 1) {
            $results.empty().hide();
            return;
        }
        
        // Get results from local storage
        const results = attributeStorage.search(taxonomy, searchTerm);
        
        if (results.length > 0) {
            $results.empty();
            results.forEach(term => {
                $results.append(`
                    <div class="autocomplete-item" 
                        data-id="${term.id}" 
                        data-value="${term.name}">
                        ${term.name}
                    </div>
                `);
            });
            $results.show();
        } else {
            $results.hide();
        }
    });

    // Autocomplete item selection
    $('#productsTable').on('click', '.autocomplete-item', function() {
        const $item = $(this);
        const value = $item.data('value');
        const $input = $item.closest('.attribute-input').find('input');
        const $cell = $item.closest('.attribute-cell');
        
        $input.val(value);
        $item.closest('.autocomplete-results').empty().hide();
        
        // Save immediately on selection
        saveAttributeValue($cell);
    });

    // Add keyboard navigation to autocomplete
    $('#productsTable').on('keydown', '.attribute-autocomplete', function(e) {
        const $input = $(this);
        const $results = $input.siblings('.autocomplete-results');
        const $items = $results.find('.autocomplete-item');
        const $highlighted = $items.filter('.highlighted');
        let index = $items.index($highlighted);
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            index = (index + 1) % $items.length;
            $items.removeClass('highlighted');
            $items.eq(index).addClass('highlighted');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            index = (index - 1 + $items.length) % $items.length;
            $items.removeClass('highlighted');
            $items.eq(index).addClass('highlighted');
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if ($highlighted.length) {
                $highlighted.click();
            } else if ($items.length) {
                $items.first().click();
            } else {
                // Save current value
                const $cell = $input.closest('.attribute-cell');
                saveAttributeValue($cell);
            }
        } else if (e.key === 'Escape') {
            $results.empty().hide();
            $input.blur();
        }
    });

    // Add hover effect for autocomplete items
    $('#productsTable').on('mouseenter', '.autocomplete-item', function() {
        $(this).siblings().removeClass('highlighted');
        $(this).addClass('highlighted');
    });
    
    // Select autocomplete item
    $('#productsTable').on('click', '.autocomplete-item', function() {
        const $item = $(this);
        const value = $item.data('value');
        const $input = $item.closest('.attribute-input').find('input');
        const $cell = $item.closest('.attribute-cell');
        
        $input.val(value);
        $item.closest('.autocomplete-results').empty().hide();
        
        // Save immediately on selection
        saveAttributeValue($cell);
    });
    
    // Save attribute on blur
    $('#productsTable').on('blur', '.attribute-autocomplete', function() {
        const $input = $(this);
        const $cell = $input.closest('.attribute-cell');
        saveAttributeValue($cell);
    });
    
    // Close autocomplete when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.autocomplete-results').length && 
            !$(e.target).hasClass('attribute-autocomplete')) {
            $('.autocomplete-results').empty().hide();
        }
    });

    // Stock status change handler
    $('#productsTable').on('change', '.stock-status-select', function() {
        const $select = $(this);
        const productId = $select.data('productid');
        const newStatus = $select.val();
        const originalStatus = $select.data('original');

        if (newStatus !== originalStatus) {
            $select.prop('disabled', true).addClass('saving');
            $select.after('<span class="saving-indicator"></span>');

            var currentUrl = window.location.href;
            var baseUrl = currentUrl.substring(0, currentUrl.lastIndexOf('/'));

            $.ajax({
                url: baseUrl + '/includes/save_product_stock.php',
                method: 'POST',
                data: {
                    product_id: productId,
                    status: newStatus
                },
                success: function() {
                    $select.removeClass('saving').data('original', newStatus);
                    $select.siblings('.saving-indicator').remove();
                    $select.prop('disabled', false);
                    
                    // Show success badge
                    const $badge = $(`<span class="badge bg-success position-absolute top-0 start-100 translate-middle">Saved!</span>`);
                    $select.parent().append($badge);
                    setTimeout(() => $badge.fadeOut(500, () => $badge.remove()), 2000);
                },
                error: function() {
                    $select.removeClass('saving').prop('disabled', false)
                        .siblings('.saving-indicator').remove();
                    $select.val(originalStatus); // Revert to original value
                    
                    // Show error badge
                    const $badge = $(`<span class="badge bg-danger position-absolute top-0 start-100 translate-middle">Error!</span>`);
                    $select.parent().append($badge);
                    setTimeout(() => $badge.fadeOut(500, () => $badge.remove()), 2000);
                }
            });
        }
    });
    
    //save description
    $('.save-description').on('click', function() {
        const productId = $(this).data('id');
        const productContent = $(this).closest('.modal-footer').siblings('.modal-body').find('textarea').val();
        const button = $(this);
        
        var currentUrl = window.location.href;
        var baseUrl = currentUrl.substring(0, currentUrl.lastIndexOf('/'));

        console.log(productId);
        console.log(productContent);
        
        console.log(baseUrl + '/includes/save_product_content.php');
        // AJAX request
        $.ajax({
            url: baseUrl + '/includes/save_product_content.php',
            type: 'POST',
            data: {
                product_id: productId,
                content: productContent
            },
            success: function(response) {
                console.log('Raw response:', response); // Log the full response
                try {
                    var jsonResponse = JSON.parse(response);
                    if (jsonResponse.status === 'success') {
                        alert(jsonResponse.message);
                    } else {
                        alert('Error: ' + jsonResponse.message);
                    }
                } catch (e) {
                    console.error('Parsing error:', e);
                    alert('Unexpected response format.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                alert('Error saving description: ' + errorThrown);
            }
        });
    });

});