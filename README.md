# Summary
Create a PHP class and corresponding MySQL table to retrieve, store, and compute exchange rates.

# Overview
Let's say that for our annual fundraiser we want to use currency conversion rates that are periodically updated automatically rather than having to constantly update them by hand.

In order to do this, we sign up for a 3rd party service that provides us with daily conversion rates for the currencies that we support. The service is a simple API that outputs XML when called with the URL http://toolserver.org/~kaldari/rates.xml.

First, define a MySQL table that can store this data. You don't actually have to set up the table anywhere, just create a SQL file that contains the CREATE statement for the table.

Next, construct a PHP class that can handle all of the following tasks:
* Retrieving the data from the API
* Parsing the data
* Storing the data in your MySQL table
* Given an amount of a foreign currency, convert it into the equivalent in US dollars. For example: ```input: 'JPY 5000'``` ```output: 'USD 65.58'```
* Given an array of amounts in foreign currencies, return an array of US equivalent amounts in the same order. For example: ```input: array( 'JPY 5000', 'CZK 62.5' )``` ```output: array( 'USD 65.58', 'USD 3.27' )``` (This can be a separate function from #4.)

# Deliverables
1. A PHP file that defines the 'CurrencyConverter' class
2. A SQL file that creates the 'exchange_rates' MySQL table