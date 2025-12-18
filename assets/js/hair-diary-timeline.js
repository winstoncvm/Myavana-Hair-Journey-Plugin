document.addEventListener('DOMContentLoaded', function() {
    const timelineContainer = document.getElementById('timelineContainer');
    const slider = document.getElementById('slider');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const progress = document.getElementById('progress');
    const dateMarkers = document.querySelectorAll('.date-marker');
    const entries = document.querySelectorAll('.main-entry');
    const totalEntries = entries.length;
    let currentIndex = 0;

    // Modal elements
    const addEntryBtn = document.getElementById('addEntryBtn');
    const entryModal = document.getElementById('entryModal');
    const modalClose = document.getElementById('modalClose');
    const cancelEntryBtn = document.getElementById('cancelEntryBtn');
    const entryForm = document.getElementById('entryForm');
    const fileUpload = document.getElementById('fileUpload');
    const hairImage = document.getElementById('hairImage');
    const imagePreview = document.getElementById('imagePreview');
    const productInput = document.getElementById('productInput');
    const addProductBtn = document.getElementById('addProductBtn');
    const productTagsContainer = document.getElementById('productTagsContainer');

    // Show timeline container
    timelineContainer.style.display = 'block';

    function updateSlider() {
        slider.scrollTo({
            left: entries[currentIndex].offsetLeft,
            behavior: 'smooth'
        });
        updateProgress();
        updateActiveMarker();
    }

    function updateProgress() {
        const progressWidth = ((currentIndex + 1) / totalEntries) * 100;
        progress.style.width = `${progressWidth}%`;
    }

    function updateActiveMarker() {
        dateMarkers.forEach(marker => {
            marker.classList.remove('active');
            if (parseInt(marker.dataset.index) === currentIndex) {
                marker.classList.add('active');
            }
        });
    }

    nextBtn.addEventListener('click', function() {
        if (currentIndex < totalEntries - 1) {
            currentIndex++;
            updateSlider();
        }
    });

    prevBtn.addEventListener('click', function() {
        if (currentIndex > 0) {
            currentIndex--;
            updateSlider();
        }
    });

    dateMarkers.forEach(marker => {
        marker.addEventListener('click', function() {
            currentIndex = parseInt(this.dataset.index);
            updateSlider();
        });
    });

    // Modal functionality
    addEntryBtn.addEventListener('click', function() {
        entryModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    function closeModal() {
        entryModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    modalClose.addEventListener('click', closeModal);
    cancelEntryBtn.addEventListener('click', closeModal);

    entryModal.addEventListener('click', function(e) {
        if (e.target === entryModal) {
            closeModal();
        }
    });

    // File upload handling
    fileUpload.addEventListener('click', function() {
        hairImage.click();
    });

    hairImage.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                imagePreview.src = event.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    fileUpload.addEventListener('dragover', function(e) {
        e.preventDefault();
        fileUpload.style.borderColor = 'var(--primary)';
        fileUpload.style.backgroundColor = 'rgba(108, 77, 138, 0.1)';
    });

    fileUpload.addEventListener('dragleave', function(e) {
        e.preventDefault();
        fileUpload.style.borderColor = '#ddd';
        fileUpload.style.backgroundColor = 'transparent';
    });

    fileUpload.addEventListener('drop', function(e) {
        e.preventDefault();
        fileUpload.style.borderColor = '#ddd';
        fileUpload.style.backgroundColor = 'transparent';
        const file = e.dataTransfer.files[0];
        if (file && file.type.match('image.*')) {
            hairImage.files = e.dataTransfer.files;
            const reader = new FileReader();
            reader.onload = function(event) {
                imagePreview.src = event.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // Product handling
    function addProduct() {
        const productName = productInput.value.trim();
        if (productName) {
            const productTag = document.createElement('div');
            productTag.className = 'product-tag-edit';
            productTag.innerHTML = `${productName}<span class="remove-product">×</span>`;
            productTag.querySelector('.remove-product').addEventListener('click', function() {
                productTag.remove();
            });
            productTagsContainer.appendChild(productTag);
            productInput.value = '';
        }
    }

    addProductBtn.addEventListener('click', addProduct);
    productInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addProduct();
        }
    });

    // Form submission with AJAX
    entryForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('action', 'myavana_add_hair_entry');
        formData.append('title', document.getElementById('entryTitle').value);
        formData.append('date', document.getElementById('entryDate').value);
        formData.append('description', document.getElementById('entryDescription').value);
        formData.append('mood', document.querySelector('input[name="mood"]:checked').value);
        formData.append('products_used', Array.from(document.querySelectorAll('.product-tag-edit')).map(tag => tag.textContent.replace('×', '').trim()));
        formData.append('ai_analysis', document.getElementById('aiAnalysis').value);
        formData.append('myavana_nonce', document.querySelector('#myavana_nonce').value);
        if (hairImage.files[0]) {
            formData.append('hair_image', hairImage.files[0]);
        }

        fetch(ajaxurl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('New entry saved successfully!');
                closeModal();
                location.reload(); // Reload to reflect new entry
            } else {
                alert('Error saving entry: ' + data.data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });

        // Reset form
        entryForm.reset();
        productTagsContainer.innerHTML = '';
        imagePreview.style.display = 'none';
        imagePreview.src = '';
    });

    // Social share buttons
    document.querySelectorAll('.share-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            alert('Social share functionality would go here!');
        });
    });

    // Initialize
    updateProgress();
    updateActiveMarker();
});