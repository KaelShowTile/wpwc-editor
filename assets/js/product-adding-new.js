$(document).ready(function() {
    // Load categories when modal is shown
    $('#addProductModal').on('shown.bs.modal', function() {
        loadCategories();
    });

    // Show modal when Add Product button is clicked
    $('#AddNewProduct').click(function() {
        $('#addProductModal').modal('show');
    });

    // Toggle stock quantity field based on manage stock checkbox
    $('#manageStock').change(function() {
        if ($(this).is(':checked')) {
            $('#stockQuantityContainer').show();
        } else {
            $('#stockQuantityContainer').hide();
        }
    });

    // Add attribute row
    $('#addAttribute').click(function() {
        const newRow = `
            <div class="attribute-row mb-2">
                <div class="row">
                    <div class="col-5">
                        <input type="text" class="form-control" name="attribute_names[]" placeholder="Attribute name (e.g. Size)">
                    </div>
                    <div class="col-5">
                        <input type="text" class="form-control" name="attribute_values[]" placeholder="Values (separated by | )">
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-danger remove-attribute"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
        `;
        $('#attributesContainer').append(newRow);
    });

    // Remove attribute row
    $(document).on('click', '.remove-attribute', function() {
        $(this).closest('.attribute-row').remove();
    });

    // Category search functionality
    $('#categorySearch').on('keyup', function() {
        const searchText = $(this).val().toLowerCase();
        if (searchText === '') {
            $('.category-item').show();
            $('.category-children').show();
            return;
        }
        
        $('.category-item').each(function() {
            const categoryName = $(this).find('.form-check-label').text().toLowerCase();
            const hasMatchingChildren = $(this).find('.category-children .form-check-label:contains(' + searchText + ')').length > 0;
            
            if (categoryName.includes(searchText) || hasMatchingChildren) {
                $(this).show();
                $(this).find('.category-children').show();
            } else {
                $(this).hide();
            }
        });
    });
    // Load categories from server
    function loadCategories() {
        var currentUrl = window.location.href;
        var baseUrl = currentUrl.substring(0, currentUrl.lastIndexOf('/'));
        
        $.ajax({
            url: baseUrl + '/includes/load_category.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderCategories(response.categories);
                } else {
                    $('#categoryContainer').html('<div class="text-danger">Error loading categories: ' + response.message + '</div>');
                }
            },
            error: function() {
                $('#categoryContainer').html('<div class="text-danger">Error loading categories. Please try again.</div>');
            }
        });
    }

    // Render categories in hierarchical structure
    function renderCategories(categories, level = 0) {
        let html = '';
        
        if (!categories || categories.length === 0) {
            if (level === 0) {
                html = '<div class="text-muted">No categories found.</div>';
            }
            return html;
        }
        
        categories.forEach(function(category) {
            // Check if category exists and has required properties
            if (!category || typeof category !== 'object') {
                console.error('Invalid category data:', category);
                return;
            }
            
            const hasChildren = category.children && category.children.length > 0;
            const indent = level * 20;
            
            html += `
                <div class="category-item" data-id="${category.term_id}" data-parent="${category.parent || 0}">
                    <div class="form-check" style="margin-left: ${indent}px;">
                        <input class="form-check-input" type="checkbox" name="product_categories[]" value="${category.term_id}" id="category-${category.term_id}">
                        <label class="form-check-label" for="category-${category.term_id}">
                            ${category.name || 'Unnamed Category'}
                        </label>
                    </div>
            `;
            
            if (hasChildren) {
                html += `
                    <div class="category-children ms-3" style="display: block;">
                        ${renderCategories(category.children, level + 1)}
                    </div>
                `;
            }
            
            html += `</div>`;
        });
        
        // If we're at the top level, update the container
        if (level === 0) {
            $('#categoryContainer').html(html);
        }
        
        return html;
    }

    // Image preview
    $('#productImage').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').html(`<img src="${e.target.result}" alt="Preview">`);
            }
            reader.readAsDataURL(file);
        }
    });
    

    // Submit form via AJAX
    $('#submitProduct').click(function() {
        // Basic validation
        if (!$('#productName').val()) {
            alert('Product name is required');
            return;
        }
        
        // Add form data
        const newProduct = {
            product_name: $('#productName').val(),
            product_description: $('#productDescription').val(),
            short_description: $('#shortDescription').val(),
            product_category: [],
            product_tags: $('#productTags').val(),
            regular_price: $('#regularPrice').val(),
            sale_price: $('#salePrice').val(),
            //taxable: $('#taxable').is(':checked') ? '1' : '0',
            //tax_class: $('#taxClass').val(),
            sku: $('#sku').val(),
            stock_status: $('#stockStatus').val(),
            manage_stock: $('#manageStock').is(':checked') ? '1' : '0',
            stock_quantity: $('#stockQuantity').val(),
            weight: $('#weight').val(),
            length: $('#length').val(),
            width: $('#width').val(),
            height: $('#height').val(),
            //shipping_class: $('#shippingClass').val()
            product_image_id: $('#productImageId').val(),
            product_gallery_ids: $('#productGalleryIds').val()
        }

        $('input[name="product_categories[]"]:checked').each(function() {
            if ($(this).val()){
                newProduct.product_category.push($(this).val());
            }
        });
        
        // Add attributes
        $('.attribute-row').each(function(index) {
            const name = $(this).find('input[name="attribute_names[]"]').val();
            const value = $(this).find('input[name="attribute_values[]"]').val();
            if (name && value) {
                newProduct['attributes[' + index + '][name]'] = name;
                newProduct['attributes[' + index + '][value]'] = value;
            }
        });
        
        // Add image file if selected
        if ($('#productImageId')) {
            newProduct['product_image'] = $('#productImageId').val();
        }

        // Show loading state
        const submitBtn = $('#submitProduct');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...');

        var currentUrl = window.location.href;
        var baseUrl = currentUrl.substring(0, currentUrl.lastIndexOf('/'));

        console.log(newProduct);

        // AJAX request
        $.ajax({
            url: baseUrl + '/includes/add_new_product.php',
            type: 'POST',
            data: newProduct,
            success: function(response) {
                console.log("Raw response:", response);
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Show success message
                        $('#successToast').toast('show');
                        
                        // Close modal after a delay
                        setTimeout(function() {
                            $('#addProductModal').modal('hide');
                            // Reset form
                            $('#addProductForm')[0].reset();
                            //clear thumb
                            $('#imagePreview').html('<span class="text-muted"></span>');
                            $('#productImageId').attr('value', "");
                            //clear gallery
                            $('#galleryPreview').html('<span class="text-muted"></span>');
                            $('#productGalleryIds').attr('value', "");
                            
                            // Reset button state
                            submitBtn.prop('disabled', false).html('Add Product');
                        }, 1500);
                    } else {
                        // Show error message
                        showAlert('danger', 'Error adding product: ' + data.message);
                    }
                } catch (e) {
                    $('#errorMessage').text('Invalid response from server');
                    $('#errorToast').toast('show');
                    submitBtn.prop('disabled', false).html('Add Product');
                }
            },
            error: function() {
                $('#errorMessage').text('Request failed. Please try again.');
                $('#errorToast').toast('show');
                submitBtn.prop('disabled', false).html('Add Product');
            }
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