<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City Selector with Price</title>
    <style>
        .location_container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .location_row-container {
            display: flex;
            flex-direction: column;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .location_row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .location_column {
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .city-select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .add-button {
            padding: 10px 15px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            align-self: flex-start;
        }

        .add-button:hover {
            background-color: #0056b3;
        }

        .route-display {
            margin-top: 10px;
            font-size: 17px;
            color: #333;
        }

        .route-price {
            margin-top: 12px;
            font-size: 18px;
            color: #007bff;
        }

        /* Responsive Design */
        @media (max-width: 769px) {
            .location_row {
                flex-direction: column;
            }
        }
    </style>
</head>
<?php
session_start();
$requiredeuroBooking = $_SESSION['requiredeuroBooking'];
include 'dbconn.php';
$cityName = "Mel";
$sql = "SELECT * FROM `filter_locations_euro` WHERE groupName Like '$cityName%'";
$result = $conn->query($sql);

// Fetch all rows into an array
$locations = $result->fetch_all(MYSQLI_ASSOC);
function formatDateAndTime($dateTimeString)
{
    // Convert the date-time string to a DateTime object
    $dateTime = new DateTime($dateTimeString);

    // Format the date as YYYYMMDD
    $formattedDate = $dateTime->format('Ymd');

    // Format the time as HHMM (24-hour format)
    $formattedTime = $dateTime->format('Hi');

    // Return both values as an array
    return [$formattedTime];
}

?>

<body>
    <?php echo '<div class="location_container">';
        echo '<div class="location_row-container">';
            echo '<div class="location_row">';
                echo '<div class="location_column">';
                    echo '<label for="pickup">Pickup</label>';
                    echo '<select class="city-select pickup" name="pickup">';
                        echo '<option value="">Select Pickup City</option>';
                        foreach ($locations as $location){
                            echo '<option value="' . $location['stationCode'] . '">' . $location['cityaddress'] . '</option>';
                        }
                    echo '</select>';
                echo '</div>';
                echo '<div class="location_column">';
                    echo '<label for="dropup">Dropup</label>';
                    echo '<select class="city-select dropup" name="dropup">';
                        echo '<option value="" selected disabled>Select Dropup City</option>';
                        foreach ($locations as $location){
                            echo '<option value="' . $location['stationCode'] . '">' . $location['cityaddress'] . '</option>';
                        }
                    echo '</select>';
                echo '</div>';
            echo '</div>';
            echo '<div class="route-display" style="display: none;">';
                echo 'Route: <span class="route"></span>';
            echo '</div>';
            echo '<div class="route-price" style="display: none;">';
                echo 'Price: <span class="price"></span>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
    ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pickupSelect = document.querySelector('.pickup');
            const dropupSelect = document.querySelector('.dropup');
            let carCategory = "CDAR";
            let infoObject = {
                pickUpDateEuro: <?php echo json_encode($requiredeuroBooking['pickDate']); ?>,
                pickUpTimeEuro: <?php echo json_encode(formatDateAndTime($requiredeuroBooking['pickTime'])[0]); ?>,
                dropOffDateEuro: <?php echo json_encode($requiredeuroBooking['dropDate']); ?>,
                dropOffTimeEuro: <?php echo json_encode(formatDateAndTime($requiredeuroBooking['dropTime'])[0]); ?>
            };
            console.log(infoObject);

            function logRouteValues() {
                const selectedPickup = pickupSelect.options[pickupSelect.selectedIndex].value;
                const selectedDropup = dropupSelect.options[dropupSelect.selectedIndex].value;

                if (selectedPickup && selectedDropup) {
                    console.log('Pickup:', selectedPickup, 'Dropup:', selectedDropup);
                    callGetQuotemobile(selectedPickup, selectedDropup, infoObject, carCategory);
                    sendPickupDropData(selectedPickup, selectedDropup);
                }
            }

            pickupSelect.addEventListener('change', logRouteValues);
            dropupSelect.addEventListener('change', logRouteValues);

        });

        function callGetQuotemobile(pick, drop, infoObject, carCategory) {
            const data = {
                carCategory: carCategory,
                pickup: pick,
                dropoff: drop,
                pickUpTime: infoObject.pickUpTimeEuro,
                dropOffTime: infoObject.dropOffTimeEuro,
                pickUpDate: infoObject.pickUpDateEuro,
                dropOffDate: infoObject.dropOffDateEuro
            };

            console.log("getQuote Data:", data);

            fetch('getQuote.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data),
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Quote Response:", data);

                    const priceContainer = document.querySelector('.route-price');
                    const priceElement = document.querySelector('.price');


                    if (data.quote) {
                        const rate = data.quote.rate;
                        const currency = data.quote.currency;

                        if (rate > 0) {
                            // Show the price and route
                            priceContainer.style.display = 'block';
                            priceElement.innerHTML = `$ ${currency} ${rate}`;
                        } else {
                            // Handle cases where rate is 0 or unavailable
                            priceContainer.style.display = 'block';
                            priceElement.innerHTML = "No price available.";
                        }


                    } else {
                        // Handle missing quote data
                        priceContainer.style.display = 'block';
                        priceElement.innerHTML = "Quote not available.";
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const priceContainer = document.querySelector('.route-price');
                    const priceElement = document.querySelector('.price');

                    priceContainer.style.display = 'block';
                    priceElement.innerHTML = "Error retrieving quote.";
                });
        }

        function sendPickupDropData(pickup, dropup) {
            if (pickup && dropup) {
                const data = {
                    pickup: pickup,
                    dropup: dropup
                };
                const routeDisplay = document.querySelector('.route-display');
                const routeElement = document.querySelector('.route');

                console.log("Sending data:", data);

                fetch('processRoute.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(responseData => {
                        console.log("Response from server:", responseData);

                        if (responseData.success) {
                            // Update the route display
                            routeDisplay.style.display = 'block';
                            routeElement.innerHTML = `${responseData.pickRoute} to ${responseData.dropRoute}`;
                        } else {
                            alert(`Error: ${responseData.message}`);
                        }
                    })
                    .catch(error => {
                        console.error("Error occurred:", error);
                        alert("An error occurred while sending data.");
                    });
            } else {
                alert("Please select both Pickup and Dropup cities.");
            }
        }
    </script>
</body>

</html>