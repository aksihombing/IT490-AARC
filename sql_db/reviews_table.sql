CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,                 -- unique ID for each review
  user_id INT NOT NULL,                              --  who wrote it
  works_id VARCHAR(50) NOT NULL,                     -- Open Library work ID 
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),  -- 1–5 star rating
  body TEXT,                                         -- optional written review, if we decide to keep written reviews
  --created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,    -- time the review was created
  );
