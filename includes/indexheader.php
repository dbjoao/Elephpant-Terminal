    <div class="terminal-header">
      <a href="index.php" style="text-decoration: none;">
        <div class="terminal-logo">
          <img src="logo.png" alt="Minerva Logo">
          <span>Minerva Terminal</span>
        </div>
      </a>
        <div class="terminal-nav">
          <a href="map.php">Index Map</a>
          <a href="earnings.php">Earnings</a>

          <form method="GET" action="stock.php" style="display: inline-flex; gap: 4px; margin: 0;">
            <input type="text" name="symbol" placeholder="Search US ticker..." required 
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