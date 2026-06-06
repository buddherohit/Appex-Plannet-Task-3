# Smart User Management System

A secure, responsive, and modern Full-Stack User Management System developed as an Internship Task-3 submission. This system features robust authentication, session protection, role-based access control (RBAC), full CRUD operations for administrators, and profile picture uploads.

## Tech Stack
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript, Chart.js, Bootstrap Icons
- **Backend**: PHP 8.0+ (using prepared statements and procedural mysqli)
- **Database**: MySQL
- **Environment**: XAMPP / WAMP server stack

---

## Key Features

### 1. Robust Authentication & Session Controls
- **Registration**: Form validation (both client-side JS and server-side PHP filters) verifying mobile formats, password lengths, and matching constraints.
- **Duplicate Checks**: Real-time SQL checks utilizing prepared statements to prevent duplicate email registrations.
- **Login Terminal**: Safe password matching using PHP's standard `password_verify()` against bcrypt hashes.
- **Remember Me**: Cryptographically signed cookie mechanism (`base64` encoded format storing userId, email, and signature hash matching user password hash) to automatically log in returning users.
- **Session Hijacking Prevention**: Forces `session_regenerate_id(true)` upon successful logins and sets secure cookie parameter settings.

### 2. Role-Based Access Control (RBAC)
- **Admin**: Has full access, including system statistics dashboard, visual Chart.js graphs, full activity logs auditing, and the ability to Create, Read, Update, and Delete (CRUD) users.
- **User**: Has limited access, landing on their personal profile page and restricted dashboard displaying only their personal activity logs.
- **Session Guards**: All PHP pages contain strict authorization guards directing unauthorized requests back to `login.php`.

### 3. Comprehensive CRUD Panel (Admin Only)
- **Create**: Add a new user directly, setting their specific system role.
- **Read**: Dynamic Bootstrap 5 table with real-time column sorting (by Name, Date Registered, or Role), live searching (matching full name or email), and paginated rows limit (10 per page).
- **Update**: Edit other users' details or force reset their passwords.
- **Delete**: Employs interactive Bootstrap modals for deletion confirmations. Prevents administrative self-deletion.

### 4. Profile Management & Picture Upload
- **Profile Customization**: Users can edit their own contact details and set custom passwords.
- **File Upload Security**:
  - Validates file MIME types (`image/jpeg`, `image/jpg`, `image/png`) and file extensions on the server.
  - Limits file uploads to a maximum of **2MB**.
  - Verifies binary data validity using PHP's `getimagesize()` function to block malicious script injections.
  - Automatically renames uploaded files to unique names (`avatar_...`) to prevent directory traversals.
  - Deletes old avatar files from disk automatically upon updating pictures.

### 5. Premium UI/UX Design
- Beautiful **Glassmorphism panels** overlaying fluid gradient backgrounds.
- Persistent **Dark Mode** switcher stored in the client browser's `localStorage` to prevent screen flash.
- Completely fluid, responsive layouts optimized for mobile, tablet, and desktop monitors.

---

## File Structure

```
user-management-system/
├── assets/
│   ├── css/
│   │   └── style.css            # Custom glassmorphic stylesheet & dark-mode rules
│   ├── js/
│   │   └── main.js             # Client-side validations, theme toggle & modal bindings
│   └── images/
│       └── default-avatar.svg  # Vector avatar SVG fallback file
├── config/
│   └── database.php            # DB connection singleton & security helper functions
├── includes/
│   ├── header.php              # Global head scripts, CDN links & session starting
│   ├── navbar.php              # Responsive top navigation & avatar dropdown menu
│   ├── sidebar.php             # Contextual menu displaying options by user role
│   └── footer.php              # Body closing tags & JS library bindings
├── auth/
│   ├── login.php               # Login page with remember-me & session security
│   ├── register.php            # Client/Server validated registration form
│   └── logout.php              # Complete session purge and cookie unlinking
├── uploads/                    # Profile picture uploads storage directory (gitkept)
├── dashboard.php               # System counters, activity list, & Chart.js graph
├── users.php                   # Paginated and searchable users management table
├── add-user.php                # Admin form to add users
├── edit-user.php               # Admin form to edit users
├── delete-user.php             # Strict delete handler form action
├── profile.php                 # Personal profile panel and picture uploader
├── index.php                   # Router script forwarding sessions
├── schema.sql                  # Database structure & default seed accounts
└── README.md                   # Installation & deployment documentation
```

---

## Setup & Installation Guide

Follow these steps to host and run this project locally on your XAMPP installation:

### Step 1: Clone or Copy Project Files
Place this repository folder directly inside your XAMPP `htdocs` directory:
`C:\xampp\htdocs\user-management-system`

### Step 2: Database Initialization
1. Launch **XAMPP Control Panel** and start both **Apache** and **MySQL** services.
2. Open your web browser and navigate to **phpMyAdmin**: `http://localhost/phpmyadmin/`
3. Click on the **SQL** tab.
4. Open the [schema.sql](file:///e:/Appex%20Plannet/schema.sql) file included in this project, copy the entire SQL text, paste it into the phpMyAdmin SQL command box, and click **Go**.
5. This automatically:
   - Creates the database `user_management_system`.
   - Creates the `users` and `activity_logs` tables.
   - Seeds default accounts:
     - **Administrator**: `admin@smartums.com` | Password: `admin123`
     - **Standard User**: `user@smartums.com` | Password: `user123`

### Step 3: Run the Application
1. In your browser, open the application URL:
   `http://localhost/user-management-system/`
2. You will be automatically redirected to the Login terminal.
3. Log in with either the Admin or User credentials listed above to explore the dashboards.

---

## Security Implementation Summary
- **SQL Injection**: Prevented using standard PHP parameter binding via `mysqli_prepare` and `mysqli_stmt_bind_param`.
- **XSS (Cross-Site Scripting)**: Neutralized using `escape_html()` (utilizing `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`) on all dynamic UI text.
- **CSRF (Cross-Site Request Forgery)**: Blocked using random hex token tokens generated on page loads, embedded as hidden form parameters, and verified server-side on posts.
- **Upload Guards**: Restricts files to less than 2MB, validates true image structures using `getimagesize()`, handles extension matches, and sanitizes filenames.
