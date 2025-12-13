/*
  Requirement: Make the "Manage Assignments" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="assignments-tbody"` to the <tbody> element
     so you can select it.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the assignments loaded from the JSON file.
let assignments = [];
const API_URL = "./api/index.php?resource=assignments";

// --- Element Selections ---
// TODO: Select the assignment form ('#assignment-form').
const assignmentForm = document.getElementById('assignment-form');
// TODO: Select the assignments table body ('#assignments-tbody').
const assignmentsTableBody = document.getElementById('assignments-tbody');
// --- Functions ---

/**
 * TODO: Implement the createAssignmentRow function.
 * It takes one assignment object {id, title, dueDate}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `dueDate`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createAssignmentRow(assignment) {
  // ... your implementation here ...
    const row = document.createElement('tr');

    const titleCell = document.createElement('td');
    titleCell.textContent = assignment.title;
    row.appendChild(titleCell);

    const dueDateCell = document.createElement('td');
    dueDateCell.textContent = assignment.dueDate;
    row.appendChild(dueDateCell);

    const actionsCell = document.createElement('td');
    actionsCell.innerHTML = `<button class="edit-btn" data-id="${assignment.id}">Edit</button>
    <button class="delete-btn" data-id="${assignment.id}">Delete</button>`;
    row.appendChild(actionsCell);

    return row;
}
/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `assignmentsTableBody`.
 * 2. Loop through the global `assignments` array.
 * 3. For each assignment, call `createAssignmentRow()`, and
 * append the resulting <tr> to `assignmentsTableBody`.
 */
function renderTable() {
  // ... your implementation here ...
    assignmentsTableBody.innerHTML = '';

    if (assignments.length === 0) {
        // Simple message if table is empty
        assignmentsTableBody.innerHTML = '<tr><td colspan="3">No assignments found.</td></tr>';
        return;
    }

    assignments.forEach(assignment => {
        const row = createAssignmentRow(assignment);
        assignmentsTableBody.appendChild(row)
    });
}
/**
 * TODO: Implement the handleAddAssignment function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, description, due date, and files inputs.
 * 3. Create a new assignment object with a unique ID (e.g., `id: \`asg_${Date.now()}\``).
 * 4. Add this new assignment object to the global `assignments` array (in-memory only).
 * 5. Call `renderTable()` to refresh the list.
 * 6. Reset the form.
 */
async function handleAddAssignment(event) {
  // ... your implementation here ...
    event.preventDefault();

    // 1. Collect and validate local form data
    const title = document.getElementById('assignment-title').value;
    const description = document.getElementById('assignment-description').value;
    const dueDate = document.getElementById('assignment-due-date').value;
    const files = document.getElementById('assignment-files').value
        .split('\n')
        .map(file => file.trim())
        .filter(file => file !== '');

    if (!title || !description || !dueDate) {
        alert("Please fill in all required fields (Title, Description, Due Date).");
        return;
    }

    // Prepare data for the API (use API's snake_case for due date)
    const newAssignmentData = {
        title: title,
        description: description,
        due_date: dueDate, // API expects 'due_date'
        files: files
    };

    try {
        // 2. Send POST request to API
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(newAssignmentData),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const apiResponse = await response.json();

        if (!apiResponse.success || !apiResponse.data) {
             throw new Error(apiResponse.message || 'API failed to create assignment.');
        }

        // 3. Add the *API-returned* data (with the real ID) to the local array
        const createdAssignment = apiResponse.data;

        // Map the fields from the API's snake_case to the frontend's camelCase
        assignments.push({
            id: createdAssignment.id,
            title: createdAssignment.title,
            description: createdAssignment.description,
            dueDate: createdAssignment.due_date, // Mapped field
            files: createdAssignment.files
        });

        // 4. Update UI
        renderTable();
        assignmentForm.reset();
        alert(`Assignment "${title}" created successfully!`);

    } catch (error) {
        console.error('Error creating assignment:', error);
        alert('Failed to add assignment. Check console for details.');
    }
}
/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `assignmentsTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `assignments` array by filtering out the assignment
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
async function handleTableClick(event) {
  // ... your implementation here ...

    if (event.target.classList.contains('delete-btn')) {
        const id = event.target.getAttribute('data-id');

        if (!confirm(`Are you sure you want to delete assignment ID ${id}? This action cannot be undone.`)) {
            return;
        }

        try {
            // 1. Send DELETE request to API, including ID as query parameter
            const deleteUrl = `${API_URL}&id=${id}`; 

            const response = await fetch(deleteUrl, {
                method: 'DELETE',
            });

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            const apiResponse = await response.json();
            if (!apiResponse.success) {
                 throw new Error(apiResponse.message || 'API failed to delete assignment.');
            }

            // 2. On success, update the local array by filtering out the deleted ID
            assignments = assignments.filter(assignment => assignment.id !== parseInt(id));

            // 3. Update UI
            renderTable();
            alert('Assignment deleted successfully.');

        } catch (error) {
            console.error('Error deleting assignment:', error);
            alert('Failed to delete assignment. Check console for details.');
        }
    }
    // TODO: Add logic for 'edit-btn' here when implementing the edit form.
}
/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'assignments.json'.
 * 2. Parse the JSON response and store the result in the global `assignments` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `assignmentForm` (calls `handleAddAssignment`).
 * 5. Add the 'click' event listener to `assignmentsTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
      // ... your implementation here ...
    try {
        // 1. Fetch data from PHP API
        const response = await fetch(API_URL);

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const apiResponse = await response.json();

        if (!apiResponse.success) {
            throw new Error(apiResponse.message || 'API request failed.');
        }

        let apiAssignments = apiResponse.data || [];

        // 2. Map the API response fields to match the frontend structure
        assignments = apiAssignments.map(a => ({
            id: a.id,
            title: a.title,
            description: a.description,
            dueDate: a.due_date,
            files: a.files || []
        }));

        // 3. Initialize UI and event listeners
        renderTable();

        assignmentForm.addEventListener('submit', handleAddAssignment);
        assignmentsTableBody.addEventListener('click', handleTableClick);
    }
    catch(error){
        console.error('Error loading assignments:', error.message);
        assignmentsTableBody.innerHTML = '<tr><td colspan="3" class="error-message">Failed to load data from API. See console.</td></tr>';
    }
}
// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
