# MYAVANA Gemini Live API Implementation

## 🎯 Overview

This implementation adds **real-time audio-to-audio communication** using Google's Gemini Live API to the MYAVANA hair journey plugin. Users can now have natural voice conversations with the AI assistant, receiving spoken responses in real-time.

## ✨ Key Features

### 🎤 Real-Time Audio Communication
- **WebSocket-based** connection for low latency
- **16kHz PCM audio** streaming optimized for Gemini Live API
- **Automatic audio format conversion** from browser microphone input
- **Real-time playback** of AI-generated speech responses

### 🔐 Enhanced Security
- **Ephemeral tokens** for secure client-side connections
- **Time-limited sessions** with automatic renewal
- **Session resumption** for connection stability
- **Secure API key management** via WordPress constants

### 🎨 MYAVANA Brand Integration
- **Brand-compliant UI** following MYAVANA design guidelines
- **Coral and blueberry** color scheme with Archivo typography
- **Live session indicators** with animated visual feedback
- **Connection quality monitoring** with signal strength display

### 📱 Responsive Design
- **Mobile-optimized** interface with touch-friendly controls
- **Accessibility features** including ARIA labels and keyboard navigation
- **Performance optimizations** for smooth animations
- **Dark mode support** with reduced motion options

## 🏗️ Architecture

### Core Components

```
┌─────────────────────────────────────────┐
│           WordPress Frontend           │
├─────────────────────────────────────────┤
│  MyavanaGeminiChatbot (Main Interface) │
│  ├─ Voice/Video Mode Toggle            │
│  ├─ Live API Mode Switch               │
│  └─ Fallback Speech Recognition        │
├─────────────────────────────────────────┤
│    MyavanaGeminiLiveAPI (Core Logic)   │
│  ├─ WebSocket Management              │
│  ├─ Audio Processing                   │
│  ├─ Session Management                 │
│  └─ Error Handling                     │
├─────────────────────────────────────────┤
│           WordPress Backend            │
│  ├─ Ephemeral Token Creation          │
│  ├─ API Key Security                   │
│  └─ Session Configuration              │
├─────────────────────────────────────────┤
│          Google Gemini Live API        │
│  ├─ WebSocket Connection              │
│  ├─ Audio Streaming                    │
│  ├─ AI Response Generation             │
│  └─ Session Resumption                 │
└─────────────────────────────────────────┘
```

### File Structure

```
myavana-hair-journey/
├── templates/
│   └── test-shortcode.php          # Updated shortcode with Live API
├── assets/
│   ├── js/
│   │   ├── gemini-live-api.js      # Core Live API implementation
│   │   ├── gemini-chatbot.js       # Updated UI integration
│   │   └── live-api-test.js        # Testing and monitoring tools
│   └── css/
│       └── gemini-chatbot.css      # Enhanced MYAVANA styling
```

## 🚀 Implementation Details

### 1. Ephemeral Token Management

**Location**: `templates/test-shortcode.php:87-147`

```php
function myavana_create_ephemeral_token($api_key) {
    $token_config = [
        'config' => [
            'uses' => 5,
            'expireTime' => date('c', time() + (30 * 60)),
            'newSessionExpireTime' => date('c', time() + (2 * 60)),
            'liveConnectConstraints' => [
                'model' => 'gemini-2.0-flash-live-001',
                // ... session configuration
            ]
        ]
    ];

    // Creates secure token for client-side Live API access
}
```

### 2. WebSocket Connection

**Location**: `assets/js/gemini-live-api.js:87-142`

