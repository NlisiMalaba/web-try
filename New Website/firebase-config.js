// Debug logging
console.log('Loading Firebase configuration...');

try {
    // Your web app's Firebase configuration
    // Replace these values with your actual Firebase project configuration
    // You can find these in your Firebase Console -> Project Settings -> Your apps
    const firebaseConfig = {
        apiKey: "REPLACE_WITH_YOUR_API_KEY",
        authDomain: "REPLACE_WITH_YOUR_PROJECT_ID.firebaseapp.com",
        databaseURL: "https://REPLACE_WITH_YOUR_PROJECT_ID.firebaseio.com",
        projectId: "REPLACE_WITH_YOUR_PROJECT_ID",
        storageBucket: "REPLACE_WITH_YOUR_PROJECT_ID.appspot.com",
        messagingSenderId: "REPLACE_WITH_YOUR_SENDER_ID",
        appId: "REPLACE_WITH_YOUR_APP_ID"
    };

    console.log('Initializing Firebase with config:', {
        ...firebaseConfig,
        apiKey: firebaseConfig.apiKey ? '***' + firebaseConfig.apiKey.slice(-4) : 'NOT SET',
        appId: firebaseConfig.appId ? '***' + firebaseConfig.appId.slice(-4) : 'NOT SET'
    });

    // Initialize Firebase
    const app = firebase.initializeApp(firebaseConfig);
    console.log('Firebase initialized successfully');

    // Initialize Firebase services
    const auth = firebase.auth();
    console.log('Firebase Auth initialized');
    
    const database = firebase.database();
    console.log('Firebase Realtime Database initialized');

    // Test database connection
    if (database) {
        console.log('Firebase database reference is available');
    } else {
        console.error('Failed to initialize Firebase Database');
    }

    // Export the Firebase services
    export { auth, database };
    
} catch (error) {
    console.error('Error initializing Firebase:', error);
    throw new Error('Failed to initialize Firebase: ' + error.message);
}
