<?php
header('Content-Type: text/html; charset=utf-8');

// Function to format CIK to 10 digits
function formatCIK($cik) {
    return str_pad($cik, 10, '0', STR_PAD_LEFT);
}

// Function to get CIK from ticker
function getCIKFromTicker($ticker) {
    $ticker = strtoupper(trim($ticker));
    $jsonFile = 'company_tickers.json';
    
    if (!file_exists($jsonFile)) {
        return ['error' => 'company_tickers.json file not found'];
    }
    
    $jsonData = file_get_contents($jsonFile);
    $companies = json_decode($jsonData, true);
    
    foreach ($companies as $company) {
        if (isset($company['ticker']) && $company['ticker'] === $ticker) {
            return [
                'cik_str' => $company['cik_str'],
                'ticker' => $company['ticker'],
                'title' => $company['title']
            ];
        }
    }
    
    return ['error' => 'Ticker not found'];
}

// Function to get SEC submissions
function getSubmissions($cik) {
    $formattedCIK = formatCIK($cik);
    $url = "https://data.sec.gov/submissions/CIK{$formattedCIK}.json";
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: Company Name email@example.com'
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['error' => 'Unable to fetch submissions data'];
    }
    
    return json_decode($response, true);
}

// Function to parse Form 4 XML
function parseForm4XML($cik, $accessionNumber, $primaryDoc) {
    // Remove the xslF345X05/ prefix if present to get the actual XML filename
    $xmlFilename = preg_replace('/^xslF345X\d+\//', '', $primaryDoc);
    
    // Construct the direct XML URL
    $accessionNumberClean = str_replace('-', '', $accessionNumber);
    $xmlUrl = "https://www.sec.gov/Archives/edgar/data/{$cik}/{$accessionNumberClean}/{$xmlFilename}";
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: Company Name email@example.com'
        ]
    ];
    
    $context = stream_context_create($options);
    $xmlContent = @file_get_contents($xmlUrl, false, $context);
    
    if ($xmlContent === false) {
        return null;
    }
    
    $xml = @simplexml_load_string($xmlContent);
    if ($xml === false) {
        return null;
    }
    
    $data = [
        'periodOfReport' => (string)$xml->periodOfReport,
        'issuerName' => (string)$xml->issuer->issuerName,
        'issuerTradingSymbol' => (string)$xml->issuer->issuerTradingSymbol,
        'reportingOwnerName' => (string)$xml->reportingOwner->reportingOwnerId->rptOwnerName,
        'reportingOwnerCik' => (string)$xml->reportingOwner->reportingOwnerId->rptOwnerCik,
        'relationship' => [],
        'transactions' => [],
        'xmlUrl' => $xmlUrl
    ];
    
    // Get relationship info
    $rel = $xml->reportingOwner->reportingOwnerRelationship;
    if ((string)$rel->isDirector == '1') $data['relationship'][] = 'Director';
    if ((string)$rel->isOfficer == '1') $data['relationship'][] = 'Officer';
    if ((string)$rel->isTenPercentOwner == '1') $data['relationship'][] = '10% Owner';
    if ((string)$rel->isOther == '1') $data['relationship'][] = 'Other';
    if (!empty((string)$rel->officerTitle)) $data['relationship'][] = (string)$rel->officerTitle;
    
    // Parse non-derivative transactions
    if (isset($xml->nonDerivativeTable->nonDerivativeTransaction)) {
        foreach ($xml->nonDerivativeTable->nonDerivativeTransaction as $trans) {
            $transactionCode = (string)$trans->transactionCoding->transactionCode;
            $transactionType = '';
            switch ($transactionCode) {
                case 'P': $transactionType = 'Purchase'; break;
                case 'S': $transactionType = 'Sale'; break;
                case 'A': $transactionType = 'Award/Grant'; break;
                case 'M': $transactionType = 'Exercise'; break;
                case 'G': $transactionType = 'Gift'; break;
                case 'D': $transactionType = 'Disposition'; break;
                case 'F': $transactionType = 'Tax Withholding'; break;
                case 'I': $transactionType = 'Discretionary Transaction'; break;
                case 'W': $transactionType = 'Acquisition/Disposition by Will'; break;
                default: $transactionType = $transactionCode;
            }
            
            $acquiredDisposed = (string)$trans->transactionAmounts->transactionAcquiredDisposedCode->value;
            
            $data['transactions'][] = [
                'securityTitle' => (string)$trans->securityTitle->value,
                'transactionDate' => (string)$trans->transactionDate->value,
                'transactionType' => $transactionType,
                'transactionCode' => $transactionCode,
                'shares' => (string)$trans->transactionAmounts->transactionShares->value,
                'pricePerShare' => (string)$trans->transactionAmounts->transactionPricePerShare->value,
                'acquiredDisposed' => $acquiredDisposed,
                'acquiredDisposedText' => $acquiredDisposed == 'A' ? 'Acquired' : 'Disposed',
                'sharesOwned' => (string)$trans->postTransactionAmounts->sharesOwnedFollowingTransaction->value,
                'ownership' => (string)$trans->ownershipNature->directOrIndirectOwnership->value == 'D' ? 'Direct' : 'Indirect'
            ];
        }
    }
    
    // Parse derivative transactions
    if (isset($xml->derivativeTable->derivativeTransaction)) {
        foreach ($xml->derivativeTable->derivativeTransaction as $trans) {
            $transactionCode = (string)$trans->transactionCoding->transactionCode;
            $transactionType = '';
            switch ($transactionCode) {
                case 'P': $transactionType = 'Purchase'; break;
                case 'S': $transactionType = 'Sale'; break;
                case 'A': $transactionType = 'Award/Grant'; break;
                case 'M': $transactionType = 'Exercise'; break;
                case 'G': $transactionType = 'Gift'; break;
                case 'D': $transactionType = 'Disposition'; break;
                case 'F': $transactionType = 'Tax Withholding'; break;
                default: $transactionType = $transactionCode;
            }
            
            $acquiredDisposed = (string)$trans->transactionAmounts->transactionShares->value;
            
            $data['transactions'][] = [
                'securityTitle' => (string)$trans->securityTitle->value,
                'transactionDate' => (string)$trans->transactionDate->value,
                'transactionType' => $transactionType,
                'transactionCode' => $transactionCode,
                'shares' => (string)$trans->transactionAmounts->transactionShares->value,
                'pricePerShare' => (string)$trans->transactionAmounts->transactionPricePerShare->value ?? 'N/A',
                'acquiredDisposed' => (string)$trans->transactionAmounts->transactionAcquiredDisposedCode->value,
                'acquiredDisposedText' => (string)$trans->transactionAmounts->transactionAcquiredDisposedCode->value == 'A' ? 'Acquired' : 'Disposed',
                'sharesOwned' => (string)$trans->postTransactionAmounts->sharesOwnedFollowingTransaction->value,
                'ownership' => (string)$trans->ownershipNature->directOrIndirectOwnership->value == 'D' ? 'Direct' : 'Indirect',
                'isDerivative' => true
            ];
        }
    }
    
    return $data;
}

