/**
 * Health Assistant Chatbot
 * Handles all chat functionality including sending/receiving messages,
 * managing chat sessions, and UI interactions.
 */

// Initialize when document is ready
$(document).ready(function() {
    'use strict';
    
    // DOM Elements
    const $chatContainer = $('.chat-container');
    const $messageInput = $('#message-input');
    const $sendButton = $('#send-button');
    const $newChatBtn = $('#new-chat-btn');
    const $chatHistory = $('.chat-history');
    const $chatTitle = $('.chat-title');
    const $chatTitleInput = $('#chat-title-input');
    const $saveTitleBtn = $('#save-title-btn');
    const $cancelTitleBtn = $('#cancel-title-btn');
    const $editTitleBtn = $('.edit-title-btn');
    const $deleteChatBtn = $('.delete-chat-btn');
    const $toggleSidebar = $('#toggleSidebar');
    const $chatSidebar = $('#chatSidebar');
    
    // State variables
    let currentSessionId = '';
    let isProcessing = false;
    let isSidebarVisible = true;
    let messageQueue = [];
    let isTyping = false;

    // Initialize chat
    function initChat() {
        // Set up event listeners first
        setupEventListeners();
        
        // Check for session ID in URL
        const urlParams = new URLSearchParams(window.location.search);
        const sessionId = urlParams.get('session');
        
        // Load chat sessions and then either the specified session or start a new one
        loadChatSessions().then(() => {
            if (sessionId) {
                loadChatSession(sessionId);
            } else {
                startNewChat();
            }
        }).catch(error => {
            console.error('Error initializing chat:', error);
            showError('Failed to initialize chat. Please refresh the page.');
        });
        
        // Auto-resize textarea as user types
        autoResizeTextarea();
        
        // Focus the message input
        $messageInput.focus();
    }

    // Set up all event listeners for the chat interface
    function setupEventListeners() {
        // Send message on button click
        $sendButton.on('click', sendMessage);
        
        // Handle message input
        $messageInput
            // Send message on Enter (but allow Shift+Enter for new lines)
            .on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            })
            // Enable/disable send button based on input
            .on('input', function() {
                $sendButton.prop('disabled', $(this).val().trim() === '');
            });
        
        // New chat button
        $newChatBtn.on('click', startNewChat);
        
        // Chat title management
        $saveTitleBtn.on('click', saveChatTitle);
        $cancelTitleBtn.on('click', cancelEditTitle);
        $chatTitleInput.on('keydown', function(e) {
            if (e.key === 'Enter') {
                saveChatTitle();
            } else if (e.key === 'Escape') {
                cancelEditTitle();
            }
        });
        
        // Toggle sidebar on mobile
        $toggleSidebar.on('click', toggleSidebar);
        
        // Handle window resize
        $(window).on('resize', handleResize);
        
        // Handle beforeunload to prevent accidental navigation
        $(window).on('beforeunload', function() {
            if (isProcessing) {
                return 'You have a message in progress. Are you sure you want to leave?';
            }
        });
    }

    // Start a new chat session
    function startNewChat() {
        // Show loading state
        $chatContainer.html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        
        // Clear input
        $messageInput.val('');
        
        // Reset chat title
        $chatTitle.text('New Chat');
        
        // Call API to create a new session
        $.ajax({
            url: 'api/chatbot.php?action=start_session',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    currentSessionId = response.session_id;
                    $chatContainer.html(''); // Clear loading state
                    updateUrlWithSession(currentSessionId);
                    loadChatSessions(); // Refresh chat history
                } else {
                    showError('Failed to start a new chat session');
                }
            },
            error: function() {
                showError('Network error. Please try again.');
            }
        });
    }

    // Send a message to the chatbot
    function sendMessage(customMessage) {
        const message = customMessage || $messageInput.val().trim();
        
        // Validate input
        if (!message || isProcessing) return;
        
        // Add message to queue if another message is being processed
        if (isProcessing) {
            messageQueue.push(message);
            return;
        }
        
        // Add user message to chat
        addMessage('user', message);
        
        // Clear input and disable while processing
        if (!customMessage) {
            $messageInput.val('');
            autoResizeTextarea();
        }
        
        isProcessing = true;
        $sendButton.prop('disabled', true);
        
        // Add typing indicator
        showTypingIndicator();
        
        // Call API to send message
        $.ajax({
            url: 'api/chatbot.php?action=send_message',
            type: 'POST',
            dataType: 'json',
            data: JSON.stringify({
                session_id: currentSessionId,
                message: message
            }),
            contentType: 'application/json',
            success: function(response) {
                removeTypingIndicator();
                
                if (response.success) {
                    // Process any quick replies if present
                    if (response.quick_replies && response.quick_replies.length > 0) {
                        addQuickReplies(response.quick_replies);
                    }
                    
                    // Add bot response to chat
                    addMessage('bot', response.response);
                    
                    // Update chat title if this is the first message
                    if ($chatTitle.text() === 'New Chat' && response.title) {
                        updateChatTitle(response.title);
                    }
                    
                    // Update chat sessions list
                    loadChatSessions();
                } else {
                    showError('Failed to send message');
                }
            },
            error: function() {
                showError('Network error. Please try again.');
            },
            complete: function() {
                isProcessing = false;
                $sendButton.prop('disabled', false);
                
                // Process next message in queue
                if (messageQueue.length > 0) {
                    sendMessage(messageQueue.shift());
                }
            }
        });
    }

    // Add a message to the chat UI
    function addMessage(sender, content, isHistory = false) {
        const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const messageId = 'msg-' + Date.now();
        
        const $message = $(`
            <div id="${messageId}" class="message ${sender}-message" data-sender="${sender}">
                <div class="message-avatar">
                    ${sender === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>'}
                </div>
                <div class="message-content-wrapper">
                    <div class="message-content">${formatMessage(content)}</div>
                    <div class="message-time">${timestamp}</div>
                </div>
            </div>
        `);
        
        $chatContainer.append($message);
        
        if (!isHistory) {
            scrollToBottom();
            $message.hide().fadeIn(300);
        }
        
        return $message;
    }

    // Format message content (convert markdown-like syntax to HTML)
    function formatMessage(content) {
        if (!content) return '';

        // Convert **bold** to <strong>bold</strong>
        content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // Convert *italic* to <em>italic</em>
        content = content.replace(/\*(.*?)\*/g, '<em>$1</em>');
        
        // Convert - list items to <ul><li>items</li></ul>
        content = content.replace(/^\s*-\s*(.+)$/gm, '<li>$1</li>');
        content = content.replace(/<li>.*<\/li>/gs, function(match) {
            return match.includes('<li>') && !match.includes('<ul>') ? '<ul>' + match + '</ul>' : match;
        });
        
        // Convert line breaks to <br> for non-list items
        content = content.replace(/\n(?!<\/?(ul|li|ol)>)/g, '<br>');
        
        // Convert URLs to clickable links
        content = content.replace(/(https?:\/\/\S+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
        
        // Convert @mentions to clickable user links
        content = content.replace(/@([\w]+)/g, '<a href="#" class="mention">@$1</a>');
        
        return content;
    }

    // Show typing indicator
    function showTypingIndicator() {
        if (!isTyping) {
            const $typingIndicator = $(`
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            `);
            $chatContainer.append($typingIndicator);
            scrollToBottom();
            isTyping = true;
        }
    }

    // Remove typing indicator
    function removeTypingIndicator() {
        if (isTyping) {
            $('.typing-indicator').fadeOut(200, function() {
                $(this).remove();
            });
            isTyping = false;
        }
    }

    // Process messages in the queue
    function processMessageQueue() {
        if (messageQueue.length > 0 && !isProcessing) {
            const message = messageQueue.shift();
            sendMessage(message);
        }
    }

    // Auto-resize textarea as user types
    function autoResizeTextarea() {
        $messageInput.css('height', 'auto');
        $messageInput.css('height', $messageInput[0].scrollHeight + 'px');
    }

    // Scroll chat to bottom
    function scrollToBottom() {
        $chatContainer.animate({
            scrollTop: $chatContainer[0].scrollHeight
        }, 300);
    }

    // Show error message
    function showError(message) {
        const $error = $(`
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        
        $chatContainer.prepend($error);
        scrollToBottom();
        
        setTimeout(() => {
            $error.alert('close');
        }, 5000);
    }

    // Update URL with session ID
    function updateUrlWithSession(sessionId) {
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('session', sessionId);
        window.history.pushState({}, '', newUrl);
    }

    // Initialize the chat when the page loads
    initChat();
});
