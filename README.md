# Bedrock Composer Store

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org)
[![License](https://img.shields.io/badge/License-GPL%20v3-red.svg)](https://www.gnu.org/licenses/gpl-3.0)

A WordPress plugin that seamlessly integrates Composer package management into Bedrock's plugin installation workflow. Add "Add to Composer" buttons directly to the WordPress plugin install screen.

## 🚀 Features

- **One-Click Integration**: Add plugins to your `composer.json` with a single click
- **Smart Detection**: Automatically detects already installed Composer packages
- **Version Management**: Fetches and adds the latest stable version automatically
- **Bedrock Optimized**: Designed specifically for Roots/Bedrock WordPress stack
- **AJAX Powered**: Seamless user experience without page reloads

## 📋 Requirements

- **PHP**: 8.1 or higher
- **WordPress**: 6.0 or higher
- **Bedrock**: Roots/Bedrock WordPress boilerplate
- **Composer**: Must be configured with wpackagist repository

## 🛠 Installation

### Via Composer (Recommended)

```bash
composer require callmeleon167/bedrock-composer-store
```

### Manual Installation

1. Download the latest release
2. Extract to your `/web/app/plugins/` directory
3. Activate the plugin in WordPress admin

### Bedrock Configuration

Ensure your `composer.json` includes the wpackagist repository:

```json
{
  "repositories": [
    {
      "type": "composer",
      "url": "https://wpackagist.org"
    }
  ]
}
```

## 📖 Usage

1. **Navigate** to `Plugins → Add New` in your WordPress admin
2. **Search** for any plugin you want to install
3. **Click** the blue "Add to Composer" button next to the install button
4. **Activate** the plugin as usual


## 🎯 How It Works

1. **Scans** the plugin install page for available plugins
2. **Checks** your `composer.json` for existing packages
3. **Displays** appropriate buttons:
   - "Add to Composer" for new plugins
   - "Already in Composer" for existing packages
4. **Fetches** plugin version from WordPress.org API
5. **Updates** `composer.json` with proper version constraints

## 🔧 Development

### Code Standards

- **PSR-4** autoloading
- **WordPress Coding Standards**
- **PHP 8.1+** features and syntax

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 🐛 Issues & Support

- **Bug Reports**: [GitHub Issues](https://github.com/CallMeLeon167/bedrock-composer-store/issues)
- **Feature Requests**: [GitHub Issues](https://github.com/CallMeLeon167/bedrock-composer-store/issues)
- **Email**: [kontakt@callmeleon.de](mailto:kontakt@callmeleon.de)

## ⚖️ License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.

---

**Made with ❤️ for the WordPress community**