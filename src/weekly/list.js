/*
  Requirement: Populate the "Weekly Course Breakdown" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="week-list-section"` to the
     <section> element that will contain the weekly articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the week list ('#week-list-section').
const weekListSection = document.getElementById("week-list-section");

// --- Functions ---

/**
 * TODO: Implement the createWeekArticle function.
 * It takes one week object {id, title, startDate, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * - The "View Details & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
 * (This is how the detail page will know which week to load).
 */
function createWeekArticle(week) {
  // ... your implementation here ...
  const article = document.createElement("article");
  article.classList.add("week-card");
  const h2 = document.createElement("h2");
  h2.classList.add("week-title");
  h2.innerText = week.title;
  const dateP = document.createElement("p");
  dateP.classList.add("week-date");
  dateP.innerText = `starts on: ${week.startDate}`;
  const descriptionP = document.createElement("p");
  descriptionP.classList.add("week-description");

  descriptionP.innerText = week.description;
  const aElement = document.createElement("a");
  aElement.href = `details.html?id=${week.id}`;
  aElement.classList.add("week-link");
  aElement.innerText = "View Details & Discussion";
  article.appendChild(h2);
  article.appendChild(dateP);
  article.appendChild(descriptionP);
  article.appendChild(aElement);
  return article;
}

/**
 * TODO: Implement the loadWeeks function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'weeks.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the weeks array. For each week:
 * - Call `createWeekArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadWeeks() {
  // ... your implementation here ...
  const weeks = await (await fetch("api/weeks.json")).json();
  weekListSection.innerHTML = "";
  weeks.map((week) => {
    weekListSection.appendChild(createWeekArticle(week));
  });
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadWeeks();
