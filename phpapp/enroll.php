<?php
header('Content-Type: application/json');

// Detect environment and set API URL
$isDocker = getenv('DOCKER_ENV') === 'true';
$nodejs_api_url = $isDocker ? 'http://nodejs:3000' : 'http://localhost:3000';

// Allow override from environment variable
$nodejs_api_url = getenv('NODEJS_API_URL') ?: $nodejs_api_url;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $courseId = $input['courseId'] ?? null;
    $studentName = $input['studentName'] ?? '';
    $studentEmail = $input['studentEmail'] ?? '';
    
    if (!$courseId) {
        echo json_encode(['success' => false, 'error' => 'Course ID is required']);
        exit;
    }
    
    $enrollmentData = [
        'courseId' => (int)$courseId,
        'studentName' => $studentName,
        'studentEmail' => $studentEmail
    ];
    
    $jsonData = json_encode($enrollmentData);
    
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
    
    $response = file_get_contents($nodejs_api_url . '/api/enroll', false, $context);
    
    if ($response === FALSE) {
        $error = error_get_last();
        echo json_encode([
            'success' => false, 
            'error' => 'Cannot connect to server: ' . ($error['message'] ?? 'Unknown error'),
            'debug' => ['api_url' => $nodejs_api_url]
        ]);
    } else {
        echo $response;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>