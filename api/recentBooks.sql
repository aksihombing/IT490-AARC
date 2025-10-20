CREATE TABLE IF NOT EXISTS recentBooks (
    /* slightly different from the library_cache setup. 
     In library_cache, we stored the general json data for the books in ONE column called json_response
     --> might change it to this format IF AND ONLY IF we are certain about what info we want to keep for the website.
     
     This method will specifically store each part of the json response as its own column instead of having a big column
     
     */
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
    INDEX index_by_year (publish_year),
    INDEX index_by_title (title)
)