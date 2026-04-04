<?php
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/vendor/autoload.php');

class StockDataFetcher {
    private $symbol;
    private $config;
    private $client;
    
    public function __construct($symbol) {
        $this->symbol = $symbol;
        $this->config = Finnhub\Configuration::getDefaultConfiguration()->setApiKey('token', FINNHUB_API_KEY);
        $this->client = new Finnhub\Api\DefaultApi(
            new GuzzleHttp\Client(),
            $this->config
        );
    }
    
    public function getRecommendations() {
        try {
            return $this->client->recommendationTrends($this->symbol);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getEarnings() {
        $earnings = [];
        
        // Get past 6 months
        for ($i = 0; $i < 6; $i++) {
            $firstDay = date('Y-m-01', strtotime("-$i months"));
            $lastDay = date('Y-m-t', strtotime("-$i months"));
            
            try {
                $earningsData = $this->client->earningsCalendar($firstDay, $lastDay, $this->symbol, false);
                if (isset($earningsData['earningsCalendar']) && !empty($earningsData['earningsCalendar'])) {
                    $earnings = array_merge($earnings, $earningsData['earningsCalendar']);
                }
            } catch (Exception $e) {
                continue;
            }
        }
        
        // Get future 3 months
        for ($i = 1; $i <= 3; $i++) {
            $firstDay = date('Y-m-01', strtotime("+$i months"));
            $lastDay = date('Y-m-t', strtotime("+$i months"));
            
            try {
                $earningsData = $this->client->earningsCalendar($firstDay, $lastDay, $this->symbol, false);
                if (isset($earningsData['earningsCalendar']) && !empty($earningsData['earningsCalendar'])) {
                    $earnings = array_merge($earnings, $earningsData['earningsCalendar']);
                }
            } catch (Exception $e) {
                continue;
            }
        }
        
        // Sort by date descending
        usort($earnings, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });
        
        return $earnings;
    }
    
    public function getNextEarnings($earnings) {
        $currentDate = date('Y-m-d');
        foreach ($earnings as $earning) {
            if ($earning['date'] >= $currentDate) {
                return $earning;
            }
        }
        return null;
    }
    
    public function getEarningsSurprises() {
        try {
            return $this->client->companyEarnings($this->symbol, null);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getCompanyNews() {
        $newsFrom = date('Y-m-d', strtotime('-30 days'));
        $newsTo = date('Y-m-d');
        try {
            return $this->client->companyNews($this->symbol, $newsFrom, $newsTo);
        } catch (Exception $e) {
            return [];
        }
    }

    
    public function getStockGrades() {
        $gradesUrl = FMP_BASE_URL . "/grades?symbol=" . urlencode($this->symbol) . "&apikey=" . FMP_API_KEY;
        $gradesCurl = curl_init();
        curl_setopt($gradesCurl, CURLOPT_URL, $gradesUrl);
        curl_setopt($gradesCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($gradesCurl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($gradesCurl, CURLOPT_TIMEOUT, 10);
        $gradesResponse = curl_exec($gradesCurl);
        $gradesError = curl_error($gradesCurl);
        curl_close($gradesCurl);
        
        if (!$gradesError && $gradesResponse) {
            $allStockGrades = json_decode($gradesResponse, true) ?? [];
            return array_slice($allStockGrades, 0, 100);
        }
        return [];
    }
    
    public function getHistoricalGrades() {
        $historicalGradesUrl = FMP_BASE_URL . "/grades-historical?symbol=" . urlencode($this->symbol) . "&limit=100&apikey=" . FMP_API_KEY;
        $historicalResponse = @file_get_contents($historicalGradesUrl);
        
        if ($historicalResponse) {
            $allGrades = json_decode($historicalResponse, true) ?? [];
            $cutoffDate = date('Y-m-d', strtotime('-18 months'));
            $filteredGrades = [];
            
            foreach ($allGrades as $grade) {
                if (isset($grade['date']) && $grade['date'] >= $cutoffDate) {
                    $filteredGrades[] = $grade;
                }
            }
            return $filteredGrades;
        }
        return [];
    }
    
    public function getBenzingaTables() {
        $benzingaUrl = 'https://www.benzinga.com/quote/' . strtoupper($this->symbol) . '/analyst-ratings';
        $benzingaCh = curl_init();
        curl_setopt($benzingaCh, CURLOPT_URL, $benzingaUrl);
        curl_setopt($benzingaCh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($benzingaCh, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($benzingaCh, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($benzingaCh, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($benzingaCh, CURLOPT_TIMEOUT, 10);
        $benzingaHtml = curl_exec($benzingaCh);
        $benzingaError = curl_error($benzingaCh);
        curl_close($benzingaCh);
        
        $benzingaTables = [];
        if (!$benzingaError && $benzingaHtml) {
            $dom = new DOMDocument();
            @$dom->loadHTML($benzingaHtml);
            $xpath = new DOMXPath($dom);
            $tables = $xpath->query("//table");
            
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
                    $benzingaTables[] = $currentTable;
                }
            }
        }
        return $benzingaTables;
    }
    
    public function getZacksData() {
        $zacksUrl = 'https://www.zacks.com/stock/quote/' . strtoupper($this->symbol);
        $zacksCh = curl_init();
        curl_setopt($zacksCh, CURLOPT_URL, $zacksUrl);
        curl_setopt($zacksCh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($zacksCh, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($zacksCh, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($zacksCh, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($zacksCh, CURLOPT_TIMEOUT, 15);
        $zacksHtml = curl_exec($zacksCh);
        $zacksError = curl_error($zacksCh);
        curl_close($zacksCh);
        
        $zacksData = [
            'zacksRank' => null,
            'zacksRankText' => null,
            'styleScores' => [],
            'industryRank' => null,
            'industry' => null,
            'sectorRank' => null,
            'stockActivity' => [],
            'earningsData' => []
        ];
        
        if (!$zacksError && $zacksHtml) {
            $dom = new DOMDocument();
            @$dom->loadHTML($zacksHtml);
            $xpath = new DOMXPath($dom);
            
            // Extract Zacks Rank
            $rankNode = $xpath->query("//p[@class='rank_view']")->item(0);
            if ($rankNode) {
                $rankText = trim($rankNode->textContent);
                if (preg_match('/(\d+)-(\w+)/', $rankText, $matches)) {
                    $zacksData['zacksRank'] = $matches[1];
                    $zacksData['zacksRankText'] = $matches[2];
                }
            }
            
            // Extract Style Scores
            $styleScoreNode = $xpath->query("//div[@class='zr_rankbox composite_group']//p[@class='rank_view']")->item(0);
            if ($styleScoreNode) {
                $styleText = trim($styleScoreNode->textContent);
                if (preg_match_all('/([A-F])\s+(\w+)/', $styleText, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $zacksData['styleScores'][$match[2]] = $match[1];
                    }
                }
            }
            
            // Extract Industry
            $industryNode = $xpath->query("//div[@class='zr_rankbox industry_rank']//p[@class='rank_view']/a[@class='sector']")->item(0);
            if ($industryNode) {
                $zacksData['industry'] = trim($industryNode->textContent);
            }
            
            // Extract Industry Rank
            $industryRankNode = $xpath->query("//div[@class='zr_rankbox industry_rank']//p[@class='rank_view']/a[@class='status']")->item(0);
            if ($industryRankNode) {
                $zacksData['industryRank'] = trim($industryRankNode->textContent);
            }
            
            // Extract Stock Activity
            $activityDls = $xpath->query("//section[@id='stock_activity']//dl");
            foreach ($activityDls as $dl) {
                $dt = $xpath->query(".//dt", $dl)->item(0);
                $dd = $xpath->query(".//dd", $dl)->item(0);
                
                if ($dt && $dd) {
                    $label = trim($dt->textContent);
                    $value = trim($dd->textContent);
                    $label = preg_replace('/\s+/', ' ', $label);
                    
                    if (!empty($label) && !empty($value)) {
                        $zacksData['stockActivity'][$label] = $value;
                    }
                }
            }
            
            // Extract Key Earnings Data
            $earningsDls = $xpath->query("//section[@id='stock_key_earnings']//dl");
            foreach ($earningsDls as $dl) {
                $dt = $xpath->query(".//dt", $dl)->item(0);
                $dd = $xpath->query(".//dd", $dl)->item(0);
                
                if ($dt && $dd) {
                    $label = trim($dt->textContent);
                    $label = preg_replace('/More Info.*$/s', '', $label);
                    $label = preg_replace('/See the Full List.*$/s', '', $label);
                    $label = preg_replace('/Visit the.*$/s', '', $label);
                    $label = preg_replace('/Neither Zacks.*$/s', '', $label);
                    
                    if (preg_match('/^([^.!?]+[.!?])/', $label, $matches)) {
                        if (strlen($matches[1]) < 50) {
                            $label = $matches[1];
                        }
                    }
                    
                    $label = preg_replace('/\s+/', ' ', $label);
                    $label = trim($label);
                    $value = trim($dd->textContent);
                    
                    if (!empty($label) && !empty($value) && strlen($label) < 100) {
                        $zacksData['earningsData'][$label] = $value;
                    }
                }
            }
            
            // Extract Sector Rank
            $sectorRankNode = $xpath->query("//dl[contains(., 'Zacks Sector Rank')]//dd")->item(0);
            if ($sectorRankNode) {
                $zacksData['sectorRank'] = trim($sectorRankNode->textContent);
            }
        }
        
        return $zacksData;
    }
    
    public function getAllData() {
        $earnings = $this->getEarnings();
        $nextEarnings = $this->getNextEarnings($earnings);
        
        return [
            'symbol' => $this->symbol,
            'recommendations' => $this->getRecommendations(),
            'earnings' => $earnings,
            'nextEarnings' => $nextEarnings,
            'earningsSurprises' => $this->getEarningsSurprises(),
            'companyNews' => $this->getCompanyNews(),
            'stockGrades' => $this->getStockGrades(),
            'historicalGrades' => $this->getHistoricalGrades(),
            'benzingaTables' => $this->getBenzingaTables(),
            'zacksData' => $this->getZacksData()
        ];
    }
}

// Main execution
$symbol = $_GET['symbol'] ?? '';

if (empty($symbol)) {
    die('No symbol provided');
}

$fetcher = new StockDataFetcher($symbol);
$data = $fetcher->getAllData();

// Extract data for use in template
extract($data);

// Function to format CIK to 10 digits
function formatCIK($cik) {
    return str_pad($cik, 10, '0', STR_PAD_LEFT);
}

// Function to get CIK from ticker
function getCIKFromTicker($ticker) {
    $ticker = strtoupper(trim($ticker));
    $jsonFile = __DIR__ . '/company_tickers.json';
    
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
    $xmlFilename = preg_replace('/^xslF345X\d+\//', '', $primaryDoc);
    $accessionNumberClean = str_replace('-', '', $accessionNumber);
    $xmlUrl = "https://www.sec.gov/Archives/edgar/data/{$cik}/{$accessionNumberClean}/{$xmlFilename}";
    
    // Create SEC filing URL
    $secFilingUrl = "https://www.sec.gov/Archives/edgar/data/{$cik}/{$accessionNumberClean}/xslF345X05/" . $xmlFilename;
    
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
        'xmlUrl' => $xmlUrl,
        'secUrl' => $secFilingUrl
    ];
    
    $rel = $xml->reportingOwner->reportingOwnerRelationship;
    if ((string)$rel->isDirector == '1') $data['relationship'][] = 'Director';
    if ((string)$rel->isOfficer == '1') $data['relationship'][] = 'Officer';
    if ((string)$rel->isTenPercentOwner == '1') $data['relationship'][] = '10% Owner';
    if ((string)$rel->isOther == '1') $data['relationship'][] = 'Other';
    if (!empty((string)$rel->officerTitle)) $data['relationship'][] = (string)$rel->officerTitle;
    
    if (isset($xml->nonDerivativeTable->nonDerivativeTransaction)) {
        foreach ($xml->nonDerivativeTable->nonDerivativeTransaction as $trans) {
            $transactionCode = (string)$trans->transactionCoding->transactionCode;
            $transactionType = match($transactionCode) {
                'P' => 'Purchase',
                'S' => 'Sale',
                'A' => 'Award/Grant',
                'M' => 'Exercise',
                'G' => 'Gift',
                'D' => 'Disposition',
                'F' => 'Tax Withholding',
                default => $transactionCode
            };
            
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
    
    return $data;
}

// Fetch insider trading data
$insiderData = [];
$companyInfo = getCIKFromTicker($symbol);
if (!isset($companyInfo['error'])) {
    $submissions = getSubmissions($companyInfo['cik_str']);
    if (!isset($submissions['error'])) {
        $filings = $submissions['filings']['recent'];
        $form4Count = 0;
        for ($i = 0; $i < count($filings['filingDate']); $i++) {
            if ($filings['form'][$i] == '4' && $form4Count < 10) {
                $form4Data = parseForm4XML($submissions['cik'], $filings['accessionNumber'][$i], $filings['primaryDocument'][$i]);
                if ($form4Data) {
                    $form4Data['filingDate'] = $filings['filingDate'][$i];
                    $form4Data['accessionNumber'] = $filings['accessionNumber'][$i];
                    $insiderData[] = $form4Data;
                    $form4Count++;
                }
            }
        }
    }
}

// ...existing code...