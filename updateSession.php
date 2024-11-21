<?php
session_start();
$requiredeuroBooking = isset($_SESSION['requiredeuroBooking']) ? $_SESSION['requiredeuroBooking'] : []; // Ensure session is initialized
$input = json_decode(file_get_contents('php://input'), true);

if ($input) {
    $pickup = $input['pickup']; // Match JavaScript key
    $dropOff = $input['dropOff'];
    $pickTime = $input['pickUpTime'];
    $dropTime = $input['dropOffTime'];
    $requiredeuroBooking['pickup'] = $pickup;
    $requiredeuroBooking['dropOff'] = $dropOff;
    $requiredeuroBooking['pickTime'] = $pickTime;
    $requiredeuroBooking['dropTime'] = $dropTime;

    // Save back to session
    $_SESSION['requiredeuroBooking'] = $requiredeuroBooking;

    // Respond with updated session data
    echo json_encode([
        "status" => "success",
        "message" => "Session updated",
        "data" => $_SESSION['requiredeuroBooking']
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid input"
    ]);
}
?>
