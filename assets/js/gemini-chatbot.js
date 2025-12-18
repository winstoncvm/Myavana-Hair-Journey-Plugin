/**
 * MYAVANA Gemini Live Chatbot
 * Enhanced AI-powered hair care assistant with real-time voice and vision analysis
 */

class MyavanaGeminiChatbot {
    constructor() {
        this.isRecording = false;
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.currentMode = 'voice';
        this.sessionId = null;
        this.isAnalyzing = false;
        this.recognition = null;
        this.synthesis = null;
        this.currentStream = null;
        this.liveAPI = null;
        this.isLiveMode = false;
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeElements();
            this.setupEventListeners();
            this.initializeSpeechRecognition();
            this.initializeSpeechSynthesis();
            this.initializeLiveAPI();
            this.showStatus('MYAVANA AI Ready', 'success');
        });
    }

    initializeElements() {
        this.elements = {
            modeToggle: document.querySelector('.mode-toggle'),
            audioInput: document.getElementById('audio-command'),
            videoContainer: document.getElementById('the-video'),
            voiceContainer: document.querySelector('.myavana-voice-control-ai-card'),
            video: document.getElementById('myavana-video'),
            canvas: document.getElementById('myavana-canvas'),
            photoBtn: document.getElementById('newphoto'),
            downloadBtn: document.getElementById('download'),
            chatTranscript: document.getElementById('chat-transcript'),
            analysisContent: document.getElementById('hair-analysis-content'),
            volumeSlider: document.getElementById('volumeSlider'),
            muteButton: document.getElementById('muteButton'),
            voiceSelect: document.getElementById('voice')
        };

        // Add live session indicator
        if (this.elements.voiceContainer) {
            const indicator = document.createElement('div');
            indicator.className = 'myavana-live-session-indicator';
            indicator.textContent = 'LIVE';
            this.elements.voiceContainer.appendChild(indicator);
            this.elements.liveIndicator = indicator;
        }

        // Add live mode toggle
        const liveModeToggle = document.createElement('div');
        liveModeToggle.className = 'myavana-live-mode-toggle';
        liveModeToggle.innerHTML = `
            <label class="myavana-toggle-switch">
                <input type="checkbox" id="liveMode" />
                <span class="myavana-toggle-slider"></span>
                <span class="myavana-toggle-label">Live Audio Mode</span>
            </label>
        `;

        if (this.elements.voiceContainer) {
            this.elements.voiceContainer.appendChild(liveModeToggle);
            this.elements.liveModeToggle = liveModeToggle.querySelector('#liveMode');
        }
    }

    setupEventListeners() {
        // Mode toggle
        if (this.elements.modeToggle) {
            this.elements.modeToggle.addEventListener('click', () => this.toggleMode());
        }

        // Voice activation
        if (this.elements.audioInput) {
            this.elements.audioInput.addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.startVoiceSession();
                } else {
                    this.stopVoiceSession();
                }
            });
        }

        // Photo capture
        if (this.elements.photoBtn) {
            this.elements.photoBtn.addEventListener('click', () => this.capturePhoto());
        }

        // Download photo
        if (this.elements.downloadBtn) {
            this.elements.downloadBtn.addEventListener('click', () => this.downloadPhoto());
        }

        // Volume control
        if (this.elements.volumeSlider) {
            this.elements.volumeSlider.addEventListener('input', (e) => {
                this.setVolume(e.target.value);
            });
        }

        // Mute toggle
        if (this.elements.muteButton) {
            this.elements.muteButton.addEventListener('click', () => this.toggleMute());
        }

        // Voice selection
        if (this.elements.voiceSelect) {
            this.elements.voiceSelect.addEventListener('change', () => {
                this.updateVoiceSettings();
            });
        }

        // Live mode toggle
        if (this.elements.liveModeToggle) {
            this.elements.liveModeToggle.addEventListener('change', (e) => {
                this.toggleLiveMode(e.target.checked);
            });
        }
    }

    initializeSpeechRecognition() {
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            
            this.recognition.continuous = true;
            this.recognition.interimResults = true;
            this.recognition.lang = 'en-US';

            this.recognition.onstart = () => {
                console.log('Speech recognition started');
                this.showStatus('Listening...', 'info');
            };

            this.recognition.onresult = (event) => {
                let finalTranscript = '';
                let interimTranscript = '';

                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const transcript = event.results[i][0].transcript;
                    if (event.results[i].isFinal) {
                        finalTranscript += transcript;
                    } else {
                        interimTranscript += transcript;
                    }
                }

                if (finalTranscript) {
                    this.processSpeechInput(finalTranscript);
                }
            };

            this.recognition.onerror = (event) => {
                console.error('Speech recognition error:', event.error);
                this.showStatus('Speech recognition error', 'error');
            };

            this.recognition.onend = () => {
                console.log('Speech recognition ended');
                if (this.isRecording) {
                    // Restart if still in recording mode
                    setTimeout(() => this.recognition.start(), 100);
                }
            };
        }
    }

    initializeSpeechSynthesis() {
        if ('speechSynthesis' in window) {
            this.synthesis = window.speechSynthesis;
        }
    }

    initializeLiveAPI() {
        if (typeof MyavanaGeminiLiveAPI !== 'undefined') {
            this.liveAPI = new MyavanaGeminiLiveAPI({
                onConnected: () => {
                    this.showStatus('Connected to MYAVANA Live AI', 'success');
                    this.elements.liveIndicator?.classList.add('active');
                },
                onDisconnected: () => {
                    this.showStatus('Disconnected from Live AI', 'info');
                    this.elements.liveIndicator?.classList.remove('active');
                },
                onAudioReceived: (audioData) => {
                    // Audio is already played by the Live API
                    console.log('Audio received from Gemini Live');
                },
                onTextReceived: (text) => {
                    this.addChatMessage('assistant', text);
                },
                onError: (error) => {
                    this.showStatus(`Live AI Error: ${error}`, 'error');
                },
                onStatusChange: (status) => {
                    this.showStatus(status, 'info');
                }
            });
        } else {
            console.warn('MyavanaGeminiLiveAPI not available');
        }
    }

    async toggleLiveMode(enabled) {
        this.isLiveMode = enabled;

        if (enabled) {
            if (this.liveAPI) {
                try {
                    await this.liveAPI.connectToLiveAPI();
                    this.showStatus('Live mode enabled - Real-time audio active', 'success');

                    // Disable fallback speech recognition when live mode is active
                    if (this.recognition && this.isRecording) {
                        this.recognition.stop();
                    }
                } catch (error) {
                    console.error('Failed to enable live mode:', error);
                    this.showStatus('Failed to enable live mode', 'error');
                    this.elements.liveModeToggle.checked = false;
                    this.isLiveMode = false;
                }
            }
        } else {
            if (this.liveAPI) {
                this.liveAPI.disconnect();
                this.showStatus('Live mode disabled', 'info');
            }
        }
    }

    toggleMode() {
        this.currentMode = this.currentMode === 'voice' ? 'video' : 'voice';
        
        if (this.currentMode === 'video') {
            this.startVideoMode();
            this.elements.modeToggle.textContent = 'Switch to Voice Mode';
            this.elements.modeToggle.setAttribute('data-mode', 'video');
        } else {
            this.startVoiceMode();
            this.elements.modeToggle.textContent = 'Switch to Video Mode';
            this.elements.modeToggle.setAttribute('data-mode', 'voice');
        }
    }

    async startVoiceMode() {
        this.elements.voiceContainer?.classList.remove('hidden');
        this.elements.videoContainer?.classList.add('hidden');
        
        // Stop video stream if active
        if (this.currentStream) {
            this.currentStream.getTracks().forEach(track => track.stop());
            this.currentStream = null;
        }
        
        this.showStatus('Voice mode activated', 'success');
    }

    async startVideoMode() {
        this.elements.voiceContainer?.classList.add('hidden');
        this.elements.videoContainer?.classList.remove('hidden');

        try {
            this.currentStream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 640 }, 
                    height: { ideal: 480 },
                    facingMode: 'user'
                } 
            });
            
            if (this.elements.video) {
                this.elements.video.srcObject = this.currentStream;
                this.elements.video.play();
            }
            
            this.showStatus('Video mode activated', 'success');
        } catch (error) {
            console.error('Error accessing camera:', error);
            this.showStatus('Camera access denied', 'error');
            // Fall back to voice mode
            this.toggleMode();
        }
    }

    async startVoiceSession() {
        if (this.isRecording) return;

        try {
            this.isRecording = true;

            if (this.isLiveMode && this.liveAPI) {
                // Use Live API for real-time audio
                await this.liveAPI.startRecording();
                this.showStatus('Live voice session started', 'success');
            } else {
                // Fallback to speech recognition + text-to-speech
                await this.startGeminiLiveSession();

                if (this.recognition) {
                    this.recognition.start();
                }
                this.showStatus('Voice session started (fallback mode)', 'success');
            }
        } catch (error) {
            console.error('Error starting voice session:', error);
            this.showStatus('Failed to start voice session', 'error');
            this.stopVoiceSession();
        }
    }

    async stopVoiceSession() {
        if (!this.isRecording) return;

        this.isRecording = false;

        if (this.isLiveMode && this.liveAPI) {
            // Stop Live API recording
            this.liveAPI.stopRecording();
            this.showStatus('Live voice session ended', 'info');
        } else {
            // Stop speech recognition
            if (this.recognition) {
                this.recognition.stop();
            }

            // End fallback session
            await this.endGeminiLiveSession();
            this.showStatus('Voice session ended', 'info');
        }
    }

    async startGeminiLiveSession() {
        if (!myavanaGeminiChatbot) return;

        try {
            const formData = new FormData();
            formData.append('action', myavanaGeminiChatbot.live_session_action);
            formData.append('nonce', myavanaGeminiChatbot.nonce);
            formData.append('action_type', 'start_session');

            const response = await fetch(myavanaGeminiChatbot.ajax_url, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                this.sessionId = result.data.session_id;
                console.log('Gemini Live session started:', this.sessionId);
            } else {
                throw new Error(result.data?.message || 'Failed to start session');
            }
        } catch (error) {
            console.error('Error starting Gemini Live session:', error);
            throw error;
        }
    }

    async endGeminiLiveSession() {
        if (!myavanaGeminiChatbot || !this.sessionId) return;

        try {
            const formData = new FormData();
            formData.append('action', myavanaGeminiChatbot.live_session_action);
            formData.append('nonce', myavanaGeminiChatbot.nonce);
            formData.append('action_type', 'end_session');

            await fetch(myavanaGeminiChatbot.ajax_url, {
                method: 'POST',
                body: formData
            });

            this.sessionId = null;
            console.log('Gemini Live session ended');
        } catch (error) {
            console.error('Error ending Gemini Live session:', error);
        }
    }

    async processSpeechInput(transcript) {
        if (!transcript.trim()) return;

        this.addChatMessage('user', transcript);
        this.showStatus('Processing...', 'info');

        try {
            if (this.isLiveMode && this.liveAPI && this.liveAPI.isConnected) {
                // Send text to Live API
                this.liveAPI.sendTextToGemini(transcript);
                // Response will be handled by Live API callbacks
            } else {
                // Fallback to standard API
                const formData = new FormData();
                formData.append('action', myavanaGeminiChatbot.live_session_action);
                formData.append('nonce', myavanaGeminiChatbot.nonce);
                formData.append('action_type', 'send_audio');
                formData.append('text_input', transcript);

                const response = await fetch(myavanaGeminiChatbot.ajax_url, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    const aiResponse = result.data.response;
                    this.addChatMessage('assistant', aiResponse);
                    this.speakResponse(aiResponse);
                    this.showStatus('Response ready', 'success');
                } else {
                    throw new Error(result.data?.message || 'AI response error');
                }
            }
        } catch (error) {
            console.error('Error processing speech input:', error);
            this.showStatus('Processing failed', 'error');
        }
    }

    capturePhoto() {
        if (!this.elements.video || !this.elements.canvas) return;

        const canvas = this.elements.canvas;
        const video = this.elements.video;
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        
        // Enable download button
        if (this.elements.downloadBtn) {
            this.elements.downloadBtn.disabled = false;
        }
        
        // Analyze the photo
        this.analyzePhoto(canvas.toDataURL('image/jpeg', 0.8));
        
        this.showStatus('Photo captured and analyzing...', 'info');
    }

    async analyzePhoto(imageData) {
        if (!myavanaGeminiChatbot || this.isAnalyzing) return;

        this.isAnalyzing = true;
        const analysisContainer = this.elements.analysisContent;
        
        if (analysisContainer) {
            analysisContainer.classList.add('myavana-analyzing');
            analysisContainer.innerHTML = '<p>Analyzing your hair with MYAVANA AI...</p>';
        }

        try {
            const base64Data = imageData.split(',')[1];
            
            const formData = new FormData();
            formData.append('action', myavanaGeminiChatbot.vision_action);
            formData.append('nonce', myavanaGeminiChatbot.nonce);
            formData.append('image_data', base64Data);
            formData.append('prompt', 'Analyze this hair image for health, curl pattern, and care recommendations.');

            const response = await fetch(myavanaGeminiChatbot.ajax_url, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success && result.data.analysis) {
                this.displayAnalysis(result.data.analysis);
                this.showStatus('Analysis complete!', 'success');
            } else {
                throw new Error(result.data?.message || 'Analysis failed');
            }
        } catch (error) {
            console.error('Error analyzing photo:', error);
            this.showStatus('Analysis failed', 'error');
            
            if (analysisContainer) {
                analysisContainer.innerHTML = '<p>Sorry, analysis failed. Please try again.</p>';
            }
        } finally {
            this.isAnalyzing = false;
            if (analysisContainer) {
                analysisContainer.classList.remove('myavana-analyzing');
            }
        }
    }

    displayAnalysis(analysis) {
        const analysisContainer = this.elements.analysisContent;
        if (!analysisContainer) return;

        let analysisHTML = '<div class="myavana-analysis-results">';
        
        if (analysis.analysis_summary) {
            analysisHTML += `<p><strong>Analysis:</strong> ${analysis.analysis_summary}</p>`;
        }
        
        if (analysis.curl_pattern) {
            analysisHTML += `<p><strong>Curl Pattern:</strong> ${analysis.curl_pattern}</p>`;
        }
        
        if (analysis.recommendations) {
            analysisHTML += `<p><strong>Recommendations:</strong> ${analysis.recommendations}</p>`;
        }
        
        analysisHTML += '</div>';
        analysisContainer.innerHTML = analysisHTML;

        // Update metrics bars
        if (analysis.hydration_level) {
            this.updateMetricBar('hydration-level', analysis.hydration_level);
        }
        
        if (analysis.health_score) {
            this.updateMetricBar('health-score', analysis.health_score * 10); // Convert to percentage
        }

        // Add analysis to chat
        const summaryText = analysis.analysis_summary || 'Hair analysis complete!';
        this.addChatMessage('assistant', summaryText);
        this.speakResponse(summaryText);
    }

    updateMetricBar(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.style.width = `${Math.min(100, Math.max(0, value))}%`;
        }
    }

    downloadPhoto() {
        if (!this.elements.canvas) return;

        const canvas = this.elements.canvas;
        const link = document.createElement('a');
        link.download = `myavana-hair-analysis-${Date.now()}.jpg`;
        link.href = canvas.toDataURL('image/jpeg', 0.8);
        link.click();
        
        this.showStatus('Photo downloaded', 'success');
    }

    addChatMessage(sender, message) {
        const chatContainer = this.elements.chatTranscript;
        if (!chatContainer) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = `myavana-chat-message ${sender}`;
        
        const avatar = document.createElement('div');
        avatar.className = `myavana-chat-avatar ${sender}`;
        avatar.textContent = sender === 'user' ? 'U' : 'AI';
        
        const bubble = document.createElement('div');
        bubble.className = 'myavana-chat-bubble';
        bubble.textContent = message;
        
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(bubble);
        
        chatContainer.appendChild(messageDiv);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    speakResponse(text) {
        if (!this.synthesis || !text) return;

        // Cancel any ongoing speech
        this.synthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        const selectedVoice = this.elements.voiceSelect?.value || 'alloy';
        
        // Configure voice settings based on selection
        const voices = this.synthesis.getVoices();
        const voice = voices.find(v => v.name.toLowerCase().includes('female')) || voices[0];
        
        if (voice) {
            utterance.voice = voice;
        }
        
        utterance.rate = 0.9;
        utterance.pitch = 1.1;
        utterance.volume = parseFloat(this.elements.volumeSlider?.value || '1');

        utterance.onstart = () => {
            this.showStatus('AI speaking...', 'info');
        };

        utterance.onend = () => {
            this.showStatus('Ready for input', 'success');
        };

        utterance.onerror = (event) => {
            console.error('Speech synthesis error:', event.error);
        };

        this.synthesis.speak(utterance);
    }

    setVolume(value) {
        const volume = parseFloat(value);
        
        // Update speech synthesis volume
        if (this.synthesis) {
            this.synthesis.cancel(); // Stop current speech to apply new volume
        }
        
        // Update any audio elements
        const audioElements = document.querySelectorAll('audio');
        audioElements.forEach(audio => {
            audio.volume = volume;
        });
    }

    toggleMute() {
        const currentVolume = parseFloat(this.elements.volumeSlider?.value || '1');
        const newVolume = currentVolume > 0 ? 0 : 1;
        
        if (this.elements.volumeSlider) {
            this.elements.volumeSlider.value = newVolume;
            this.setVolume(newVolume);
        }
        
        if (this.elements.muteButton) {
            this.elements.muteButton.textContent = newVolume > 0 ? '🔇' : '🔊';
        }
    }

    updateVoiceSettings() {
        const selectedVoice = this.elements.voiceSelect?.value;
        console.log('Voice updated to:', selectedVoice);
        // Voice settings are applied during speech synthesis
    }

    showStatus(message, type = 'info') {
        // Remove existing status indicators
        const existingIndicators = document.querySelectorAll('.myavana-status-indicator');
        existingIndicators.forEach(indicator => indicator.remove());

        // Create new status indicator
        const indicator = document.createElement('div');
        indicator.className = `myavana-status-indicator ${type}`;
        indicator.textContent = message;
        
        document.body.appendChild(indicator);
        
        // Show indicator
        requestAnimationFrame(() => {
            indicator.classList.add('show');
        });
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            indicator.classList.remove('show');
            setTimeout(() => {
                if (indicator.parentNode) {
                    indicator.remove();
                }
            }, 300);
        }, 3000);
    }

    destroy() {
        // Cleanup when component is destroyed
        if (this.recognition) {
            this.recognition.stop();
        }

        if (this.synthesis) {
            this.synthesis.cancel();
        }

        if (this.currentStream) {
            this.currentStream.getTracks().forEach(track => track.stop());
        }

        if (this.liveAPI) {
            this.liveAPI.disconnect();
        }

        this.endGeminiLiveSession();
    }
}

// Initialize the chatbot
let myavanaGeminiChatbotInstance;

document.addEventListener('DOMContentLoaded', () => {
    myavanaGeminiChatbotInstance = new MyavanaGeminiChatbot();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (myavanaGeminiChatbotInstance) {
        myavanaGeminiChatbotInstance.destroy();
    }
});