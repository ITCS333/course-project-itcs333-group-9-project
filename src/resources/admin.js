/*
  Requirement: Make the "Manage Resources" page interactive.
*/
let resources = [];
let editingResourceId = null;
const resourceForm = document.querySelector('#resource-form');
const resourcesTableBody = document.querySelector('#resources-tbody');
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

  if (editingResourceId !== null) {
    const item = resources.find(r => r.id === editingResourceId);
    item.title = title;
    item.description = description;
    item.link = link;
  } else {
    const newResource = {
      id: `res_${Date.now()}`,
      title,
      description,
      link
    };
    resources.push(newResource);
  }

  editingResourceId = null;
  renderTable();
  resourceForm.reset();
}

 

function handleTableClick(event) {
  if (event.target.classList.contains('delete-btn')) {
    const id = event.target.dataset.id;
    resources = resources.filter(resource => resource.id !== id);
    renderTable();
  }

  if (event.target.classList.contains('edit-btn')) {
    const id = event.target.dataset.id;
    const item = resources.find(r => r.id === id);

    document.querySelector('#resource-title').value = item.title;
    document.querySelector('#resource-description').value = item.description;
    document.querySelector('#resource-link').value = item.link;

    editingResourceId = id;
  }
}

async function loadAndInitialize() {
  const response = await fetch('./api/resources.json');
  const data = await response.json();
  resources = data;

  renderTable();
  resourceForm.addEventListener('submit', handleAddResource);
  resourcesTableBody.addEventListener('click', handleTableClick);
}

loadAndInitialize();


