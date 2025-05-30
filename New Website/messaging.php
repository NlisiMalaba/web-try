<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get user data
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, email FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messaging - HealthAssist Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .messaging-container {
            display: flex;
            height: calc(100vh - 200px);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }
        
        .conversation-list {
            width: 350px;
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
        }
        
        .conversation-header {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        .conversation-item:hover {
            background-color: #f8fafc;
        }
        
        .conversation-item.active {
            background-color: #eef2ff;
            border-left: 3px solid #4f46e5;
        }
        
        .conversation-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-weight: 600;
            color: #4f46e5;
        }
        
        .conversation-details {
            flex: 1;
        }
        
        .conversation-name {
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .conversation-preview {
            font-size: 0.85rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-time {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        
        .message-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .message-header {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
        }
        
        .message-header-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #4f46e5;
        }
        
        .message-header-name {
            font-weight: 500;
        }
        
        .message-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f8fafc;
        }
        
        .message {
            margin-bottom: 20px;
            max-width: 70%;
        }
        
        .message.received {
            margin-right: auto;
        }
        
        .message.sent {
            margin-left: auto;
            text-align: right;
        }
        
        .message-content {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 18px;
            background-color: #e2e8f0;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .sent .message-content {
            background-color: #4f46e5;
            color: white;
        }
        
        .message-time {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 4px;
        }
        
        .message-input {
            padding: 15px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
        }
        
        .message-input input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            outline: none;
            font-size: 0.9rem;
        }
        
        .message-input button {
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .message-input button:hover {
            background: #4338ca;
        }
        
        .no-conversation {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #94a3b8;
            text-align: center;
            padding: 20px;
        }
        
        .no-conversation i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Include the sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search messages...">
                </div>
                
                <div class="user-menu">
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['first_name'] . ' ' . $user['last_name']); ?>" alt="Profile" class="avatar">
                        <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                    </div>
                </div>
            </header>
            
            <div class="content">
                <div class="page-header">
                    <h1>Messages</h1>
                    <p>Communicate with your healthcare providers and support team</p>
                </div>
                
                <div class="messaging-container">
                    <!-- Conversation List -->
                    <div class="conversation-list">
                        <div class="conversation-header">
                            Conversations
                        </div>
                        
                        <!-- Conversation Item -->
                        <div class="conversation-item active">
                            <div class="conversation-avatar">DR</div>
                            <div class="conversation-details">
                                <div class="conversation-name">Dr. Sarah Johnson</div>
                                <div class="conversation-preview">Just a reminder about your appointment tomorrow at 2 PM</div>
                            </div>
                            <div class="conversation-time">2h ago</div>
                        </div>
                        
                        <!-- More conversation items -->
                        <div class="conversation-item">
                            <div class="conversation-avatar">NP</div>
                            <div class="conversation-details">
                                <div class="conversation-name">Nurse Practitioner</div>
                                <div class="conversation-preview">Your lab results are in. Let's discuss them at your next appointment.</div>
                            </div>
                            <div class="conversation-time">1d ago</div>
                        </div>
                        
                        <div class="conversation-item">
                            <div class="conversation-avatar">PT</div>
                            <div class="conversation-details">
                                <div class="conversation-name">Physical Therapy Team</div>
                                <div class="conversation-preview">Don't forget to do your daily exercises!</div>
                            </div>
                            <div class="conversation-time">2d ago</div>
                        </div>
                    </div>
                    
                    <!-- Message Area -->
                    <div class="message-area">
                        <div class="message-header">
                            <div class="message-header-avatar">DR</div>
                            <div class="message-header-name">Dr. Sarah Johnson</div>
                        </div>
                        
                        <div class="message-body">
                            <!-- Received Message -->
                            <div class="message received">
                                <div class="message-content">
                                    Hello! How are you feeling today?
                                </div>
                                <div class="message-time">Today, 10:30 AM</div>
                            </div>
                            
                            <!-- Sent Message -->
                            <div class="message sent">
                                <div class="message-content">
                                    Hi Dr. Johnson, I'm feeling much better today. The medication seems to be helping.
                                </div>
                                <div class="message-time">Today, 10:35 AM</div>
                            </div>
                            
                            <!-- Received Message -->
                            <div class="message received">
                                <div class="message-content">
                                    That's great to hear! Have you been experiencing any side effects from the new prescription?
                                </div>
                                <div class="message-time">Today, 10:36 AM</div>
                            </div>
                            
                            <!-- Sent Message -->
                            <div class="message sent">
                                <div class="message-content">
                                    Just a bit of drowsiness, but nothing too concerning. Should I adjust the timing?
                                </div>
                                <div class="message-time">Today, 10:38 AM</div>
                            </div>
                            
                            <!-- Received Message -->
                            <div class="message received">
                                <div class="message-content">
                                    Mild drowsiness is normal. Try taking it in the evening instead. Let me know if it persists after a few days.
                                </div>
                                <div class="message-time">Today, 10:40 AM</div>
                            </div>
                            
                            <!-- Sent Message -->
                            <div class="message sent">
                                <div class="message-content">
                                    Will do, thank you! Also, I wanted to ask about my next appointment...
                                </div>
                                <div class="message-time">Today, 10:42 AM</div>
                            </div>
                            
                            <!-- Received Message -->
                            <div class="message received">
                                <div class="message-content">
                                    Yes, we have you scheduled for next Tuesday at 2 PM. Does that still work for you?
                                </div>
                                <div class="message-time">Today, 10:43 AM</div>
                            </div>
                            
                            <!-- Sent Message -->
                            <div class="message sent">
                                <div class="message-content">
                                    That works perfectly. See you then!
                                </div>
                                <div class="message-time">Today, 10:45 AM</div>
                            </div>
                        </div>
                        
                        <!-- Message Input -->
                        <div class="message-input">
                            <input type="text" placeholder="Type a message...">
                            <button type="button">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Simple message sending functionality
        document.addEventListener('DOMContentLoaded', function() {
            const messageInput = document.querySelector('.message-input input');
            const sendButton = document.querySelector('.message-input button');
            const messageBody = document.querySelector('.message-body');
            
            function sendMessage() {
                const messageText = messageInput.value.trim();
                if (messageText) {
                    // Create new message element
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'message sent';
                    
                    const now = new Date();
                    const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    
                    messageDiv.innerHTML = `
                        <div class="message-content">
                            ${messageText}
                        </div>
                        <div class="message-time">${timeString}</div>
                    `;
                    
                    // Add to message body and scroll to bottom
                    messageBody.appendChild(messageDiv);
                    messageBody.scrollTop = messageBody.scrollHeight;
                    
                    // Clear input
                    messageInput.value = '';
                    
                    // Simulate reply after a short delay
                    setTimeout(sendAutoReply, 1000);
                }
            }
            
            function sendAutoReply() {
                const replies = [
                    "I'll get back to you shortly.",
                    "Thanks for your message.",
                    "I'll check that for you.",
                    "Is there anything else you'd like to know?",
                    "I'll make a note of that."
                ];
                
                const randomReply = replies[Math.floor(Math.random() * replies.length)];
                
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message received';
                
                const now = new Date();
                const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                messageDiv.innerHTML = `
                    <div class="message-content">
                        ${randomReply}
                    </div>
                    <div class="message-time">${timeString}</div>
                `;
                
                messageBody.appendChild(messageDiv);
                messageBody.scrollTop = messageBody.scrollHeight;
            }
            
            // Send message on button click
            sendButton.addEventListener('click', sendMessage);
            
            // Send message on Enter key
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
            
            // Toggle active conversation
            const conversationItems = document.querySelectorAll('.conversation-item');
            conversationItems.forEach(item => {
                item.addEventListener('click', function() {
                    // Remove active class from all items
                    conversationItems.forEach(i => i.classList.remove('active'));
                    // Add active class to clicked item
                    this.classList.add('active');
                    // Here you would typically load the conversation
                });
            });
            
            // Scroll to bottom of messages on load
            messageBody.scrollTop = messageBody.scrollHeight;
        });
    </script>
</body>
</html>
