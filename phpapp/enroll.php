<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $courseId = $input['courseId'] ?? null;
    $studentName = $input['studentName'] ?? '';
    $studentEmail = $input['studentEmail'] ?? '';
    
    if (!$courseId) {
        echo json_encode(['success' => false, 'error' => 'Course ID is required']);
        exit;
    }
    
    // Prepare data to send to Node.js API
    $enrollmentData = [
        'courseId' => (int)$courseId,
        'studentName' => $studentName,
        'studentEmail' => $studentEmail
    ];
    
    $jsonData = json_encode($enrollmentData);
    
    // Use file_get_contents
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n" .
                        "Content-Length: " . strlen($jsonData) . "\r\n",
            'method'  => 'POST',
            'content' => $jsonData,
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents('http://localhost:3000/api/enroll', false, $context);
    
    if ($response === FALSE) {
        $error = error_get_last();
        echo json_encode([
            'success' => false, 
            'error' => 'Cannot connect to server: ' . $error['message']
        ]);
    } else {
        // Pass through the Node.js response directly
        $result = json_decode($response, true);
        
        // Make sure we have the expected response format
        if (isset($result['success']) && $result['success'] === true) {
            echo json_encode([
                'success' => true,
                'message' => $result['message'] ?? 'Enrollment successful!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $result['error'] ?? 'Enrollment failed'
            ]);
        }
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>