<?php
function myavana_home_one_shortcode() {
    $user_id = get_current_user_id();
     //home 
    wp_enqueue_script('myavana-homepage-scripts', MYAVANA_URL . 'assets/js/homepage.js', [], '2.0.0', true);
    wp_enqueue_style('myavana-homepage-styles', MYAVANA_URL . 'assets/css/homepage.css', [], '2.0.1');
    ob_start();
?>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=Archivo+Expanded:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
    <div class="main-body">
        <!-- App Navigation (for logged-in users) -->
        <?php if (is_user_logged_in()) : ?>
        <!-- <nav class="myavana-app-nav" id="myavanaAppNav">
            <div class="nav-container">
                <div class="nav-logo">
                    <img src="<?php echo esc_url(home_url()); ?>/wp-content/plugins/myavana-hair-journey/assets/images/myavana-primary-logo.png" alt="MYAVANA" class="logo-img">
                </div>
                
                <div class="nav-menu" id="navMenu">
                    <a href="#hero" class="nav-link active" data-section="hero">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                    <a href="#dashboard-preview" class="nav-link" data-section="dashboard">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="#timeline-section" class="nav-link" data-section="timeline">
                        <i class="fas fa-clock"></i>
                        <span>Timeline</span>
                    </a>
                    <a href="#ai-features" class="nav-link" data-section="ai">
                        <i class="fas fa-robot"></i>
                        <span>AI Assistant</span>
                    </a>
                    <a href="#community" class="nav-link" data-section="community">
                        <i class="fas fa-users"></i>
                        <span>Community</span>
                    </a>
                </div>
                
                <div class="nav-actions">
                    <button class="nav-action-btn" id="quickAddEntry" title="Quick Add Entry">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="nav-action-btn" id="aiChatToggle" title="AI Chat">
                        <i class="fas fa-comment-dots"></i>
                    </button>
                    <div class="nav-profile">
                        <img src="<?php echo esc_url(get_avatar_url($user_id)); ?>" alt="Profile" class="profile-avatar">
                    </div>
                </div>
                
                <button class="nav-mobile-toggle" id="navMobileToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav> -->
        <?php endif; ?>

        <!-- Hero Section -->
        <section class="hero" id="hero">
            <div class="signature-m m-1">M</div>
            <div class="container">
                <div class="hero-content">
                    <?php if (is_user_logged_in()) : ?>
                        <h4 class="section-title">Welcome Back, <?php echo esc_html(get_userdata($user_id)->display_name); ?>! 🌟</h4>
                        <h2>Ready to Continue Your Hair Transformation?</h2>
                        <p>Your personalized dashboard is waiting with new insights, progress tracking, and AI-powered recommendations tailored just for you.</p>
                        <div class="hero-buttons">
                            <button class="secondary-button" id="openDashboardBtn">
                                <i class="fas fa-chart-line"></i>
                                Open My Dashboard
                            </button>
                            <button class="secondary-button secondary-outline" id="quickTimelineBtn">
                                <i class="fas fa-clock"></i>
                                View Timeline
                            </button>
                        </div>
                        
                        <!-- Quick Stats Preview -->
                        <div class="hero-quick-stats">
                            <div class="quick-stat">
                                <div class="stat-icon">📈</div>
                                <div class="stat-content">
                                    <div class="stat-number" id="heroEntriesCount">--</div>
                                    <div class="stat-label">Entries This Month</div>
                                </div>
                            </div>
                            <div class="quick-stat">
                                <div class="stat-icon">🔥</div>
                                <div class="stat-content">
                                    <div class="stat-number" id="heroStreakCount">--</div>
                                    <div class="stat-label">Day Streak</div>
                                </div>
                            </div>
                            <div class="quick-stat">
                                <div class="stat-icon">💯</div>
                                <div class="stat-content">
                                    <div class="stat-number" id="heroHealthScore">--</div>
                                    <div class="stat-label">Avg Health Score</div>
                                </div>
                            </div>
                        </div>
                    <?php else : ?>
                        <h4 class="section-title">Transform Your Hair Journey</h4>
                        <h2>YOUR PERSONALIZED HAIR JOURNEY STARTS HERE</h2>
                        <p>Track, analyze, and optimize your hair health with our AI-powered platform. Discover the perfect routine for your unique hair type and goals.</p>
                        <div class="hero-buttons">
                            <button class="secondary-button myavana-signup-b">Start Your Journey Now</button>
                            <button class="secondary-button secondary-outline" onclick="document.getElementById('how-it-works').scrollIntoView({behavior: 'smooth'})">
                                Learn How It Works
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-image"></div>
            <div class="signature-m m-2">M</div>
        </section>

        <!-- Dashboard Preview Section (for logged-in users) -->
        <?php if (is_user_logged_in()) : ?>
        <section id="dashboard-preview" class="dashboard-preview-section">
            <div class="container">
                <div class="section-header">
                    <h2>Your Hair Journey Dashboard</h2>
                    <p>Get a quick overview of your progress and access all your tools in one place</p>
                </div>
                
                <div class="dashboard-preview-grid">
                    <div class="preview-card timeline-card" data-feature="timeline">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-timeline"></i>
                            </div>
                            <div class="card-stats">
                                <span class="stat-value" id="totalEntries">0</span>
                                <span class="stat-label">Entries</span>
                            </div>
                        </div>
                        <h3>Interactive Timeline</h3>
                        <p>Visualize your hair journey with our beautiful timeline featuring progress photos and detailed entries.</p>
                        <div class="card-preview">
                            <div class="timeline-mini">
                                <div class="timeline-dot active" title="Latest entry"></div>
                                <div class="timeline-dot" title="Previous entry"></div>
                                <div class="timeline-dot" title="First entry"></div>
                                <div class="timeline-line"></div>
                            </div>
                            <div class="preview-text">Latest: <span id="latestEntryDate">Loading...</span></div>
                        </div>
                        <button class="preview-btn" onclick="scrollToTimeline()">
                            <i class="fas fa-eye"></i> Open Timeline
                        </button>
                    </div>
                    
                    <div class="preview-card analytics-card" data-feature="analytics">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="card-stats">
                                <span class="stat-value" id="averageHealth">0</span>
                                <span class="stat-label">Avg Health</span>
                            </div>
                        </div>
                        <h3>Analytics Dashboard</h3>
                        <p>Track your progress with detailed charts, health scores, and personalized insights.</p>
                        <div class="card-preview">
                            <div class="mini-chart">
                                <div class="chart-bar" style="height: 60%" data-value="6"></div>
                                <div class="chart-bar" style="height: 80%" data-value="8"></div>
                                <div class="chart-bar" style="height: 100%" data-value="10"></div>
                                <div class="chart-bar" style="height: 70%" data-value="7"></div>
                            </div>
                            <div class="preview-text">Trending: <span id="healthTrend" class="trend-positive">+15%</span></div>
                        </div>
                        <button class="preview-btn" onclick="openAnalytics()">
                            <i class="fas fa-chart-line"></i> View Analytics
                        </button>
                    </div>
                    
                    <!-- <div class="preview-card ai-card" data-feature="ai">
                        <div class="card-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <h3>AI Hair Assistant</h3>
                        <p>Get personalized advice, analyze photos, and chat with our AI-powered hair expert.</p>
                        <div class="card-preview">
                            <div class="ai-chat-preview">
                                <div class="chat-bubble ai">Hi! How can I help with your hair today?</div>
                                <div class="chat-bubble user">Analyze my hair photo</div>
                            </div>
                        </div>
                        <button class="preview-btn">Chat with AI</button>
                    </div> -->
                    
                    <div class="preview-card profile-card" data-feature="profile">
                        <div class="card-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3>Hair Profile</h3>
                        <p>Manage your hair type, preferences, routine, and track your personal transformation.</p>
                        <div class="card-preview">
                            <div class="profile-mini">
                                <div class="profile-ring"></div>
                                <div class="profile-info">
                                    <div class="info-line"></div>
                                    <div class="info-line short"></div>
                                </div>
                            </div>
                        </div>
                        <button class="preview-btn">View Profile</button>
                    </div>
                </div>
                
                <div class="dashboard-full-access">
                    <button class="dashboard-full-btn" id="openFullDashboard">
                        <i class="fas fa-external-link-alt"></i>
                        Open Full Dashboard
                    </button>
                </div>
            </div>
        </section>
        <?php endif; ?>

         <!-- ======================= -->
        <!--      HOW IT WORKS       -->
        <!-- ======================= -->
        <section id="how-it-works" class="section-padding">
            <div class="container">
                <p class="subheader">Your Journey, Simplified in Three Steps</p>
                <div class="steps-grid">
                    <article class="step-card">
                        <!-- <div class="icon-placeholder"></div> -->
                        <h3>1. Document Your Moments</h3>
                        <p>Log your routines, products, and photos in your personal hair journal. Build a visual record of your progress.</p>
                        
                    </article>
                    <article class="step-card">
                        <!-- <div class="icon-placeholder"></div> -->
                        <h3>2. Unlock AI Insights</h3>
                        <p>Use our AI Analyst to scan your photos for data-driven scores on health, hydration, damage, and your true hair type.</p>
                    </article>
                    <article class="step-card">
                        <!-- <div class="icon-placeholder"></div> -->
                        <h3>3. Thrive and Transform</h3>
                        <p>Watch your story unfold on an interactive timeline, track your health with charts, and get personalized recommendations.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- My Hair Journey Timeline Section -->
        

        <!-- AI Features Showcase -->
        <section id="ai-features" class="ai-features-section section-padding">
            <div class="container">
                <div class="section-header text-center">
                    <h2>AI-Powered Hair Intelligence</h2>
                    <p>Experience the future of hair care with our advanced AI technology</p>
                </div>
                
                <div class="ai-features-grid">
                    <div class="ai-feature-card">
                        <div class="feature-visual">
                            <div class="camera-preview">
                                <div class="camera-frame">
                                    <div class="analysis-overlay">
                                        <div class="scan-line"></div>
                                        <div class="analysis-points">
                                            <div class="point point-1"></div>
                                            <div class="point point-2"></div>
                                            <div class="point point-3"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="feature-content">
                            <h3>Smart Photo Analysis</h3>
                            <p>Upload a photo and get instant AI analysis of your hair health, curl pattern, porosity, and damage assessment.</p>
                            <?php if (is_user_logged_in()) : ?>
                                <button class="feature-btn" data-action="open-ai-camera">Try Photo Analysis</button>
                            <?php else : ?>
                                <button class="feature-btn myavana-signup-b">Sign Up to Try</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="ai-feature-card">
                        <div class="feature-visual">
                            <div class="chat-interface">
                                <div class="chat-messages">
                                    <div class="message ai-message">
                                        <div class="message-bubble">What's your hair type and main concerns?</div>
                                    </div>
                                    <div class="message user-message">
                                        <div class="message-bubble">I have 3C curls that get frizzy</div>
                                    </div>
                                    <div class="message ai-message">
                                        <div class="message-bubble">I recommend a leave-in conditioner with shea butter...</div>
                                    </div>
                                </div>
                                <div class="typing-indicator">
                                    <span></span><span></span><span></span>
                                </div>
                            </div>
                        </div>
                        <div class="feature-content">
                            <h3>AI Hair Consultant</h3>
                            <p>Chat with our AI expert for personalized advice, product recommendations, and styling tips based on your unique hair profile.</p>
                            <?php if (is_user_logged_in()) : ?>
                                <button class="feature-btn" data-action="open-ai-chat">Start Chatting</button>
                            <?php else : ?>
                                <button class="feature-btn myavana-signup-b">Sign Up to Chat</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="ai-feature-card">
                        <div class="feature-visual">
                            <div class="routine-builder">
                                <div class="routine-steps">
                                    <div class="step-item">
                                        <div class="step-icon">🧴</div>
                                        <div class="step-text">Cleanse</div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-icon">💧</div>
                                        <div class="step-text">Condition</div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-icon">🌿</div>
                                        <div class="step-text">Treatment</div>
                                    </div>
                                </div>
                                <div class="ai-badge">✨ AI Optimized</div>
                            </div>
                        </div>
                        <div class="feature-content">
                            <h3>Personalized Routines</h3>
                            <p>Get AI-generated hair care routines tailored to your hair type, lifestyle, and goals. Updated based on your progress.</p>
                            <?php if (is_user_logged_in()) : ?>
                                <button class="feature-btn" data-action="build-routine">Build My Routine</button>
                            <?php else : ?>
                                <button class="feature-btn myavana-signup-b">Sign Up for Routines</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

      
        <!-- Enhanced Homepage JavaScript -->
        <script>
            jQuery(document).ready(function($) {
                const MyavanaHomepage = {
                    init() {
                        this.setupNavigation();
                        this.setupDashboardIntegration();
                        this.setupQuickStats();
                        this.setupAIFeatures();
                        this.setupResponsive();
                        this.loadUserStats();
                    },

                    setupNavigation() {
                        // Smooth scroll navigation
                        $('.nav-link').on('click', function(e) {
                            e.preventDefault();
                            const target = $(this).attr('href');
                            $(target).scrollIntoView({behavior: 'smooth'});
                            
                            // Update active state
                            $('.nav-link').removeClass('active');
                            $(this).addClass('active');
                        });

                        // Mobile nav toggle
                        $('#navMobileToggle').on('click', function() {
                            $('#navMenu').toggleClass('mobile-open');
                            $(this).find('i').toggleClass('fa-bars fa-times');
                            $('body').toggleClass('nav-open');
                        });
                        
                        // Close mobile nav on link click
                        $('.nav-link').on('click', function() {
                            $('#navMenu').removeClass('mobile-open');
                            $('#navMobileToggle i').removeClass('fa-times').addClass('fa-bars');
                            $('body').removeClass('nav-open');
                        });
                        
                        // Navigation scroll behavior
                        let lastScrollY = window.scrollY;
                        $(window).on('scroll', function() {
                            const currentScrollY = window.scrollY;
                            const nav = $('#myavanaAppNav');
                            
                            if (currentScrollY > lastScrollY && currentScrollY > 100) {
                                nav.addClass('nav-hidden');
                            } else {
                                nav.removeClass('nav-hidden');
                            }
                            lastScrollY = currentScrollY;
                            
                            // Update active navigation based on scroll position
                            this.updateActiveNavigation();
                        }.bind(this));
                    },

                    updateActiveNavigation() {
                        const scrollPos = $(window).scrollTop() + 100;
                        $('.nav-link').each(function() {
                            const target = $($(this).attr('href'));
                            if (target.length && target.offset().top <= scrollPos && 
                                target.offset().top + target.outerHeight() > scrollPos) {
                                $('.nav-link').removeClass('active');
                                $(this).addClass('active');
                            }
                        });
                    },

                    setupDashboardIntegration() {
                        // Dashboard buttons
                        $('#openDashboardBtn, #openFullDashboard').on('click', function() {
                            window.location.href = '<?php echo home_url("/dashboard"); ?>';
                        });

                        // Quick timeline access
                        $('#quickTimelineBtn').on('click', function() {
                            $('#timeline-section')[0].scrollIntoView({behavior: 'smooth'});
                        });

                        // Preview cards
                        $('.preview-card').on('click', function() {
                            const feature = $(this).data('feature');
                            switch(feature) {
                                case 'timeline':
                                    $('#timeline-section')[0].scrollIntoView({behavior: 'smooth'});
                                    break;
                                case 'analytics':
                                    window.location.href = '<?php echo home_url("/dashboard?view=analytics"); ?>';
                                    break;
                                case 'ai':
                                    $('#ai-features')[0].scrollIntoView({behavior: 'smooth'});
                                    break;
                                case 'profile':
                                    window.location.href = '<?php echo home_url("/dashboard?view=profile"); ?>';
                                    break;
                            }
                        });

                        // Quick actions
                        $('#quickAddEntry').on('click', function() {
                            window.location.href = '<?php echo home_url("/dashboard?action=add-entry"); ?>';
                        });

                        $('#aiChatToggle').on('click', function() {
                            window.location.href = '<?php echo home_url("/dashboard?view=ai-chat"); ?>';
                        });
                    },

                    setupQuickStats() {
                        // Animate stats on scroll
                        const statsObserver = new IntersectionObserver((entries) => {
                            entries.forEach(entry => {
                                if (entry.isIntersecting) {
                                    $(entry.target).addClass('animate-in');
                                }
                            });
                        }, { threshold: 0.1 });

                        document.querySelectorAll('.quick-stat').forEach(stat => {
                            statsObserver.observe(stat);
                        });
                    },

                    setupAIFeatures() {
                        // AI feature buttons
                        $('[data-action="open-ai-camera"]').on('click', function() {
                            window.location.href = '<?php echo home_url("/dashboard?view=ai-analysis"); ?>';
                        });

                        $('[data-action="open-ai-chat"]').on('click', function() {
                            window.location.href = '<?php echo home_url("/dashboard?view=ai-chat"); ?>';
                        });

                        $('[data-action="build-routine"]').on('click', function() {
                            window.location.href = '<?php echo home_url("/dashboard?view=routine-builder"); ?>';
                        });

                        // Animate AI features
                        const aiObserver = new IntersectionObserver((entries) => {
                            entries.forEach(entry => {
                                if (entry.isIntersecting) {
                                    $(entry.target).addClass('feature-animate');
                                }
                            });
                        }, { threshold: 0.2 });

                        document.querySelectorAll('.ai-feature-card').forEach(card => {
                            aiObserver.observe(card);
                        });
                    },

                    setupResponsive() {
                        // Handle responsive features
                        const handleResize = () => {
                            const isMobile = window.innerWidth <= 768;
                            
                            if (isMobile) {
                                $('.nav-menu').removeClass('mobile-open');
                                $('#navMobileToggle i').removeClass('fa-times').addClass('fa-bars');
                            }
                        };

                        $(window).on('resize', handleResize);
                        handleResize();
                    },

                    <?php if (is_user_logged_in()) : ?>
                    loadUserStats() {
                        // Load user statistics
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'myavana_get_dashboard_stats',
                                nonce: '<?php echo wp_create_nonce('myavana_diary'); ?>'
                            },
                            success: (response) => {
                                if (response.success) {
                                    const data = response.data;
                                    
                                    // Animate numbers
                                    this.animateNumber('#heroEntriesCount', data.entries_this_month);
                                    this.animateNumber('#heroStreakCount', data.current_streak);
                                    this.animateNumber('#heroHealthScore', Math.round(data.avg_health_score * 10) / 10);
                                    
                                    // Update dashboard previews
                                    this.updateDashboardPreviews(data);
                                }
                            },
                            error: () => {
                                // Fallback to mock data for previews
                                this.updateDashboardPreviews({
                                    total_entries: 12,
                                    latest_entry_date: 'Yesterday',
                                    avg_health_score: 7.5,
                                    health_trend: 15
                                });
                            }
                        });
                    },
                    <?php else : ?>
                    loadUserStats() {
                        // No stats to load for non-logged-in users
                    },
                    <?php endif; ?>

                    <?php if (is_user_logged_in()) : ?>
                    updateDashboardPreviews(data) {
                        // Update timeline preview
                        $('#totalEntries').text(data.total_entries || data.entries_total || 0);
                        $('#latestEntryDate').text(data.latest_entry_date || data.last_entry || 'No entries yet');
                        
                        // Update analytics preview
                        const avgHealth = data.avg_health_score || data.average_health || 0;
                        $('#averageHealth').text(parseFloat(avgHealth).toFixed(1));
                        
                        const trend = data.health_trend || 0;
                        const trendElement = $('#healthTrend');
                        trendElement.text(`${trend > 0 ? '+' : ''}${trend}%`);
                        trendElement.removeClass('trend-positive trend-negative')
                                  .addClass(trend >= 0 ? 'trend-positive' : 'trend-negative');
                        
                        // Animate charts
                        this.animateCharts();
                    },

                    animateCharts() {
                        $('.chart-bar').each(function(index) {
                            $(this).delay(index * 100).animate({
                                opacity: 1
                            }, 500, function() {
                                $(this).addClass('animated');
                            });
                        });
                    },
                    <?php endif; ?>

                    animateNumber(selector, target) {
                        const element = $(selector);
                        const start = 0;
                        const duration = 1000;
                        const startTime = performance.now();

                        const animate = (currentTime) => {
                            const elapsed = currentTime - startTime;
                            const progress = Math.min(elapsed / duration, 1);
                            const current = Math.floor(start + (target - start) * progress);
                            
                            element.text(current);
                            
                            if (progress < 1) {
                                requestAnimationFrame(animate);
                            }
                        };

                        requestAnimationFrame(animate);
                    }
                };

                // Initialize
                MyavanaHomepage.init();
            });

            // Global functions for button clicks
            function scrollToTimeline() {
                document.getElementById('timeline-section').scrollIntoView({behavior: 'smooth'});
            }

            function openAnalytics() {
                window.location.href = '<?php echo home_url("/dashboard?view=analytics"); ?>';
            }
        </script>
        
         

        <!-- Features Section -->
        <section class="features">
            <div class="container">
                <div class="section-title">
                    <h2>EMPOWER YOUR HAIR STORY</h2>
                    <p>Powerful tools designed to help you understand, track, and optimize your hair care journey</p>
                </div>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="https://6vt.d95.myftpupload.com/wp-content/uploads/2025/06/output-onlinepngtools-4.png" alt="Hair Analysis"/>
                        </div>
                        <h3>AI Hair Analysis</h3>
                        <p>Get instant insights about your hair type, health, and needs through our advanced image recognition technology.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="https://6vt.d95.myftpupload.com/wp-content/uploads/2025/06/output-onlinepngtools-3.png" alt="Hair Analysis"/>
                        </div>
                        <h3>Journey Timeline</h3>
                        <p>Visualize your hair progress over time with our beautiful timeline feature that tracks every milestone.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="https://6vt.d95.myftpupload.com/wp-content/uploads/2025/06/output-onlinepngtools-5.png" alt="Hair Analysis"/>
                        </div>
                        <h3>Community Support</h3>
                        <p>Connect with others on similar hair journeys, share tips, and get inspired by real transformations.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Hair Journey Section -->
        <section class="hair-journey">
            <div class="container">
                <div class="journey-container">
                    <div class="journey-image">
                        <img src="https://6vt.d95.myftpupload.com/wp-content/uploads/2025/06/pexels-zandatsu-32213423-scaled.jpg" alt="Woman with beautiful curly hair">
                    </div>
                    <div class="journey-content">
                        <h2>Track Your Hair Transformation</h2>
                        <p>Myavana's patented technology creates a customized roadmap for your hair health and beauty goals. We analyze multiple factors to develop a plan that evolves with your hair's changing needs.</p>
                        <div class="journey-steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Create Your Profile</h4>
                                    <p>Tell us about your hair type, challenges, and goals to personalize your experience.</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Document Your Journey</h4>
                                    <p>Add entries with photos, notes, and product reviews to track your progress.</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>See Your Growth</h4>
                                    <p>Watch your hair transform through our visual timeline and analytics dashboard.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- AI Chatbot Preview -->
        

        <!-- Community Section -->
        <section id="community" class="community section section-padding">
            <div class="container">
                <div class="text-center fade-in">
                    <h2 class="start-title " style="margin-bottom: 20px;">JOIN OUR COMMUNITY</h2>
                    <p class="body-text" style="font-size: 20px; color: var(--blueberry); max-width: 600px; margin: 0 auto;">Connect with fellow hair enthusiasts, share your journey, and celebrate transformations together</p>
                </div>
                
                <div class="testimonial-grid">
                    <div class="testimonial fade-in">
                        <div class="testimonial-content">
                            <p>MYAVANA completely transformed how I understand my hair. The AI analysis helped me identify what my hair actually needed, and now I have the healthiest hair I've ever had!</p>
                        </div>
                        <div class="testimonial-author">— Sarah, 4C Natural Hair Journey</div>
                    </div>
                    
                    <div class="testimonial fade-in">
                        <div class="testimonial-content">
                            <p>The timeline feature is incredible! Being able to see my hair journey over time and track what products actually worked has been a game-changer for my routine.</p>
                        </div>
                        <div class="testimonial-author">— Maya, Transitioning Hair</div>
                    </div>
                    
                    <div class="testimonial fade-in">
                        <div class="testimonial-content">
                            <p>I love the community aspect. Sharing my journey and seeing others' transformations keeps me motivated and inspired to take better care of my hair.</p>
                        </div>
                        <div class="testimonial-author">— Jasmine, Curly Hair Enthusiast</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA Section -->
        <section class="final-cta section section-padding">
            <div class="container text-center fade-in">
                <h2 class="heading-primary text-white" style="color: #fff !important;">READY TO TRANSFORM YOUR HAIR JOURNEY?</h2>
                <p class="body-text">Join thousands of women who are taking control of their hair health with AI-powered insights and community support.</p>
                <button class="cta-button myavana-signup-b">Start Your Free Journey Today</button>
            </div>
        </section>

        <!-- Footer -->
        <footer class="section-padding">
            <div class="container">
                <div class="footer-grid">
                    <div class="footer-about">
                        <div class="footer-logo">
                            <img src="https://6vt.d95.myftpupload.com/wp-content/uploads/2025/06/myavana_primary_white_and_coral.avif" alt="Myavana Logo">
                            <!-- <span class="footer-logo-text">MYAVANA</span> -->
                        </div>
                        <p>Empowering you with personalized hair care through AI technology and scientific expertise.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-pinterest-p"></i></a>
                            <a href="#"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                    <div class="footer-links">
                        <h4>Myavana</h4>
                        <ul>
                            <li><a href="#">About Us</a></li>
                            <li><a href="#">Our Technology</a></li>
                            <li><a href="#">Press</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">Contact</a></li>
                        </ul>
                    </div>
                    <div class="footer-links">
                        <h4>Resources</h4>
                        <ul>
                            <li><a href="#">Hair Type Guide</a></li>
                            <li><a href="#">Product Reviews</a></li>
                            <li><a href="#">Hair Care Tips</a></li>
                            <li><a href="#">Blog</a></li>
                            <li><a href="#">FAQs</a></li>
                        </ul>
                    </div>
                    <div class="footer-newsletter">
                        <h4>Stay Connected</h4>
                        <p>Subscribe to get hair care tips, exclusive offers, and product updates.</p>
                        <form class="newsletter-form">
                            <input type="email" placeholder="Your email address">
                            <button type="submit"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </footer>


    </div>
<?php
    return ob_get_clean();
}