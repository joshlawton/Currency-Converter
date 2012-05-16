CREATE TABLE IF NOT EXISTS exchange_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    currency varchar(3),
    rate DECIMAL(7,4)
);