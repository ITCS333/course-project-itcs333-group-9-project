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
  currentWeekId = id;
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

  if (week.links.length == 0) {
    const pElement = document.createElement("p");
    pElement.innerText = "No resources available for this week.";
    pElement.classList.add("no-resources");
    weekList.appendChild(pElement);
    return;
  }

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
  const spanAuthor = document.createElement("span");
  spanAuthor.classList.add("comment-author");
  spanAuthor.innerText = `Posted by: ${comment.author}`;
  const deleteButton = document.createElement("button");
  deleteButton.classList.add("delete-comment-btn");
  deleteButton.innerText = "Delete";
  articleFooter.appendChild(spanAuthor);
  articleFooter.appendChild(deleteButton);
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

  // if (localStorage.getItem("user") != undefined) {
  //   console.log(JSON.parse(localStorage.getItem("user")));
  //   const user = JSON.parse(localStorage.getItem("user"));
  //   commentObj.author = user.name;
  // }
  customAddCommentToDB(commentObj).then((data) => {
    if (!data) {
      // alert("You must be logged in to post a comment.")
      showToast("You must be logged in to post a comment.", "error");
      return;
    }
    currentComments.push(data);
    renderComments();
    event.target.reset();
    showToast("Comment posted successfully!", "success");
  });
  // currentComments.push(commentObj);
}
async function customAddCommentToDB(comment) {
  try {
    const response = await fetch(
      `/src/weekly/api/index.php?resource=comments`,
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          week_id: currentWeekId,
          author: comment.author,
          text: comment.text,
        }),
      }
    );
    const data = await response.json();
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${data.error || data.message}`);
    }
    // currentComments[currentComments.length - 1] = data.data;
    return data.data;
  } catch (err) {
    console.log("Error adding comment to DB:", err.message);
    return false;
  }
}

function handleDeleteComment(event) {
  const commentCard = event.target.closest(".comment-card");
  const commentText = commentCard.querySelector(".comment-text").innerText;
  const commentIndex = currentComments.findIndex(
    (comment) => comment.text === commentText
  );
  if (commentIndex !== -1) {
    customDeleteCommentFromDB(currentComments[commentIndex].id).then(
      (success) => {
        if (!success || success == 500) {
          if (success == 500) {
            // alert("You cannot delete other users' comments.")
            showToast("You cannot delete other users' comments.", "error");
            return;
          }
          // alert("You must be logged in as to delete your comment.")
          showToast(
            "You must be logged in as to delete your comment.",
            "error"
          );
          return;
        }
        currentComments.splice(commentIndex, 1);
        renderComments();
        showToast("Comment deleted successfully!", "success");
      }
    );
  }
}
async function customDeleteCommentFromDB(commentId) {
  try {
    const response = await fetch(
      `/src/weekly/api/index.php?resource=comments&id=${commentId}`,
      {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: commentId }),
      }
    );
    const data = await response.json();
    if (!response.ok) {
      if (response.status == 500) {
        return 500;
      }
      throw new Error(`HTTP ${response.status}: ${data.error || data.message}`);
    }
    return true;
  } catch (err) {
    console.log("Error deleting comment from DB:", err.message);
    return false;
  }
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
  // const test= await (await fetch("/src/weekly/api/index.php?resource=weeks&week_id=1")).json();
  // console.log(test)
  // const weeks = await (await fetch("api/weeks.json")).json();
  // const weekComments = await (await fetch("api/comments.json")).json();
  let week = null;
  let comments = [];
  try {
    const { data: commentsDb } = await (
      await fetch(
        `/src/weekly/api/index.php?resource=comments&week_id=${weekId}`
      )
    ).json();
    comments = commentsDb;
    console.log(comments);
    // const week = weeks.filter((week) => week.id == weekId)[0];
    const { data } = await (
      await fetch(`/src/weekly/api/index.php?resource=weeks&week_id=${weekId}`)
    ).json();
    week = {
      id: data.id,
      title: data.title,
      startDate: data.start_date,
      description: data.description,
      links: data.links,
    };
    console.log(week);
  } catch (err) {
    console.log("Error fetching week or comments from API:", err);
    const weeks = await (await fetch("api/weeks.json")).json();
    const weekComments = await (await fetch("api/comments.json")).json();
    week = weeks.filter((week) => week.id == weekId)[0];
    comments = weekComments[weekId];
  }
  if (week == null) {
    weekTitle.innerText = "Error: This week does not exist";
    return;
  }

  // const comments = weekComments[weekId];
  currentComments.push(...comments);

  renderWeekDetails(week);
  renderComments();
  document
    .getElementById("comment-form")
    .addEventListener("submit", handleAddComment);
  commentsDiv.addEventListener("click", function (event) {
    if (event.target && event.target.classList.contains("delete-comment-btn")) {
      handleDeleteComment(event);
    }
  });
}

// --- Initial Page Load ---
initializePage();

function showToast(message, type = "info", duration = 3000) {
  // Ensure container exists
  let toastContainer = document.getElementById("toast-container");
  if (!toastContainer) {
    toastContainer = document.createElement("div");
    toastContainer.id = "toast-container";
    toastContainer.style.position = "fixed";
    toastContainer.style.top = "20px";
    toastContainer.style.right = "20px";
    toastContainer.style.display = "flex";
    toastContainer.style.flexDirection = "column";
    toastContainer.style.gap = "10px";
    toastContainer.style.maxWidth = "90vw"; // responsive width
    toastContainer.style.zIndex = "9999";
    document.body.appendChild(toastContainer);
  }

  // Create toast element
  const toast = document.createElement("div");
  toast.innerText = message;
  toast.classList.add("toast");

  // Basic styles
  toast.style.padding = "10px 20px";
  toast.style.borderRadius = "5px";
  toast.style.color = "#fff";
  toast.style.fontWeight = "bold";
  toast.style.boxShadow = "0px 2px 6px rgba(0,0,0,0.2)";
  toast.style.wordWrap = "break-word";
  toast.style.maxWidth = "100%";
  toast.style.opacity = "0";
  toast.style.transition = "opacity 0.5s ease, transform 0.5s ease";
  toast.style.transform = "translateY(-10px)";

  // Background based on type
  switch (type) {
    case "success":
      toast.style.backgroundColor = "#4caf50";
      break;
    case "error":
      toast.style.backgroundColor = "#f44336";
      break;
    case "warning":
      toast.style.backgroundColor = "#ff9800";
      break;
    default:
      toast.style.backgroundColor = "#2196f3";
  }

  toastContainer.appendChild(toast);

  // Animate in
  setTimeout(() => {
    toast.style.opacity = "1";
    toast.style.transform = "translateY(0)";
  }, 100);

  // Animate out and remove
  setTimeout(() => {
    toast.style.opacity = "0";
    toast.style.transform = "translateY(-10px)";
    setTimeout(() => {
      toast.remove();
    }, 500);
  }, duration);
}
