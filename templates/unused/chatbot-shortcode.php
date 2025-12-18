<?php
function myavana_chatbot_shortcode() {
    ob_start();
    ?>
    <div class="myavana-chatbot">
        <button class="chatbot-toggle">Chat with Myavana</button>
        <div class="chatbot-window" style="display: none;">
            <h3>Hair Journey Assistant</h3>
            <p>What stage are you in your hair journey?</p>
            <select id="hair-journey-stage">
                <option value="Postpartum haircare">Postpartum haircare</option>
                <option value="Nourishing and growing">Nourishing and growing</option>
                <option value="Experimenting">Experimenting</option>
                <option value="Bored/Stuck">Bored/Stuck</option>
                <option value="Repairing and restoring">Repairing and restoring</option>
                <option value="Desperate for a change">Desperate for a change</option>
                <option value="Trying something new">Trying something new</option>
                <option value="Loving my recent hairstyle change">Loving my recent hairstyle change</option>
            </select>
            <div id="chatbot-response"></div>
        </div>
    </div>
    <style>
        .myavana-chatbot { position: fixed; bottom: 20px; right: 20px; }
        .chatbot-toggle { background: #4CAF50; color: white; padding: 10px; border-radius: 50%; }
        .chatbot-window { background: white; border: 1px solid #ccc; padding: 20px; max-width: 300px; }
        .chatbot-window select { width: 100%; padding: 10px; margin: 10px 0; }
    </style>
    <script>
        document.querySelector('.chatbot-toggle').addEventListener('click', function() {
            const window = document.querySelector('.chatbot-window');
            window.style.display = window.style.display === 'none' ? 'block' : 'none';
        });
        document.querySelector('#hair-journey-stage').addEventListener('change', function() {
            const stage = this.value;
            // Mock AI API call (replace with actual xAI API)
            const response = `Based on your stage "${stage}", we recommend focusing on regimens for hair health and consulting a stylist.`;
            document.querySelector('#chatbot-response').textContent = response;
        });
    </script>
    <?php
    return ob_get_clean();
}
?>