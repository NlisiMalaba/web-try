<?php
require_once 'includes/header.php';
require_once 'includes/auth.php';

$user = verifyToken();
if (!$user) {
    header('Location: login.php');
    exit;
}

// Add custom CSS and JS
$customCSS = 'assets/css/chatbot.css';
$customJS = 'assets/js/chatbot.js';
?>

<link rel="stylesheet" href="<?php echo $customCSS; ?>?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<div class="container-fluid h-100">
    <div class="row h-100">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </div>
                        <input type="text" class="form-control" id="chat-title-input" placeholder="Enter chat title">
                        <button class="btn btn-primary" type="button" id="save-title-btn">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-outline-secondary" type="button" id="cancel-title-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="row flex-grow-1 m-0">
                <!-- Chat sidebar -->
                <div class="col-md-4 col-lg-3 p-0 border-end d-md-flex flex-column chat-sidebar" id="chatSidebar">
                    <div class="p-3 border-bottom">
                        <button class="btn btn-primary w-100" id="new-chat-btn">
                            <i class="fas fa-plus me-2"></i> New Chat
                        </button>
                    </div>
                    <div class="chat-history-container">
                        <div class="chat-history">
                            <div class="text-center p-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Chat area -->
                <div class="col-md-8 col-lg-9 p-0 d-flex flex-column h-100">
                    <div class="chat-container" id="chatContainer">
                        <!-- Messages will be added here dynamically -->
                        <div class="welcome-message text-center p-5">
                            <div class="mb-4">
                                <i class="fas fa-robot text-primary" style="font-size: 4rem; opacity: 0.2;"></i>
                            </div>
                            <h3>Welcome to Health Assistant</h3>
                            <p class="text-muted">How can I help you with your health today?</p>
                        </div>
                    </div>
                    
                    <div class="chat-input-container border-top p-3">
                        <div class="input-group">
                            <textarea 
                                id="message-input" 
                                class="form-control" 
                                placeholder="Type your message here..." 
                                rows="1"
                                style="resize: none;"
                            ></textarea>
                            <button class="btn btn-primary" id="send-button" disabled>
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <small class="text-muted mt-1 d-block text-end">Press Enter to send, Shift+Enter for new line</small>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Include custom JavaScript -->
<script src="<?php echo $customJS; ?>?v=<?php echo time(); ?>"></script>

<?php include 'includes/footer.php'; ?>
                                    <li><a class="dropdown-item text-danger" href="#" id="deleteChat">Delete Chat</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body chat-container" id="chatContainer">
                            <div class="chat-messages" id="chatMessages">
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="bi bi-robot" style="font-size: 3rem; color: #0d6efd;"></i>
                                    </div>
                                    <h4>How can I help you today?</h4>
                                    <p class="text-muted">Ask me about your health, medications, or any concerns you have.</p>
                                </div>
                            </div>
                            <div class="chat-input mt-auto">
                                <form id="messageForm" class="d-flex">
                                    <input type="hidden" id="currentSessionId" value="">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="userMessage" 
                                               placeholder="Type your message here..." autocomplete="off" required>
                                        <button class="btn btn-primary" type="submit" id="sendMessage">
                                            <i class="bi bi-send-fill"></i>
                                        </button>
                                    </div>
                                </form>
                                <div class="form-text text-end mt-1">
                                    <small>Health Assistant may produce inaccurate information about people, places, or facts.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Rename Chat Modal -->
<div class="modal fade" id="renameChatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rename Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="chatTitleInput" class="form-label">Chat Title</label>
                    <input type="text" class="form-control" id="chatTitleInput" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveChatTitle">Save changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteChatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this chat? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteChat">Delete</button>
            </div>
        </div>
    </div>
</div>

