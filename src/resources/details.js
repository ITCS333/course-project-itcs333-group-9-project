// --- Global Data Store ---
let currentResourceId = null;
let currentComments = [];

// --- Element Selections ---
const resourceTitle = document.getElementById("resource-title");
const resourceDescription = document.getElementById("resource-description");
const resourceLink = document.getElementById("resource-link");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newComment = document.getElementById("new-comment");

// --- Functions ---

// Get ID from URL
function getResourceIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

// Render main resource details
function renderResourceDetails(resource) {
  resourceTitle.textContent = resource.title;
  resourceDescription.textContent = resource.description;
  resourceLink.href = resource.link;
}

// Create a single <article> for a comment
function createCommentArticle(comment) {
  const article = document.createElement("article");

  const p = document.createElement("p");
  p.textContent = comment.text;

  const footer = document.createElement("footer");
  footer.textContent = `Posted by ${comment.author}`;

  article.appendChild(p);
  article.appendChild(footer);

  return article;
}

// Render all comments
function renderComments() {
  commentList.innerHTML = "";

  currentComments.forEach((comment) => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

// Add comment handler
function handleAddComment(event) {
  event.preventDefault();

  const commentText = newComment.value.trim();
  if (!commentText) return;

  const newObj = {
    author: "Student",
    text: commentText
  };

  currentComments.push(newObj);
  renderComments();
  newComment.value = "";
}

// Initialize page
async function initializePage() {
  currentResourceId = getResourceIdFromURL();

  if (!currentResourceId) {
    resourceTitle.textContent = "Resource not found.";
    return;
  }

  try {
    const [resourcesRes, commentsRes] = await Promise.all([
      fetch("resources.json"),
      fetch("resource-comments.json")
    ]);

    const resources = await resourcesRes.json();
    const comments = await commentsRes.json();

    const resource = resources.find(
      (r) => r.id.toString() === currentResourceId
    );

    currentComments = comments[currentResourceId] || [];

    if (!resource) {
      resourceTitle.textContent = "Resource not found.";
      return;
    }

    renderResourceDetails(resource);
    renderComments();

    commentForm.addEventListener("submit", handleAddComment);
  } catch (error) {
    resourceTitle.textContent = "Error loading resource.";
  }
}

// --- Initial Page Load ---
initializePage();
