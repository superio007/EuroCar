<?php
include "dbconn.php";
header('Content-Type: application/json');

function getLocation($cityCode, $conn) {
    $sql = "SELECT cityaddress FROM `filter_locations_euro` WHERE stationCode LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cityCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    // Fetch only the cityaddress
    $row = $result->fetch_assoc();
    return $row ? $row['cityaddress'] : null;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (isset($data['pickup']) && isset($data['dropup'])) {
    $pickup = $data['pickup'];
    $dropup = $data['dropup'];

    $pickRoute = getLocation($pickup, $conn);
    $dropRoute = getLocation($dropup, $conn);

    if ($pickRoute && $dropRoute) {
        // Send success response
        echo json_encode([
            'success' => true,
            'pickRoute' => $pickRoute,
            'dropRoute' => $dropRoute,
            'message' => "Received valid pickup and dropup locations."
        ]);
    } else {
        // One or both locations not found
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => "One or both locations not found."
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => "Invalid input. Both Pickup and Dropup are required."
    ]);
}
?>
