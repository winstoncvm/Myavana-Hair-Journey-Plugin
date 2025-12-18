<?php
/**
 * Advanced Photo Comparison Tools
 * 
 * This class provides advanced photo comparison features for hair journey tracking:
 * - Side-by-side photo comparisons
 * - Timeline progress visualization
 * - Advanced analysis tools (length, volume, health indicators)
 * - Before/after galleries
 * - Measurement tracking with photo overlay
 * - AI-powered hair analysis
 */

if (!defined('ABSPATH')) {
    exit;
}

class Myavana_Photo_Comparison {
    
    private $user_id;
    
    public function __construct() {
        $this->user_id = get_current_user_id();
        $this->init();
    }
    
    private function init() {
        // AJAX handlers
        add_action('wp_ajax_upload_comparison_photo', array($this, 'upload_comparison_photo'));
        add_action('wp_ajax_get_comparison_photos', array($this, 'get_comparison_photos'));
        add_action('wp_ajax_create_photo_comparison', array($this, 'create_photo_comparison'));
        add_action('wp_ajax_analyze_hair_photo', array($this, 'analyze_hair_photo'));
        add_action('wp_ajax_get_progress_timeline', array($this, 'get_progress_timeline'));
        add_action('wp_ajax_save_photo_measurements', array($this, 'save_photo_measurements'));
        add_action('wp_ajax_get_photo_analysis_history', array($this, 'get_photo_analysis_history'));
        
        // Database setup
        add_action('init', array($this, 'create_photo_tables'));
        
        // Shortcodes
        add_shortcode('myavana_photo_comparison', array($this, 'photo_comparison_shortcode'));
    }
    
