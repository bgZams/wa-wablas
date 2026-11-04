<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Percakapan - {{ $phone }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .chat-container {
            height: calc(100vh - 280px);
            overflow-y: auto;
            scroll-behavior: smooth;
        }

        .chat-container::-webkit-scrollbar {
            width: 6px;
        }

        .chat-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .chat-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
        }

        .chat-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5568d3, #653a8b);
        }

        .message-bubble {
            max-width: 70%;
            word-wrap: break-word;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-incoming {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 1.25rem 1.25rem 1.25rem 0.25rem;
        }

        .message-outgoing {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 1.25rem 1.25rem 0.25rem 1.25rem;
        }

        .typing-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #9ca3af;
            animation: typing 1.4s infinite;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
            }
            30% {
                transform: translateY(-10px);
            }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .input-focus:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
        }

        .send-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s;
        }

        .send-button:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .send-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .back-button {
            transition: all 0.3s;
        }

        .back-button:hover {
            transform: translateX(-5px);
        }

        .status-badge {
            font-size: 0.7rem;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
        }

        @media (max-width: 768px) {
            .message-bubble {
                max-width: 85%;
            }

            .chat-container {
                height: calc(100vh - 320px);
            }
        }

        .emoji-picker {
            position: absolute;
            bottom: 100%;
            right: 0;
            margin-bottom: 0.5rem;
        }

        .date-divider {
            position: relative;
            text-align: center;
            margin: 2rem 0;
        }

        .date-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, transparent, #d1d5db, transparent);
        }

        .date-divider span {
            position: relative;
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="glass-effect shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="back-button flex items-center space-x-3 text-gray-700 hover:text-purple-600">
                    <i class="fas fa-arrow-left text-xl"></i>
                    <span class="font-semibold hidden sm:inline">Kembali</span>
                </a>

                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-800">{{ $phone }}</h2>
                        <p class="text-xs text-gray-500">
                            <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                            WhatsApp Bot
                        </p>
                    </div>
                </div>

                <button onclick="refreshChat()" class="text-gray-600 hover:text-purple-600 transition">
                    <i class="fas fa-sync-alt text-xl"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6">
        <div class="glass-effect rounded-2xl shadow-2xl overflow-hidden fade-in">
            <!-- Chat Header -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-comments text-white text-xl"></i>
                        <h3 class="text-xl font-bold text-white">Percakapan</h3>
                    </div>
                    <span class="bg-white/20 text-white text-xs px-3 py-1 rounded-full">
                        {{ count($conversationLogs) }} Pesan
                    </span>
                </div>
            </div>

            <!-- Chat Container -->
            <div id="chatContainer" class="chat-container bg-gray-50 p-6">
                @php
                    $currentDate = null;
                @endphp

                @forelse ($conversationLogs as $log)
                    @php
                        $messageDate = \Carbon\Carbon::createFromTimestamp($log['timestamp'])->format('Y-m-d');
                        $showDateDivider = $currentDate !== $messageDate;
                        $currentDate = $messageDate;
                    @endphp

                    @if ($showDateDivider)
                        <div class="date-divider">
                            <span>{{ \Carbon\Carbon::createFromTimestamp($log['timestamp'])->format('d M Y') }}</span>
                        </div>
                    @endif

                    <div class="flex {{ $log['type'] === 'outgoing' ? 'justify-end' : 'justify-start' }} mb-4">
                        <div class="message-bubble {{ $log['type'] === 'outgoing' ? 'message-outgoing' : 'message-incoming' }} px-4 py-3 shadow-md">
    
                        @if ($log['type'] === 'incoming')
                            <div class="flex items-center space-x-2 mb-1">
                                <i class="fas fa-user-circle text-gray-500 text-sm"></i>
                                <span class="text-xs font-semibold text-gray-600">{{ $phone }}</span>
                            </div>
                        @endif
                    
                        <p class="{{ $log['type'] === 'outgoing' ? 'text-white' : 'text-gray-800' }} text-sm leading-relaxed whitespace-pre-line">
                            {!! nl2br(e($log['message'])) !!}
                        </p>
                    
                        <div class="flex items-center justify-between mt-2 gap-3">
                            <span class="{{ $log['type'] === 'outgoing' ? 'text-white/70' : 'text-gray-500' }} text-xs">
                                {{ \Carbon\Carbon::createFromTimestamp($log['timestamp'])->format('H:i') }}
                            </span>
                            </div>
                    </div>
                    </div>
                @empty
                    <div class="text-center py-16">
                        <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                            <i class="fas fa-comments text-gray-300 text-5xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">Belum Ada Percakapan</h3>
                        <p class="text-gray-500">Mulai percakapan dengan mengirim pesan</p>
                    </div>
                @endforelse
            </div>

            <!-- Reply Form -->
            <div class="bg-white border-t border-gray-200 p-4">
                <form action="{{ route('reply') }}" method="POST" id="replyForm" class="flex items-end space-x-3">
                    @csrf
                    <input type="hidden" name="phone" value="{{ $phone }}">

                    <!-- Emoji Button -->
                    <button type="button" onclick="toggleEmojiPicker()" class="flex-shrink-0 w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition text-gray-600 hover:text-purple-600">
                        <i class="far fa-smile text-xl"></i>
                    </button>

                    <!-- Message Input -->
                    <div class="flex-1 relative">
                        <textarea
                            name="message"
                            id="messageInput"
                            rows="1"
                            placeholder="Ketik pesan..."
                            required
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-2xl focus:border-purple-500 input-focus resize-none transition"
                            onkeydown="handleKeyPress(event)"
                            oninput="autoResize(this)"
                        ></textarea>

                        <!-- Emoji Picker (Simple) -->
                        <div id="emojiPicker" class="hidden emoji-picker glass-effect rounded-xl shadow-2xl p-3 w-64">
                            <div class="grid grid-cols-8 gap-2">
                                @foreach(['😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '👍', '👎', '👏', '🙏', '💪', '🎉', '🎊', '✨', '⭐', '🌟'] as $emoji)
                                    <button type="button" onclick="insertEmoji('{{ $emoji }}')" class="text-2xl hover:bg-purple-100 rounded-lg p-1 transition">
                                        {{ $emoji }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Send Button -->
                    <button
                        type="submit"
                        id="sendButton"
                        class="send-button flex-shrink-0 w-12 h-12 rounded-full text-white flex items-center justify-center shadow-lg transition"
                    >
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>

                <!-- Character Counter (Optional) -->
                <div class="text-xs text-gray-400 text-right mt-2" id="charCounter">0 / 1000</div>
            </div>
        </div>
    </main>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-6 right-6 glass-effect rounded-xl shadow-2xl px-6 py-4 transform translate-x-full transition-transform duration-300 z-50">
        <div class="flex items-center space-x-3">
            <i id="toastIcon" class="fas fa-check-circle text-green-500 text-xl"></i>
            <p id="toastMessage" class="text-gray-800 font-medium"></p>
        </div>
    </div>

    <script>
        // Auto scroll to bottom on load
        window.addEventListener('load', () => {
            scrollToBottom();
        });

        function scrollToBottom() {
            const chatContainer = document.getElementById('chatContainer');
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        // Auto resize textarea
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';

            // Update character counter
            const charCounter = document.getElementById('charCounter');
            const length = textarea.value.length;
            charCounter.textContent = `${length} / 1000`;

            if (length > 900) {
                charCounter.classList.add('text-red-500');
            } else {
                charCounter.classList.remove('text-red-500');
            }
        }

        // Handle Enter key (Shift+Enter for new line)
        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                document.getElementById('replyForm').submit();
            }
        }

        // Emoji picker toggle
        function toggleEmojiPicker() {
            const picker = document.getElementById('emojiPicker');
            picker.classList.toggle('hidden');
        }

        // Insert emoji
        function insertEmoji(emoji) {
            const input = document.getElementById('messageInput');
            const start = input.selectionStart;
            const end = input.selectionEnd;
            const text = input.value;

            input.value = text.substring(0, start) + emoji + text.substring(end);
            input.selectionStart = input.selectionEnd = start + emoji.length;
            input.focus();

            autoResize(input);
            toggleEmojiPicker();
        }

        // Close emoji picker when clicking outside
        document.addEventListener('click', (event) => {
            const picker = document.getElementById('emojiPicker');
            const emojiButton = event.target.closest('[onclick="toggleEmojiPicker()"]');
            const pickerElement = event.target.closest('#emojiPicker');

            if (!emojiButton && !pickerElement && !picker.classList.contains('hidden')) {
                picker.classList.add('hidden');
            }
        });

        // Refresh chat
        function refreshChat() {
            showToast('Memuat ulang percakapan...', 'info');
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }

        // Toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastIcon = document.getElementById('toastIcon');
            const toastMessage = document.getElementById('toastMessage');

            if (type === 'success') {
                toastIcon.className = 'fas fa-check-circle text-green-500 text-xl';
            } else if (type === 'error') {
                toastIcon.className = 'fas fa-times-circle text-red-500 text-xl';
            } else if (type === 'info') {
                toastIcon.className = 'fas fa-info-circle text-blue-500 text-xl';
            }

            toastMessage.textContent = message;
            toast.classList.remove('translate-x-full');

            setTimeout(() => {
                toast.classList.add('translate-x-full');
            }, 3000);
        }

        // Show toast from Laravel session
        @if(session('success'))
            showToast("{{ session('success') }}", 'success');
            scrollToBottom();
        @elseif(session('error'))
            showToast("{{ session('error') }}", 'error');
        @endif

        // Form submission handling
        document.getElementById('replyForm').addEventListener('submit', function(e) {
            const button = document.getElementById('sendButton');
            const input = document.getElementById('messageInput');

            if (input.value.trim() === '') {
                e.preventDefault();
                showToast('Pesan tidak boleh kosong', 'error');
                return;
            }

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        });
    </script>
</body>
</html>
