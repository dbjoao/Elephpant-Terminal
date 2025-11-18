<?php
require_once(__DIR__ . '/config.php');
session_start();

class Trading212Portfolio {
    private $apiKey;
    private $apiSecret;
    private $baseUrl;
    private $lastRequestTime = [];
    
    public function __construct($apiKey, $apiSecret) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->baseUrl = TRADING212_BASE_URL;
    }
    
    /**
     * Fetch account metadata
     * Rate Limit: 1 request per 5 seconds
     */
    public function getAccountMetadata() {
        $this->enforceRateLimit('metadata', 5);
        $url = $this->baseUrl . '/api/v0/equity/account/info';
        return $this->makeRequest($url, 'GET');
    }
    
    /**
     * Fetch account cash information
     * Rate Limit: 1 request per 5 seconds
     */
    public function getAccountCash() {
        $this->enforceRateLimit('cash', 5);
        $url = $this->baseUrl . '/api/v0/equity/account/cash';
        return $this->makeRequest($url, 'GET');
    }
    
    /**
     * Fetch all open positions
     * Rate Limit: 1 request per 5 seconds
     */
    public function getAllPositions() {
        $this->enforceRateLimit('portfolio', 5);
        $url = $this->baseUrl . '/api/v0/equity/portfolio';
        $positions = $this->makeRequest($url, 'GET');
        
        // Calculate pplPercent if missing
        if (is_array($positions)) {
            foreach ($positions as &$position) {
                if (!isset($position['pplPercent'])) {
                    $invested = $position['quantity'] * $position['averagePrice'];
                    $position['pplPercent'] = $invested > 0 ? (($position['ppl'] / $invested) * 100) : 0;
                }
                $position['currentValue'] = $position['quantity'] * $position['currentPrice'];
            }
        }
        
        return $positions;
    }
    
    /**
     * Fetch a specific position by ticker (GET method)
     * Rate Limit: 1 request per 1 second
     */
    public function getPositionByTicker($ticker) {
        $this->enforceRateLimit('position', 1);
        $url = $this->baseUrl . '/api/v0/equity/portfolio/' . urlencode($ticker);
        return $this->makeRequest($url, 'GET');
    }
    
    /**
     * Enforce rate limiting
     */
    private function enforceRateLimit($endpoint, $seconds) {
        if (isset($this->lastRequestTime[$endpoint])) {
            $elapsed = microtime(true) - $this->lastRequestTime[$endpoint];
            $waitTime = $seconds - $elapsed;
            
            if ($waitTime > 0) {
                usleep($waitTime * 1000000);
            }
        }
        
        $this->lastRequestTime[$endpoint] = microtime(true);
    }
    
    /**
     * Make HTTP request to Trading212 API
     */
    private function makeRequest($url, $method = 'GET', $data = null) {
        $ch = curl_init();
        
        $credentials = $this->apiKey . ':' . $this->apiSecret;
        $encodedCredentials = base64_encode($credentials);
        
        $headers = [
            'Authorization: Basic ' . $encodedCredentials,
            'Content-Type: application/json'
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($method === 'POST' && $data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }
        
        return false;
    }
}

// Handle API credentials
$apiKey = '';
$apiSecret = '';
$showLoginForm = false;

// Check if logout requested
if (isset($_GET['logout'])) {
    setcookie('trading212_api_key', '', time() - 3600, '/');
    setcookie('trading212_api_secret', '', time() - 3600, '/');
    header('Location: portfolio.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_key']) && isset($_POST['api_secret'])) {
    $apiKey = trim($_POST['api_key']);
    $apiSecret = trim($_POST['api_secret']);
    
    // Store in cookies (30 days)
    setcookie('trading212_api_key', $apiKey, time() + (30 * 24 * 60 * 60), '/');
    setcookie('trading212_api_secret', $apiSecret, time() + (30 * 24 * 60 * 60), '/');
    
    header('Location: portfolio.php');
    exit;
}

// Check for existing credentials in cookies
if (isset($_COOKIE['trading212_api_key']) && isset($_COOKIE['trading212_api_secret'])) {
    $apiKey = $_COOKIE['trading212_api_key'];
    $apiSecret = $_COOKIE['trading212_api_secret'];
} else {
    $showLoginForm = true;
}

// Initialize Trading212 API only if credentials exist
$accountInfo = [];
$cashData = [];
$positions = [];

