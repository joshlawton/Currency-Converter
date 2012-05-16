<?php
date_default_timezone_set('America/New_York');

/**
 * Converts a string or array of foreign currencies and amounts into a single, default currency.
 * 
 * @author Josh Lawton
 */
class CurrencyConverter {
    /**
     * According to Wikipedia, there are 182 currencies in circulation throughout the world.
     * We could store the exchange rates in memory, rather than looking up each exchange
     * rate upon conversion from disk. The currency symbol becomes the array key e.g.,
     *
     *     $exchangeRate['CHF'] = 1.1154
     * 
     * http://en.wikipedia.org/wiki/List_of_circulating_currencies
     * 
     * One other consideration is how often to pull data from the remote web service.
     * One approach is to store the timestamp of the last refresh as a private property.
     * This functionality is omitted in this sample code.
     */
    private $webServiceURI = 'http://toolserver.org/~kaldari/rates.xml';
    private $defaultCurrency = 'USD';
    private $exchangeRates = array();
    private $mysqli = NULL;
    
    public function __construct($uri = NULL) {
        if (isset($uri) && !empty($uri)) {
            $this->webServiceURI = $uri;
        }

        // Ideally we'd use PDO or a database abstraction layer
        if ($this->mysqli === NULL) {
            $this->mysqli = new mysqli('localhost', 'username', 'password', 'CurrencyConverter');
        }
        
        $this->getExchangeRates();
    }
    
    /**
     * Fetches the current list of exchange rates from the remote server.
     * The rates are saved in MySQL.
     */
    private function getExchangeRates() {
        $values = array();

        // NOTE: We could use curl for more robust handling of remote connections
        
        // TODO: Improve error handling when calling the RESTful web service
        $exchangeRateXML = simplexml_load_file($this->webServiceURI);

        /**
         * Using multi-insert in one query execution instead of executing
         * mulltiple prepared statements for lower server overhead.
         */
        foreach ($exchangeRateXML->conversion as $conversion) {
            $values[] = '("' . (string)$conversion->currency . '", ' . (float)$conversion->rate . ')';
        }

        $this->mysqli->query('INSERT INTO exchange_rates (currency, rate) VALUES ' . implode(',', $values));
    }
    
    /**
     * In other languages we would overload a function based on its parameters.
     * Alas we'll create a single API method which acccepts either an array or single
     * currency to convert and use private methods to process the conversion depending
     * on the parameter type.
     * 
     * @param string|array $transaction A string or array of currencies for which to compute the exchange rate.
     * @return string|array A string or array of computed exchange rates.
     */
    public function convertCurrency($transaction) {
        if (is_array($transaction)) {
            return $this->convertCurrencyByArray($transaction);
        } else {
            return $this->convertCurrencyByAmount($transaction);
        }
    }
    
    /**
     * Creates an array in which each element corresponds to the converted amount.
     * @param $transactions An array of transactions (0 => "CHF 12.34", 1 => "AUS 98.76")
     * @retrun $convertedCurrency An array of converted currency
     */
    private function convertCurrencyByArray($transactions) {
        $convertedCurrency = array();

        // We'll use prepared statements because we have [2, 212] potential requests

        $stmt = $this->mysqli->prepare("SELECT currency, rate FROM exchange_rates WHERE currency = ?");
        
        foreach ($transactions as $transaction) {
            list($currency, $amount) = explode(" ", $transaction);
            $stmt->bind_param("s", $currency);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            
            $convertedCurrency[] = $this->defaultCurrency . ' ' . number_format((float)$row['rate'] * (float)$amount, 2);
        }

        $stmt->close();
        
        return $convertedCurrency;
    }
    
    /**
     * Converts the input into its corresponding exchange rate
     * 
     * @param string $transaction A string containing the currency symbol and original amount e.g., CHF 123.45
     * @return string A string containing the computed exchange rate in the default currency e.g., USD 146.80
     */
    private function convertCurrencyByAmount($transaction) {
        list($currency, $amount) = explode(" ", $transaction);

        $stmt = $this->mysqli->prepare("SELECT currency, rate FROM exchange_rates WHERE currency = ?");
        $stmt->bind_param("s", $currency);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        
        $stmt->close();

        return $this->defaultCurrency . ' ' . number_format((float)$row['rate'] * (float)$amount, 2);
    }

    public function __destruct() {
        $this->mysqli->close();
    }
}

$cc = new CurrencyConverter();
echo $cc->convertCurrency('AUD 562.5') . "\n";
print_r($cc->convertCurrency(array('JPY 5000', 'CZK 62.5')));