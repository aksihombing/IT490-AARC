CREATE TABLE club_invites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bundle_name VARCHAR(100) NOT NULL,
  version INT NOT NULL,
  status ENUM('new', 'passed', 'failed') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (bundle_name, version)
  
);