if (!$showLoginForm && !empty($apiKey) && !empty($apiSecret)) {
    $portfolio = new Trading212Portfolio($apiKey, $apiSecret);

    // Fetch data with error handling
    $accountInfo = $portfolio->getAccountMetadata();
    if ($accountInfo === false) {
        $accountInfo = [];
    }

    sleep(1);

    $cashData = $portfolio->getAccountCash();
    if ($cashData === false) {
        $cashData = [];
    }

    sleep(1);

    $positions = $portfolio->getAllPositions();
    if ($positions === false) {
        $positions = [];
    }
}

// Sort functionality
$sort_by = $_GET['sort_by'] ?? 'ppl';
$sort_order = $_GET['sort_order'] ?? 'desc';

if (!empty($positions)) {
    usort($positions, function($a, $b) use ($sort_by, $sort_order) {
        $val_a = $a[$sort_by] ?? 0;
        $val_b = $b[$sort_by] ?? 0;
        
        if (in_array($sort_by, ['quantity', 'averagePrice', 'currentPrice', 'ppl', 'pplPercent', 'currentValue'])) {
            $val_a = (float)$val_a;
            $val_b = (float)$val_b;
        }
        
        if ($val_a == $val_b) return 0;
        
        $result = $val_a < $val_b ? -1 : 1;
        return $sort_order === 'desc' ? -$result : $result;
    });
}

// Calculate totals
$totalInvested = 0;
$totalCurrentValue = 0;
$totalPpl = 0;
if (!empty($positions)) {
    foreach ($positions as $position) {
        $totalInvested += $position['quantity'] * $position['averagePrice'];
        $totalCurrentValue += $position['currentValue'];
        $totalPpl += $position['ppl'];
    }
}
$totalPplPercent = $totalInvested > 0 ? (($totalPpl / $totalInvested) * 100) : 0;

// Helper functions
function getSortUrl($column, $current_sort, $current_order) {
    $new_order = ($current_sort === $column && $current_order === 'asc') ? 'desc' : 'asc';
    return "?sort_by={$column}&sort_order={$new_order}";
}

