<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

// Verify user is logged in
$user = verifyToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$userId = $user['id'];

try {
    switch ($action) {
        case 'start_session':
            $sessionId = uniqid('chat_', true);
            $title = 'Chat ' . date('M j, Y');
            
            // Create new chat session
            $sessionData = [
                'user_id' => $userId,
                'session_id' => $sessionId,
                'title' => $title,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $sessionId = insert('chatbot_sessions', $sessionData);
            
            if ($sessionId) {
                $response = [
                    'success' => true,
                    'session_id' => $sessionData['session_id'],
                    'title' => $title
                ];
            } else {
                throw new Exception('Failed to create chat session');
            }
            break;
            
        case 'send_message':
            $sessionId = $input['session_id'] ?? '';
            $message = trim($input['message'] ?? '');
            
            if (empty($sessionId) || empty($message)) {
                throw new Exception('Missing required fields');
            }
            
            // Verify the session belongs to the user
            $session = fetchOne(
                "SELECT id FROM chatbot_sessions WHERE session_id = :session_id AND user_id = :user_id",
                [':session_id' => $sessionId, ':user_id' => $userId]
            );
            
            if (!$session) {
                throw new Exception('Invalid session');
            }
            
            // Save user message
            $messageData = [
                'session_id' => $sessionId,
                'message_type' => 'user',
                'content' => $message,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if (!insert('chatbot_messages', $messageData)) {
                throw new Exception('Failed to save message');
            }
            
            // Process message and generate response
            $botResponse = processChatbotMessage($userId, $sessionId, $message);
            
            // Save bot response
            $botMessageData = [
                'session_id' => $sessionId,
                'message_type' => 'bot',
                'content' => $botResponse,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if (!insert('chatbot_messages', $botMessageData)) {
                throw new Exception('Failed to save bot response');
            }
            
            // Update session timestamp
            update('chatbot_sessions', 
                ['updated_at' => date('Y-m-d H:i:s')], 
                'session_id = :session_id', 
                [':session_id' => $sessionId]
            );
            
            $response = [
                'success' => true,
                'response' => $botResponse,
                'session_id' => $sessionId
            ];
            break;
            
        case 'get_sessions':
            $sessions = fetchAll(
                "SELECT session_id, title, created_at, updated_at 
                 FROM chatbot_sessions 
                 WHERE user_id = :user_id 
                 ORDER BY updated_at DESC",
                [':user_id' => $userId]
            );
            
            $response = [
                'success' => true,
                'sessions' => $sessions
            ];
            break;
            
        case 'get_messages':
            $sessionId = $_GET['session_id'] ?? '';
            
            if (empty($sessionId)) {
                throw new Exception('Session ID is required');
            }
            
            // Verify session belongs to user
            $session = fetchOne(
                "SELECT id FROM chatbot_sessions WHERE session_id = :session_id AND user_id = :user_id",
                [':session_id' => $sessionId, ':user_id' => $userId]
            );
            
            if (!$session) {
                throw new Exception('Session not found or access denied');
            }
            
            $messages = fetchAll(
                "SELECT id, message_type, content, created_at 
                 FROM chatbot_messages 
                 WHERE session_id = :session_id 
                 ORDER BY created_at ASC",
                [':session_id' => $sessionId]
            );
            
            $response = [
                'success' => true,
                'messages' => $messages
            ];
            break;
            
        case 'update_session':
            $sessionId = $input['session_id'] ?? '';
            $title = trim($input['title'] ?? '');
            
            if (empty($sessionId) || empty($title)) {
                throw new Exception('Missing required fields');
            }
            
            // Verify session belongs to user
            $session = fetchOne(
                "SELECT id FROM chatbot_sessions WHERE session_id = :session_id AND user_id = :user_id",
                [':session_id' => $sessionId, ':user_id' => $userId]
            );
            
            if (!$session) {
                throw new Exception('Session not found or access denied');
            }
            
            // Update session title
            $updated = update('chatbot_sessions', 
                ['title' => $title],
                'session_id = :session_id',
                [':session_id' => $sessionId]
            );
            
            if ($updated === false) {
                throw new Exception('Failed to update session');
            }
            
            $response = [
                'success' => true,
                'message' => 'Session updated',
                'title' => $title
            ];
            break;
            
        case 'delete_session':
            $sessionId = $input['session_id'] ?? '';
            
            if (empty($sessionId)) {
                throw new Exception('Session ID is required');
            }
            
            // Verify session belongs to user and delete
            $deleted = delete(
                'chatbot_sessions',
                'session_id = :session_id AND user_id = :user_id',
                [':session_id' => $sessionId, ':user_id' => $userId]
            );
            
            if ($deleted === false) {
                throw new Exception('Failed to delete session');
            }
            
            // Messages will be deleted automatically due to foreign key constraint
            
            $response = [
                'success' => true,
                'message' => 'Session deleted'
            ];
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    error_log('Chatbot API Error: ' . $e->getMessage());
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);

/**
 * Process user message and generate bot response
 * This is a simplified version - in production, integrate with an AI service like OpenAI's GPT
 * 
 * @param int $userId User ID
 * @param string $sessionId Chat session ID
 * @param string $message User message
 * @return string Bot response
 */
function processChatbotMessage($userId, $sessionId, $message) {
    // Convert message to lowercase for easier processing
    $message = strtolower(trim($message));
    
    // Cache user's health conditions
    static $userConditions = [];
    if (!isset($userConditions[$userId])) {
        $conditions = fetchAll(
            "SELECT name, type FROM health_conditions WHERE user_id = :user_id",
            [':user_id' => $userId]
        );
        $userConditions[$userId] = $conditions;
    } else {
        $conditions = $userConditions[$userId];
    }
    
    $hasChronicCondition = !empty(array_filter($conditions, function($c) {
        return $c['type'] === 'chronic';
    }));
    
    // Cache disease knowledge base
    static $diseaseKnowledge = [];
    
    // Function to get cached disease knowledge
    function getDiseaseKnowledge($condition) {
        global $diseaseKnowledge;
        if (!isset($diseaseKnowledge[$condition])) {
            $knowledge = fetchOne(
                "SELECT management_guidelines 
                 FROM disease_knowledge_base 
                 WHERE LOWER(disease_name) LIKE :condition 
                 LIMIT 1",
                [':condition' => "%$condition%"]
            );
            $diseaseKnowledge[$condition] = $knowledge ?? null;
        }
        return $diseaseKnowledge[$condition];
    }
    
    // Check for greetings
    if (preg_match('/(hello|hi|hey|greetings|good\s(morning|afternoon|evening))\b/', $message)) {
        $greeting = "Hello! ";
        
        if ($hasChronicCondition) {
            $conditionNames = array_map(function($c) { 
                return strtolower($c['name']); 
            }, $conditions);
            
            if (!empty($conditionNames)) {
                $greeting .= "I see you're managing " . 
                    (count($conditionNames) > 1 ? 
                        implode(', ', array_slice($conditionNames, 0, -1)) . ' and ' . end($conditionNames) : 
                        $conditionNames[0]) . 
                    ". ";
            }
        }
        
        return $greeting . "I'm your health assistant. How can I help you today?";
    }
    
    // Check for medication-related questions
    if (preg_match('/(medication|prescription|drug|pill|medicine)/', $message)) {
        static $userMedications = [];
        if (!isset($userMedications[$userId])) {
            $medications = fetchAll(
                "SELECT name, dosage, frequency, start_date 
                 FROM medications 
                 WHERE user_id = :user_id AND (end_date IS NULL OR end_date >= CURDATE())
                 ORDER BY name",
                [':user_id' => $userId]
            );
            $userMedications[$userId] = $medications;
        } else {
            $medications = $userMedications[$userId];
        }
        
        if (empty($medications)) {
            return "I don't see any active medications in your profile. Would you like to add some?";
        }
        
        $response = "Here are your current medications:\n\n";
        foreach ($medications as $med) {
            $response .= "• **{$med['name']}**";
            if (!empty($med['dosage'])) $response .= " ({$med['dosage']})";
            if (!empty($med['frequency'])) $response .= ", {$med['frequency']}";
            if (!empty($med['start_date'])) {
                $startDate = date('M j, Y', strtotime($med['start_date']));
                $response .= " (since {$startDate})";
            }
            $response .= "\n";
        }
        
        $response .= "\nWould you like more information about any of these medications or help with medication reminders?";
        return $response;
    }
    
    // Check for symptom reporting
    if (preg_match('/(symptom|pain|ache|feel|experience|have|hurts|hurt|hurting|discomfort)/', $message)) {
        // Extract symptom details using regex patterns
        $symptom = '';
        $severity = '';
        $duration = '';
        
        // Try to extract symptom details
        if (preg_match('/(headache|migraine|nausea|dizziness|fatigue|shortness of breath|chest pain)/i', $message, $matches)) {
            $symptom = strtolower($matches[1]);
        }
        
        if (preg_match('/(mild|moderate|severe|extreme) (pain|discomfort)/i', $message, $matches)) {
            $severity = strtolower($matches[1]);
        } elseif (preg_match('/pain level.*?(\d+)/i', $message, $matches)) {
            $level = (int)$matches[1];
            if ($level >= 7) $severity = 'severe';
            elseif ($level >= 4) $severity = 'moderate';
            else $severity = 'mild';
        }
        
        if (preg_match('/(for|since) (\d+) (minute|hour|day|week|month|year)/i', $message, $matches)) {
            $duration = "{$matches[2]} {$matches[3]}" . ($matches[2] > 1 ? 's' : '');
        }
        
        // Build response based on extracted information
        $response = "I understand you're experiencing ";
        
        if ($symptom) {
            $response .= $severity ? "$severity $symptom" : $symptom;
        } else {
            $response .= $severity ? "$severity symptoms" : "some symptoms";
        }
        
        if ($duration) {
            $response .= " for the past $duration";
        }
        
        $response .= ". ";
        
        // Add appropriate guidance based on symptom severity
        if ($severity === 'severe' || preg_match('/(chest pain|difficulty breathing|severe pain)/i', $message)) {
            $response .= "This sounds serious. Please seek immediate medical attention if you haven't already. " .
                        "Would you like me to help you find the nearest urgent care facility or contact emergency services?";
        } else {
            $response .= "Could you tell me more about what you're feeling and when it started? This will help me provide better guidance.\n\n" .
                        "You might want to track this symptom in your health journal. Would you like me to help you log it?";
        }
        
        return $response;
    }
    
    // Check for appointment scheduling
    if (preg_match('/(appointment|schedule|see (a|my) (doctor|provider|physician)|visit|check.?up)/i', $message)) {
        static $userAppointments = [];
        if (!isset($userAppointments[$userId])) {
            $upcomingAppointments = fetchAll(
                "SELECT a.appointment_date, a.start_time, p.name as provider_name, p.specialty 
                 FROM appointments a 
                 JOIN providers p ON a.provider_id = p.id 
                 WHERE a.user_id = :user_id AND a.appointment_date >= CURDATE() 
                 ORDER BY a.appointment_date, a.start_time 
                 LIMIT 3",
                [':user_id' => $userId]
            );
            $userAppointments[$userId] = $upcomingAppointments;
        } else {
            $upcomingAppointments = $userAppointments[$userId];
        }
        
        $response = "I can help you with appointments. ";
        
        if (!empty($upcomingAppointments)) {
            $response .= "Here are your upcoming appointments:\n\n";
            
            foreach ($upcomingAppointments as $apt) {
                $date = date('M j, Y', strtotime($apt['appointment_date']));
                $time = date('g:i A', strtotime($apt['start_time']));
                $response .= "• **{$apt['provider_name']}** ({$apt['specialty']}) on $date at $time\n";
            }
            
            $response .= "\n";
        }
        
        $response .= "You can schedule a new appointment through the 'Appointments' section of your dashboard. " .
                    "Would you like me to take you there, or would you prefer to check availability with a specific provider?";
        
        return $response;
    }
    
    // Check for general health advice
    if (preg_match('/(advice|recommendation|suggestion|tip|help with|how to|what should I do)/i', $message)) {
        if ($hasChronicCondition) {
            // Provide condition-specific advice if we can identify the condition
            $conditionKeywords = [];
            foreach ($conditions as $condition) {
                $conditionKeywords = array_merge($conditionKeywords, explode(' ', strtolower($condition['name'])));
            }
            
            $conditionKeywords = array_unique($conditionKeywords);
            $matchedConditions = [];
            
            foreach ($conditionKeywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    $matchedConditions[] = $keyword;
                }
            }
            
            if (!empty($matchedConditions)) {
                $condition = implode(' ', $matchedConditions);
                
                // Get cached disease-specific knowledge
                $knowledge = getDiseaseKnowledge($condition);
                
                if ($knowledge && !empty($knowledge['management_guidelines'])) {
                    return "For $condition, here are some general management guidelines:\n\n" . 
                           $knowledge['management_guidelines'] . 
                           "\n\nRemember to always consult with your healthcare provider for personalized advice.";
                }
            }
            
            // General chronic condition advice
            return "Managing a chronic condition can be challenging. Here are some general tips that might help:\n\n" .
                   "• Take medications as prescribed and keep track of any side effects\n" .
                   "• Maintain a healthy diet and stay hydrated\n" .
                   "• Get regular physical activity as tolerated\n" .
                   "• Keep all scheduled medical appointments\n" .
                   "• Monitor your symptoms and report any significant changes to your healthcare provider\n\n" .
                   "Would you like more specific information about managing any particular aspect of your condition?";
        } else {
            // General health advice
            return "Here are some general health tips that might be helpful:\n\n" .
                   "• Aim for 7-9 hours of quality sleep each night\n" .
                   "• Stay hydrated by drinking plenty of water throughout the day\n" .
                   "• Eat a balanced diet rich in fruits, vegetables, and whole grains\n" .
                   "• Engage in at least 150 minutes of moderate exercise per week\n" .
                   "• Practice stress-reduction techniques like deep breathing or meditation\n" .
                   "• Don't skip regular check-ups with your healthcare provider\n\n" .
                   "Is there a specific health topic you'd like to know more about?";
        }
    }
    
    // Check for emergency situations
    if (preg_match('/(emergency|911|urgent|can\'t breathe|severe pain|chest pain|passing out|fainting)/i', $message)) {
        return "⚠️ **This sounds serious!** If you're experiencing a medical emergency, please call emergency services (911 in the US) or go to the nearest emergency room immediately.\n\n" .
               "I'm here to help, but I can't provide emergency assistance. Your health and safety are the top priority. Would you like me to help you find the nearest emergency room?";
    }
    
    // Default response for unrecognized queries
    return "I'm here to help with your health and wellness. You can ask me about:\n\n" .
           "• Your medications and reminders\n" .
           "• Symptom tracking and management\n" .
           "• Appointment scheduling\n" .
           "• General health advice\n" .
           "• Information about chronic conditions\n\n" .
           "What would you like to know more about?";
}
?>
