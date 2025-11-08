# IT490-AARC

## DMZ

The Dev-DMZ branch contains the Data Processor for all API calls within the Development cluster.

**Library_API.php** - request processor

**api_process.php** 
<ul>
    <li>*doBookSearch()* - Checks cache by general query, calls api if book not found in cache</li>
    <li>*doBookDetails()* - Checks cache by OLID, calls api if needed</li>
    <li>*getRecentBooks()* - Calls pre-populated cache</li>
    <li>*doBookRecommend()* - Uses a hybrid content-based recommendation system and calls the subjects api specifically</li>
</ul>

**api_endpoints.php** 
<ul>
    <li>*curl_get()* - Helper function to cURL api</li>
    <li>*simple_sanitize()* - Used for santizing subjects</li>
    <li>*api_search()* - Mainly calls the Search api endpoint with page and limits</li>
    <li>*api_olid_details()* - Calls Search api for specific OLID</li>
</ul>

**api_cache.php** 
<ul>
    <li>*db()* - Helper function reference local database</li>
    <li>*bookCache_check_query()* - Checks cache for specific query</li>
    <li>*bookCache_check_olid()* - Checks cache for specific OLID</li>
    <li>*bookCache_add()* - Adds to cache; used by bookCache_check_query() and bookCache_check_olid()</li>
</ul>