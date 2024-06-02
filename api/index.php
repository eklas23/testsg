<?php

// Set the timezone to Dhaka, Bangladesh
date_default_timezone_set('Asia/Dhaka');

// Get today's date in the format 'Y-m-d'
$today_date = date('Y-m-d');

// Construct the sitemap URL with today's date
$sitemap_url = "https://prothomalo.com/sitemap/sitemap-daily-$today_date.xml";

// Fetch the XML content
$xml_content = file_get_contents($sitemap_url);
if ($xml_content === FALSE) {
    die("Error: Unable to fetch the sitemap.");
}

// Load the XML content
$xml = new SimpleXMLElement($xml_content);

// Initialize an array to store the latest 10 items
$latest_items = [];

// Function to fetch the h1 content from a given URL
function fetch_h1_content($url) {
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute cURL
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return null;
    }

    // Load the HTML into DOMDocument
    $doc = new DOMDocument();
    @$doc->loadHTML($response, LIBXML_NOERROR | LIBXML_NOWARNING);

    // Use DOMXPath to query the h1 tag with data-title-0 attribute
    $xpath = new DOMXPath($doc);
    $h1 = $xpath->query('//h1[@data-title-0]');

    if ($h1->length > 0) {
        return $h1->item(0)->textContent;
    }

    return null;
}

// Loop through each <url> element and extract <loc> and <lastmod>
foreach ($xml->url as $url) {
    $loc = (string)$url->loc;
    $lastmod = (string)$url->lastmod;

    // Fetch the h1 content from the loc URL
    $title = fetch_h1_content($loc);

    // Add the title, loc, and lastmod to the latest_items array
    $latest_items[] = [
        'title' => $title,
        'loc' => $loc,
        'lastmod' => $lastmod
    ];

    // Break the loop if we have already collected 10 items
    if (count($latest_items) >= 11) {
        break;
    }
}

// Function to send data to Firebase
function send_to_firebase($data) {
    $firebase_url = 'https://ksmp-9b69f-default-rtdb.firebaseio.com/latest-items.json';
    $headers = [
        'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $firebase_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

// Send the latest items to Firebase
$response = send_to_firebase($latest_items);

// Output the response from Firebase
echo $response;
?>
