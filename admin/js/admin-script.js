jQuery(document).ready(function ($) {
    'use strict';

    const form = $('#wc-bulk-price-form');
    const previewBtn = $('#preview-changes');
    const applyBtn = $('#apply-changes');
    const previewContainer = $('#preview-container');
    const previewContent = $('#preview-content');
    const resultsContainer = $('#results-container');
    const resultsContent = $('#results-content');

    /**
     * Show loading state
     */
    function showLoading(button) {
        button.prop('disabled', true);
        button.data('original-text', button.text());
        button.text(wcBulkPriceEditor.strings.loading);
    }

    /**
     * Hide loading state
     */
    function hideLoading(button) {
        button.prop('disabled', false);
        button.text(button.data('original-text'));
    }

    /**
     * Show error message
     */
    function showError(message) {
        const errorHtml = `
            <div class="notice notice-error is-dismissible">
                <p><strong>${message}</strong></p>
            </div>
        `;
        previewContent.html(errorHtml);
        previewContainer.show();
    }

    /**
     * Preview changes
     */
    previewBtn.on('click', function (e) {
        e.preventDefault();

        // Validate form
        if (!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }

        showLoading(previewBtn);
        resultsContainer.hide();
        applyBtn.prop('disabled', true);

        const formData = {
            action: 'wc_bulk_price_preview',
            nonce: wcBulkPriceEditor.nonce,
            category_id: $('#category_id').val(),
            old_price: $('#old_price').val(),
            new_price: $('#new_price').val()
        };

        $.ajax({
            url: wcBulkPriceEditor.ajax_url,
            type: 'POST',
            data: formData,
            success: function (response) {
                hideLoading(previewBtn);

                if (response.success) {
                    previewContent.html(response.data.html);
                    previewContainer.show();
                    applyBtn.prop('disabled', false);

                    // Scroll to preview
                    $('html, body').animate({
                        scrollTop: previewContainer.offset().top - 50
                    }, 500);
                } else {
                    showError(response.data.message || wcBulkPriceEditor.strings.error);
                }
            },
            error: function () {
                hideLoading(previewBtn);
                showError(wcBulkPriceEditor.strings.error);
            }
        });
    });

    /**
     * Apply changes
     */
    applyBtn.on('click', function (e) {
        e.preventDefault();

        // Get selected changes
        const selectedChanges = [];
        $('.change-checkbox:checked').each(function () {
            selectedChanges.push($(this).val());
        });

        if (selectedChanges.length === 0) {
            alert('Prosím vyberte alespoň jeden produkt/variantu ke změně.');
            return;
        }

        // Confirm action
        let confirmMessage = wcBulkPriceEditor.strings.confirm_update +
            '\n\nPočet vybraných změn: ' + selectedChanges.length;

        if (selectedChanges.length > 100) {
            confirmMessage += '\n\nUpozornění: Aktualizace může trvat několik minut.';
        }

        if (!confirm(confirmMessage)) {
            return;
        }

        showLoading(applyBtn);
        previewBtn.prop('disabled', true);

        const formData = {
            action: 'wc_bulk_price_update',
            nonce: wcBulkPriceEditor.nonce,
            category_id: $('#category_id').val(),
            old_price: $('#old_price').val(),
            new_price: $('#new_price').val(),
            selected_changes: selectedChanges
        };

        $.ajax({
            url: wcBulkPriceEditor.ajax_url,
            type: 'POST',
            data: formData,
            success: function (response) {
                hideLoading(applyBtn);
                previewBtn.prop('disabled', false);

                if (response.success) {
                    resultsContent.html(response.data.html);
                    resultsContainer.show();
                    previewContainer.hide();
                    applyBtn.prop('disabled', true);

                    // Reset form
                    form[0].reset();

                    // Scroll to results
                    $('html, body').animate({
                        scrollTop: resultsContainer.offset().top - 50
                    }, 500);
                } else {
                    showError(response.data.message || wcBulkPriceEditor.strings.error);
                }
            },
            error: function () {
                hideLoading(applyBtn);
                previewBtn.prop('disabled', false);
                showError(wcBulkPriceEditor.strings.error);
            }
        });
    });

    /**
     * Select all changes functionality
     */
    $('#select-all-changes, .select-all-checkbox').on('change', function () {
        const isChecked = $(this).prop('checked');
        $('.change-checkbox').prop('checked', isChecked);
        $('#select-all-changes').prop('checked', isChecked);
        $('.select-all-checkbox').prop('checked', isChecked);
        updateApplyButtonState();
    });

    /**
     * Individual checkbox change
     */
    $(document).on('change', '.change-checkbox', function () {
        updateSelectAllState();
        updateApplyButtonState();
    });

    /**
     * Update select all checkbox state
     */
    function updateSelectAllState() {
        const totalCheckboxes = $('.change-checkbox').length;
        const checkedCheckboxes = $('.change-checkbox:checked').length;

        const selectAllChecked = totalCheckboxes === checkedCheckboxes;
        $('#select-all-changes').prop('checked', selectAllChecked);
        $('.select-all-checkbox').prop('checked', selectAllChecked);
    }

    /**
     * Update apply button state based on selection
     */
    function updateApplyButtonState() {
        const hasSelection = $('.change-checkbox:checked').length > 0;
        applyBtn.prop('disabled', !hasSelection);
    }

    /**
     * Reset preview when form changes
     */
    form.on('change', 'input, select', function () {
        previewContainer.hide();
        resultsContainer.hide();
        applyBtn.prop('disabled', true);
    });
});
