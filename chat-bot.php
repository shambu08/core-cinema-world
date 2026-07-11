<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'php/db_connect.php';

// Get user details
$userStmt = $pdo->prepare("SELECT full_name, username FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch();

// Get movie count for stats
$movieCount = $pdo->query("SELECT COUNT(*) as count FROM movies")->fetch(PDO::FETCH_ASSOC)['count'];
$hallCount = $pdo->query("SELECT COUNT(*) as count FROM cinema_halls")->fetch(PDO::FETCH_ASSOC)['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Movie Assistant - Core Cinema World</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .nav-brand i {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-brand h2 {
            font-size: 22px;
            color: #1f2937;
        }
        
        .nav-user {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logout-btn {
            color: #ef4444;
            text-decoration: none;
            font-size: 18px;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: #667eea;
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            transform: translateX(-5px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Chat Container */
        .chat-container {
            background: white;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            display: flex;
            flex-direction: column;
            height: 70vh;
        }
        
        /* Chat Header */
        .chat-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 20px 25px;
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .chat-avatar {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        
        .chat-header-info h3 {
            font-size: 18px;
            margin-bottom: 3px;
        }
        
        .chat-header-info p {
            font-size: 12px;
            opacity: 0.8;
        }
        
        /* Chat Messages */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message {
            display: flex;
            gap: 12px;
            max-width: 80%;
            animation: fadeInUp 0.3s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .message-avatar.ai {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .message-avatar.user {
            background: #10b981;
            color: white;
        }
        
        .message-bubble {
            background: white;
            padding: 12px 16px;
            border-radius: 18px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            font-size: 14px;
            line-height: 1.5;
            color: #1f2937;
        }
        
        .message.user .message-bubble {
            background: #667eea;
            color: white;
        }
        
        .message-time {
            font-size: 10px;
            color: #9ca3af;
            margin-top: 5px;
            display: block;
        }
        
        /* Typing Indicator */
        .typing-indicator {
            display: flex;
            gap: 5px;
            padding: 12px 16px;
            background: white;
            border-radius: 18px;
            width: fit-content;
        }
        
        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: #9ca3af;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }
        
        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.5;
            }
            30% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }
        
        /* Chat Input */
        .chat-input-container {
            padding: 20px;
            background: white;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
        }
        
        .chat-input-container input {
            flex: 1;
            padding: 14px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 30px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .chat-input-container input:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .chat-input-container button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .chat-input-container button:hover {
            transform: scale(1.02);
        }
        
        /* Suggestion Chips */
        .suggestion-chips {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .chip {
            background: white;
            border: 1px solid #e5e7eb;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .chip:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .message {
                max-width: 95%;
            }
            
            .suggestion-chips {
                overflow-x: auto;
                flex-wrap: nowrap;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <i class="fas fa-film"></i>
            <h2>Core Cinema World</h2>
        </div>
        <div class="nav-user">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>
    
    <div class="container">
        <a href="home.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Home</a>
        
        <div class="chat-container">
            <div class="chat-header">
                <div class="chat-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chat-header-info">
                    <h3>AI Movie Assistant</h3>
                    <p>Powered by Core Cinema AI • Online</p>
                </div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <div class="message ai">
                    <div class="message-avatar ai">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-bubble">
                        Hello <?php echo htmlspecialchars($user['full_name']); ?>! 👋<br>
                        I'm your AI movie assistant. I can help you with:<br><br>
                        🎬 Find movies by genre, language, or mood<br>
                        🎟️ Check show timings and availability<br>
                        💰 Ticket prices and offers<br>
                        ⭐ Movie ratings and reviews<br>
                        📍 Cinema hall locations<br><br>
                        How can I help you today?
                        <span class="message-time"><?php echo date('h:i A'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="suggestion-chips" id="suggestionChips">
                <div class="chip" onclick="sendSuggestion('Show me action movies')">🎬 Action movies</div>
                <div class="chip" onclick="sendSuggestion('What are the show timings?')">⏰ Show timings</div>
                <div class="chip" onclick="sendSuggestion('Ticket prices')">💰 Ticket prices</div>
                <div class="chip" onclick="sendSuggestion('Latest movies')">🍿 Latest movies</div>
                <div class="chip" onclick="sendSuggestion('Top rated movies')">⭐ Top rated</div>
                <div class="chip" onclick="sendSuggestion('Cinema halls near me')">📍 Cinema halls</div>
            </div>
            
            <div class="chat-input-container">
                <input type="text" id="chatInput" placeholder="Ask me anything about movies..." onkeypress="handleKeyPress(event)">
                <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i> Send</button>
            </div>
        </div>
    </div>
    
    <script>
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        
        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }
        
        function sendSuggestion(text) {
            chatInput.value = text;
            sendMessage();
        }
        
        function sendMessage() {
            const message = chatInput.value.trim();
            if (!message) return;
            
            // Add user message
            addMessage(message, 'user');
            chatInput.value = '';
            
            // Show typing indicator
            showTypingIndicator();
            
            // Send to AI
            fetch('php/ai_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                hideTypingIndicator();
                addMessage(data.reply, 'ai');
            })
            .catch(error => {
                hideTypingIndicator();
                addMessage("Sorry, I'm having trouble connecting. Please try again.", 'ai');
                console.error('Error:', error);
            });
        }
        
        function addMessage(text, sender) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${sender}`;
            
            const avatar = document.createElement('div');
            avatar.className = `message-avatar ${sender}`;
            avatar.innerHTML = sender === 'ai' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
            
            const bubble = document.createElement('div');
            bubble.className = 'message-bubble';
            bubble.innerHTML = text;
            
            const timeSpan = document.createElement('span');
            timeSpan.className = 'message-time';
            const now = new Date();
            timeSpan.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            bubble.appendChild(timeSpan);
            
            messageDiv.appendChild(avatar);
            messageDiv.appendChild(bubble);
            
            chatMessages.appendChild(messageDiv);
            scrollToBottom();
        }
        
        let typingIndicatorDiv = null;
        
        function showTypingIndicator() {
            typingIndicatorDiv = document.createElement('div');
            typingIndicatorDiv.className = 'message ai';
            typingIndicatorDiv.innerHTML = `
                <div class="message-avatar ai">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            `;
            chatMessages.appendChild(typingIndicatorDiv);
            scrollToBottom();
        }
        
        function hideTypingIndicator() {
            if (typingIndicatorDiv) {
                typingIndicatorDiv.remove();
                typingIndicatorDiv = null;
            }
        }
        
        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>