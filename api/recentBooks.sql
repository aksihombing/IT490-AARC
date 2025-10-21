DROP TABLE IF EXISTS recentBooks;

/* easy way to delete all entries when the script is run i guess !*/


CREATE TABLE IF NOT EXISTS recentBooks (
   
    /*basics -- may need more depending on what info we need for the website ? */
    id INT AUTO_INCREMENT PRIMARY KEY,
    olid VARCHAR(50) DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) DEFAULT NULL,
    author VARCHAR(255) DEFAULT 'Unknown Author',
    isbn VARCHAR(50) DEFAULT NULL,
    -- from /works/olid/edition.json
    book_desc TEXT DEFAULT NULL,



    /* ints */

    publish_year INT DEFAULT NULL,
    ratings_average DECIMAL(3, 2) DEFAULT NULL,
    ratings_count INT DEFAULT NULL,



    /* subject/genre */
    -- these should be JSON_ENCODE(data) when INSERTING values
    -- use JSON_DECODE(data) when reading it from the frontend

    subjects JSON DEFAULT NULL,
    person_key JSON DEFAULT NULL,
    place_key JSON DEFAULT NULL,
    time_key JSON DEFAULT NULL,
    
    cover_url TEXT DEFAULT NULL,


    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX index_by_year (publish_year),
    INDEX index_by_title (title)
)