<?php
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
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php
    require "dbconn.php";
    $responseEuro = $_SESSION['responseEuro'];
    if (!empty($_SESSION['responseEuro'])) {
        $xmlresEuro = new SimpleXMLElement($_SESSION['responseEuro']);
    } else {
        echo "No XML response available in session.";
    }
    $pickUp = $dataArray['pickLocation'] ?? '';
    $drop = $dataArray['dropLocation'] ?? '';
    $sql = $conn->prepare("SELECT * FROM `airport_list` WHERE citycode IN ( ? , ?)");
    $sql->bind_param("ss", $pickUp, $drop);
    $sql->execute();
    $result = $sql->get_result();
    $pickupDetails = '';
    $dropoffDetails = '';

    // Loop through the results to assign pickup and drop-off airport names and cities
    while ($row = $result->fetch_assoc()) {
        if ($row['citycode'] === $pickUp) {
            $pickupDetails = $row['city'] . ' ' . $row['airpotname'];
        }
        if ($row['citycode'] === $drop) {
            $dropoffDetails = $row['city'] . ' ' . $row['airpotname'];
        }
    }
    // $conn->close();

    // Date formatting function
    function formatDate($dateString)
    {
        $date = new DateTime($dateString);
        return $date->format('D, d M, Y \a\t H:i');
    }

    $pickDate = formatDate($dataArray['pickUpDateTime'] ?? '');
    $dropDate = formatDate($dataArray['dropOffDateTime'] ?? '');
    $categoriesEuro = [
        'Economy' => ['CDAR'], // Replace with actual car codes for Economy
        'Compact' => ['CFAR', 'DFAR'],
        'Midsize' => ['IDAR'],
        'Luxury/Sports Car' => ['JDAR', 'LDAR'],
        'SUV' => ['SFAR', 'JFAR'],
        'Station Wagon' => ['FWAR'],
        'Van/People Carrier' => ['PVAR'],
        '7-12 Passenger Vans' => ['UFAD'] // Replace with actual codes if necessary
    ];

    function extractVehicleDetailsEuro($xmlResponse, $categoriesEuro)
    {
        $vehicleDetails = [];

        if (isset($xmlResponse->serviceResponse->carCategoryList->carCategory)) {
            // Iterate over the carCategory elements
            foreach ($xmlResponse->serviceResponse->carCategoryList->carCategory as $vehicle) {
                // Extract relevant vehicle details
                $code = (string)$vehicle['carCategoryCode']; // Car category code
                $name = (string)$vehicle['carCategoryName']; // Car category name
                $seats = (int)$vehicle['carCategorySeats'];  // Number of seats
                $baggage = (int)$vehicle['carCategoryBaggageQuantity']; // Baggage capacity
                $rate = (float)$vehicle['carCategoryPowerHP']; // Example: power as a stand-in for rate (replace with actual rate data if available)
                $currency = "USD"; // Placeholder, as currency is not in the response (modify accordingly)
                $co2 = (int)$vehicle['carCategoryCO2Quantity']; // CO2 emissions
                $fuelType = (string)$vehicle['fuelTypeCode']; // Fuel type

                // Match vehicles based on the category provided in $categories (for example, carCategoryCode)
                foreach ($categoriesEuro as $category => $codes) {
                    if (in_array($code, $codes)) {
                        // Add vehicle details to the result array
                        $vehicleDetails[$category][] = [
                            'name' => $name,
                            'code' => $code,
                            'seats' => $seats,
                            'baggage' => $baggage,
                            'rate' => $rate, // Replace this with actual rate data if available
                            'currency' => $currency,
                            'co2' => $co2,
                            'fuelType' => $fuelType,
                        ];
                    }
                }
            }
        }

        return $vehicleDetails;
    }
    $sql = "SELECT MarkupPrice FROM `markup_price`";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // output data of each row
        while ($row = $result->fetch_assoc()) {
            $markUp = $row['MarkupPrice'];
        }
    } else {
        echo "0 results";
    }
    // echo $markUp;
    $conn->close();
    $vehicleDetailsEuro = extractVehicleDetailsEuro($responseEuro, $categoriesEuro);
    function calculatePercentage($part, $total)
    {
        $og = $total;
        if ($total == 0) {
            return "Total cannot be zero"; // To avoid division by zero error
        }
        $percentage = ($total * $part) / 100;
        return $percentage + $og;
    }
    function filterVehicles($xmlresEuro, $transmission = '', $doors = '', $powerKWMin = '', $powerKWMax = '', $fuelTypes = [])
    {
        $vehicleDetails = []; // Array to store filtered vehicles

        // Loop through the car categories provided in the XML
        foreach ($xmlresEuro->serviceResponse->carCategoryList->carCategory as $vehicle) {
            $matches = true; // Flag to track if the vehicle matches all conditions

            // Filter by transmission (automatic or manual)
            if ($transmission === 'automatic' && (string)$vehicle['carCategoryAutomatic'] !== 'Y') {
                $matches = false;
            } elseif ($transmission === 'manual' && (string)$vehicle['carCategoryAutomatic'] !== 'N') {
                $matches = false;
            }

            // Filter by number of doors
            if ($doors && (string)$vehicle['carCategoryDoors'] !== $doors) {
                $matches = false;
            }

            // Filter by power (KW) range
            $powerKW = (float)$vehicle['carCategoryPowerKW'];
            if ($powerKWMin && $powerKW < (float)$powerKWMin) {
                $matches = false;
            }
            if ($powerKWMax && $powerKW > (float)$powerKWMax) {
                $matches = false;
            }

            // Filter by fuel type
            if (!empty($fuelTypes) && !in_array((string)$vehicle['fuelTypeCode'], $fuelTypes)) {
                $matches = false;
            }

            // If the vehicle matches all the filters, add it to the result array
            if ($matches) {
                $vehicleDetails[] = $vehicle;
            }
        }

        return $vehicleDetails; // Return the filtered vehicle details
    }
    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search'])) {
        // Retrieve form data
        $transmission = isset($_GET['transmission']) ? $_GET['transmission'] : '';
        $fuelTypes = isset($_GET['fuelTypes']) ? $_GET['fuelTypes'] : [];
        $mileage = isset($_GET['mileage']) ? $_GET['mileage'] : '';
        $doors = isset($_GET['doors']) ? $_GET['doors'] : '';

        if (isset($transmission)) {
            // Apply the filtering function to each XML response
            $filteredVehicles = filterVehicles($xmlresEuro, $transmission, $doors, $powerKWMin, $powerKWMax, $fuelTypes); // Filter vehicles
    
            // Create new XML structure for filtered results
            $carsXml = new SimpleXMLElement('<Cars></Cars>');
    
            foreach ($filteredVehicles as $vehicle) {
                $car = $carsXml->addChild('Car');
                foreach ($vehicle->attributes() as $key => $value) {
                    $car->addAttribute($key, $value);
                }
            }
    
            // Output the filtered XML
            header('Content-Type: text/xml');
            echo $carsXml->asXML(); // Display the XML structure
        }
    }

    ?>
    <?php include 'header.php'; ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" id="popUp" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Search Your Next Rental</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php include 'searchWidget.php'; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row" style="background: url('./images/res_back.jpg');background-repeat: no-repeat;
        background-attachment: local;
        background-size: 100% 100%;
        height: 14rem;">
        <div class="container align-content-center">
            <h1 class="text-white text-center">
                Explore, Discover & Save, 24,000 <br>
                Locations & Local Support
            </h1>
        </div>
    </div>
    <!-- location details -->
    <div class="row py-3 p-md-0" style="background-color: rgba(35,31,32,.5)!important">
        <div class="container row">
            <div class="col-md-10">
                <?php //foreach($results as $res):
                ?>
                <p class="text-center my-3 text-white">
                    <?php echo $pickupDetails; ?>, <?php echo $pickDate; ?> -> <?php echo $dropoffDetails; ?>, <?php echo $dropDate; ?>
                </p>
                <?php //endforeach;
                ?>
            </div>
            <div class="col-md-2 d-flex align-content-center justify-content-center">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg">MODIFY</button>
            </div>
        </div>
    </div>
    <!-- search dropdown desktop-->
    <div style="background-color: #ced1d4; height: auto;" class="p-3 d-none d-md-block">
        <div>
            <form class="container d-flex flex-wrap align-items-baseline" action="" method="get">
                <details>
                    <summary class="dropdown">Transmission Types <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                    <div class="shown">
                        <input type="checkbox" name="transmission" id="automatic" value="Automatic">
                        <label for="automatic" style="font-size: 1rem;">Automatic Only</label><br>
                        <input type="checkbox" name="transmission" id="manual" value="Manual">
                        <label for="manual" style="font-size: 1rem;">Manual Only</label>
                    </div>
                </details>
                <details>
                    <summary class="dropdown">Fuel Type or Electric <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                    <div class="shown">
                        <input type="checkbox" id="diesel" name="fuelTypes[]" value="Diesel">
                        <label for="diesel" style="font-size: 1rem;">Diesel</label><br>
                        <input type="checkbox" id="electric" name="fuelTypes[]" value="Electric">
                        <label for="electric" style="font-size: 1rem;">Electric</label><br>
                        <input type="checkbox" id="hybrid" name="fuelTypes[]" value="Hybrid">
                        <label for="hybrid" style="font-size: 1rem;">Hybrid</label><br>
                        <input type="checkbox" id="unspecifiedFuel" name="fuelTypes[]" value="Unspecified">
                        <label for="unspecifiedFuel" style="font-size: 1rem;">Unspecified fuel/power</label>
                    </div>
                </details>
                <details>
                    <summary class="dropdown">Mileage <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                    <div class="shown">
                        <input type="radio" id="unlimited" name="mileage" value="Unlimited">
                        <label for="unlimited" style="font-size: 1rem;">Unlimited</label><br>
                        <input type="radio" id="limited" name="mileage" value="Limited">
                        <label for="limited" style="font-size: 1rem;">Limited</label>
                    </div>
                </details>
                <details>
                    <summary class="dropdown">Doors <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                    <div class="shown">
                        <input type="radio" id="doors2" name="doors" value="2">
                        <label for="doors2" style="font-size: 1rem;">2</label><br>
                        <input type="radio" id="doors4" name="doors" value="4">
                        <label for="doors4" style="font-size: 1rem;">4+</label>
                    </div>
                </details>
                <div class="d-flex gap-3">
                    <button name="search" type="submit" class="btn btn-primary">Search</button>
                    <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>" class="btn btn-danger">Reset</a>
                </div>
            </form>
        </div>
    </div>
    <!-- search dropdown mobile-->
    <div style="background-color: #ced1d4; height: auto;padding:1rem 0.5rem" class="d-md-none">
        <div>
            <form action="" method="get">
                <div class="d-flex">
                    <div>
                        <details>
                            <summary class="dropdown">Transmission Types <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                            <div class="shown">
                                <input type="checkbox" name="transmission" id="automatic-mobile" value="Automatic">
                                <label for="automatic-mobile" style="font-size: 1rem;">Automatic Only</label><br>
                                <input type="checkbox" name="transmission" id="manual-mobile" value="Manual">
                                <label for="manual-mobile" style="font-size: 1rem;">Manual Only</label>
                            </div>
                        </details>
                        <details>
                            <summary class="dropdown">Fuel Type or Electric <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                            <div class="shown">
                                <input type="checkbox" id="diesel-mobile" name="fuelTypes[]" value="Diesel">
                                <label for="diesel-mobile" style="font-size: 1rem;">Diesel</label><br>
                                <input type="checkbox" id="electric-mobile" name="fuelTypes[]" value="Electric">
                                <label for="electric-mobile" style="font-size: 1rem;">Electric</label><br>
                                <input type="checkbox" id="hybrid-mobile" name="fuelTypes[]" value="Hybrid">
                                <label for="hybrid-mobile" style="font-size: 1rem;">Hybrid</label><br>
                                <input type="checkbox" id="unspecifiedFuel-mobile" name="fuelTypes[]" value="Unspecified">
                                <label for="unspecifiedFuel-mobile" style="font-size: 1rem;">Unspecified fuel/power</label>
                            </div>
                        </details>
                    </div>
                    <div>
                        <details>
                            <summary class="dropdown">Mileage <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                            <div class="shown">
                                <input type="radio" id="unlimited-mobile" name="mileage" value="Unlimited">
                                <label for="unlimited-mobile" style="font-size: 1rem;">Unlimited</label><br>
                                <input type="radio" id="limited-mobile" name="mileage" value="Limited">
                                <label for="limited-mobile" style="font-size: 1rem;">Limited</label>
                            </div>
                        </details>
                        <details>
                            <summary class="dropdown">Doors <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                            <div class="shown">
                                <input type="radio" id="doors2-mobile" name="doors" value="2">
                                <label for="doors2-mobile" style="font-size: 1rem;">2</label><br>
                                <input type="radio" id="doors4-mobile" name="doors" value="4+">
                                <label for="doors4-mobile" style="font-size: 1rem;">4+</label>
                            </div>
                        </details>
                    </div>
                </div>
                <div class="d-flex justify-content-center gap-3">
                    <button name="search" type="submit" class="btn btn-primary">Search</button>
                    <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>" class="btn btn-danger">Reset</a>
                </div>
            </form>
        </div>
    </div>
    <!-- price table desktop-->
    <div id="price_table" class="container d-none d-md-block">
        <table class="table table-bordered my-4">
            <thead>
                <tr>
                    <th scope="col"></th>
                    <th scope="col">
                        <img src="./images/economy.jpg" alt="">
                        <p class="text-center">Economy</p>
                    </th>
                    <th scope="col">
                        <img src="./images/compact.jpg" alt="">
                        <p class="text-center">Compact </p>
                    </th>
                    <th scope="col">
                        <img src="./images/midsize.jpg" alt="">
                        <p class="text-center">Midsize</p>
                    </th>
                    <th scope="col">
                        <img src="./images/LuxurySportsCar.jpg" alt="">
                        <p class="text-center">Luxury/Sports Car</p>
                    </th>
                    <th scope="col">
                        <img src="./images/suv.jpg" alt="">
                        <p class="text-center">SUV</p>
                    </th>
                    <th scope="col">
                        <img src="./images/stationwagon.jpg" alt="">
                        <p class="text-center">Station Wagon</p>
                    </th>
                    <th scope="col">
                        <img src="./images/VanPeopleCarrier.jpg" alt="">
                        <p class="text-center">Van/People Carrier</p>
                    </th>
                    <th scope="col">
                        <img src="./images/7-12PassengerVans.jpg" alt="">
                        <p class="text-center">7-12 Passenger Vans</p>
                    </th>
                </tr>
            </thead>
            <tbody>
            <tr id="Euro">
                <th class="d-flex justify-content-center" id="Euro_image">
                    <img src="./images/EuroCar.svg" alt="">
                </th>
                <?php
                // Loop through categories and display rates
                foreach ($categoriesEuro as $category => $codes) {
                    $found = false; // Track if we find a vehicle in the category
                    $dataSize = implode(',', $codes); // Dynamically generate the sizes for data-size

                    echo '<td class="text-center" data-size="' . $dataSize . '">';

                    foreach ($xmlresEuro->serviceResponse->carCategoryList->carCategory as $vehicle) {
                        if (in_array((string)$vehicle['carCategoryCode'], $codes)) {
                            // Display the rate for the first matching vehicle
                            $rate = (float)$vehicle['carCategoryPowerHP']; // Replace with actual rate data if different
                            echo 'AUD ' . $rate;
                            $found = true;
                            break; // Only display the first matching vehicle
                        }
                    }

                    if (!$found) {
                        echo 'Not Available';
                    }

                    echo '</td>';
                }
                ?>
            </tr>

            </tbody>
        </table>
    </div>
    <!-- price table mobile-->
    <div id="price_table_mobile" class="container d-md-none">
        <table class="table table-bordered my-4">
            <thead>
                <tr>
                    <th></th>
                    <th class="d-flex justify-content-center">
                        <img style="width: 9rem;" src="./images/EuroCar.svg" alt="">
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categoriesEuro as $category => $codes): ?>
                    <tr>
                        <!-- Category Column -->
                        <td scope="col" class="text-center align-middle">
                            <div style="display: flex; flex-direction: column; align-items: center;">
                                <img src="./images/<?php echo strtolower($category); ?>.jpg" alt="" style="max-width: 100px; height: auto;">
                                <p style="text-align: center; width: 100%; word-wrap: break-word; margin-top: 5px;">
                                    <?php echo ucfirst($category); ?>
                                </p>
                            </div>
                        </td>

                        <!-- Company Column -->
                        <td class="text-center mobile" data-size="<?php echo implode(',', $codes); ?>">
                            <?php
                            // Loop through vehicle categories and match with the given size codes
                            $found = false; // Track if we find a vehicle in the category

                            foreach ($xmlresEuro->serviceResponse->carCategoryList->carCategory as $vehicle) {
                                if (in_array((string)$vehicle['carCategoryCode'], $codes)) {
                                    // Display the rate for the first matching vehicle
                                    $rate = (float)$vehicle['carCategoryPowerHP']; // Replace with actual rate data if needed
                                    echo 'AUD ' . number_format($rate, 2);
                                    $found = true;
                                    break; // Only display the first matching vehicle
                                }
                            }

                            if (!$found) {
                                echo 'Not Available';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>



    <!-- result line desktop-->
    <div class="d-none d-md-block">
        <div style="background-color: #ced1d4; height: auto; display: none;" class="p-3" id="results-count-container">
            <div class="container d-flex justify-content-start text-white">
                <span id="results-count">SHOWING 0 RESULTS</span>
            </div>
        </div>
    </div>
    <!-- result line mobile-->
    <div class="d-md-none">
        <div style="background-color: #ced1d4; height: auto; display: none;" class="p-3" id="results-count-container-mobile">
            <div class="container d-flex justify-content-start text-white">
                <span id="results-count-mobile">SHOWING 0 RESULTS</span>
            </div>
        </div>
    </div>
    <!-- results cards ZE desktop-->
    <div class="d-none d-md-block">
        <div class="container">
            <div id="vehicle-list-Euro" class="vehicle-list">
                <?php
                if (isset($xmlresEuro->serviceResponse->carCategoryList->carCategory)) {
                    // Loop through each vehicle in the carCategoryList
                    foreach ($xmlresEuro->serviceResponse->carCategoryList->carCategory as $vehicle) {
                        // Extract details as strings or integers as necessary
                        $name = (string) $vehicle['carCategorySample']; // Vehicle sample name
                        $passengers = (string) $vehicle['carCategorySeats']; // Number of seats
                        $luggage = (string) $vehicle['carCategoryBaggageQuantity']; // Baggage capacity
                        $transmission = (string) $vehicle['carCategoryAutomatic'] === 'Y' ? 'Automatic' : 'Manual'; // Transmission type
                        $rate = (float) $vehicle['carCategoryPowerHP']; // Use Power HP as a placeholder for rate
                        $final = calculatePercentage($markUp, $rate); // Calculate markup
                        $currency = "USD"; // Placeholder for currency
                        $vendor = "Hertz"; // Example vendor
                        $image = "https://images.hertz.com/vehicles/220x128/default.jpg"; // Placeholder image
                        $vendorLogo = "./images/EuroCar.svg"; // Placeholder for vendor logo
                        $reference = (string) $vehicle['carCategoryCode']; // Car category code as reference

                        // Use the carCategoryCode (e.g., CDAR, CFAR, etc.) as the data-size value
                        $dataSize = (string) $vehicle['carCategoryCode']; 

                        // Output the vehicle HTML with the correct data-size
                        echo '
                        <div class="res_card res_hertz vehicle-item" data-size="' . $dataSize . '">
                            <div class="row">
                                <div class="col-4 d-grid">
                                    <img style="width:20rem;" src="' . $image . '" alt="' . $name . '">
                                    <img src="' . $vendorLogo . '" alt="' . $vendor . '">
                                </div>
                                <div class="col-4">
                                    <strong>' . $name . '</strong>
                                    <p>OR SIMILAR | ' . strtoupper($transmission) . ' CLASS</p>
                                    <div class="d-flex gap-2 my-3">
                                        <div class="car_spec">' . ucfirst($transmission) . '</div>
                                        <div class="car_spec">
                                            <img src="./images/door-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/person-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/S-luggage-icon.png" alt="">' . $luggage . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/snow-icon.png" alt="">
                                        </div>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/plane-icon.png" alt="">
                                        <label for=""> On Airport</label>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/km-icon.png" alt="">
                                        <label for=""> Unlimited Kilometres</label>
                                    </div>
                                    <div class="text-primary" style="">
                                        + Terms and Conditions
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="res_pay">
                                        <div>
                                            <p>Insurances Package</p>
                                            <p>Rates starting at ...</p>
                                        </div>
                                        <div>
                                            <p>' . $currency . ' ' . $final . '</p>
                                        </div>
                                    </div>
                                    <div class="res_pay">
                                        <div class="d-flex">
                                            <a href="book.php?reference=' . $reference . '&vdNo=ZE" class="btn btn-primary">BOOK NOW</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<p>No vehicles available</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <!-- results cards ZE mobile-->
    <div class="d-md-none">
        <div>
            <div id="vehicle-list-Euro-mobile" class="vehicle-list-mobile container">
                <?php
                // Loop through each vehicle in the XML
                if (isset($xmlresEuro->serviceResponse->carCategoryList->carCategory)) {
                    // Loop through each vehicle in the carCategoryList
                    foreach ($xmlresEuro->serviceResponse->carCategoryList->carCategory as $vehicle) {
                        // Extract details as strings or integers as necessary
                        $name = (string) $vehicle['carCategorySample']; // Vehicle sample name
                        $passengers = (string) $vehicle['carCategorySeats']; // Number of seats
                        $luggage = (string) $vehicle['carCategoryBaggageQuantity']; // Baggage capacity
                        $transmission = (string) $vehicle['carCategoryAutomatic'] === 'Y' ? 'Automatic' : 'Manual'; // Transmission type
                        $rate = (float) $vehicle['carCategoryPowerHP']; // Use Power HP as a placeholder for rate
                        $final = calculatePercentage($markUp, $rate); // Calculate markup
                        $currency = "USD"; // Placeholder for currency
                        $vendor = "Hertz"; // Example vendor
                        $image = "https://images.hertz.com/vehicles/220x128/default.jpg"; // Placeholder image
                        $vendorLogo = "./images/EuroCar.svg"; // Placeholder for vendor logo
                        $reference = (string) $vehicle['carCategoryCode']; // Car category code as reference

                        // Use the carCategoryCode (e.g., CDAR, CFAR, etc.) as the data-size value
                        $dataSize = (string) $vehicle['carCategoryCode']; 

                        // Output the HTML for each vehicle, hide them initially
                        echo '
                        <div class="res_card vehicle-item-mobile" data-size="' . $dataSize . '">
                            <div>
                                <div class="d-grid">
                                    <img style="width:20rem;" src="' . $image . '" alt="' . $name . '">
                                    <img src="' . $vendorLogo . '" alt="' . $vendor . '">
                                </div>
                                <div>
                                    <strong>' . $name . '</strong>
                                    <p>OR SIMILAR | ' . strtoupper($transmission) . ' CLASS</p>
                                    <div class="d-flex gap-2 my-3">
                                        <div class="car_spec">' . ucfirst($transmission) . '</div>
                                        <div class="car_spec">
                                            <img src="./images/door-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/person-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/S-luggage-icon.png" alt="">' . $luggage . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/snow-icon.png" alt="">
                                        </div>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/plane-icon.png" alt="">
                                        <label for=""> On Airport</label>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/km-icon.png" alt="">
                                        <label for=""> Unlimited Kilometres</label>
                                    </div>
                                    <div class="text-primary" style="">
                                        + Terms and Conditions
                                    </div>
                                </div>
                                <div>
                                    <div class="res_pay">
                                        <div>
                                            <p>Insurances Package</p>
                                            <p>Rates starting at ...</p>
                                        </div>
                                        <div>
                                            <p>' . $currency . ' ' . $final . '</p>
                                        </div>
                                    </div>
                                    <div class="res_pay">
                                        <div class="d-flex">
                                            <a href="book.php?reference=' . $reference . '&vdNo=ZE"; class="btn btn-primary">BOOK NOW</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<p>No vehicles available</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Hide all res_card elements initially
        document.querySelectorAll('.res_card').forEach(function(card) {
            card.style.display = 'none';
        });

        document.querySelectorAll('td.text-center').forEach(function(td) {
            td.addEventListener('click', function() {
                var selectedSizes = td.getAttribute('data-size').split(','); // Get the list of sizes for the selected category
                var vendorRow = td.closest('tr').id; // Get the id of the closest row to identify the vendor (e.g., hertz, dollar, thrifty)

                // Hide all vehicle lists first
                document.querySelectorAll('.vehicle-list').forEach(function(list) {
                    list.style.display = 'none'; // Hide all lists
                });

                // Show the specific vendor's vehicle list
                var vendorList = document.getElementById('vehicle-list-' + vendorRow);
                vendorList.style.display = 'block';
                if (!vendorList) {
                    console.error(`Element with id 'vehicle-list-${vendorRow}' not found`);
                    return;
                }


                // Hide all vehicles in this list first
                vendorList.querySelectorAll('.vehicle-item').forEach(function(vehicle) {
                    vehicle.style.display = 'none';
                });

                // Show the vehicles that match the selected sizes
                var vehiclesShown = false; // Track whether we display any vehicles
                var vehicleCount = 0; // Track how many vehicles are displayed

                selectedSizes.forEach(function(size) {
                    var matchingVehicles = vendorList.querySelectorAll('.vehicle-item[data-size="' + size + '"]');
                    matchingVehicles.forEach(function(vehicle) {
                        vehicle.style.display = 'block'; // Show matching vehicles
                        vehiclesShown = true;
                        vehicleCount++; // Increment the count for each shown vehicle
                    });
                });

                // If no vehicles are shown, handle the empty case
                if (!vehiclesShown) {
                    alert('No matching vehicles found.');
                }

                // Update the results count dynamically
                if (vehicleCount > 0) {
                    document.getElementById('results-count').innerText = 'SHOWING ' + vehicleCount + ' RESULTS';
                    document.getElementById('results-count-container').style.display = 'block'; // Show the results count section
                } else {
                    document.getElementById('results-count-container').style.display = 'none'; // Hide the results count if no vehicles are found
                }
            });
        });
        // // Add event listener to all 'td.mobile' elements for mobile view
        document.querySelectorAll('td.mobile').forEach(function(td) {
            td.addEventListener('click', function() {
                var selectedSizes = td.getAttribute('data-size').split(','); // Get the list of sizes for the selected category
                var vendorRow = 'Euro'; // Adjust based on the vendor
                
                // Hide all vehicle lists first
                document.querySelectorAll('.vehicle-list-mobile').forEach(function(list) {
                    list.style.display = 'none'; // Hide all lists
                });

                // Show the specific vendor's vehicle list
                var vendorList = document.getElementById('vehicle-list-' + vendorRow + '-mobile');
                vendorList.style.display = 'block';

                // Hide all vehicles in this list first
                vendorList.querySelectorAll('.vehicle-item-mobile').forEach(function(vehicle) {
                    vehicle.style.display = 'none';
                });

                // Show the vehicles that match the selected sizes
                var vehiclesShown = false;
                var vehicleCount = 0; // Initialize the vehicle count

                selectedSizes.forEach(function(size) {
                    var matchingVehicles = vendorList.querySelectorAll('.vehicle-item-mobile[data-size="' + size + '"]');
                    matchingVehicles.forEach(function(vehicle) {
                        vehicle.style.display = 'block'; // Show matching vehicles
                        vehiclesShown = true;
                        vehicleCount++; // Increment the vehicle count
                    });
                });

                // If no vehicles are shown, show an alert
                if (!vehiclesShown) {
                    alert('No matching vehicles found.');
                }

                // Update the results count dynamically
                if (vehicleCount > 0) {
                    document.getElementById('results-count-mobile').innerText = 'SHOWING ' + vehicleCount + ' RESULTS';
                    document.getElementById('results-count-container-mobile').style.display = 'block'; // Show the results count section
                } else {
                    document.getElementById('results-count-container-mobile').style.display = 'none'; // Hide the results count if no vehicles are found
                }
            });
        });
    });
</script>

</html>