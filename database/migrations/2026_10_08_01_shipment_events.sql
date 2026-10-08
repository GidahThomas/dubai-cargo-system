-- History of every stage a shipment reaches, used for the customer tracking timeline.
CREATE TABLE IF NOT EXISTS shipment_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shipment_id INT UNSIGNED NOT NULL,
  status VARCHAR(40) NOT NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_shipment_events_shipment
    FOREIGN KEY (shipment_id) REFERENCES shipments(id)
    ON DELETE CASCADE,
  INDEX idx_shipment_events_shipment (shipment_id, created_at)
) ENGINE=InnoDB;

-- Existing shipments start their history at their current stage.
INSERT INTO shipment_events (shipment_id, status, created_at)
SELECT s.id, s.status, s.updated_at
FROM shipments s
WHERE NOT EXISTS (SELECT 1 FROM shipment_events e WHERE e.shipment_id = s.id);
