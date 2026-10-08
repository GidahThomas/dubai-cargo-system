-- Outgoing WhatsApp and SMS messages, sent in the background by tools/send_messages.php.
CREATE TABLE IF NOT EXISTS message_queue (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  channel ENUM('whatsapp', 'sms') NOT NULL,
  user_id INT UNSIGNED NULL,
  recipient VARCHAR(32) NOT NULL,
  title VARCHAR(190) NOT NULL,
  body TEXT NOT NULL,
  status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  last_error VARCHAR(500) NULL,
  next_attempt_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_message_queue_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_message_queue_due (status, next_attempt_at),
  INDEX idx_message_queue_created (created_at)
) ENGINE=InnoDB;
