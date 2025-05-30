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
$query = "SELECT first_name, last_name, email, profile_image FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get healthcare providers for the new message modal
$providers_query = "SELECT id, first_name, last_name, role FROM users WHERE role IN ('doctor', 'nurse')";
$providers_stmt = $db->prepare($providers_query);
$providers_stmt->execute();
$healthcare_providers = $providers_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - HealthAssist Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-light: #eef2ff;
            --text-primary: #1a202c;
            --text-secondary: #4a5568;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
        }
        
        body {
            background-color: var(--bg-light);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
        }
        
        .message-container {
            height: 500px;
            overflow-y: auto;
            padding: 20px;
        }
        
        .message {
            margin-bottom: 15px;
            max-width: 70%;
        }
        
        .message-sent {
            margin-left: auto;
        }
        
        .message-received {
            margin-right: auto;
        }
        
        .message-bubble {
            padding: 10px 15px;
            border-radius: 18px;
            position: relative;
        }
        
        .message-sent .message-bubble {
            background-color: var(--primary-color);
            color: white;
            border-top-right-radius: 4px;
        }
        
        .message-received .message-bubble {
            background-color: white;
            border: 1px solid var(--border-color);
            border-top-left-radius: 4px;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 4px;
        }
        
        .conversation-item {
            padding: 10px 15px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .conversation-item:hover, .conversation-item.active {
            background-color: var(--primary-light);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Include the sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="content">
                <div class="page-header">
                    <h1>Messages</h1>
                    <p>Communicate with your healthcare providers</p>
                </div>
                
                <div class="container-fluid p-4">
                    <div class="row">
                        <!-- Conversations List -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Conversations</h6>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                                        <i class="fas fa-plus"></i> New
                                    </button>
                                </div>
                                <div class="list-group list-group-flush">
                                    <!-- Conversation items will be loaded here by Firebase -->
                                <div id="conversationsList" class="list-group list-group-flush">
                                    <div class="text-center p-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 mb-0">Loading conversations...</p>
                                    </div>
                                </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Messages Area -->
                        <div class="col-md-8">
                            <div class="card h-100">
                                <div class="card-header d-flex align-items-center">
                                    <img id="conversationAvatar" src="" 
                                         class="rounded-circle me-3" width="40" height="40" alt="">
                                    <div>
                                        <h6 class="mb-0" id="conversationName">Select a conversation</h6>
                                        <small class="text-muted" id="conversationStatus">Offline</small>
                                    </div>
                                    <div class="ms-auto">
                                        <button class="btn btn-sm btn-light">
                                            <i class="fas fa-phone"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light">
                                            <i class="fas fa-video"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Messages -->
                                <div class="message-container" id="messagesContainer">
                                    <div class="text-center p-4">
                                        <p class="text-muted">Select a conversation to view messages</p>
                                    </div>
                                </div>
                                
                                <!-- Message Input -->
                                <div class="card-footer bg-white">
                                    <form id="messageForm" class="d-flex">
                                        <input type="hidden" id="currentConversationId" value="">
                                        <input type="hidden" id="recipientId" value="">
                                        <button type="button" class="btn btn-light border me-2" title="Attach file" disabled>
                                            <i class="fas fa-paperclip"></i>
                                        </button>
                                        <input type="text" id="messageInput" class="form-control" placeholder="Type a message..." disabled>
                                        <button type="submit" class="btn btn-primary ms-2" disabled>
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- New Message Modal -->
    <div class="modal fade" id="newMessageModal" tabindex="-1" aria-labelledby="newMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newMessageModalLabel">New Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newConversationForm">
                        <div class="mb-3">
                            <label for="recipient" class="form-label">To:</label>
                            <select class="form-select" id="recipient" required>
                                <option value="" selected disabled>Select a healthcare provider</option>
                                <?php foreach ($healthcare_providers as $provider): ?>
                                    <option value="<?php echo $provider['id']; ?>">
                                        <?php echo htmlspecialchars($provider['first_name'] . ' ' . $provider['last_name'] . ' (' . $provider['role'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="initialMessage" class="form-label">Message:</label>
                            <textarea class="form-control" id="initialMessage" rows="3" placeholder="Type your message here..." required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="startConversationBtn">Send Message</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Load Firebase SDKs first -->
    <script src="https://www.gstatic.com/firebasejs/9.6.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.0/firebase-database-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.0/firebase-auth-compat.js"></script>
    
    <!-- Load Firebase Config -->
    <script src="firebase-config.js"></script>
    
    <!-- Load Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
    
    <!-- Load Firebase Service -->
    <script type="module" src="js/firebase-service.js"></script>
    
    <!-- Error Display -->
    <div id="firebaseError" class="alert alert-danger d-none position-fixed top-0 end-0 m-3" role="alert" style="z-index: 1100;">
        <strong>Firebase Error:</strong> <span id="firebaseErrorMessage"></span>
    </div>
    
    <!-- Main App JS -->
    <script type="module">
        // Debug: Log module loading
        console.log('Loading Firebase service module...');
        
        // Show error function
        function showError(message) {
            console.error(message);
            const errorDiv = document.getElementById('firebaseError');
            const errorMessage = document.getElementById('firebaseErrorMessage');
            if (errorDiv && errorMessage) {
                errorMessage.textContent = message;
                errorDiv.classList.remove('d-none');
                // Hide error after 10 seconds
                setTimeout(() => {
                    errorDiv.classList.add('d-none');
                }, 10000);
            }
        }
        
        // Try to import Firebase service with error handling
        let firebaseService;
        try {
            import('./js/firebase-service.js')
                .then(module => {
                    firebaseService = module.firebaseService;
                    console.log('Firebase service loaded successfully');
                    initApp();
                })
                .catch(error => {
                    showError('Failed to load Firebase service: ' + error.message);
                    console.error('Firebase service import error:', error);
                });
        } catch (error) {
            showError('Error initializing Firebase: ' + error.message);
            console.error('Firebase initialization error:', error);
        }
        
        // DOM Elements
        const messageContainer = document.querySelector('.message-container');
        const messageForm = document.querySelector('#messageForm');
        const messageInput = document.querySelector('#messageInput');
        const conversationsList = document.querySelector('#conversationsList'); // Fixed selector
        const newMessageForm = document.querySelector('#newConversationForm'); // Fixed selector
        const recipientSelect = document.querySelector('#recipient');
        const newMessageText = document.querySelector('#initialMessage');
        const startConversationBtn = document.querySelector('#startConversationBtn');
        
        // Current user data
        const currentUser = {
            id: '<?php echo $user_id; ?>',
            name: '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>',
            avatar: '<?php echo !empty($user['profile_image']) ? 'uploads/' . htmlspecialchars($user['profile_image']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . '+' . $user['last_name']) . '&background=random'; ?>'
        };
        
        // Current conversation state
        let currentConversationId = null;
        let currentRecipient = null;
        
        // Initialize the app
        function initApp() {
            console.log('Initializing app with user:', currentUser);
            
            // Check if Firebase is available
            if (typeof firebase === 'undefined') {
                showError('Firebase SDK not loaded. Check your internet connection.');
                return;
            }
            
            // Initialize Firebase
            try {
                console.log('Initializing Firebase...');
                initializeFirebase();
                setupEventListeners();
                scrollToBottom();
            } catch (error) {
                showError('Failed to initialize Firebase: ' + error.message);
                console.error('Firebase init error:', error);
            }
        }
        
        // Initialize Firebase
        function initializeFirebase() {
            console.log('Setting up Firebase auth state listener...');
            
            try {
                firebaseService.initAuthStateListener((user) => {
                    console.log('Auth state changed:', user ? 'User signed in' : 'User signed out');
                    
                    if (user) {
                        // User is signed in
                        console.log('User is signed in:', user.uid);
                        firebaseService.updateUserStatus(true)
                            .then(() => {
                                console.log('User status updated to online');
                                loadConversations();
                            })
                            .catch(error => {
                                showError('Error updating user status: ' + error.message);
                                console.error('Update status error:', error);
                            });
                    } else {
                        // User is signed out - redirect to login
                        console.log('User is signed out, redirecting to login...');
                        window.location.href = 'login.php';
                    }
                });
                
                // Set up presence system
                setupPresence();
            } catch (error) {
                showError('Error in Firebase initialization: ' + error.message);
                console.error('Firebase init error:', error);
            }
        }
        
        // Set up presence tracking
        function setupPresence() {
            // This is handled in the Firebase service
        }
        
        // Set up event listeners
        function setupEventListeners() {
            // Send message form
            if (messageForm) {
                messageForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const message = messageInput.value.trim();
                    if (message && currentRecipient) {
                        sendMessage(message);
                        messageInput.value = '';
                    }
                });
            }
            
            // New message form
            if (newMessageForm) {
                newMessageForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const recipientId = recipientSelect.value;
                    const message = newMessageText.value.trim();
                    
                    if (recipientId && message) {
                        createNewConversation(recipientId, message);
                        // Close the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('newMessageModal'));
                        modal.hide();
                    }
                });
            }
            
            // Populate recipient select
            populateRecipientSelect();
        }
        
        // Load conversations for the current user
        function loadConversations() {
            firebaseService.getConversations((conversations) => {
                renderConversations(conversations);
                
                // If there's a conversation ID in the URL, load it
                const urlParams = new URLSearchParams(window.location.search);
                const conversationId = urlParams.get('conversation_id');
                
                if (conversationId) {
                    loadConversation(conversationId);
                } else if (conversations.length > 0) {
                    // Load the first conversation by default
                    loadConversation(conversations[0].id);
                }
            });
        }
        
        // Load a specific conversation
        function loadConversation(conversationId) {
            currentConversationId = conversationId;
            
            // Update URL
            window.history.pushState({}, '', `?conversation_id=${conversationId}`);
            
            // Update active conversation in the list
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.toggle('active', item.dataset.conversationId === conversationId);
            });
            
            // Clear current messages
            messageContainer.innerHTML = '';
            
            // Load messages for this conversation
            firebaseService.getMessages(conversationId, (messages) => {
                renderMessages(messages);
                scrollToBottom();
            });
        }
        
        // Send a new message
        async function sendMessage(text) {
            if (!currentConversationId || !currentRecipient) return;
            
            try {
                await firebaseService.sendMessage(currentRecipient.id, text);
                scrollToBottom();
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again.');
            }
        }
        
        // Create a new conversation
        async function createNewConversation(recipientId, initialMessage) {
            try {
                // First create the conversation
                const result = await firebaseService.createConversation([recipientId]);
                
                if (result.success) {
                    // Then send the first message
                    currentConversationId = result.conversationId;
                    currentRecipient = {
                        id: recipientId,
                        name: recipientSelect.options[recipientSelect.selectedIndex].text
                    };
                    
                    await sendMessage(initialMessage);
                    
                    // Reload conversations to show the new one
                    loadConversations();
                    
                    // Load the new conversation
                    loadConversation(result.conversationId);
                }
            } catch (error) {
                console.error('Error creating conversation:', error);
                alert('Failed to create conversation. Please try again.');
            }
        }
        
        // Render conversations list
        function renderConversations(conversations) {
            if (!conversationsList) return;
            
            conversationsList.innerHTML = '';
            
            if (conversations.length === 0) {
                conversationsList.innerHTML = `
                    <div class="text-center p-4 text-muted">
                        <p>No conversations yet</p>
                    </div>
                `;
                return;
            }
            
            conversations.forEach(conversation => {
                // Get the other participant's ID
                const participantId = Object.keys(conversation.participants).find(
                    id => id !== currentUser.id
                );
                
                // In a real app, you would fetch the participant's details
                const participantName = 'Dr. ' + participantId.substring(0, 8); // Placeholder
                const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(participantName)}&background=random`;
                
                const lastMessageTime = conversation.lastMessageTime 
                    ? formatTime(conversation.lastMessageTime) 
                    : '';
                
                const isActive = conversation.id === currentConversationId;
                
                const conversationElement = document.createElement('div');
                conversationElement.className = `conversation-item ${isActive ? 'active' : ''}`;
                conversationElement.dataset.conversationId = conversation.id;
                conversationElement.innerHTML = `
                    <div class="d-flex align-items-center">
                        <img src="${avatarUrl}" class="rounded-circle me-3" width="40" height="40" alt="${participantName}">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">${participantName}</h6>
                                <small class="text-muted">${lastMessageTime}</small>
                            </div>
                            <p class="mb-0 text-truncate" style="max-width: 200px;">
                                ${conversation.lastMessage || 'No messages yet'}
                            </p>
                        </div>
                    </div>
                `;
                
                conversationElement.addEventListener('click', () => {
                    currentRecipient = { id: participantId, name: participantName };
                    loadConversation(conversation.id);
                });
                
                conversationsList.appendChild(conversationElement);
            });
        }
        
        // Render messages in the conversation
        function renderMessages(messages) {
            if (!messageContainer) return;
            
            messageContainer.innerHTML = '';
            
            if (messages.length === 0) {
                messageContainer.innerHTML = `
                    <div class="text-center my-5">
                        <p class="text-muted">No messages yet. Say hello!</p>
                    </div>
                `;
                return;
            }
            
            let currentDate = null;
            
            messages.forEach(message => {
                const messageDate = new Date(message.timestamp).toDateString();
                const isCurrentUser = message.senderId === currentUser.id;
                
                // Add date separator if needed
                if (currentDate !== messageDate) {
                    currentDate = messageDate;
                    const dateElement = document.createElement('div');
                    dateElement.className = 'text-center my-3';
                    dateElement.innerHTML = `
                        <span class="badge bg-light text-muted">
                            ${formatDate(message.timestamp)}
                        </span>
                    `;
                    messageContainer.appendChild(dateElement);
                }
                
                // Create message element
                const messageElement = document.createElement('div');
                messageElement.className = `message ${isCurrentUser ? 'message-sent' : 'message-received'}`;
                
                messageElement.innerHTML = `
                    ${!isCurrentUser ? `
                        <small class="d-block mb-1" style="color: var(--text-secondary);">
                            ${currentRecipient?.name || 'User'}
                        </small>
                    ` : ''}
                    <div class="message-bubble">
                        ${message.text}
                        <div class="message-time">
                            ${formatTime(message.timestamp)}
                            ${isCurrentUser ? `
                                <i class="fas fa-check${message.read ? '-double text-primary' : ''} ms-1"></i>
                            ` : ''}
                        </div>
                    </div>
                `;
                
                messageContainer.appendChild(messageElement);
            });
            
            scrollToBottom();
        }
        
        // Populate recipient select dropdown
        function populateRecipientSelect() {
            if (!recipientSelect) return;
            
            // Clear existing options
            recipientSelect.innerHTML = '<option selected disabled>Select a healthcare provider</option>';
            
            // Add healthcare providers from PHP
            const providers = <?php echo json_encode($healthcare_providers); ?>;
            
            providers.forEach(provider => {
                const option = document.createElement('option');
                option.value = provider.id;
                option.textContent = `${provider.first_name} ${provider.last_name} (${provider.role})`;
                recipientSelect.appendChild(option);
            });
        }
        
        // Helper function to format time
        function formatTime(timestamp) {
            if (!timestamp) return '';
            
            const date = new Date(timestamp);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        
        // Helper function to format date
        function formatDate(timestamp) {
            if (!timestamp) return '';
            
            const date = new Date(timestamp);
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            
            if (date.toDateString() === today.toDateString()) {
                return 'Today';
            } else if (date.toDateString() === yesterday.toDateString()) {
                return 'Yesterday';
            } else {
                return date.toLocaleDateString();
            }
        }
        
        // Helper function to scroll to bottom of messages
        function scrollToBottom() {
            if (messageContainer) {
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
        }
    </script>
    
    <?php include_once "includes/footer.php"; ?>
</body>
</html>
