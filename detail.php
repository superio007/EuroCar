<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Reservation Details</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .section {
            margin-bottom: 20px;
        }

        .section p {
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <?php
    // Define the XML request
    require "dbconn.php";
    $xmlRequest = <<<XML
    <?xml version="1.0" encoding="UTF-8"?>
    <message>
        <serviceRequest serviceCode="search.searchbyid">
            <serviceParameters>
                <reservation resNumber="1181185739" />
            </serviceParameters>
        </serviceRequest>
    </message>
    XML;
    function getLocation($searchTerm, $conn)
    {
        // Prepare the SQL query
        $stmt = $conn->prepare("SELECT cityaddress FROM `filter_locations_euro` WHERE stationCode LIKE ?");

        // Bind parameters to prevent SQL injection
        $searchTerm = '%' . $searchTerm . '%'; // Add wildcard for LIKE
        $stmt->bind_param("s", $searchTerm); // "s" indicates a string parameter

        // Execute the query
        $stmt->execute();

        // Fetch results
        $result = $stmt->get_result();
        if ($result) {
            $addresses = [];
            while ($row = $result->fetch_assoc()) {
                $addresses[] = $row['cityaddress']; // Extract only cityaddress
            }
            return $addresses; // Return the flat array of cityaddress values
        } else {
            return []; // Return an empty array if no results are found
        }

        // Close the statement
        $stmt->close();
    }



    // API endpoint URL (replace with the actual URL)
    $apiUrl = "https://applications-ptn.europcar.com/xrs/resxml";

    // Authentication credentials (replace with actual values)
    $callerCode = "1132097";
    $password = "02092024";
    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'XML-Request' => $xmlRequest,
        'callerCode' => $callerCode,
        'password' => $password,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: text/xml',
    ]);

    // Execute the request and capture the response
    $response = curl_exec($ch);

    // Check for errors
    if ($response === false) {
        echo "cURL Error: " . curl_error($ch);
    } else {
        echo "\n$response\n";
    }

    // Convert XML string to SimpleXMLElement object
    $xml = new SimpleXMLElement($response);

    // Retrieve reservation details
    $reservation = $xml->serviceResponse->reservation;

    // Assign reservation attributes to separate variables
    $carCategory = (string)$reservation['carCategory'];
    $carCategoryAirCond = (string)$reservation['carCategoryAirCond'];
    $carCategoryAutomatic = (string)$reservation['carCategoryAutomatic'];
    $carCategoryBaggageQuantity = (string)$reservation['carCategoryBaggageQuantity'];
    $carCategoryDoors = (string)$reservation['carCategoryDoors'];
    $carCategoryModelHeight = (string)$reservation['carCategoryModelHeight'];
    $carCategoryModelLength = (string)$reservation['carCategoryModelLength'];
    $carCategoryModelWidth = (string)$reservation['carCategoryModelWidth'];
    $carCategoryPowerHP = (string)$reservation['carCategoryPowerHP'];
    $carCategoryPowerKW = (string)$reservation['carCategoryPowerKW'];
    $carCategorySeats = (string)$reservation['carCategorySeats'];
    $countryOfReservation = (string)$reservation['countryOfReservation'];
    $duration = (string)$reservation['duration'];
    $fuelTypeCode = (string)$reservation['fuelTypeCode'];
    $isModifiable = (string)$reservation['isModifiable'];
    $preferredLanguage = (string)$reservation['preferredLanguage'];
    $productCode = (string)$reservation['productCode'];
    $productFamily = (string)$reservation['productFamily'];
    $productLevel = (string)$reservation['productLevel'];
    $productVersion = (string)$reservation['productVersion'];
    $rateId = (string)$reservation['rateId'];
    $resNumber = (string)$reservation['resNumber'];
    $resStationID = (string)$reservation['resStationID'];
    $resTimeStamp = (string)$reservation['resTimeStamp'];
    $checkinDate = (string)$xml->serviceResponse->reservation->checkin['date'];
    $checkinTime = (string)$xml->serviceResponse->reservation->checkin['time'];
    $checkinStationID = (string)$xml->serviceResponse->reservation->checkin['stationID'];
    $checkinLocation = getlocation($checkinStationID, $conn);
    // Retrieve check-out details
    $checkoutDate = (string)$xml->serviceResponse->reservation->checkout['date'];
    $checkoutTime = (string)$xml->serviceResponse->reservation->checkout['time'];
    $checkoutStationID = (string)$xml->serviceResponse->reservation->checkout['stationID'];
    $checkoutLocation = getlocation($checkoutStationID, $conn);
    $statusCode = (string)$reservation['statusCode'];
    $type = (string)$reservation['type'];
    // Retrieve driver details
    $driverFirstName = (string)$xml->serviceResponse->driver['firstName'];
    $driverLastName = (string)$xml->serviceResponse->driver['lastName'];
    curl_close($ch);
    function formatDate($inputDate)
    {
        // Ensure the input date is in the correct format (YYYYMMDD)
        if (preg_match('/^\d{8}$/', $inputDate)) {
            // Use substr to split the input date and format it
            $formattedDate = substr($inputDate, 0, 4) . '-' . substr($inputDate, 4, 2) . '-' . substr($inputDate, 6, 2);
            return $formattedDate;
        } else {
            // Return an error message if the format is incorrect
            return "Invalid date format. Please provide a date in YYYYMMDD format.";
        }
    }
    function formatTime($inputTime)
    {
        // Ensure the input time is in the correct format (HHMM)
        if (preg_match('/^\d{4}$/', $inputTime)) {
            // Extract hours and minutes
            $hours = substr($inputTime, 0, 2);
            $minutes = substr($inputTime, 2, 2);

            // Convert to 12-hour format with AM/PM
            $formattedTime = date("h A", strtotime("$hours:$minutes"));
            return $formattedTime;
        } else {
            // Return an error message if the format is incorrect
            return "Invalid time format. Please provide a time in HHMM format.";
        }
    }
    ?>
    <div class="container">
        <h1 class="text-center m-4">Vehicle Reservation Details</h1>

        <div class="section">
            <h2 class="bg-warning text-primary p-2">Reservation Information</h2>
            <p><strong>Pick-Up Date</strong> <?php echo formatDate($checkinDate); ?></p>
            <p><strong>Pick-Up Time</strong> <?php echo formatTime($checkinTime); ?></p>
            <p><strong>Return Date</strong> <?php echo formatDate($checkoutDate); ?></p>
            <p><strong>Return Time</strong> <?php echo formatTime($checkoutTime); ?></p>
            <p><strong>Pick-Up Location:</strong> <?php echo (string)$checkinLocation[0]; ?></p>
            <p><strong>Return Location:</strong> <?php echo (string)$checkoutLocation[0]; ?></p>
        </div>

        <div class="section">
            <h2 class="bg-warning text-primary p-2">Customer Information</h2>
            <p><strong>Customer First Name:</strong> <?php echo $driverFirstName; ?></p>
            <p><strong>Customer Last Name:</strong> <?php echo $driverLastName; ?></p>
        </div>

        <div class="section">
            <h2 class="bg-warning text-primary p-2">Vehicle Details</h2>
            <p><strong>Vehicle Code:</strong> <?php echo $carCategory; ?></p>
            <p><strong>Passenger Quantity:</strong> <?php echo $carCategoryDoors; ?></p>
            <p><strong>Baggage Quantity:</strong> <?php echo $carCategoryBaggageQuantity; ?></p>
            <p><strong>Air Conditioning:</strong> <?php echo $carCategoryAirCond; ?></p>
            <p><strong>Transmission Type:</strong> <?php if ($carCategoryAutomatic == "Y") {
                                                        echo "Automatic";
                                                    } else {
                                                        echo "Manual";
                                                    } ?></p>
            <p><strong>Fuel Type:</strong> <?php echo $fuelTypeCode; ?></p>
        </div>
        <div class="d-flex justify-content-center gap-5 mt-3">
            <button onclick="window.location.href='index.php';" class="btn bg-warning text-primary">
                Go Back To Homepage
            </button>
            <button onclick="window.print()" class="btn bg-primary text-warning">
                Download Now
            </button>
        </div>
    </div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            window.location.href = "index.php";
        }, 3000);
    });
</script>

</html>