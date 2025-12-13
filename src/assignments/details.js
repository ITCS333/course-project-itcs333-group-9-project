/*
  Requirement: Populate the assignment detail page and discussion forum.

  Instructions:
  1. Link this file to `details.html` using:
     <script src="details.js" defer></script>

  2. In `details.html`, add the following IDs:
     - To the <h1>: `id="assignment-title"`
     - To the "Due" <p>: `id="assignment-due-date"`
     - To the "Description" <p>: `id="assignment-description"`
     - To the "Attached Files" <ul>: `id="assignment-files-list"`
     - To the <div> for comments: `id="comment-list"`
     - To the "Add a Comment" <form>: `id="comment-form"`
     - To the <textarea>: `id="new-comment-text"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// These will hold the data related to *this* assignment.
let currentAssignmentId = null;
let currentComments = [];
const API_URL = "./api/index.php";

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.
const assignmentTitle = document.getElementById("assignment-title");
const assignmentDueDate = document.getElementById("assignment-due-date");
const assignmentDescription = document.getElementById("assignment-description");
const assignmentFilesList = document.getElementById("assignment-files-list");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentText = document.getElementById("new-comment-text");
// --- API Helpers ---

/**
 * Generic function to fetch data from the API
 */
async function fetchData(endpoint) {
    const response = await fetch(endpoint);
    if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
    }
    const apiResponse = await response.json();
    if (!apiResponse.success) {
        throw new Error(apiResponse.message || 'API request failed.');
    }
    return apiResponse.data;
}

/**
 * Generic function to POST data to the API
 */
async function postData(endpoint, data) {
    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    });
    if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
    }
    const apiResponse = await response.json();
    if (!apiResponse.success) {
        throw new Error(apiResponse.message || 'Failed to post data to API.');
    }
    return apiResponse.data;
}

// --- Functions ---

/**
 * TODO: Implement the getAssignmentIdFromURL function.
 * It should:
 * 1. Get the query string from `window.location.search`.
 * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
 * 3. Return the id.
 */
function getAssignmentIdFromURL() {
  // ... your implementation here ...
  let queryString = window.location.search;
  let urlParams = new URLSearchParams(queryString);
  return urlParams.get("id");
}

/**
 * TODO: Implement the renderAssignmentDetails function.
 * It takes one assignment object.
 * It should:
 * 1. Set the `textContent` of `assignmentTitle` to the assignment's title.
 * 2. Set the `textContent` of `assignmentDueDate` to "Due: " + assignment's dueDate.
 * 3. Set the `textContent` of `assignmentDescription`.
 * 4. Clear `assignmentFilesList` and then create and append
 * `<li><a href="#">...</a></li>` for each file in the assignment's 'files' array.
 */
function renderAssignmentDetails(assignment) {
  // ... your implementation here ...
  assignmentTitle.textContent = assignment.title;
  
  assignmentDueDate.textContent = "Due: " + assignment.due_date; 
  assignmentDescription.textContent = assignment.description;
  assignmentFilesList.innerHTML = "";

  (assignment.files || []).forEach(file => {
      const li = document.createElement("li");
      const a = document.createElement("a");

      a.href = file; 
      a.textContent = file.split('/').pop();

      li.appendChild(a);
      assignmentFilesList.appendChild(li);
  });
}

/**
 * TODO: Implement the createCommentArticle function.
 * It takes one comment object {author, text}.
 * It should return an <article> element matching the structure in `details.html`.
 */
function createCommentArticle(comment)
{
  // ... your implementation here ...
  const article = document.createElement("article");
  const p = document.createElement("p");
  
  p.textContent = comment.text; 
  const footer = document.createElement("footer");
  
  footer.textContent = "Posted by: " + comment.author; 

  article.appendChild(p);
  article.appendChild(footer);
  return article;
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
  commentList.innerHTML = "";

  if (currentComments.length === 0) {
      commentList.innerHTML = '<p class="info-message">No comments yet. Be the first!</p>';
      return;
  }

  currentComments.forEach(comment => {
      const commentArticle = createCommentArticle(comment);
      commentList.appendChild(commentArticle);
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
async function handleAddComment(event) {
  // ... your implementation here ...
    event.preventDefault();

    // Disable form elements temporarily to prevent double submission
    const submitButton = event.target.querySelector('button[type="submit"]');
    if (submitButton) submitButton.disabled = true;

    const commentText = newCommentText.value.trim();

    if (commentText === "") {
        if (submitButton) submitButton.disabled = false;
        return;
    }

    try {
        const newCommentData = {
            // Hardcoding 'Student' as author as per your original TODO, 
            // but the API supports different names
            assignment_id: currentAssignmentId,
            author: "Student", 
            text: commentText
        };

        // 1. Send the new comment to the API
        const newComment = await postData(API_URL + "?resource=comments", newCommentData);

        // 2. Add the newly created comment (with ID, timestamp, etc.) to the local array
        currentComments.push(newComment);

        // 3. Render and clear
        renderComments();
        newCommentText.value = "";

    } catch (error) {
        console.error('Error posting comment:', error);
        alert('Failed to post comment. Check console for details.');
    } finally {
        if (submitButton) submitButton.disabled = false;
    }
}

/**
 * TODO: Implement an `initializePage` function.
 * This function needs to be 'async'.
 * It should:
 * 1. Get the `currentAssignmentId` by calling `getAssignmentIdFromURL()`.
 * 2. If no ID is found, display an error and stop.
 * 3. `fetch` both 'assignments.json' and 'comments.json' (you can use `Promise.all`).
 * 4. Find the correct assignment from the assignments array using the `currentAssignmentId`.
 * 5. Get the correct comments array from the comments object using the `currentAssignmentId`.
 * Store this in the global `currentComments` variable.
 * 6. If the assignment is found:
 * - Call `renderAssignmentDetails()` with the assignment object.
 * - Call `renderComments()` to show the initial comments.
 * - Add the 'submit' event listener to `commentForm` (calls `handleAddComment`).
 * 7. If the assignment is not found, display an error.
 */
async function initializePage() {
  // ... your implementation here ...
    try {
        currentAssignmentId = getAssignmentIdFromURL();

        if (!currentAssignmentId) {
            assignmentTitle.textContent = "Error: No assignment ID found in URL.";
            return;
        }

        // --- 1. Fetch Assignment Details ---
        const assignmentEndpoint = `${API_URL}?resource=assignments&id=${currentAssignmentId}`;
        const assignment = await fetchData(assignmentEndpoint);

        // --- 2. Fetch Comments ---
        const commentsEndpoint = `${API_URL}?resource=comments&assignment_id=${currentAssignmentId}`;
        currentComments = await fetchData(commentsEndpoint);

        // --- 3. Render Data ---
        if (assignment) {
            renderAssignmentDetails(assignment);
            renderComments();
            commentForm.addEventListener("submit", handleAddComment);
        } else {
             // This case should be handled by the 404 response check in fetchData, 
             // but included for clarity.
            assignmentTitle.textContent = "Assignment not found for ID: " + currentAssignmentId;
        }

    } catch (error) {
        // Display a user-friendly error on the page
        console.error('Error initializing page:', error);
        assignmentTitle.textContent = "Error loading assignment or comments.";
        assignmentDescription.textContent = `A detailed error occurred: ${error.message}. Please check the console.`;
    }
}
// --- Initial Page Load ---
initializePage();