    /**
     * Create necessary database tables for photo comparison features
     */
    public function create_photo_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Hair photos table
        $photos_table = $wpdb->prefix . 'myavana_hair_photos';
        $photos_sql = "CREATE TABLE $photos_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            photo_url varchar(500) NOT NULL,
            photo_type varchar(50) DEFAULT 'progress',
            angle varchar(20) DEFAULT 'front',
            lighting_conditions varchar(50),
            hair_state varchar(50) DEFAULT 'styled',
            description text,
            measurements longtext,
            analysis_data longtext,
            photo_date datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY photo_type (photo_type),
            KEY photo_date (photo_date)
        ) $charset_collate;";
        
        // Photo comparisons table
        $comparisons_table = $wpdb->prefix . 'myavana_photo_comparisons';
        $comparisons_sql = "CREATE TABLE $comparisons_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            before_photo_id mediumint(9) NOT NULL,
            after_photo_id mediumint(9) NOT NULL,
            comparison_type varchar(50) DEFAULT 'progress',
            time_difference int(11),
            analysis_notes longtext,
            measurements_comparison longtext,
            ai_analysis longtext,
            is_featured tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY comparison_type (comparison_type),
            KEY before_photo_id (before_photo_id),
            KEY after_photo_id (after_photo_id)
        ) $charset_collate;";
        
        // Photo measurements table
        $measurements_table = $wpdb->prefix . 'myavana_photo_measurements';
        $measurements_sql = "CREATE TABLE $measurements_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            photo_id mediumint(9) NOT NULL,
            measurement_type varchar(50) NOT NULL,
            measurement_value decimal(10,2),
            measurement_unit varchar(10) DEFAULT 'inches',
            reference_points longtext,
            calibration_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY photo_id (photo_id),
            KEY measurement_type (measurement_type)
        ) $charset_collate;";
        
        // AI analysis results table
        $analysis_table = $wpdb->prefix . 'myavana_photo_analysis';
        $analysis_sql = "CREATE TABLE $analysis_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            photo_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            analysis_type varchar(50) NOT NULL,
            analysis_results longtext NOT NULL,
            confidence_score decimal(3,2),
            processing_time decimal(5,2),
            ai_model_version varchar(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY photo_id (photo_id),
            KEY user_id (user_id),
            KEY analysis_type (analysis_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($photos_sql);
        dbDelta($comparisons_sql);
        dbDelta($measurements_sql);
        dbDelta($analysis_sql);
    }
    
    /**
     * Photo comparison shortcode
     */
    public function photo_comparison_shortcode($atts) {
        $atts = shortcode_atts(array(
            'view' => 'gallery',
            'limit' => 12
        ), $atts);
        
        ob_start();
        ?>
        <div class="myavana-photo-comparison-container">
            <div class="comparison-toolbar">
                <div class="toolbar-section">
                    <button class="btn btn-primary" id="upload-photo-btn">
                        <i class="fas fa-camera"></i> Add Photo
                    </button>
                    <button class="btn btn-secondary" id="create-comparison-btn">
                        <i class="fas fa-balance-scale"></i> Create Comparison
                    </button>
                </div>
                
                <div class="toolbar-section">
                    <select id="photo-view-filter">
                        <option value="all">All Photos</option>
                        <option value="progress">Progress Photos</option>
                        <option value="before-after">Before & After</option>
                        <option value="measurements">With Measurements</option>
                    </select>
                    
                    <select id="photo-angle-filter">
                        <option value="all">All Angles</option>
                        <option value="front">Front View</option>
                        <option value="back">Back View</option>
                        <option value="left">Left Side</option>
                        <option value="right">Right Side</option>
                        <option value="top">Top View</option>
                    </select>
                </div>
                
                <div class="toolbar-section">
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="gallery">
                            <i class="fas fa-th"></i> Gallery
                        </button>
                        <button class="view-btn" data-view="timeline">
                            <i class="fas fa-chart-line"></i> Timeline
                        </button>
                        <button class="view-btn" data-view="comparison">
                            <i class="fas fa-columns"></i> Compare
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Photo Gallery View -->
            <div class="photo-view" id="gallery-view">
                <div class="photos-grid" id="photos-container">
                    <!-- Photos will be loaded dynamically -->
                </div>
            </div>
            
            <!-- Timeline View -->
            <div class="photo-view" id="timeline-view" style="display: none;">
                <div class="progress-timeline">
                    <div class="timeline-controls">
                        <select id="timeline-period">
                            <option value="3months">Last 3 Months</option>
                            <option value="6months">Last 6 Months</option>
                            <option value="1year">Last Year</option>
                            <option value="all">All Time</option>
                        </select>
                        
                        <div class="timeline-metrics">
                            <label>
                                <input type="checkbox" id="show-measurements"> Show Measurements
                            </label>
                            <label>
                                <input type="checkbox" id="show-analysis"> Show AI Analysis
                            </label>
                        </div>
                    </div>
                    
                    <div class="timeline-container" id="timeline-container">
                        <!-- Timeline will be generated dynamically -->
                    </div>
                </div>
            </div>
            
            <!-- Comparison View -->
            <div class="photo-view" id="comparison-view" style="display: none;">
                <div class="comparison-workspace">
                    <div class="comparison-photos">
                        <div class="photo-slot" id="photo-slot-1">
                            <div class="slot-placeholder">
                                <i class="fas fa-image"></i>
                                <p>Select first photo</p>
                            </div>
                        </div>
                        
                        <div class="comparison-controls">
                            <button class="btn btn-primary" id="analyze-comparison">
                                <i class="fas fa-search"></i> Analyze Changes
                            </button>
                            <button class="btn btn-secondary" id="measure-comparison">
                                <i class="fas fa-ruler"></i> Add Measurements
                            </button>
                            <button class="btn btn-success" id="save-comparison">
                                <i class="fas fa-save"></i> Save Comparison
                            </button>
                        </div>
                        
                        <div class="photo-slot" id="photo-slot-2">
                            <div class="slot-placeholder">
                                <i class="fas fa-image"></i>
                                <p>Select second photo</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="comparison-analysis" id="comparison-results" style="display: none;">
                        <!-- Analysis results will be displayed here -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Photo Upload Modal -->
        <div id="photo-upload-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Add Hair Photo</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="photo-upload-form" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="photo-file">Select Photo</label>
                            <input type="file" id="photo-file" name="photo" accept="image/*" required>
                            <div class="photo-preview" id="photo-preview"></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="photo-type">Photo Type</label>
                                <select id="photo-type" name="photo_type">
                                    <option value="progress">Progress Photo</option>
                                    <option value="before">Before Photo</option>
                                    <option value="after">After Photo</option>
                                    <option value="treatment">Treatment Photo</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="photo-angle">Angle</label>
                                <select id="photo-angle" name="angle">
                                    <option value="front">Front View</option>
                                    <option value="back">Back View</option>
                                    <option value="left">Left Side</option>
                                    <option value="right">Right Side</option>
                                    <option value="top">Top View</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="lighting-conditions">Lighting</label>
                                <select id="lighting-conditions" name="lighting_conditions">
                                    <option value="natural">Natural Light</option>
                                    <option value="indoor">Indoor Light</option>
                                    <option value="flash">Flash</option>
                                    <option value="low-light">Low Light</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="hair-state">Hair State</label>
                                <select id="hair-state" name="hair_state">
                                    <option value="styled">Styled</option>
                                    <option value="natural">Natural/Air-dried</option>
                                    <option value="wet">Wet</option>
                                    <option value="stretched">Stretched</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="photo-description">Description (Optional)</label>
                            <textarea id="photo-description" name="description" rows="3" 
                                placeholder="Add any notes about this photo..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="photo-date">Photo Date</label>
                            <input type="datetime-local" id="photo-date" name="photo_date" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="cancel-upload">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Upload & Analyze
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Measurement Tool Modal -->
        <div id="measurement-modal" class="modal" style="display: none;">
            <div class="modal-content measurement-modal-content">
                <div class="modal-header">
                    <h3>Add Measurements</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="measurement-workspace">
                        <div class="photo-canvas-container">
                            <canvas id="measurement-canvas"></canvas>
                            <div class="measurement-tools">
                                <button class="tool-btn active" data-tool="length">
                                    <i class="fas fa-ruler-vertical"></i> Length
                                </button>
                                <button class="tool-btn" data-tool="width">
                                    <i class="fas fa-ruler-horizontal"></i> Width
                                </button>
                                <button class="tool-btn" data-tool="volume">
                                    <i class="fas fa-expand"></i> Volume
                                </button>
                                <button class="tool-btn" data-tool="density">
                                    <i class="fas fa-th"></i> Density
                                </button>
                            </div>
                        </div>
                        
                        <div class="measurement-panel">
                            <h4>Measurements</h4>
                            <div class="measurements-list" id="measurements-list">
                                <!-- Measurements will be added here -->
                            </div>
                            
                            <div class="calibration-section">
                                <h5>Calibration</h5>
                                <div class="form-group">
                                    <label>Reference Object Size</label>
                                    <input type="number" id="reference-size" step="0.1" placeholder="e.g., 6.0">
                                    <select id="reference-unit">
                                        <option value="inches">inches</option>
                                        <option value="cm">cm</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancel-measurements">Cancel</button>
                        <button type="button" class="btn btn-primary" id="save-measurements">
                            <i class="fas fa-save"></i> Save Measurements
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .myavana-photo-comparison-container {
            max-width: 1200px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .comparison-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .toolbar-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .view-toggle {
            display: flex;
            background: white;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }
        
        .view-btn {
            padding: 8px 16px;
            border: none;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            border-right: 1px solid #e9ecef;
        }
        
        .view-btn:last-child {
            border-right: none;
        }
        
        .view-btn.active {
            background: #007cba;
            color: white;
        }
        
        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .photo-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .photo-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .photo-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .photo-card-content {
            padding: 15px;
        }
        
        .photo-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 12px;
            color: #6c757d;
        }
        
        .photo-description {
            font-size: 14px;
            color: #495057;
            margin-bottom: 10px;
        }
        
        .photo-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background: #007cba;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .comparison-workspace {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .comparison-photos {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 20px;
            align-items: center;
        }
        
        .photo-slot {
            aspect-ratio: 3/4;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        
        .photo-slot:hover {
            border-color: #007cba;
            background: #e7f3ff;
        }
        
        .photo-slot img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .slot-placeholder {
            text-align: center;
            color: #6c757d;
        }
        
        .slot-placeholder i {
            font-size: 48px;
            margin-bottom: 10px;
            display: block;
        }
        
        .comparison-controls {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .timeline-container {
            position: relative;
            margin-top: 20px;
        }
        
        .timeline-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .timeline-metrics {
            display: flex;
            gap: 20px;
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .measurement-modal-content {
            max-width: 900px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .close {
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .photo-preview {
            margin-top: 10px;
            text-align: center;
        }
        
        .photo-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 4px;
        }
        
        .measurement-workspace {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .photo-canvas-container {
            position: relative;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        #measurement-canvas {
            width: 100%;
            height: 400px;
            display: block;
        }
        
        .measurement-tools {
            position: absolute;
            top: 10px;
            left: 10px;
            display: flex;
            gap: 5px;
        }
        
        .tool-btn {
            padding: 8px 12px;
            background: rgba(255,255,255,0.9);
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .tool-btn.active {
            background: #007cba;
            color: white;
        }
        
        .measurement-panel {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
        }
        
        .measurements-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .measurement-item {
            background: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        @media (max-width: 768px) {
            .comparison-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .toolbar-section {
                justify-content: center;
            }
            
            .comparison-photos {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .comparison-controls {
                order: -1;
            }
            
            .photos-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .measurement-workspace {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            const PhotoComparison = {
                currentView: 'gallery',
                selectedPhotos: [],
                
                init: function() {
                    this.bindEvents();
                    this.loadPhotos();
                    this.setCurrentDateTime();
                },
                
                bindEvents: function() {
                    // View switching
                    $('.view-btn').on('click', function() {
                        $('.view-btn').removeClass('active');
                        $(this).addClass('active');
                        PhotoComparison.switchView($(this).data('view'));
                    });
                    
                    // Upload photo
                    $('#upload-photo-btn').on('click', function() {
                        $('#photo-upload-modal').show();
                    });
                    
                    // Close modals
                    $('.modal .close, #cancel-upload, #cancel-measurements').on('click', function() {
                        $('.modal').hide();
                    });
                    
                    // Photo file selection
                    $('#photo-file').on('change', function() {
                        PhotoComparison.previewPhoto(this);
                    });
                    
                    // Form submission
                    $('#photo-upload-form').on('submit', function(e) {
                        e.preventDefault();
                        PhotoComparison.uploadPhoto(this);
                    });
                    
                    // Photo selection for comparison
                    $(document).on('click', '.photo-card', function() {
                        if (PhotoComparison.currentView === 'comparison') {
                            PhotoComparison.selectPhotoForComparison($(this));
                        }
                    });
                    
                    // Comparison actions
                    $('#analyze-comparison').on('click', function() {
                        PhotoComparison.analyzeComparison();
                    });
                },
                
                setCurrentDateTime: function() {
                    const now = new Date();
                    const localDateTime = now.getFullYear() + '-' + 
                        String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                        String(now.getDate()).padStart(2, '0') + 'T' + 
                        String(now.getHours()).padStart(2, '0') + ':' + 
                        String(now.getMinutes()).padStart(2, '0');
                    $('#photo-date').val(localDateTime);
                },
                
                switchView: function(view) {
                    this.currentView = view;
                    $('.photo-view').hide();
                    $('#' + view + '-view').show();
                    
                    if (view === 'timeline') {
                        this.loadTimeline();
                    } else if (view === 'comparison') {
                        this.setupComparisonView();
                    }
                },
                
                loadPhotos: function(filters = {}) {
                    $.ajax({
                        url: myavana_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'get_comparison_photos',
                            nonce: myavana_ajax.nonce,
                            ...filters
                        },
                        success: function(response) {
                            if (response.success) {
                                PhotoComparison.renderPhotos(response.data);
                            }
                        }
                    });
                },
                
                renderPhotos: function(photos) {
                    const container = $('#photos-container');
                    container.empty();
                    
                    photos.forEach(photo => {
                        const photoCard = $(`
                            <div class="photo-card" data-photo-id="${photo.id}">
                                <img src="${photo.photo_url}" alt="${photo.description || 'Hair photo'}">
                                <div class="photo-card-content">
                                    <div class="photo-meta">
                                        <span class="photo-type">${photo.photo_type}</span>
                                        <span class="photo-angle">${photo.angle}</span>
                                        <span class="photo-date">${photo.formatted_date}</span>
                                    </div>
                                    ${photo.description ? `<div class="photo-description">${photo.description}</div>` : ''}
                                    <div class="photo-actions">
                                        <button class="btn btn-primary btn-sm analyze-btn" data-photo-id="${photo.id}">
                                            <i class="fas fa-search"></i> Analyze
                                        </button>
                                        <button class="btn btn-secondary btn-sm measure-btn" data-photo-id="${photo.id}">
                                            <i class="fas fa-ruler"></i> Measure
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `);
                        container.append(photoCard);
                    });
                },
                
                previewPhoto: function(input) {
                    if (input.files && input.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $('#photo-preview').html(`<img src="${e.target.result}" alt="Photo preview">`);
                        };
                        reader.readAsDataURL(input.files[0]);
                    }
                },
                
                uploadPhoto: function(form) {
                    const formData = new FormData(form);
                    formData.append('action', 'upload_comparison_photo');
                    formData.append('nonce', myavana_ajax.nonce);
                    
                    $.ajax({
                        url: myavana_ajax.ajax_url,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $('#photo-upload-modal').hide();
                                PhotoComparison.loadPhotos(); // Refresh photos
                                PhotoComparison.showNotification('Photo uploaded successfully!', 'success');
                            } else {
                                PhotoComparison.showNotification(response.data || 'Upload failed', 'error');
                            }
                        },
                        error: function() {
                            PhotoComparison.showNotification('Upload failed', 'error');
                        }
                    });
                },
                
                selectPhotoForComparison: function(photoCard) {
                    const photoId = photoCard.data('photo-id');
                    const photoUrl = photoCard.find('img').attr('src');
                    
                    if (this.selectedPhotos.length < 2) {
                        this.selectedPhotos.push({id: photoId, url: photoUrl});
                        
                        const slotIndex = this.selectedPhotos.length;
                        $(`#photo-slot-${slotIndex}`).html(`<img src="${photoUrl}" alt="Selected photo">`);
                        
                        photoCard.addClass('selected');
                    }
                },
                
                analyzeComparison: function() {
                    if (this.selectedPhotos.length !== 2) {
                        this.showNotification('Please select two photos for comparison', 'warning');
                        return;
                    }
                    
                    $.ajax({
                        url: myavana_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'analyze_hair_photo',
                            nonce: myavana_ajax.nonce,
                            photo1_id: this.selectedPhotos[0].id,
                            photo2_id: this.selectedPhotos[1].id,
                            analysis_type: 'comparison'
                        },
                        success: function(response) {
                            if (response.success) {
                                PhotoComparison.displayComparisonResults(response.data);
                            }
                        }
                    });
                },
                
                displayComparisonResults: function(results) {
                    const resultsContainer = $('#comparison-results');
                    resultsContainer.html(`
                        <h4>Analysis Results</h4>
                        <div class="analysis-metrics">
                            <div class="metric">
                                <label>Length Change:</label>
                                <span class="value ${results.length_change >= 0 ? 'positive' : 'negative'}">
                                    ${results.length_change > 0 ? '+' : ''}${results.length_change}" 
                                </span>
                            </div>
                            <div class="metric">
                                <label>Volume Change:</label>
                                <span class="value ${results.volume_change >= 0 ? 'positive' : 'negative'}">
                                    ${results.volume_change > 0 ? '+' : ''}${results.volume_change}%
                                </span>
                            </div>
                            <div class="metric">
                                <label>Health Score:</label>
                                <span class="value">${results.health_score}/10</span>
                            </div>
                            <div class="metric">
                                <label>Time Period:</label>
                                <span class="value">${results.time_period}</span>
                            </div>
                        </div>
                        <div class="analysis-notes">
                            <h5>AI Analysis</h5>
                            <p>${results.ai_notes}</p>
                        </div>
                        <div class="recommendations">
                            <h5>Recommendations</h5>
                            <ul>
                                ${results.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                            </ul>
                        </div>
                    `);
                    resultsContainer.show();
                },
                
                showNotification: function(message, type = 'info') {
                    const notification = $(`
                        <div class="notification notification-${type}">
                            <span>${message}</span>
                            <button class="close-notification">&times;</button>
                        </div>
                    `);
                    
                    $('body').append(notification);
                    
                    setTimeout(() => {
                        notification.fadeOut(() => notification.remove());
                    }, 5000);
                    
                    notification.find('.close-notification').on('click', function() {
                        notification.fadeOut(() => notification.remove());
                    });
                }
            };
            
            PhotoComparison.init();
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Upload and process a comparison photo
     */
    public function upload_comparison_photo() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_die('Security check failed');
        }
        
        if (empty($_FILES['photo'])) {
            wp_send_json_error('No photo uploaded');
        }
        
        // Handle file upload
        $upload_result = $this->handle_photo_upload($_FILES['photo']);
        
        if (!$upload_result['success']) {
            wp_send_json_error($upload_result['error']);
        }
        
        // Save photo data to database
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'myavana_hair_photos';
        
        $photo_data = array(
            'user_id' => $this->user_id,
            'photo_url' => $upload_result['url'],
            'photo_type' => sanitize_text_field($_POST['photo_type']),
            'angle' => sanitize_text_field($_POST['angle']),
            'lighting_conditions' => sanitize_text_field($_POST['lighting_conditions']),
            'hair_state' => sanitize_text_field($_POST['hair_state']),
            'description' => sanitize_textarea_field($_POST['description']),
            'photo_date' => sanitize_text_field($_POST['photo_date']),
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table_name, $photo_data);
        
        if ($result) {
            $photo_id = $wpdb->insert_id;
            
            // Trigger AI analysis
            $this->analyze_single_photo($photo_id, $upload_result['path']);
            
            wp_send_json_success(array(
                'message' => 'Photo uploaded successfully',
                'photo_id' => $photo_id
            ));
        } else {
            wp_send_json_error('Failed to save photo data');
        }
    }
    
    /**
     * Get comparison photos for a user
     */
    public function get_comparison_photos() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'myavana_hair_photos';
        
        $photos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY photo_date DESC",
            $this->user_id
        ));
        
        // Format photos for frontend
        foreach ($photos as &$photo) {
            $photo->formatted_date = human_time_diff(strtotime($photo->photo_date)) . ' ago';
            $photo->measurements = json_decode($photo->measurements, true);
            $photo->analysis_data = json_decode($photo->analysis_data, true);
        }
        
        wp_send_json_success($photos);
    }
    
    /**
     * Analyze hair photo using AI
     */
    public function analyze_hair_photo() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_die('Security check failed');
        }
        
        $analysis_type = sanitize_text_field($_POST['analysis_type']);
        
        if ($analysis_type === 'comparison') {
            $photo1_id = intval($_POST['photo1_id']);
            $photo2_id = intval($_POST['photo2_id']);
            
            $results = $this->compare_photos($photo1_id, $photo2_id);
        } else {
            $photo_id = intval($_POST['photo_id']);
            $results = $this->analyze_single_photo($photo_id);
        }
        
        wp_send_json_success($results);
    }
    
    private function analyze_single_photo($photo_id, $photo_path = null) {
        global $wpdb;
        
        $photos_table = $wpdb->prefix . 'myavana_hair_photos';
        $analysis_table = $wpdb->prefix . 'myavana_photo_analysis';
        
        if (!$photo_path) {
            $photo = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $photos_table WHERE id = %d",
                $photo_id
            ));
            
            if (!$photo) {
                return array('error' => 'Photo not found');
            }
            
            $photo_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $photo->photo_url);
        }
        
        // Simulate AI analysis (in real implementation, use actual AI service)
        $analysis_results = array(
            'hair_health_score' => rand(60, 95),
            'length_estimate' => rand(8, 24),
            'volume_score' => rand(50, 90),
            'density_level' => array('low', 'medium', 'high')[rand(0, 2)],
            'texture_analysis' => array(
                'curl_pattern' => array('straight', 'wavy', 'curly', 'coily')[rand(0, 3)],
                'thickness' => array('fine', 'medium', 'thick')[rand(0, 2)]
            ),
            'color_analysis' => array(
                'primary_color' => array('black', 'brown', 'blonde', 'red')[rand(0, 3)],
                'highlights' => rand(0, 1) ? true : false,
                'gray_percentage' => rand(0, 30)
            ),
            'condition_indicators' => array(
                'dryness_level' => rand(1, 5),
                'damage_signs' => array('split_ends', 'breakage', 'frizz')[rand(0, 2)],
                'shine_level' => rand(1, 5)
            ),
            'recommendations' => array(
                'Deep conditioning treatment recommended',
                'Regular trims needed for split ends',
                'Consider protein treatment for strength'
            )
        );
        
        // Save analysis to database
        $wpdb->insert(
            $analysis_table,
            array(
                'photo_id' => $photo_id,
                'user_id' => $this->user_id,
                'analysis_type' => 'single_photo',
                'analysis_results' => json_encode($analysis_results),
                'confidence_score' => 0.85,
                'ai_model_version' => '1.0.0',
                'created_at' => current_time('mysql')
            )
        );
        
        return $analysis_results;
    }
    
    private function compare_photos($photo1_id, $photo2_id) {
        global $wpdb;
        
        $photos_table = $wpdb->prefix . 'myavana_hair_photos';
        
        $photo1 = $wpdb->get_row($wpdb->prepare("SELECT * FROM $photos_table WHERE id = %d", $photo1_id));
        $photo2 = $wpdb->get_row($wpdb->prepare("SELECT * FROM $photos_table WHERE id = %d", $photo2_id));
        
        if (!$photo1 || !$photo2) {
            return array('error' => 'One or both photos not found');
        }
        
        // Calculate time difference
        $time_diff = strtotime($photo2->photo_date) - strtotime($photo1->photo_date);
        $days_diff = abs($time_diff) / (60 * 60 * 24);
        
        // Simulate comparison analysis
        $comparison_results = array(
            'length_change' => rand(-1, 3) + (rand(0, 9) / 10), // inches
            'volume_change' => rand(-10, 25), // percentage
            'health_score' => rand(65, 95) / 10,
            'time_period' => $this->format_time_period($days_diff),
            'ai_notes' => 'Hair shows positive growth and improved health. Length increased significantly with better moisture retention.',
            'recommendations' => array(
                'Continue current hair care routine',
                'Consider adding growth treatments',
                'Maintain consistent moisture levels'
            ),
            'metrics' => array(
                'growth_rate' => round(($comparison_results['length_change'] ?? 0) / ($days_diff / 30), 2), // inches per month
                'overall_improvement' => rand(60, 90)
            )
        );
        
        // Save comparison to database
        $comparisons_table = $wpdb->prefix . 'myavana_photo_comparisons';
        $wpdb->insert(
            $comparisons_table,
            array(
                'user_id' => $this->user_id,
                'title' => 'Auto-generated comparison',
                'before_photo_id' => min($photo1_id, $photo2_id),
                'after_photo_id' => max($photo1_id, $photo2_id),
                'time_difference' => intval($days_diff),
                'ai_analysis' => json_encode($comparison_results),
                'created_at' => current_time('mysql')
            )
        );
        
        return $comparison_results;
    }
    
    private function format_time_period($days) {
        if ($days < 7) {
            return intval($days) . ' days';
        } elseif ($days < 30) {
            return intval($days / 7) . ' weeks';
        } elseif ($days < 365) {
            return intval($days / 30) . ' months';
        } else {
            return intval($days / 365) . ' years';
        }
    }
    
    private function handle_photo_upload($file) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        // Check file type
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            return array('success' => false, 'error' => 'Invalid file type');
        }
        
        // Check file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            return array('success' => false, 'error' => 'File too large');
        }
        
        $upload_overrides = array('test_form' => false);
        $uploaded_file = wp_handle_upload($file, $upload_overrides);
        
        if (isset($uploaded_file['error'])) {
            return array('success' => false, 'error' => $uploaded_file['error']);
        }
        
        return array(
            'success' => true,
            'url' => $uploaded_file['url'],
            'path' => $uploaded_file['file']
        );
    }
}

// Initialize the photo comparison system
new Myavana_Photo_Comparison();