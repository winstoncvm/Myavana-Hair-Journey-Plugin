// 2) UI logic & event wiring:
//         - references to DOM elements
//         - load/save settings
//         - button event handlers
//         ...
// Helper to get an element by ID
const $ = (id) => document.getElementById(id);

// DOM element references
const keyEl = $("openaiApiKey");
const sessionInstructionsEl = $("sessionInstructions");
const startInstructionsEl = $("startInstructions");
const endInstructionsEl = $("endInstructions");
const temperatureEl = $("temperature");
const voiceEl = $("voice");
const startButtonEl = $("startButton");
const endButtonEl = $("endButton");
const autoSaveStatusEl = $("autoSaveStatus");
const settingsButtonEl = $("settingsButton");
const settingsFormEl = $("settingsForm");
const remoteAudioEl = $('remoteAudio');
const muteButton = $('muteButton');
const volumeSlider = $('volumeSlider');
const logEl = $("log");
const inputContainerEl = $("inputContainer");
const textInputEl = $("textInput");
const sendButtonEl = $("sendButton");

// Default values
const defaultKey = "Your OPENAI_API_KEY from platform.openai.com";
const defaultSessionInstructions = "You are a friendly assistant";
const defaultStartInstructions = "Greet the user and ask how you can help";
const defaultEndInstructions = "Give a quick good-bye. Sometimes remind the user to press the button to start a new session.";
const defaultTemperature = 1.0;
const defaultVoice = "alloy";


// Volume controls
volumeSlider.addEventListener('input', (e) => remoteAudioEl.volume = e.target.value);

// Mute button
let isMuted = false;
muteButton.addEventListener('click', () => {
    isMuted = !isMuted;
    remoteAudioEl.muted = isMuted;
    muteButton.textContent = isMuted ? '🔇' : '🔊';
});


/**
 * Toggle the session control buttons based on the session state.
 * @param {boolean} isSessionActive - Whether a session is currently active.
 */
function toggleSessionButtons(isSessionActive) {
    [startButtonEl, endButtonEl].forEach(button => {
        button.hidden = (button === startButtonEl) ? isSessionActive : !isSessionActive;
        button.disabled = (button === startButtonEl) ? isSessionActive : false;
    });
}


