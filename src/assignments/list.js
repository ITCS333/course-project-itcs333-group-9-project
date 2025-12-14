/*
  Requirement: Populate the "Course Assignments" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="assignment-list-section"` to the
     <section> element that will contain the assignment articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the assignment list ('#assignment-list-section').
const listSection = document.getElementById('assignment-list-section');

// --- Functions ---

/**
 * TODO: Implement the createAssignmentArticle function.
 * It takes one assignment object {id, title, dueDate, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * The "View Details" link's `href` MUST be set to `details.html?id=${id}`.
 * This is how the detail page will know which assignment to load.
 */
function createAssignmentArticle(assignment) {
  // ... your implementation here ...
  const { id, title, dueDate, description } = assignment;
  const article = document.createElement('article');

  const h2 = document.createElement('h2');
  h2.textContent = title;

  const pDueDate = document.createElement('p');

  pDueDate.innerHTML = `<strong>Due Date:</strong> ${dueDate}`;

  const pDescription = document.createElement('p');
  pDescription.textContent = description;

  const aDetails = document.createElement('a');
  aDetails.href = `details.html?id=${id}`;
  aDetails.textContent = 'View Details';

  article.appendChild(h2);
  article.appendChild(pDueDate);
  article.appendChild(pDescription);
  article.appendChild(aDetails);
  return article;
}

/**
 * TODO: Implement the loadAssignments function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'assignments.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the assignments array. For each assignment:
 * - Call `createAssignmentArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadAssignments() {
let assignments = [];
    let source = 'API';

    try {
        // --- 1. Attempt to fetch from API ---
        const apiResponse = await fetch("./api/index.php?resource=assignments");
        
        if (!apiResponse.ok) {
            throw new Error(`API HTTP error! status: ${apiResponse.status}`);
        }

        const apiData = await apiResponse.json();
        assignments = apiData.data || [];

    } catch (apiError) {
        console.warn(`API failed (${apiError.message}). Attempting to load local assignments.json as fallback.`);
        source = 'JSON';

        try {
            // --- 2. Attempt to fetch from local JSON file (Fallback) ---
            const jsonResponse = await fetch("./api/assignments.json"); 

            if (!jsonResponse.ok) {
                throw new Error(`Local JSON HTTP error! status: ${jsonResponse.status}`);
            }

            const jsonData = await jsonResponse.json();

            // (The JSON data structure already matches the necessary keys)
            assignments = jsonData.map(item => ({
                id: item.id,
                title: item.title,
                dueDate: item.dueDate, 
                description: item.description 
            }));

        } catch (jsonError) {
            console.error('Failed to load assignments from both API and local JSON:', jsonError);
            assignments = []; 
            source = 'Failed';
        }
    }

    if (listSection) {
        listSection.innerHTML = ''; 

        if (assignments.length === 0) {
            listSection.innerHTML = `<p class="info-message">No assignments found at this time. (Source: ${source})</p>`;
            return;
        }

        assignments.forEach(assignment => {
            const article = createAssignmentArticle({
                id: assignment.id,
                title: assignment.title,
                dueDate: assignment.dueDate || assignment.due_date,
                description: assignment.description 
            });
            listSection.appendChild(article);
        });
        
        console.log(`Assignments successfully loaded from ${source}. Total: ${assignments.length}`);
    }
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadAssignments();
