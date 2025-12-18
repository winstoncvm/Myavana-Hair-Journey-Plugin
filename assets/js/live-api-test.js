/**
 * MYAVANA Gemini Live API Test & Optimization
 * Testing and performance monitoring utilities for the Live API implementation
 */

class MyavanaLiveAPITester {
    constructor() {
        this.testResults = {
            connection: null,
            audioLatency: null,
            ephemeralToken: null,
            webSocketPerformance: null,
            audioQuality: null
        };

        this.performanceMetrics = {
            connectionTime: 0,
            firstAudioLatency: 0,
            averageResponseTime: 0,
            totalMessages: 0,
            errors: []
        };

        this.audioTestBuffer = null;
        this.startTime = null;
    }

    // Test ephemeral token creation
    async testEphemeralTokenCreation() {
        console.log('🔑 Testing ephemeral token creation...');
        const startTime = Date.now();

        try {
            if (!myavanaGeminiChatbot) {
                throw new Error('MYAVANA chatbot configuration not found');
            }

            const formData = new FormData();
            formData.append('action', myavanaGeminiChatbot.live_session_action);
            formData.append('nonce', myavanaGeminiChatbot.nonce);
            formData.append('action_type', 'create_ephemeral_token');

            const response = await fetch(myavanaGeminiChatbot.ajax_url, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            const duration = Date.now() - startTime;

            if (result.success) {
                this.testResults.ephemeralToken = {
                    status: 'success',
                    duration: duration,
                    expiresAt: result.data.expires_at,
                    message: `Token created in ${duration}ms`
                };

                console.log('✅ Ephemeral token created successfully', {
                    duration,
                    expiresAt: result.data.expires_at
                });

                return result.data.token;
            } else {
                throw new Error(result.data?.message || 'Token creation failed');
            }

        } catch (error) {
            this.testResults.ephemeralToken = {
                status: 'error',
                duration: Date.now() - startTime,
                message: error.message
            };

            console.error('❌ Ephemeral token creation failed:', error);
            return null;
        }
    }

    // Test WebSocket connection
    async testWebSocketConnection(token) {
        console.log('🔗 Testing WebSocket connection...');
        const startTime = Date.now();

        return new Promise((resolve) => {
            try {
                const wsUrl = `wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1alpha.GenerativeService/BidiGenerateContent?access_token=${token}`;
                const ws = new WebSocket(wsUrl);
                let connectionEstablished = false;

                const timeout = setTimeout(() => {
                    if (!connectionEstablished) {
                        ws.close();
                        this.testResults.connection = {
                            status: 'timeout',
                            duration: 10000,
                            message: 'Connection timed out after 10 seconds'
                        };
                        console.error('❌ WebSocket connection timed out');
                        resolve(false);
                    }
                }, 10000);

                ws.onopen = () => {
                    connectionEstablished = true;
                    clearTimeout(timeout);

                    const duration = Date.now() - startTime;
                    this.performanceMetrics.connectionTime = duration;

                    this.testResults.connection = {
                        status: 'success',
                        duration: duration,
                        message: `Connected in ${duration}ms`
                    };

                    console.log('✅ WebSocket connected successfully', { duration });

                    // Send setup message to test session initialization
                    const setupMessage = {
                        setup: {
                            model: "models/gemini-2.0-flash-live-001",
                            generationConfig: {
                                responseModalities: ["AUDIO"]
                            }
                        }
                    };

                    ws.send(JSON.stringify(setupMessage));

                    setTimeout(() => {
                        ws.close();
                        resolve(true);
                    }, 2000);
                };

                ws.onerror = (error) => {
                    clearTimeout(timeout);
                    this.testResults.connection = {
                        status: 'error',
                        duration: Date.now() - startTime,
                        message: 'WebSocket connection error'
                    };
                    console.error('❌ WebSocket error:', error);
                    resolve(false);
                };

                ws.onclose = (event) => {
                    if (!connectionEstablished) {
                        clearTimeout(timeout);
                        this.testResults.connection = {
                            status: 'error',
                            duration: Date.now() - startTime,
                            message: `Connection closed: ${event.code} - ${event.reason}`
                        };
                        console.error('❌ WebSocket closed unexpectedly:', event.code, event.reason);
                        resolve(false);
                    }
                };

            } catch (error) {
                this.testResults.connection = {
                    status: 'error',
                    duration: Date.now() - startTime,
                    message: error.message
                };
                console.error('❌ WebSocket test failed:', error);
                resolve(false);
            }
        });
    }

    // Test audio context initialization
    async testAudioContext() {
        console.log('🎤 Testing audio context...');
        const startTime = Date.now();

        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)({
                sampleRate: 16000
            });

            if (audioContext.state === 'suspended') {
                await audioContext.resume();
            }

            // Test microphone access
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    sampleRate: 16000,
                    channelCount: 1,
                    echoCancellation: true,
                    noiseSuppression: true
                }
            });

            stream.getTracks().forEach(track => track.stop());

            const duration = Date.now() - startTime;

            this.testResults.audioQuality = {
                status: 'success',
                duration: duration,
                sampleRate: audioContext.sampleRate,
                state: audioContext.state,
                message: `Audio system ready in ${duration}ms`
            };

            console.log('✅ Audio context initialized successfully', {
                duration,
                sampleRate: audioContext.sampleRate,
                state: audioContext.state
            });

            audioContext.close();
            return true;

        } catch (error) {
            this.testResults.audioQuality = {
                status: 'error',
                duration: Date.now() - startTime,
                message: error.message
            };

            console.error('❌ Audio context test failed:', error);
            return false;
        }
    }

    // Test audio latency
    async testAudioLatency() {
        console.log('⏱️ Testing audio latency...');

        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.setValueAtTime(440, audioContext.currentTime);
            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);

            const startTime = audioContext.currentTime;
            oscillator.start(startTime);
            oscillator.stop(startTime + 0.1);

            // Estimate latency (simplified test)
            const estimatedLatency = audioContext.baseLatency + audioContext.outputLatency;

            this.testResults.audioLatency = {
                status: 'success',
                baseLatency: audioContext.baseLatency,
                outputLatency: audioContext.outputLatency,
                totalLatency: estimatedLatency,
                message: `Estimated audio latency: ${Math.round(estimatedLatency * 1000)}ms`
            };

            console.log('✅ Audio latency test completed', {
                baseLatency: audioContext.baseLatency,
                outputLatency: audioContext.outputLatency,
                totalLatency: estimatedLatency
            });

            audioContext.close();
            return estimatedLatency;

        } catch (error) {
            this.testResults.audioLatency = {
                status: 'error',
                message: error.message
            };
            console.error('❌ Audio latency test failed:', error);
            return null;
        }
    }

    // Run complete test suite
    async runCompleteTest() {
        console.log('🚀 Starting MYAVANA Live API complete test suite...\n');

        this.startTime = Date.now();
        const results = {
            overall: 'running',
            tests: {}
        };

        // Test 1: Ephemeral Token
        const token = await this.testEphemeralTokenCreation();
        results.tests.ephemeralToken = this.testResults.ephemeralToken;

        if (!token) {
            results.overall = 'failed';
            results.message = 'Cannot proceed without valid ephemeral token';
            return results;
        }

        // Test 2: WebSocket Connection
        const connectionSuccess = await this.testWebSocketConnection(token);
        results.tests.connection = this.testResults.connection;

        // Test 3: Audio Context
        const audioSuccess = await this.testAudioContext();
        results.tests.audioContext = this.testResults.audioQuality;

        // Test 4: Audio Latency
        const latency = await this.testAudioLatency();
        results.tests.audioLatency = this.testResults.audioLatency;

        // Overall result
        const totalDuration = Date.now() - this.startTime;
        const allTestsPassed = connectionSuccess && audioSuccess && latency !== null;

        results.overall = allTestsPassed ? 'success' : 'partial';
        results.totalDuration = totalDuration;
        results.summary = this.generateSummary(results);

        console.log('\n📊 Test Suite Complete!');
        console.log('Results:', results);

        // Display results in UI if available
        this.displayResults(results);

        return results;
    }

    // Generate human-readable summary
    generateSummary(results) {
        let summary = {
            status: results.overall,
            recommendations: [],
            issues: []
        };

        // Check token creation
        if (results.tests.ephemeralToken?.status === 'success') {
            if (results.tests.ephemeralToken.duration > 2000) {
                summary.recommendations.push('Token creation is slow - check network connection');
            }
        } else {
            summary.issues.push('Ephemeral token creation failed - check API key configuration');
        }

        // Check connection
        if (results.tests.connection?.status === 'success') {
            if (results.tests.connection.duration > 5000) {
                summary.recommendations.push('WebSocket connection is slow - check network latency');
            }
        } else {
            summary.issues.push('WebSocket connection failed - check network and firewall settings');
        }

        // Check audio
        if (results.tests.audioContext?.status !== 'success') {
            summary.issues.push('Audio system not available - check microphone permissions');
        }

        if (results.tests.audioLatency?.totalLatency > 0.1) {
            summary.recommendations.push('High audio latency detected - consider using headphones');
        }

        return summary;
    }

    // Display results in UI
    displayResults(results) {
        // Create or update test results display
        let resultsContainer = document.getElementById('myavana-test-results');

        if (!resultsContainer) {
            resultsContainer = document.createElement('div');
            resultsContainer.id = 'myavana-test-results';
            resultsContainer.className = 'myavana-test-results-container';
            document.body.appendChild(resultsContainer);
        }

        const statusIcon = results.overall === 'success' ? '✅' : results.overall === 'partial' ? '⚠️' : '❌';
        const statusClass = results.overall === 'success' ? 'success' : results.overall === 'partial' ? 'warning' : 'error';

        resultsContainer.innerHTML = `
            <div class="myavana-test-header">
                <h3>${statusIcon} Live API Test Results</h3>
                <button class="myavana-close-results" onclick="this.parentElement.parentElement.style.display='none'">×</button>
            </div>
            <div class="myavana-test-content">
                <div class="myavana-test-status ${statusClass}">
                    Overall Status: ${results.overall.toUpperCase()}
                    <small>(Completed in ${results.totalDuration}ms)</small>
                </div>

                <div class="myavana-test-details">
                    ${Object.entries(results.tests).map(([testName, result]) => `
                        <div class="myavana-test-item ${result.status}">
                            <strong>${testName}:</strong>
                            <span class="status">${result.status}</span>
                            ${result.duration ? `<small>(${result.duration}ms)</small>` : ''}
                            <div class="message">${result.message}</div>
                        </div>
                    `).join('')}
                </div>

                ${results.summary.issues.length > 0 ? `
                    <div class="myavana-test-issues">
                        <h4>🔴 Issues Found:</h4>
                        <ul>
                            ${results.summary.issues.map(issue => `<li>${issue}</li>`).join('')}
                        </ul>
                    </div>
                ` : ''}

                ${results.summary.recommendations.length > 0 ? `
                    <div class="myavana-test-recommendations">
                        <h4>💡 Recommendations:</h4>
                        <ul>
                            ${results.summary.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                        </ul>
                    </div>
                ` : ''}

                <div class="myavana-test-actions">
                    <button onclick="myavanaLiveAPITester.runCompleteTest()">🔄 Run Test Again</button>
                    <button onclick="console.log('Full Results:', ${JSON.stringify(results, null, 2)})">📋 Copy Full Results</button>
                </div>
            </div>
        `;

        // Style the results container
        if (!document.getElementById('myavana-test-styles')) {
            const styles = document.createElement('style');
            styles.id = 'myavana-test-styles';
            styles.textContent = `
                .myavana-test-results-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    width: 400px;
                    max-height: 80vh;
                    overflow-y: auto;
                    background: white;
                    border: 2px solid var(--myavana-coral, #e7a690);
                    border-radius: 12px;
                    box-shadow: 0 8px 24px rgba(34, 35, 35, 0.2);
                    z-index: 10000;
                    font-family: 'Archivo', sans-serif;
                    font-size: 13px;
                }

                .myavana-test-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 16px;
                    background: var(--myavana-coral, #e7a690);
                    color: white;
                    border-radius: 10px 10px 0 0;
                }

                .myavana-test-header h3 {
                    margin: 0;
                    font-size: 16px;
                    font-weight: 600;
                }

                .myavana-close-results {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 20px;
                    cursor: pointer;
                    padding: 0;
                    width: 24px;
                    height: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .myavana-test-content {
                    padding: 16px;
                }

                .myavana-test-status {
                    padding: 12px;
                    border-radius: 8px;
                    margin-bottom: 16px;
                    font-weight: 600;
                }

                .myavana-test-status.success {
                    background: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }

                .myavana-test-status.warning {
                    background: #fff3cd;
                    color: #856404;
                    border: 1px solid #ffeeba;
                }

                .myavana-test-status.error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }

                .myavana-test-item {
                    padding: 8px 12px;
                    margin: 8px 0;
                    border-radius: 6px;
                    border-left: 4px solid;
                }

                .myavana-test-item.success {
                    background: #f8fff9;
                    border-left-color: #28a745;
                }

                .myavana-test-item.error {
                    background: #fff8f8;
                    border-left-color: #dc3545;
                }

                .myavana-test-item .status {
                    text-transform: uppercase;
                    font-weight: 600;
                    font-size: 11px;
                }

                .myavana-test-item .message {
                    color: #666;
                    font-size: 12px;
                    margin-top: 4px;
                }

                .myavana-test-issues, .myavana-test-recommendations {
                    margin: 16px 0;
                    padding: 12px;
                    border-radius: 8px;
                }

                .myavana-test-issues {
                    background: #fff5f5;
                    border: 1px solid #fed7d7;
                }

                .myavana-test-recommendations {
                    background: #f7fafc;
                    border: 1px solid #e2e8f0;
                }

                .myavana-test-actions {
                    display: flex;
                    gap: 8px;
                    margin-top: 16px;
                }

                .myavana-test-actions button {
                    flex: 1;
                    padding: 8px 12px;
                    border: 1px solid var(--myavana-coral, #e7a690);
                    background: white;
                    color: var(--myavana-coral, #e7a690);
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 12px;
                    font-weight: 600;
                }

                .myavana-test-actions button:hover {
                    background: var(--myavana-coral, #e7a690);
                    color: white;
                }

                @media (max-width: 768px) {
                    .myavana-test-results-container {
                        position: fixed;
                        top: 10px;
                        left: 10px;
                        right: 10px;
                        width: auto;
                    }
                }
            `;
            document.head.appendChild(styles);
        }

        // Auto-hide after 30 seconds if successful
        if (results.overall === 'success') {
            setTimeout(() => {
                if (resultsContainer && resultsContainer.style.display !== 'none') {
                    resultsContainer.style.display = 'none';
                }
            }, 30000);
        }
    }

    // Monitor connection quality during live session
    monitorConnectionQuality(liveAPI) {
        if (!liveAPI) return;

        const qualityBars = document.querySelectorAll('.myavana-connection-bar');

        // Simulate connection quality monitoring
        setInterval(() => {
            const quality = this.getConnectionQuality(liveAPI);
            this.updateConnectionQualityDisplay(qualityBars, quality);
        }, 1000);
    }

    getConnectionQuality(liveAPI) {
        // Simple quality estimation based on connection state
        if (!liveAPI.isConnected) return 0;

        // In a real implementation, you would measure:
        // - WebSocket ping/pong times
        // - Audio buffer health
        // - Error rates
        // - Network conditions

        return Math.min(4, Math.floor(Math.random() * 4) + 1); // Placeholder
    }

    updateConnectionQualityDisplay(bars, quality) {
        bars.forEach((bar, index) => {
            if (index < quality) {
                bar.classList.add('active');
            } else {
                bar.classList.remove('active');
            }
        });
    }
}

// Initialize global tester instance
window.myavanaLiveAPITester = new MyavanaLiveAPITester();

// Auto-run test when Live API is initialized
document.addEventListener('DOMContentLoaded', () => {
    // Add test button to interface
    const testButton = document.createElement('button');
    testButton.id = 'myavana-test-button';
    testButton.textContent = '🧪 Test Live API';
    testButton.className = 'myavana-test-button';
    testButton.onclick = () => myavanaLiveAPITester.runCompleteTest();

    // Style the test button
    testButton.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
        background: var(--myavana-coral, #e7a690);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px 16px;
        font-family: 'Archivo', sans-serif;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(231, 166, 144, 0.3);
        transition: all 0.3s ease;
    `;

    testButton.addEventListener('mouseenter', () => {
        testButton.style.transform = 'translateY(-2px)';
        testButton.style.boxShadow = '0 6px 16px rgba(231, 166, 144, 0.4)';
    });

    testButton.addEventListener('mouseleave', () => {
        testButton.style.transform = 'translateY(0)';
        testButton.style.boxShadow = '0 4px 12px rgba(231, 166, 144, 0.3)';
    });

    document.body.appendChild(testButton);
});