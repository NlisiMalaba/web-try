-- Chatbot conversation sessions
CREATE TABLE IF NOT EXISTS chatbot_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    title VARCHAR(255) DEFAULT 'New Chat',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chatbot messages
CREATE TABLE IF NOT EXISTS chatbot_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    message_type ENUM('user', 'bot', 'system') NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    metadata JSON DEFAULT NULL,
    FOREIGN KEY (session_id) REFERENCES chatbot_sessions(session_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chronic disease knowledge base
CREATE TABLE IF NOT EXISTS disease_knowledge_base (
    id INT AUTO_INCREMENT PRIMARY KEY,
    disease_name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    symptoms TEXT,
    treatments TEXT,
    management_guidelines TEXT,
    emergency_signs TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_disease (disease_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User health profile for chatbot context
CREATE TABLE IF NOT EXISTS user_health_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    chronic_conditions JSON DEFAULT NULL,
    medications JSON DEFAULT NULL,
    allergies TEXT,
    recent_metrics JSON DEFAULT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
