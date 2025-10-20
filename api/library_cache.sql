CREATE TABLE library_cache (
  id INT AUTO_INCREMENT PRIMARY KEY,
  search_type ENUM('title', 'author') NOT NULL,
  query VARCHAR(255) NOT NULL,

  /* basics -- may need more depending on what info we need for the website ? */
  olid VARCHAR(50) DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  subtitle VARCHAR(255) DEFAULT NULL,
  alternative_title VARCHAR(255) DEFAULT NULL,
  alternative_subtitle VARCHAR(255) DEFAULT NULL,
  author VARCHAR(255) DEFAULT 'Unknown Author',
  isbn VARCHAR(50) DEFAULT NULL,
  publisher VARCHAR(255) DEFAULT NULL,
  publish_year INT DEFAULT NULL,
  ratings_count INT DEFAULT NULL,

  /* subject/genre */
  subject_key JSON DEFAULT NULL,
  person_key JSON DEFAULT NULL,
  place_key JSON DEFAULT NULL,
  time_key JSON DEFAULT NULL,

  cover_url TEXT DEFAULT NULL,

  /*response_json JSON NOT NULL,*/
  /* had it all saved as one jsos column but im changing it i guess */

  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP GENERATED ALWAYS AS (DATE_ADD(last_updated, INTERVAL 1 DAY)) STORED,
  /* GENERATED ALWAYS prevents php or users overriding it the expiration. not sure how we want to handle expirations
   
   https://dev.to/arctype/a-complete-guide-to-generated-columns-in-mysql-2lnb
   
   STORED or VIRTUAL can be used for generated columns. says that the value is physicially stored
   */
  INDEX(search_type, query)
  /*  indexing it by search type and the query makes it easier to find it in the cache and call it  */
);