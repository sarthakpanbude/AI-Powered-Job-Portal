# AI-Powered Job Portal 🚀

An advanced, interactive job placement and sourcing platform engineered for **Candidates (Students)**, **Employers (Recruiters)**, and **Administrators**. Powered by modern **PHP backend mechanics**, a beautiful **TailwindCSS utility layer**, dynamic **AJAX matching vector scans**, **Chart.js analytics panels**, and robust **security-hardened authorization structures**.

---

## 🛠️ Tech Stack & Core Libraries

- **Backend Logic & MVC:** PHP 8.2 (Secure PDO, object-oriented prepared interfaces)
- **Database Engine:** MySQL / MariaDB (relational structures with cascading constraints)
- **Frontend Styling:** Vanilla CSS + TailwindCSS utility layout layer (Premium HSL variables, fluid glassmorphism, responsive flex/grid layouts)
- **Interactive Layers:** Modern Javascript (ES6+, Fetch API, AJAX asynchronous handlers, dynamic UI calibration)
- **Visual Analytics:** Chart.js (interactive, gradient-filled vector data charts)
- **Typography & Assets:** Google Fonts (*Plus Jakarta Sans* & *Inter*), FontAwesome (Iconography vectors)

---

## 📂 Project Architecture

```directory
AI Powered Job Portal/
│
├── admin/                     # System administrator portal dashboard
│   └── dashboard.php          # Admin analytics dashboard
│
├── api/                       # API modules (e.g. integrations endpoints)
│
├── assets/                    # Static image assets, corporate logos
│
├── config/                    # Global configurations
│   └── db.php                 # Safe database connection setup using PDO
│
├── css/                       # Custom compiled custom styles and overrides
│
├── database/                  # Schema files and migrations
│   ├── schema.sql             # Relational schema & seeded default test records
│   ├── setup.php              # Automated database and table builder script
│   └── add_reset_columns.php  # Database migration for secure token fields
│
├── includes/                  # Common UI template inclusions and utilities
│   ├── header.php             # Core HTML structural tags & styling imports
│   ├── navbar.php             # Modern responsive navigation bar
│   ├── footer.php             # Standardized footer template
│   └── auth_check.php         # [NEW] DRY centralized role-based authorization check
│
├── recruiter/                 # Employer portals and applicant tracking
│   ├── dashboard.php          # Employer dashboard (analytics and activity tracker)
│   ├── jobs.php               # Vacancy management console
│   ├── post_job.php           # Rich vacancy publication tool
│   ├── applicants.php         # Evaluation pipeline interface
│   ├── interviews.php         # Active assessments and scheduler
│   └── profile.php            # Partner corporate metadata update settings
│
├── student/                   # Candidate portals
│   ├── dashboard.php          # Candidate dashboard (match scores, applications tracker)
│   ├── resume.php             # ATS optimizer and interactive resume builder
│   ├── applications.php       # Job applications pipeline list
│   ├── saved_jobs.php         # Saved vacancies bookmark panel
│   ├── ai_recommendations.php # Tailored high-match listings dashboard
│   ├── api_ai_match.php       # AJAX backend matching simulator endpoint
│   ├── apply_job.php          # Asynchronous LinkedIn-style Easy Apply form handler
│   ├── assessments.php        # Interactive evaluation quizzes
│   ├── career_roadmap.php     # Custom professional progression tracking
│   ├── interview_prep.php     # AI-simulated query practice and feedback cards
│   ├── referrals.php          # Referral generation and wallet ledger tracker
│   └── profile.php            # Candidate profile management console
│
├── index.php                  # Interactive landing index page and search portal
├── jobs.php                   # Portal job board list search & filters
├── companies.php              # corporate partner directories
├── login.php                  # Secure portal login interface (rate limited)
├── logout.php                 # Session invalidation script
├── register.php               # Account creator system
├── forgot-password.php        # Reset password request interface
└── reset-password.php         # Secure reset validation form (token-based check)
```

---

## ⚙️ Local Installation & Servicing

### Prerequisites
1. **XAMPP / WampServer** installed on your system.
2. **PHP 8.2+** and **MySQL (MariaDB)** running locally.

### Step-by-Step Configuration

1. **Clone or Position Directory:**
   Copy the project directory to your local web root:
   * **Windows XAMPP:** `C:\xampp\htdocs\AI Powered Job Portal`
   * **Linux XAMPP:** `/opt/lampp/htdocs/AI Powered Job Portal`

2. **Initialize Database and Seed Test Accounts:**
   Run the database setup script. You can execute this directly inside your terminal or browser:
   * **CLI approach:**
     ```bash
     C:\xampp\php\php.exe database/setup.php
     ```
   * **Browser approach:** Navigate to `http://localhost/AI Powered Job Portal/database/setup.php`

3. **Start Local Development Server:**
   You can run this application directly through XAMPP Apache or start a localized development server inside the root project directory:
   ```bash
   C:\xampp\php\php.exe -S localhost:8000
   ```

4. **Access the Portal:**
   Open your browser and navigate to:
   **[http://localhost:8000/](http://localhost:8000/)**

---

## 🔑 Demo Access Credentials
You can log in and instantly evaluate different portal workflows using the default accounts below (all default accounts use **`password`** as their password):

| Portal Role | Access Username | Default Password | Primary Workflows |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@admin.com` | `password` | Review dashboard overview, approve postings, evaluate student list. |
| **Recruiter** | `recruiter@company.com` | `password` | Post vacancies, manage pipeline statuses, schedule interviews, view analytics. |
| **Candidate** | `student@student.com` | `password` | Scan vacancies with AI matcher, check matches, build ATS resumes, earn referrals. |

---

## 🔒 Security Audit & Design Standards

We have implemented standard defense mechanisms and architecture rules to guarantee enterprise-grade data safety:

### 1. SQL Injection (SQLi) Prevention
All data queries interact via **PDO Prepared Statements** using bound parameters. Raw user inputs are strictly isolated from the query execution block, neutralizing SQLi attack vectors entirely.

### 2. Cross-Site Scripting (XSS) Defenses
All user-submitted attributes (names, job titles, companies) are sanitized during render blocks using `htmlspecialchars()`.

### 3. Password Integrity
Plaintext user passwords are never stored. Passwords are securely transformed into irreversible cryptography strings using the industry-proven **BCRYPT** hashing algorithm (`password_hash` & `password_verify`).

### 4. Brute-Force Rate Limiting
To defend against brute-force password guessing, a session lockout mechanism is configured in `login.php`. Users are restricted to **5 authentication failures**, after which their interface triggers a **60-second block**.

### 5. Secure Token-Based Password Resets
Our "Forgot Password" feature prevents token harvesting and reuse:
- Generates cryptographically secure random reset tokens (`bin2hex(random_bytes(24))`).
- Automatically configures token expiry (1 hour).
- After a token is consumed, `reset_token` and `reset_expiry` fields are safely updated to `NULL` to prevent reuse or replay attacks.

### 6. DRY Centralized Session Checks
Authentication session and role-based permissions are centralized inside `includes/auth_check.php` using the global `check_auth()` validator helper. This simplifies authorization and enforces clean DRY architecture principles.

---

*Engineered by Antigravity under TechnoHacks Solutions guidelines. All rights reserved.*
