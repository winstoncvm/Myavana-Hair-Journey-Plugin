jQuery(document).ready(function($) {
    // Chart initialization
    const ctx = document.getElementById('hairHealthChart')?.getContext('2d');
    if (ctx && myavanaAnalyticsData.chartData) {
        new Chart(ctx, {
            type: 'line',
            data: myavanaAnalyticsData.chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            font: { family: 'Avenir Next', size: 14 },
                            color: '#222323'
                        }
                    },
                    tooltip: {
                        bodyFont: { family: 'Avenir Next' },
                        titleFont: { family: 'Avenir Next', weight: 'bold' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            font: { family: 'Avenir Next', size: 12 },
                            color: '#222323'
                        },
                        grid: { color: '#eeece1' },
                        title: {
                            display: true,
                            text: 'Health Rating',
                            font: { family: 'Avenir Next', size: 14 },
                            color: '#222323'
                        }
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        ticks: {
                            font: { family: 'Avenir Next', size: 12 },
                            color: '#222323'
                        },
                        grid: { display: false },
                        title: {
                            display: true,
                            text: 'Analysis Frequency',
                            font: { family: 'Avenir Next', size: 14 },
                            color: '#222323'
                        }
                    },
                    x: {
                        ticks: {
                            font: { family: 'Avenir Next', size: 12 },
                            color: '#222323'
                        },
                        grid: { color: '#eeece1' }
                    }
                }
            }
        });
    }

    // New Tip Button Handler
    $('#new-tip-btn').click(function() {
        $.ajax({
            url: myavanaAnalyticsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'myavana_get_new_tip',
                context: myavanaAnalyticsData.context,
                nonce: myavanaAnalyticsData.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.ai-recommendations p').fadeOut(300, function() {
                        $(this).text(response.data.tip).fadeIn(300);
                    });
                } else {
                    $('.ai-recommendations p').fadeOut(300, function() {
                        $(this).text('Unable to fetch new tip. Please try again.').fadeIn(300);
                    });
                }
            },
            error: function() {
                $('.ai-recommendations p').fadeOut(300, function() {
                    $(this).text('Error fetching new tip. Please try again.').fadeIn(300);
                });
            }
        });
    });

    // Modal Handler
    $('#open-hairstyle-modal').click(function() {
        $('#hairstyleModal').removeClass('hidden').addClass('flex');
    });

    $('.modal-close').click(function() {
        $('#hairstyleModal').removeClass('flex').addClass('hidden');
    });

    // Download Routine PDF
    $('#download-routine-btn').click(function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        doc.setFont('Avenir Next', 'normal');
        doc.setFontSize(16);
        doc.text('Myavana Personalized Haircare Routine', 20, 20);
        doc.setFontSize(12);
        doc.text($('.ai-recommendations p').text(), 20, 40, { maxWidth: 160 });
        doc.save('myavana-haircare-routine.pdf');
    });

    // Animate cards on scroll
    const animateCards = () => {
        $('.animate-card').each(function() {
            const cardTop = $(this).offset().top;
            const windowBottom = $(window).scrollTop() + $(window).height();
            if (cardTop < windowBottom - 50) {
                $(this).addClass('animate__animated animate__fadeInUp');
            }
        });
    };

    $(window).on('scroll', animateCards);
    animateCards();

    // Fade in content
    const content = $('.analytics-content');
    content.hide();
    setTimeout(() => content.fadeIn(300), 100);
});