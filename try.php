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
    $responseEuro = $_SESSION['responseEuro'];
    $xmlresEuro = new SimpleXMLElement($responseEuro);
    // var_dump($xmlresEuro);
  ?>
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
                // Categories to match your table headings
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

                // Loop through categories and display rates
                foreach ($categoriesEuro as $category => $codes) {
                    $found = false; // Track if we find a vehicle in the category

                    echo '<td class="text-center">';
                    
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

  <div class="">
    <div class="container">
        <div>
            <?php
                // Assuming you have loaded your XML data into $xmlresEuro variable

                // Loop through each vehicle in the carCategoryList
                foreach ($xmlresEuro->serviceResponse->carCategoryList->carCategory as $vehicle) {
                    // Get the vehicle details
                    $name = (string)$vehicle['carCategorySample'];
                    $passengers = (string)$vehicle['carCategorySeats'];
                    $luggage = (string)$vehicle['carCategoryBaggageQuantity'];
                    $transmission = (string)$vehicle['carCategoryAutomatic'] === 'Y' ? 'Automatic' : 'Manual';
                    $rate = (float)$vehicle['carCategoryPowerHP']; // Placeholder for rate (replace with actual rate if available)
                    $currency = "USD"; // Currency placeholder
                    $vendor = "Hertz"; // Static vendor name or replace if dynamic
                    $image = "https://images.hertz.com/vehicles/220x128/default.jpg"; // Use dynamic image if available
                    $vendorLogo = "./images/hertz.png"; // Use dynamic logos if needed
                    $reference = (string)$vehicle['carCategoryCode']; // Use carCategoryCode as reference

                    // Output the HTML for each vehicle, hide them initially
                    echo '
                    <div class="res_card res_hertz vehicle-item" data-size="' . $passengers . '" >
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
                                        <p>' . $currency . ' ' . $rate . '</p>
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
            ?>
        </div>
    </div>
</div>

</body>
</html>