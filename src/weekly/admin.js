/*
  Requirement: Make the "Manage Weekly Breakdown" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="weeks-tbody"` to the <tbody> element
     inside your `weeks-table`.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the weekly data loaded from the JSON file.
let weeks = [];
let editingWeekId = null;

// --- Element Selections ---
// TODO: Select the week form ('#week-form').
const weekForm = document.getElementById("week-form");

// TODO: Select the weeks table body ('#weeks-tbody').
const weeksTbody = document.getElementById("weeks-tbody");

// --- Functions ---

/**
 * TODO: Implement the createWeekRow function.
 * It takes one week object {id, title, description}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `description`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createWeekRow(week) {
  // ... your implementation here ...
  const tableRow = document.createElement("tr");
  const weekTitleTd = document.createElement("td");
  weekTitleTd.innerText = week.title;
  weekTitleTd.classList.add("weeks-td");
  const weekDecriptionTd = document.createElement("td");
  weekDecriptionTd.innerText = week.description;
  weekDecriptionTd.classList.add("weeks-td", "desc");
  tableRow.appendChild(weekTitleTd);
  tableRow.appendChild(weekDecriptionTd);
  const lastTableDataElement = document.createElement("td");
  const editButton = document.createElement("button");
  const deleteButton = document.createElement("button");
  editButton.innerText = "Edit";
  deleteButton.innerText = "Delete";
  editButton.classList.add("edit-btn", "edit", "action-btn");
  deleteButton.classList.add("delete-btn", "edit", "action-btn");

  deleteButton.dataset.id = week.id;
  editButton.dataset.id = week.id;

  lastTableDataElement.appendChild(editButton);
  lastTableDataElement.appendChild(deleteButton);
  lastTableDataElement.classList.add("weeks-td");
  tableRow.appendChild(lastTableDataElement);
  return tableRow;
}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `weeksTableBody`.
 * 2. Loop through the global `weeks` array.
 * 3. For each week, call `createWeekRow()`, and
 * append the resulting <tr> to `weeksTableBody`.
 */
function renderTable() {
  // ... your implementation here ...
  const tableBody = document.getElementById("weeks-tbody");
  tableBody.innerHTML = "";
  weeks.map((week) => {
    tableBody.appendChild(createWeekRow(week));
  });
}

/**
 * TODO: Implement the handleAddWeek function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, start date, and description inputs.
 * 3. Get the value from the 'week-links' textarea. Split this value
 * by newlines (`\n`) to create an array of link strings.
 * 4. Create a new week object with a unique ID (e.g., `id: \`week_${Date.now()}\``).
 * 5. Add this new week object to the global `weeks` array (in-memory only).
 * 6. Call `renderTable()` to refresh the list.
 * 7. Reset the form.
 */
