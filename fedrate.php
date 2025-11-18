<?php

function getFedRateData() {
    $url = "https://www.investing.com/central-banks/fed-rate-monitor";

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) {
        return [];
    }

    // Load HTML into DOMDocument
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    $fedData = [
        'meeting_info' => [],
        'probabilities' => [],
        'rate_table' => [],
        'last_updated' => ''
    ];

    // Target only the FIRST cardWrapper
    $firstCard = $xpath->query("//div[@class='cardWrapper'][1]")->item(0);
    
    if (!$firstCard) {
        return $fedData;
    }

    // Extract meeting date from the first card header
    $meetingDate = $xpath->query(".//div[@class='fedRateDate']", $firstCard);
    if ($meetingDate->length > 0) {
        $fedData['meeting_date'] = trim($meetingDate->item(0)->nodeValue);
    }

    // Extract meeting time and future price from the first card only
    $infoFed = $xpath->query(".//div[@class='infoFed']//i", $firstCard);
    if ($infoFed->length >= 2) {
        $fedData['meeting_info']['meeting_time'] = trim($infoFed->item(0)->nodeValue);
        $fedData['meeting_info']['future_price'] = trim($infoFed->item(1)->nodeValue);
    }

    // Extract probability percentages from the first card only
    $percItems = $xpath->query(".//div[@class='percfedRateItem']", $firstCard);
    foreach ($percItems as $item) {
        $spans = $item->getElementsByTagName('span');
        if ($spans->length >= 2) {
            $fedData['probabilities'][] = [
                'target_rate' => trim($spans->item(0)->nodeValue),
                'probability' => trim($spans->item(1)->nodeValue)
            ];
        }
    }

    // Extract table data from the first card only
    $table = $xpath->query(".//table[contains(@class, 'fedRateTbl')]//tbody/tr", $firstCard);
    foreach ($table as $row) {
        $cells = $row->getElementsByTagName('td');
        if ($cells->length >= 4) {
            $fedData['rate_table'][] = [
                'target_rate' => trim(preg_replace('/\s+/', ' ', $cells->item(0)->nodeValue)),
                'current_probability' => trim($cells->item(1)->nodeValue),
                'previous_day_probability' => trim($cells->item(2)->nodeValue),
                'previous_week_probability' => trim($cells->item(3)->nodeValue)
            ];
        }
    }

    // Extract last updated timestamp from the first card only
    $updated = $xpath->query(".//div[@class='fedUpdate']", $firstCard);
    if ($updated->length > 0) {
        $fedData['last_updated'] = trim($updated->item(0)->nodeValue);
    }

    return $fedData;
}

// Only output JSON if this file is accessed directly (optional - for testing)
if (basename($_SERVER['PHP_SELF']) == 'fedrate.php') {
    header('Content-Type: application/json');
    echo json_encode(getFedRateData(), JSON_PRETTY_PRINT);
}
?>