# NESTORA – Online Furniture Information Management System

**Furniture for a Considered Life**

NESTORA is a web-based Furniture Information Management System developed using Core PHP and MySQL. It allows administrators to manage furniture, customers, and orders while enabling customers to browse furniture, manage shopping carts, and place orders online.

**Live Website:** https://nestora.infinityfreeapp.com

**GitHub Repository:** https://github.com/primakezadev/Furniture_Management_System

---

## 1. Project Overview

This project was developed by Group VI for the Backend Development Using PHP module (ITLDP601).

The system has two main user roles:

- **Administrator:** Manages furniture, customers, and customer orders.
- **Customer:** Registers, logs in, browses furniture, adds products to a cart, and places orders.

## 2. Main Features

### Administrator Features
- Secure administrator login and logout.
- Admin dashboard with system summaries.
- Add new furniture with details, prices, quantities, and images.
- View, update, and delete furniture records.
- View and manage customer records.
- View and manage customer orders.
- Update order statuses.
- Change administrator password.

### Customer Features
- Customer registration and login.
- Secure logout.
- Browse available furniture.
- Browse furniture by room category, including Dining and Living.
- Search and view furniture details and images.
- Add furniture to the shopping cart.
- View and manage shopping cart items.
- Place furniture orders.
- Password recovery through email.

### General Features
- Role-based access for administrators and customers.
- Password hashing and verification.
- Database operations using PDO and prepared statements.
- Pagination for record listings.
- Furniture image handling.
- Email functionality using PHPMailer.

## 3. Technologies Used

| Technology | Purpose |
|---|---|
| PHP 8.2 | Backend development |
| MySQL | Database management |
| HTML5 | Web page structure |
| CSS3 | Styling and layout |
| JavaScript | Client-side functionality |
| Bootstrap | Responsive interface components |
| PDO | Database connectivity |
| Composer | PHP dependency management |
| PHPMailer | Email functionality |
| XAMPP | Local development environment |
| Git and GitHub | Version control and collaboration |
| InfinityFree | Online hosting |

## 4. Project Structure

```text
Furniture_Management_System/
│
├── admin/              # Administrator pages
├── assets/
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── images/         # Website and furniture images
│
├── authentication/     # Authentication and password recovery
├── cart/               # Shopping cart functionality
├── config/             # Database and application configuration
├── customer/           # Customer pages
├── database/           # Database SQL scripts
├── furniture/          # Furniture management
├── includes/           # Shared components
├── orders/             # Order management
├── uploads/            # Uploaded files
├── vendor/             # Composer dependencies
│
├── composer.json
├── composer.lock
├── register.php
├── login.php
└── README.md
```

## 5. Requirements

To run the project locally, you need:

- XAMPP with Apache, MySQL, and PHP.
- Composer.
- Git.
- A modern web browser.

## 6. Installation and Setup

### Step 1: Clone the repository

Open PowerShell or Command Prompt:

```bash
git clone https://github.com/primakezadev/Furniture_Management_System.git
```

### Step 2: Place the project inside XAMPP

Move or clone the project into your XAMPP `htdocs` directory.

Example local project path:

```text
C:\xamp_new\htdocs\nestora\Furniture_Management_System
```

### Step 3: Start Apache and MySQL

1. Open XAMPP Control Panel.
2. Start Apache.
3. Start MySQL.

### Step 4: Create the database

1. Open phpMyAdmin:

   `http://localhost/phpmyadmin`

2. Create a database named:

```text
furniture_management
```

3. Open the project's `database/` folder.
4. Import the provided SQL database script into the database.

### Step 5: Configure database connection

Open:

```text
config/database.php
```

Configure the connection using your local MySQL details.

Example:

```php
$host = "localhost";
$dbname = "furniture_management";
$username = "root";
$password = "";
```

Change these values if your local MySQL configuration is different.

**Important:** Never publish production database passwords or email SMTP credentials in a public repository.

### Step 6: Install dependencies

Open a terminal in the project root and run:

```bash
composer install
```

This installs the dependencies listed in `composer.lock`, including PHPMailer.

### Step 7: Configure email functionality

If using email features such as password recovery, configure the SMTP settings in the relevant project configuration.

Do not upload private email credentials to GitHub.

### Step 8: Run the application

For the example local project path above, open:

```text
http://localhost/nestora/Furniture_Management_System/
```

Use the appropriate local URL if you install the project in a different folder.


## Account Access

To access the system:

- Register a new customer account through the registration page.
- Admin access is restricted to authorized administrators.

  
- For security reasons, login credentials are not published in this repository.

## 8. Security

The project includes security practices such as:

- Password hashing using PHP `password_hash()`.
- Password verification using `password_verify()`.
- Prepared statements for database queries.
- Session-based authentication.
- CSRF token validation on protected forms where implemented.
- Input validation.
- Protection of administrator and customer pages through authentication checks.

## 9. Deployment

NESTORA is deployed online using InfinityFree hosting.

**Live Website:** https://nestora.infinityfreeapp.com

The deployed application uses a MySQL database hosted by the hosting provider.

The live website can be accessed through a web browser.

## 10. Contributors

**Group VI – IT Option, Level Six, Year Two**

Add the full names of all group members here:

1. Keza Prima
2. Kwizera Elvis
3. Imanishimwe julienne

## 11. Academic Information

- **Institution:** Rwanda Polytechnic
- **Department:** Information and Communication Technology (ICT)
- **Module:** Backend Development Using PHP
- **Module Code:** ITLDP601
- **Project Title:** Online Furniture Information Management System
- **Group:** VI
- **Academic Level:** Level Six, Year Two

---

**NESTORA – Furniture for a Considered Life.**