```javascript
async connectToLiveAPI() {
    // Create ephemeral token
    await this.createEphemeralToken();

    // Establish WebSocket connection
    const wsUrl = `wss://generativelanguage.googleapis.com/ws/...?access_token=${this.ephemeralToken}`;
    this.websocket = new WebSocket(wsUrl);

    // Handle connection lifecycle
    this.websocket.onopen = () => this.startSession();
    this.websocket.onmessage = (event) => this.handleWebSocketMessage(event.data);
    this.websocket.onclose = (event) => this.handleReconnection();
}
```

### 3. Audio Processing Pipeline

**Location**: `assets/js/gemini-live-api.js:246-311`

```javascript
// Audio Input: Browser Microphone → WebM → PCM 16kHz
async processAudioChunk(blob) {
    const arrayBuffer = await blob.arrayBuffer();
    const audioBuffer = await this.audioContext.decodeAudioData(arrayBuffer);
    const pcmData = this.convertToPCM16(audioBuffer);
    const base64Data = btoa(String.fromCharCode.apply(null, new Uint8Array(pcmData)));
    this.sendAudioToGemini(base64Data);
}

// Audio Output: Gemini PCM → AudioBuffer → Speaker
handleAudioData(base64Data) {
    const audioData = atob(base64Data);
    const audioBuffer = new ArrayBuffer(audioData.length * 2);
    const audioView = new Int16Array(audioBuffer);
    // Convert and play audio
    this.playAudioData(audioView);
}
```

### 4. Session Management

**Location**: `assets/js/gemini-live-api.js:144-190`

```javascript
startSession() {
    const setupMessage = {
        setup: {
            model: "models/gemini-2.0-flash-live-001",
            generationConfig: {
                responseModalities: ["AUDIO"],
                contextWindowCompression: { slidingWindow: {} },
                sessionResumption: this.resumptionHandle ? { handle: this.resumptionHandle } : {}
            },
            systemInstruction: {
                parts: [{ text: "You are MYAVANA AI..." }]
            }
        }
    };
    this.sendMessage(setupMessage);
}
```

## 🎨 UI/UX Features

### Live Mode Toggle
- **Prominent toggle switch** to enable/disable Live API mode
- **Visual feedback** with coral branding colors
- **Smooth animations** following MYAVANA design system

### Connection Quality Indicator
- **Real-time signal bars** showing connection strength
- **Animated indicators** during active sessions
- **Performance monitoring** for optimal user experience

### Enhanced Voice Interface
- **Spectrum visualizer** showing audio activity
- **Live session badge** with pulsing animation
- **Status notifications** with branded styling

### MYAVANA Brand Compliance
- **Archivo typography** (Regular and Black weights)
- **Official color palette** (Coral, Onyx, Blueberry, etc.)
- **Component styling** following brand guidelines
- **Accessibility standards** with ARIA labels

## 🔧 Configuration

### 1. API Key Setup

Add to `wp-config.php`:
```php
define('MYAVANA_GEMINI_API_KEY', 'your-gemini-api-key-here');
```

### 2. WordPress Debug Mode

For testing and monitoring:
```php
define('WP_DEBUG', true);
```

### 3. Model Configuration

**Supported Models:**
- `gemini-2.0-flash-live-001` (Recommended - optimized for Live API)
- `gemini-2.5-flash-native-audio-preview-09-2025` (Native audio output)

### 4. Audio Configuration

**Optimized Settings:**
- **Sample Rate**: 16kHz (Gemini requirement)
- **Channels**: Mono (1 channel)
- **Bit Depth**: 16-bit PCM
- **Output**: 24kHz (Gemini output rate)

## 🧪 Testing & Monitoring

### Built-in Test Suite

**Location**: `assets/js/live-api-test.js`

**Features:**
- **Ephemeral token creation** testing
- **WebSocket connection** validation
- **Audio system** compatibility checks
- **Latency measurement** and optimization
- **Visual results display** with recommendations

**Usage:**
```javascript
// Run complete test suite
await myavanaLiveAPITester.runCompleteTest();

