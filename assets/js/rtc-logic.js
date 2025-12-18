
// 1) Core WebRTC & Realtime logic:
//     - Global variables
//     - fetchEphemeralKey
//     - startSession
//     - endSession
//     - handleMessage
//     ...
// Globals
let pc;                      // RTCPeerConnection
let track;                   // Local audio track
let dc;                      // Data channel
const assistantResults = {}; // Track interim/final transcripts
const userMessages = {};     // Track user messages per item ID

// Expose to console for debugging & fun
window.pc = pc;
window.track = track;
window.dc = dc;

// Model & function definitions
const model = "gpt-4o-mini-realtime-preview";
const gptFunctions = [
    {
        type: "function",
        name: "end_session",
        description: "the user would like to stop interacting with the Agent",
        parameters: {},
    },
];


/**
 * Send initial session instructions and start message.
 * This is called when the data channel is opened.
 * @returns {void}
 */
function sessionStartMessages() {
    const sessionInstruct = localStorage.getItem("sessionInstructions") || defaultSessionInstructions;
    const startInstruct = localStorage.getItem("startInstructions") || defaultStartInstructions;
    const temperature = parseFloat(localStorage.getItem("temperature")) || defaultTemperature;

    // Update the session
    const systemMessage = {
        type: "session.update",
        session: {
            instructions: sessionInstruct,
            voice: voiceEl.value,
            tools: gptFunctions,
            tool_choice: "auto",
            input_audio_transcription: {model: "whisper-1"},
            temperature: temperature,
        }
    };
    dc.send(JSON.stringify(systemMessage));

    // Start instructions
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

/**
 * Handle incoming DataChannel messages from the GPT server.
 */
function handleMessage(event) {
    const message = JSON.parse(event.data);
    const itemId = message.item_id;

    switch (message.type) {
        case "session.created":
            const expiresAt = new Date(message?.session?.expires_at * 1000);
            console.log(`Session created and will expire at ${expiresAt}`, message);
            toggleSessionButtons(true);
            break;

        case "input_audio_buffer.speech_started":
            userMessages[itemId] = {message: ""};
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
                assistantResults[itemId] = assistantResults[itemId] || "";
                assistantResults[itemId] += message.delta;
                appendOrUpdateLog(`Assistant (interim): ${assistantResults[itemId]}`, "interim-result", itemId);
            }
            break;

        case "response.audio_transcript.done":
            if (itemId) {
                assistantResults[itemId] = message.transcript;
                appendOrUpdateLog(`Assistant: ${message.transcript}`, "agent-message", itemId);
            }
            break;

        case "response.function_call_arguments.done":
            const {name} = message;
            if (name === "end_session") {
                console.log("Ending session based on user request");
                endSession();
            }
            break;

        default:
            // For debugging
            console.info(`Unhandled message from server: ${message.type}`, message);
            break;
    }
}

/**
 * Start a new WebRTC session with the OpenAI Realtime API.
 */
async function startSession() {
    console.log("start_session");
    startButtonEl.disabled = true;

    // API Key required
    const apiKey = keyEl.value;
    if (!apiKey || apiKey === "Your OPENAI_API_KEY from platform.openai.com") {
        console.error("No OpenAI API Key provided");
        appendOrUpdateLog("Error: No OpenAI API Key provided.", "system-message");
        toggleSessionButtons(false);
        return;
    }

    // Capture the local mic
    // technically optional for the API, but required in this example
    let stream;
    try {
        stream = await navigator.mediaDevices.getUserMedia({audio: true});
        if (!stream) {
            console.error("Failed to get local stream");
            return;
        }
        [track] = stream.getAudioTracks();
    } catch (err) {
        console.error("Error accessing mic:", err);
        appendOrUpdateLog("Mic access error. Check permissions.", "system-message");
        toggleSessionButtons(false);
        return;
    }

    // Start the WebRTC session
    try {
        // Create PeerConnection
        pc = new RTCPeerConnection();

        // On receiving remote track
        pc.ontrack = (e) => remoteAudioEl.srcObject = e.streams[0];
        // Add the local audio track and reference to its source stream
        pc.addTrack(track, stream);

        // Create data channel
        dc = pc.createDataChannel("oai");

        // Send session instructions upon opening the data channel
        dc.addEventListener("open", () => sessionStartMessages());

        // Handle incoming messages from the server
        dc.addEventListener("message", handleMessage);

        // implicit setLocalDescription style
        await pc.setLocalDescription();

        // Create answer
        const baseUrl = "https://api.openai.com/v1/realtime";
        const response = await fetch(`${baseUrl}?model=${model}`, {
            method: "POST",
            body: pc.localDescription.sdp,
            headers: {
                Authorization: `Bearer ${apiKey}`,
                "Content-Type": "application/sdp"
            },
        });
        if (!response.ok) {
            console.error("Failed to fetch SDP answer:", await response.text());
        }
        const answer = {type: "answer", sdp: await response.text()};
        await pc.setRemoteDescription(answer);

        // Wait for connection to be established before proceeding
        await new Promise((resolve, reject) => {
            const timeout = setTimeout(() => reject(`Connection timeout. Current state: ${pc.connectionState}`), 10_000);
            pc.addEventListener("connectionstatechange", () => {
                if (pc.connectionState === "connected") {
                    clearTimeout(timeout);
                    console.log("Peer connection established!");
                    resolve();
                }
            });
        });

        // toggleSessionButtons(true);
    } catch (err) {
        console.error("Error starting session:", err);
        appendOrUpdateLog("Error starting session. Please try again.", "system-message");
        if (pc?.connectionState !== "closed") {
            pc.close();
        }
        toggleSessionButtons(false);
    }
}

/**
 * End the current session, optionally sending instructions for a closing message.
 * @param {string} instructions - Instructions for the closing message.
 */
async function endSession(instructions = endInstructionsEl.value || defaultEndInstructions) {
    console.log("Ending session...");

    if (dc?.readyState === "open") {
        // Close after the final message
        dc.addEventListener("message", (event) => {
            const message = JSON.parse(event.data);
            if (message.type === "output_audio_buffer.stopped") {
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
                instructions: instructions,
                max_output_tokens: 200
            }
        };
        dc.send(JSON.stringify(message));
    }

    // Turn off mic
    if (track.status !== "ended") {
        track.stop();
    }

    endButtonEl.disabled = true;

}