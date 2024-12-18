
# Neurolost Project

## Overview
Neurolost is a dynamic web application designed for managing and evaluating professionals, tests, and user data. The platform integrates PHP, SQL, CSS, and JavaScript to deliver a seamless and interactive experience. This repository includes all the necessary components to deploy and run the application.

## Features
- **User Authentication**: Includes login, registration, and logout functionality.
- **Professional Evaluation**: Enables managing professions, editing expert information, and evaluating suitability.
- **Test Management**: Includes test selection, processing, and viewing results.
- **Profile Management**: Users can update profiles and track progress.
- **Responsive Design**: CSS and JavaScript ensure a smooth user interface across devices.

## File Structure
```
neurolost-main/
├── Puppeteer/             # Puppeteer scripts for automation
├── SQL/                   # SQL files for database management
├── css/                   # Stylesheets for the application
├── js/                    # JavaScript files for interactivity
├── tests/                 # Test files and scripts
├── account.php            # Account management
├── changes.php            # Tracks changes
├── correction.php         # Corrects data entries
├── create-tables.sql      # SQL script to set up database tables
├── edit_experts.php       # Edit expert details
├── edit_professions.php   # Edit profession details
├── evaluate_professions.php # Profession evaluation logic
├── experts.php            # Manage experts
├── home.php               # Dashboard
├── index.php              # Main entry point
├── login.php              # User login logic
├── logout.php             # User logout logic
├── process_tests.php      # Test processing logic
├── professions.php        # Manage professions
├── progress.php           # Track user progress
├── rated_professions.php  # Rated professions display
├── register-respondent.php # Respondent registration
├── register.php           # User registration
├── select_tests.php       # Test selection
├── suitability.php        # Evaluate suitability
├── update_profile.php     # Profile update functionality
├── view_test_results.php  # View test results
```

## Installation

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/your-username/neurolost.git
   cd neurolost-main
   ```

2. **Set Up the Database**:
   - Import `create-tables.sql` into your database management system.
   - Configure database credentials in a dedicated configuration file (e.g., `config.php`).

3. **Configure the Environment**:
   - Ensure PHP, a web server (e.g., Apache), and a database (e.g., MySQL) are installed.
   - Place the project in your web server's root directory.

4. **Start the Server**:
   - Launch your web server and navigate to `http://localhost/neurolost-main/`.

## Usage
- **Home Page**: Navigate to `index.php` to access the dashboard.
- **User Registration**: Use `register.php` for account creation.
- **Test Management**: Manage and evaluate tests via `process_tests.php` and `view_test_results.php`.
- **Profile Updates**: Update user information in `update_profile.php`.

## Technologies Used
- **Backend**: PHP, SQL
- **Frontend**: HTML, CSS, JavaScript
- **Database**: MySQL
- **Automation**: Puppeteer

