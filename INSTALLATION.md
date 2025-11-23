# NexusForms Installation & Testing Guide

Complete guide for installing, configuring, and testing NexusForms WordPress Form Builder.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Installation](#installation)
3. [Initial Configuration](#initial-configuration)
4. [Testing Checklist](#testing-checklist)
5. [Build Process](#build-process)
6. [Troubleshooting](#troubleshooting)
7. [Development Setup](#development-setup)

---

## System Requirements

### Minimum Requirements

- **PHP**: 8.0 or higher
- **WordPress**: 6.0 or higher
- **MySQL**: 5.7 or higher (or MariaDB 10.3+)
- **Node.js**: 18.x or higher (for development)
- **npm**: 9.x or higher (for development)

### Recommended

- **PHP**: 8.2+
- **WordPress**: Latest version
- **MySQL**: 8.0+
- **Memory Limit**: 256MB minimum
- **Max Upload Size**: 64MB minimum (for file uploads)

### PHP Extensions Required

- `json`
- `mysqli` or `pdo_mysql`
- `fileinfo` (for file uploads)
- `mbstring`

---

## Installation

### Method 1: Manual Installation (Development)

1. **Clone or Download** the plugin to your WordPress plugins directory:
   ```bash
   cd /path/to/wordpress/wp-content/plugins/
   git clone <repository-url> nexusforms
   cd nexusforms
   ```

2. **Install Dependencies**:
   ```bash
   npm install
   ```

3. **Build Assets**:
   ```bash
   npm run build
   ```

4. **Activate Plugin** in WordPress:
   - Navigate to **Plugins** → **Installed Plugins**
   - Find "NexusForms - WordPress Form Builder"
   - Click **Activate**

### Method 2: ZIP Installation (Production)

1. **Build the plugin** (if not already built):
   ```bash
   npm run build
   ```

2. **Create a ZIP file** of the plugin directory (excluding `node_modules` and `.git`):
   ```bash
   zip -r nexusforms.zip nexusforms/ -x "*/node_modules/*" "*/.git/*" "*/assets/src/*"
   ```

3. **Upload via WordPress Admin**:
   - Navigate to **Plugins** → **Add New** → **Upload Plugin**
   - Choose the ZIP file
   - Click **Install Now**
   - Activate the plugin

### Method 3: FTP Upload

1. Build the plugin locally (if needed)
2. Upload the entire `nexusforms` folder to `/wp-content/plugins/`
3. Activate via WordPress admin

---

## Initial Configuration

### 1. Database Setup

The plugin automatically creates 4 custom database tables on activation:

- `wp_nexus_forms` - Stores form configurations
- `wp_nexus_fields` - Stores form fields
- `wp_nexus_entries` - Stores form submissions
- `wp_nexus_notifications` - Stores notification settings

**Verify Tables Created**:
```sql
SHOW TABLES LIKE 'wp_nexus_%';
```

You should see all 4 tables listed.

### 2. File Upload Directory

The plugin creates secure upload directories:

**Directory Structure**:
```
wp-content/uploads/nexusforms/
├── {form_id}/
│   ├── {entry_id}/
│   │   ├── uploaded-file.pdf
│   │   └── .htaccess
│   └── .htaccess
└── index.php
```

**Verify Directory Permissions**:
```bash
ls -la wp-content/uploads/nexusforms/
```

Permissions should be `755` for directories and `644` for files.

### 3. Plugin Settings

Navigate to **NexusForms** → **Settings** and configure:

- **Disable CSS**: Uncheck (unless using custom styling)
- **File Upload Settings**: Set maximum file sizes and allowed types
- **Email Settings**: Configure SMTP if needed (recommended for production)
- **Recaptcha**: Add reCAPTCHA keys for spam protection (optional)

---

## Testing Checklist

### Core Functionality Tests

#### ✅ Form Builder Tests

- [ ] **Create New Form**
  1. Navigate to **NexusForms** → **New Form**
  2. Enter form title
  3. Add fields from palette (drag or click)
  4. Save as draft
  5. Verify form saved successfully

- [ ] **Edit Form**
  1. Open existing form
  2. Modify field labels and settings
  3. Reorder fields via drag-and-drop
  4. Save changes
  5. Verify changes persisted

- [ ] **Field Types**
  - [ ] Text Input
  - [ ] Email
  - [ ] Textarea
  - [ ] Number
  - [ ] Phone (tel)
  - [ ] URL
  - [ ] Dropdown (select)
  - [ ] Radio Buttons
  - [ ] Checkboxes
  - [ ] File Upload

- [ ] **Field Settings**
  - [ ] Label and placeholder
  - [ ] Required/optional toggle
  - [ ] Default values
  - [ ] Validation rules (min/max length, pattern)
  - [ ] Conditional logic rules
  - [ ] Field descriptions

#### ✅ Frontend Display Tests

- [ ] **Shortcode Rendering**
  1. Copy shortcode: `[nexusforms id="X"]`
  2. Add to page/post
  3. View on frontend
  4. Verify form displays correctly
  5. Check responsive layout (mobile/tablet/desktop)

- [ ] **Page Builder Integration**
  - [ ] **Gutenberg Block**
    1. Add NexusForms block in editor
    2. Select form from dropdown
    3. Preview and publish
    4. Verify on frontend

  - [ ] **Elementor Widget** (if Elementor installed)
    1. Add NexusForms widget
    2. Select form
    3. Configure display options
    4. Preview and publish

  - [ ] **DIVI Module** (if DIVI installed)
  - [ ] **Bricks Element** (if Bricks installed)
  - [ ] **WPBakery Component** (if WPBakery installed)

#### ✅ Form Submission Tests

- [ ] **Basic Submission**
  1. Fill out all required fields
  2. Submit form
  3. Verify success message displays
  4. Check entry in **NexusForms** → **Entries**
  5. Verify all field data saved correctly

- [ ] **Validation**
  - [ ] Required field validation (leave required field empty)
  - [ ] Email format validation
  - [ ] URL format validation
  - [ ] Number validation (min/max)
  - [ ] Text length validation (min/max)
  - [ ] Pattern/regex validation

- [ ] **AJAX Submission**
  1. Submit form without page reload
  2. Verify inline error messages
  3. Verify success message
  4. Verify form reset after submission

- [ ] **File Upload**
  1. Add file upload field
  2. Upload allowed file type (PDF, JPG, etc.)
  3. Verify file saved to `/wp-content/uploads/nexusforms/`
  4. Try uploading disallowed file type (should fail)
  5. Try exceeding max file size (should fail)
  6. Test multiple file upload (if enabled)

#### ✅ Conditional Logic Tests

- [ ] **Show/Hide Fields**
  1. Create conditional rule (e.g., "Show field B if field A = 'Yes'")
  2. Test on frontend - change field A value
  3. Verify field B shows/hides correctly

- [ ] **Multiple Conditions**
  1. Create rule with multiple conditions (ALL or ANY)
  2. Test various combinations
  3. Verify logic works as expected

#### ✅ Multi-Step Form Tests

- [ ] **Enable Multi-Step**
  1. Enable multi-step in form settings
  2. Create 3+ steps
  3. Assign fields to different steps
  4. Enable progress bar and step numbers

- [ ] **Step Navigation**
  1. Submit first step (should validate)
  2. Navigate to next step
  3. Go back to previous step
  4. Verify data persists between steps
  5. Complete final step and submit

#### ✅ Email Notification Tests

- [ ] **Admin Notification**
  1. Enable email notifications in form settings
  2. Set recipient to admin email
  3. Configure subject and body with smart tags
  4. Submit form
  5. Verify email received with correct data

- [ ] **Smart Tags**
  - [ ] `{form_title}` - Form title
  - [ ] `{all_fields}` - All field data
  - [ ] `{field_id}` - Specific field value
  - [ ] `{date}` - Submission date
  - [ ] `{time}` - Submission time
  - [ ] `{ip_address}` - User IP
  - [ ] `{site_name}` - Site name
  - [ ] `{site_url}` - Site URL

- [ ] **HTML vs Plain Text**
  - [ ] Test HTML format with formatting
  - [ ] Test plain text format

#### ✅ Import/Export Tests

- [ ] **Export Form**
  1. Export form as JSON
  2. Copy to clipboard or download file
  3. Verify JSON structure is valid

- [ ] **Import Form**
  1. Import exported JSON
  2. Verify form created with all fields
  3. Verify settings preserved
  4. Verify conditional logic preserved

#### ✅ Template Tests

- [ ] **Apply Template**
  1. Create new form
  2. Click "Use Template"
  3. Select template (Contact, Registration, etc.)
  4. Verify fields populated
  5. Customize and save

---

## Build Process

### Development Build (Watch Mode)

```bash
npm start
```

This starts the development server with hot reloading:
- Watches for file changes
- Auto-rebuilds on save
- Faster build times (unminified)

### Production Build

```bash
npm run build
```

This creates optimized production assets:
- Minified JavaScript
- Optimized CSS
- Source maps for debugging

### Build Output

```
assets/build/
├── admin.js        (162 KB - React admin interface)
├── admin.css       (10.4 KB - Admin styles)
├── frontend.js     (1.93 KB - Frontend submission handler)
├── frontend.css    (6.83 KB - Frontend form styles)
├── blocks.js       (1.9 KB - Gutenberg block)
└── blocks.css      (Gutenberg block styles)
```

### Verify Build

```bash
ls -lh assets/build/
```

All files should be present and have reasonable file sizes.

---

## Troubleshooting

### Common Issues

#### 1. **White Screen After Activation**

**Cause**: PHP version too old or missing extensions

**Solution**:
```bash
# Check PHP version
php -v  # Should be 8.0+

# Check for required extensions
php -m | grep -E 'json|mysqli|fileinfo|mbstring'
```

#### 2. **Forms Not Saving**

**Cause**: Database tables not created or permission issues

**Solution**:
```sql
-- Check if tables exist
SHOW TABLES LIKE 'wp_nexus_%';

-- If missing, deactivate and reactivate plugin
-- Or manually create tables (see includes/class-database.php)
```

#### 3. **Assets Not Loading (404 Errors)**

**Cause**: Build assets missing

**Solution**:
```bash
cd /path/to/nexusforms
npm install
npm run build

# Verify assets created
ls -la assets/build/
```

#### 4. **File Uploads Failing**

**Cause**: Directory permissions or upload size limits

**Solution**:
```bash
# Fix directory permissions
chmod 755 wp-content/uploads/nexusforms
chmod 644 wp-content/uploads/nexusforms/.htaccess

# Check PHP upload limits
php -i | grep -E 'upload_max_filesize|post_max_size'

# Increase in php.ini if needed:
# upload_max_filesize = 64M
# post_max_size = 64M
```

#### 5. **Email Notifications Not Sending**

**Cause**: WordPress mail() function issues or server configuration

**Solution**:
1. Install SMTP plugin (e.g., WP Mail SMTP)
2. Configure SMTP settings
3. Test email with test form submission
4. Check spam folder

#### 6. **Conditional Logic Not Working**

**Cause**: JavaScript errors or caching

**Solution**:
```bash
# Check browser console for errors
# Clear browser cache
# Rebuild frontend assets
npm run build

# Verify frontend.js loaded
# View page source and search for "nexusforms-frontend"
```

### Debug Mode

Enable WordPress debug mode to see detailed errors:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Check debug log
tail -f wp-content/debug.log
```

---

## Development Setup

### Setting Up Development Environment

1. **Clone Repository**:
   ```bash
   git clone <repo-url> nexusforms
   cd nexusforms
   ```

2. **Install Dependencies**:
   ```bash
   npm install
   ```

3. **Start Development Server**:
   ```bash
   npm start
   ```

4. **Create Symlink** (optional, for local development):
   ```bash
   ln -s /path/to/nexusforms /path/to/wordpress/wp-content/plugins/nexusforms
   ```

### Development Workflow

1. Make changes to source files in `assets/src/`
2. Watch mode auto-rebuilds on save
3. Refresh browser to see changes
4. Run tests before committing

### Code Style

The project uses:
- **ESLint** for JavaScript linting
- **Prettier** for code formatting (if configured)
- **WordPress Coding Standards** for PHP

### Testing

```bash
# Run JavaScript tests (if configured)
npm test

# Run PHP tests (if configured)
composer test
```

---

## Additional Resources

### Documentation

- [WordPress Codex](https://codex.wordpress.org/)
- [React Documentation](https://react.dev/)
- [@wordpress/components](https://developer.wordpress.org/block-editor/reference-guides/components/)

### Support

- **Issues**: [GitHub Issues](https://github.com/yourusername/nexusforms/issues)
- **Documentation**: [Plugin Website](https://nexusforms.com)
- **Community**: [WordPress.org Forums](https://wordpress.org/support/plugin/nexusforms)

---

## Quick Start Summary

```bash
# 1. Install dependencies
npm install

# 2. Build assets
npm run build

# 3. Activate plugin in WordPress
# Navigate to Plugins → Activate "NexusForms"

# 4. Create your first form
# Navigate to NexusForms → New Form

# 5. Add to page
# Use shortcode: [nexusforms id="1"]

# Done! 🎉
```

---

**Last Updated**: 2025-01-23
**Plugin Version**: 1.0.0
