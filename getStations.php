<?php
// Include the connection file
include 'dbconn.php'; // Assuming conn.php contains your connection code

// SQL query
$sql = "
    SELECT 
        MIN(Id) AS Id, 
        Vendor_Id, 
        stationCode, 
        country, 
        city, 
        cityaddress, 
        groupName
    FROM 
        filter_locations_euro
    WHERE 
        groupName IS NOT NULL 
        AND groupName != '0' 
    GROUP BY 
        groupName
";

// Execute the query
$result = $conn->query($sql);
$stations = [];
    while ($row = $result->fetch_assoc()) {
        $stations[] = [
            'stationCode' => $row['stationCode'],
            'stationName' => $row['cityaddress'],
            'city' => $row['city'],
            'group' => $row['groupName'],
            'vendorId' => $row['Vendor_Id'],
            'country' => $row['country']
        ];
    }
    if (!empty($stations)) {
        echo json_encode($stations);
    } else {
        echo json_encode(['message' => 'No data found']);
    }
$conn->close();


