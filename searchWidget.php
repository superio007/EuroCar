<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/74e6741759.js" crossorigin="anonymous"></script>
    <link rel="stylesheet"
        href="https://code.jquery.com/ui/1.14.0/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://code.jquery.com/ui/1.14.0/jquery-ui.js"></script>
    <link rel="stylesheet"
        href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php
    require 'dbconn.php';
    $sql = "SELECT * FROM eurocar_locations";
    $result = $conn->query($sql);
    $groupedLocations = [];

    while ($row = $result->fetch_assoc()) {
        $city = $row['City'];
        $groupedLocations[$city][] = [
            'stationCode' => $row['stationCode'],
            'stationName' => $row['stationName']
        ];
    }
    // var_dump($data);
    function convertDate($date_str) {
        // Convert the string date into a DateTime object using 'm/d/Y' format
        $date_obj = DateTime::createFromFormat('m/d/Y', $date_str);
        
        // Format the DateTime object into the desired format 'Ymd'
        if ($date_obj) {
            return $date_obj->format('Ymd');
        } else {
            return "Invalid date format!";
        }
    }
    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['search_btn'])) {
        $pickup = $_POST['pickCityCode'];
        $dropOff = $_POST['dropCityCode'];
        $pickDate = $_POST['pickDate'];  // e.g., "08/17/2024"
        $pickTime = $_POST['pickTime'];
        $dropDate = $_POST['dropDate'];  // e.g., "09/20/2024"
        $dropTime = $_POST['dropTime'];
        $groupName = $_POST['groupName'];
        // Convert the dates to the desired format
        $formatpickDate = convertDate($pickDate);
        $formatdropDate = convertDate($dropDate);
        $xmlRequestEuro = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <message>
        <serviceRequest serviceCode=\"getCarCategories\">
            <serviceParameters>
            <reservation>
                <checkout stationID=\"$pickup\" date=\"$formatpickDate\"/>
                <checkin stationID=\"$dropOff\" date=\"$formatdropDate\"/>
            </reservation>
            </serviceParameters>
        </serviceRequest>
        </message>
        ";
        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, 'https://applications-ptn.europcar.com/xrs/resxml');
        curl_setopt($ch, CURLOPT_POST, 1);

        // URL-encode the parameters (XML Request, callerCode, and password)
        $postFields = http_build_query([
        'XML-Request' => $xmlRequestEuro,
        'callerCode' => '1132097',
        'password' => '02092024',
        ]);

        // Set the POST fields and headers
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: text/xml'
        ]);

        // Execute the cURL request
        $responseEuro = curl_exec($ch);
        // Check for errors
        if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        } else {
        // Display the response
        $requiredeuroBooking = [
            "pickup" => $pickup,
            "dropOff" =>$dropOff,
            "pickDate" => $formatpickDate,
            "dropDate" => $formatdropDate,
            "pickTime" => $pickTime,
            "dropTime" => $dropTime
        ];
        $dataarray = [
            'groupName' => $groupName,
            'pickUpDateTime' => $pickUpDateTime,
            'dropOffDateTime' => $dropOffDateTime,
            'pickLocation' => $pickup,
            'dropLocation' => $dropOff,
        ];
        $_SESSION['dataarray'] = $dataarray;
        $_SESSION['responseEuro'] = $responseEuro;
        $_SESSION['requiredeuroBooking'] = $requiredeuroBooking;
        echo "<script>window.location.href='results.php'</script>";
        }
        // Close the cURL session
        curl_close($ch);
    }
    ?>

    <datalist id="airport_name">
        <?php while ($row = $result->fetch_assoc()): ?>
            <option value="<?php echo $row['stationName']; ?>" data-code="<?php echo $row['stationCode']; ?>">
                <?php echo $row['stationName']; ?>
            </option>
        <?php endwhile; ?>
    </datalist>
    <!-- for desktop -->
    <div id="searchbox" class="d-none d-md-block bg-white p-3 rounded-3 mt-3">
        <form action="" method="post">
            <div class="d-flex justify-content-start">
                <h2>SEARCH FOR CAR PRICES</h2>
            </div>
            <div class="row">
                <div class="col-6 d-grid position-relative">
                    <label for="pick">PICK UP LOCATION:</label>
                    <input type="text" name="pick" id="pickInput" class="form-control" autocomplete="off" placeholder="Enter city or airport code">
                    <input type="hidden" name="pickCityCode" id="pickCityCode"> <!-- Hidden input to store pick-up city code -->
                    <input type="hidden" name="groupName" id="groupName">
                    <!-- Suggestion box -->
                    <div id="suggestionBox" class="suggestion-box bg-white border rounded position-absolute">

                    </div>
                </div>
                <div class="col-6 d-grid position-relative">
                    <label for="drop">DROP OFF LOCATION:</label>
                    <input type="text" name="drop" id="dropInput" class="form-control" autocomplete="off" placeholder="Enter city or airport code">
                    <input type="hidden" name="dropCityCode" id="dropCityCode"> <!-- Hidden input to store drop-off city code -->

                    <!-- Suggestion box for drop-off locations -->
                    <div id="dropSuggestionBox" class="suggestion-box bg-white border rounded position-absolute" style="display:none;"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-6 d-flex justify-content-between gap-3 mt-3">
                    <div class="w-50 d-grid outer_div">
                        <label for="pickDate">PICK UP DATE:</label>
                        <input type="text" class="input_div" name="pickDate" id="pickDate">
                    </div>
                    <div class="w-50 d-grid outer_div">
                        <label for="pickTime">PICK UP TIME:</label>
                        <input type="text" class="input_div" name="pickTime" id="pickTime">
                    </div>
                </div>
                <div class="col-6 d-flex justify-content-between gap-3 mt-3">
                    <div class="w-50 d-grid outer_div">
                        <label for="dropDate">DROP OFF DATE:</label>
                        <input type="text" class="input_div" name="dropDate" id="dropDate">
                    </div>
                    <div class="w-50 d-grid outer_div">
                        <label for="dropTime">DROP OFF TIME:</label>
                        <input type="text" class="input_div" name="dropTime" id="dropTime">
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="d-flex justify-content-end">
                    <button type="submit" name="search_btn" class="btn btn-primary rounded-2">SEARCH <i class="fa-solid fa-arrow-right fa-sm" style="color: #ffffff;"></i></button>
                </div>
            </div>
        </form>
    </div>
    <!-- for mobile -->
    <div class="d-md-none bg-white p-3 rounded-3 mt-3">
        <form action="" method="post">
            <div class="d-flex justify-content-start mb-3">
                <h2>SEARCH FOR CAR PRICES</h2>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="mobile_pick">Pick Up Location:</label>
                    <input type="text" name="pick" id="mobile_pick" list="airport_name" placeholder="CITY OR AIRPORT CODE" autocomplete="off" class="form-control">
                    <input type="hidden" name="pickCityCode" id="mobile_pickCityCode">
                    <input type="hidden" name="groupName" id="groupName">
                    <div id="suggestionBox-mobile" class="suggestionBox-mobile bg-white border rounded position-absolute" style="display:none;"></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="mobile_drop">Drop Off Location:</label>
                    <input type="text" name="drop" id="mobile_drop" list="airport_name" placeholder="CITY OR AIRPORT CODE" autocomplete="off" class="form-control">
                    <input type="hidden" name="dropCityCode" id="mobile_dropCityCode">
                    <div id="drop-suggestionBox-mobile" class="drop-suggestionBox-mobile bg-white border rounded position-absolute" style="display:none;"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="mobile_pickDate">Pick Up Date:</label>
                    <input type="text" name="pickDate" id="mobile_pickDate" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="mobile_pickTime">Pick Up Time:</label>
                    <input type="text" name="pickTime" id="mobile_pickTime" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="mobile_dropDate">Drop Off Date:</label>
                    <input type="text" name="dropDate" id="mobile_dropDate" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="mobile_dropTime">Drop Off Time:</label>
                    <input type="text" name="dropTime" id="mobile_dropTime" class="form-control">
                </div>
            </div>
            <div>
                <button type="submit" name="search_btn" class="btn btn-primary rounded-2">SEARCH <i class="fa-solid fa-arrow-right fa-sm" style="color: #ffffff;"></i></button>
            </div>
        </form>
    </div>
    <script>
        // Function to handle the autocomplete logic
        $(document).ready(function() {
            $('#pickInput').on('click', function() {
                // Make AJAX request when the input is clicked
                $.ajax({
                    url: 'getStations.php',
                    method: 'POST',
                    success: function(response) {
                        var stations = JSON.parse(response);
                        console.log(stations);
                        $('#suggestionBox').empty().show(); // Clear existing suggestions and show the box

                        // Check if there are valid stations
                        if (stations.length === 0) {
                            $('#suggestionBox').append(`
                    <div class="no-data-message" style="padding: 10px; color: red;">
                        No stations found.
                    </div>
                `);
                        } else {
                            // Iterate through stations and add them to the suggestion box
                            stations.forEach(function(station) {
                                $('#suggestionBox').append(`
                        <div class="suggestion-item" data-code="${station.stationCode}">
                            ${station.stationName} (${station.group}) All locations
                        </div>
                    `);
                            });

                            // Handle click on suggestion items
                            $('.suggestion-item').on('click', function() {
                                var stationName = $(this).text().trim();
                                var stationCode = $(this).data('code');

                                $('#pickInput').val(stationName);
                                $('#pickCityCode').val(stationCode);
                                $('#dropInput').val(stationName);
                                $('#dropCityCode').val(stationCode);

                                $('#suggestionBox').hide();
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Error during AJAX request:', textStatus, errorThrown);
                    },
                });
            });


            $('#pickInput').on('click', function() {
                if ($('#suggestionBox').children().length > 0) {
                    $('#suggestionBox').show();
                }
            });

            $('#mobile_pick').on('focus', function() {
                // Make AJAX request when the input is focused
                $.ajax({
                    url: 'getStations.php',
                    method: 'POST',
                    success: function(response) {
                        var stations = JSON.parse(response);
                        $('#suggestionBox-mobile').empty().show(); // Clear existing suggestions and show the box

                        // Check if there are valid stations
                        if (stations.length === 0) {
                            $('#suggestionBox-mobile').append(`
                    <div class="no-data-message" style="padding: 10px; color: red;">
                        No stations found.
                    </div>
                `);
                        } else {
                            // Group stations by city (group)
                            var groupedStations = stations.reduce(function(grouped, station) {
                                var city = station.group;
                                if (!grouped[city]) {
                                    grouped[city] = [];
                                }
                                grouped[city].push(station);
                                return grouped;
                            }, {});

                            // Populate the suggestion box
                            Object.keys(groupedStations).forEach(function(city) {
                                var cityStations = groupedStations[city];
                                cityStations.forEach(function(station) {
                                    $('#suggestionBox-mobile').append(`
                            <div class="suggestion-item-mobile" data-code="${station.stationCode}">
                                ${station.stationName} All locations
                            </div>
                        `);
                                });
                            });

                            // Handle click on suggestion items
                            $('.suggestion-item-mobile').on('click', function() {
                                var stationName = $(this).text().trim();
                                var stationCode = $(this).data('code');

                                $('#mobile_pick').val(stationName);
                                $('#mobile_pickCityCode').val(stationCode);
                                $('#mobile_drop').val(stationName);
                                $('#mobile_dropCityCode').val(stationCode);

                                $('#suggestionBox-mobile').hide();
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Error during AJAX request:', textStatus, errorThrown);
                    },
                });
            });


            $('#mobile_pick').on('click', function() {
                if ($('#suggestionBox-mobile').children().length > 0) {
                    $('#suggestionBox-mobile').show();
                }
            });

            $('#dropInput').on('focus', function() {
                // Make AJAX request when the input field is focused
                $.ajax({
                    url: 'getStations.php',
                    method: 'POST',
                    success: function(response) {
                        var stations = JSON.parse(response);
                        $('#dropSuggestionBox').empty().show(); // Clear existing suggestions and show the box

                        // Check if there are valid stations
                        if (stations.length === 0) {
                            $('#dropSuggestionBox').append(`
                    <div class="no-data-message" style="padding: 10px; color: red;">
                        No stations found.
                    </div>
                `);
                        } else {
                            // Group stations by city (group)
                            var groupedStations = stations.reduce(function(grouped, station) {
                                var city = station.group;
                                if (!grouped[city]) {
                                    grouped[city] = [];
                                }
                                grouped[city].push(station);
                                return grouped;
                            }, {});

                            // Populate the suggestion box
                            Object.keys(groupedStations).forEach(function(city) {
                                var cityStations = groupedStations[city];
                                cityStations.forEach(function(station) {
                                    $('#dropSuggestionBox').append(`
                            <div class="suggestion-item" data-code="${station.stationCode}">
                                ${station.stationName} (${station.group}) All locations
                            </div>
                        `);
                                });
                            });

                            // Handle click on suggestion items
                            $('.suggestion-item').on('click', function() {
                                var stationName = $(this).text().trim();
                                var stationCode = $(this).data('code');

                                $('#dropInput').val(stationName);
                                $('#dropCityCode').val(stationCode);

                                $('#dropSuggestionBox').hide();
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Error during AJAX request:', textStatus, errorThrown);
                    },
                });
            });


            $('#dropInput').on('click', function() {
                if ($('#dropSuggestionBox').children().length > 0) {
                    $('#dropSuggestionBox').show();
                }
            });
            $('#mobile_drop').on('focus', function() {
                // Make AJAX request when the input field is focused
                $.ajax({
                    url: 'getStations.php',
                    method: 'POST',
                    success: function(response) {
                        var stations = JSON.parse(response);
                        $('#drop-suggestionBox-mobile').empty().show(); // Clear existing suggestions and show the box

                        // Check if there are valid stations
                        if (stations.length === 0) {
                            $('#drop-suggestionBox-mobile').append(`
                    <div class="no-data-message" style="padding: 10px; color: red;">
                        No stations found.
                    </div>
                `);
                        } else {
                            // Group stations by city (group)
                            var groupedStations = stations.reduce(function(grouped, station) {
                                var city = station.group;
                                if (!grouped[city]) {
                                    grouped[city] = [];
                                }
                                grouped[city].push(station);
                                return grouped;
                            }, {});

                            // Populate the suggestion box
                            Object.keys(groupedStations).forEach(function(city) {
                                var cityStations = groupedStations[city];
                                cityStations.forEach(function(station) {
                                    $('#drop-suggestionBox-mobile').append(`
                            <div class="drop-suggestion-item-mobile" data-code="${station.stationCode}">
                                ${station.stationName} (${station.group}) All locations
                            </div>
                        `);
                                });
                            });

                            // Handle click on suggestion items
                            $('.drop-suggestion-item-mobile').on('click', function() {
                                var stationName = $(this).text().trim();
                                var stationCode = $(this).data('code');

                                $('#mobile_drop').val(stationName);
                                $('#mobile_dropCityCode').val(stationCode);

                                $('#drop-suggestionBox-mobile').hide();
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Error during AJAX request:', textStatus, errorThrown);
                    },
                });
            });

            $('#mobile_drop').on('click', function() {
                if ($('#drop-suggestionBox-mobile').children().length > 0) {
                    $('#drop-suggestionBox-mobile').show();
                }
            });


        });

        $(function() {
            $("#pickDate").datepicker();
        });
        $(document).ready(function() {
            $('#pickTime').timepicker({});
        });
        $(function() {
            $("#dropDate").datepicker();
        });
        $(document).ready(function() {
            $('#dropTime').timepicker({});
        });
        // mobile
        $(function() {
            $("#mobile_pickDate").datepicker();
        });
        $(document).ready(function() {
            $('#mobile_pickTime').timepicker({});
        });
        $(function() {
            $("#mobile_dropDate").datepicker();
        });
        $(document).ready(function() {
            $('#mobile_dropTime').timepicker({});
        });
    </script>
</body>

</html>