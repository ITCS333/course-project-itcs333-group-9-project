# ITCS Course Page Project

## Overview
This is a static HTML website project for a Web Development course. It's a GitHub Classroom assignment where students are required to complete various HTML pages for a course management system.

## Project Type
- **Type**: Static HTML website
- **Language**: HTML/CSS (frontend only)
- **Server**: Python HTTP Server (for serving static files)
- **Port**: 5000

## Project Structure
```
.
├── index.html              # Main homepage (incomplete - contains TODO instructions)
├── src/
│   ├── admin/
│   │   └── manage_users.html    # Admin portal for managing students
│   ├── assignments/
│   │   ├── admin.html           # Admin view for assignments
│   │   ├── details.html         # Assignment details page
│   │   └── list.html            # Student view of assignments
│   ├── auth/
│   │   └── login.html           # Login page
│   ├── common/
│   │   └── styles.css           # Shared stylesheet
│   ├── discussion/
│   │   ├── baord.html           # Discussion board (note: typo in filename)
│   │   └── topic.html           # Discussion topic view
│   ├── resources/
│   │   ├── admin.html           # Admin resources management
│   │   ├── details.html         # Resource details
│   │   └── list.html            # Student view of resources
│   └── weekly/
│       ├── admin.html           # Admin weekly breakdown
│       ├── details.html         # Weekly details
│       └── list.html            # Student view of weekly breakdown
└── assets/
    └── README.md
```

## Current State
The project is a GitHub Classroom assignment template. All HTML files contain TODO comments with instructions for students to complete the pages. The files are currently empty of actual content but have structural guidelines in the comments.

## Team Members
- Moayed Ali Ebrahim Mubarak (202307169)
- Ali Hasan Ali Kadhem (202302877)  
- Mohammed Ali Rady (202304896)

## Configuration

### Workflow
- **Name**: Server
- **Command**: `python3 server.py`
- **Purpose**: Serves static HTML files on port 5000 with cache-control headers disabled
- **Note**: Custom server (server.py) prevents browser caching, ensuring CSS/JS changes appear immediately in preview

### Deployment
- **Target**: Autoscale (suitable for static websites)
- **Run Command**: `python3 server.py`

## Development
This is a learning project where students need to:
1. Complete the index.html with proper navigation
2. Fill in all TODO items in each HTML page
3. Add styling (likely using a CSS framework like PicoCSS or Bootstrap)
4. Create functional forms and links between pages

## Notes
- No build process required (pure HTML/CSS/JS)
- No dependencies or package manager needed
- Simple Python HTTP server is sufficient for development and deployment
- The site is currently showing blank pages because the HTML files only contain TODO comments