/**
* Insert a new log entry or update an existing one (by messageId).
* @param {string} message - The message to log.
* @param {string} className - Optional CSS class for styling (e.g., 'user-message', 'agent-message', 'system-message', 'interim-result')
* @param {string} messageId - Optional ID to update an existing log entry.
*/
function appendOrUpdateLog(message, className = "", messageId = null) {
    let logEntry;

    // Helper to create AI message HTML
    const createAiMessage = (content) => {
        return `
            <div class="ai-message">
                <section class="myavana-ai-profile-icon mr-3">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14.187 8.096L15 5.25L15.813 8.096C16.0231 8.83114 16.4171 9.50062 16.9577 10.0413C17.4984 10.5819 18.1679 10.9759 18.903 11.186L21.75 12L18.904 12.813C18.1689 13.0231 17.4994 13.4171 16.9587 13.9577C16.4181 14.4984 16.0241 15.1679 15.814 15.903L15 18.75L14.187 15.904C13.9769 15.1689 13.5829 14.4994 13.0423 13.9587C12.5016 13.4181 11.8321 13.0241 11.097 12.814L8.25 12L11.096 11.187C11.8311 10.9769 12.5006 10.5829 13.0413 10.0423C13.5819 9.50162 13.9759 8.83214 14.186 8.097L14.187 8.096Z" fill="black" stroke="black" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M6 14.25L5.741 15.285C5.59267 15.8785 5.28579 16.4206 4.85319 16.8532C4.42059 17.2858 3.87853 17.5927 3.285 17.741L2.25 18L3.285 18.259C3.87853 18.4073 4.42059 18.7142 4.85319 19.1468C5.28579 19.5794 5.59267 20.1215 5.741 20.715L6 21.75L6.259 20.715C6.40725 20.1216 6.71398 19.5796 7.14639 19.147C7.5788 18.7144 8.12065 18.4075 8.714 18.259L9.75 18L8.714 17.741C8.12065 17.5925 7.5788 17.2856 7.14639 16.853C6.71398 16.4204 6.40725 15.8784 6.259 15.285L6 14.25Z" fill="black" stroke="black" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M6.5 4L6.303 4.5915C6.24777 4.75718 6.15472 4.90774 6.03123 5.03123C5.90774 5.15472 5.75718 5.24777 5.5915 5.303L5 5.5L5.5915 5.697C5.75718 5.75223 5.90774 5.84528 6.03123 5.96877C6.15472 6.09226 6.24777 6.24282 6.303 6.4085L6.5 7L6.697 6.4085C6.75223 6.24282 6.84528 6.09226 6.96877 5.96877C7.09226 5.84528 7.24282 5.75223 7.4085 5.697L8 5.5L7.4085 5.303C7.24282 5.24777 7.09226 5.15472 6.96877 5.03123C6.84528 4.90774 6.75223 4.75718 6.697 4.5915L6.5 4Z" fill="black" stroke="black" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </section>
                <div class="ai-message-content">${content}</div>
            </div>
        `;
    };

    // Helper to create user message HTML
    const createUserMessage = (content) => {
        return `
            <div class="usr-message">
                <div class="usr-message-content">${content}</div>
                <section class="myavana-users-profile-icon ml-3">
                    <svg viewBox="0 0 15 15" class="icon">
                        <path d="M7.5 0.875C5.49797 0.875 3.875 2.49797 3.875 4.5C3.875 6.15288 4.98124 7.54738 6.49373 7.98351C5.2997 8.12901 4.27557 8.55134 3.50407 9.31167C2.52216 10.2794 2.02502 11.72 2.02502 13.5999C2.02502 13.8623 2.23769 14.0749 2.50002 14.0749C2.76236 14.0749 2.97502 13.8623 2.97502 13.5999C2.97502 11.8799 3.42786 10.7206 4.17091 9.9883C4.91536 9.25463 6.02674 8.87499 7.49995 8.87499C8.97317 8.87499 10.0846 9.25463 10.8291 9.98831C11.5721 10.7206 12.025 11.8799 12.025 13.5999C12.025 13.8623 12.2376 14.0749 12.5 14.0749C12.7623 14.075 12.975 13.8623 12.975 13.6C12.975 11.72 12.4778 10.2794 11.4959 9.31166C10.7244 8.55135 9.70025 8.12903 8.50625 7.98352C10.0187 7.5474 11.125 6.15289 11.125 4.5C11.125 2.49797 9.50203 0.875 7.5 0.875ZM4.825 4.5C4.825 3.02264 6.02264 1.825 7.5 1.825C8.97736 1.825 10.175 3.02264 10.175 4.5C10.175 5.97736 8.97736 7.175 7.5 7.175C6.02264 7.175 4.825 5.97736 4.825 4.5Z"></path>
                    </svg>
                </section>
            </div>
        `;
    };

    // Clean message content (remove prefixes like "Assistant: ", "User: ")
    const cleanContent = (msg) => {
        return msg.replace(/^(Assistant|User|Session started|Error): ?/i, "").trim();
    };

    if (messageId && $(messageId)) {
        // Update existing message
        logEntry = $(messageId);
        const contentDiv = logEntry.querySelector('.ai-message-content') || logEntry.querySelector('.usr-message-content');
        if (contentDiv) {
            contentDiv.textContent = cleanContent(message);
        }
    } else {
        // Create new message
        const content = cleanContent(message);
        if (className === 'user-message') {
            logEntry = document.createElement('div');
            logEntry.innerHTML = createUserMessage(content);
        } else {
            // Handle agent-message, system-message, interim-result as AI messages
            logEntry = document.createElement('div');
            logEntry.innerHTML = createAiMessage(content);
        }
        logEntry.classList.add('log-message');
        if (className) logEntry.classList.add(className);
        if (messageId) logEntry.id = messageId;
        logEl.insertBefore(logEntry, inputContainerEl);
    }
    logEl.scrollTop = logEl.scrollHeight;
}

/**
 * Load settings from localStorage into UI fields.
 * If not set, use default values.
 * @returns {void}
 */
