$(document).ready(function() {
    let selectedMediaId = null;
    let selectedMediaUrl = null;
    let currentPage = 1;
    let isLoading = false;
    let hasMoreMedia = true;
    let galleryMediaIds = [];
    let galleryMediaUrls = {};
    let currentModalMode = 'thumbnail'; // 'thumbnail' or 'gallery'
    
    // Open media library modal
    $('#mediaLibraryBtn').click(function() {
        $('#mediaLibraryModal').modal('show');
        resetMediaLibrary();
        loadMediaLibrary();
    });
    
    // Remove selected image
    $('#removeImageBtn').click(function() {
        $('#productImageId').val('');
        $('#imagePreview').html('<span class="text-muted">No image selected</span>');
        $(this).hide();
        selectedMediaId = null;
        selectedMediaUrl = null;
    });
    
    // Refresh media library
    $('#refreshMedia').click(function() {
        resetMediaLibrary();
        loadMediaLibrary();
    });
    
    // Enable upload button when file is selected
    $('#mediaUpload').change(function() {
        const file = this.files[0];
        $('#uploadMediaBtn').prop('disabled', !file);
        
        // Show preview if file is selected
        if (file) {
            showUploadPreview(file);
        } else {
            $('#uploadPreviewContainer').hide();
        }
    });

    // Show preview of selected file
    function showUploadPreview(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#uploadPreview').html(`<img src="${e.target.result}" class="img-fluid" alt="Preview" style="max-height: 200px;">`);
            $('#uploadPreviewContainer').show();
            
            // Set default title if not already set
            if (!$('#imageTitle').val()) {
                $('#imageTitle').val(file.name.replace(/\.[^/.]+$/, "")); // Remove extension
            }
        };
        reader.readAsDataURL(file);
    }
    
    $('#uploadMediaBtn').click(function() {
        const fileInput = $('#mediaUpload')[0];
        if (!fileInput.files.length) return;
        
        const formData = new FormData();
        formData.append('action', 'upload_media');
        formData.append('media_file', fileInput.files[0]);
        formData.append('image_title', $('#imageTitle').val());
        formData.append('image_alt', $('#imageAltText').val());
        formData.append('image_description', $('#imageDescription').val());
        
        $('#uploadProgress').show();
        $('#uploadMediaBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...');
        
        // Get base URL
        const currentUrl = window.location.href;
        const baseUrl = currentUrl.substring(0, currentUrl.lastIndexOf('/'));
        
        $.ajax({
            url: baseUrl + '/includes/media_upload.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        $('#uploadProgress .progress-bar')
                            .css('width', percent + '%')
                            .attr('aria-valuenow', percent)
                            .text(percent + '%');
                    }
                });
                return xhr;
            },
            success: function(response) {
                let data;
                if (typeof response === 'string') {
                    try {
                        data = JSON.parse(response);
                    } catch (e) {
                        console.error('Failed to parse JSON:', response);
                        showToast('error', 'Invalid server response format');
                        return;
                    }
                } else {
                    data = response;
                }
                
                if (data.success) {
                    // Show success message
                    showToast('success', 'Image uploaded successfully!');
                    
                    // Switch back to media library tab
                    $('#mediaTabs button[data-bs-target="#media-library"]').tab('show');
                    
                    // Refresh media library to show the new image
                    resetMediaLibrary();
                    loadMediaLibrary();
                    
                    // Select the newly uploaded image
                    selectMediaItem(data.attachment_id, data.url);
                    
                    // Reset upload form
                    $('#mediaUpload').val('');
                    $('#imageTitle').val('');
                    $('#imageAltText').val('');
                    $('#imageDescription').val('');
                    $('#uploadPreviewContainer').hide();
                } else {
                    showToast('error', 'Upload failed: ' + data.message);
                }
            },
            error: function(xhr, status, error) {
                showToast('error', 'Upload error: ' + error);
                $('#uploadProgress').hide();
                $('#uploadProgress .progress-bar').css('width', '0%').attr('aria-valuenow', 0).text('0%');
                $('#uploadMediaBtn').prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Upload Image');
            },
            complete: function() {
                $('#uploadProgress').hide();
                $('#uploadProgress .progress-bar').css('width', '0%').attr('aria-valuenow', 0).text('0%');
                $('#uploadMediaBtn').prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Upload Image');
            }
        });
    });
    
    // Select media item from library
    $(document).on('click', '.media-item', function() {
        const mediaId = $(this).data('id');
        const mediaUrl = $(this).data('url');
        selectMediaItem(mediaId, mediaUrl);
    });
    
    // Confirm media selection
    $('#selectMediaBtn').click(function() {
        if (selectedMediaId) {
            $('#productImageId').val(selectedMediaId);
            $('#imagePreview').html('<img src="' + selectedMediaUrl + '" alt="Preview" style="max-width: 100%;">');
            $('#removeImageBtn').show();
            $('#mediaLibraryModal').modal('hide');
        }
    });
    
    // Load more media items
    $('#loadMoreMedia').click(function() {
        if (!isLoading && hasMoreMedia) {
            currentPage++;
            loadMediaLibrary(false);
        }
    });
    
    // Reset media library
    function resetMediaLibrary() {
        currentPage = 1;
        isLoading = false;
        hasMoreMedia = true;
        $('#mediaLibraryItems').html('');
        $('#loadMoreMediaContainer').hide();
    }
    
    // Load media library with pagination
    function loadMediaLibrary(clearExisting = true) {
        if (isLoading) return;
        
        isLoading = true;
        const currentUrl = window.location.href;
        const baseUrl = currentUrl.substring(0, currentUrl.lastIndexOf('/'));
        
        // Show loading indicator
        if (clearExisting) {
            $('#mediaLibraryItems').html('<div class="col-12 text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading media library...</p></div>');
        } else {
            $('#loadMoreMedia').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
        }
        
        $.ajax({
            url: baseUrl + '/includes/get_media.php',
            type: 'GET',
            dataType: 'json',
            data: {
                page: currentPage,
                per_page: 24
            },
            success: function(response) {
                if (response.success) {
                    renderMediaLibrary(response.media, clearExisting);
                    hasMoreMedia = response.has_more;
                    
                    // Show or hide load more button
                    if (hasMoreMedia) {
                        $('#loadMoreMediaContainer').show();
                        $('#loadMoreMedia').html('Load More Images');
                    } else {
                        $('#loadMoreMediaContainer').hide();
                    }
                    
                    // Update loaded count
                    if (response.total_loaded > 0) {
                        $('#mediaCount').text('Loaded ' + response.total_loaded + ' images');
                    }
                } else {
                    $('#mediaLibraryItems').html('<div class="col-12 text-center text-danger">Error loading media: ' + response.message + '</div>');
                    $('#loadMoreMediaContainer').hide();
                }
            },
            error: function() {
                $('#mediaLibraryItems').html('<div class="col-12 text-center text-danger">Error loading media library.</div>');
                $('#loadMoreMediaContainer').hide();
            },
            complete: function() {
                isLoading = false;
            }
        });
    }
    
    // Render media library items
    function renderMediaLibrary(media, clearExisting) {
        let html = '';
        
        if (clearExisting) {
            $('#mediaLibraryItems').html('');
        }
        
        if (media.length === 0 && clearExisting) {
            html = '<div class="col-12 text-center text-muted py-5">No media items found.</div>';
            $('#mediaLibraryItems').html(html);
            return;
        }
        
        media.forEach(function(item) {
            html += `
                <div class="col-md-auto">
                    <div class="media-item card h-100" data-id="${item.id}" data-url="${item.url}" style="cursor: pointer;">
                        <img src="${item.thumbnail}" class="card-img-top" alt="${item.title}" style="height: 100px; object-fit: cover;">
                        <div class="card-body p-2">
                            <h6 class="card-title mb-0 text-truncate">${item.title}</h6>
                            <p class="card-text small text-muted mb-0">${item.date_formatted}</p>
                        </div>
                    </div>
                </div>
            `;
        });
        
        if (clearExisting) {
            $('#mediaLibraryItems').html(html);
        } else {
            $('#mediaLibraryItems').append(html);
        }
    }
    
    // Select a media item
    function selectMediaItem(id, url) {
        selectedMediaId = id;
        selectedMediaUrl = url;
        
        // Update UI
        $('.media-item').removeClass('selected');
        $(`.media-item[data-id="${id}"]`).addClass('selected');
        $('#mediaPreviewContainer').html(`<img src="${url}" class="img-fluid" alt="Preview">`);
        $('#mediaSelectionInfo').html(`<p class="small">Selected: <strong>Image #${id}</strong></p>`);
        $('#selectMediaBtn').prop('disabled', false);
    }

    // toast messages, move it to a seperate file later
    function showToast(type, message) {
        // Remove existing toasts
        $('.custom-toast').remove();
        
        // Create toast element
        const toast = $('<div class="custom-toast alert alert-dismissible fade show" role="alert"></div>');
        
        // Set style based on type
        if (type === 'success') {
            toast.addClass('alert-success');
        } else {
            toast.addClass('alert-danger');
        }
        
        // Add message and close button
        toast.html(`
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `);
        
        // Add styles for toast positioning
        toast.css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'z-index': '9999',
            'min-width': '300px'
        });
        
        // Append to body and show
        $('body').append(toast);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            toast.alert('close');
        }, 5000);
    }

    // Open media library modal for gallery selection
    $('#galleryLibraryBtn').click(function() {
        currentModalMode = 'gallery';
        
        // Load current gallery values
        const currentGallery = $('#productGalleryIds').val();
        if (currentGallery) {
            galleryMediaIds = currentGallery.split(',').map(id => parseInt(id.trim()));
            // We'll need to fetch URLs for these IDs
            updateSelectedGalleryInfo();
        }
        
        // Update modal title and button text
        $('#mediaLibraryModalLabel').text('Select Gallery Images');
        $('#selectGalleryBtn').text('Add to Gallery').show();
        $('#selectMediaBtn').hide();
        
        $('#mediaLibraryModal').modal('show');
        resetMediaLibrary();
        loadMediaLibrary();
    });
    
    // Toggle gallery image selection
    $(document).on('click', '.media-item', function() {
        if (currentModalMode !== 'gallery') return;
        
        const mediaId = $(this).data('id');
        const mediaUrl = $(this).data('url');
        
        if (galleryMediaIds.includes(mediaId)) {
            // Remove from selection
            galleryMediaIds = galleryMediaIds.filter(id => id !== mediaId);
            delete galleryMediaUrls[mediaId];
            $(this).removeClass('selected');
        } else {
            // Add to selection
            galleryMediaIds.push(mediaId);
            galleryMediaUrls[mediaId] = mediaUrl;
            $(this).addClass('selected');
        }
        
        updateSelectedGalleryInfo();
    });
    
    // Save gallery selection
    $('#selectGalleryBtn').click(function() {
        // Update hidden field
        $('#productGalleryIds').val(galleryMediaIds.join(','));
        
        // Update preview
        updateGalleryPreview();
        
        // Close modal
        $('#mediaLibraryModal').modal('hide');
        
        showToast('success', 'Gallery updated successfully!');
    });
    
    // Update selected gallery info in modal
    function updateSelectedGalleryInfo() {
        $('#selectedImagesCount').text(galleryMediaIds.length + ' images selected');
        
        let previewsHtml = '';
        if (galleryMediaIds.length === 0) {
            previewsHtml = '<span class="text-muted">No images selected yet</span>';
        } else {
            galleryMediaIds.forEach(id => {
                if (galleryMediaUrls[id]) {
                    previewsHtml += `
                        <div class="selected-image-thumb">
                            <img src="${galleryMediaUrls[id]}" class="img-thumbnail" alt="Selected image">
                            <button type="button" class="btn-remove-image" data-id="${id}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                }
            });
        }
        
        $('#selectedImagesContainer').html(previewsHtml);
    }
    
    // Remove image from gallery selection
    $(document).on('click', '.btn-remove-image', function(e) {
        e.stopPropagation();
        const mediaId = $(this).data('id');
        
        // Remove from selection
        galleryMediaIds = galleryMediaIds.filter(id => id !== mediaId);
        delete galleryMediaUrls[mediaId];
        
        // Update UI
        $(`.media-item[data-id="${mediaId}"]`).removeClass('selected');
        updateSelectedGalleryInfo();
    });
    
    // Update gallery preview in main form
    function updateGalleryPreview() {
        const preview = $('#galleryPreview');
        
        if (galleryMediaIds.length === 0) {
            preview.html('<span class="text-muted">No gallery images selected</span>');
            return;
        }
        
        let previewHtml = '<div class="d-flex flex-wrap gap-2">';
        galleryMediaIds.forEach(id => {
            if (galleryMediaUrls[id]) {
                previewHtml += `<img src="${galleryMediaUrls[id]}" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;" alt="Gallery image">`;
            }
        });
        previewHtml += '</div>';
        
        preview.html(previewHtml);
    }
    
    // Initialize gallery if there are existing values
    function initGallery() {
        const currentGallery = $('#productGalleryIds').val();
        if (currentGallery) {
            galleryMediaIds = currentGallery.split(',').map(id => parseInt(id.trim()));
            
            // We need to fetch URLs for these IDs
            if (galleryMediaIds.length > 0) {
                const currentUrl = window.location.href;
                const baseUrl = currentUrl.substring(0, currentUrl.lastIndexOf('/'));
                
                $.ajax({
                    url: baseUrl + '/includes/get_media_by_ids.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_media_by_ids',
                        media_ids: galleryMediaIds
                    },
                    success: function(response) {
                        if (response.success) {
                            response.media.forEach(item => {
                                galleryMediaUrls[item.id] = item.url;
                            });
                            updateGalleryPreview();
                        }
                    }
                });
            }
        }
    }

    // Initialize gallery on page load
    initGallery();
});