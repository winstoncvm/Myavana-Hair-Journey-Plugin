/**
 * MYAVANA Gemini Live API Handler
 * Real-time audio-to-audio communication using Gemini Live API with WebSockets
 * Following MYAVANA brand guidelines and security best practices
 */

class MyavanaGeminiLiveAPI {
    constructor(options = {}) {
        this.isConnected = false;
        this.isRecording = false;
        this.websocket = null;
        this.mediaRecorder = null;
        this.audioContext = null;
        this.audioChunks = [];
        this.sessionId = null;
        this.ephemeralToken = null;
        this.tokenExpiryTime = null;
        this.resumptionHandle = null;

        // Audio configuration for Gemini Live API requirements
        this.audioConfig = {
            sampleRate: 16000,
            channelCount: 1,
            bitDepth: 16,
            mimeType: 'audio/pcm;rate=16000'
        };

        // Callbacks
        this.onConnected = options.onConnected || (() => {});
        this.onDisconnected = options.onDisconnected || (() => {});
        this.onAudioReceived = options.onAudioReceived || (() => {});
        this.onTextReceived = options.onTextReceived || (() => {});
        this.onError = options.onError || (() => {});
        this.onStatusChange = options.onStatusChange || (() => {});

        // Initialize audio processing
        this.initializeAudio();
    }

    async initializeAudio() {
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)({
                sampleRate: this.audioConfig.sampleRate
            });

            if (this.audioContext.state === 'suspended') {
                await this.audioContext.resume();
            }

