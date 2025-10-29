/*
  Requirement: Populate the weekly detail page and discussion forum.

  Instructions:
  1. Link this file to `details.html` using:
     <script src="details.js" defer></script>

  2. In `details.html`, add the following IDs:
     - To the <h1>: `id="week-title"`
     - To the start date <p>: `id="week-start-date"`
     - To the description <p>: `id="week-description"`
     - To the "Exercises & Resources" <ul>: `id="week-links-list"`
     - To the <div> for comments: `id="comment-list"`
     - To the "Ask a Question" <form>: `id="comment-form"`
     - To the <textarea>: `id="new-comment-text"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// These will hold the data related to *this* specific week.
let currentWeekId = null;
let currentComments = [];

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.
const weekTitle = document.getElementById("week-title");
const startDate = document.getElementById("week-start-date");
const weekDescription = document.getElementById("week-description");
const weekList = document.getElementById("week-links-list");
const commentsDiv = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentTextarea = document.getElementById("new-comment");

// --- Functions ---

/**
 * TODO: Implement the getWeekIdFromURL function.
 * It should:
 * 1. Get the query string from `window.location.search`.
 * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
 * 3. Return the id.
 */
function getWeekIdFromURL() {
  // ... your implementation here ...
  const queryString = window.location.search;
  // console.log(queryString);
  const id = new URLSearchParams(queryString).get("id");
  return id;
}

/**
 * TODO: Implement the renderWeekDetails function.
 * It takes one week object.
 * It should:
 * 1. Set the `textContent` of `weekTitle` to the week's title.
 * 2. Set the `textContent` of `weekStartDate` to "Starts on: " + week's startDate.
 * 3. Set the `textContent` of `weekDescription`.
 * 4. Clear `weekLinksList` and then create and append `<li><a href="...">...</a></li>`
 * for each link in the week's 'links' array. The link's `href` and `textContent`
 * should both be the link URL.
 */
function renderWeekDetails(week) {
  // ... your implementation here ...
  weekTitle.innerText = week.title;
  startDate.innerText = `Starts on: ${week.startDate}`;
  weekDescription.innerText = week.description;
  weekList.innerHTML = "";

  week.links.map((link) => {
    const listElement = document.createElement("li");
    console.log(link);
    const aElement = document.createElement("a");
    aElement.href = link;
    aElement.target = "_blank";
    aElement.classList.add("resource-link");
    aElement.innerText = link;
    listElement.appendChild(aElement);

    weekList.appendChild(listElement);
  });
}

/**
 * TODO: Implement the createCommentArticle function.
 * It takes one comment object {author, text}.
 * It should return an <article> element matching the structure in `details.html`.
 * (e.g., an <article> containing a <p> and a <footer>).
 */
function createCommentArticle(comment) {
  // ... your implementation here ...
  const commentArticle = document.createElement("article");
  commentArticle.classList.add("comment-card");
  const articleComment = document.createElement("p");
  articleComment.classList.add("comment-text");
  articleComment.innerText = comment.text;

  const articleFooter = document.createElement("footer");
  articleFooter.classList.add("comment-footer");
  articleFooter.innerText = comment.author;
  commentArticle.appendChild(articleComment);
  commentArticle.appendChild(articleFooter);
  return commentArticle;
}

/**
 * TODO: Implement the renderComments function.
 * It should:
 * 1. Clear the `commentList`.
 * 2. Loop through the global `currentComments` array.
 * 3. For each comment, call `createCommentArticle()`, and
 * append the resulting <article> to `commentList`.
 */
function renderComments() {
  // ... your implementation here ...
  console.log(currentComments);
  commentsDiv.innerHTML = "";
  currentComments.map((comment) => {
    commentsDiv.appendChild(createCommentArticle(comment));
  });
}

/**
 * TODO: Implement the handleAddComment function.
 * This is the event handler for the `commentForm` 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the text from `newCommentText.value`.
 * 3. If the text is empty, return.
 * 4. Create a new comment object: { author: 'Student', text: commentText }
 * (For this exercise, 'Student' is a fine hardcoded author).
 * 5. Add the new comment to the global `currentComments` array (in-memory only).
 * 6. Call `renderComments()` to refresh the list.
 * 7. Clear the `newCommentText` textarea.
 */
function handleAddComment(event) {
  // ... your implementation here ...
  event.preventDefault();
  const commentText = newCommentTextarea.value;
  if ((commentText.trim().length = 0)) {
    return;
  }
  const commentObj = { author: "Student", text: commentText };
  currentComments.push(commentObj);
  renderComments();
  event.target.reset();
}

/**
 * TODO: Implement an `initializePage` function.
 * This function needs to be 'async'.
 * It should:
 * 1. Get the `currentWeekId` by calling `getWeekIdFromURL()`.
 * 2. If no ID is found, set `weekTitle.textContent = "Week not found."` and stop.
 * 3. `fetch` both 'weeks.json' and 'week-comments.json' (you can use `Promise.all`).
 * 4. Parse both JSON responses.
 * 5. Find the correct week from the weeks array using the `currentWeekId`.
 * 6. Get the correct comments array from the comments object using the `currentWeekId`.
 * Store this in the global `currentComments` variable. (If no comments exist, use an empty array).
 * 7. If the week is found:
 * - Call `renderWeekDetails()` with the week object.
 * - Call `renderComments()` to show the initial comments.
 * - Add the 'submit' event listener to `commentForm` (calls `handleAddComment`).
 * 8. If the week is not found, display an error in `weekTitle`.
 */
async function initializePage() {
  // ... your implementation here ...
  const weekId = getWeekIdFromURL();
  console.log(weekId);
  if (weekId == null) {
    weekTitle.innerText = "Week not found.";
    return;
  }

  const weeks = await (await fetch("api/weeks.json")).json();
  const weekComments = await (await fetch("api/comments.json")).json();
  const week = weeks.filter((week) => week.id == weekId)[0];

  if (week == null) {
    weekTitle.innerText = "Error: This week does not exist";
    return;
  }

  const comments = weekComments[weekId];
  currentComments.push(...comments);

  renderWeekDetails(week);
  renderComments();
  document
    .getElementById("comment-form")
    .addEventListener("submit", handleAddComment);
}

// --- Initial Page Load ---
initializePage();
