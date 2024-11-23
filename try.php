<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City Selector with Price</title>
    <style>
        .container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .row-container {
            display: flex;
            flex-direction: column;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .column {
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
            font-weight: bold;
            color: #333;
        }

        .route-price {
            margin-top: 12px;
            font-size: 18px;
            color: #007bff;
        }

        /* Responsive Design */
        @media (max-width: 769px) {
            .row {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Initial Row -->
        <div class="row-container">
            <div class="row">
                <div class="column">
                    <label for="pickup">Pickup</label>
                    <select class="city-select pickup" name="pickup">
                        <option value="">Select Pickup City</option>
                        <option value="Sydney">Sydney</option>
                        <option value="Melbourne">Melbourne</option>
                        <option value="Brisbane">Brisbane</option>
                        <option value="Perth">Perth</option>
                        <option value="Adelaide">Adelaide</option>
                    </select>
                </div>
                <div class="column">
                    <label for="dropup">Dropup</label>
                    <select class="city-select dropup" name="dropup">
                        <option value="">Select Dropup City</option>
                        <option value="Sydney">Sydney</option>
                        <option value="Melbourne">Melbourne</option>
                        <option value="Brisbane">Brisbane</option>
                        <option value="Perth">Perth</option>
                        <option value="Adelaide">Adelaide</option>
                    </select>
                </div>
            </div>
            <div class="route-display" style="display: none;">
                Route: <span class="route"></span>
            </div>
            <div class="route-price" style="display: none;">
                Price: $<span class="price"></span>
            </div>
        </div>
    </div>

    <script>
        // Define route prices
        const routePrices = {
            "Sydney-Melbourne": 100,
            "Melbourne-Brisbane": 120,
            "Brisbane-Perth": 150,
            "Perth-Adelaide": 130,
            "Adelaide-Sydney": 110,
            // Add more routes and prices as needed
        };

        // Function to update the route and price display
        function updateRouteDisplay(rowContainer) {
            const pickup = rowContainer.querySelector(".pickup").value;
            const dropup = rowContainer.querySelector(".dropup").value;
            const routeDisplay = rowContainer.querySelector(".route-display");
            const routePrice = rowContainer.querySelector(".route-price");
            const routeText = rowContainer.querySelector(".route");
            const priceText = rowContainer.querySelector(".price");

            if (pickup && dropup) {
                const routeKey = `${pickup}-${dropup}`;
                const price = routePrices[routeKey] || "Not Available";

                routeText.textContent = `${pickup} → ${dropup}`;
                priceText.textContent = price;

                routeDisplay.style.display = "block";
                routePrice.style.display = "block";
            } else {
                routeDisplay.style.display = "none";
                routePrice.style.display = "none";
            }
        }

        // Add event listeners for pickup and dropup selection
        document.addEventListener("change", (event) => {
            if (event.target.classList.contains("pickup") || event.target.classList.contains("dropup")) {
                const rowContainer = event.target.closest(".row-container");
                updateRouteDisplay(rowContainer);
            }
        });
    </script>
</body>

</html>