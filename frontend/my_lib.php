<?php
// CHIZZY
// Start session and check if user is logged in.
// If not, send them back to login page.
session_start();
if (!isset($_SESSION['session_key'])) {
  header("Location: main.inc.php");
  exit;
}
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>My Library</title>
  <!-- linking external CSS file for styling -->
  <link rel="stylesheet" href="/css/library.css">
</head>

<body>
  <!-- Page heading -->
  <h2>My Library</h2>

  <!-- Message that shows up when a user has no saved books, will go to the search page assuming its called search??? -->
  <div id="empty" class="empty" style="display:none;">
    Your library is empty. <a href="search.php">Search for books →</a>
  </div>

  <!-- This grid will hold all the book cards -->
  <div id="grid" class="grid"></div>

  <script>
    //  Function that builds a HTML card for each book
    function cardHTML(it) {
      // If book has a cover ID, use Open Library’s cover API to show it
      const cover = it.cover_id
        ? `https://covers.openlibrary.org/b/id/${it.cover_id}-M.jpg?default=false`
        // Otherwise, show a simple "No cover" placeholder image
        : 'data:image/svg+xml;charset=utf8,' + encodeURIComponent(
          "<svg xmlns='http://www.w3.org/2000/svg' width='300' height='400'><rect width='100%' height='100%' fill='#eee'/><text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' fill='#777'>No cover</text></svg>"
        );

      // If author names exist, join them together into one string
      const authors = Array.isArray(it.author_names) ? it.author_names.join(', ') : '';

      // Return HTML for the book card (title, author, open button)
      // .replace() part makes sure text can’t break HTML
      // im assuming the work id for open lib is called work_olid, will change later
      return `
      <div class="card">
        <a href="book.php?works_id=${encodeURIComponent(it.works_id)}">
          <img class="cover" src="${cover}" alt="Cover">
        </a>
        <div class="title">${(it.title || '').replace(/[&<>"']/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s]))}</div>
        <div class="author">${(authors || '').replace(/[&<>"']/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s]))}</div>
        <div class="actions">
          <!-- “Open” button leads to that book’s details page -->
          <a class="btn" href="book.php?works_id=${encodeURIComponent(it.works_id)}">Open</a>
          <!-- Possible feature idk man? for the Remove button -->
          <!--
          <button class="btn btn-danger" data-work="${it.works_id}">Remove</button>
          -->
        </div>
      </div>`;
    }

    //   loads your personal library from backend
    async function loadLibrary() {
      // Fetch data from your internal API (library_list.php)
      const res = await fetch('/api/library_list.php', { credentials: 'include' });

      // If something goes wrong (like user not logged in), show empty message
      if (!res.ok) {
        document.getElementById('empty').style.display = 'block';
        return;
      }

      // Convert JSON response to JS object
      const data = await res.json();

      // Grab the “items” array (your saved books)
      const items = data.items || [];

      // Get the grid container
      const grid = document.getElementById('grid');

      // If no books saved → show empty message
      if (items.length === 0) {
        document.getElementById('empty').style.display = 'block';
        return;
      }

      // Hide “empty” message and display the book cards
      document.getElementById('empty').style.display = 'none';
      grid.innerHTML = items.map(cardHTML).join('');

      //  (Commented out for now) remove feature event listener for removing
      /*
      grid.onclick = async (e) => {
        const btn = e.target.closest('.btn-danger');
        if (!btn) return;
        const work = btn.getAttribute('data-work');
        const res = await fetch('/api/library_collect.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({ action: 'remove', works_id: work })
        });
        if (res.ok) loadLibrary();
        else alert('Failed to remove.');
      };
      */
    }

    //  Run the function as soon as the page loads
    loadLibrary();
  </script>
</body>

</html>