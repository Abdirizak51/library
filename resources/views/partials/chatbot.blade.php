<button id="chatbot-toggle" aria-label="Open AI Assistant">🤖</button>

<div id="chatbot-panel" class="hidden">
    <div class="chatbot-header">
        <div>🤖</div>
        <div>
            <div class="font-semibold text-sm">LibraryAI Assistant</div>
            <div class="text-xs text-stone-400">Ask me anything about books</div>
        </div>
        <button onclick="document.getElementById('chatbot-panel').classList.add('hidden')"
                class="ml-auto text-white text-xl">×</button>
    </div>
    <div id="chatbot-messages" class="chatbot-messages">
        <div class="msg-bot">Hello! I can recommend books and answer questions. What are you looking for?</div>
    </div>
    <div class="chatbot-input-area">
        <input id="chatbot-input" type="text" class="chatbot-input"
               placeholder="Ask about a book…"
               onkeydown="if(event.key==='Enter') sendChat()">
        <button onclick="sendChat()" class="btn-primary px-3 py-2 rounded-xl text-sm">→</button>
    </div>
</div>

<script>
document.getElementById('chatbot-toggle').addEventListener('click', () => {
    document.getElementById('chatbot-panel').classList.toggle('hidden');
});

async function sendChat() {
    const input = document.getElementById('chatbot-input');
    const msg = input.value.trim();
    if (!msg) return;
    input.value = '';
    addMessage(msg, 'user');
}

function addMessage(text, type) {
    const el = document.createElement('div');
    el.className = `msg-${type}`;
    el.textContent = text;
    const container = document.getElementById('chatbot-messages');
    container.appendChild(el);
    container.scrollTop = container.scrollHeight;
    return el;
}
</script>