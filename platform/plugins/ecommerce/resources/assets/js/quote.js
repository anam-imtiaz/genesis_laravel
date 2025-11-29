$(() => {
    // Completely override delete handler for quotes to ensure table reloads
    // We need to intercept before the default handler runs
    $(document).on('click', '.delete-crud-entry', function(event) {
        const $self = $(this);
        const deleteURL = $self.data('section');
        const tableId = $self.data('parent-table');
        console.log($self);
        console.log(deleteURL);
        console.log(tableId);
        return false;
        
        // Only handle quotes deletions - let others use default handler
        if (deleteURL && deleteURL.includes('quotes')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            
            Botble.showButtonLoading($self);
            
            $httpClient
                .make()
                .delete(deleteURL)
                .then(({ data }) => {
                    // Close the modal first, before showing success message
                    const $modal = $self.closest('.modal');
                    if ($modal.length) {
                        $modal.modal('hide');
                    }
                    $('.modal-confirm-delete').modal('hide');
                    
                    // Remove modal backdrop if it exists
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                    
                    // Show success message after modal is closed
                    setTimeout(() => {
                        Botble.showSuccess(data.message || 'Deleted successfully');
                    }, 100);
                    
                    // Find and reload the quotes table
                    let dataTable = null;
                    let foundTableId = tableId;
                    
                    // Method 1: Use the tableId from data attribute
                    if (foundTableId && window.LaravelDataTables && window.LaravelDataTables[foundTableId]) {
                        dataTable = window.LaravelDataTables[foundTableId];
                    }
                    
                    // Method 2: Find table by class in DOM
                    if (!dataTable) {
                        const $quotesTable = $('table.quotes-table');
                        if ($quotesTable.length) {
                            foundTableId = $quotesTable.attr('id');
                            if (foundTableId && window.LaravelDataTables && window.LaravelDataTables[foundTableId]) {
                                dataTable = window.LaravelDataTables[foundTableId];
                            }
                        }
                    }
                    
                    // Method 3: Search for any table with quotes in the ID
                    if (!dataTable && window.LaravelDataTables) {
                        for (const key in window.LaravelDataTables) {
                            if (key.toLowerCase().includes('quotes') || key.toLowerCase().includes('quote')) {
                                dataTable = window.LaravelDataTables[key];
                                foundTableId = key;
                                break;
                            }
                        }
                    }
                    
                    // Method 4: Try to get DataTable instance directly from jQuery
                    if (!dataTable) {
                        const $quotesTable = $('table.quotes-table');
                        if ($quotesTable.length && $.fn.DataTable) {
                            try {
                                const dt = $quotesTable.DataTable();
                                if (dt) {
                                    dataTable = dt;
                                }
                            } catch (e) {
                                console.error('Error getting DataTable:', e);
                            }
                        }
                    }
                    
                    // Reload the table if found
                    if (dataTable) {
                        if (dataTable.ajax && typeof dataTable.ajax.reload === 'function') {
                            dataTable.ajax.reload(null, false);
                        } else if (typeof dataTable.draw === 'function') {
                            dataTable.draw(false);
                        }
                    } else {
                        // Fallback: reload the page if table not found
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                })
                .catch((error) => {
                    Botble.showError(error.response?.data?.message || 'Delete failed');
                    const $modal = $self.closest('.modal');
                    if ($modal.length) {
                        $modal.modal('hide');
                    }
                    $('.modal-confirm-delete').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                })
                .finally(() => {
                    Botble.hideButtonLoading($self);
                });
            
            return false;
        }
    });
    
    // Handle bulk delete for quotes table
    $(document).on('click', '.confirm-trigger-bulk-actions-button', function(event) {
        const $self = $(this);
        const tableId = $self.data('table-id');
        const url = $self.data('href');
        const method = $self.data('method');
        console.log($self);
        console.log(tableId);
        console.log(url);
        console.log(method);
        
        return false;
        
        // Check if this is for quotes table
        if (url && url.includes('quotes') && tableId) {
            // Store table ID for later use
            const quotesTableId = tableId;
            
            // Override the default bulk action handler
            event.preventDefault();
            event.stopImmediatePropagation();
            
            // Get selected IDs
            const ids = [];
            $(`#${tableId}`).find('.checkboxes:checked').each(function() {
                ids.push($(this).val());
            });
            
            if (ids.length === 0) {
                Botble.showError('Please select at least one record to perform this action!');
                return false;
            }
            
            Botble.showButtonLoading($self);
            
            $httpClient
                .make()
                .post(url, {
                    'ids-checked': ids,
                })
                .then(({ data }) => {
                    // Close the modal first
                    const $modal = $self.closest('.modal');
                    if ($modal.length) {
                        $modal.modal('hide');
                    }
                    $('.bulk-action-confirm-modal').modal('hide');
                    
                    // Remove modal backdrop if it exists
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                    
                    // Show success message after modal is closed
                    setTimeout(() => {
                        Botble.showSuccess(data.message || 'Deleted successfully');
                    }, 100);
                    
                    // Find and reload the quotes table
                    let dataTable = null;
                    
                    // Try to find the table
                    if (window.LaravelDataTables && window.LaravelDataTables[quotesTableId]) {
                        dataTable = window.LaravelDataTables[quotesTableId];
                    } else {
                        // Try to find by class
                        const $quotesTable = $('table.quotes-table');
                        if ($quotesTable.length) {
                            const actualTableId = $quotesTable.attr('id');
                            if (actualTableId && window.LaravelDataTables && window.LaravelDataTables[actualTableId]) {
                                dataTable = window.LaravelDataTables[actualTableId];
                            }
                        }
                    }
                    
                    // Reload the table
                    if (dataTable) {
                        if (dataTable.ajax && typeof dataTable.ajax.reload === 'function') {
                            dataTable.ajax.reload(null, false);
                        } else if (typeof dataTable.draw === 'function') {
                            dataTable.draw(false);
                        }
                    }
                })
                .catch((error) => {
                    Botble.showError(error.response?.data?.message || 'Bulk delete failed');
                    const $modal = $self.closest('.modal');
                    console.log($modal);
                    if ($modal.length) {
                        $modal.modal('hide');
                    //     $('.bulk-action-confirm-modal').modal('hide');
                    //     $('.modal-backdrop').remove();
                    //     $('body').removeClass('modal-open');
                    //     $('body').css('padding-right', '');
                    // }
                })
                .finally(() => {
                    Botble.hideButtonLoading($self);
                });
            
            return false;
        }
    });
});

