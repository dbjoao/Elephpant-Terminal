<?php
require_once(__DIR__ . '/config.php');

// Set default dates
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d', strtotime('+0 day'));
$sort_by = $_GET['sort_by'] ?? 'importance';
$sort_order = $_GET['sort_order'] ?? 'desc';

$curl = curl_init();

$apiUrl = BENZINGA_BASE_URL . "/calendar/earnings?token=" . BENZINGA_API_TOKEN . 
          "&parameters[date_from]={$date_from}&parameters[date_to]={$date_to}&parameters[tickers]=&pagesize=1000";

curl_setopt_array($curl, [
  CURLOPT_URL => $apiUrl,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => [
    "accept: application/json"
  ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  $data = json_decode($response, true);
  
  // Sort the data
  if (isset($data['earnings']) && count($data['earnings']) > 0) {
    usort($data['earnings'], function($a, $b) use ($sort_by, $sort_order) {
      $val_a = $a[$sort_by] ?? '';
      $val_b = $b[$sort_by] ?? '';
      
      // Convert to float for numeric comparisons
      if (in_array($sort_by, ['eps', 'eps_est', 'eps_surprise', 'eps_surprise_percent', 'revenue', 'revenue_est', 'revenue_surprise', 'revenue_surprise_percent', 'importance'])) {
        $val_a = (float)$val_a;
        $val_b = (float)$val_b;
      }
      
      if ($val_a == $val_b) return 0;
      
      $result = $val_a < $val_b ? -1 : 1;
      return $sort_order === 'desc' ? -$result : $result;
    });
  }
  
  // Function to generate sort URL
  function getSortUrl($column, $current_sort, $current_order, $date_from, $date_to) {
    $new_order = ($current_sort === $column && $current_order === 'asc') ? 'desc' : 'asc';
    return "?date_from={$date_from}&date_to={$date_to}&sort_by={$column}&sort_order={$new_order}";
  }
  
  // Function to get sort arrow
  function getSortArrow($column, $current_sort, $current_order) {
    if ($current_sort !== $column) return ' ↕';
    return $current_order === 'asc' ? ' ↑' : ' ↓';
  }
  ?>
  <!DOCTYPE html>
  <html>
  <head>
    <title>Earnings Analysis</title>
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
      
      .date-form { 
        background: linear-gradient(135deg, #1a1f2e 0%, #141821 100%);
        border: 1px solid #2d3548;
        border-left: 3px solid #ff7a00;
        padding: 14px 16px;
        margin-bottom: 12px;
        border-radius: 3px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      }
      
      .date-form form {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
      }
      
      .date-form label {
        color: #9ca3af;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }
      
      .date-form input[type="date"] { 
        padding: 6px 10px;
        background: #0d1117;
        border: 1px solid #2d3548;
        border-radius: 3px;
        color: #e0e6ed;
        font-size: 11px;
        font-family: 'Courier New', monospace;
      }
      
      .date-form input[type="date"]:focus {
        outline: none;
        border-color: #ff7a00;
      }
      
      .date-form button { 
        padding: 6px 15px;
        background: rgba(255,122,0,0.9);
        color: white;
        border: 1px solid #ff7a00;
        border-radius: 3px;
        cursor: pointer;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.2s;
      }
      
      .date-form button:hover { 
        background: #ff7a00;
        box-shadow: 0 2px 8px rgba(255,122,0,0.3);
      }
      
      .date-form button[type="button"] {
        background: rgba(45,53,72,0.9);
        border-color: #2d3548;
      }
      
      .date-form button[type="button"]:hover {
        background: #2d3548;
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
      </div>
    </div>
    <?php include 'tickertape.php'; ?>
    
    <div class="container">
      <div class="date-form">
        <form method="GET">
          <label>From: <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" required></label>
          <label>To: <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" required></label>
          <button type="submit">Filter</button>
          <button type="button" onclick="window.location.href='earnings.php'">Reset to Default</button>
        </form>
      </div>

      <?php if (isset($data['earnings']) && count($data['earnings']) > 0): ?>
      <div class="info-banner">
        Showing <strong><?php echo count($data['earnings']); ?></strong> earnings reports from <strong><?php echo $date_from; ?></strong> to <strong><?php echo $date_to; ?></strong>
      </div>
      
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th><a href="<?php echo getSortUrl('date', $sort_by, $sort_order, $date_from, $date_to); ?>">Date<?php echo getSortArrow('date', $sort_by, $sort_order); ?></a></th>
              <th><a href="<?php echo getSortUrl('time', $sort_by, $sort_order, $date_from, $date_to); ?>">Time<?php echo getSortArrow('time', $sort_by, $sort_order); ?></a></th>
              <th><a href="<?php echo getSortUrl('ticker', $sort_by, $sort_order, $date_from, $date_to); ?>">Ticker<?php echo getSortArrow('ticker', $sort_by, $sort_order); ?></a></th>
              <th><a href="<?php echo getSortUrl('name', $sort_by, $sort_order, $date_from, $date_to); ?>">Company<?php echo getSortArrow('name', $sort_by, $sort_order); ?></a></th>
              <th><a href="<?php echo getSortUrl('period', $sort_by, $sort_order, $date_from, $date_to); ?>">Period<?php echo getSortArrow('period', $sort_by, $sort_order); ?></a></th>
              <th><a href="<?php echo getSortUrl('eps', $sort_by, $sort_order, $date_from, $date_to); ?>">EPS<?php echo getSortArrow('eps', $sort_by, $sort_order); ?></a></th>
              <th><a href="<?php echo getSortUrl('eps_est', $sort_by, $sort_order, $date_from, $date_to); ?>">EPS Est<?php echo getSortArrow('eps_est', $sort_by, $sort_order); ?></a></th>
              <th><a href="<?php echo getSortUrl('eps_surprise', $sort_by, $sort_order, $date_from, $date_to); ?>">EPS Surprise<?php echo getSortArrow('eps_surprise', $sort_by, $sort_order); ?></a></th>
              <th><a href="<?php echo getSortUrl('eps_surprise_percent', $sort_by, $sort_order, $date_from, $date_to); ?>">EPS Surprise %<?php echo getSortArrow('eps_surprise_percent', $sort_by, $sort_order); ?></a></th>
              <th><a href="<?php echo getSortUrl('revenue', $sort_by, $sort_order, $date_from, $date_to); ?>">Revenue<?php echo getSortArrow('revenue', $sort_by, $sort_order); ?></a></th>
              <th><a href="<?php echo getSortUrl('revenue_est', $sort_by, $sort_order, $date_from, $date_to); ?>">Revenue Est<?php echo getSortArrow('revenue_est', $sort_by, $sort_order); ?></a></th>
              <th><a href="<?php echo getSortUrl('revenue_surprise', $sort_by, $sort_order, $date_from, $date_to); ?>">Revenue Surprise<?php echo getSortArrow('revenue_surprise', $sort_by, $sort_order); ?></a></th>
              <th><a href="<?php echo getSortUrl('revenue_surprise_percent', $sort_by, $sort_order, $date_from, $date_to); ?>">Revenue Surprise %<?php echo getSortArrow('revenue_surprise_percent', $sort_by, $sort_order); ?></a></th>
              <th><a href="<?php echo getSortUrl('importance', $sort_by, $sort_order, $date_from, $date_to); ?>">Importance<?php echo getSortArrow('importance', $sort_by, $sort_order); ?></a></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data['earnings'] as $earning): ?>
            <tr>
              <td><?php echo htmlspecialchars($earning['date']); ?></td>
              <td><?php echo htmlspecialchars($earning['time']); ?></td>
              <td><strong><a href="stock.php?symbol=<?php echo urlencode($earning['ticker']); ?>" target="_blank"><?php echo htmlspecialchars($earning['ticker']); ?></a></strong></td>
              <td><?php echo htmlspecialchars($earning['name']); ?> <a href="https://www.google.com/finance/quote/<?php echo urlencode($earning['ticker']); ?>:NYSE?window=5D" target="_blank">Google</a> / <a href="https://finance.yahoo.com/quote/<?php echo urlencode($earning['ticker']); ?>/" target="_blank">Yahoo</a></td>
              <td><?php echo htmlspecialchars($earning['period'] . ' ' . $earning['period_year']); ?></td>
              <td><?php echo number_format((float)$earning['eps'], 2); ?></td>
              <td><?php echo number_format((float)$earning['eps_est'], 2); ?></td>
              <td class="<?php echo (float)$earning['eps_surprise'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo number_format((float)$earning['eps_surprise'], 2); ?>
              </td>
              <td class="<?php echo (float)$earning['eps_surprise_percent'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo number_format((float)$earning['eps_surprise_percent'] * 100, 2); ?>%
              </td>
              <td><?php echo '$' . number_format((float)$earning['revenue']); ?></td>
              <td><?php echo '$' . number_format((float)$earning['revenue_est']); ?></td>
              <td class="<?php echo (float)$earning['revenue_surprise'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo '$' . number_format((float)$earning['revenue_surprise']); ?>
              </td>
              <td class="<?php echo (float)$earning['revenue_surprise_percent'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo number_format((float)$earning['revenue_surprise_percent'] * 100, 2); ?>%
              </td>
              <td><?php echo htmlspecialchars($earning['importance']); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="no-data">
        No earnings data found for the selected date range.
      </div>
      <?php endif; ?>
    </div>
        <div class="container">
    </div>
  </body>
  </html>
  <?php
}
?>