            console.log('Audio context initialized:', this.audioContext.state);
        } catch (error) {
            console.error('Error initializing audio context:', error);
            this.onError('Failed to initialize audio system');
        }
    }

    async createEphemeralToken() {
        if (!myavanaGeminiChatbot) {
            throw new Error('MYAVANA chatbot configuration not found');
        }

        try {
            const formData = new FormData();
            formData.append('action', myavanaGeminiChatbot.live_session_action);
            formData.append('nonce', myavanaGeminiChatbot.nonce);
            formData.append('action_type', 'create_ephemeral_token');

            const response = await fetch(myavanaGeminiChatbot.ajax_url, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.ephemeralToken = result.data.token;
                this.tokenExpiryTime = new Date(result.data.expires_at).getTime();
                console.log('Ephemeral token created, expires:', result.data.expires_at);
                return this.ephemeralToken;
            } else {
                throw new Error(result.data?.message || 'Failed to create ephemeral token');
            }
        } catch (error) {
            console.error('Error creating ephemeral token:', error);
            throw error;
        }
    }

    async connectToLiveAPI() {
        if (this.isConnected) {
            console.log('Already connected to Gemini Live API');
            return;
        }

        try {
            this.onStatusChange('Connecting to MYAVANA AI...');

            // Create ephemeral token if needed
            if (!this.ephemeralToken || Date.now() > this.tokenExpiryTime - 60000) {
                await this.createEphemeralToken();
            }

            // WebSocket URL for Gemini Live API v1alpha
            const wsUrl = `wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1alpha.GenerativeService/BidiGenerateContent?access_token=${this.ephemeralToken}`;

            this.websocket = new WebSocket(wsUrl);

            this.websocket.onopen = () => {
                console.log('Connected to Gemini Live API');
                this.isConnected = true;
                this.onConnected();
                this.onStatusChange('Connected to MYAVANA AI');
                this.startSession();
            };

            this.websocket.onmessage = (event) => {
                this.handleWebSocketMessage(event.data);
            };

            this.websocket.onclose = (event) => {
                console.log('WebSocket closed:', event.code, event.reason);
                this.isConnected = false;
                this.onDisconnected();
                this.onStatusChange('Disconnected from MYAVANA AI');

                // Handle reconnection logic
                if (event.code !== 1000) { // Not a normal closure
                    this.handleReconnection();
                }
            };

            this.websocket.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.onError('Connection error occurred');
                this.onStatusChange('Connection error');
            };

        } catch (error) {
            console.error('Error connecting to Live API:', error);
            this.onError('Failed to connect to MYAVANA AI');
            this.onStatusChange('Connection failed');
            throw error;
        }
    }

    startSession() {
        if (!this.websocket || this.websocket.readyState !== WebSocket.OPEN) {
            console.error('WebSocket not ready for session start');
            return;
        }

        const setupMessage = {
            setup: {
                model: "models/gemini-2.0-flash-live-001",
                generationConfig: {
                    responseModalities: ["AUDIO"],
                    contextWindowCompression: {
                        slidingWindow: {}
                    }
                },
                sessionResumption: this.resumptionHandle ? { handle: this.resumptionHandle } : {},
                systemInstruction: {
                    parts: [{
                        text: "You are MYAVANA AI, a specialized hair care expert assistant. Provide personalized, professional hair care advice based on user questions. Be friendly, knowledgeable, and supportive. Focus on natural hair care, curl patterns, hair health, and product recommendations. Keep responses conversational, encouraging, and speak naturally as if you're a caring friend and expert. Use a warm, empathetic tone that makes users feel supported in their hair journey."
                    }]
                }
            }
        };

        this.sendMessage(setupMessage);
        console.log('Session started with configuration');
    }

    handleWebSocketMessage(data) {
        try {
            const message = JSON.parse(data);
            console.log('Received message:', message);

            if (message.serverContent) {
                this.handleServerContent(message.serverContent);
            }

            if (message.sessionResumptionUpdate) {
                this.handleSessionResumptionUpdate(message.sessionResumptionUpdate);
            }

            if (message.goAway) {
                this.handleGoAway(message.goAway);
            }

            if (message.data) {
                this.handleAudioData(message.data);
            }

        } catch (error) {
            console.error('Error parsing WebSocket message:', error);
        }
    }

    handleServerContent(content) {
        if (content.modelTurn && content.modelTurn.parts) {
            for (const part of content.modelTurn.parts) {
                if (part.text) {
                    this.onTextReceived(part.text);
                }
                if (part.inlineData && part.inlineData.mimeType.startsWith('audio/')) {
                    this.handleInlineAudio(part.inlineData);
                }
            }
        }

        if (content.turnComplete) {
            this.onStatusChange('Response complete');
        }

        if (content.generationComplete) {
            this.onStatusChange('Ready for input');
        }
    }

    handleSessionResumptionUpdate(update) {
        if (update.resumable && update.newHandle) {
            this.resumptionHandle = update.newHandle;
            console.log('Session resumption handle updated');
        }
    }

    handleGoAway(goAway) {
        console.log('Server will disconnect in:', goAway.timeLeft);
        this.onStatusChange(`Reconnecting in ${goAway.timeLeft}...`);

        // Prepare for reconnection
        setTimeout(() => {
            if (this.resumptionHandle) {
                this.reconnectWithResumption();
            }
        }, 1000);
    }

    async handleInlineAudio(inlineData) {
        try {
            const audioData = atob(inlineData.data);
            const audioBuffer = new ArrayBuffer(audioData.length);
            const audioView = new Uint8Array(audioBuffer);

            for (let i = 0; i < audioData.length; i++) {
                audioView[i] = audioData.charCodeAt(i);
            }

            // Convert to audio buffer for playback
            const audioContextBuffer = await this.audioContext.decodeAudioData(audioBuffer);
            this.playAudioBuffer(audioContextBuffer);

            this.onAudioReceived(audioBuffer);
        } catch (error) {
            console.error('Error handling inline audio:', error);
        }
    }

    handleAudioData(base64Data) {
        try {
            const audioData = atob(base64Data);
            const audioBuffer = new ArrayBuffer(audioData.length * 2); // 16-bit samples
            const audioView = new Int16Array(audioBuffer);

            // Convert bytes to 16-bit samples
            for (let i = 0; i < audioData.length; i += 2) {
                const sample = (audioData.charCodeAt(i + 1) << 8) | audioData.charCodeAt(i);
                audioView[i / 2] = sample;
            }

            this.playAudioData(audioView);
            this.onAudioReceived(audioBuffer);
        } catch (error) {
            console.error('Error handling audio data:', error);
        }
    }

    async playAudioBuffer(audioBuffer) {
        try {
            const source = this.audioContext.createBufferSource();
            source.buffer = audioBuffer;
            source.connect(this.audioContext.destination);
            source.start();
        } catch (error) {
            console.error('Error playing audio buffer:', error);
        }
    }

    playAudioData(audioData) {
        try {
            // Create audio buffer from PCM data
            const audioBuffer = this.audioContext.createBuffer(1, audioData.length, 24000); // Gemini outputs 24kHz
            const channelData = audioBuffer.getChannelData(0);

            // Convert Int16 to Float32 and copy to buffer
            for (let i = 0; i < audioData.length; i++) {
                channelData[i] = audioData[i] / 32768.0; // Convert to -1.0 to 1.0 range
            }

            const source = this.audioContext.createBufferSource();
            source.buffer = audioBuffer;
            source.connect(this.audioContext.destination);
            source.start();
        } catch (error) {
            console.error('Error playing audio data:', error);
        }
    }

    async startRecording() {
        if (this.isRecording) {
            console.log('Already recording');
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    sampleRate: this.audioConfig.sampleRate,
                    channelCount: this.audioConfig.channelCount,
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                }
            });

            this.mediaRecorder = new MediaRecorder(stream, {
                mimeType: 'audio/webm;codecs=opus'
            });

            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.processAudioChunk(event.data);
                }
            };

            this.mediaRecorder.start(250); // Send data every 250ms for real-time
            this.isRecording = true;
            this.onStatusChange('Recording...');

        } catch (error) {
            console.error('Error starting recording:', error);
            this.onError('Failed to access microphone');
        }
    }

    stopRecording() {
        if (!this.isRecording) return;

        if (this.mediaRecorder && this.mediaRecorder.state === 'recording') {
            this.mediaRecorder.stop();
            this.mediaRecorder.stream.getTracks().forEach(track => track.stop());
        }

        this.isRecording = false;
        this.onStatusChange('Stopped recording');
    }

    async processAudioChunk(blob) {
        try {
            // Convert WebM to PCM for Gemini Live API
            const arrayBuffer = await blob.arrayBuffer();
            const audioBuffer = await this.audioContext.decodeAudioData(arrayBuffer);

            // Resample to 16kHz if needed and convert to PCM
            const pcmData = this.convertToPCM16(audioBuffer);
            const base64Data = btoa(String.fromCharCode.apply(null, new Uint8Array(pcmData)));

            this.sendAudioToGemini(base64Data);
        } catch (error) {
            console.error('Error processing audio chunk:', error);
        }
    }

    convertToPCM16(audioBuffer) {
        const sampleRate = audioBuffer.sampleRate;
        const targetSampleRate = this.audioConfig.sampleRate;
        const channelData = audioBuffer.getChannelData(0);

        let samples;
        if (sampleRate !== targetSampleRate) {
            // Simple resampling
            const ratio = sampleRate / targetSampleRate;
            const newLength = Math.floor(channelData.length / ratio);
            samples = new Float32Array(newLength);

            for (let i = 0; i < newLength; i++) {
                const index = Math.floor(i * ratio);
                samples[i] = channelData[index];
            }
        } else {
            samples = channelData;
        }

        // Convert to 16-bit PCM
        const pcmData = new ArrayBuffer(samples.length * 2);
        const pcmView = new Int16Array(pcmData);

        for (let i = 0; i < samples.length; i++) {
            const sample = Math.max(-1, Math.min(1, samples[i]));
            pcmView[i] = Math.floor(sample * 32767);
        }

        return pcmData;
    }

    sendAudioToGemini(base64Data) {
        if (!this.isConnected || !this.websocket) return;

        const message = {
            realtimeInput: {
                mediaChunks: [{
                    data: base64Data,
                    mimeType: this.audioConfig.mimeType
                }]
            }
        };

        this.sendMessage(message);
    }

    sendTextToGemini(text) {
        if (!this.isConnected || !this.websocket) return;

        const message = {
            clientContent: {
                turns: [{
                    role: "user",
                    parts: [{
                        text: text
                    }]
                }]
            }
        };

        this.sendMessage(message);
    }

    sendMessage(message) {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify(message));
        } else {
            console.error('WebSocket not ready to send message');
        }
    }

    async handleReconnection() {
        console.log('Attempting to reconnect...');
        this.onStatusChange('Reconnecting...');

        try {
            await new Promise(resolve => setTimeout(resolve, 2000)); // Wait 2 seconds

            if (this.resumptionHandle) {
                await this.reconnectWithResumption();
            } else {
                await this.connectToLiveAPI();
            }
        } catch (error) {
            console.error('Reconnection failed:', error);
            this.onError('Reconnection failed');
        }
    }

    async reconnectWithResumption() {
        try {
            // Create new ephemeral token for reconnection
            await this.createEphemeralToken();
            await this.connectToLiveAPI();
        } catch (error) {
            console.error('Session resumption failed:', error);
            this.resumptionHandle = null;
            await this.connectToLiveAPI();
        }
    }

    disconnect() {
        this.onStatusChange('Disconnecting...');

        if (this.mediaRecorder && this.isRecording) {
            this.stopRecording();
        }

        if (this.websocket) {
            this.websocket.close(1000, 'User disconnected');
            this.websocket = null;
        }

        this.isConnected = false;
        this.sessionId = null;
        this.ephemeralToken = null;
        this.resumptionHandle = null;

        this.onStatusChange('Disconnected');
    }

    // Voice Activity Detection
    setupVoiceActivityDetection() {
        // This could be enhanced with a proper VAD library
        // For now, using a simple amplitude-based detection
        console.log('Voice Activity Detection setup - implement as needed');
    }

    // Utility methods
    isTokenValid() {
        return this.ephemeralToken && Date.now() < this.tokenExpiryTime - 60000;
    }

    getConnectionStatus() {
        return {
            connected: this.isConnected,
            recording: this.isRecording,
            tokenValid: this.isTokenValid(),
            sessionId: this.sessionId
        };
    }
}

// Export for use in other scripts
window.MyavanaGeminiLiveAPI = MyavanaGeminiLiveAPI;