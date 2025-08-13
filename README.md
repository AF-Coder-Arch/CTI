# CTI Tracker - PHP Web Application

A simple PHP web application for tracking Cyber Threat Intelligence (CTI) findings with MySQL database backend.

## Features

- **Dashboard**: Overview of open/closed findings with recent activity
- **Open Findings**: View and manage active findings with search functionality
- **Closed Findings**: Archive of resolved findings with search capability
- **Color Coding**: Visual indicators based on finding age:
  - 🟢 Green: 0-7 days
  - 🟡 Yellow: 8-30 days
  - 🔴 Red: 30+ days
- **Bootstrap UI**: Modern, responsive design
- **Search Functionality**: Full-text search across titles and descriptions

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

## Installation

### Method 1: Automated Setup (Recommended)

1. **Create Database**:
   ```bash
   mysql -u root -p -e "CREATE DATABASE cti_tracker;"
   ```

2. **Configure Database**:
   Edit `config/database.php` with your MySQL credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'cti_tracker');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

3. **Run Setup Script**:
   - Navigate to `http://your-domain/setup_database.php` in your browser
   - Or run via command line: `php setup_database.php`
   - **Important**: Delete `setup_database.php` after successful setup

4. **Deploy Files**:
   - Copy all files to your web server document root
   - Ensure proper permissions for web server access

5. **Access Application**:
   - Navigate to your web server URL
   - Example: `http://localhost/CTI/`

### Method 2: Manual Setup

1. **Database Setup**:
   ```bash
   mysql -u root -p < sql/setup.sql
   ```

2. Follow steps 2, 4, and 5 from Method 1

## File Structure

```
CTI/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   ├── functions.php         # Helper functions
│   ├── header.php           # Common header template
│   └── footer.php           # Common footer template
├── sql/
│   └── setup.sql            # Database schema and sample data
├── index.php               # Dashboard
├── open_findings.php       # Open findings page
├── closed_findings.php     # Closed findings page
├── add_finding.php         # Add new finding form
├── setup_database.php      # Automated database setup script
└── README.md              # This file
```

## Usage

### Adding a Finding
1. Click "Add Finding" in the navigation
2. Enter title and description
3. Finding will be created with "Open" status

### Managing Findings
- **Close Finding**: Click "Close Finding" button on open findings
- **Reopen Finding**: Click "Reopen Finding" button on closed findings
- **Search**: Use search box to filter findings by title or description

### Color Coding
The system automatically assigns colors based on how long a finding has been open:
- Newly created findings appear green
- Findings open for 8-30 days appear yellow
- Findings open for more than 30 days appear red

## Database Schema

```sql
CREATE TABLE findings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_closed DATETIME NULL,
    status ENUM('open', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Security Notes

- Input validation is implemented for form submissions
- SQL injection protection using prepared statements
- XSS protection through proper HTML escaping
- Consider implementing authentication for production use

## Customization

- **Colors**: Modify color thresholds in `includes/functions.php`
- **Styling**: Customize CSS in `includes/header.php`
- **Fields**: Add additional fields by modifying database schema and forms

## Troubleshooting

1. **Database Connection Issues**:
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists and user has proper permissions

2. **Permission Errors**:
   - Ensure web server has read access to all files
   - Check file permissions (typically 644 for files, 755 for directories)

3. **PHP Errors**:
   - Check PHP error logs
   - Ensure required PHP extensions are installed (PDO, PDO_MySQL)

## License

This project is open source and available under the MIT License.