<?php
session_start();
$requiredeuroBooking = $_SESSION['requiredeuroBooking'];
echo "<pre>";
var_dump($requiredeuroBooking);
echo "</pre>";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>

    <script>
        let data = {
            "pickup": "Kiran", // Ensure consistent key name
            "dropOff": "ritika"
        };
        fetch("updateSession.php", {
            method: "POST",
            headers: { // Correct header casing
                "Content-Type": "application/json"
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            console.log("Success", result);
        })
        .catch(error => {
            console.log("Error", error);
        });
    </script>
</body>

</html>
