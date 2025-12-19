<?php
/**
 * MYAVANA Hair Journey - Luxury Homepage Template
 *
 * A beautiful, user-centric homepage that appeals to both registered and non-registered users
 * Uses luxury MYAVANA branding: onyx (#222323), white (#ffffff), and coral (#e7a690)
 */

function myavana_luxury_home_shortcode() {
    // Check if user is logged in
    $is_logged_in = is_user_logged_in();
    $current_user = wp_get_current_user();

    // Get user profile data if logged in
    $user_profile = null;
    if ($is_logged_in) {
        global $wpdb;
        $user_profile = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}myavana_profiles WHERE user_id = %d",
            $current_user->ID
        ));
    }

    // Get user stats for logged-in users
    $user_stats = [];
    $is_new_user = false;
    if ($is_logged_in) {
        global $wpdb;
        $entries_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_author = %d AND post_type = 'hair_journey_entry' AND post_status = 'publish'",
            $current_user->ID
        ));
        $days_active = $wpdb->get_var($wpdb->prepare(
            "SELECT DATEDIFF(CURDATE(), MIN(post_date)) FROM {$wpdb->prefix}posts WHERE post_author = %d AND post_type = 'hair_journey_entry'",
            $current_user->ID
        ));

        // Check if user is new (no entries and recent registration)
        $user_registered = strtotime($current_user->user_registered);
        $days_since_registration = (time() - $user_registered) / (24 * 60 * 60);
        $is_new_user = ($entries_count == 0 && $days_since_registration <= 7);

        // Check if user has completed onboarding
        $onboarding_completed = get_user_meta($current_user->ID, 'myavana_onboarding_completed', true);
        $show_onboarding = ($is_new_user && !$onboarding_completed);

        $user_stats = [
            'entries' => $entries_count ?: 0,
            'days_active' => $days_active ?: 0,
            'streak' => 7, // Placeholder for streak calculation
            'is_new_user' => $is_new_user,
            'show_onboarding' => $show_onboarding,
            'onboarding_completed' => ($onboarding_completed === 'completed') ? true : false
        ];
    }

    
    wp_enqueue_style('myavana-free-analysis', MYAVANA_URL . 'assets/css/free-hair-analysis.css', [], '1.0.0');
    
    wp_enqueue_script('myavana-free-analysis', MYAVANA_URL . 'assets/js/free-hair-analysis.js', ['jquery'], '1.0.0', true);

    // Enqueue AI Analysis Modal for logged-in users
    if (is_user_logged_in()) {
        wp_enqueue_script('myavana-ai-analysis-modal', MYAVANA_URL . 'assets/js/ai-analysis-modal.js', ['jquery'], '1.0.0', true);
        wp_localize_script('myavana-ai-analysis-modal', 'myavanaAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('myavana_profile_nonce')
        ]);
    }

    // Localize script with AJAX data
    wp_localize_script('myavana-luxury-home', 'myavanaLuxuryData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myavana_nonce'),
        'isLoggedIn' => $is_logged_in,
        'currentUserId' => $is_logged_in ? $current_user->ID : 0,
        'currentUserName' => $is_logged_in ? $current_user->display_name : '',
        'userStats' => $user_stats,
        'isNewUser' => $is_new_user,
        'showOnboarding' => $is_logged_in && isset($show_onboarding) ? $show_onboarding : false
    ]);

    ob_start();
    ?>

    <div class="myavana-luxury-homepage">
        <!-- Luxury Navigation -->
        <nav class="myavana-luxury-nav">
            <div class="myavana-luxury-nav-container">
                <a href="<?php echo home_url(); ?>" class="myavana-luxury-logo">
                    <div class="myavana-logo-section">
                        <img src="<?php echo esc_url(home_url()); ?>/wp-content/plugins/myavana-hair-journey/assets/images/myavana-primary-logo.png"
                            alt="Myavana Logo" class="myavana-logo" />
                    </div>
                </a>

                <?php if (!$is_logged_in): ?>
                    <!-- GUEST NAV -->
                    <div class="myavana-luxury-nav-menu">
                        <a href="#features" class="myavana-luxury-nav-link">Features</a>
                        <a href="#how-it-works" class="myavana-luxury-nav-link">How It Works</a>
                        <a href="#" onclick="showMyavanaModal('login'); return false;" class="myavana-luxury-nav-link myavana-nav-signin-mobile">
                            Sign In
                        </a>
                    </div>

                    <div class="myavana-luxury-nav-actions">
                        <button class="myavana-luxury-btn-secondary" onclick="showMyavanaModal('login')">Sign In</button>
                        <button class="myavana-luxury-btn-primary" onclick="showMyavanaModal('register')">Start Your Journey</button>
                    </div>

                <?php else: ?>
                    <!-- LOGGED-IN NAV -->
                    <div class="myavana-luxury-nav-menu" id="mainNavMenu">
                        <a href="/hair-journey/" class="myavana-luxury-nav-link">My Hair Journey</a>
                        <a href="/community/" class="myavana-luxury-nav-link">Community</a>
                        <!-- <a href="/members/admin/hair_insights/" class="myavana-luxury-nav-link">Analytics</a> -->
                        <a style="cursor: pointer;" class="myavana-luxury-nav-link" onclick="createGoal()">+ Goal</a>
                            <a style="cursor: pointer;" class="myavana-luxury-nav-link" onclick="createRoutine()">+ Routine</a>
                            <a style="cursor: pointer;" class="myavana-luxury-nav-link" onclick="openAIAnalysisModal()">Smart Entry</a>
                            <a style="cursor: pointer;" class="myavana-luxury-nav-link" onclick="createEntry()">+ Entry</a>
                        <!-- Action Buttons - Desktop -->
                        <!-- <div class="myavana-luxury-nav-action-buttons desktop-only">
                           
                        </div> -->

                        <!-- Logout always visible on desktop -->
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="myavana-luxury-nav-link myavana-nav-logout-desktop">
                            Logout
                        </a>
                    </div>

                    <!-- Right side: Avatar + Action Buttons on Mobile -->
                    <!-- <div class="myavana-luxury-nav-actions" id="mobileActionArea">
                        <div class="myavana-luxury-profile-dropdown">
                            <img src="<?php echo get_avatar_url($current_user->ID, ['size' => 40]); ?>"
                                alt="<?php echo esc_attr($current_user->display_name); ?>"
                                class="myavana-luxury-avatar"
                                onclick="toggleProfileDropdown(event)">
                        </div>
                    </div> -->

                    <!-- Mobile Menu Toggle -->
                    <!-- CORRECT — only jQuery handles it -->
                    <button class="myavana-luxury-mobile-toggle" aria-label="Toggle menu">
                        <span></span><span></span><span></span>
                    </button>
                <?php endif; ?>

                <!-- MOBILE SLIDE-OUT MENU (only for logged-in users) -->
                <?php if ($is_logged_in): ?>
                <div class="myavana-mobile-menu-overlay" id="mobileMenuOverlay" onclick="toggleMobileMenu()"></div>
                <div class="myavana-mobile-menu-panel" id="mobileMenuPanel">
                    <div class="mobile-menu-header">
                        <div class="mobile-menu-user">
                            <img src="<?php echo get_avatar_url($current_user->ID, ['size' => 60]); ?>" alt="Avatar" class="mobile-menu-avatar">
                            <div>
                                <strong><?php echo esc_html($current_user->display_name); ?></strong>
                                <small>Welcome back!</small>
                            </div>
                        </div>
                    </div>

                    <div class="mobile-menu-links">
                        <a href="/hair-journey/">My Hair Journey</a>
                        <a href="/members/admin/hair_insights/">Analytics</a>
                        <hr>
                        <button type="button" class="mobile-menu-action" onclick="createGoal(); toggleMobileMenu()">+ Goal</button>
                        <button type="button" class="mobile-menu-action" onclick="createRoutine(); toggleMobileMenu()">+ Routine</button>
                        <button type="button" class="mobile-menu-action smart" onclick="openAIAnalysisModal(); toggleMobileMenu()">Smart Entry</button>
                        <button type="button" class="mobile-menu-action primary" onclick="createEntry(); toggleMobileMenu()">+ Entry</button>
                        <hr>
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="mobile-menu-logout">Logout</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </nav>
        <!-- Hero Section -->
        <section class="myavana-luxury-hero">
            <div class="myavana-luxury-hero-container">
                <?php if (!$is_logged_in): ?>
                    <!-- Non-logged-in Hero -->
                    <div class="myavana-luxury-hero-content">
                        <div class="myavana-luxury-hero-badge">
                            ✨ AI-Powered Hair Care Revolution
                        </div>
                        <h1 class="myavana-luxury-hero-title">
                            Transform Your<br>
                            <span class="gradient-text">Hair Journey</span>
                        </h1>
                        <h2 class="myavana-luxury-hero-subtitle">
                            Professional Hair Care, Personalized for You
                        </h2>
                        <p class="myavana-luxury-hero-description">
                            Join thousands of women who've transformed their hair health with our AI-powered platform.
                            Get personalized recommendations, track your progress, and connect with a supportive community.
                        </p>
                        <div class="myavana-luxury-hero-actions">
                            <button class="myavana-luxury-btn-primary" onclick="showMyavanaModal('register')">
                                <i class="fas fa-sparkles"></i>
                                Start Your Journey
                            </button>
                            <button class="myavana-luxury-btn-secondary" onclick="scrollToSection('features')">
                                <i class="fas fa-play"></i>
                                See How It Works
                            </button>
                        </div>
                    </div>

                    <div class="myavana-luxury-hero-visual">
                        <div class="myavana-luxury-hero-image">
                        <div class="hero-image"></div>
                        </div>

                        <!-- Global Stats -->
                        <div class="myavana-luxury-global-stats">
                            <div class="myavana-luxury-stat">
                                <span class="myavana-luxury-stat-number">50K+</span>
                                <span class="myavana-luxury-stat-label">Hair Journeys</span>
                            </div>
                            <div class="myavana-luxury-stat">
                                <span class="myavana-luxury-stat-number">98%</span>
                                <span class="myavana-luxury-stat-label">Satisfaction Rate</span>
                            </div>
                            <div class="myavana-luxury-stat">
                                <span class="myavana-luxury-stat-number">1M+</span>
                                <span class="myavana-luxury-stat-label">AI Analyses</span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Logged-in Hero -->
                    <div class="myavana-luxury-hero-content">
                        <?php if ($is_new_user && $user_stats['entries'] == 0): ?>
                            <!-- New User with No Entries -->
                            <div class="myavana-luxury-hero-badge">
                                Welcome to MYAVANA, <?php echo esc_html($current_user->display_name); ?>! 🎉
                            </div>
                            <h1 class="myavana-luxury-hero-title">
                                Let's Start Your<br>
                                <span class="gradient-text">Hair Journey</span>
                            </h1>
                            <h2 class="myavana-luxury-hero-subtitle">
                                Ready to transform your hair with personalized AI insights?
                            </h2>
                            <p class="myavana-luxury-hero-description">
                                Let's get you started with a quick setup to understand your hair goals and create your first entry.
                                This will help us provide personalized recommendations just for you.
                            </p>
                            <div class="myavana-luxury-hero-actions">
                                <!-- <button class="myavana-luxury-btn-primary" onclick="MyavanaLuxuryHomepage.startOnboarding()">
                                    <i class="fas fa-rocket"></i>
                                    Start My Journey
                                </button> -->
                                <a class="myavana-luxury-btn-primary" href="/hair-journey/">
                                    <i class="fas fa-rocket"></i>
                                    Start My Journey
                                </a> 
                                <!-- <button class="myavana-luxury-btn-secondary" onclick="showMyavanaModal('new-entry')">
                                    <i class="fas fa-camera"></i>
                                    Quick Entry
                                </button> -->
                            </div>
                        <?php else: ?>
                            <!-- Existing User -->
                            <div class="myavana-luxury-hero-badge">
                                Welcome back, <?php echo esc_html($current_user->display_name); ?>! ✨
                            </div>
                            <h1 class="myavana-luxury-hero-title">
                                Your Hair<br>
                                <span class="gradient-text">Journey Continues</span>
                            </h1>
                            <h2 class="myavana-luxury-hero-subtitle">
                                <?php
                                $greeting_messages = [
                                    "Ready to add today's progress?",
                                    "Your hair transformation awaits!",
                                    "Let's capture your beautiful journey!",
                                    "Time to document your hair evolution!"
                                ];
                                echo $greeting_messages[array_rand($greeting_messages)];
                                ?>
                            </h2>
                            <p class="myavana-luxury-hero-description">
                                <?php if ($user_profile && isset($user_profile->hair_health_rating)): ?>
                                    Your current hair health score: <strong><?php echo esc_html($user_profile->hair_health_rating); ?>/10</strong>
                                    <br>Keep up the amazing progress on your hair care routine!
                                <?php else: ?>
                                    You have <strong><?php echo $user_stats['entries']; ?></strong> entries in your journey.
                                    Keep documenting your progress to see amazing transformations!
                                <?php endif; ?>
                            </p>
                            <div class="myavana-luxury-hero-actions">
                                <a class="myavana-luxury-btn-primary" href="/hair-journey/">
                                    <i class="fas fa-camera"></i>
                                    My Hair Diary
                                </a>
                                <button class="myavana-luxury-btn-secondary" onclick="showMyavanaModal('ai-analysis')">
                                    <i class="fas fa-magic"></i>
                                    AI Analysis
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="myavana-luxury-hero-visual">
                        <!-- Personal Stats -->
                        <div class="myavana-luxury-personal-stats">
                            <div class="myavana-luxury-stat">
                                <span class="myavana-luxury-stat-number"><?php echo $user_stats['entries']; ?></span>
                                <span class="myavana-luxury-stat-label">Entries</span>
                            </div>
                            <div class="myavana-luxury-stat">
                                <span class="myavana-luxury-stat-number"><?php echo $user_stats['days_active']; ?></span>
                                <span class="myavana-luxury-stat-label">Days Active</span>
                            </div>
                            <div class="myavana-luxury-stat">
                                <span class="myavana-luxury-stat-number"><?php echo $user_stats['streak']; ?></span>
                                <span class="myavana-luxury-stat-label">Day Streak</span>
                            </div>
                        </div>

                        <!-- Quick Actions Dashboard -->
                        <div class="myavana-luxury-quick-dashboard">
                            <div class="quick-action-card" onclick="showMyavanaModal('timeline')">
                                <div class="action-icon">
                                    <i class="fas fa-timeline"></i>
                                </div>
                                <div class="action-content">
                                    <h4>View Timeline</h4>
                                    <p>See your hair journey progress</p>
                                </div>
                            </div>
                            <div class="quick-action-card" onclick="showMyavanaModal('analytics')">
                                <div class="action-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="action-content">
                                    <h4>Analytics</h4>
                                    <p>Track your improvements</p>
                                </div>
                            </div>
                            <div class="quick-action-card" onclick="showMyavanaModal('ai-chat')">
                                <div class="action-icon">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="action-content">
                                    <h4>AI Assistant</h4>
                                    <p>Get personalized advice</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!$is_logged_in): ?>
            <!-- Free Hair Analysis CTA Section -->
            <section class="myavana-free-analysis-cta" id="free-analysis">
                <div class="myavana-free-analysis-container">
                    <div class="myavana-free-analysis-content">
                        <div class="myavana-free-analysis-badge">
                            <span class="badge-icon">✨</span>
                            <span class="badge-text">Free AI-Powered Analysis</span>
                        </div>

                        <h2 class="myavana-free-analysis-title">
                            Discover Your<br>
                            <span class="gradient-text-animated">Perfect Hair Care Routine</span>
                        </h2>

                        <p class="myavana-free-analysis-description">
                            Get instant, personalized insights about your hair with our advanced AI technology.
                            Upload a photo and receive professional analysis in seconds – completely free!
                        </p>

                        <div class="myavana-free-analysis-features">
                            <div class="feature-pill">
                                <i class="fas fa-bolt"></i>
                                <span>Instant Results</span>
                            </div>
                            <div class="feature-pill">
                                <i class="fas fa-brain"></i>
                                <span>AI-Powered</span>
                            </div>
                            <div class="feature-pill">
                                <i class="fas fa-gift"></i>
                                <span>3 Free Analyses</span>
                            </div>
                            <div class="feature-pill">
                                <i class="fas fa-lock"></i>
                                <span>No Account Needed</span>
                            </div>
                        </div>

                        <button class="myavana-free-analysis-btn" id="startFreeAnalysisBtn">
                            <span class="btn-icon">📸</span>
                            <span class="btn-text">Start Free Hair Analysis</span>
                            <span class="btn-shine"></span>
                        </button>

                        <div class="myavana-free-analysis-trust">
                            <div class="trust-stat">
                                <span class="trust-number">1M+</span>
                                <span class="trust-label">Analyses Completed</span>
                            </div>
                            <div class="trust-stat">
                                <span class="trust-number">4.9★</span>
                                <span class="trust-label">Average Rating</span>
                            </div>
                            <div class="trust-stat">
                                <span class="trust-number">98%</span>
                                <span class="trust-label">Satisfaction Rate</span>
                            </div>
                        </div>
                    </div>

                    <div class="myavana-free-analysis-visual">
                        <div class="analysis-preview-card">
                            <div class="preview-header">
                                <div class="preview-avatar"></div>
                                <div class="preview-info">
                                    <div class="preview-name">Your Hair Analysis</div>
                                    <div class="preview-status">Powered by Myavana AI</div>
                                </div>
                            </div>
                            <div class="preview-image">
                                <img src="<?php echo MYAVANA_URL; ?>assets/images/analysis-image.jpg"
                                     alt="Hair Analysis Preview"
                                     class="preview-analysis-image">
                            </div>
                            <div class="preview-results">
                                <div class="result-item">
                                    <span class="result-icon">🎯</span>
                                    <span class="result-text">Hair Type Analysis</span>
                                </div>
                                <div class="result-item">
                                    <span class="result-icon">💪</span>
                                    <span class="result-text">Health Assessment</span>
                                </div>
                                <div class="result-item">
                                    <span class="result-icon">⭐</span>
                                    <span class="result-text">Personalized Tips</span>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Elements -->
                        <div class="floating-element element-1">
                            <i class="fas fa-sparkles"></i>
                        </div>
                        <div class="floating-element element-2">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="floating-element element-3">
                            <i class="fas fa-heart"></i>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Section (Non-logged-in users only) -->
            <section class="myavana-luxury-features" id="features">
                <div class="myavana-luxury-features-container">
                    <div class="myavana-luxury-section-header">
                        <div class="myavana-luxury-section-badge">Why MYAVANA</div>
                        <h2 class="myavana-luxury-section-title">
                            Everything You Need for<br>
                            <span class="gradient-text">Beautiful Hair</span>
                        </h2>
                        <p class="myavana-luxury-section-description">
                            Our comprehensive platform combines cutting-edge AI technology with expert knowledge
                            to give you personalized hair care like never before.
                        </p>
                    </div>

                    <div class="myavana-luxury-features-grid">
                        <div class="myavana-luxury-feature-card">
                            <div class="myavana-luxury-feature-icon">
                                <i class="fas fa-magic"></i>
                            </div>
                            <h3 class="myavana-luxury-feature-title">AI Hair Analysis</h3>
                            <p class="myavana-luxury-feature-description">
                                Get instant, professional-grade analysis of your hair health, texture, and needs
                                using our advanced AI vision technology.
                            </p>
                            <a href="#" class="myavana-luxury-feature-link" onclick="showMyavanaModal('register')">
                                Try Analysis <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                        <div class="myavana-luxury-feature-card">
                            <div class="myavana-luxury-feature-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h3 class="myavana-luxury-feature-title">Journey Tracking</h3>
                            <p class="myavana-luxury-feature-description">
                                Document your hair transformation with photos, notes, and progress tracking.
                                See your beautiful journey unfold over time.
                            </p>
                            <a href="#" class="myavana-luxury-feature-link" onclick="showMyavanaModal('register')">
                                Start Tracking <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                        <div class="myavana-luxury-feature-card">
                            <div class="myavana-luxury-feature-icon">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <h3 class="myavana-luxury-feature-title">Personalized Routines</h3>
                            <p class="myavana-luxury-feature-description">
                                Receive custom hair care routines tailored to your specific hair type, goals,
                                and lifestyle preferences.
                            </p>
                            <a href="#" class="myavana-luxury-feature-link" onclick="showMyavanaModal('register')">
                                Get Routine <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                        <div class="myavana-luxury-feature-card">
                            <div class="myavana-luxury-feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="myavana-luxury-feature-title">Community Support</h3>
                            <p class="myavana-luxury-feature-description">
                                Connect with thousands of women on similar journeys. Share experiences,
                                get advice, and celebrate wins together.
                            </p>
                            <a href="#community" class="myavana-luxury-feature-link">
                                Join Community <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                        <div class="myavana-luxury-feature-card">
                            <div class="myavana-luxury-feature-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3 class="myavana-luxury-feature-title">Progress Analytics</h3>
                            <p class="myavana-luxury-feature-description">
                                Detailed insights and analytics to track your hair health improvements,
                                routine effectiveness, and goal achievement.
                            </p>
                            <a href="#" class="myavana-luxury-feature-link" onclick="showMyavanaModal('register')">
                                View Analytics <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                        <div class="myavana-luxury-feature-card">
                            <div class="myavana-luxury-feature-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h3 class="myavana-luxury-feature-title">Mobile Experience</h3>
                            <p class="myavana-luxury-feature-description">
                                Access your hair journey anywhere with our responsive design and
                                progressive web app capabilities.
                            </p>
                            <a href="#" class="myavana-luxury-feature-link" onclick="showMyavanaModal('register')">
                                Get Started <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- How It Works Section -->
            <section class="myavana-luxury-how-it-works" id="how-it-works">
                <div class="myavana-luxury-how-it-works-container">
                    <div class="myavana-luxury-section-header">
                        <div class="myavana-luxury-section-badge">Simple Process</div>
                        <h2 class="myavana-luxury-section-title">
                            How It Works
                        </h2>
                        <p class="myavana-luxury-section-description">
                            Get started with your hair transformation in just 3 simple steps.
                        </p>
                    </div>

                    <div class="myavana-luxury-steps-grid">
                        <div class="myavana-luxury-step">
                            <div class="myavana-luxury-step-number">1</div>
                            <div class="myavana-luxury-step-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h3 class="myavana-luxury-step-title">Create Your Profile</h3>
                            <p class="myavana-luxury-step-description">
                                Sign up and tell us about your hair type, goals, and current routine.
                                This helps our AI understand your unique needs.
                            </p>
                        </div>

                        <div class="myavana-luxury-step">
                            <div class="myavana-luxury-step-number">2</div>
                            <div class="myavana-luxury-step-icon">
                                <i class="fas fa-camera"></i>
                            </div>
                            <h3 class="myavana-luxury-step-title">Take Your First Photo</h3>
                            <p class="myavana-luxury-step-description">
                                Upload a photo of your hair for instant AI analysis. Get detailed insights
                                about your hair health and personalized recommendations.
                            </p>
                        </div>

                        <div class="myavana-luxury-step">
                            <div class="myavana-luxury-step-number">3</div>
                            <div class="myavana-luxury-step-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3 class="myavana-luxury-step-title">Track & Transform</h3>
                            <p class="myavana-luxury-step-description">
                                Follow your personalized routine, document your progress, and watch
                                your hair transform over time with detailed analytics.
                            </p>
                        </div>
                    </div>

                    <div class="myavana-luxury-cta-center">
                        <button class="myavana-luxury-btn-primary" onclick="showMyavanaModal('register')">
                            <i class="fas fa-rocket"></i>
                            Start Your Transformation
                        </button>
                    </div>
                </div>
            </section>

            <!-- Call to Action Section -->
            <section class="myavana-luxury-final-cta">
                <div class="myavana-luxury-final-cta-container">
                    <h2 class="myavana-luxury-final-cta-title">
                        Ready to Transform<br>
                        <span class="gradient-text">Your Hair Journey?</span>
                    </h2>
                    <p class="myavana-luxury-final-cta-description">
                        Join thousands of women who've already started their hair transformation with MYAVANA.
                        Your beautiful hair journey starts here.
                    </p>
                    <div class="myavana-luxury-final-cta-actions">
                        <button class="myavana-luxury-btn-primary" onclick="showMyavanaModal('register')">
                            <i class="fas fa-sparkles"></i>
                            Start Free Today
                        </button>
                        <button class="myavana-luxury-btn-secondary" onclick="showMyavanaModal('login')">
                            <i class="fas fa-sign-in-alt"></i>
                            Sign In
                        </button>
                    </div>

                    <!-- Trust Indicators -->
                    <div class="myavana-luxury-trust-indicators">
                        <div class="trust-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>100% Secure</span>
                        </div>
                        <div class="trust-item">
                            <i class="fas fa-gift"></i>
                            <span>Free to Start</span>
                        </div>
                        <div class="trust-item">
                            <i class="fas fa-heart"></i>
                            <span>50K+ Happy Users</span>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Entry Form Modal -->
            <div class="myavana-modal-overlay" id="entryModal" style="display: none;">
                <div class="mya-modal">
                    <div class="mya-modal-header">
                        <h2 class="mya-modal-title" id="modalTitle">Add Hair Journey Entry</h2>
                        <button class="myavana-close-btn" id="closeModalBtn">×</button>
                    </div>
                    <div class="mya-modal-body">
                        <form id="entryForm" class="myavana-entry-form">
                            <input type="hidden" id="entryId" name="entry_id" value="">
                            <input type="hidden" id="entryDate" name="entry_date" value="">

                            <div class="myavana-form-group">
                                <label class="myavana-form-label" for="entryTitle">Entry Title *</label>
                                <input type="text" id="entryTitle" name="title" class="myavana-form-input"
                                    placeholder="e.g., Wash day with new products" required>
                            </div>

                            <div class="myavana-form-group">
                                <label class="myavana-form-label" for="entryType">Entry Type *</label>
                                <select id="entryType" name="entry_type" class="myavana-form-select" required>
                                    <option value="">Select entry type</option>
                                    <option value="wash">Wash Day</option>
                                    <option value="treatment">Treatment</option>
                                    <option value="styling">Styling</option>
                                    <option value="progress">Progress Photo</option>
                                    <option value="general">General</option>
                                </select>
                            </div>

                            <div class="myavana-form-group">
                                <label class="myavana-form-label" for="entryDescription">Description</label>
                                <textarea id="entryDescription" name="description" class="myavana-form-textarea"
                                        rows="4" placeholder="Describe your hair journey moment..."></textarea>
                            </div>

                            <div class="myavana-form-row">
                                <div class="myavana-form-group">
                                    <label class="myavana-form-label" for="healthRating">Hair Health (1-10)</label>
                                    <div class="myavana-rating-input">
                                        <input type="range" id="healthRating" name="health_rating"
                                            min="1" max="10" value="5" class="myavana-range-input">
                                        <div class="myavana-rating-display">
                                            <span id="ratingValue">5</span>/10
                                        </div>
                                    </div>
                                </div>

                                <div class="myavana-form-group">
                                    <label class="myavana-form-label" for="moodRating">How You Feel</label>
                                    <select id="moodRating" name="mood" class="myavana-form-select">
                                        <option value="excited">😊 Excited</option>
                                        <option value="happy">😄 Happy</option>
                                        <option value="content">😌 Content</option>
                                        <option value="neutral">😐 Neutral</option>
                                        <option value="concerned">😟 Concerned</option>
                                        <option value="frustrated">😤 Frustrated</option>
                                    </select>
                                </div>
                            </div>

                            <div class="myavana-form-group">
                                <label class="myavana-form-label" for="productsUsed">Products Used</label>
                                <input type="text" id="productsUsed" name="products" class="myavana-form-input"
                                    placeholder="e.g., Moisturizing shampoo, leave-in conditioner">
                            </div>

                            <div class="myavana-form-group">
                                <label class="myavana-form-label" for="entryNotes">Notes & Observations</label>
                                <textarea id="entryNotes" name="notes" class="myavana-form-textarea"
                                        rows="3" placeholder="Any additional notes or observations..."></textarea>
                            </div>

                            <div class="myavana-form-group">
                                <label class="myavana-form-label" for="entryPhoto">Upload Photo</label>
                                <div class="myavana-file-upload">
                                    <input type="file" id="entryPhoto" name="photo" accept="image/*" class="myavana-file-input">
                                    <label for="entryPhoto" class="myavana-file-label">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <polyline points="21,15 16,10 5,21"/>
                                        </svg>
                                        <span>Choose photo or drag & drop</span>
                                    </label>
                                    <div class="myavana-file-preview" id="photoPreview" style="display: none;"></div>
                                </div>
                            </div>

                            <div class="myavana-form-actions">
                                <button type="button" class="myavana-btn-secondary" id="cancelBtn">Cancel</button>
                                <button type="submit" class="myavana-btn-primary" id="saveBtn">
                                    <span class="myavana-btn-text">Save Entry</span>
                                    <span class="myavana-btn-loading" style="display: none;">
                                        <svg class="myavana-spinner" width="16" height="16" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                            <path d="M12,2 A10,10 0 0,1 22,12" stroke="currentColor" stroke-width="4" fill="none"/>
                                        </svg>
                                        Saving...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Luxury Homepage JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navigation scroll effect
        const nav = document.querySelector('.myavana-luxury-nav');
        let lastScrollY = window.scrollY;

        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }

            // Hide/show nav on scroll
            if (window.scrollY > lastScrollY && window.scrollY > 100) {
                nav.style.transform = 'translateY(-100%)';
            } else {
                nav.style.transform = 'translateY(0)';
            }
            lastScrollY = window.scrollY;
        });

        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.myavana-luxury-feature-card, .myavana-luxury-step, .myavana-luxury-stat').forEach(el => {
            observer.observe(el);
        });

        // Stats counter animation
        const animateCounter = (element, target, duration = 2000) => {
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current).toLocaleString();
            }, 16);
        };

        // Animate counters when they come into view
        const statNumbers = document.querySelectorAll('.myavana-luxury-stat-number');
        statNumbers.forEach(stat => {
            observer.observe(stat);
            stat.addEventListener('animateIn', () => {
                const value = stat.textContent.replace(/[^\d]/g, '');
                if (value) {
                    animateCounter(stat, parseInt(value));
                }
            });
        });
    });

    // Global functions
    function scrollToSection(sectionId) {
        const element = document.getElementById(sectionId);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth' });
        }
    }
  
    

    function toggleProfileDropdown(e) {
        e.stopPropagation();
        let menu = document.querySelector('.myavana-luxury-profile-dropdown-menu');
        
        if (!menu) {
            // Create dropdown if it doesn't exist
            const dropdownHTML = `
                <div class="myavana-luxury-profile-dropdown-menu">
                    <a href="/members/<?php echo $current_user->user_login; ?>/profile/">View Profile</a>
                    <a href="/hair-journey/">My Hair Journey</a>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="logout">Logout</a>
                </div>
            `;
            document.querySelector('.myavana-luxury-profile-dropdown').insertAdjacentHTML('beforeend', dropdownHTML);
            menu = document.querySelector('.myavana-luxury-profile-dropdown-menu');
        }
        
        menu.classList.toggle('show');

        // Close when clicking outside
        const closeDropdown = (e) => {
            if (!e.target.closest('.myavana-luxury-profile-dropdown')) {
                menu.classList.remove('show');
                document.removeEventListener('click', closeDropdown);
            }
        };
        setTimeout(() => document.addEventListener('click', closeDropdown), 0);
    }

    // Modal integration (assumes modal system exists)
    if (typeof showMyavanaModal !== 'function') {
        window.showMyavanaModal = function(modalType) {
            console.log('Opening modal:', modalType);
            // Fallback behavior if modal system isn't loaded
            if (modalType === 'register') {
                alert('Registration modal would open here');
            } else if (modalType === 'login') {
                alert('Login modal would open here');
            }
        };
    }
    </script>

    <!-- Free Hair Analysis Modal -->
    <div class="myavana-free-analysis-modal" id="freeAnalysisModal">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <div class="modal-controls">
                <button class="modal-expand-btn" id="expandModalBtn" title="Expand">
                    <i class="fas fa-expand-alt"></i>
                </button>
                <button class="modal-export-btn" id="exportResultsBtn" title="Export Results" style="display: none;">
                    <i class="fas fa-download"></i>
                </button>
                <button class="modal-close" id="closeFreeAnalysisModal" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-content-wrapper">
                <!-- Step 1: Upload -->
                <div class="modal-step active" id="uploadStep">
                <div class="modal-header">
                    <div class="modal-icon">📸</div>
                    <h3 class="modal-title">Upload Your Hair Photo</h3>
                    <p class="modal-description">Take or upload a clear photo of your hair for AI analysis</p>
                </div>

                <div class="upload-area" id="uploadArea">
                    <input type="file" id="hairPhotoInput" accept="image/*" style="display: none;">
                    <div class="upload-content">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt fa-4x"></i>
                        </div>
                        <h4>Drag & Drop or Click to Upload</h4>
                        <p>Supports: JPG, PNG (Max 10MB)</p>
                        <button class="upload-btn" onclick="document.getElementById('hairPhotoInput').click()">
                            Choose Photo
                        </button>
                    </div>
                    <div class="upload-preview" id="uploadPreview" style="display: none;">
                        <img id="previewImage" src="" alt="Preview">
                        <button class="change-photo-btn" onclick="document.getElementById('hairPhotoInput').click()">
                            <i class="fas fa-sync-alt"></i> Change Photo
                        </button>
                    </div>
                </div>

                <div class="modal-actions">
                    <button class="modal-btn-secondary" id="cancelUpload">Cancel</button>
                    <button class="modal-btn-primary" id="analyzeBtn" disabled>
                        <i class="fas fa-magic"></i> Analyze My Hair
                    </button>
                </div>

                <div class="modal-trust-badges">
                    <div class="trust-badge">
                        <i class="fas fa-shield-alt"></i>
                        <span>100% Secure</span>
                    </div>
                    <div class="trust-badge">
                        <i class="fas fa-user-secret"></i>
                        <span>Privacy Protected</span>
                    </div>
                    <div class="trust-badge">
                        <i class="fas fa-bolt"></i>
                        <span>Instant Results</span>
                    </div>
                </div>
            </div>

            <!-- Step 2: Analyzing -->
            <div class="modal-step" id="analyzingStep">
                <div class="analyzing-content">
                    <div class="analyzing-spinner">
                        <div class="spinner-ring"></div>
                        <div class="spinner-icon">🧠</div>
                    </div>
                    <h3>Analyzing Your Hair...</h3>
                    <p class="analyzing-text" id="analyzingText">Our AI is examining your photo</p>
                    <div class="analyzing-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <span class="progress-percent" id="progressPercent">0%</span>
                    </div>
                </div>
            </div>

            <!-- Step 3: Results -->
            <div class="modal-step" id="resultsStep">
                <div class="results-content" id="resultsContent">
                    <!-- Results will be dynamically inserted here -->
                </div>
            </div>
            </div><!-- Close modal-content-wrapper -->
        </div><!-- Close modal-container -->
    </div><!-- Close modal -->

    <?php
    return ob_get_clean();
}
?>