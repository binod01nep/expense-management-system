<?php
// Simple currency conversion API (in production, use a real API like Fixer.io or CurrencyAPI)
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['from']) && isset($_GET['to'])) {
    $from = strtoupper($_GET['from']);
    $to = strtoupper($_GET['to']);
    
    // Mock exchange rates (in production, fetch from real API)
    $rates = [
        'USD' => 1.0,
        'EUR' => 0.85,
        'GBP' => 0.73,
        'INR' => 83.0,
        'CAD' => 1.35,
        'AUD' => 1.55,
        'JPY' => 110.0,
        'CHF' => 0.92
    ];
    
    if (isset($rates[$from]) && isset($rates[$to])) {
        $rate = $rates[$to] / $rates[$from];
        echo json_encode([
            'success' => true,
            'from' => $from,
            'to' => $to,
            'rate' => $rate,
            'timestamp' => time()
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Currency not supported'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