// Monitor connection quality
myavanaLiveAPITester.monitorConnectionQuality(liveAPI);
```

### Performance Monitoring

**Metrics Tracked:**
- Connection establishment time
- First audio response latency
- Average response time
- Error rates and types
- Token renewal frequency

## 🚀 Usage Instructions

### For Users

1. **Navigate to the chatbot**: Use the `[myavana_test]` shortcode
2. **Enable Live Mode**: Toggle the "Live Audio Mode" switch
3. **Grant permissions**: Allow microphone access when prompted
4. **Start conversation**: Click the microphone to begin voice chat
5. **Natural conversation**: Speak naturally and receive audio responses

### For Developers

1. **Integration**: Include the shortcode in any page/post
2. **Customization**: Modify `assets/css/gemini-chatbot.css` for styling
3. **Configuration**: Adjust model parameters in the token creation function
4. **Testing**: Enable WP_DEBUG to access testing tools
5. **Monitoring**: Use browser console for detailed logging

## 🔍 Technical Specifications

### Browser Requirements
- **WebSocket support** (All modern browsers)
- **Web Audio API** (Chrome 34+, Firefox 25+, Safari 14.1+)
- **MediaRecorder API** (Chrome 47+, Firefox 29+, Safari 14.1+)
- **getUserMedia** (HTTPS required for microphone access)

### Network Requirements
- **WebSocket connections** (Port 443)
- **Stable internet** (Recommended: 1+ Mbps)
- **Low latency** (< 200ms for optimal experience)

### Security Considerations
- **HTTPS required** for microphone access
- **Ephemeral tokens** expire after 30 minutes
- **Session resumption** tokens valid for 2 hours
- **No API keys** exposed to client-side code

## 🐛 Troubleshooting

### Common Issues

**"Failed to connect to MYAVANA AI"**
- Check API key configuration in `wp-config.php`
- Verify network connectivity and firewall settings
- Run the built-in test suite for detailed diagnosis

**"Microphone access denied"**
- Ensure site is served over HTTPS
- Check browser permissions for microphone
- Try refreshing the page and granting permissions again

**"Audio quality issues"**
- Use headphones to prevent feedback
- Check internet connection stability
- Reduce background noise

**"High latency responses"**
- Check network latency to Google services
- Consider using a different model variant
- Monitor connection quality indicator

### Debug Tools

**Console Logging:**
```javascript
// Enable detailed logging
localStorage.setItem('myavana-debug', 'true');

// View test results
myavanaLiveAPITester.runCompleteTest().then(console.log);

// Monitor connection
myavanaGeminiChatbotInstance.liveAPI.getConnectionStatus();
```

## 📈 Performance Optimization

### Recommendations

1. **Use headphones** to prevent audio feedback
2. **Stable internet connection** (WiFi preferred over cellular)
3. **Close unnecessary browser tabs** to free up resources
4. **Grant microphone permissions** before starting session
5. **Test connection quality** using built-in tools

### Best Practices

- **Short, clear speech** for better recognition
- **Wait for AI responses** before speaking again
- **Use Live mode toggle** to preserve bandwidth when not needed
- **Monitor connection quality** indicator for optimal experience

## 🔄 Future Enhancements

### Potential Improvements

1. **Voice Activity Detection** for hands-free operation
2. **Multi-language support** for international users
3. **Custom voice selection** from available options
4. **Advanced audio processing** with noise cancellation
5. **Session recording** for analysis and improvement

### Integration Opportunities

1. **BuddyPress integration** for community voice chats
2. **Hair analysis from voice** descriptions
3. **Personalized voice responses** based on user profile
4. **Voice-guided tutorials** for hair care routines

---

## 📞 Support

For technical support or questions about this implementation:

1. **Check the test suite** results for common issues
2. **Review browser console** for error messages
3. **Verify configuration** following this documentation
4. **Test with different browsers** to isolate issues

**Implementation Complete!** 🎉

The MYAVANA Gemini Live API integration is now ready for real-time audio-to-audio hair care conversations with full brand compliance and security best practices.