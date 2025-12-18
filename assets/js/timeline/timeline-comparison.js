/**
 * MYAVANA Hair Journey - Timeline Comparison Module
 *
 * Handles AI hair analysis comparison functionality for the timeline component.
 * Allows users to compare two analyses side-by-side with detailed metrics and insights.
 *
 * @package MyavanaHairJourney
 * @subpackage Timeline
 * @since 2.3.5
 */

(function(window) {
    'use strict';

    // Ensure MyavanaTimeline namespace exists
    if (typeof window.MyavanaTimeline === 'undefined') {
        window.MyavanaTimeline = {};
    }

    /**
     * Timeline Comparison Module
     * Manages the comparison of two hair analyses
     */
    const TimelineComparison = {

        /**
         * Storage for available analyses
         */
        availableAnalyses: [],

        /**
         * Initialize the comparison module
         */
        init: function() {
            console.log('[Timeline Comparison] Module initialized');
            this.bindEvents();
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            const self = this;

            // Generate comparison button
            jQuery(document).on('click', '#generateComparison', function() {
                self.generate();
            });

            // Close comparison modal
            jQuery(document).on('click', '#closeCompareModal', function() {
                self.close();
            });

            // Modal backdrop click
            jQuery(document).on('click', '#compareAnalysisModal', function(e) {
                if (e.target.id === 'compareAnalysisModal') {
                    self.close();
                }
            });
        },

        /**
         * Open compare analysis modal and populate dropdowns
         */
        open: function() {
            const modal = document.getElementById('compareAnalysisModal');
            if (!modal) {
                console.error('[Timeline Comparison] Compare modal not found');
                return;
            }

            // Gather all analyses from the sidebar
            const analyses = [];

            // Get from Splide slider
            const splideSlides = document.querySelectorAll('.analysis-slide');
            splideSlides.forEach((slide, index) => {
                const dateEl = slide.querySelector('.analysis-slide-date');
                const healthEl = slide.querySelector('.analysis-metric .metric-value');
                const date = dateEl ? dateEl.textContent.trim() : `Analysis ${index + 1}`;
                const health = healthEl ? healthEl.textContent.replace('%', '') : '--';

                analyses.push({
                    index: index,
                    date: date,
                    label: `${date} (Health: ${health}%)`,
                    element: slide
                });
            });

            // Populate dropdown menus
            const select1 = document.getElementById('compareAnalysis1');
            const select2 = document.getElementById('compareAnalysis2');

            if (!select1 || !select2) {
                console.error('[Timeline Comparison] Compare dropdowns not found');
                return;
            }

            // Clear existing options
            select1.innerHTML = '<option value="">Select an analysis...</option>';
            select2.innerHTML = '<option value="">Select an analysis...</option>';

            // Add analysis options
            analyses.forEach((analysis, idx) => {
                const option1 = document.createElement('option');
                option1.value = idx;
                option1.textContent = analysis.label;
                select1.appendChild(option1);

                const option2 = document.createElement('option');
                option2.value = idx;
                option2.textContent = analysis.label;
                select2.appendChild(option2);
            });

            // Store analyses data for later use
            this.availableAnalyses = analyses;

            // Show modal
            modal.classList.add('active');
            console.log('[Timeline Comparison] Modal opened with', analyses.length, 'analyses');
        },

        /**
         * Close compare analysis modal
         */
        close: function() {
            const modal = document.getElementById('compareAnalysisModal');
            if (modal) {
                modal.classList.remove('active');
            }

            // Reset comparison results
            const resultsDiv = document.getElementById('comparisonResults');
            if (resultsDiv) {
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
            }
        },

        /**
         * Generate comparison between two analyses
         */
        generate: function() {
            const select1 = document.getElementById('compareAnalysis1');
            const select2 = document.getElementById('compareAnalysis2');

            if (!select1 || !select2) return;

            const index1 = parseInt(select1.value);
            const index2 = parseInt(select2.value);

            if (isNaN(index1) || isNaN(index2)) {
                alert('Please select two analyses to compare');
                return;
            }

            if (index1 === index2) {
                alert('Please select two different analyses');
                return;
            }

            const analyses = this.availableAnalyses;
            const analysis1Element = analyses[index1]?.element;
            const analysis2Element = analyses[index2]?.element;

            if (!analysis1Element || !analysis2Element) {
                alert('Error loading analysis data');
                return;
            }

            // Extract data from both analyses
            const data1 = this.extractAnalysisData(analysis1Element, analyses[index1].date);
            const data2 = this.extractAnalysisData(analysis2Element, analyses[index2].date);

            // Generate comparison HTML
            this.displayComparison(data1, data2);
        },

        /**
         * Extract analysis data from slide element
         *
         * @param {HTMLElement} slideElement - The slide element containing analysis data
         * @param {string} date - The date of the analysis
         * @return {Object} Extracted analysis data
         */
        extractAnalysisData: function(slideElement, date) {
            const metrics = slideElement.querySelectorAll('.analysis-metric');
            const data = {
                date: date,
                health: '--',
                hydration: '--',
                elasticity: '--',
                type: '--',
                curlPattern: '--'
            };

            // Extract metrics
            metrics.forEach(metric => {
                const label = metric.querySelector('.metric-label')?.textContent.trim().toLowerCase();
                const value = metric.querySelector('.metric-value')?.textContent.trim().replace('%', '');

                if (label && value) {
                    if (label.includes('health')) data.health = value;
                    else if (label.includes('hydration')) data.hydration = value;
                    else if (label.includes('elasticity')) data.elasticity = value;
                }
            });

            // Extract hair type info
            const typeInfo = slideElement.querySelector('.hair-type-info h3');
            const typeDesc = slideElement.querySelector('.hair-type-info p');
            if (typeInfo) data.curlPattern = typeInfo.textContent.trim();
            if (typeDesc) data.type = typeDesc.textContent.trim();

            return data;
        },

        /**
         * Display comparison results
         *
         * @param {Object} data1 - First analysis data
         * @param {Object} data2 - Second analysis data
         */
        displayComparison: function(data1, data2) {
            const resultsDiv = document.getElementById('comparisonResults');
            if (!resultsDiv) return;

            // Calculate differences
            const healthDiff = this.calculateDiff(data1.health, data2.health);
            const hydrationDiff = this.calculateDiff(data1.hydration, data2.hydration);
            const elasticityDiff = this.calculateDiff(data1.elasticity, data2.elasticity);

            // Generate comparison HTML
            const html = `
                <h3 style="font-family: 'Archivo Black', sans-serif; color: var(--myavana-onyx); margin-bottom: 1.5rem; text-align: center;">
                    Comparison Results
                </h3>
                <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <div style="text-align: center;">
                        <h4 style="font-family: 'Archivo', sans-serif; font-weight: 600; color: var(--myavana-blueberry); margin-bottom: 1rem;">
                            ${data1.date}
                        </h4>
                        <div style="font-size: 0.875rem; color: var(--myavana-onyx);">
                            <div style="margin-bottom: 0.5rem;"><strong>${data1.curlPattern}</strong></div>
                            <div style="opacity: 0.7;">${data1.type}</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <svg viewBox="0 0 24 24" width="24" height="24" style="fill: var(--myavana-coral);">
                            <path d="M13.172 12l-4.95-4.95 1.414-1.414L16 12l-6.364 6.364-1.414-1.414z"/>
                        </svg>
                    </div>
                    <div style="text-align: center;">
                        <h4 style="font-family: 'Archivo', sans-serif; font-weight: 600; color: var(--myavana-blueberry); margin-bottom: 1rem;">
                            ${data2.date}
                        </h4>
                        <div style="font-size: 0.875rem; color: var(--myavana-onyx);">
                            <div style="margin-bottom: 0.5rem;"><strong>${data2.curlPattern}</strong></div>
                            <div style="opacity: 0.7;">${data2.type}</div>
                        </div>
                    </div>
                </div>

                <div style="background: var(--myavana-stone); border-radius: 12px; padding: 1.5rem;">
                    <h4 style="font-family: 'Archivo', sans-serif; font-weight: 600; color: var(--myavana-onyx); margin-bottom: 1rem;">
                        Metric Comparison
                    </h4>

                    ${this.generateMetricRow('Health Score', data1.health, data2.health, healthDiff)}
                    ${this.generateMetricRow('Hydration', data1.hydration, data2.hydration, hydrationDiff)}
                    ${this.generateMetricRow('Elasticity', data1.elasticity, data2.elasticity, elasticityDiff)}
                </div>

                <div style="margin-top: 1.5rem; padding: 1rem; background: ${healthDiff.isPositive ? 'rgba(231, 166, 144, 0.1)' : 'rgba(74, 77, 104, 0.1)'}; border-radius: 8px; border-left: 4px solid ${healthDiff.isPositive ? 'var(--myavana-coral)' : 'var(--myavana-blueberry)'};">
                    <p style="font-family: 'Archivo', sans-serif; color: var(--myavana-onyx); margin: 0;">
                        <strong>Overall Progress:</strong> ${this.generateInsight(healthDiff, hydrationDiff, elasticityDiff)}
                    </p>
                </div>
            `;

            resultsDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
        },

        /**
         * Calculate difference between two values
         *
         * @param {string|number} val1 - First value
         * @param {string|number} val2 - Second value
         * @return {Object} Difference data
         */
        calculateDiff: function(val1, val2) {
            const num1 = parseFloat(val1);
            const num2 = parseFloat(val2);

            if (isNaN(num1) || isNaN(num2)) {
                return { diff: 0, percent: 0, isPositive: false, hasData: false };
            }

            const diff = num2 - num1;
            const percent = num1 > 0 ? ((diff / num1) * 100).toFixed(1) : 0;

            return {
                diff: diff.toFixed(1),
                percent: percent,
                isPositive: diff > 0,
                hasData: true
            };
        },

        /**
         * Generate metric comparison row HTML
         *
         * @param {string} label - Metric label
         * @param {string|number} val1 - First value
         * @param {string|number} val2 - Second value
         * @param {Object} diffData - Difference data
         * @return {string} HTML string
         */
        generateMetricRow: function(label, val1, val2, diffData) {
            const arrow = diffData.isPositive ? '↑' : (diffData.diff < 0 ? '↓' : '→');
            const color = diffData.isPositive ? 'var(--myavana-coral)' : (diffData.diff < 0 ? 'var(--myavana-blueberry)' : '#888');

            return `
                <div style="display: grid; grid-template-columns: 150px 1fr 80px 1fr; gap: 1rem; align-items: center; padding: 1rem 0; border-bottom: 1px solid rgba(0,0,0,0.05);">
                    <div style="font-family: 'Archivo', sans-serif; font-weight: 600; color: var(--myavana-onyx);">
                        ${label}
                    </div>
                    <div style="text-align: center; font-size: 1.25rem; font-weight: 600; color: var(--myavana-blueberry);">
                        ${val1}${val1 !== '--' ? '%' : ''}
                    </div>
                    <div style="text-align: center; font-size: 1.5rem; color: ${color};">
                        ${arrow} ${diffData.hasData ? Math.abs(diffData.diff) + '%' : ''}
                    </div>
                    <div style="text-align: center; font-size: 1.25rem; font-weight: 600; color: var(--myavana-coral);">
                        ${val2}${val2 !== '--' ? '%' : ''}
                    </div>
                </div>
            `;
        },

        /**
         * Generate insight text based on differences
         *
         * @param {Object} healthDiff - Health difference data
         * @param {Object} hydrationDiff - Hydration difference data
         * @param {Object} elasticityDiff - Elasticity difference data
         * @return {string} Insight text
         */
        generateInsight: function(healthDiff, hydrationDiff, elasticityDiff) {
            if (!healthDiff.hasData) {
                return "Insufficient data for detailed comparison.";
            }

            const improvements = [];
            if (healthDiff.isPositive) improvements.push(`health improved by ${healthDiff.diff}%`);
            if (hydrationDiff.isPositive) improvements.push(`hydration improved by ${hydrationDiff.diff}%`);
            if (elasticityDiff.isPositive) improvements.push(`elasticity improved by ${elasticityDiff.diff}%`);

            if (improvements.length === 0) {
                return "Your metrics have remained stable or decreased. Consider adjusting your routine for better results.";
            } else if (improvements.length === 1) {
                return `Great progress! Your ${improvements[0]}.`;
            } else {
                return `Excellent progress! Your ${improvements.join(', ')}.`;
            }
        }
    };

    // Attach to MyavanaTimeline namespace
    window.MyavanaTimeline.Comparison = TimelineComparison;

    // Backward compatibility - expose functions to global scope
    window.openCompareModal = function() {
        TimelineComparison.open();
    };

    window.closeCompareModal = function() {
        TimelineComparison.close();
    };

    window.generateComparison = function() {
        TimelineComparison.generate();
    };

    window.extractAnalysisData = function(slideElement, date) {
        return TimelineComparison.extractAnalysisData(slideElement, date);
    };

    window.displayComparison = function(data1, data2) {
        return TimelineComparison.displayComparison(data1, data2);
    };

    window.calculateDiff = function(val1, val2) {
        return TimelineComparison.calculateDiff(val1, val2);
    };

    window.generateMetricRow = function(label, val1, val2, diffData) {
        return TimelineComparison.generateMetricRow(label, val1, val2, diffData);
    };

    window.generateInsight = function(healthDiff, hydrationDiff, elasticityDiff) {
        return TimelineComparison.generateInsight(healthDiff, hydrationDiff, elasticityDiff);
    };

    // Initialize on DOM ready
    jQuery(document).ready(function() {
        TimelineComparison.init();
    });

    console.log('[Timeline Comparison] Module loaded successfully');

})(window);
