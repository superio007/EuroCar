<?php
session_start();

header('Content-Type: application/json');

// Enable error reporting during development
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        $carCategory = $data['carCategory'] ?? '';
        $pickup = $data['pickup'] ?? [];
        $dropoff = $data['dropoff'] ?? [];
        $pickEuro = $pickup['euro'] ?? '';
        $dropEuro = $dropoff['euro'] ?? '';
        $pickDate = $data['pickUpDate'];
        $dropDate = $data['dropOffDate'];
        $pickTime = $data['pickUpTime'];
        $droptime = $data['dropOffTime'];
        
        

        function getQuote($carCategory, $pickEuro, $dropEuro, $pickDate, $dropDate, $pickTime, $dropTime)
        {
            $xmlRequestEuro = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                <message>
                    <serviceRequest serviceCode=\"getQuote\">
                        <serviceParameters>
                            <reservation carCategory=\"$carCategory\" rateId=\"RATE_ID\">
                                <checkout stationID=\"$pickEuro\" date=\"$pickDate\" time=\"$pickTime\"/>
                                <checkin stationID=\"$dropEuro\" date=\"$dropDate\" time=\"$dropTime\"/>
                            </reservation>
                            <driver countryOfResidence=\"AU\"/>
                        </serviceParameters>
                    </serviceRequest>
                </message>";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://applications-ptn.europcar.com/xrs/resxml');
            curl_setopt($ch, CURLOPT_POST, 1);
            $postFields = http_build_query([
                'XML-Request' => $xmlRequestEuro,
                'callerCode' => '1132097',
                'password' => '02092024',
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: text/xml'
            ]);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['error' => 'cURL Error: ' . $error];
            }

            return ['response' => $response];
        }

        $response = getQuote($carCategory, $pickEuro, $dropEuro, $pickDate, $dropDate, $pickTime, $droptime);
        $rate = 0;
        $currency = 'USD';

        if (isset($response['response']) && !empty($response['response'])) {
            $xml = simplexml_load_string($response['response']);
            if ($xml !== false && isset($xml->serviceResponse->reservation->quote)) {
                $rate = (float) $xml->serviceResponse->reservation->quote['basePrice'];
                $currency = (string) $xml->serviceResponse->reservation->quote['currency'];
            }
        }

        echo json_encode([
            'carCategory' => $carCategory,
            'pickup' => $pickup,
            'dropoff' => $dropoff,
            'quote' => [
                'rate' => $rate,
                'currency' => $currency
            ],
            'pickDetails' => [
                'pickDate' => $pickDate,
                'pickTime' => $pickTime,
                'dropDate' => $dropDate,
                'dropTime' => $droptime
            ]
        ]);
    } else {
        echo json_encode(['error' => 'Invalid JSON payload']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
