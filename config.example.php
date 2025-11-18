<?php
/**
 * API Configuration File - EXAMPLE
 * Copy this file to config.php and add your actual API keys
 * 
 * NEVER commit config.php to version control!
 */

// Finnhub API Configuration
// Get your key at: https://finnhub.io/
define('FINNHUB_API_KEY', 'your_finnhub_api_key_here');

// Financial Modeling Prep API Configuration
// Get your key at: https://financialmodelingprep.com/
define('FMP_API_KEY', 'your_fmp_api_key_here');

// Benzinga API Configuration
// Get your token at: https://www.benzinga.com/apis/
define('BENZINGA_API_TOKEN', 'your_benzinga_token_here');

// Trading212 API Configuration (Optional - can be set via UI)
define('TRADING212_API_KEY', '');
define('TRADING212_API_SECRET', '');

// API Base URLs (Usually no need to change)
define('FINNHUB_BASE_URL', 'https://finnhub.io/api/v1');
define('FMP_BASE_URL', 'https://financialmodelingprep.com/stable');
define('BENZINGA_BASE_URL', 'https://api.benzinga.com/api/v2.1');
define('TRADING212_BASE_URL', 'https://live.trading212.com');

// Rate Limiting Configuration
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_DELAY', 1);

// Application Settings
define('DEFAULT_TIMEZONE', 'America/New_York');
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');

date_default_timezone_set(DEFAULT_TIMEZONE);
?>