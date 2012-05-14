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
     * We'll store the exchange rates in memory, rather than looking up each exchange
     * rate upon conversion. The currency symbol becomes the array key e.g.,
     *
     *     $exchangeRate['CHF'] = 1.1154
     * 
     * http://en.wikipedia.org/wiki/List_of_circulating_currencies
     */
    
    private $webServiceURI = 'http://toolserver.org/~kaldari/rates.xml';
    private $defaultCurrency = 'USD';
    private $exchangeRates = array();
    
    public function __construct($uri = NULL) {
        if (isset($uri) && !empty($uri)) {
            $this->webServiceURI = $uri;
        }
        
        $this->getExchangeRates();
    }
    
    /**
     * Fetches the current list of exchange rates from the remote server.
     * The rates are saved in an associative array for quick retrieval.
     */
    private function getExchangeRates() {
        /**
         * We could use curl to access the XML feed.
         */
        $exchangeRateXML = simplexml_load_file($this->webServiceURI);
        
        foreach ($exchangeRateXML->conversion as $conversion) {
            $this->exchangeRates[(string)$conversion->currency] = (float)$conversion->rate;
        }
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
                    
        foreach ($transactions as $transaction) {
            list($currency, $amount) = explode(" ", $transaction);
            
            $convertedCurrency[] = $this->computeExchangeRate($currency, $amount);
        }
        
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
       
        return $this->computeExchangeRate($currency, $amount);
    }
    
    /**
     * Formats the currency and amount into the default currency
     * 
     * @param string $currency The currency symbol
     * @param float $amount The amount for this transaction
     * @return string A formated string in the default currency e.g., USD 987.65
     */
    private function computeExchangeRate($currency, $amount) {
        if (array_key_exists((string)$currency, $this->exchangeRates)) {
            return $this->defaultCurrency . ' ' . number_format($this->exchangeRates[$currency] * (float)$amount, 2);
        } else {
            // TODO: Handle unrecognized currency symbol 
        }
    }
}

$cc = new CurrencyConverter();
echo $cc->convertCurrency('AUD 562.5') . PHP_EOL;
print_r($cc->convertCurrency(array('JPY 5000', 'CZK 62.5')));