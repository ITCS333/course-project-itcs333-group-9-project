/* 
  Requirement: Populate the "Course Resources" list page.
*/

// --- Element Selections ---
const listSection = document.querySelector('#resource-list-section');

// --- Functions ---

function createResourceArticle(resource) {
  const article = document.createElement('article');

  const title = document.createElement('h2');
  title.textContent = resource.title;

  const desc = document.createElement('p');
  desc.textContent = resource.description;

  const link = document.createElement('a');
  link.textContent = "View Resource & Discussion";
  link.href = `details.html?id=${resource.id}`;

  article.appendChild(title);
  article.appendChild(desc);
  article.appendChild(link);

  return article;
}

async function loadResources() {
  const response = await fetch('resources.json');
  const data = await response.json();

  listSection.innerHTML = "";

  data.forEach(resource => {
    const article = createResourceArticle(resource);
    listSection.appendChild(article);
  });
}

// --- Initial Page Load ---
loadResources();