function getSortArrow($column, $current_sort, $current_order) {
    if ($current_sort !== $column) return ' ↕';
    return $current_order === 'asc' ? ' ↑' : ' ↓';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Trading212 Portfolio</title>
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
      
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }
      
      body { 
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: #0a0e17;
        color: #e0e6ed;
        line-height: 1.4;
        padding: 0;
        margin: 0;
      }
      
      .terminal-header {
        background: linear-gradient(180deg, #1a1f2e 0%, #141821 100%);
        border-bottom: 2px solid #ff7a00;
        padding: 8px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
      }
      
      .terminal-logo {
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .terminal-logo img {
        height: 32px;
        width: auto;
      }

      .terminal-logo span {
        color: #ff7a00;
        font-weight: 700;
        font-size: 14px;
        letter-spacing: 0.5px;
      }
      .container {
        max-width: 1920px;
        margin: 0 auto;
        padding: 12px;
      }
      
      .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 12px;
      }

      .stat-card {
        background: linear-gradient(135deg, #1a1f2e 0%, #141821 100%);
        border: 1px solid #2d3548;
        border-radius: 3px;
        padding: 14px 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      }

      .stat-card.highlight {
        border-left: 3px solid #ff7a00;
      }

      .stat-card.success {
        border-left: 3px solid #00ff88;
      }

      .stat-card.danger {
        border-left: 3px solid #ef4444;
      }

      .stat-label {
        color: #9ca3af;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
      }

      .stat-value {
        font-size: 20px;
        font-weight: 700;
        color: #e0e6ed;
      }

      .stat-value.positive {
        color: #00ff88;
      }

      .stat-value.negative {
        color: #ef4444;
      }

      .stat-sub {
        font-size: 10px;
        color: #6b7280;
        margin-top: 4px;
      }
      
      .info-banner {
        background: linear-gradient(135deg, #1a2332 0%, #0f1620 100%);
        border: 1px solid #2d3548;
        border-left: 3px solid #00d4aa;
        padding: 10px 16px;
        margin-bottom: 12px;
        border-radius: 3px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        color: #9ca3af;
        font-size: 11px;
      }

      .error-banner {
        background: linear-gradient(135deg, #2a1a1a 0%, #1a0f0f 100%);
        border: 1px solid #4a2d2d;
        border-left: 3px solid #ef4444;
        padding: 10px 16px;
        margin-bottom: 12px;
        border-radius: 3px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        color: #ef4444;
        font-size: 11px;
      }
      
      .info-banner strong {
        color: #00d4aa;
        font-weight: 600;
      }
      
      .table-container {
        background: linear-gradient(135deg, #1a1f2e 0%, #141821 100%);
        border: 1px solid #2d3548;
        border-radius: 3px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        margin-bottom: 12px;
      }
      
      table { 
        border-collapse: collapse; 
        width: 100%;
        font-size: 10px;
      }
      
      th, td { 
        padding: 8px 6px;
        text-align: left;
        border-bottom: 1px solid #2d3548;
      }

      th.right, td.right {
        text-align: right;
      }
      
      th { 
        background: #0d1117;
        color: #9ca3af;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 9px;
        letter-spacing: 0.3px;
        position: sticky;
        top: 0;
        z-index: 10;
        cursor: pointer;
        user-select: none;
      }
      
      th:hover { 
        background: rgba(255,122,0,0.1);
        color: #ff7a00;
      }
      
      th a { 
        color: inherit;
        text-decoration: none;
        display: block;
      }
      
      tbody tr {
        transition: background 0.2s;
      }
      
      tbody tr:hover { 
        background: rgba(255,122,0,0.05);
      }
      
      tbody tr:nth-child(even) {
        background: rgba(255,255,255,0.02);
      }
      
      tbody tr:nth-child(even):hover {
        background: rgba(255,122,0,0.05);
      }

      tfoot {
        background: #0d1117;
        font-weight: 700;
      }

      tfoot td {
        border-top: 2px solid #ff7a00;
        padding: 10px 6px;
      }
      
      .positive { 
        color: #00ff88;
        font-weight: 600;
      }
      
      .negative { 
        color: #ef4444;
        font-weight: 600;
      }
      
      td a {
        color: #60a5fa;
        text-decoration: none;
        transition: color 0.2s;
      }
      
      td a:hover {
        color: #ff7a00;
      }

      .ticker-link {
        font-weight: 700;
        color: #ff7a00 !important;
      }
      
      .no-data {
        text-align: center;
        padding: 30px 16px;
        color: #6b7280;
        font-style: italic;
        font-size: 11px;
      }
        .terminal-nav {
            display: flex;
            gap: 6px;
        }
        
        .terminal-nav a {
            color: #b0b8c4;
            text-decoration: none;
            padding: 5px 10px;
            background: rgba(255,122,0,0.1);
            border: 1px solid rgba(255,122,0,0.3);
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.2s;
        }
        
        .terminal-nav a:hover {
            background: rgba(255,122,0,0.2);
            border-color: #ff7a00;
            color: #ff7a00;
        }
      
      .login-container {
        max-width: 500px;
        margin: 80px auto;
        padding: 30px;
        background: linear-gradient(135deg, #1a1f2e 0%, #141821 100%);
        border: 1px solid #2d3548;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
      }
      
      .login-header {
        text-align: center;
        margin-bottom: 30px;
      }
      
      .login-header h2 {
        color: #ff7a00;
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 8px;
      }
      
      .login-header p {
        color: #9ca3af;
        font-size: 12px;
      }
      
      .form-group {
        margin-bottom: 20px;
      }
      
      .form-group label {
        display: block;
        color: #9ca3af;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
      }
      
      .form-group input {
        width: 100%;
        padding: 12px;
        background: #0d1117;
        border: 1px solid #2d3548;
        border-radius: 4px;
        color: #e0e6ed;
        font-size: 13px;
        font-family: 'Inter', monospace;
        transition: border-color 0.2s;
      }
      
      .form-group input:focus {
        outline: none;
        border-color: #ff7a00;
      }
      
      .form-group input::placeholder {
        color: #6b7280;
      }
      
      .submit-btn {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #ff7a00 0%, #ff9500 100%);
        border: none;
        border-radius: 4px;
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        cursor: pointer;
        transition: all 0.2s;
      }
      
      .submit-btn:hover {
        background: linear-gradient(135deg, #ff8800 0%, #ffa500 100%);
        box-shadow: 0 4px 12px rgba(255,122,0,0.4);
      }
      
      .help-text {
        margin-top: 20px;
        padding: 12px;
        background: rgba(255,122,0,0.1);
        border: 1px solid rgba(255,122,0,0.3);
        border-radius: 4px;
        font-size: 11px;
        color: #9ca3af;
        line-height: 1.6;
      }
      
      .help-text a {
        color: #ff7a00;
        text-decoration: none;
      }
      
      .help-text a:hover {
        text-decoration: underline;
      }
      
      .logout-btn {
        padding: 5px 10px;
        background: rgba(239,68,68,0.1);
        border: 1px solid rgba(239,68,68,0.3);
        border-radius: 3px;
        color: #ef4444;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-decoration: none;
        transition: all 0.2s;
      }
      
      .logout-btn:hover {
        background: rgba(239,68,68,0.2);
        border-color: #ef4444;
      }
    </style>
</head>
<body>
    <div class="terminal-header">
      <a href="index.php" style="text-decoration: none;">
        <div class="terminal-logo">
          <img src="logo.png" alt="Minerva Logo">
          <span>MarketRat</span>
        </div>
      </a>
      <div class="terminal-nav">
        <a href="earnings.php">Earnings</a>
        <a href="portfolio.php" class="active">Portfolio</a>
        <form method="GET" action="stock.php" style="display: inline-flex; gap: 4px; margin: 0;">
            <input type="text" name="symbol" placeholder="Search ticker..." required 
                   style="padding: 5px 10px; background: #0d1117; border: 1px solid rgba(255,122,0,0.3); 
                          border-radius: 3px; color: #e0e6ed; font-size: 11px; font-family: 'Inter', sans-serif; 
                          outline: none; text-transform: uppercase;" 
                   onfocus="this.style.borderColor='#ff7a00'" 
                   onblur="this.style.borderColor='rgba(255,122,0,0.3)'">
            <button type="submit" style="padding: 5px 10px; background: rgba(255,122,0,0.1); 
                                         border: 1px solid rgba(255,122,0,0.3); border-radius: 3px; 
                                         color: #b0b8c4; font-size: 11px; font-weight: 500; 
                                         text-transform: uppercase; letter-spacing: 0.5px; cursor: pointer; 
                                         transition: all 0.2s;" 
                    onmouseover="this.style.background='rgba(255,122,0,0.2)'; this.style.borderColor='#ff7a00'; this.style.color='#ff7a00'" 
                    onmouseout="this.style.background='rgba(255,122,0,0.1)'; this.style.borderColor='rgba(255,122,0,0.3)'; this.style.color='#b0b8c4'">
              Search
            </button>
          </form>
          <?php if (!$showLoginForm): ?>
          <a href="portfolio.php?logout=1" class="logout-btn">Logout</a>
          <?php endif; ?>
      </div>
    </div>

    <?php if ($showLoginForm): ?>
    <div class="login-container">
      <div class="login-header">
        <h2>Trading212 API Login</h2>
        <p>Enter your API credentials to view your portfolio</p>
      </div>
      
      <form method="POST" action="portfolio.php">
        <div class="form-group">
          <label for="api_key">API Key</label>
          <input type="text" id="api_key" name="api_key" placeholder="Enter your Trading212 API key" required>
        </div>
        
        <div class="form-group">
          <label for="api_secret">API Secret</label>
          <input type="password" id="api_secret" name="api_secret" placeholder="Enter your Trading212 API secret" required>
        </div>
        
        <button type="submit" class="submit-btn">Connect Portfolio</button>
      </form>
      
      <div class="help-text">
        <strong>⚠️ Security Notice:</strong> Your credentials are stored locally in browser cookies for 30 days. 
        Never share your API keys with anyone. You can obtain your API credentials from your 
        <a href="https://www.trading212.com/en/settings" target="_blank">Trading212 account settings</a>.
      </div>
    </div>
    <?php else: ?>
    <div class="container">
      <?php if (empty($accountInfo) && empty($cashData) && empty($positions)): ?>
      <div class="error-banner">
        ⚠️ Unable to fetch portfolio data. Please wait 30 seconds and <a href="portfolio.php" style="color: #ff7a00; text-decoration: underline;">refresh</a>. API rate limit may have been exceeded.
      </div>
      <?php endif; ?>

      <div class="stats-grid">
        <div class="stat-card highlight">
          <div class="stat-label">Account ID</div>
          <div class="stat-value"><?php echo htmlspecialchars($accountInfo['id'] ?? 'N/A'); ?></div>
          <div class="stat-sub">Currency: <?php echo htmlspecialchars($accountInfo['currencyCode'] ?? 'N/A'); ?></div>
        </div>

        <div class="stat-card">
          <div class="stat-label">Free Cash</div>
          <div class="stat-value">€<?php echo number_format($cashData['free'] ?? 0, 2); ?></div>
          <div class="stat-sub">Available for trading</div>
        </div>

        <div class="stat-card highlight">
          <div class="stat-label">Total Cash</div>
          <div class="stat-value">€<?php echo number_format($cashData['total'] ?? 0, 2); ?></div>
          <div class="stat-sub">Cash + Invested</div>
        </div>

        <div class="stat-card">
          <div class="stat-label">Invested</div>
          <div class="stat-value">€<?php echo number_format($cashData['invested'] ?? 0, 2); ?></div>
          <div class="stat-sub">Total position value</div>
        </div>

        <div class="stat-card <?php echo ($cashData['ppl'] ?? 0) >= 0 ? 'success' : 'danger'; ?>">
          <div class="stat-label">Profit/Loss</div>
          <div class="stat-value <?php echo ($cashData['ppl'] ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
            €<?php echo number_format($cashData['ppl'] ?? 0, 2); ?>
          </div>
          <div class="stat-sub">Unrealized P&L</div>
        </div>

        <div class="stat-card">
          <div class="stat-label">Result</div>
          <div class="stat-value <?php echo ($cashData['result'] ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
            €<?php echo number_format($cashData['result'] ?? 0, 2); ?>
          </div>
          <div class="stat-sub">Realized gains</div>
        </div>
      </div>

      <?php if (!empty($positions)): ?>
      <div class="info-banner">
        Showing <strong><?php echo count($positions); ?></strong> open positions | 
        Total Value: <strong>€<?php echo number_format($totalCurrentValue, 2); ?></strong> | 
        Total P&L: <strong class="<?php echo $totalPpl >= 0 ? 'positive' : 'negative'; ?>">€<?php echo number_format($totalPpl, 2); ?> (<?php echo number_format($totalPplPercent, 2); ?>%)</strong>
      </div>
      
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th><a href="<?php echo getSortUrl('ticker', $sort_by, $sort_order); ?>">Ticker<?php echo getSortArrow('ticker', $sort_by, $sort_order); ?></a></th>
              <th class="right"><a href="<?php echo getSortUrl('quantity', $sort_by, $sort_order); ?>">Quantity<?php echo getSortArrow('quantity', $sort_by, $sort_order); ?></a></th>
              <th class="right"><a href="<?php echo getSortUrl('averagePrice', $sort_by, $sort_order); ?>">Avg Price<?php echo getSortArrow('averagePrice', $sort_by, $sort_order); ?></a></th>
              <th class="right"><a href="<?php echo getSortUrl('currentPrice', $sort_by, $sort_order); ?>">Current Price<?php echo getSortArrow('currentPrice', $sort_by, $sort_order); ?></a></th>
              <th class="right">Invested</th>
              <th class="right">Current Value</th>
              <th class="right"><a href="<?php echo getSortUrl('ppl', $sort_by, $sort_order); ?>">P&L<?php echo getSortArrow('ppl', $sort_by, $sort_order); ?></a></th>
              <th class="right">P&L %</th>
              <th>Links</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($positions as $position): 
              $ticker = str_replace(['_US_EQ', '_EQ', 'm_EQ'], '', $position['ticker']);
              $invested = $position['quantity'] * $position['averagePrice'];
            ?>
            <tr>
              <td><strong class="ticker-link"><?php echo htmlspecialchars($ticker); ?></strong></td>
              <td class="right"><?php echo number_format($position['quantity'], 4); ?></td>
              <td class="right">€<?php echo number_format($position['averagePrice'], 2); ?></td>
              <td class="right">€<?php echo number_format($position['currentPrice'], 2); ?></td>
              <td class="right">€<?php echo number_format($invested, 2); ?></td>
              <td class="right">€<?php echo number_format($position['currentValue'], 2); ?></td>
              <td class="right <?php echo $position['ppl'] >= 0 ? 'positive' : 'negative'; ?>">
                €<?php echo number_format($position['ppl'], 2); ?>
              </td>
              <td class="right <?php echo $position['pplPercent'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo number_format($position['pplPercent'], 2); ?>%
              </td>
              <td>
                <a href="https://www.google.com/finance/quote/<?php echo urlencode($ticker); ?>:NYSE" target="_blank">Google</a> / 
                <a href="https://finance.yahoo.com/quote/<?php echo urlencode($ticker); ?>/" target="_blank">Yahoo</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4">TOTAL</td>
              <td class="right">€<?php echo number_format($totalInvested, 2); ?></td>
              <td class="right">€<?php echo number_format($totalCurrentValue, 2); ?></td>
              <td class="right <?php echo $totalPpl >= 0 ? 'positive' : 'negative'; ?>">
                €<?php echo number_format($totalPpl, 2); ?>
              </td>
              <td class="right <?php echo $totalPplPercent >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo number_format($totalPplPercent, 2); ?>%
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <?php else: ?>
      <div class="no-data">
        No open positions found.
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
</body>
</html>