function handleAddWeek(event) {
  // ... your implementation here ...
  event.preventDefault();
  const title = document.getElementById("week-title").value;
  const startDate = document.getElementById("week-start-date").value;
  const description = document.getElementById("week-description").value;
  const weekLinks = document.getElementById("week-links").value.split("\n");
  console.log(weekLinks);
  const weekObj = {
    id: Math.floor(Math.random() * 4294967295 + 1),
    title,
    startDate,
    description,
    links: weekLinks,
  };
  customAddWeekToDB(weekObj).then((success) => {
    if (!success) {
      // alert("You must be logged in as an admin to add a week.")
      showToast("You must be logged in as an admin to add a week.", "error");
      return;
    }
    weeks.push(weekObj);
    renderTable();
    event.target.reset();
    showToast("Week added successfully!", "success");
  });
}
async function customAddWeekToDB(week) {
  try {
    const response = await fetch(`/src/weekly/api/index.php?resource=weeks`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        week_id: week.id,
        title: week.title,
        start_date: week.startDate,
        description: week.description,
        links: week.links,
      }),
    });
    const data = await response.json();
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${data.error || data.message}`);
    }
    return true;
    console.log("Week added to DB:", data);
  } catch (err) {
    console.log("Error adding week to DB: ", err.message);
    return false;
  }
}

/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `weeksTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `weeks` array by filtering out the week
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
function handleTableClick(event) {
  // ... your implementation here ...
  if (event.target.classList.contains("delete-btn")) {
    customDeleteWeekFromDB(event.target.dataset.id).then((success) => {
      if (!success) {
        // alert("You must be logged in as an admin to delete a week.")
        showToast(
          "You must be logged in as an admin to delete a week.",
          "error"
        );
        return;
      }
      weeks = weeks.filter((week) => week.id != event.target.dataset.id);
      renderTable();
      showToast("Week deleted successfully!", "success");
    });
  }
  if (event.target.classList.contains("edit-btn")) {
    // TODO: navigate to the top of the page
    window.scrollTo({ top: 80, behavior: "smooth" });
    editingWeekId = event.target.dataset.id;
    document.querySelector(".add-week-btn").classList.add("hide-btn");
    document.querySelector(".update-week-btn").classList.remove("hide-btn");
    document.querySelector(".cancel-update-btn").classList.remove("hide-btn");
    document.getElementById("week-title").value = weeks.filter(
      (week) => week.id == event.target.dataset.id
    )[0].title;
    document.getElementById("week-start-date").value = weeks.filter(
      (week) => week.id == event.target.dataset.id
    )[0].startDate;
    document.getElementById("week-description").value = weeks.filter(
      (week) => week.id == event.target.dataset.id
    )[0].description;
    document.getElementById("week-links").value = weeks
      .filter((week) => week.id == event.target.dataset.id)[0]
      .links.join("\n");
    // weeks = weeks.filter((week) => week.id != event.target.dataset.id);
    renderTable();
    document.querySelectorAll(".delete-btn").forEach((btn) => {
      console.log(btn);
      btn.disabled = true;
    });
  }
}
async function customDeleteWeekFromDB(weekId) {
  console.log("asdkljasdkjasdjkljs");
  try {
    const response = await fetch(`/src/weekly/api/index.php?resource=weeks`, {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ week_id: weekId }),
    });
    const data = await response.json();
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${data.error || data.message}`);
    }
    console.log("Week deleted from DB:", data);
    return true;
  } catch (err) {
    console.log("Error deleting week from DB: ", err.message);
    return false;
  }
}

function handleUpdateWeek(event) {
  event.preventDefault();

  const title = document.getElementById("week-title").value;
  const startDate = document.getElementById("week-start-date").value;
  const description = document.getElementById("week-description").value;
  const weekLinks = document.getElementById("week-links").value.split("\n");
  if (title.trim() === "" || startDate.trim() === "") {
    // alert("Title and Start Date are required.");
    showToast("Title and Start Date are required.", "error");
    return;
  }

  const weekId = editingWeekId;

  customUpdateWeekToDB({
    id: weekId,
    title,
    startDate,
    description,
    links: weekLinks,
  }).then((success) => {
    if (!success) {
      // alert("You must be logged in as an admin to update a week.")
      showToast("You must be logged in as an admin to update a week.", "error");
      return;
    }
    const newWeekObj = weeks.map((week, index) => {
      if (week.id == weekId) {
        return {
          id: weekId,
          title,
          startDate,
          description,
          links: weekLinks,
        };
      } else {
        return week;
      }
    });
    weeks = newWeekObj;
    editingWeekId = null;
    weekForm.reset();
    document.querySelector(".add-week-btn").classList.remove("hide-btn");
    document.querySelector(".update-week-btn").classList.add("hide-btn");
    document.querySelector(".cancel-update-btn").classList.add("hide-btn");
    renderTable();
    showToast("Week updated successfully!", "success");
  });

  document.querySelectorAll(".delete-btn").forEach((btn) => {
    console.log(btn);
    btn.disabled = false;
  });
  // event.target.reset();

  editingWeekId = null;
}
async function customUpdateWeekToDB(week) {
  try {
    const response = await fetch(`/src/weekly/api/index.php?resource=weeks`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        week_id: week.id,
        title: week.title,
        start_date: week.startDate,
        description: week.description,
        links: week.links,
      }),
    });
    const data = await response.json();
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${data.error || data.message}`);
    }
    return true;
  } catch (err) {
    console.log("Error updating week to DB: ", err.message);
    return false;
  }
}
function handleCancelUpdate(event) {
  event.preventDefault();
  editingWeekId = null;
  weekForm.reset();
  document.querySelector(".add-week-btn").classList.remove("hide-btn");
  document.querySelector(".update-week-btn").classList.add("hide-btn");
  document.querySelector(".cancel-update-btn").classList.add("hide-btn");
  document.querySelectorAll(".delete-btn").forEach((btn) => {
    console.log(btn);
    btn.disabled = false;
  });
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'weeks.json'.
 * 2. Parse the JSON response and store the result in the global `weeks` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `weekForm` (calls `handleAddWeek`).
 * 5. Add the 'click' event listener to `weeksTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
  // ... your implementation here ...
  // const data = await (await fetch("api/weeks.json")).json();
  try {
    const { data } = await (
      await fetch("/src/weekly/api/index.php?resource=weeks")
    ).json();
    weeks = data.map((week) => {
      return {
        id: week.id,
        title: week.title,
        startDate: week.start_date,
        description: week.description,
        links: week.links,
      };
    });
  } catch (err) {
    console.log("Error fetching weeks:", err);
    weeks = await (await fetch("api/weeks.json")).json();
  }
  renderTable();
  document
    .getElementById("week-form")
    .addEventListener("submit", handleAddWeek);
  document
    .getElementById("weeks-tbody")
    .addEventListener("click", handleTableClick);
  document
    .getElementById("update-week")
    .addEventListener("click", handleUpdateWeek);
  document
    .getElementById("cancel-update")
    .addEventListener("click", handleCancelUpdate);
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();

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
