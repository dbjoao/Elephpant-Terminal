<?php
// Set the URL to fetch
$url = 'https://www.benzinga.com/quote/NVDA/analyst-ratings';

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

// Execute cURL request
$html = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Load HTML into DOMDocument
$dom = new DOMDocument();
@$dom->loadHTML($html);

// Create XPath object
$xpath = new DOMXPath($dom);

// Find all tables in the page
$tables = $xpath->query("//table");

// Extract table data
$tableData = [];
foreach ($tables as $table) {
    $rows = $xpath->query(".//tr", $table);
    $currentTable = [];
    
    foreach ($rows as $row) {
        $cells = $xpath->query(".//th | .//td", $row);
        $rowData = [];
        
        foreach ($cells as $cell) {
            $rowData[] = trim($cell->textContent);
        }
        
        if (!empty($rowData)) {
            $currentTable[] = $rowData;
        }
    }
    
    if (!empty($currentTable)) {
        $tableData[] = $currentTable;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NVDA Analyst Ratings</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
        }
        .table-container {
            background: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .no-data {
            padding: 20px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>NVDA Analyst Ratings</h1>
    
    <?php
    if (!empty($tableData)) {
        echo "<p>Found " . count($tableData) . " table(s) on the page:</p>";
        
        foreach ($tableData as $index => $table) {
            echo "<div class='table-container'>";
            echo "<h3>Table " . ($index + 1) . "</h3>";
            echo "<table>";
            
            // First row as header
            if (!empty($table[0])) {
                echo "<thead><tr>";
                foreach ($table[0] as $header) {
                    echo "<th>" . htmlspecialchars($header) . "</th>";
                }
                echo "</tr></thead>";
            }
            
            // Rest of the rows as data
            echo "<tbody>";
            for ($i = 1; $i < count($table); $i++) {
                echo "<tr>";
                foreach ($table[$i] as $cell) {
                    echo "<td>" . htmlspecialchars($cell) . "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody>";
            
            echo "</table>";
            echo "</div>";
        }
    } else {
        echo "<div class='no-data'>";
        echo "<p>No tables found on the page.</p>";
        echo "<p>The content may be loaded dynamically with JavaScript.</p>";
        echo "</div>";
    }
    ?>
</body>
</html>