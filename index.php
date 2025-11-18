<?php
require_once 'fedrate.php';
$fedRateData = getFedRateData();
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

      .fed-meta {
        padding: 10px 16px;
        background: #0d1117;
        border-bottom: 1px solid #2d3548;
        font-size: 10px;
        color: #9ca3af;
      }

      .fed-meta strong {
        color: #00d4aa;
        margin-right: 8px;
      }

      .fed-info-row {
        display: inline-block;
        margin-right: 20px;
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
      
      <?php if (!empty($fedRateData)): ?>
      <div class="table-container">        
        <div class="fed-meta">
          <div class="fed-info-row">
            <strong>Meeting Date:</strong> <?php echo htmlspecialchars($fedRateData['meeting_date'] ?? 'N/A'); ?>
          </div>
          <div class="fed-info-row">
            <strong>Meeting Time:</strong> <?php echo htmlspecialchars($fedRateData['meeting_info']['meeting_time'] ?? 'N/A'); ?>
          </div>
          <div class="fed-info-row">
            <strong>Future Price:</strong> <?php echo htmlspecialchars($fedRateData['meeting_info']['future_price'] ?? 'N/A'); ?>
          </div>
          <div class="fed-info-row" style="float: right;">
            <?php echo htmlspecialchars($fedRateData['last_updated'] ?? ''); ?>
          </div>
        </div>

        <?php if (!empty($fedRateData['probabilities'])): ?>
        <table>
          <thead>
            <tr>
              <th>Target Rate</th>
              <th class="right">Probability</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($fedRateData['probabilities'] as $prob): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($prob['target_rate']); ?></strong></td>
              <td class="right <?php 
                $probVal = floatval(str_replace('%', '', $prob['probability']));
                echo $probVal > 50 ? : '';
              ?>">
                <?php echo htmlspecialchars($prob['probability']); ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>

        <?php if (!empty($fedRateData['rate_table'])): ?>
        <table style="margin-top: 1px;">
          <thead>
            <tr>
              <th>Target Rate</th>
              <th class="right">Current</th>
              <th class="right">Prev Day</th>
              <th class="right">Prev Week</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($fedRateData['rate_table'] as $rate): 
              $current = floatval(str_replace('%', '', $rate['current_probability']));
              $prevDay = floatval(str_replace('%', '', $rate['previous_day_probability']));
              $prevWeek = floatval(str_replace('%', '', $rate['previous_week_probability']));
              
              $dayChange = $current - $prevDay;
              $weekChange = $current - $prevWeek;
            ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($rate['target_rate']); ?></strong></td>
              <td class="right"><?php echo htmlspecialchars($rate['current_probability']); ?></td>
              <td class="right <?php echo $dayChange > 0 ?  : ($dayChange < 0 ?  : ''); ?>">
                <?php echo htmlspecialchars($rate['previous_day_probability']); ?>
              </td>
              <td class="right <?php echo $weekChange > 0 ?  : ($weekChange < 0 ?  : ''); ?>">
                <?php echo htmlspecialchars($rate['previous_week_probability']); ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
      
      <?php else: ?>
      <div class="table-container">
        <div class="no-data">
          Unable to load Fed rate data at this time.
        </div>
      </div>
      <?php endif; ?>

      <!-- TradingView Widget BEGIN -->
      <div class="tradingview-widget-container">
        <div class="tradingview-widget-container__widget"></div>
        <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-stock-heatmap.js" async>
        {
        "dataSource": "SPX500",
        "blockSize": "market_cap_basic",
        "blockColor": "change",
        "grouping": "sector",
        "locale": "en",
        "symbolUrl": "",
        "colorTheme": "dark",
        "exchanges": [],
        "hasTopBar": false,
        "isDataSetEnabled": false,
        "isZoomEnabled": true,
        "hasSymbolTooltip": false,
        "isMonoSize": false,
        "width": 1500,
        "height": 500
      }
        </script>
      </div>
      <!-- TradingView Widget END -->
    </div>

    <!-- TradingView Widget BEGIN -->
    <div class="container">
      <div class="tradingview-widget-container">
        <div class="tradingview-widget-container__widget"></div>
        <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-forex-cross-rates.js" async>
        {
        "colorTheme": "dark",
        "isTransparent": false,
        "locale": "en",
        "currencies": [
          "EUR",
          "USD",
          "JPY",
          "GBP",
          "CHF",
          "CNY"
        ],
        "backgroundColor": "rgba(26, 31, 46, 1)",
        "width": 550,
        "height": 400
      }
        </script>
      </div>
      <!-- TradingView Widget END -->
    </div>
  </body>
  </html>
