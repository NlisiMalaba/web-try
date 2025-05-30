// Debug logging
console.log('Firebase service module is being loaded');

// Import Firebase services
import { auth, database } from '../firebase-config.js';

class FirebaseService {
    constructor() {
        console.log('Initializing FirebaseService...');
        
        this.currentUser = null;
        this.conversationsRef = null;
        this.messagesRef = null;
        this.usersRef = null;
        this.currentConversationId = null;
        
        try {
            // Initialize Firebase references
            this.conversationsRef = database.ref('conversations');
            this.messagesRef = database.ref('messages');
            this.usersRef = database.ref('users');
            console.log('Firebase references initialized');
        } catch (error) {
            console.error('Error initializing Firebase references:', error);
            throw new Error('Failed to initialize Firebase service');
        }
    }

    // Initialize Firebase Auth state listener
    initAuthStateListener(callback) {
        auth.onAuthStateChanged((user) => {
            this.currentUser = user;
            if (callback) callback(user);
        });
    }

    // Sign in with email/password
    async signIn(email, password) {
        try {
            const userCredential = await auth.signInWithEmailAndPassword(email, password);
            return { success: true, user: userCredential.user };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    // Sign out
    async signOut() {
        try {
            await auth.signOut();
            return { success: true };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    // Get all conversations for the current user
    getConversations(callback) {
        if (!this.currentUser) return;
        
        return this.conversationsRef
            .orderByChild(`participants/${this.currentUser.uid}`)
            .equalTo(true)
            .on('value', (snapshot) => {
                const conversations = [];
                snapshot.forEach((childSnapshot) => {
                    conversations.push({
                        id: childSnapshot.key,
                        ...childSnapshot.val()
                    });
                });
                if (callback) callback(conversations);
            });
    }

    // Get messages for a specific conversation
    getMessages(conversationId, callback) {
        if (!conversationId) return;
        this.currentConversationId = conversationId;
        
        return this.messagesRef
            .child(conversationId)
            .orderByChild('timestamp')
            .on('value', (snapshot) => {
                const messages = [];
                snapshot.forEach((childSnapshot) => {
                    messages.push({
                        id: childSnapshot.key,
                        ...childSnapshot.val()
                    });
                });
                if (callback) callback(messages);
            });
    }

    // Send a new message
    async sendMessage(recipientId, text) {
        if (!this.currentUser || !this.currentConversationId) return;
        
        const messageData = {
            senderId: this.currentUser.uid,
            recipientId,
            text,
            timestamp: firebase.database.ServerValue.TIMESTAMP,
            read: false
        };

        try {
            // Add message to the conversation
            const newMessageRef = this.messagesRef
                .child(this.currentConversationId)
                .push();
            
            await newMessageRef.set(messageData);
            
            // Update last message in conversation
            await this.conversationsRef
                .child(this.currentConversationId)
                .update({
                    lastMessage: text,
                    lastMessageTime: firebase.database.ServerValue.TIMESTAMP,
                    [`lastMessageFrom/${this.currentUser.uid}`]: true
                });

            return { success: true, messageId: newMessageRef.key };
        } catch (error) {
            console.error('Error sending message:', error);
            return { success: false, error: error.message };
        }
    }

    // Create a new conversation
    async createConversation(participantIds) {
        if (!this.currentUser) return;

        // Include current user in participants
        const allParticipants = [...new Set([...participantIds, this.currentUser.uid])];
        const participants = {};
        allParticipants.forEach(id => participants[id] = true);

        try {
            const newConversationRef = this.conversationsRef.push();
            
            await newConversationRef.set({
                createdAt: firebase.database.ServerValue.TIMESTAMP,
                participants,
                lastMessage: 'Conversation started',
                lastMessageTime: firebase.database.ServerValue.TIMESTAMP
            });

            return { 
                success: true, 
                conversationId: newConversationRef.key 
            };
        } catch (error) {
            console.error('Error creating conversation:', error);
            return { success: false, error: error.message };
        }
    }

    // Get user data
    async getUserData(userId) {
        try {
            const snapshot = await this.usersRef.child(userId).once('value');
            return snapshot.val();
        } catch (error) {
            console.error('Error getting user data:', error);
            return null;
        }
    }

    // Update user's online status
    updateUserStatus(online = true) {
        if (!this.currentUser) return;
        
        const status = {
            status: online ? 'online' : 'offline',
            lastChanged: firebase.database.ServerValue.TIMESTAMP
        };
        
        this.usersRef.child(`${this.currentUser.uid}/status`).set(status);
        
        // Set up presence system
        const userStatusDatabaseRef = database.ref('.info/connected');
        userStatusDatabaseRef.on('value', (snapshot) => {
            if (snapshot.val() === false) return;
            
            const userStatusRef = this.usersRef.child(`${this.currentUser.uid}/status`);
            
            // Set up presence system
            database.ref('.info/connected').on('value', (snapshot) => {
                if (snapshot.val() === false) return;
                
                // When we disconnect, update the status
                userStatusRef.onDisconnect().set({
                    status: 'offline',
                    lastChanged: firebase.database.ServerValue.TIMESTAMP
                }).then(() => {
                    // When we connect, update the status
                    userStatusRef.set({
                        status: 'online',
                        lastChanged: firebase.database.ServerValue.TIMESTAMP
                    });
                });
            });
        });
    }
}

export const firebaseService = new FirebaseService();