<style>
    .chat-container {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 200px);
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        margin-bottom: 1rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .message {
        max-width: 90%;
        padding: 0.75rem 1rem;
        border-radius: 1rem;
    }

    .user-message {
        margin-left: auto;
        background-color: #007bff;
        color: white;
    }

    .bot-message {
        margin-right: auto;
        background-color: #f8f9fa;
    }

    .message-time {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 0.5rem;
    }

    .typing-indicator {
        display: flex;
        justify-content: center;
        padding: 1rem;
    }

    .typing-dot {
        width: 8px;
        height: 8px;
        background-color: #007bff;
        border-radius: 50%;
        margin: 0 4px;
        animation: typing 1.4s infinite ease-in-out;
    }

    .typing-dot:nth-child(1) { animation-delay: 0s; }
    .typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .typing-dot:nth-child(3) { animation-delay: 0.4s; }

    @keyframes typing {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }

    @media (max-width: 768px) {
        .chat-container {
            height: calc(100vh - 180px);
        }
        
        .message {
            max-width: 100%;
        }
    }
</style>

<script>
    $(document).ready(function() {
        let currentSessionId = '';
        let isProcessing = false;

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Load chat sessions
        function loadSessions() {
            $.ajax({
                url: 'api/chatbot.php?action=get_sessions',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const sessions = response.sessions || [];
                        const $sessionsList = $('#sessionsList');
                        const $chatHistory = $('#chatHistory');
                        
                        // Clear existing items
                        $sessionsList.empty();
                        $chatHistory.empty();
                        
                        if (sessions.length === 0) {
                            $sessionsList.append('<li><a class="dropdown-item" href="#">No chat sessions found</a></li>');
                            $chatHistory.html('<div class="text-center p-3 text-muted">No chat history available</div>');
                            return;
                        }
                        
                        // Update dropdown menu
                        sessions.forEach(session => {
                            const date = new Date(session.updated_at).toLocaleString();
                            $sessionsList.append(
                                `<li><a class="dropdown-item session-item" href="#" data-session-id="${session.session_id}">
                                    <div><strong>${session.title}</strong></div>
                                    <small class="text-muted">${date}</small>
                                </a></li>`
                            );
                            
                            // Add to chat history
                            const active = session.session_id === currentSessionId ? 'active' : '';
                            $chatHistory.append(
                                `<a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center ${active}" 
                                   data-session-id="${session.session_id}">
                                    <span class="text-truncate me-2">${session.title}</span>
                                    <small class="text-muted">${formatDate(session.updated_at)}</small>
                                </a>`
                            );
                        });
                        
                        // Add click handlers for session items
                        $('.session-item').on('click', function(e) {
                            e.preventDefault();
                            const sessionId = $(this).data('session-id');
                            loadChatSession(sessionId);
                        });
                        
                        // If no session is active, auto-select the most recent one
                        if (!currentSessionId && sessions.length > 0) {
                            loadChatSession(sessions[0].session_id);
                        }
                    } else {
                        showAlert('Failed to load chat sessions', 'danger');
                    }
                },
                error: function() {
                    showAlert('Error connecting to server', 'danger');
                }
            });
        }
        
        // Format date for display
        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) {
                return 'Today, ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } else if (diffDays === 1) {
                return 'Yesterday, ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } else if (diffDays < 7) {
                return date.toLocaleDateString([], { weekday: 'short', hour: '2-digit', minute: '2-digit' });
            } else {
                return date.toLocaleDateString([], { year: 'numeric', month: 'short', day: 'numeric' });
            }
        }
        
        // Load a specific chat session
        function loadChatSession(sessionId) {
            if (!sessionId) return;
            
            currentSessionId = sessionId;
            
            // Update active state in chat history
            $('#chatHistory .list-group-item').removeClass('active');
            $(`#chatHistory .list-group-item[data-session-id="${sessionId}"]`).addClass('active');
            
            // Show loading state
            const $chatMessages = $('#chatMessages');
            $chatMessages.html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            
            // Load messages for this session
            $.ajax({
                url: `api/chatbot.php?action=get_messages&session_id=${sessionId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayMessages(response.messages || []);
                        
                        // Update chat title
                        const sessionTitle = $(`#chatHistory .list-group-item[data-session-id="${sessionId}"] span`).text().trim();
                        $('#chatTitle').text(sessionTitle || 'New Chat');
                        
                        // Update current session ID in form
                        $('#currentSessionId').val(sessionId);
                    } else {
                        showAlert('Failed to load chat messages', 'danger');
                    }
                },
                error: function() {
                    showAlert('Error connecting to server', 'danger');
                }
            });
        }
        
        // Display messages in the chat
        function displayMessages(messages) {
            const $chatMessages = $('#chatMessages');
            
            if (messages.length === 0) {
                $chatMessages.html(`
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-robot" style="font-size: 3rem; color: #0d6efd;"></i>
                        </div>
                        <h4>How can I help you today?</h4>
                        <p class="text-muted">Ask me about your health, medications, or any concerns you have.</p>
                    </div>
                `);
                return;
            }
            
            let html = '';
            messages.forEach(msg => {
                const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const isBot = msg.message_type === 'bot';
                const messageClass = isBot ? 'message-bot' : 'message-user';
                const avatar = isBot 
                    ? '<i class="bi bi-robot me-2"></i>'
                    : '<i class="bi bi-person me-2"></i>';
                
                html += `
                    <div class="message ${messageClass} d-flex align-items-start">
                        <div class="me-2">${avatar}</div>
                        <div>
                            <div>${msg.content.replace(/\n/g, '<br>')}</div>
                            <div class="message-time">${time}</div>
                        </div>
                    </div>
                `;
            });
            
            $chatMessages.html(html);
            scrollToBottom();
        }
        
        // Show typing indicator
        function showTypingIndicator() {
            const $typingIndicator = `
                <div class="typing-indicator" id="typingIndicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            `;
            
            $('#chatMessages').append($typingIndicator);
            scrollToBottom();
        }
        
        // Hide typing indicator
        function hideTypingIndicator() {
            $('#typingIndicator').remove();
        }
        
        // Scroll chat to bottom
        function scrollToBottom() {
            const container = document.querySelector('.chat-messages');
            container.scrollTop = container.scrollHeight;
        }
        
        // Show alert message
        function showAlert(message, type = 'info') {
            const alert = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Add alert before the main content
            $('main').prepend(alert);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);
        }
        
        // Handle new chat button click
        $('#newChatBtn').on('click', function() {
            if (isProcessing) return;
            
            isProcessing = true;
            showTypingIndicator();
            
            $.ajax({
                url: 'api/chatbot.php?action=start_session',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    hideTypingIndicator();
                    isProcessing = false;
                    
                    if (response.success) {
                        currentSessionId = response.session_id;
                        $('#currentSessionId').val(currentSessionId);
                        $('#chatTitle').text(response.title || 'New Chat');
                        
                        // Clear chat messages
                        $('#chatMessages').html(`
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="bi bi-robot" style="font-size: 3rem; color: #0d6efd;"></i>
                                </div>
                                <h4>How can I help you today?</h4>
                                <p class="text-muted">Ask me about your health, medications, or any concerns you have.</p>
                            </div>
                        `);
                        
                        // Reload sessions to update the list
                        loadSessions();
                    } else {
                        showAlert(response.message || 'Failed to start new chat', 'danger');
                    }
                },
                error: function() {
                    hideTypingIndicator();
                    isProcessing = false;
                    showAlert('Error connecting to server', 'danger');
                }
            });
        });
        
        // Handle send message form submission
        $('#messageForm').on('submit', function(e) {
            e.preventDefault();
            
            const message = $('#userMessage').val().trim();
            if (!message || isProcessing) return;
            
            // If no active session, create one first
            if (!currentSessionId) {
                $('#newChatBtn').click();
                // Wait a bit for the session to be created
                setTimeout(() => {
                    sendMessage(message);
                }, 500);
                return;
            }
            
            sendMessage(message);
        });
        
        // Send message to server
        function sendMessage(message) {
            isProcessing = true;
            
            // Add user message to chat
            const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const userMessageHtml = `
                <div class="message message-user d-flex align-items-start">
                    <div class="me-2"><i class="bi bi-person"></i></div>
                    <div>
                        <div>${message.replace(/\n/g, '<br>')}</div>
                        <div class="message-time">${time}</div>
                    </div>
                </div>
            `;
            
            // If this is the first message in a new chat, clear the welcome message
            if ($('#chatMessages .message').length === 0) {
                $('#chatMessages').html('');
            }
            
            $('#chatMessages').append(userMessageHtml);
            scrollToBottom();
            
            // Show typing indicator
            showTypingIndicator();
            
            // Clear input
            $('#userMessage').val('');
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
