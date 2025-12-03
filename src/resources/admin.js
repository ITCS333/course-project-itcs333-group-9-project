/*
  Requirement: Make the "Manage Resources" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="resources-tbody"` to the <tbody> element
     inside your `resources-table`.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
let resources = [];

// --- Element Selections ---
const resourceForm = document.querySelector('#resource-form');
const resourcesTableBody = document.querySelector('#resources-tbody');

// --- Functions ---

function createResourceRow(resource) {
  const tr = document.createElement('tr');

  const titleTd = document.createElement('td');
  titleTd.textContent = resource.title;

  const descTd = document.createElement('td');
  descTd.textContent = resource.description;

  const actionsTd = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.classList.add('edit-btn');
  editBtn.dataset.id = resource.id;

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('delete-btn');
  deleteBtn.dataset.id = resource.id;

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(titleTd);
  tr.appendChild(descTd);
  tr.appendChild(actionsTd);

  return tr;
}

function renderTable() {
  resourcesTableBody.innerHTML = '';
  resources.forEach(resource => {
    const row = createResourceRow(resource);
    resourcesTableBody.appendChild(row);
  });
}

function handleAddResource(event) {
  event.preventDefault();

  const title = document.querySelector('#resource-title').value.trim();
  const description = document.querySelector('#resource-description').value.trim();
  const link = document.querySelector('#resource-link').value.trim();

  const newResource = {
    id: `res_${Date.now()}`,
    title,
    description,
    link
  };

  resources.push(newResource);
  renderTable();
  resourceForm.reset();
}

function handleTableClick(event) {
  if (event.target.classList.contains('delete-btn')) {
    const id = event.target.dataset.id;
    resources = resources.filter(resource => resource.id !== id);
    renderTable();
  }
}

async function loadAndInitialize() {
  const response = await fetch('resources.json');
  const data = await response.json();
  resources = data;

  renderTable();
  resourceForm.addEventListener('submit', handleAddResource);
  resourcesTableBody.addEventListener('click', handleTableClick);
}

// --- Initial Page Load ---
loadAndInitialize();

