$(document).ready(function(){
    
    // prefix change handler
    $('#productsTable').on('change', '.glint-suffix-select', function() {
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
                url: baseUrl + '/includes/save_product_suffix.php',
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
});


function updateQuantityStep($field){
    const productId = $field.data('productid');
    let newValue = $field.text().trim();

    var currentUrl = window.location.href;
    var baseUrl = currentUrl.substring(0, currentUrl.lastIndexOf('/'));

    $.ajax({
        url: baseUrl + '/includes/save_product_step.php',
        method: 'POST',
        data: {
            product_id: productId,
            status: newValue
        },
        success: function() {
            $field.removeClass('saving').addClass('saved');
            setTimeout(() => $field.removeClass('saved'), 2000);

            $field.data('original', newValue);
        },
        error: function() {
            $field.removeClass('saving').addClass('error');
            setTimeout(() => $field.removeClass('error'), 2000);
            
            // Revert to original value on error
            $field.text($field.data('original'));
        }
    });
}