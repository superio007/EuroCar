<?php
session_start();

// Enable error reporting for debugging
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
$currentTimestamp = time();
$customerReference = uniqid();

// Store the unique ID in the session
$_SESSION['customerReference'] = $customerReference;

$leadData = $_SESSION['leadformData'];
$selectedData = $_SESSION['selectedDataArray'];
$contactNumber = $leadData['phone'];
$name = $leadData['fname'] . ' '. $leadData['lname'];
$email = $leadData['email'];
$price = $selectedData[0]['price']; 

// Retrieve session data
$apiData = $_SESSION['apiData'] ?? null;
$leadPassenger = $_SESSION['leadformData'] ?? null;
$usersData = $_SESSION['usersData'] ?? null;
$arrData = $_SESSION['arrData'];
//  var_dump($arrData);

// Database connection details
$db_host = 'localhost';
$db_user = 'devyourbestwayho_dev_u2';
$db_pass = 'fFF~vGL!DIej';
$db_name = 'devyourbestwayho_dev_v2';

// Create connection
$mysqli = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$mysqli) {
    die("Connection failed: " . mysqli_connect_error());
}

$show = false;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['call-pay'])) {
    $paymentMethod = $_POST['payment'] ?? '';

    if ($paymentMethod === 'azuPay') {
            function getToken()
    {
        $url = "https://jb-auth-uat.azurewebsites.net/Client";
        $postData = array(
            "claims" => array(
                "userName" => "stuart@geelongtravel.com.au"
            ),
            "clientId" => "515909a2-c9a6-46ee-a923-6cb1170e3571",
            "secret" => "oF7QWRUYYUmISudsRgixrg=="
        );
        $postDataJson = json_encode($postData);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json"
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataJson);
        $response = curl_exec($ch);
        $tokenData = json_decode($response, true);
        if ($response === false) {
            die("Error: Curl request failed: " . curl_error($ch));
        }
        curl_close($ch);
        $token = $tokenData['accessToken'];
        return $token;
    }
    if (isset($_COOKIE['token'])) {
        $token = $_COOKIE['token'];
    } else {
        $token = getToken();
    }
    if (isset($_GET['packageId'])) {
        $url = "https://b2b.journeybeyondrail.com/api/search/agent/search-packages?package=$select_type&from=$formatted_date";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $token"
        ));
        $response = curl_exec($ch);
        if ($response === false) {
            die("Error: Curl request failed: " . curl_error($ch));
        }
        curl_close($ch);
        $data = json_decode($response, true);
        $filteredData = $data['packageResponses'];
        foreach ($filteredData as $filter) {
            $packagePriceTypeId = $filter['priceTypeId'];
            $bookingTypeId = $filter['bookingTypeId'];
            break;
        }
        $filteredData = array_filter($filteredData, function ($item) use ($packageId) {
            return $item['packageId'] == $packageId;
        });
    }
    function formatDate($dateString) {
        // Create a DateTime object from the input date string
        $date = new DateTime($dateString);
        
        // Format the date to the desired format
        return $date->format('d/m/Y H:i:s');
    }
    $maildate = formatDate($arrData['packageDepartureDate']);
        
        $apiToken = getToken();
        // $url = "https://jb-b2b-api-test.azurewebsites.net/api/Booking/create-and-confirm";
              $url = "https://b2b.journeybeyondrail.com/api/Booking/create-and-confirm";
        // request
        $postApiJson = json_encode($arrData);
    //  echo $postApiJson;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer $apiToken"
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postApiJson);
        $response = curl_exec($ch);
        // status code here 
        $apiData = json_decode($response, true);
        // var_dump($apiData);
  
  if ($apiData['isSuccess'] === false) {
        // If the API response indicates failure, display an alert with the error message
        echo "<script>
            alert('Error: " . addslashes($apiData['errorMessage']) . "');
            if (confirm('Would you like to go back to the home page?')) {
                window.location.href='https://dev.yourbestwayhome.com.au/aussietrain_livesite/'; // Replace with the home page URL
            }
            </script>";
    }
   
    $_SESSION['apiData'] = $apiData;
        $passenger = 1;
        $leadPassenger = $apiData['bookingDetails']['leadPassenger'];
      if ($response === false) {
            die("Error: Curl request failed: " . curl_error($ch));
        } else {
            if ($apiData['isSuccess'] == "true") {
                // Send the email before redirecting
                $mail = new PHPMailer(true);
                try {
                    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'info@geelongtravel.com.au';
                    $mail->Password   = 'hcckndbthbhmfxpq';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->setFrom('info@aussietrains.com.au', 'AussiTrains');
                    $mail->addAddress($leadData['email'], $leadData['fname']);
                    // $mail->addCC('info@aussietrains.com.au');
                    $mail->isHTML(true);
                    $mail->Subject = 'Booking Reference Number: ' . $apiData['bookingReferenceNumber'];
        
                    $mail->Body = "
                    <!DOCTYPE html>
                    <html lang='en'>
                    <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Booking Details</title>
                    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH' crossorigin='anonymous' />
                    <link href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css' rel='stylesheet' type='text/css' media='screen'>
                    <script src='https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js'></script>
      
                    <script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js'></script>
                    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js' integrity='sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz' crossorigin='anonymous'></script>
                    <style>
                        .table-responsive {
                            overflow-x: auto;
                        }
                        @media screen and (max-width: 769px) {
                            #nav-menu {
                                display: none;
                            }
                            #logo {
                                margin: 0;
                            }
                            .table-responsive {
                                overflow-x: auto;
                            }
                            .table-hover th, .table-hover td {
                                display: block;
                                width: 100%;
                            }
                            .table-hover thead {
                                display: none;
                            }
                            .table-hover tr {
                                display: flex;
                                flex-direction: column;
                                border-bottom: 1px solid #dee2e6;
                                margin-bottom: 1rem;
                                padding-bottom: 1rem;
                            }
                            .table-hover td {
                                border: none;
                            }
                            .table-hover td::before {
                                content: attr(data-label);
                                font-weight: bold;
                                width: 100%;
                                display: inline-block;
                            }
                        }
                    </style>
                    </head>
                    <body>
                    <div class='container'>
                        <strong>Thank you for booking with Aussie Trains</strong>
                        <p class='text-center my-5' style='text-transform:uppercase;'>Booking Details</p>
                        <table class='table table-hover'>
                            <tbody>
                                <tr>
                                    <th scope='row'>Booking ID</th>
                                    <td>{$apiData['bookingDetails']['bookingId']}</td>
                                </tr>
                                <tr>
                                    <th scope='row'>Name</th>
                                    <td>{$apiData['bookingDetails']['name']}</td>
                                </tr>
                                <tr>
                                    <th scope='row'>Reference Number</th>
                                    <td>{$apiData['bookingDetails']['referenceNumber']}</td>
                                </tr>
                                <tr>
                                    <th scope='row'>Total Amount</th>
                                    <td>\${$apiData['bookingDetails']['totalSellAmount']}</td>
                                </tr>
                                <tr>
                                    <th scope='row'>Departure Date</th>
                                    <td>{$maildate}</td>
                                </tr>
                            </tbody>
                        </table>
            
                        <h2 class='text-center my-4' style='text-transform:uppercase;'>Lead <span style='color:#154782;'>Passenger</span> Details</h2>
                        <div class='table-responsive'>
                            <table class='table table-hover'>
                                <thead>
                                    <tr>
                                        <th>Sr/no</th>
                                        <th>Title</th>
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td data-label='Sr/no'>1</td>
                                        <td data-label='Title'>{$leadPassenger['title']}</td>
                                        <td data-label='First Name'>{$leadPassenger['firstName']}</td>
                                        <td data-label='Last Name'>{$leadPassenger['lastName']}</td>
                                    </tr>
                                    <tr>
                                        <th colspan='2'>Email</th>
                                        <th colspan='2'>Telephone</th>
                                    </tr>
                                    <tr>
                                        <td colspan='2' data-label='Email'>{$leadPassenger['email']}</td>
                                        <td colspan='2' data-label='Telephone'>{$leadPassenger['telephone']}</td>
                                    </tr>
                                    <tr>
                                        <td data-label='Vegetarian Meal'>" . ($leadPassenger['vegetarianMeal'] ? 'Yes' : 'No') . "</td>
                                        <td data-label='Lactose-Free Meal'>" . ($leadPassenger['lactoseFreeMeal'] ? 'Yes' : 'No') . "</td>
                                        <td data-label='Gluten-Free Meal'>" . ($leadPassenger['glutenFreeMeal'] ? 'Yes' : 'No') . "</td>
                                        <td data-label='Vegan Meal'>" . ($leadPassenger['veganMeal'] ? 'Yes' : 'No') . "</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p>For more details, click: <a href='https://dev.yourbestwayhome.com.au/aussietrain_livesite/show.php?referenceNo={$apiData['bookingReferenceNumber']}' style='background-color: #ffbb00; border: none; color: white;  padding: 10px 16px; margin: 10px 0px; text-align: center;  text-decoration: none;  display: inline-block;  font-size: 13px;'>Check Your Details Here</a></p>
                    </div>
                    </body>
                    </html>
                    ";
        
                    $mail->AltBody = 'Thanks for your booking. For more details, visit: https://dev.yourbestwayhome.com.au/aussietrain_livesite/show.php?referenceNo=' . $apiData['bookingReferenceNumber'];
                    $mail->send();
                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
                $Ref = $apiData['bookingReferenceNumber'];
                // Redirect after email is sent
                echo "<script>window.location.href=\"show.php?referenceNo=$Ref\"</script>";
            }
        }

        // AzuPay logic

        function generateRandomSecurityCode($length = 10) {
            $prefix = uniqid(); // Generate a unique prefix
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            $randomString = '';

            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, strlen($characters) - 1)];
            }

            return $prefix . $randomString;
        }

        // Check if apiData and leadPassenger are set
        if (!$apiData || !$leadPassenger) {
            die("Session data is missing.");
        }

        $clientTransactionId = $apiData['bookingDetails']['bookingId'] ?? null;
        $paymentAmount = isset($apiData['bookingDetails']['totalSellAmount']) ? (int)$apiData['bookingDetails']['totalSellAmount'] : 0;

        if (!$clientTransactionId || !$paymentAmount) {
            die("Session data is missing or invalid.");
        }

        $client = new \GuzzleHttp\Client();
        $authorization_code = 'SECRB12ADA_3137b12750b376c3b9809e254c35b512_V8hcgQ9RTlK265WL'; // uat
        $access_url = 'https://api-uat.azupay.com.au/v1'; // uat
        $payID = "payments@gauratravel.com.au";
        $payIDSuffix = "gauratravel.com.au";
        $clientId = "3137b12750b376c3b9809e254c35b512"; // uat

        $paymentDescription = "Balance payment for Booking #" . $apiData['bookingDetails']['bookingId'];

        $next_3_days = date("Y-m-d", strtotime("+3 days"));
        $current_time = date("H:i:s");
        $next_3_days2 = $next_3_days . "T" . $current_time . "Z";

        $full_payment_auth_code = generateRandomSecurityCode();

        // Update auth code in the database
        $sql_authcode_store = "UPDATE wpk4_backend_travel_bookings
            SET full_payment_auth_code = '$full_payment_auth_code'
            WHERE order_id='$clientTransactionId'";
        $results_authcode_store = mysqli_query($mysqli, $sql_authcode_store);

        $query_get_order_date = "SELECT * FROM wpk4_backend_travel_bookings WHERE order_id='$clientTransactionId'";
        $result_get_order_date = mysqli_query($mysqli, $query_get_order_date);
        $order_date_for_azupay = '';
        while ($row_get_order_date = mysqli_fetch_assoc($result_get_order_date)) {
            $order_date_for_azupay = $row_get_order_date['order_date'];
        }

        $next_1_days_24hrs = date('d/m/Y H:i:s', strtotime($order_date_for_azupay . ' +1 day'));

        try {
            $azupay_response = $client->request('POST', $access_url . '/paymentRequest', [
                'body' => json_encode([
                    'PaymentRequest' => [
                        'paymentNotification' => [
                            'paymentNotificationEndpointUrl' => 'https://dev.yourbestwayhome.com.au/aussietrain_livesite/azupay-post-receiver.php',
                            'paymentNotificationAuthorizationHeaderValue' => $full_payment_auth_code
                        ],
                        'multiPayment' => true,
                        'paymentExpiryDatetime' => $next_3_days2,
                        'payIDSuffix' => $payIDSuffix,
                        'clientId' => $clientId,
                        'clientTransactionId' => (string)$clientTransactionId, // Ensure this is a string
                        'paymentAmount' => $paymentAmount, // Ensure paymentAmount is an integer
                        'paymentDescription' => $paymentDescription
                    ]
                ]),
                'headers' => [
                    'Authorization' => $authorization_code,
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ],
            ]);

            $azupay_data = json_decode($azupay_response->getBody(), true);

            $paymentRequestId = $azupay_data['PaymentRequestStatus']['paymentRequestId'];

            $current_date_timestamp = date('Y-m-d H:i:s');

            // Insert into custom payments table
            $sql_insert_custom_payments = "INSERT INTO wpk4_backend_travel_booking_custom_payments 
            (order_id, case_id, type_of_payment, email, amount, payment_client_id, auth_code, requested_on, requested_by, payment_request_id)
            VALUES ('$clientTransactionId', '0', 'Balance Payment', '{$leadPassenger['email']}', '$paymentAmount', '$clientTransactionId', '$full_payment_auth_code', '$current_date_timestamp', 'wptcron', '$paymentRequestId')";
            mysqli_query($mysqli, $sql_insert_custom_payments);

            $paymentRequeststatus = $azupay_data['PaymentRequestStatus']['status'];
            $paymentRequestcreatedDateTime = $azupay_data['PaymentRequestStatus']['createdDateTime'];
            $paymentRequestcheckoutUrl = $azupay_data['PaymentRequest']['checkoutUrl'];
            $paymentRequestExpiryDatetime = date("d/m/Y H:i:s", strtotime($azupay_data['PaymentRequest']['paymentExpiryDatetime']));
            $paymentRequestpayID = $azupay_data['PaymentRequest']['payID'];
            $paymentRequestpaymentDescription = $azupay_data['PaymentRequest']['paymentDescription'];
            $paymentRequestAmount = $azupay_data['PaymentRequest']['paymentAmount'];
            $paymentRequestclientId = $azupay_data['PaymentRequest']['clientId'];

            $paymentRequestAmount = number_format((float)$paymentRequestAmount, 2, '.', '');
            $fname = $leadPassenger['firstName'] ?? 'Customer'; // Default to 'Customer' if undefined
            $lname = $leadPassenger['lastName'] ?? ''; // Default to empty string if undefined

            $mailbody_azupay = '<img class="size-full wp-image-42537 aligncenter" src="https://dev.yourbestwayhome.com.au/aussietrain_livesite/logo.svg" alt="Ausie Trains" width="203" height="50" />
            <table class="wp-travel-wrapper" style="color: #5d5d5d; font-family: Roboto, sans-serif; margin: auto;" width="100%" cellspacing="0" cellpadding="0">
            <tbody>
                <tr class="wp-travel-content" style="background: #fff;">
                    <td class="wp-travel-content-top" style="background: #fff; margin: 0; padding: 20px 25px;" colspan="2" align="left">
                        <p style="line-height: 1.55; font-size: 14px;">Dear ' . ucfirst(strtolower($fname)) . ' ' . ucfirst(strtolower($lname)) . ',</p>
                        <p style="line-height: 1.55; font-size: 14px;"><b>Booking ID: #' . $clientTransactionId . '</b></p>
                    </td>
                </tr>
                <tr class="wp-travel-content" style="background: #fff;">
                    <td style="font-size: 14px; background: #fff; margin: 0; padding: 0px 0px 8px 25px;" colspan="2" align="left">
                        Kindly proceed with the balance payment to confirm the booking by using the below Pay by Bank option.
                        </br>
                        Balance to be paid is $' . $paymentRequestAmount . '
                        </br></br>
                        Your payment is due immediately and this link will expire if not paid by ' . $next_3_days2 . '
                        </br>
                        <a href="' . $paymentRequestcheckoutUrl . '" style="background-color: #ffbb00; border: none; color: white;  padding: 10px 16px; margin: 10px 0px; text-align: center;  text-decoration: none;  display: inline-block;  font-size: 13px;">Click Here</a>
                        </br>';
            $mailbody_azupay .= 'Please be advised that this inbox is not being monitored. Should you have any further inquiries, please contact our Customer Care Team at 1300 359 463 or +91 33350 76444.
                    </br>
                    Thank you for choosing Ausie Trains for your travel needs. We look forward to serving you on your upcoming journey.
                    </br>
                    </br>
                    Best regards,
                    </br>
                    Ausie Trains
                    </br>
                    </td>
                </tr>
                </tbody>
                </table>';
            $referencrNo = $apiData['bookingReferenceNumber'];
            $mailbody_azupay = stripslashes($mailbody_azupay);
            $emailsubject = "Balance payment information - " . $clientTransactionId;
            $mail = new PHPMailer(true);

            try {
                $mail->IsSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->Port = '587';
                $mail->SMTPAuth = true;
                $mail->Username = '';
                $mail->Password = 'hcckndbthbhmfxpq';
                $mail->SMTPSecure = 'tls';
                $mail->From = 'info@aussietrains.com.au';
                $mail->FromName = 'AusieTrains';
                $mail->AddAddress($leadPassenger['email'], $fname . ' ' . $lname);
                // $mail->addCC('info@aussietrains.com.au');
                $mail->WordWrap = 50;
                $mail->IsHTML(true);
                $mail->Subject = $emailsubject;
                $mail->Body = $mailbody_azupay;

                if ($mail->Send()) {
                    $show = true;
                    echo "<script>window.location.href = \"show.php?referenceNo=$referencrNo&ispaid=No\"</script>";
                    exit();
                }
            } catch (Exception $e) {
                echo "Mailer Error: " . $mail->ErrorInfo;
            }

            $mail->clearAddresses();
            setcookie("token", "", time() - 3600, "/");
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            echo 'Error: ' . $e->getMessage();
            echo 'Request: ' . $e->getRequest()->getBody();
            echo 'Response: ' . $e->getResponse()->getBody();
        }
    } elseif ($paymentMethod === 'zenithPay2f') {
        // Zenith Pay logic
        $client = new \GuzzleHttp\Client();

        $emailUsed = $leadPassenger['email'] ?? 'jdoe@example.com';
        $email = (trim($emailUsed) === '') ? 'jdoe@example.com' : $emailUsed;

        $api_key = '9wnrr7RBIEWmD4xV2uXSIQ';
        $username = 'austrains';
        $password = 'dN73ygwCacrMXfBH';
        $merchantCode = 'austrains';
        $payMode = '0'; // As per your logic
        $amount = (float)$apiData['bookingDetails']['totalSellAmount'];
        $amountForFingerprint = number_format($amount * 100, 0, '', '');
        // $muPID = 'REFERENCE1';
        $muPID = uniqid('order_');  // Generates a unique order reference starting with 'order_'

        $timestamp = gmdate('Y-m-d\TH:i:s');

        // JavaScript-based fingerprint calculation should be moved to client-side.

        try {
            // Initialize the payment using the Zenith Pay API
            $payment = $client->request('POST', 'https://pay.travelpay.com.au/Online/v4', [
                'json' => [
                    'apiKey' => $api_key,
                    'merchantCode' => $merchantCode,
                    'fingerprint' => '', // This will be handled client-side
                    'merchantUniquePaymentId' => $muPID,
                    'timestamp' => $timestamp,
                    'redirectUrl' => 'https://dev.yourbestwayhome.com.au/aussietrain_livesite/payment_response.php',
                    'mode' => $payMode,
                    'customerName' => $leadPassenger['firstName'] ?? 'John Snow',
                    'customerReference' => 'REFERENCE1',
                    'paymentAmount' => number_format($amount, 2, '.', ''),
                    'title' => 'AussieTrains',
                    'customerEmail' => $email,
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ]);

            $paymentResponse = json_decode($payment->getBody(), true);

            // Assuming the payment response contains a success status and a redirect URL
            if (isset($paymentResponse['status']) && $paymentResponse['status'] == 'success') {
                $paymentRedirectUrl = $paymentResponse['redirectUrl'];
                header("Location: $paymentRedirectUrl");
                exit();
            } else {
                //echo "Payment initiation failed. Please try again.";
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Methods</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.b2bpay.com.au/js/zenpay.payment.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <style>
        .payment-methods {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        .payment-method {
            margin-bottom: 20px;
        }
        .payment-method input[type="radio"] {
            margin-right: 10px;
        }
        .payment-method label {
            font-weight: bold;
        }
        .payment-method p {
            margin: 0;
            color: #555;
        }
        .payment-method p small {
            color: #999;
        }
        .text-center {
            margin-bottom: 20px;
        }
        .submit-button {
            text-align: center;
        }
        .hidden {
            display: none !important;
        }

    </style>
 
</head>
<body>
    <?php require 'header.php';?>

    <?php if (!$show): ?>
        <div class="payment-methods">
            <h2 class="text-center">Choose your Payment Method</h2>
            <form method="POST" action="">
                <div class="payment-method form-check">
                    <input class="form-check-input" type="radio" id="azuPay" name="payment" value="azuPay" required>
                    <!--<label class="form-check-label" for="azuPay">Bank transfer</label>-->
                    <!--<p>No extra fee.</p>-->
                     <label class="form-check-label" for="azuPay"> <img src="https://dev.yourbestwayhome.com.au/aussietrain_livesite/images/svfd.PNG" width="60px">Pay By Bank</label>
                    <br><p>Easy and secure</p>
                    <p>Pay by Bank is supported by all major banks. An easy
way for you to pay straight from your bank account, 24/7 365 days</p>
<a href="https://dev.yourbestwayhome.com.au/aussietrain_livesite/terms.php"> Terms and conditons</a>
                </div>
                <br>
                <br>
                <div class="payment-method form-check">
                    <input class="form-check-input" type="radio" id="zenithPay" name="payment" value="zenithPay" required>
                    <label class="form-check-label" for="zenithPay">Credit / Debit card</label>
                    <p>Fees: 1.2% for Mastercard, 1.4% for Visa, 1.8% for Amex, and 3.0% for all international cards.</p>
                </div>
                 <br>
                <br>
                <div class="submit-button" id="defaultSubmitButton">
                    <button type="submit" class="btn btn-primary" name="call-pay">Submit</button>
                </div>
                <div class="submit-button" id="button2" style="display:none;">
                    <button type="button" id="initiatePaymentButton" class="btn btn-primary">Proceed with Credit Card</button>
                </div>
            </form>
        </div>

        <div id="zenithPayForm" style="display:none;">
            
            <input type="text" id="payMode" value="0" hidden>
            <input type="text" id="selectOverrideFeePayer" hidden>
            <input type="text" id="selectUserMode" hidden>
            <input type="email" id="customerEmail" value="<?php echo htmlspecialchars($email); ?>" hidden>
            <input type="number" id="amount" value="<?php echo htmlspecialchars($price); ?>" hidden>
            <!--<button type="button" id="initiatePaymentButton">Initiate Payment</button>-->
        </div>
    <?php else: ?>
        <div class="d-flex justify-content-center text-center align-items-center" style="height: 80vh;">
            <div>
                <p>You will receive mail for payment information</p>
                <p>Select payment option!</p>
                <a href="show.php?referenceNo=<?php echo $apiData['bookingReferenceNumber']; ?>">Show Details</a>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function updateMuPID() {
            return 'REFERENCE1';
        }

        function setTimestamp() {
            const now = new Date();
            const year = now.getUTCFullYear();
            const month = String(now.getUTCMonth() + 1).padStart(2, '0');
            const day = String(now.getUTCDate()).padStart(2, '0');
            const hours = String(now.getUTCHours()).padStart(2, '0');
            const minutes = String(now.getUTCMinutes()).padStart(2, '0');
            const seconds = String(now.getUTCSeconds()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
        }

        function updateUrls() {
            return {
                displayUrl: 'https://pay.travelpay.com.au/Online/v4',
                urlRedirectFull: 'https://dev.yourbestwayhome.com.au/aussietrain_livesite/',
                callbackUrl: 'https://dev.yourbestwayhome.com.au/aussietrain_livesite/payment_response.php'
            };
        }
        

        $(document).ready(function() {
            $('input[type="radio"]').on('change', function () {
                console.log('Selected value: ' + $(this).val());  // Log the selected value

                if ($(this).val() === 'zenithPay') {
                    console.log('Hiding default submit button');  // Log the action
                    $('#defaultSubmitButton').hide();  // Hide the original submit button
                    $('#button2').show();  // Show the new button
                } else {
                    console.log('Showing default submit button');  // Log the action
                    $('#defaultSubmitButton').show();  // Show the original submit button if other options are selected
                    $('#button2').hide();  // Hide the new button
                }
            });
        });
        
        $('#initiatePaymentButton').on('click', function () {
            const emailUsed = $('#customerEmail').val();
            const email = (typeof emailUsed === 'undefined' || !emailUsed || emailUsed.trim() === '') ? 'jdoe@example.com' : emailUsed;
        
            const version = 'v4';
            const payMode = $('#payMode').val();
            const amount = parseFloat($('#amount').val());
            let timestamp = setTimestamp();
            
            const paymentData = {
                email: email,
                amount: amount.toFixed(2),
                payMode: payMode,
                version: version,
                timestamp: timestamp
            };
            
        
            // Send the necessary information to the backend via AJAX to generate the fingerprint and secure data
            $.ajax({
                url: 'pay_try_ajax.php', // Backend endpoint for processing sensitive data
                method: 'POST',
                data: paymentData,
                success: function (response) {
                    
                    // Assuming the backend returns 'strFingerprint', 'muPID', 'timestamp', and 'displayUrl'
                    //const { strFingerprint, muPID, timestamp, apiKey, merchantCode } = response;
        
                    // Initiate the payment using the values generated on the server-side
                    var payment = $.zpPayment({
                        url: 'https://pay.travelpay.com.au/Online/v4',
                        apiKey: response.apiKey, // The backend should return this if necessary
                        merchantCode: response.merchantCode, // Securely retrieved from the server
                        fingerprint: response.strFingerprint,
                        merchantUniquePaymentId: response.muPID,
                        timestamp: timestamp,
                        redirectUrl: 'https://dev.yourbestwayhome.com.au/aussietrain_livesite/payment_response.php',
                        mode: payMode,
                        customerName: "<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>",
                        customerReference: "<?php echo $_SESSION['customerReference']; ?>", 
                        paymentAmount: amount.toFixed(2),
                        title: 'AussieTrains',
                        customerEmail: email,
                        overrideFeePayer: 0,
                        userMode: 0,
                    });
                    console.log('msg:', payment);
                    payment.open(); // Open the payment window
                },
                error: function (error) {
                    console.error('Error initiating payment:', error);
                    alert('Failed to initiate payment. Please try again.');
                }
            });
        });


        $('#initiatePaymentButton_old').on('click', function () {
            const emailUsed = $('#customerEmail').val();
            const email = (typeof emailUsed === 'undefined' || !emailUsed || emailUsed.trim() === '') ? 'jdoe@example.com' : emailUsed;

            const version = 'v4';
        
            let muPID = <?php echo $currentTimestamp; ?>;
            let timestamp = setTimestamp();

            const api_key = $('#apiKey').val();
            const un = $('#username').val();
            const pw = $('#password').val();
            const mc = 'austrains'; //$('#merchantCode').val();
            const payMode = $('#payMode').val();
            const amount = parseFloat($('#amount').val());
            const amountForFingerprint = Math.round(amount * 100).toString();
            const urls = updateUrls();
            const displayUrl = urls.displayUrl;

            console.log('Values used for fingerprint:');
            console.log('api_key:', api_key);
            console.log('un:', un);
            console.log('pw:', pw);
            console.log('payMode:', payMode);
            console.log('amountForFingerprint:', amountForFingerprint);
            console.log('muPID:', muPID);
            console.log('timestamp:', timestamp);

            let strFingerprint;
            if (version === 'v3') {
                strFingerprint = CryptoJS.SHA1(
                    api_key + '|' + un + '|' + pw + '|' + payMode + '|' + amountForFingerprint + '|' + muPID + '|' + timestamp
                ).toString();
            } else if (version === 'v4') {
                strFingerprint = CryptoJS.SHA512(
                    api_key + '|' + un + '|' + pw + '|' + payMode + '|' + amountForFingerprint + '|' + muPID + '|' + timestamp
                ).toString();
            }

            console.log('Fingerprint:', strFingerprint);

            var payment = $.zpPayment({
                url: displayUrl,
                apiKey: api_key,
                merchantCode: mc,
                fingerprint: strFingerprint,
                merchantUniquePaymentId: muPID,
                timestamp: timestamp,
                redirectUrl: 'https://dev.yourbestwayhome.com.au/aussietrain_livesite/payment_response.php',
                mode: payMode,
                customerName: "<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>",
                // customerReference: "<?php echo uniqid(); ?>",
                        customerReference: "<?php echo $_SESSION['customerReference']; ?>", 
                paymentAmount: amount.toFixed(2),
                title: 'AussieTrains',
                customerEmail: email,
                overrideFeePayer: 0,
                userMode: 0,
            });

            payment.open();
        });

        $('input[type="radio"]').on('change', function () {
            if ($(this).val() === 'zenithPay') {
                $('#zenithPayForm').show();
            } else {
                $('#zenithPayForm').hide();
            }
        });
    </script>

    <?php include 'footer.php';?>
</body>
</html>
