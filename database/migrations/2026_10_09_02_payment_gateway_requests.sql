-- Mobile-money push requests sent through AzamPay, and the gateway's answer for each.
CREATE TABLE IF NOT EXISTS payment_gateway_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payment_id INT UNSIGNED NOT NULL,
  gateway VARCHAR(30) NOT NULL DEFAULT 'azampay',
  external_id VARCHAR(64) NOT NULL,
  provider VARCHAR(30) NOT NULL,
  msisdn VARCHAR(20) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('requested', 'success', 'failed') NOT NULL DEFAULT 'requested',
  gateway_reference VARCHAR(120) NULL,
  message VARCHAR(255) NULL,
  callback_payload TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_payment_gateway_external (external_id),
  INDEX idx_payment_gateway_payment (payment_id),
  CONSTRAINT fk_payment_gateway_payment
    FOREIGN KEY (payment_id) REFERENCES payments(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;
