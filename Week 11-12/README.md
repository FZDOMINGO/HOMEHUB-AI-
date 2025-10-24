# 🏠 HomeHub - Property Management System

A comprehensive web application for property management, featuring tenant-landlord matching, booking systems, and AI-powered recommendations.

## 🚀 Features

- **Property Management**: List, browse, and manage properties
- **User Management**: Separate dashboards for tenants, landlords, and admins
- **Booking System**: Property reservations and visit scheduling
- **AI Recommendations**: Machine learning-powered property matching
- **Real-time Notifications**: Live updates for bookings and messages
- **Admin Dashboard**: Comprehensive analytics and user management

## 🛠️ Tech Stack

- **Backend**: PHP 8.2, MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **AI Service**: Python Flask with scikit-learn
- **Database**: MySQL/MariaDB
- **Deployment**: Railway.app ready

## 📁 Project Structure

```
HomeHub/
├── admin/           # Admin dashboard and management
├── tenant/          # Tenant interface and features
├── landlord/        # Landlord property management
├── guest/           # Public pages and property browsing
├── ai/              # Python AI service for recommendations
├── api/             # REST API endpoints
├── config/          # Database and app configuration
├── includes/        # Shared PHP components
├── sql/             # Database schema and migrations
├── assets/          # CSS, JS, and media files
└── uploads/         # User uploaded files
```

## 🎯 Quick Start

### Local Development (XAMPP)

1. **Install XAMPP** with PHP 8.2+ and MySQL
2. **Clone the repository**:
   ```bash
   git clone https://github.com/yourusername/homehub.git
   cd homehub
   ```
3. **Setup Database**:
   - Import `sql/homehub.sql` into MySQL
   - Update `config/db_connect.php` with your credentials
4. **Start Services**:
   - Start Apache and MySQL in XAMPP
   - Access: `http://localhost/homehub`

### AI Service Setup (Optional)

1. **Install Python dependencies**:
   ```bash
   cd ai
   pip install -r requirements.txt
   ```
2. **Start AI service**:
   ```bash
   python api_server.py
   ```

## 🌐 Railway.app Deployment

This project is configured for one-click deployment on Railway.app:

[![Deploy on Railway](https://railway.app/button.svg)](https://railway.app/new/template)

### Manual Deployment Steps:

1. **Push to GitHub**:
   ```bash
   git add .
   git commit -m "Initial deployment"
   git push origin main
   ```

2. **Create Railway Project**:
   - Connect your GitHub repository
   - Railway auto-detects PHP configuration

3. **Add MySQL Database**:
   - Add MySQL service in Railway dashboard
   - Import your database schema

4. **Environment Variables**:
   ```
   DATABASE_URL=mysql://user:pass@host:port/db
   APP_ENV=production
   ```

## 📊 Default Admin Account

- **Username**: admin
- **Password**: admin123
- **Email**: admin@homehub.local

*Change these credentials after first login!*

## 🔧 Configuration

### Database Configuration
Update `config/db_connect.php` for local development or use environment variables for production.

### AI Features
Configure AI service in `ai/config.py` and ensure Python dependencies are installed.

## 📝 API Documentation

### Authentication Endpoints
- `POST /api/login.php` - User authentication
- `POST /api/logout.php` - User logout
- `GET /api/check_session.php` - Session validation

### Property Endpoints
- `GET /api/get-property-details.php` - Property information
- `GET /api/get-available-properties.php` - Available properties list

### Booking Endpoints
- `GET /api/get-booking-status.php` - Booking status
- `GET /api/get-landlord-reservations.php` - Landlord reservations

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Check MySQL service is running
   - Verify credentials in `config/db_connect.php`

2. **AI Service Not Working**:
   - Ensure Python dependencies are installed
   - Check `ai/api_server.py` is running on port 5000

3. **File Upload Issues**:
   - Check `uploads/` directory permissions
   - Verify PHP upload settings in `.user.ini`

### Support

For support and questions, please open an issue in the GitHub repository.

---

**Made with ❤️ for property management**