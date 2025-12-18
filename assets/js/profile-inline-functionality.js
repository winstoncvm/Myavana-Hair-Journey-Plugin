/**
 * Myavana Profile Shortcode - Additional Inline Functionality
 *
 * This file contains the inline JavaScript that was previously embedded
 * in the profile shortcode PHP file. Moving it here for better organization
 * and maintainability.
 */

// Debug script loading
console.log('Loading profile-inline-functionality.js');
console.log('jQuery available:', typeof jQuery !== 'undefined');
console.log('Splide available:', typeof Splide !== 'undefined');

jQuery(document).ready(function($) {
    console.log('DOM Ready in profile-inline-functionality.js');

    // Enhanced progress slider functionality
    const progressSlider = document.getElementById('goal-progress');
    const progressValue = document.getElementById('progress-value-enhanced');
    const progressBarFill = document.getElementById('progress-bar-fill');

    if (progressSlider && progressValue && progressBarFill) {
        progressSlider.addEventListener('input', function() {
            const value = this.value;
            progressValue.textContent = value + '%';
            progressBarFill.style.width = value + '%';
        });
    }

    // Set default dates for goal form
    function setDefaultDates() {
        const today = new Date().toISOString().split('T')[0];
        const goalStartDate = document.getElementById('goal-start-date');
        const goalTargetDate = document.getElementById('goal-target-date');

        if (goalStartDate && !goalStartDate.value) {
            goalStartDate.value = today;
        }

        if (goalTargetDate && !goalTargetDate.value) {
            const futureDate = new Date();
            futureDate.setMonth(futureDate.getMonth() + 3);
            goalTargetDate.value = futureDate.toISOString().split('T')[0];
        }
    }

    // Initialize default dates
    setDefaultDates();

    // Initialize Splide slider
    if (document.querySelector('.analysis-splide')) {
        new Splide('.analysis-splide', {
            type: 'slide',
            perPage: 2, // Show 2 cards at once
            gap: '1.5rem',
            padding: { left: '1rem', right: '1rem' },
            arrows: true,
            pagination: true,
            autoHeight: false,
            breakpoints: {
                768: {
                    perPage: 1, // Single card on mobile
                    padding: { left: '0.5rem', right: '0.5rem' },
                    gap: '1rem'
                }
            },
            classes: {
                arrows: 'splide__arrows analysis-splide__arrows',
                arrow: 'splide__arrow analysis-splide__arrow',
                pagination: 'splide__pagination analysis-splide__pagination',
            }
        }).mount();
    }

    // Store original about-me value from data attribute
    const aboutMeText = $('#about-me-text');
    if (aboutMeText.length) {
        const originalValue = aboutMeText.data('original-value');
        if (originalValue) {
            aboutMeText.data('original-value', originalValue);
        }
    } 

    // Handle Analysis View in Offcanvas
    $(document).on('click', '.myavana-history-details-btn, .view-details, .analysis-action-btn', function(e) {
        e.preventDefault();
        console.log('View Details clicked', this);
        
        let analysisData = $(this).data('analysis');
        console.log('Raw Analysis Data:', analysisData);

        // Parse the data if it's a string
        try {
            if (typeof analysisData === 'string') {
                analysisData = JSON.parse(analysisData);
            }
            console.log('Parsed Analysis Data:', analysisData);

            if (!analysisData || !analysisData.hair_analysis) {
                throw new Error('Invalid analysis data structure');
            }

            const timestamp = new Date(analysisData.timestamp);
            const formattedDate = timestamp.toLocaleDateString('en-US', { 
                weekday: 'long',
                year: 'numeric', 
                month: 'long', 
                day: 'numeric'
            });

            // Update offcanvas content
            $('#analysis-date').text(formattedDate);

            // Update image
            if (analysisData.image_url) {
                $('#analysis-image').attr('src', analysisData.image_url).show();
            } else {
                $('#analysis-image').hide();
            }

            // Update metrics with animation
            const metrics = {
                health: analysisData.hair_analysis.health_score || 0,
                hydration: analysisData.hair_analysis.hydration || 0,
                elasticity: analysisData.hair_analysis.elasticity || 0
            };

            Object.entries(metrics).forEach(([key, value]) => {
                const score = $(`#${key}-score`);
                const progress = $(`#${key}-progress`);
                
                score.text(value + '%');
                progress.css('width', '0%').animate({
                    width: value + '%'
                }, 800, 'easeOutQuart');
            });

            // Update hair details
            $('#hair-type').text(analysisData.hair_analysis.type || '--');
            $('#curl-pattern').text(analysisData.hair_analysis.curl_pattern || '--');
            $('#porosity').text(analysisData.hair_analysis.porosity || '--');

            // Update summary
            $('#analysis-summary').text(analysisData.summary || 'No summary available');

            // Update recommendations
            const recommendations = analysisData.recommendations || [];
            const recsHtml = recommendations.length ? 
                recommendations.map(rec => `
                    <div class="recommendation-item">
                        <div class="rec-icon">💡</div>
                        <div class="rec-text">${rec}</div>
                    </div>
                `).join('') :
                '<div class="no-recommendations">No recommendations available</div>';
            
            $('#analysis-recommendations').html(recsHtml);

            // Show the offcanvas
            $('#analysisViewOffcanvas').addClass('active');
            $('#viewOffcanvasOverlay').addClass('active');
            $('body').addClass('offcanvas-active');

        } catch (error) {
            console.error('Error processing analysis data:', error);
            return;

            // Update header
            $('#analysis-date').text(formattedDate);

            // Update image
            if (analysisData.image_url) {
                $('#analysis-image').attr('src', analysisData.image_url).show();
            } else {
                $('#analysis-image').hide();
            }

            // Update metrics with animation
            const metrics = {
                health: analysisData.hair_analysis.health_score || 0,
                hydration: analysisData.hair_analysis.hydration || 0,
                elasticity: analysisData.hair_analysis.elasticity || 0
            };

            // Animate metrics
            Object.entries(metrics).forEach(([key, value]) => {
                const score = $(`#${key}-score`);
                const progress = $(`#${key}-progress`);
                
                score.text(value + '%');
                progress.css('width', '0%').animate({
                    width: value + '%'
                }, 800, 'easeOutQuart');
            });

            // Update hair details
            $('#hair-type').text(analysisData.hair_analysis.type || '--');
            $('#curl-pattern').text(analysisData.hair_analysis.curl_pattern || '--');
            $('#porosity').text(analysisData.hair_analysis.porosity || '--');

            // Update summary
            $('#analysis-summary').text(analysisData.summary || 'No summary available');

            // Update recommendations
            const recommendations = analysisData.recommendations || [];
            const recsHtml = recommendations.length ? 
                recommendations.map(rec => `
                    <div class="recommendation-item">
                        <div class="rec-icon">💡</div>
                        <div class="rec-text">${rec}</div>
                    </div>
                `).join('') :
                '<div class="no-recommendations">No recommendations available</div>';
            
            $('#analysis-recommendations').html(recsHtml);

            // Show offcanvas
            $('#analysisViewOffcanvas').addClass('show');
            $('#viewOffcanvasOverlay').addClass('show');
            $('body').addClass('offcanvas-open');
        }
    });

    // Close offcanvas
    $(document).on('click', '.offcanvas-close-hjn, #viewOffcanvasOverlay', function() {
        closeViewOffcanvas();
    });

    // Function to close offcanvas
    function closeViewOffcanvas() {
        $('#analysisViewOffcanvas').removeClass('active');
        $('#viewOffcanvasOverlay').removeClass('active');
        $('body').removeClass('offcanvas-active');
    }
    
    // Make it available globally
    window.closeViewOffcanvas = closeViewOffcanvas;
    

    // Initialize Hair Analysis Splide Slider
    if (document.getElementById('hair-analysis-splide') && typeof Splide !== 'undefined') {
        const hairAnalysisSlider = new Splide('#hair-analysis-splide', {
            type: 'loop',
            perPage: 1,
            perMove: 1,
            gap: '2rem',
            padding: { left: '5%', right: '5%' },
            arrows: true,
            pagination: true,
            autoplay: false,
            speed: 800,
            easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
            keyboard: 'global',
            drag: true,
            snap: true,
            focus: 'center',
            trimSpace: false,
            breakpoints: {
                768: {
                    padding: { left: '2%', right: '2%' },
                    gap: '1rem'
                },
                480: {
                    padding: { left: '5px', right: '5px' },
                    gap: '0.5rem'
                }
            },
            classes: {
                arrows: 'splide__arrows myavana-splide-arrows',
                arrow : 'splide__arrow myavana-splide-arrow',
                prev  : 'splide__arrow--prev myavana-splide-prev',
                next  : 'splide__arrow--next myavana-splide-next',
                pagination: 'splide__pagination myavana-splide-pagination',
                page: 'splide__pagination__page myavana-splide-dot'
            }
        });

        hairAnalysisSlider.mount();

        console.log('Hair Analysis Splide Slider initialized with', hairAnalysisSlider.length, 'slides');
    }

    // Progress Slider and Update for goals
    $(document).on('input', '.goal-progress-slider', function() {
        const index = $(this).data('index');
        const value = $(this).val();
        $(this).closest('.goal-progress').find('.progress-fill').css('width', value + '%');
        $(this).closest('.goal-progress').find('.progress-percentage').text(value + '%');
    });

    $(document).on('click', '.update-progress-btn', function() {
        const $btn = $(this);
        const index = $btn.data('index');
        const progress = $btn.closest('.goal-progress').find('.goal-progress-slider').val();
        const $goalCard = $btn.closest('.goal-card');
        const $progressFill = $goalCard.find('.progress-fill');
        const $progressPercentage = $goalCard.find('.progress-percentage');

        // Add loading state
        $btn.addClass('loading').prop('disabled', true);
        const originalText = $btn.text();
        $btn.text('Updating...');

        $.ajax({
            url: window.myavanaProfileAjax?.ajaxurl || ajaxurl,
            type: 'POST',
            data: {
                action: 'myavana_update_goal_progress',
                index: index,
                progress: progress,
                nonce: window.myavanaProfileAjax?.nonce || ''
            },
            success: function(response) {
                // Remove loading state
                $btn.removeClass('loading').prop('disabled', false).text(originalText);

                if (response.success) {
                    const data = response.data;

                    // Show success feedback with toast
                    showToast('Goal progress updated successfully!', 'success');

                    // Animate progress bar update
                    $progressFill.css('width', progress + '%').attr('data-progress', progress);
                    $progressPercentage.text(progress + '%');

                    // Check for milestone achievement
                    if (data.new_milestone) {
                        triggerMilestoneAnimation($goalCard, data.new_milestone, progress);
                    } else if (parseInt(progress) === 100) {
                        // Completion celebration
                        $goalCard.addClass('goal-completed');
                        setTimeout(() => {
                            $goalCard.removeClass('goal-completed');
                        }, 2000);
                    }

                    // Reload page after 2 seconds to show new insights/history
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showToast('Error: ' + response.data, 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Remove loading state
                $btn.removeClass('loading').prop('disabled', false).text(originalText);
                showToast('Failed to connect to server. Please try again.', 'error');
            }
        });
    });

    // Toast notification function
    function showToast(message, type = 'success') {
        // Remove existing toasts
        $('.myavana-toast').remove();

        // Create toast element
        const $toast = $('<div>', {
            class: `myavana-toast ${type}`,
            html: `
                <div class="toast-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    <span>${message}</span>
                </div>
            `
        });

        // Append to body and show
        $('body').append($toast);

        // Fade in
        setTimeout(() => $toast.addClass('show'), 10);

        // Auto-hide after 4 seconds
        setTimeout(() => {
            $toast.removeClass('show');
            setTimeout(() => $toast.remove(), 300);
        }, 4000);
    }

    // Milestone Achievement Animation
    function triggerMilestoneAnimation($goalCard, milestone, progress) {
        const milestoneNames = {
            25: 'First Quarter',
            50: 'Halfway There',
            75: 'Almost Done',
            100: 'Champion'
        };

        const milestoneIcons = {
            25: 'fas fa-seedling',
            50: 'fas fa-star-half-alt',
            75: 'fas fa-fire',
            100: 'fas fa-crown'
        };

        // Create celebration modal
        const $celebration = $('<div>', {
            class: 'milestone-celebration',
            html: `
                <div class="celebration-content">
                    <div class="celebration-confetti"></div>
                    <div class="celebration-icon">
                        <i class="${milestoneIcons[milestone]}"></i>
                    </div>
                    <h2 class="celebration-title">Achievement Unlocked!</h2>
                    <h3 class="celebration-milestone">${milestoneNames[milestone]}</h3>
                    <div class="celebration-progress">${progress}% Complete</div>
                    <p class="celebration-message">Keep up the amazing work on your hair journey!</p>
                    <button class="celebration-close">Continue</button>
                </div>
            `
        });

        // Append and show
        $('body').append($celebration);
        setTimeout(() => $celebration.addClass('show'), 10);

        // Confetti animation
        createConfetti($celebration.find('.celebration-confetti'));

        // Close button
        $celebration.find('.celebration-close').on('click', function() {
            $celebration.removeClass('show');
            setTimeout(() => $celebration.remove(), 300);
        });

        // Auto-close after 5 seconds
        setTimeout(() => {
            $celebration.removeClass('show');
            setTimeout(() => $celebration.remove(), 300);
        }, 5000);

        // Update badge in card
        $goalCard.find(`.achievement-badge[data-milestone="${milestone}"]`)
            .addClass('earned just-earned')
            .find('.badge-earned').fadeIn();

        setTimeout(() => {
            $goalCard.find('.achievement-badge.just-earned').removeClass('just-earned');
        }, 2000);
    }

    // Confetti generation
    function createConfetti($container) {
        const colors = ['#e7a690', '#222323', '#4a4d68', '#f5f5f7'];
        for (let i = 0; i < 50; i++) {
            const $confetti = $('<div>',  {
                class: 'confetti-piece',
                css: {
                    left: Math.random() * 100 + '%',
                    backgroundColor: colors[Math.floor(Math.random() * colors.length)],
                    animationDelay: Math.random() * 3 + 's',
                    animationDuration: (Math.random() * 3 + 2) + 's'
                }
            });
            $container.append($confetti);
        }
    }

    // Toggle collapsible sections
    $(document).on('click', '.updates-toggle, .history-toggle', function() {
        const $header = $(this).closest('.updates-header, .history-header');
        const $content = $header.siblings('.updates-content, .history-content');
        const $icon = $(this).find('i');

        $content.toggleClass('expanded');
        $icon.toggleClass('fa-chevron-down fa-chevron-up');
    });

    // Initialize progress history charts
    function initializeProgressCharts() {
        $('.progress-chart').each(function() {
            const $canvas = $(this);
            const history = JSON.parse($canvas.attr('data-history') || '[]');

            if (history.length > 0 && typeof Chart !== 'undefined') {
                const ctx = $canvas[0].getContext('2d');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: history.map(h => new Date(h.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
                        datasets: [{
                            label: 'Progress %',
                            data: history.map(h => h.progress),
                            borderColor: '#222323',
                            backgroundColor: 'rgba(34, 35, 35, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#e7a690',
                            pointBorderColor: '#222323',
                            pointBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#222323',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                borderColor: '#e7a690',
                                borderWidth: 1,
                                padding: 12,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y + '% progress';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    }

    // Initialize charts when document is ready
    if (typeof Chart !== 'undefined') {
        initializeProgressCharts();
    }

    // Analysis Modal
    const modal = $('.myavana-analysis-modal');

    // Open modal with analysis data
    function openAnalysisModal(analysisData) {
        // Parse the data if it's a string
        const analysis = typeof analysisData === 'string' ? JSON.parse(analysisData) : analysisData;

        // Set basic info
        $('#analysis-modal-date').text(new Date(analysis.timestamp || analysis.date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }));

        // Set hair characteristics
        const hair = analysis.hair_analysis || (analysis.full_analysis ? analysis.full_analysis.hair_analysis : {});
        $('#detail-type').text(hair.type || '--');
        $('#detail-curl-pattern').text(hair.curl_pattern || '--');
        $('#detail-length').text(hair.length || '--');
        $('#detail-texture').text(hair.texture || '--');
        $('#detail-density').text(hair.density || '--');
        $('#detail-porosity').text(hair.porosity || '--');

        // Set metrics
        $('#metric-health-score').text((hair.health_score || '--') + '%');
        $('#metric-hydration').text((hair.hydration || '--') + '%');
        $('#metric-elasticity').text(hair.elasticity || '--');

        // Animate metric bars
        setTimeout(() => {
            $('#fill-health-score').css('width', (hair.health_score || 0) + '%');
            $('#fill-hydration').css('width', (hair.hydration || 0) + '%');
            $('#fill-elasticity').css('width', (hair.elasticity ? hair.elasticity.replace(/\D/g, '') : 0) + '%');
        }, 100);

        // Set recommendations
        const recList = $('#recommendations-list').empty();
        const recommendations = analysis.recommendations || (analysis.full_analysis ? analysis.full_analysis.recommendations : []);
        if (recommendations && recommendations.length) {
            recommendations.forEach(rec => {
                recList.append(`<li>${rec}</li>`);
            });
        } else {
            recList.append('<li>No specific recommendations available.</li>');
        }

        // Set products
        const productsGrid = $('#products-grid').empty();
        const products = analysis.products || (analysis.full_analysis ? analysis.full_analysis.products : []);
        if (products && products.length) {
            products.forEach(prod => {
                productsGrid.append(`
                    <div class="myavana-product-card">
                        <div class="myavana-product-name">${prod.name || 'Unnamed Product'}</div>
                        <div class="myavana-product-match">
                            <span class="myavana-product-match-value">${prod.match || 0}% match</span>
                        </div>
                        <div class="myavana-product-match-bar">
                            <div class="myavana-product-match-fill" style="width: ${prod.match || 0}%"></div>
                        </div>
                    </div>
                `);
            });
        } else {
            productsGrid.append('<p>No product recommendations available.</p>');
        }

        // Set summary
        $('#full-summary').text(analysis.full_context || analysis.summary || 'No detailed summary available.');

        // Show modal
        modal.addClass('active');
        $('.myavana-analysis-modal').show();
        $('body').css('overflow', 'hidden');
    }

    // Close modal
    $('.myavana-modal-close').click(function() {
        modal.removeClass('active');
        $('.myavana-analysis-modal').hide();
        $('body').css('overflow', '');
    });

    // Click outside modal to close
    modal.click(function(e) {
        if ($(e.target).hasClass('myavana-analysis-modal')) {
            modal.removeClass('active');
            $('.myavana-analysis-modal').hide();
            $('body').css('overflow', '');
        }
    });

    // Remove duplicate click handler - we're using the delegated one above

    // Initialize metric bars on slide change
    function initMetricBars() {
        $('.myavana-metric-fill').each(function() {
            const width = $(this).data('width') || '0%';
            $(this).css('width', width);
        });
    }

    // Initialize on load
    initMetricBars();

    // Expose openAnalysisModal function globally
    window.openAnalysisModal = openAnalysisModal;
});