jQuery(document).ready(function ($) {
    'use strict';

    const form = $('#wc-bulk-price-form');
    const previewBtn = $('#preview-changes');
    const applyBtn = $('#apply-changes');
    const previewContainer = $('#preview-container');
    const previewContent = $('#preview-content');
    const resultsContainer = $('#results-container');
    const resultsContent = $('#results-content');

    // Initialize Select2 (or SelectWoo in newer WooCommerce)
    const selectMethod = $.fn.selectWoo ? 'selectWoo' : 'select2';

    if ($.fn[selectMethod]) {
        $('.wc-category-select')[selectMethod]({
            width: '100%',
            allowClear: true,
            placeholder: $(this).data('placeholder'), // Ensure placeholder works
            language: {
                noResults: function () {
                    return "Nenalezena žádná kategorie";
                }
            }
        });
    }

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
     * Helper to get content from WP Editor (TinyMCE) or Textarea
     */
    function getWysiwygContent(id) {
        let content;
        if (typeof tinymce !== 'undefined' && tinymce.get(id) && !tinymce.get(id).isHidden()) {
            content = tinymce.get(id).getContent();
        } else {
            content = $('#' + id).val();
        }
        return content;
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
            new_price: $('#new_price').val(),
            new_short_description: getWysiwygContent('new_short_description'),
            new_description: getWysiwygContent('new_description')
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
            confirmMessage += '\n\nUpozornění: Aktualizace bude probíhat po částech (dávky po 50), aby se předešlo chybám serveru.';
        }

        if (!confirm(confirmMessage)) {
            return;
        }

        // Setup batch processing
        const batchSize = 50;
        const totalBatches = Math.ceil(selectedChanges.length / batchSize);
        let currentBatch = 0;
        let successCount = 0;
        let allHtmlResults = '';
        let hasErrors = false;

        // UI Prep
        resultsContainer.hide();
        resultsContent.html('');
        const originalBtnText = applyBtn.text();
        applyBtn.prop('disabled', true);
        previewBtn.prop('disabled', true);

        function processBatch(startIndex) {
            currentBatch++;
            const batchChanges = selectedChanges.slice(startIndex, startIndex + batchSize);
            const progressPercent = Math.round((currentBatch / totalBatches) * 100);
            const processedCount = Math.min(startIndex + batchChanges.length, selectedChanges.length);

            applyBtn.text(
                wcBulkPriceEditor.strings.loading + ' (' + processedCount + '/' + selectedChanges.length + ')'
            );

            const formData = {
                action: 'wc_bulk_price_update',
                nonce: wcBulkPriceEditor.nonce,
                category_id: $('#category_id').val(),
                old_price: $('#old_price').val(),
                new_price: $('#new_price').val(),
                new_short_description: getWysiwygContent('new_short_description'),
                new_description: getWysiwygContent('new_description'),
                selected_changes: batchChanges
            };

            $.ajax({
                url: wcBulkPriceEditor.ajax_url,
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        successCount += response.data.success_count;

                        // Accumulate only the list items, stripping the outer wrappers if possible, 
                        // or just appending the whole block is fine for now.
                        allHtmlResults += response.data.html;

                        if (startIndex + batchSize < selectedChanges.length) {
                            // Process next batch
                            processBatch(startIndex + batchSize);
                        } else {
                            // Finished
                            finishProcessing();
                        }
                    } else {
                        hasErrors = true;
                        resultsContent.append('<div class="notice notice-error"><p>' + (response.data.message || wcBulkPriceEditor.strings.error) + '</p></div>');
                        finishProcessing();
                    }
                },
                error: function () {
                    hasErrors = true;
                    resultsContent.append('<div class="notice notice-error"><p>' + wcBulkPriceEditor.strings.error + '</p></div>');
                    finishProcessing();
                }
            });
        }

        function finishProcessing() {
            applyBtn.text(originalBtnText).prop('disabled', true); // Keep disabled after run
            previewBtn.prop('disabled', false);

            // Construct final summary
            let finalHtml = '';

            if (successCount > 0) {
                finalHtml += '<div class="notice notice-success"><p><strong>' +
                    'Úspěšně aktualizováno ' + successCount + ' cen (zpracováno v ' + currentBatch + ' dávkách).' +
                    '</strong></p></div>';
            }

            resultsContent.html(finalHtml + allHtmlResults);
            resultsContainer.show();
            previewContainer.hide();

            // Scroll to results
            $('html, body').animate({
                scrollTop: resultsContainer.offset().top - 50
            }, 500);

            // Reset form partly? 
            // In original code we reset form. Here we might want to keep it to see what was done.
            // But let's follow previous logic -> reset form after success.
            if (!hasErrors) {
                form[0].reset();
                $('.wc-category-select').val(null).trigger('change');

                // Reset TinyMCE editors
                if (typeof tinymce !== 'undefined') {
                    if (tinymce.get('new_short_description')) tinymce.get('new_short_description').setContent('');
                    if (tinymce.get('new_description')) tinymce.get('new_description').setContent('');
                }
            }
        }

        // Start first batch
        processBatch(0);
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
    form.on('change', 'input, select, textarea', function () {
        previewContainer.hide();
        resultsContainer.hide();
        applyBtn.prop('disabled', true);
    });
});
