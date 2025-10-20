CREATE TABLE IF NOT EXISTS popularBooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    olid VARCHAR(50) DEFAULT NULL,
    -- optional OpenLibrary ID
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) DEFAULT NULL,
    alternative_title VARCHAR(255) DEFAULT NULL,
    alternative_subtitle VARCHAR(255) DEFAULT NULL,
    author VARCHAR(255) DEFAULT 'Unknown Author',
    isbn VARCHAR(50) DEFAULT NULL,
    publisher VARCHAR(255) DEFAULT NULL,
    publish_year INT DEFAULT NULL,
    ratings_count INT DEFAULT NULL,
    subject_key JSON DEFAULT NULL,
    person_key JSON DEFAULT NULL,
    place_key JSON DEFAULT NULL,
    time_key JSON DEFAULT NULL,
    cover_url TEXT DEFAULT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ratings (ratings_count),
    INDEX idx_title (title)
)