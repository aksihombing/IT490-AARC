CREATE TABLE api_cache (
  id INT AUTO_INCREMENT PRIMARY KEY,
    search_type ENUM('title', 'author') NOT NULL,
    query VARCHAR(255) NOT NULL,
    response_json JSON NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP GENERATED ALWAYS AS (DATE_ADD(last_updated, INTERVAL 1 DAY)) STORED,
    /* GENERATED ALWAYS prevents php or users overriding it the expiration.

    https://dev.to/arctype/a-complete-guide-to-generated-columns-in-mysql-2lnb
    
    STORED or VIRTUAL can be used for generated columns
       */
    INDEX(search_type, query)
    /*  indexing it by search type and the query makes it easier to find it in the cache and call it  */
);