function loadSettings() {
    keyEl.value = localStorage.getItem("openaiApiKey") || defaultKey;
    sessionInstructionsEl.value = localStorage.getItem("sessionInstructions") || defaultSessionInstructions;
    startInstructionsEl.value = localStorage.getItem("startInstructions") || defaultStartInstructions;
    endInstructionsEl.value = localStorage.getItem("endInstructions") || defaultEndInstructions;
    temperatureEl.value = localStorage.getItem("temperature") || defaultTemperature;
    voiceEl.value = localStorage.getItem("voice") || defaultVoice;
}

/**
 * Autosave changed settings to localStorage (debounced by 2s).
 * @returns {void}
 */
function autoSaveSettings() {
    const settings = {
        openaiApiKey: keyEl.value,
        sessionInstructions: sessionInstructionsEl.value,
        startInstructions: startInstructionsEl.value,
        temperature: temperatureEl.value,
        voice: voiceEl.value
    };
    for (const [k, v] of Object.entries(settings)) {
        localStorage.setItem(k, v);
    }
    sendNewSettings();
    autoSaveStatusEl.textContent =
        "Settings autosaved at " + new Date().toLocaleTimeString();
}

/**
 * Reset settings and re-save them to localStorage.
 * @returns {void}
 */
function resetSettings() {
    const settings = {
        sessionInstructions: sessionInstructionsEl.value,
        startInstructions: startInstructionsEl.value,
        temperature: temperatureEl.value,
        voice: voiceEl.value
    };
    for (const [k, v] of Object.entries(settings)) {
        localStorage.setItem(k, v);
    }
    loadSettings();
    sendNewSettings();
    console.log("Settings reset!");
}

/**
 * Send updated session instructions to the GPT server (if data channel is open).
 * @returns {void}
 */
function sendNewSettings() {
    if (dc && dc.readyState === "open") {
        const message = {
            type: "session.update",
            session: {
                instructions: sessionInstructionsEl.value,
                tools: gptFunctions,
                tool_choice: "auto",
                temperature: parseFloat(temperatureEl.value),
                // voice: voiceEl.value  // can't change voice mid-session
            }
        };
        dc.send(JSON.stringify(message));
        console.log("Sent updated session settings:", message);
    } else {
        console.log("Data channel not open. Please wait until session starts.");
    }
}

// Debounce the settings autosave
[keyEl, sessionInstructionsEl, startInstructionsEl, endInstructionsEl, temperatureEl, voiceEl]
    .forEach((input) => {
        input.addEventListener("change", () => {
            clearTimeout(window.autoSaveTimeout);
            window.autoSaveTimeout = setTimeout(autoSaveSettings, 2000);
        });
    });

/**
 * Send the user's typed message to the GPT server over the data channel.
 * @returns {void}
 */
function sendText() {
    const text = textInputEl.value.trim();
    if (!text) return;
    if (dc && dc.readyState === "open") {
        const message = {
            type: "response.create",
            response: {
                modalities: ["text", "audio"],
                instructions: text,
                temperature: parseFloat(temperatureEl.value),
                max_output_tokens: 500
            }
        };
        dc.send(JSON.stringify(message));
        appendOrUpdateLog("User (text): " + text, "user-message");
        textInputEl.value = "";
    } else {
        appendOrUpdateLog("Data channel not open. Please wait until session starts.", "system-message");
    }
}

// Button event handlers for starting / ending sessions
startButtonEl.addEventListener("click", async () => {
    if (pc?.connectionState === "connected") {
        console.log("Session already started");
        appendOrUpdateLog("Session already started.", "system-message");
        return;
    }
    toggleSessionButtons(true);
    await startSession();
});

// End session button - cancel any in-progress response and close the session
endButtonEl.addEventListener("click", async () => {
    if (pc?.connectionState === "closed") {
        console.log(`No session to end. Connection state: ${pc.connectionState}`);
        appendOrUpdateLog("No session to end.", "system-message");
        return;
    }
    toggleSessionButtons(false);
    // cancel any in-progress response - not needed for speech input due to turn detection
    dc.send(JSON.stringify({type: "response.cancel"}));
    await endSession();
});

// Show/hide settings form
settingsButtonEl.addEventListener("click", () => {
    settingsFormEl.style.display =
        (settingsFormEl.style.display === "none") ? "block" : "none";
});

// Send on click or Enter press
sendButtonEl.addEventListener("click", sendText);
textInputEl.addEventListener("keydown", (ev) => {
    if (ev.key === "Enter") {
        sendText();
    }
});

loadSettings();