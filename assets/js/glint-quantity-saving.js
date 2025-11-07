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

            // Add to update queue
            addToUpdateQueue({
                product_id: productId,
                field_name: 'glint_qty_suffix',
                new_value: newStatus,
                old_value: originalStatus
            }).then(() => {
                $select.removeClass('saving').data('original', newStatus);
                $select.siblings('.saving-indicator').remove();
                $select.prop('disabled', false);

                // Show success badge
                const $badge = $(`<span class="badge bg-success position-absolute top-0 start-100 translate-middle">Saved!</span>`);
                $select.parent().append($badge);
                setTimeout(() => $badge.fadeOut(500, () => $badge.remove()), 2000);
            }).catch(() => {
                $select.removeClass('saving').prop('disabled', false)
                    .siblings('.saving-indicator').remove();
                $select.val(originalStatus); // Revert to original value

                // Show error badge
                const $badge = $(`<span class="badge bg-danger position-absolute top-0 start-100 translate-middle">Error!</span>`);
                $select.parent().append($badge);
                setTimeout(() => $badge.fadeOut(500, () => $badge.remove()), 2000);
            });
        }
    });
});


function updateQuantityStep($field){
    const productId = $field.data('productid');
    let newValue = $field.text().trim();
    const originalValue = $field.data('original');

    // Add to update queue
    addToUpdateQueue({
        product_id: productId,
        field_name: 'glint_qty_step',
        new_value: newValue,
        old_value: originalValue
    }).then(() => {
        $field.removeClass('saving').addClass('saved');
        setTimeout(() => $field.removeClass('saved'), 2000);

        $field.data('original', newValue);
    }).catch(() => {
        $field.removeClass('saving').addClass('error');
        setTimeout(() => $field.removeClass('error'), 2000);

        // Revert to original value on error
        $field.text($field.data('original'));
    });
}
