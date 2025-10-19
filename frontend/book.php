<?php
session_start();
if (!isset($_SESSION['session_key'])) { header("Location: main.inc.php"); exit; }

$work = $_GET['works_id'] ?? '';
if ($work === '') { header("Location: main.inc.php?content=search"); exit; }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Book Details</title>
  <link rel="stylesheet" href="/css/book.css">
</head>
<body>
<?php include("nav.inc.php"); ?>
<h2 id="book-title">Book Title</h2>
<p id="book-author">by Author Name</p>

<div class="book-info">
  <img id="cover" class="cover" alt="Book Cover">
  <div id="book-data">Book details go here (release, genre, etc.)</div>
</div>

<button id="addToLib" class="btn">Add to My Library</button>

<section>
  <h3>Write a Review</h3>
  <form id="reviewForm">
    <label>Rating:
      <select id="rating" required>
        <option value="">Select...</option>
        <option>1</option><option>2</option><option>3</option><option>4</option><option>5</option>
      </select>
    </label>
    <br>
    <label>Review:</label><br>
    <textarea id="comment" rows="3" placeholder="Write your thoughts here..."></textarea>
    <br>
    <button class="btn" type="submit">Submit</button>
  </form>
</section>

<section>
  <h3>User Reviews</h3>
  <div id="reviews"></div>
</section>

<script>
const WORKS_ID = "<?= htmlspecialchars($work, ENT_QUOTES, 'UTF-8') ?>";

// optional fast-fill via query params
const qs = new URLSearchParams(location.search);
const qTitle   = qs.get('title');
const qAuthors = qs.getAll('author_names[]');
const qCoverId = qs.get('cover_id');
if (qTitle) document.getElementById('book-title').textContent = qTitle;
if (qAuthors && qAuthors.length)
  document.getElementById('book-author').textContent = 'by ' + qAuthors.join(', ');
if (qCoverId)
  document.getElementById('cover').src = `https://covers.openlibrary.org/b/id/${encodeURIComponent(qCoverId)}-L.jpg?default=false`;

function esc(s){return (s||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));}

// load reviews
async function loadReviews(){
  const res = await fetch(`/http/reviews_list.php?works_id=${encodeURIComponent(WORKS_ID)}`);
  if (!res.ok){ document.getElementById('reviews').innerHTML = '<div>Failed to load reviews.</div>'; return; }
  const data = await res.json();
  const items = Array.isArray(data.items) ? data.items : [];
  const html = items.length ? items.map(r => {
    const name = r.user_display || r.username || 'User';
    const rating = Number(r.rating)||0;
    const text = r.comment ?? r.body ?? '';
    return `<div class="review"><b>${esc(name)}</b> (${rating}/5): ${esc(text)}</div>`;
  }).join('') : '<div>No reviews yet.</div>';
  document.getElementById('reviews').innerHTML = html;
}

//although we may have some data from query params, this load full details from backend 
async function loadDetails(){
  const r = await fetch(`/http/book_details.php?works_id=${encodeURIComponent(WORKS_ID)}`, {credentials:'include'});
  if (!r.ok) return; 
  const d = await r.json();
  if (d.status !== 'success') return;

  const b = d.item || {};
  document.getElementById('book-title').textContent  = b.title || 'Unknown';
  document.getElementById('book-author').textContent = (b.author_names && b.author_names.length)
    ? 'by ' + b.author_names.join(', ')
    : '';
  if (b.cover_id) {
    document.getElementById('cover').src = `https://covers.openlibrary.org/b/id/${b.cover_id}-L.jpg?default=false`;
  }
  document.getElementById('book-data').textContent = b.description || '';
}

// submit review
document.getElementById('reviewForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const rating  = Number(document.getElementById('rating').value);
  const comment = document.getElementById('comment').value.trim();
  if (!rating || rating < 1 || rating > 5) { alert('Pick a rating 1–5'); return; }

  const res = await fetch('/http/reviews_create.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    credentials: 'include',
    body: JSON.stringify({ works_id: WORKS_ID, rating, comment })
  });
  if (res.ok) { e.target.reset(); loadReviews(); }
  else { alert('Error saving review.'); }
});

// add to library
document.getElementById('addToLib').addEventListener('click', async () => {
  const res = await fetch('/http/library_collect.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    credentials: 'include',
    body: JSON.stringify({ action:'add', works_id: WORKS_ID })
  });
  if (res.ok) alert('Added to library!');
  else alert('Failed to add.');
});

loadReviews();
</script>

</body>
</html>




