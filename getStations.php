<?php
require 'dbconn.php'; // Include your database connection

if (isset($_POST['searchTerm'])) {
    $searchTerm = $_POST['searchTerm'];

    // Query to search for stations matching the input
    $stmt = $conn->prepare("SELECT stationCode, stationName, City FROM eurocar_locations WHERE City LIKE ?");
    $searchTerm = "%$searchTerm%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    $stations = [];
    while ($row = $result->fetch_assoc()) {
        $stations[] = [
            'stationCode' => $row['stationCode'],
            'stationName' => $row['stationName'],
            'city' => $row['City']
        ];
    }

    if(!empty($stations)){
        echo json_encode($stations);
    }else{
        echo json_encode(['message' => 'No data found']);
    }
}
?>
