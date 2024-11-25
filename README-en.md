# 🎲 Gacha System

A modern gacha system based on PHP and MySQL with complete user management, gacha mechanics, task system, and payment integration.

## ✨ Key Features

### User System
- Secure user registration and login
- Light/Dark theme support
- Multi-language support (English/Chinese)
- Personalized dashboard
- Auto-save user preferences

### Gacha System
- Multiple draw options (Single/5x/10x draws)
- Five rarity levels (Mythical/Legendary/Epic/Rare/Common)
- Dynamic gacha animations
- Detailed prize pool information
- Draw history records
- Inventory management

### Task System
- Daily tasks
- Monthly tasks
- Limited-time tasks
- Automatic reward distribution
- Task progress tracking
- Real-time reward notifications

### Payment System
- PayPal integration
- Multiple recharge options
- Bonus token system
- Secure transaction processing
- Transaction history

## 🎨 Interface Design

### Theme Support
- Adaptive light/dark mode
- Smooth theme transition animations
- Ergonomic color schemes
- Consistent visual style

### Responsive Design
- Full mobile support
- Adaptive to all screen sizes
- Touch-optimized interface
- Smooth animations

### User Experience
- Intuitive operation interface
- Clear visual feedback
- Rich animation effects
- Friendly error messages

## 🛠️ Technical Implementation

### Frontend Technologies
- HTML5 + CSS3
- JavaScript (ES6+)
- Bootstrap 5
- Animate.css
- Custom animation effects

### Backend Technologies
- PHP 7+
- MySQL database
- PDO database operations
- RESTful API design

### Security Features
- Password encryption
- SQL injection protection
- XSS attack prevention
- CSRF protection
- Secure session management

## 📦 System Requirements

- PHP 7.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- SSL certificate (for payment features)
- Modern browser support

## 🚀 Installation Steps

1. Clone the project:
   ```bash
   git clone https://github.com/your-username/gacha-system.git
   ```

2. Configure database:
   ```sql
   CREATE DATABASE gacha_system;
   USE gacha_system;
   SOURCE database.sql;
   ```

3. Configure environment variables:
   ```bash
   cp .env.example .env
   ```

4. Set permissions:
   ```bash
   chmod 755 -R public/
   chmod 644 -R config/
   ```

## 📝 Configuration Guide

### Database Configuration
```php
// config/database.php
return [
    'host' => 'localhost',
    'dbname' => 'gacha_system',
    'username' => 'your_username',
    'password' => 'your_password',
];
```

### PayPal Configuration
```env
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_client_secret
PAYPAL_MODE=sandbox # or 'live' for production
```

## 🔧 Development Guide

### Directory Structure
```plaintext
gacha/
├── config/        # Configuration files
├── models/        # Data models
├── public/        # Public access directory
│   ├── admin/     # Admin backend
│   ├── styles/    # CSS files
│   └── locale/    # Language files
├── screenshots/   # Screenshots
└── README.md      # Documentation
```

### Development Standards
- Follow PSR-4 autoloading standard
- Use PDO prepared statements
- Unified error handling
- Complete logging system
- Code commenting standards

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Contributing

1. Fork the project
2. Create your feature branch
3. Commit your changes
4. Submit a Pull Request

## 🙏 Acknowledgments

- [Bootstrap](https://getbootstrap.com/)
- [PayPal API](https://developer.paypal.com/)
- [Animate.css](https://animate.style/)

## 📱 Contact

- **Author**: Cham Yeong Pin
- **Email**: yeongpin1999@gmail.com
- **Website**: https://www.pinstudios.rr.nu

## 🔄 Changelog

### v1.0.0 (2024-11-25)
- Initial release
- Basic functionality implementation
- Payment system integration 
