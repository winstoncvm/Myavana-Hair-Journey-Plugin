jQuery(document).ready(function($) {
    if (typeof TourGuideClient !== 'undefined') {
        const tour = new TourGuideClient({
            steps: [
                {
                    selector: '.myavana-entry-form',
                    content: 'Welcome! Start by adding a hair journey entry here.'
                },
                {
                    selector: '.myavana-timeline',
                    content: 'View your hair journey timeline, powered by Myavana.'
                }
            ],
            completeOnFinish: true
        });

        // Trigger tour on first login (check via AJAX)
        $.ajax({
            url: myavana.ajax_url,
            type: 'POST',
            data: {
                action: 'check_first_login',
                nonce: myavana.nonce
            },
            success: function(response) {
                if (response.data.is_first_login) {
                    tour.start();
                }
            }
        });
    }
});
document.addEventListener("DOMContentLoaded", function() {
    const container = document.querySelector('.myavana-video-chatbot-container');
    const videoContainer = document.querySelector('.the-video');
    const video = document.getElementById('myavana-video');
    const canvas = document.getElementById('myavana-canvas');
    const msg = document.getElementById('msg');
    const newPhotoBtn = document.getElementById('newphoto');
    const downloadBtn = document.getElementById('download');
    const modeToggle = document.querySelector('.mode-toggle');
    const voiceGrid = document.querySelector('.myavana-voice-ai-grid-container');
    const audioCommand = document.getElementById('audio-command');
    const muteButton = document.getElementById('muteButton');
    const volumeSlider = document.getElementById('volumeSlider');
    const remoteAudio = document.getElementById('remoteAudio');
    const voiceSelect = document.getElementById('voice');
    const hairAnalysisContent = document.getElementById('hair-analysis-content');
    const logEl = document.getElementById('log');
    const inputContainerEl = document.getElementById('inputContainer');

    let audioStream = null;
    let videoStream = null;
    let isVideoMode = false;
    let isVideoActive = false;
    let hasAnalyzedHair = false;
    let isMuted = false;
    let pc = null;
    let audioTrack = null;
    let dc = null;
    let visionContext = null;
    let lastAnalysisTime = 0;
    let analysisInterval = null;
    let sessionId = generateUUID();
    const analysisCooldown = 120000;
    const data = typeof myavanaData !== 'undefined' ? myavanaData : {
        ajax_url: '',
        nonce: '',
        user_id: 0,
        openai_realtime_api: '',
        openai_api_key: '',
        xai_api_key: ''
    };
    const assistantResults = {};
    const userMessages = {};

    const model = "gpt-4o-mini-realtime-preview";
    const gptFunctions = [
        {
            type: "function",
            name: "end_session",
            description: "the user would like to stop interacting with the Agent",
            parameters: {}
        }
    ];

    const systemPrompt = `
        You are Mya, a professional, empathetic, and expert Myavana AI Haircare Assistant. Your role is to provide personalized, science-based haircare advice tailored to the user's hair characteristics, needs, and goals. Use the following guidelines:
        1. **Tone and Style**: Be friendly, supportive, and professional. Use clear, concise language and explain technical terms (e.g., porosity, curl pattern) when used. Show enthusiasm and empathy for the user's haircare journey.
        2. **Personalization**: Incorporate the user's hair analysis (e.g., type, curl pattern, hydration, health score) and environmental context from vision analysis to tailor responses. Subtly reference the user's mood or demeanor to build rapport, e.g., "You seem in a great mood today, let's make your hair shine even brighter!"
        3. **Haircare Expertise**: Offer detailed recommendations on hair health, styling techniques, product choices, and routines. Suggest Myavana products when appropriate, explaining their benefits for the user's specific hair needs.
        4. **Context Awareness**: Use vision analysis context to inform responses. For example, if the hair appears dry, recommend hydrating products; if the environment is humid, address frizz control.
        5. **Actionable Advice**: Provide step-by-step guidance and practical tips. Include precautions (e.g., avoiding heat damage for fragile hair) and explain the benefits of recommended actions.
        6. **Engagement**: Ask follow-up questions to clarify needs (e.g., "What’s your current haircare routine?" or "Do you prefer lightweight products?") to deepen personalization.
        7. **Cultural Sensitivity**: Be inclusive and respectful of diverse hair types, cultural practices, and styling preferences.
        Current vision analysis context:
        {VISION_CONTEXT}
        Start each session with a welcoming message inviting the user to share their haircare concerns or goals. If no specific question is asked, provide general advice based on the vision analysis or prompt the user to describe their needs.
    `;

    const defaultSessionInstructions = systemPrompt;
    const defaultStartInstructions = "Hello! I’m Mya, your Myavana AI Haircare Assistant. How can I assist you with your hair today?";
    const defaultTemperature = 0.8;

    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function initChatbot() {
        loadSettings();
        modeToggle.addEventListener('click', toggleMode);
        newPhotoBtn.addEventListener('click', takePhoto);
        downloadBtn.addEventListener('click', downloadPhoto);
        muteButton.addEventListener('click', toggleMute);
        volumeSlider.addEventListener('input', () => {
            remoteAudio.volume = parseFloat(volumeSlider.value);
            remoteAudio.muted = remoteAudio.volume === 0;
        });
        voiceSelect.addEventListener('change', autoSaveSettings);
        audioCommand.addEventListener('change', toggleSession);
        container.querySelector('.maximize-btn').addEventListener('click', toggleFullscreen);
        startVoiceMode();
    }

    async function toggleMode() {
        isVideoMode = !isVideoMode;
        if (isVideoMode) {
            modeToggle.dataset.mode = 'video';
            modeToggle.classList.add('video-mode');
            modeToggle.querySelector('span').textContent = 'Switch to Voice Mode';
            voiceGrid.classList.add('hidden');
            videoContainer.classList.remove('hidden');
            startVideo();
            if (dc?.readyState === 'open') {
            sendText('Switched to video mode. You can now capture hair images for analysis while continuing our conversation.');
            } else {
                await startSession(); // Start voice session if not already active
            }
        } else {
            modeToggle.dataset.mode = 'voice';
            modeToggle.classList.remove('video-mode');
            modeToggle.querySelector('span').textContent = 'Switch to Video Mode';
            voiceGrid.classList.remove('hidden');
            videoContainer.classList.add('hidden');
            stopVideo();
            if (dc?.readyState === 'open') {
                sendText('Switched to voice mode. Please continue the haircare consultation.');
            }
        }
    }

    async function startVideo() {
        try {
            videoStream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' },
                audio: false
            });
            video.srcObject = videoStream;
            video.play();
            isVideoActive = true;
            videoContainer.style.display = 'block';
            msg.style.display = 'none';
            appendOrUpdateLog('Camera activated. Analyzing your hair in the background.', 'system-message');
            if (dc?.readyState !== 'open') {
                await startSession();
            }
            if (!analysisInterval) {
                analysisInterval = setInterval(silentAnalyze, analysisCooldown);
                silentAnalyze();
            }
        } catch (err) {
            console.error('Error accessing camera:', err);
            appendOrUpdateLog('Could not access camera. Please enable camera permissions.', 'error-message');
            isVideoActive = false;
            msg.innerHTML = `${err.name}: ${err.message}`;
        }
    }

    function stopVideo() {
        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
            videoStream = null;
        }
        isVideoActive = false;
        video.srcObject = null;
        videoContainer.style.display = 'none';
        msg.style.display = 'block';
        if (analysisInterval) {
            clearInterval(analysisInterval);
            analysisInterval = null;
        }
    }

    async function silentAnalyze() {
        if (!isVideoActive || hasAnalyzedHair) return;
        const now = Date.now();
        if (now - lastAnalysisTime < analysisCooldown) return;
        lastAnalysisTime = now;

        try {
            const offscreenCanvas = document.createElement('canvas');
            offscreenCanvas.width = 640;
            offscreenCanvas.height = 480;
            const ctx = offscreenCanvas.getContext('2d');
            ctx.drawImage(video, 0, 0, offscreenCanvas.width, offscreenCanvas.height);
            const dataUrl = offscreenCanvas.toDataURL('image/jpeg', 0.8);
            const base64Image = dataUrl.replace(/^data:image\/jpeg;base64,/, '');

            video.style.display = 'block';
            canvas.style.display = 'none';

            const analysis = await analyzeImageWithAI(base64Image);
            console.log('Vision API response:', analysis);

            updateHairAnalysis(analysis);
            updateHairMetrics(analysis.hair_analysis);
            visionContext = analysis.full_context;
            hasAnalyzedHair = true;

            // Save screenshot and create automated entry
            await saveScreenshotAndEntry(base64Image, analysis);

            if (dc?.readyState === 'open') {
                const visionInstructions = `
                    Vision analysis: ${JSON.stringify(analysis, null, 2)}.
                    Instructions: Integrate these visual insights into the conversation naturally. For example, if the user seems in a good mood, say something like, "You're radiating positivity today, let's make your hair shine too!" If hair appears dry, suggest hydrating products casually. Keep the chat fun, helpful, and engaging without disrupting the current topic. Only reference visual details when relevant to haircare or to build rapport.
                `;
                sendText(visionInstructions);
                updateRealtimeSession();
            }
        } catch (error) {
            console.error('Error in silent analysis:', error);
            appendOrUpdateLog(`Background analysis failed: ${error.message}`, 'error-message');
        }
    }

    async function saveScreenshotAndEntry(imageData, analysis) {
        jQuery.ajax({
            url: data.ajax_url,
            type: 'POST',
            data: {
                action: 'myavana_create_auto_entry',
                nonce: data.nonce,
                image_data: imageData,
                analysis: JSON.stringify(analysis),
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    appendOrUpdateLog('Automated hair journey entry created.', 'system-message');
                } else {
                    appendOrUpdateLog('Failed to create automated entry: ' + response.data, 'error-message');
                }
            },
            error: function() {
                appendOrUpdateLog('Error creating automated entry.', 'error-message');
            }
        });
    }

    async function takePhoto() {
        if (!isVideoActive) {
            appendOrUpdateLog('Please enable camera to analyze your hair.', 'error-message');
            return;
        }
        if (hasAnalyzedHair) {
            appendOrUpdateLog('Hair analysis already completed for this session.', 'system-message');
            return;
        }
        await silentAnalyze();
    }

    function downloadPhoto() {
        canvas.toBlob(blob => {
            const link = document.createElement('a');
            link.download = 'hair_photo.jpg';
            link.href = URL.createObjectURL(blob);
            link.click();
        }, 'image/jpeg', 1);
    }

    async function analyzeImageWithAI(imageData) {
        const prompt = `
            Analyze the provided image for a comprehensive description to support a haircare consultation. Include:
            1. **Environment**: Describe the setting (e.g., lighting, background, indoor/outdoor). If unclear, note as "Not visible".
            2. **User Description**: Estimate visible characteristics (e.g., attire, posture). If not visible, note as "Not visible".
            3. **Mood and Demeanor**: Infer the user's mood or demeanor based on visible cues (e.g., facial expression, body language). If not visible, note as "Not visible".
            4. **Hair Analysis**:
            - Hair type (e.g., straight, wavy, curly, coily). If unclear, note as "Unclear".
            - Curl pattern (e.g., 2A, 3B, 4C). If unclear, note as "Unclear".
            - Length (e.g., short, medium, long). If unclear, note as "Unclear".
            - Texture and density. If unclear, note as "Unclear".
            - Hydration level (e.g., dry, normal, oily). Estimate as a percentage (0-100) or note as "Unclear".
            - Health score (0-100, based on shine, split ends, etc.). If unclear, note as "Unclear".
            - Hairstyle (e.g., updo, loose, braided). If unclear, note as "Unclear".
            - Visible damage or concerns (e.g., frizz, breakage). If none, note as "None observed".
            5. **Recommendations**: Suggest haircare products and routines based on the analysis. If analysis is unclear, provide general haircare tips.
            6. **Products**: List recommended products with name, id, and match percentage.
            7. **Summary**: Provide a concise summary of the hair analysis for user communication. If analysis is unclear, note limitations.
            8. **Full Context**: Combine all observations into a detailed narrative for use as context in a conversational AI.

            Return the response in JSON format with fields: environment, user_description, mood_demeanor, hair_analysis (with subfields: type, curl_pattern, length, texture, density, hydration, health_score, hairstyle, damage), recommendations (array), products (array with name, id, match percentage), summary, full_context.
        `;

        const formData = new FormData();
        formData.append('image_data', imageData);
        formData.append('prompt', prompt);
        formData.append('nonce', data.nonce);
        formData.append('action', 'myavana_vision_api');

        try {
            const response = await fetch(data.ajax_url, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            console.log('OpenAI Vision API response:', result);
            if (!result.success) {
                throw new Error(result.data.message || 'Vision API request failed');
            }
            return result.data.analysis;
        } catch (error) {
            console.error('Error analyzing image:', error);
            throw error;
        }
    }

    function updateHairAnalysis(analysis) {
        if (!analysis) {
            hairAnalysisContent.innerHTML = `<p>No analysis data available.</p>`;
            return;
        }

        let html = `
            <div class="analysis-result">
                <h4>Your Hair Profile</h4>
                <p>${analysis.summary || 'No summary available.'}</p>
                <div class="hair-details">
                    <div class="detail">
                        <span class="detail-label">Type:</span>
                        <span class="detail-value">${analysis.hair_analysis?.type || 'N/A'}</span>
                    </div>
                    <div class="detail">
                        <span class="detail-label">Curl Pattern:</span>
                        <span class="detail-value">${analysis.hair_analysis?.curl_pattern || 'N/A'}</span>
                    </div>
                    <div class="detail">
                        <span class="detail-label">Length:</span>
                        <span class="detail-value">${analysis.hair_analysis?.length || 'N/A'}</span>
                    </div>
                    <div class="detail">
                        <span class="detail-label">Texture:</span>
                        <span class="detail-value">${analysis.hair_analysis?.texture || 'N/A'}</span>
                    </div>
                    <div class="detail">
                        <span class="detail-label">Density:</span>
                        <span class="detail-value">${analysis.hair_analysis?.density || 'N/A'}</span>
                    </div>
                    <div class="detail">
                        <span class="detail-label">Hydration:</span>
                        <span class="detail-value">${analysis.hair_analysis?.hydration ? `${analysis.hair_analysis.hydration}%` : 'N/A'}</span>
                    </div>
                    <div class="detail">
                        <span class="detail-label">Health Score:</span>
                        <span class="detail-value">${analysis.hair_analysis?.health_score ? `${analysis.hair_analysis.health_score}%` : 'N/A'}</span>
                    </div>
                    <div class="detail">
                        <span class="detail-label">Hairstyle:</span>
                        <span class="detail-value">${analysis.hair_analysis?.hairstyle || 'N/A'}</span>
                    </div>
                    <div class="detail">
                        <span class="detail-label">Damage:</span>
                        <span class="detail-value">${analysis.hair_analysis?.damage || 'None observed'}</span>
                    </div>
                </div>
                <h4>Environment & Context</h4>
                <p><strong>Setting:</strong> ${analysis.environment || 'N/A'}</p>
                <p><strong>User Description:</strong> ${analysis.user_description || 'N/A'}</p>
                <p><strong>Mood:</strong> ${analysis.mood_demeanor || 'N/A'}</p>
                <h4>Recommended Products</h4>
                <div class="product-recommendations">
        `;
        if (Array.isArray(analysis.products) && analysis.products.length > 0) {
            analysis.products.forEach(product => {
                html += `
                    <div class="product">
                        <div class="product-match" style="width: ${product.match || 0}%"></div>
                        <span class="product-name">${product.name || 'Unnamed Product'}</span>
                        <span class="product-match-value">${product.match || 0}% match</span>
                    </div>
                `;
            });
        } else {
            html += `<p>No product recommendations available.</p>`;
        }
        html += `
                </div>
                <h4>Care Recommendations</h4>
                <ul class="care-tips">
        `;
        if (Array.isArray(analysis.recommendations) && analysis.recommendations.length > 0) {
            analysis.recommendations.forEach(tip => {
                html += `<li>${tip}</li>`;
            });
        } else {
            html += `<li>No care recommendations available.</li>`;
        }
        html += `</ul></div>`;
        hairAnalysisContent.innerHTML = html;
    }

    function updateHairMetrics(metrics) {
        const safePercentage = (value) => {
            const num = parseInt(value);
            return isNaN(num) || num < 0 ? 0 : num > 100 ? 100 : num;
        };
        document.getElementById('hydration-level').style.width = `${safePercentage(metrics?.hydration)}%`;
        document.getElementById('curl-pattern').style.width = `${safePercentage(metrics?.curl_pattern)}%`;
        document.getElementById('health-score').style.width = `${safePercentage(metrics?.health_score)}%`;
        document.querySelectorAll('.metric-fill').forEach(bar => {
            bar.style.transition = 'width 1s ease-in-out';
        });
    }

    function startVoiceMode() {
        voiceGrid.classList.remove('hidden');
        videoContainer.classList.add('hidden');
        muteButton.classList.remove('hidden');
        volumeSlider.classList.remove('hidden');
        appendOrUpdateLog('Hi! I’m Mya, your Myavana AI Hair Assistant. Switch to video mode to analyze your hair or ask me about your haircare needs!', 'agent-message');
    }

    function toggleMute() {
        isMuted = !isMuted;
        remoteAudio.muted = isMuted;
        if (audioTrack) audioTrack.enabled = !isMuted;
        muteButton.textContent = isMuted ? '🔇' : '🔊';
    }

    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            container.requestFullscreen().catch(err => {
                console.error(`Fullscreen error: ${err.message}`);
                appendOrUpdateLog('Could not enable fullscreen.', 'error-message');
            });
        } else {
            document.exitFullscreen();
        }
    }

    function toggleSessionButtons(isSessionActive) {
        audioCommand.checked = isSessionActive;
    }

    async function toggleSession() {
        if (audioCommand.checked) {
            if (dc?.readyState === 'open') {
                appendOrUpdateLog('Session already started.', 'system-message');
                return;
            }
            await startSession();
        } else {
            if (dc?.readyState !== 'open') {
                appendOrUpdateLog('No session to end.', 'system-message');
                return;
            }
            await endSession();
        }
    }

    function sessionStartMessages() {
        const sessionInstruct = localStorage.getItem("sessionInstructions") || defaultSessionInstructions.replace('{VISION_CONTEXT}', visionContext || 'No vision analysis available yet.');
        const startInstruct = localStorage.getItem("startInstructions") || defaultStartInstructions;
        const temperature = parseFloat(localStorage.getItem("temperature")) || defaultTemperature;

        const systemMessage = {
            type: "session.update",
            session: {
                instructions: sessionInstruct,
                voice: voiceSelect.value,
                tools: gptFunctions,
                tool_choice: "auto",
                input_audio_transcription: { model: "whisper-1" },
                temperature
            }
        };
        dc.send(JSON.stringify(systemMessage));

        const startMessage = {
            type: "response.create",
            response: {
                modalities: ["text", "audio"],
                instructions: startInstruct,
                max_output_tokens: 100
            }
        };
        dc.send(JSON.stringify(startMessage));
        appendOrUpdateLog("Session started.", "system-message");
    }

    async function startSession() {
        console.log("Starting session...");
        const apiKey = data.openai_api_key || "YOUR_OPENAI_API_KEY_HERE";
        if (!apiKey) {
            console.error("No OpenAI API Key provided");
            appendOrUpdateLog("Error: No OpenAI API Key provided.", "system-message");
            toggleSessionButtons(false);
            return;
        }

        try {
            audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            if (!audioStream) {
                console.error("Failed to get audio stream");
                appendOrUpdateLog("Mic access error. Check permissions.", "system-message");
                toggleSessionButtons(false);
                return;
            }
            [audioTrack] = audioStream.getAudioTracks();
            pc = new RTCPeerConnection();
            pc.ontrack = (e) => {
                remoteAudio.srcObject = e.streams[0];
                remoteAudio.play().catch(err => console.error("Audio playback error:", err));
            };
            pc.addTrack(audioTrack, audioStream);

            dc = pc.createDataChannel("oai");
            dc.addEventListener("open", () => {
                console.log("Data channel opened");
                sessionStartMessages();
            });
            dc.addEventListener("message", handleMessage);
            dc.addEventListener("error", (err) => {
                console.error("Data channel error:", err);
                appendOrUpdateLog("Error in voice session.", "error-message");
            });
            dc.addEventListener("close", () => {
                console.log("Data channel closed");
                appendOrUpdateLog("Voice session closed.", "system-message");
                toggleSessionButtons(false);
            });

            await pc.setLocalDescription();
            const baseUrl = "https://api.openai.com/v1/realtime";
            const response = await fetch(`${baseUrl}?model=${model}`, {
                method: "POST",
                body: pc.localDescription.sdp,
                headers: {
                    Authorization: `Bearer ${apiKey}`,
                    "Content-Type": "application/sdp"
                }
            });
            if (!response.ok) {
                const errorText = await response.text();
                console.error("Failed to fetch SDP answer:", errorText);
                appendOrUpdateLog("Error starting session: " + errorText, "error-message");
                toggleSessionButtons(false);
                return;
            }
            const answer = { type: "answer", sdp: await response.text() };
            await pc.setRemoteDescription(answer);

            await new Promise((resolve, reject) => {
                const timeout = setTimeout(() => reject(`Connection timeout. Current state: ${pc.connectionState}`), 10000);
                pc.addEventListener("connectionstatechange", () => {
                    if (pc.connectionState === "connected") {
                        clearTimeout(timeout);
                        console.log("Peer connection established!");
                        resolve();
                    }
                });
            });

            toggleSessionButtons(true);
        } catch (err) {
            console.error("Error starting session:", err);
            appendOrUpdateLog("Error starting session. Please try again.", "error-message");
            if (pc?.connectionState !== "closed") pc.close();
            toggleSessionButtons(false);
        }
    }

    async function endSession() {
        const instructions = "Give a quick good-bye. Sometimes remind the user to press the button to start a new session.";
        console.log("Ending session...");
        if (dc?.readyState === "open") {
            dc.addEventListener("message", (event) => {
                const message = JSON.parse(event.data);
                if (message.type === "response.done") {
                    pc.close();
                    console.log("Session ended.");
                    appendOrUpdateLog("Session ended.", "system-message");
                    toggleSessionButtons(false);
                }
            });
            const message = {
                type: "response.create",
                response: {
                    modalities: ["text", "audio"],
                    instructions,
                    max_output_tokens: 200
                }
            };
            dc.send(JSON.stringify(message));
        }
        if (audioTrack?.readyState !== "ended") audioTrack.stop();
        if (audioStream) {
            audioStream.getTracks().forEach(t => t.stop());
            audioStream = null;
        }
        if (videoStream) {
            videoStream.getTracks().forEach(t => t.stop());
            videoStream = null;
        }
        toggleSessionButtons(false);
        sessionId = generateUUID(); // Reset session ID
    }

    function sendText(text) {
        if (!text) return;
        if (dc && dc.readyState === "open") {
            const message = {
                type: "response.create",
                response: {
                    conversation: "none",
                    metadata: { topic: "model_see_user" },
                    modalities: ["text", "audio"],
                    instructions: text
                }
            };
            dc.send(JSON.stringify(message));
            appendOrUpdateLog("User (text): " + text, "user-message");
        } else {
            appendOrUpdateLog("Data channel not open. Please wait until session starts.", "system-message");
        }
    }

    function handleMessage(event) {
        const message = JSON.parse(event.data);
        const itemId = message.item_id || message.response_id || `msg_${Date.now()}`;
        console.log(`Received message: ${message.type}`, message);
        switch (message.type) {
            case "session.created":
                const expiresAt = new Date(message?.session?.expires_at * 1000);
                console.log(`Session created and will expire at ${expiresAt}`);
                toggleSessionButtons(true);
                break;
            case "input_audio_buffer.speech_started":
                userMessages[itemId] = { message: "" };
                appendOrUpdateLog(`User: ...`, "user-message", itemId);
                break;
            case "conversation.item.input_audio_transcription.completed":
                const content = message.transcript;
                if (content && userMessages[itemId]) {
                    userMessages[itemId].message = content;
                    appendOrUpdateLog(`User: ${content}`, "user-message", itemId);
                }
                break;
            case "response.audio_transcript.delta":
                if (itemId) {
                    assistantResults[itemId] = (assistantResults[itemId] || "") + message.delta;
                    appendOrUpdateLog(`Assistant (interim): ${assistantResults[itemId]}`, "interim-result", itemId);
                }
                break;
            case "response.audio_transcript.done":
                if (itemId) {
                    assistantResults[itemId] = message.transcript;
                    appendOrUpdateLog(`Assistant: ${message.transcript}`, "agent-message", itemId);
                }
                break;
            case "response.content_part.added":
                if (itemId) {
                    assistantResults[itemId] = assistantResults[itemId] || "";
                    appendOrUpdateLog(`Assistant (interim): ${assistantResults[itemId]}`, "interim-result", itemId);
                }
                break;
            case "response.content_part.done":
                if (itemId && message.content && message.content.text) {
                    assistantResults[itemId] = message.content.text;
                    appendOrUpdateLog(`Assistant: ${message.content.text}`, "agent-message", itemId);
                }
                break;
            case "response.output_item.added":
                if (itemId && message.item?.content?.[0]?.text) {
                    assistantResults[itemId] = message.item.content[0].text;
                    appendOrUpdateLog(`Assistant: ${message.item.content[0].text}`, "agent-message", itemId);
                }
                break;
            case "response.output_item.done":
                if (itemId && assistantResults[itemId]) {
                    appendOrUpdateLog(`Assistant: ${assistantResults[itemId]}`, "agent-message", itemId);
                }
                break;
            case "response.audio.delta":
                if (message.audio && message.audio.data) {
                    const audioData = atob(message.audio.data);
                    const arrayBuffer = new ArrayBuffer(audioData.length);
                    const view = new Uint8Array(arrayBuffer);
                    for (let i = 0; i < audioData.length; i++) {
                        view[i] = audioData.charCodeAt(i);
                    }
                    const blob = new Blob([arrayBuffer], { type: 'audio/mp3' });
                    const url = URL.createObjectURL(blob);
                    remoteAudio.src = url;
                    remoteAudio.play().catch(err => console.error("Audio playback error:", err));
                }
                break;
            case "response.function_call_arguments.done":
                const { name } = message;
                if (name === "end_session") {
                    console.log("Ending session based on user request");
                    endSession();
                }
                break;
            case "response.done":
                console.log("Response completed:", message);
                break;
            default:
                console.info(`Unhandled message from server: ${message.type}`, message);
                break;
        }
    }

    function updateRealtimeSession() {
        if (dc?.readyState === 'open') {
            dc.send(JSON.stringify({
                type: 'session.update',
                session: {
                    instructions: systemPrompt.replace('{VISION_CONTEXT}', visionContext || 'No vision analysis available yet.')
                }
            }));
        }
    }

    function appendOrUpdateLog(message, className = '', messageId = null) {
        let logEntry;
        const createAiMessage = content => `
            <div class="ai-message">
                <section class="myavana-ai-profile-icon mr-3">
                    <img src="https://6vt.d95.myftpupload.com/wp-content/uploads/2025/06/Signature-M-blueberry@2x.png" alt="myavna logo m"/>
                </section>
                <div class="ai-message-content">
                ${content}
                    <div class="sound-wave">
                        <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:svgjs="http://svgjs.com/svgjs" preserveAspectRatio="none" viewBox="0 0 1440 560">
                            <g mask='url("#SvgjsMask1099")' fill="none">
                                <rect fill="#ffffff"></rect>
                                <g transform="translate(0, 0)" stroke-linecap="round" stroke="url(#SvgjsLinearGradient1100)">
                                    <path d="M375 202.15 L375 357.85" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M398 155.33 L398 404.67" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M421 196.44 L421 363.56" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M444 259.91 L444 300.09" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M467 208.25 L467 351.75" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M490 184.8 L490 375.2" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M513 249.28 L513 310.72" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M536 220.75 L536 339.25" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M559 254.8 L559 305.2" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M582 186.77 L582 373.23" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M605 210.13 L605 349.87" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M628 234.45 L628 325.55" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M651 241.1 L651 318.89" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M674 202.95 L674 357.05" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M697 165.81 L697 394.19" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M720 224.51 L720 335.49" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M743 157.59 L743 402.4" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M766 164.98 L766 395.02" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M789 158.93 L789 401.07" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M812 224.24 L812 335.76" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M835 171.73 L835 388.27" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M858 264.89 L858 295.11" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M881 175.14 L881 384.86" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M904 248.17 L904 311.83" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M927 185.4 L927 374.6" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M950 234.82 L950 325.18" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M973 229.9 L973 330.1" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M996 194.25 L996 365.75" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M1019 162.47 L1019 397.53" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M1042 205.06 L1042 354.94" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M1065 240.52 L1065 319.48" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                </g>
                            </g>
                            <defs>
                                <mask id="SvgjsMask1099">
                                    <rect width="1440" height="560" fill="#ffffff"></rect>
                                </mask>
                                <linearGradient x1="360" y1="280" x2="1080" y2="280" gradientUnits="userSpaceOnUse" id="SvgjsLinearGradient1100">
                                    <stop stop-color="#4a4d68" offset="0"></stop>
                                    <stop stop-color="#fce5d7" offset="1"></stop>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                </div>
            </div>
        `;
        const createUserMessage = content => `
            <div class="usr-message">
                <div class="usr-message-content">${content}
                    <div class="sound-wave">
                        <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:svgjs="http://svgjs.com/svgjs" preserveAspectRatio="none" viewBox="0 0 1440 560">
                            <g mask='url("#SvgjsMask1099")' fill="none">
                                <rect fill="#f5f5f7"></rect>
                                <g transform="translate(0, 0)" stroke-linecap="round" stroke="url(#SvgjsLinearGradient1100)">
                                    <path d="M375 202.15 L375 357.85" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M398 155.33 L398 404.67" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M421 196.44 L421 363.56" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M444 259.91 L444 300.09" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M467 208.25 L467 351.75" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M490 184.8 L490 375.2" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M513 249.28 L513 310.72" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M536 220.75 L536 339.25" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M559 254.8 L559 305.2" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M582 186.77 L582 373.23" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M605 210.13 L605 349.87" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M628 234.45 L628 325.55" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M651 241.1 L651 318.89" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M674 202.95 L674 357.05" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M697 165.81 L697 394.19" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M720 224.51 L720 335.49" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M743 157.59 L743 402.4" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M766 164.98 L766 395.02" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M789 158.93 L789 401.07" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M812 224.24 L812 335.76" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M835 171.73 L835 388.27" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M858 264.89 L858 295.11" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M881 175.14 L881 384.86" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M904 248.17 L904 311.83" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M927 185.4 L927 374.6" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M950 234.82 L950 325.18" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M973 229.9 L973 330.1" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M996 194.25 L996 365.75" stroke-width="17.25" class="bar-scale2 stop-animation"></path>
                                    <path d="M1019 162.47 L1019 397.53" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                    <path d="M1042 205.06 L1042 354.94" stroke-width="17.25" class="bar-scale3 stop-animation"></path>
                                    <path d="M1065 240.52 L1065 319.48" stroke-width="17.25" class="bar-scale1 stop-animation"></path>
                                </g>
                            </g>
                            <defs>
                                <mask id="SvgjsMask1099">
                                    <rect width="1440" height="560" fill="#ffffff"></rect>
                                </mask>
                                <linearGradient x1="360" y1="280" x2="1080" y2="280" gradientUnits="userSpaceOnUse" id="SvgjsLinearGradient1100">
                                    <stop stop-color="#e7a690" offset="0"></stop>
                                    <stop stop-color="#fce5d7" offset="1"></stop>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                </div>
                <section class="myavana-users-profile-icon ml-3">
                    <svg viewBox="0 0 15 15" class="icon">
                        <path d="M7.5 0.875C5.49797 0.875 3.875 2.49797 3.875 4.5C3.875 6.15288 4.98124 7.54738 6.49373 7.98351C5.2997 8.12901 4.27557 8.55134 3.50407 9.31167C2.52216 10.2794 2.02502 11.72 2.02502 13.5999C2.02502 13.8623 2.23769 14.0749 2.50002 14.0749C2.76236 14.0749 2.97502 13.8623 2.97502 13.5999C2.97502 11.8799 3.42786 10.7206 4.17091 9.9883C4.91536 9.25463 6.02674 8.87499 7.49995 8.87499C8.97317 8.87499 10.0846 9.25463 10.8291 9.98831C11.5721 10.7206 12.025 11.8799 12.025 13.5999C12.025 13.8623 12.2376 14.0749 12.5 14.0749C12.7623 14.075 12.975 13.8623 12.975 13.6C12.975 11.72 12.4778 10.2794 11.4959 9.31166C10.7244 8.55135 9.70025 8.12903 8.50625 7.98352C10.0187 7.5474 11.125 6.15289 11.125 4.5C11.125 2.49797 9.50203 0.875 7.5 0.875ZM4.825 4.5C4.825 3.02264 6.02264 1.825 7.5 1.825C8.97736 1.825 10.175 3.02264 10.175 4.5C10.175 5.97736 8.97736 7.175 7.5 7.175C6.02264 7.175 4.825 5.97736 4.825 4.5Z"></path>
                    </svg>
                </section>
            </div>
        `;
        const cleanContent = msg => msg.replace(/^(Assistant|User|Session started|Error): ?/i, '').trim();

        if (messageId && document.getElementById(messageId)) {
            logEntry = document.getElementById(messageId);
            const contentDiv = logEntry.querySelector('.ai-message-content') || logEntry.querySelector('.usr-message-content');
            if (contentDiv) contentDiv.textContent = cleanContent(message);
        } else {
            const content = cleanContent(message);
            logEntry = document.createElement('div');
            logEntry.innerHTML = className === 'user-message' ? createUserMessage(content) : createAiMessage(content);
            logEntry.classList.add('log-message');
            if (className) logEntry.classList.add(className);
            if (messageId) logEntry.id = messageId;
            logEl.insertBefore(logEntry, inputContainerEl);
        }
        logEl.scrollTop = logEl.scrollHeight;

        // Save conversation to database
        jQuery.ajax({
            url: data.ajax_url,
            type: 'POST',
            data: {
                action: 'myavana_save_conversation',
                nonce: data.nonce,
                message: message,
                message_type: className.replace('-message', ''),
                session_id: sessionId
            },
            success: function(response) {
                if (!response.success) {
                    console.error('Failed to save conversation:', response.data);
                }
            },
            error: function() {
                console.error('Error saving conversation');
            }
        });
    }

    function loadSettings() {
        voiceSelect.value = localStorage.getItem('voice') || 'alloy';
    }

    function autoSaveSettings() {
        localStorage.setItem('voice', voiceSelect.value);
    }

    initChatbot();
});