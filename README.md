# Auction Nova - Online Auction Platform

A comprehensive online auction platform built with PHP, MySQL, Bootstrap, and jQuery. Features real-time bidding, user management, seller tools, and admin controls.

## 🚀 Features

### 🧑‍💻 User Features
- **Authentication System**
  - User registration with age verification (18+)
  - Secure login/logout with password reset
  - Profile management with image upload
  
- **Auction Browsing**
  - Category-wise auction listings
  - Advanced search and filtering
  - Real-time countdown timers
  - Auction details with image gallery
  
- **Bidding System**
  - Real-time bid placement with AJAX
  - Minimum increment validation
  - Bid history tracking
  - Winning bid notifications
  
- **Watchlist & Notifications**
  - Add auctions to watchlist
  - Email notifications for bid updates
  - Dashboard with activity overview
  
### 🛍️ Seller Features
- **Auction Management**
  - Create new auctions with multiple images
  - Set starting price, duration, and increment
  - View and manage all auctions
  - Track bids and auction performance
  
- **Analytics**
  - View count tracking
  - Bid statistics
  - Revenue insights

### 🛠️ Admin Features
- **User Management**
  - View all registered users
  - Activate/deactivate accounts
  - User activity monitoring
  
- **Auction Control**
  - Approve/reject new auctions
  - Featured auction management
  - Category management
  
- **Analytics & Reports**
  - Platform statistics
  - Revenue reports
  - User activity logs

## 🏗️ Technical Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, Bootstrap 5.3
- **JavaScript**: jQuery 3.7, AJAX
- **Icons**: Font Awesome 6.4
- **Server**: Apache (XAMPP recommended)

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server
- GD extension for image processing
- PDO MySQL extension

## 🚀 Installation

1. **Clone or Download**
   ```bash
   git clone [repository-url]
   # OR download and extract to your web directory
   ```

2. **Setup Database**
   - Start XAMPP (Apache + MySQL)
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the database schema from `database/schema.sql`
   - Or run the SQL commands manually

3. **Configure Settings**
   - Update database credentials in `config/database.php` if needed
   - Modify site settings in `config/config.php`

4. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/profiles/
   ```

5. **Access the Site**
   - Open http://localhost/Auction Nova/
   - Default admin login: admin@auctionnova.com / admin123

## 📁 Project Structure

```
Auction Nova/
├── api/                    # AJAX API endpoints
├── assets/                 # CSS, JS, images
├── auth/                   # Authentication pages
├── config/                 # Configuration files
├── database/               # Database schema
├── includes/               # Common includes
├── seller/                 # Seller management
├── user/                   # User dashboard
├── uploads/                # File uploads
├── admin/                  # Admin panel (to be implemented)
└── index.php              # Homepage
```

## 🔧 Configuration

### Database Settings
Edit `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'auction_nova';
private $username = 'root';
private $password = '';
```

### Site Settings
Edit `config/config.php`:
```php
define('SITE_NAME', 'Auction Nova');
define('SITE_URL', 'http://localhost/Auction Nova/');
define('MIN_BID_INCREMENT', 50);
define('MAX_AUCTION_DURATION', 7);
```

## 🎨 Customization

### Styling
- Main styles: `assets/css/style.css`
- Bootstrap variables can be customized
- Color scheme defined in CSS variables

### Functionality
- Add new auction categories in database
- Modify bid increment rules in config
- Customize email templates (future feature)

## 🔒 Security Features

- CSRF token protection
- SQL injection prevention with PDO
- Password hashing with PHP's password_hash()
- Input sanitization and validation
- File upload security checks
- Session management

## 📱 Responsive Design

- Mobile-first approach
- Bootstrap 5.3 grid system
- Touch-friendly interface
- Optimized for all screen sizes

## 🚀 Future Enhancements

- [ ] Email notification system
- [ ] Payment gateway integration
- [ ] Live chat between buyers/sellers
- [ ] Mobile app (React Native)
- [ ] Advanced analytics dashboard
- [ ] Multi-language support
- [ ] Social media integration
- [ ] Automated auction ending
- [ ] Shipping management
- [ ] Rating and review system

## 🐛 Known Issues

- Email notifications not implemented yet
- Some placeholder images need replacement
- Admin panel partially complete
- Payment system not integrated

## 📞 Support

For support and questions:
- Create an issue in the repository
- Contact: admin@auctionnova.com

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 🙏 Acknowledgments

- Bootstrap team for the excellent framework
- Font Awesome for the icons
- jQuery team for the JavaScript library
- PHP community for excellent documentation

---

**Made with ❤️ in India**

*Auction Nova - Your trusted online auction platform*
