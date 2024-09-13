<?php

// Define the XML request payload for getting cities
$xmlRequest = '<?xml version="1.0" encoding="UTF-8"?>
<message>
  <serviceRequest serviceCode="getCarCategories">
    <serviceParameters>
      <reservation>
        <checkout stationID="MADT01" date="20240924"/>
        <checkin stationID="MADT01" date="20240925"/>
      </reservation>
    </serviceParameters>
  </serviceRequest>
</message>

';

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