$result = null;
$companyInfo = null;

if (isset($_GET['ticker']) && !empty($_GET['ticker'])) {
    $ticker = $_GET['ticker'];
    $companyInfo = getCIKFromTicker($ticker);
    
    if (!isset($companyInfo['error'])) {
        $result = getSubmissions($companyInfo['cik_str']);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>EDGAR Search</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .section { margin-bottom: 30px; }
        .form4-section { background-color: #f0f8ff; padding: 15px; margin: 15px 0; border: 2px solid #4CAF50; border-radius: 5px; }
        .transaction-acquired { background-color: #d4edda; }
        .transaction-disposed { background-color: #e7e7e7ff; }
        .info-table { max-width: 800px; }
    </style>
</head>
<body>
    <?php require_once 'index.php'; ?>
    <form method="GET">
        <input type="text" name="ticker" value="<?php echo isset($_GET['ticker']) ? htmlspecialchars($_GET['ticker']) : ''; ?>" required>
        <button type="submit">Search</button>
    </form>
    
    <?php if ($companyInfo): ?>
        <div class="section">
            <h2>Company Information</h2>
            <?php if (isset($companyInfo['error'])): ?>
                <p><strong>Error:</strong> <?php echo htmlspecialchars($companyInfo['error']); ?></p>
            <?php else: ?>
                <table class="info-table">
                    <tr><th>Field</th><th>Value</th></tr>
                    <tr><td><strong>Ticker</strong></td><td><?php echo htmlspecialchars($companyInfo['ticker']); ?></td></tr>
                    <tr><td><strong>CIK</strong></td><td><?php echo htmlspecialchars($companyInfo['cik_str']); ?></td></tr>
                    <tr><td><strong>Formatted CIK</strong></td><td><?php echo formatCIK($companyInfo['cik_str']); ?></td></tr>
                    <tr><td><strong>Company Name</strong></td><td><?php echo htmlspecialchars($companyInfo['title']); ?></td></tr>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($result): ?>
        <div class="section">
            <h2>SEC Submissions Data</h2>
            <?php if (isset($result['error'])): ?>
                <p><strong>Error:</strong> <?php echo htmlspecialchars($result['error']); ?></p>
            <?php else: ?>
            

                <!-- Form 4 Filings (Insider Trading) -->
                <h3>Form 4 - Insider Trading Activity</h3>
                <?php 
                $filings = $result['filings']['recent'];
                $form4Count = 0;
                for ($i = 0; $i < count($filings['filingDate']); $i++): 
                    if ($filings['form'][$i] == '4'):
                        $form4Count++;
                        if ($form4Count > 15) break; // Limit to 15 Form 4s
                        
                        $accessionNumber = $filings['accessionNumber'][$i];
                        $primaryDoc = $filings['primaryDocument'][$i];
                        $form4Data = parseForm4XML($result['cik'], $accessionNumber, $primaryDoc);
                        
                        if ($form4Data):
                ?>
                <div class="form4-section">
                    <h4>Filing Date: <?php echo htmlspecialchars($filings['filingDate'][$i]); ?> | Period of Report: <?php echo htmlspecialchars($form4Data['periodOfReport']); ?></h4>
                    
                    <?php 
                    // Construct the styled XML URL with xslF345X05/
                    $accessionNumberClean = str_replace('-', '', $accessionNumber);
                    $xmlFilename = preg_replace('/^xslF345X\d+\//', '', $primaryDoc);
                    $styledXmlUrl = "https://www.sec.gov/Archives/edgar/data/{$result['cik']}/{$accessionNumberClean}/xslF345X05/{$xmlFilename}";
                    ?>
                    
                    <table class="info-table">
                        <tr><th>Field</th><th>Value</th></tr>
                        <tr><td>Reporting Owner</td><td><?php echo htmlspecialchars($form4Data['reportingOwnerName']); ?></td></tr>
                        <tr><td>Owner CIK</td><td><?php echo htmlspecialchars($form4Data['reportingOwnerCik']); ?></td></tr>
                        <tr><td>Relationship</td><td><?php echo htmlspecialchars(implode(', ', $form4Data['relationship'])); ?></td></tr>
                        <tr><td>Accession Number</td><td><?php echo htmlspecialchars($accessionNumber); ?></td></tr>
                        <tr><td>Styled XML Document</td><td><a href="<?php echo htmlspecialchars($styledXmlUrl); ?>" target="_blank">View Styled XML</a></td></tr>
                    </table>
                    
                    <?php if (!empty($form4Data['transactions'])): ?>
                    <h5>Transaction Details</h5>
                    <table>
                        <tr>
                            <th>Transaction Date</th>
                            <th>Security Type</th>
                            <th>Transaction Type</th>
                            <th>Code</th>
                            <th>Shares</th>
                            <th>Price Per Share</th>
                            <th>Total Value</th>
                            <th>Acquired/Disposed</th>
                            <th>Shares Owned After</th>
                            <th>Ownership Type</th>
                        </tr>
                        <?php foreach ($form4Data['transactions'] as $trans): 
                            $pricePerShare = floatval($trans['pricePerShare']);
                            $shares = floatval($trans['shares']);
                            $totalValue = $pricePerShare > 0 ? $pricePerShare * $shares : 0;
                            $rowClass = $trans['acquiredDisposed'] == 'A' ? 'transaction-acquired' : 'transaction-disposed';
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td><?php echo htmlspecialchars($trans['transactionDate']); ?></td>
                            <td><?php echo htmlspecialchars($trans['securityTitle']); ?></td>
                            <td><?php echo htmlspecialchars($trans['transactionType']); ?></td>
                            <td><?php echo htmlspecialchars($trans['transactionCode']); ?></td>
                            <td><?php echo number_format($shares); ?></td>
                            <td><?php echo $pricePerShare > 0 ? '$' . number_format($pricePerShare, 2) : 'N/A'; ?></td>
                            <td><?php echo $totalValue > 0 ? '$' . number_format($totalValue, 2) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($trans['acquiredDisposedText']); ?></td>
                            <td><?php echo number_format(floatval($trans['sharesOwned'])); ?></td>
                            <td><?php echo htmlspecialchars($trans['ownership']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>
                </div>
                <?php 
                        endif;
                    endif;
                endfor; 
                
                if ($form4Count == 0):
                ?>
                <p>No Form 4 filings found in recent submissions.</p>
                <?php endif; ?>

                <!-- Recent Filings -->
                <h3>All Recent Form 4 Filings</h3>
                <table>
                    <tr>
                        <th>Filing Date</th>
                        <th>Report Date</th>
                        <th>Form Type</th>
                        <th>Accession Number</th>
                        <th>Primary Document</th>
                    </tr>
                    <?php 
                    $form4DisplayCount = 0;
                    for ($i = 0; $i < count($filings['filingDate']); $i++): 
                        if ($filings['form'][$i] == '4'):
                            $form4DisplayCount++;
                            if ($form4DisplayCount > 20) break; // Limit to 20 Form 4s
                            
                            $accessionNumber = str_replace('-', '', $filings['accessionNumber'][$i]);
                            $primaryDoc = $filings['primaryDocument'][$i];
                            $docUrl = "https://www.sec.gov/Archives/edgar/data/{$result['cik']}/{$accessionNumber}/{$primaryDoc}";
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($filings['filingDate'][$i]); ?></td>
                        <td><?php echo htmlspecialchars($filings['reportDate'][$i] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($filings['form'][$i]); ?></td>
                        <td><?php echo htmlspecialchars($filings['accessionNumber'][$i]); ?></td>
                        <td><a href="<?php echo htmlspecialchars($docUrl); ?>" target="_blank">View Document</a></td>
                    </tr>
                    <?php 
                        endif;
                    endfor; 
                    ?>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>     