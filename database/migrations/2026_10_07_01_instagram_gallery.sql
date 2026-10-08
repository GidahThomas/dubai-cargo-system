-- Posts imported from the company's Instagram "Download your information" export.
CREATE TABLE IF NOT EXISTS instagram_posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_key CHAR(40) NOT NULL,
  caption TEXT NULL,
  posted_at DATETIME NULL,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  product_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_instagram_posts_source (source_key),
  INDEX idx_instagram_posts_visible (is_visible, posted_at),
  CONSTRAINT fk_instagram_posts_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS instagram_post_media (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  media_path VARCHAR(255) NOT NULL,
  media_type ENUM('image', 'video') NOT NULL DEFAULT 'image',
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_instagram_post_media_post
    FOREIGN KEY (post_id) REFERENCES instagram_posts(id)
    ON DELETE CASCADE,
  INDEX idx_instagram_post_media_post (post_id, sort_order)
) ENGINE=InnoDB;
