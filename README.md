# 📦 Dubai Cargo System

**Intelligent Cargo Management & Logistics Platform**

> A comprehensive web-based solution for managing cargo shipments, tracking deliveries, and optimizing logistics operations in the Dubai region with real-time monitoring and reporting capabilities.

---

## 🎯 Project Overview

Dubai Cargo System is a modern logistics management platform designed to streamline cargo handling, shipment tracking, and delivery operations. Built with PHP and modern web technologies, it provides an integrated solution for cargo companies to manage their entire operational workflow.

### Core Objectives
- Centralize cargo shipment management
- Real-time shipment tracking and monitoring
- Optimize delivery routes and scheduling
- Automate cargo documentation and billing
- Enhance customer communication and transparency
- Generate detailed logistics analytics and reports

---

## ✨ Key Features

### 📦 Cargo Management
- Create and manage shipments
- Track cargo from origin to destination
- Assign cargo to delivery vehicles
- Monitor cargo status in real-time
- Generate shipping labels and documents
- Support for multiple cargo types

### 🚚 Delivery Management
- Route optimization and planning
- Driver assignment and scheduling
- Real-time GPS tracking
- Delivery confirmations
- Proof of delivery (POD)
- Multiple drop-off locations

### 📋 Shipment Tracking
- Live tracking dashboard
- Shipment history and archive
- Customer tracking portal
- Automated status notifications
- Delay alerts and escalations
- Detailed shipment timeline

### 💳 Billing & Invoicing
- Automated bill generation
- Multiple payment methods
- Invoice management
- Rate calculation
- Service charge tracking
- Financial reporting

### 📊 Analytics & Reporting
- Delivery performance metrics
- Revenue analysis
- Fleet utilization reports
- Cost optimization insights
- Custom report generation
- Data export (CSV, PDF)

### 👥 User Management
- Role-based access control (RBAC)
- Multi-user support
- Admin dashboard
- Driver management
- Customer accounts
- Activity logging

---

## 🛠️ Technology Stack

### Backend
- **Language:** PHP 7.4+
- **Framework:** Custom MVC / Laravel
- **Database:** MySQL
- **Server:** Apache/Nginx
- **API:** RESTful Architecture

### Frontend
- **HTML5** - Semantic markup
- **CSS3** - Responsive styling
- **JavaScript** - Dynamic interactions
- **Bootstrap** - UI framework
- **jQuery** - DOM manipulation

### Infrastructure
- **Database:** MySQL 5.7+
- **Version Control:** Git
- **Authentication:** Session-based / JWT
- **File Storage:** Local filesystem
- **Email:** SMTP integration

---

## 📋 System Architecture

```
Dubai Cargo System
│
├── Frontend Layer
│   ├── Admin Dashboard
│   ├── Driver Portal
│   ├── Customer Tracking
│   └── Reporting Dashboard
│
├── API Layer
│   ├── Authentication
│   ├── Shipment Management
│   ├── Tracking Service
│   ├── Billing Engine
│   └── Notification Service
│
├── Business Logic
│   ├── Route Optimization
│   ├── Cargo Processing
│   ├── Payment Processing
│   ├── Document Generation
│   └── Report Engine
│
└── Data Layer
    ├── MySQL Database
    ├── File Storage
    ├── Cache Layer
    └── Log Storage
```

---

## 🚀 Getting Started

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Git
- Node.js (optional, for frontend build)

### Installation

```bash
# Clone the repository
git clone https://github.com/GidahThomas/dubai-cargo-system.git
cd dubai-cargo-system

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Configure database in .env
# Update DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Run database migrations
php artisan migrate  # or custom migration script

# Install frontend dependencies (if using npm)
npm install

# Start development server
php artisan serve
```

### Environment Configuration

Update your `.env` file with your database and API credentials:

```env
APP_NAME=Dubai_Cargo_System
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cargo_system
DB_USERNAME=root
DB_PASSWORD=password

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password

# Third-party Services (optional)
GPS_API_KEY=your_api_key
NOTIFICATION_SERVICE=twilio  # or custom
```

---

## 📚 API Endpoints

### Shipment Management
```
GET    /api/shipments              - List all shipments
POST   /api/shipments              - Create new shipment
GET    /api/shipments/{id}         - Get shipment details
PUT    /api/shipments/{id}         - Update shipment
DELETE /api/shipments/{id}         - Cancel shipment
GET    /api/shipments/search       - Search shipments
```

