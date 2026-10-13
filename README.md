# 🤝 Helping Hands — NGO Donor & Donation Management System

A web-based application designed to help NGOs organize donor information, manage donations, track fund allocations, and simplify administrative activities through a centralized database system.

## 📌 Overview

Non-governmental organizations (NGOs) often manage donor details, donation records, and fund allocation information using separate spreadsheets or manual records. This can make it difficult to maintain consistent data and monitor contributions efficiently.

**Helping Hands** aims to simplify these activities by providing a centralized web application where donor and donation information can be organized and managed more efficiently.

The project combines a user-friendly website with an administrative panel and a MySQL database to support better record management and transparency.

## ✨ Key Features

### 🌐 Public Website
- **Home Page:** Introduction to the organization and its mission.
- **About Us:** Information about the organization.
- **Campaigns:** Display of fundraising campaigns.
- **Donation Page:** Interface for donation-related activities.
- **Contact Page:** Contact form for inquiries.

### 🛠️ Admin Panel
- **Dashboard:** Centralized administrative overview.
- **Donor Management:** Manage donor records.
- **Donation Management:** Maintain donation records.
- **Campaign Management:** Manage fundraising campaigns.
- **Fund Allocation:** Organize allocation records.
- **Reports:** Access donation and administrative reports.
- **Messages:** Review submitted inquiries.

### 🗄️ Database Management
- MySQL database integration.
- Relational database schema.
- Structured storage of application records.
- Database constraints and relationships defined in the schema.

## 🧰 Tech Stack

| Technology | Purpose |
|---|---|
| HTML5 | Web page structure |
| CSS3 | Styling and responsive layout |
| JavaScript | Client-side interactions |
| PHP | Server-side logic |
| MySQL | Database management |
| XAMPP | Local development environment |
| Git & GitHub | Version control and project hosting |

## 📂 Project Structure

```text
helping-hands/
│
├── admin/                 # Administrative panel
│   ├── dashboard.php
│   ├── donors.php
│   ├── donations.php
│   ├── campaigns.php
│   ├── allocations.php
│   └── reports.php
│
├── assets/
│   ├── css/               # Stylesheets
│   └── ...                # Other frontend assets
│
├── config/                # Configuration and session files
│
├── database/
│   └── schema.sql         # Database schema
│
├── includes/              # Shared PHP components
│
├── index.php              # Home page
├── about.php              # About page
├── campaigns.php          # Campaign page
├── donate.php              # Donation page
├── contact.php             # Contact page
└── .gitignore
```

## ⚙️ Installation & Setup

Follow these steps to run Helping Hands locally.

### Prerequisites

Install the following software:

- [XAMPP](https://www.apachefriends.org/)
- A modern web browser
- [Git](https://git-scm.com/) (optional, for cloning the repository)

### Step 1: Clone the Repository

Open a terminal and run:

```bash
git clone https://github.com/alinashaikh987/helping-hands.git
```

Alternatively, download the repository as a ZIP file from GitHub.

### Step 2: Place the Project in XAMPP

Move or extract the project into the XAMPP `htdocs` directory.

Example Windows path:

```text
C:\xampp\htdocs\helping-hands
```

### Step 3: Start XAMPP

Open the XAMPP Control Panel and start:

- Apache
- MySQL

### Step 4: Create the Database

1. Open `http://localhost/phpmyadmin/`.
2. Create a MySQL database for the project.
3. Import the `database/schema.sql` file using phpMyAdmin.
4. Confirm that the required tables have been created.

Use the database name expected by your application's database connection configuration.

### Step 5: Configure the Database Connection

Open the database configuration file used by the application and configure the local MySQL connection details.

Update the host, database name, username, and password as required by your local environment.

**Security note:** Do not commit real database passwords, API keys, or other private credentials to a public repository.

### Step 6: Run the Application

Open the following URL in your browser:

```text
http://localhost/helping-hands/
```

The application should load through your local Apache server.

## 🔐 Security Considerations

- Keep database credentials out of public source code.
- Validate and sanitize user input on the server.
- Use secure password handling for authentication.
- Apply appropriate authorization checks to administrative pages.
- Avoid storing real donor information in public sample data.

## 🎯 Project Objectives

- Centralize donor and donation records.
- Simplify routine NGO administrative activities.
- Organize campaign and fund allocation information.
- Practice PHP and MySQL integration.
- Apply relational database design concepts in a practical web application.

## 🚀 Future Enhancements

Potential improvements include:

- Interactive dashboard analytics and charts.
- Advanced search and filtering.
- Exportable PDF and Excel reports.
- Email notifications and donation receipts.
- Enhanced role-based access control.
- Online payment gateway integration.

*These are proposed enhancements and are not necessarily implemented in the current version.*

## 👩‍💻 Author

**Alina Shaikh**

- GitHub: [@alinashaikh987](https://github.com/alinashaikh987)
- LinkedIn: [Alina Shaikh](https://www.linkedin.com/in/alinashaikh-2169a7313/)
- Portfolio: [Visit My Portfolio](https://alina-shaikh-portfolio.netlify.app/)

## 📄 License

This project was developed for educational purposes. A formal open-source license can be added if you choose to distribute the project under specific terms.

---

⭐ If you find this project useful, consider starring the repository.

**Built with 💚 to support better NGO donation management.**
