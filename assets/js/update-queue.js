$(document).ready(function() {
    let updateQueue = [];
    let isProcessing = false;
    let pollingInterval = null;

    // Initialize queue system
    function initUpdateQueue() {
        startPolling();
        setupUnloadWarning();
    }

    // Add update to queue
    window.addToUpdateQueue = function(data) {
        return new Promise((resolve, reject) => {
            const queueData = {
                product_id: data.product_id,
                field_name: data.field_name,
                new_value: data.new_value,
                old_value: data.old_value,
                taxonomy: data.taxonomy // for attributes
            };

            $.ajax({
                url: getBaseUrl() + '/includes/add_to_update_queue.php',
                method: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify(queueData),
                success: function(response) {
                    if (response.success) {
                        if (response.queued) {
                            updateQueue.push({
                                id: response.queue_id,
                                ...queueData,
                                status: 'queued'
                            });
                            console.log("Add-to-queue get success response");
                            updateQueueDisplay();
                        }
                        resolve(response);
                    } else {
                        reject(new Error(response.message));
                    }              
                },
                error: function(xhr) {
                    reject(new Error('Queue request failed'));
                }
            });
        });
    };

    // Process queue
    function processQueue() {
        console.log("Checking queue...");
        if (isProcessing || updateQueue.length === 0) return;

        isProcessing = true;

        console.log("start update queue");

        $.ajax({
            url: getBaseUrl() + '/includes/process_update_queue.php',
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.processed) {

                    console.log("Get success message, will update queue");
                    // Remove processed item from queue
                    updateQueue = updateQueue.filter(item => item.id != response.update_id);
                    updateQueueDisplay();

                    // Continue processing if more items
                    if (updateQueue.length > 0) {
                        setTimeout(processQueue, 500); // Small delay between processes
                    }
                }
                isProcessing = false;
            },
            error: function(xhr) {
                console.log(xhr);
                isProcessing = false;
            }
        });
    }

    // Start polling for queue status
    function startPolling() {
        pollingInterval = setInterval(function() {
            $.ajax({
                url: getBaseUrl() + '/includes/get_update_queue.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateQueue = response.queue.map(item => ({
                            id: item.id,
                            product_id: item.product_id,
                            field_name: item.field_name,
                            new_value: item.new_value,
                            old_value: item.old_value,
                            status: 'pending',
                            timestamp: item.timestamp
                        }));
                        updateQueueDisplay();

                        // Process queue if not already processing
                        if (!isProcessing && updateQueue.length > 0) {
                            processQueue();
                        }
                    }
                },
                error: function(xhr){
                   console.log(xhr); 
                }
            });
        }, 3000); // Poll every 3 seconds
    }

    // Update queue display
    function updateQueueDisplay() {
        const $queueContainer = $('.update-queue');

        if ($queueContainer.find('.queue-count').length === 0) {
            // Initialize the queue display if not exists
            $queueContainer.html(`
                <div class="queue-status-bar" style="display: none;">
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="fas fa-clock me-2"></i>
                        <span>Processing updates...Do not close this page.</span>
                        <button type="button" class="btn btn-sm btn-outline-info ms-auto" onclick="showUpdateHistory()">
                            <i class="fas fa-history me-1"></i>History
                        </button>
                    </div>
                    <div class="queue-list mt-2" style="display: none;">
                        <div class="queue-items">
                            <!-- Queue items will be populated here -->
                        </div>
                    </div>
                </div>
            `);
        }

        const $statusBar = $queueContainer.find('.queue-status-bar');
        const $queueListContainer = $queueContainer.find('.queue-list');
        const $queueCount = $queueContainer.find('.queue-count');

        $queueCount.text(updateQueue.length);

        if (updateQueue.length > 0) {
            $queueContainer.show();
            $statusBar.show();
            $queueListContainer.show();

            const $queueItems = $queueListContainer.find('.queue-items');
            $queueItems.empty();
            updateQueue.forEach(item => {
                const $item = $(`
                    <div class="queue-item d-flex justify-content-between align-items-center p-2 border-bottom">
                        <div>
                            <strong>${item.field_name}</strong> - Product #${item.product_id}
                        </div>
                        <div>
                            <span class="badge bg-${item.status === 'pending' ? 'warning' : 'info'}">${item.status}</span>
                        </div>
                    </div>
                `);
                $queueItems.append($item);
            });
        } else {
            $queueContainer.hide();
            $statusBar.hide();
            $queueListContainer.hide();
        }
    }

    // Setup unload warning
    function setupUnloadWarning() {
        $(window).on('beforeunload', function(e) {
            if (updateQueue.length > 0) {
                e.preventDefault();
                return 'You have pending updates in the queue. Are you sure you want to leave?';
            }
        });
    }

    // Get base URL
    function getBaseUrl() {
        const currentUrl = window.location.href;
        return currentUrl.substring(0, currentUrl.lastIndexOf('/'));
    }

    // Show history modal
    window.showUpdateHistory = function() {
        $.ajax({
            url: getBaseUrl() + '/includes/get_update_queue.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const $historyModal = $('#updateHistoryModal');
                    const $historyList = $historyModal.find('.history-list');

                    $historyList.empty();
                    response.history.forEach(item => {
                        const $item = $(`
                            <div class="history-item">
                                <div class="history-timestamp">${new Date(item.timestamp).toLocaleString()}</div>
                                <div class="history-field">${item.field_name}</div>
                                <div class="history-product">Product #${item.product_id}</div>
                                <div class="history-change">
                                    <span class="old-value">${item.old_value || 'empty'}</span> →
                                    <span class="new-value">${item.new_value}</span>
                                </div>
                            </div>
                        `);
                        $historyList.append($item);
                    });

                    $historyModal.modal('show');
                }
            }
        });
    };

    // Initialize
    initUpdateQueue();
});