### Tracking
```
GET    /api/track/{tracking_id}    - Track shipment
GET    /api/shipments/{id}/history - Shipment history
GET    /api/shipments/{id}/status  - Current status
```

### Delivery & Routes
```
GET    /api/routes                 - List routes
POST   /api/routes                 - Create route
GET    /api/deliveries             - List deliveries
PUT    /api/deliveries/{id}        - Update delivery status
POST   /api/deliveries/{id}/proof  - Upload proof of delivery
```

### Billing
```
GET    /api/invoices               - List invoices
POST   /api/invoices               - Generate invoice
GET    /api/invoices/{id}          - Get invoice details
POST   /api/payments               - Record payment
GET    /api/payments               - Payment history
```

### Users & Access
```
POST   /api/auth/login             - User login
POST   /api/auth/logout            - User logout
GET    /api/users                  - List users (admin)
POST   /api/users                  - Create user (admin)
GET    /api/profile                - Current user profile
PUT    /api/profile                - Update profile
```

---

## 🗄️ Database Schema

### Primary Tables
- `shipments` - Shipment records with origin, destination, status
- `shipment_items` - Items within each shipment
- `deliveries` - Delivery assignments and tracking
- `routes` - Delivery routes and schedules
- `drivers` - Driver profiles and information
- `vehicles` - Fleet management
- `invoices` - Billing and payment records
- `users` - User accounts and authentication
- `locations` - Dubai area/warehouse locations
- `activity_log` - System activity tracking

---

## 🔐 Security Features

- ✅ User authentication & authorization
- ✅ Password hashing (bcrypt)
- ✅ CSRF token protection
- ✅ SQL injection prevention
- ✅ Input validation and sanitization
- ✅ XSS protection
- ✅ Rate limiting on API endpoints
- ✅ HTTPS/SSL support
- ✅ Audit logging
- ✅ Role-based access control

---

## 🧪 Testing

```bash
# Run tests
php artisan test

# Run specific test suite
php artisan test tests/Feature/ShipmentTest.php

# Generate code coverage
php artisan test --coverage
```

---

## 🌐 Deployment

### Production Setup

```bash
# Install production dependencies
composer install --no-dev

# Optimize application
php artisan optimize
php artisan config:cache
php artisan route:cache

# Run migrations
php artisan migrate --force

# Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Hosting Options
- AWS EC2 + RDS
- DigitalOcean
- Heroku
- Custom VPS
- Google Cloud Platform

---

## 📱 User Roles

### Admin
- Full system access
- User management
- Configuration settings
- System analytics
- Report generation

### Manager
- View all shipments and deliveries
- Assign drivers to routes
- Monitor performance
- Generate reports
- Manage customer accounts

### Driver
- View assigned deliveries
- Update delivery status
- Upload proof of delivery
- View earnings
- Track route

### Accountant
- View invoices and payments
- Process refunds
- Financial reporting
- Rate management

### Customer
- Track shipments
- View invoice history
- Download documents
- Communicate with support

---

## 📈 Performance Optimization

- Database query optimization with indexing
- Caching strategies (Redis/Memcached)
- API response pagination
- Image optimization
- Lazy loading for tracking maps
- Database connection pooling
- CDN for static assets

---

## 🐛 Known Issues & Roadmap

### Current Version: 1.0.0
- ✅ Basic shipment management
- ✅ Delivery tracking
- ✅ Billing system
- ✅ User management
- ✅ Reporting

### Upcoming Features (v1.1.0)
- 📌 Mobile app (iOS/Android)
- 📌 Advanced route optimization (AI/ML)
- 📌 Real-time GPS tracking
- 📌 Automated notifications (SMS/Email)
- 📌 Multi-language support
- 📌 Integration with payment gateways
- 📌 Customs documentation automation

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit changes (`git commit -m 'Add new feature'`)
4. Push to branch (`git push origin feature/new-feature`)
5. Submit a Pull Request

### Code Standards
- Follow PSR-12 PHP standards
- Write meaningful commit messages
- Include tests for new features
- Update documentation

---

## 📞 Support & Contact

**Developer:** Gida Thomas  
**Email:** gidamasaudathomas@gmail.com  
**GitHub:** [@GidahThomas](https://github.com/GidahThomas)  
**University:** University of Dodoma

---

## 📄 License

This project is licensed under the MIT License.

---

## 🙏 Acknowledgments

- Built as part of software engineering coursework
- Inspired by real-world logistics challenges
- Thanks to all contributors and testers

---

**Last Updated:** September 2026 | **Status:** 🟢 Active | **Version:** 1.0.0
