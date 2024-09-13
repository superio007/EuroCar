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
        // Convert the dates to the desired format
        $formatpickDate = convertDate($pickDate);
        $formatdropDate = convertDate($dropDate);

        // Define the XML request payload for getting cities
        $xmlRequest = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <message>
        <serviceRequest serviceCode=\"getCarCategories\">
            <serviceParameters>
            <reservation>
                <checkout stationID=\'$pickup\' date=\'$formatpickDate\'/>
                <checkin stationID=\'$dropOff\' date=\'$formatdropDate\'/>
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
        'XML-Request' => $xmlRequest,
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
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        } else {
        // Display the response
        $xmlres = new SimpleXMLElement($response);
        echo "<pre>";
        print_r($xmlres);
        echo "</pre>";
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
                <div class="col-6 d-grid">
                    <label for="pick">Pick UP LOCATION:</label>
                    <input type="text" name="pick" id="pick" list="airport_name" placeholder="CITY OR AIRPORT CODE" autocomplete="off">
                    <input type="hidden" name="pickCityCode" id="pickCityCode"> <!-- Hidden input to store citycode -->
                </div>
                <div class="col-6 d-grid">
                    <label for="drop">DROP OFF LOCATION:</label>
                    <input type="text" name="drop" id="drop" list="airport_name" placeholder="CITY OR AIRPORT CODE" autocomplete="off">
                    <input type="hidden" name="dropCityCode" id="dropCityCode"> <!-- Hidden input for Drop Off citycode -->
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
                </div>
                <div class="col-md-6 mb-3">
                    <label for="mobile_drop">Drop Off Location:</label>
                    <input type="text" name="drop" id="mobile_drop" list="airport_name" placeholder="CITY OR AIRPORT CODE" autocomplete="off" class="form-control">
                    <input type="hidden" name="dropCityCode" id="mobile_dropCityCode">
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
        function updateCityCode(inputId, hiddenInputId) {
            var input = document.getElementById(inputId).value;
            var datalist = document.getElementById('airport_name').options;
            var cityCode = '';

            // Loop through the datalist options to find a match for the input value
            for (var i = 0; i < datalist.length; i++) {
                if (datalist[i].value === input) {
                    cityCode = datalist[i].getAttribute('data-code');
                    break;
                }
            }

            // Set the hidden input with the corresponding citycode
            document.getElementById(hiddenInputId).value = cityCode;
        }

        // Event listeners for Pick Up and Drop Off inputs
        document.getElementById('pick').addEventListener('input', function() {
            updateCityCode('pick', 'pickCityCode');
        });

        document.getElementById('drop').addEventListener('input', function() {
            updateCityCode('drop', 'dropCityCode');
        });


        document.getElementById('pick').addEventListener('input', function() {
            // Get the Pick Up Location value
            var pickValue = this.value;

            // Set the Drop Off Location to the same value
            document.getElementById('drop').value = pickValue;

            // Also copy the Pick Up City Code to the Drop Off City Code
            var pickCityCodeValue = document.getElementById('pickCityCode').value;
            document.getElementById('dropCityCode').value = pickCityCodeValue;
        });

        // mobile version 

        // Event listeners for Pick Up and Drop Off inputs
        document.getElementById('mobile_pick').addEventListener('input', function() {
            updateCityCode('mobile_pick', 'mobile_pickCityCode');
        });

        document.getElementById('mobile_drop').addEventListener('input', function() {
            updateCityCode('mobile_drop', 'mobile_dropCityCode');
        });


        document.getElementById('mobile_pick').addEventListener('input', function() {
            // Get the Pick Up Location value
            var pickValue = this.value;

            // Set the Drop Off Location to the same value
            document.getElementById('mobile_drop').value = pickValue;

            // Also copy the Pick Up City Code to the Drop Off City Code
            var pickCityCodeValue = document.getElementById('mobile_pickCityCode').value;
            document.getElementById('mobile_dropCityCode').value = pickCityCodeValue;
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