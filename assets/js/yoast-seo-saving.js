function updateYoastTitle($field){
    const productId = $field.data('productid');
    let newValue = $field.text().trim();
    const originalValue = $field.data('original');

    // Add to update queue
    addToUpdateQueue({
        product_id: productId,
        field_name: '_yoast_wpseo_title',
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

function updateYoastDesc($field){
    const productId = $field.data('productid');
    let newValue = $field.text().trim();
    const originalValue = $field.data('original');

    // Add to update queue
    addToUpdateQueue({
        product_id: productId,
        field_name: '_yoast_wpseo_metadesc',
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
