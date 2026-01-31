````markdown
# Module: admin-files

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `admin-files` |
| **Name** | File Administration |
| **Version** | `1.1.0` |
| **Category** | Administrative Module |
| **Complexity** | 🔴 High |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **admin-files** module is the **central file and media management system** of Conn2Flow. It is responsible for all functionality related to uploading, organizing, categorizing, and managing digital files (images, videos, documents, etc.).

## 🏗️ Main Features

### 📤 **Upload System**
- **Multiple uploads**: Support for multiple files simultaneously
- **Size validation**: Maximum limit of 10MB per file
- **Supported types**: Images, videos, audios, documents
- **Automatic thumbnail generation**: For images and videos
- **Organization by date**: Automatic `YYYY/MM/` structure
- **Unique names**: System of unique identifiers to avoid conflicts

### 🗂️ **Organization and Categorization**
- **Multiple categories**: A file can belong to multiple categories
- **Advanced filters**: By date, category, type, name
- **Flexible sorting**: Alphabetical, by date (ascending/descending)
- **Pagination**: 100 files per page
- **Quick search**: Real-time search system

### 🖼️ **Visualization and Management**
- **Smart preview**: Automatic thumbnails for different types
- **Detailed information**: Name, date, type, size
- **Quick actions**: Copy URL, select, edit, delete
- **Responsive modal**: Interface adaptable to different devices
- **Iframe support**: Integration in popups and modals

### 🔗 **System Integration**
- **CKEditor**: Native integration with text editor
- **File selector**: Modal for selection in other modules
- **REST API**: Endpoints for programmatic operations
- **Permanent links**: Stable URLs for files

## 🗄️ Database Structure

### Main Table: `files`
```sql
CREATE TABLE files (
    id_files INT AUTO_INCREMENT PRIMARY KEY,
    id_users INT NOT NULL,                 -- User who uploaded
    name VARCHAR(255) NOT NULL,               -- Original file name
    id VARCHAR(255) UNIQUE NOT NULL,          -- Unique identifier
    type VARCHAR(100),                        -- MIME type
    path VARCHAR(500),                     -- File path
    path_mini VARCHAR(500),                -- Thumbnail path
    status CHAR(1) DEFAULT 'A',               -- Status (A=Active, D=Deleted)
    version INT DEFAULT 1,                     -- Version control
    creation_date DATETIME DEFAULT NOW(),      -- Upload date
    modification_date DATETIME DEFAULT NOW(),   -- Last modification
    INDEX idx_status (status),
    INDEX idx_user (id_users),
    INDEX idx_type (type),
    FOREIGN KEY (id_users) REFERENCES users(id_users)
);
```

### Relationship Table: `files_categories`
```sql
CREATE TABLE files_categories (
    id_files INT NOT NULL,
    id_categories INT NOT NULL,
    PRIMARY KEY (id_files, id_categories),
    FOREIGN KEY (id_files) REFERENCES files(id_files),
    FOREIGN KEY (id_categories) REFERENCES categories(id_categories)
);
```

## 📁 File Structure

### Physical Organization
```
contents/files/
├── YYYY/                    # Year
│   └── MM/                  # Month
│       ├── file.ext      # Original file
│       └── mini/            # Thumbnails
│           └── file.ext  # Thumbnail
```

### Main Codes

#### 🔧 **Core PHP Functions**

##### `admin_files_list($params)`
Main function for listing files with support for filters, pagination, and categorization.

**Parameters:**
- `page` (string): Template where the list will be rendered

**Functionalities:**
- Application of filters (date, category, sorting)
- Pagination with 100 items per page
- Grouping by categories
- AJAX support for dynamic loading
- Generation of URLs and thumbnails

##### `admin_files_ajax_upload_file()`
AJAX processing of file uploads.

**Functionalities:**
- Size validation (max. 10MB)
- Generation of unique identifier
- Creation of directory structure
- Thumbnail processing
- Insertion into the database
- JSON return with status

##### `admin_files_create_dir_inheriting_permission($dir)`
Creation of directories with permission inheritance from the parent directory.

#### 🖥️ **Core JavaScript**

##### jQuery File Upload System
```javascript
$('#fileupload').fileupload({
    url: 'admin-files/?ajax=upload_file',
    dataType: 'json',
    maxFileSize: 10000000, // 10MB
    acceptFileTypes: /(\.|\/)(gif|jpe?g|png|mp4|pdf|doc|docx)$/i
});
```

##### Filter System
```javascript
// Filters by date, category, and sorting
var filters = {
    dateFrom: $('#rangestart').calendar('get date'),
    dateTo: $('#rangeend').calendar('get date'),
    categories: $('#categories').dropdown('get value'),
    order: $('#order').dropdown('get value')
};
```

##### AJAX Pagination
```javascript
function loadMoreFiles() {
    $.post('admin-files/?ajax=list_more_results', {
        page: currentPageList + 1,
        filters: JSON.stringify(filters)
    }, function(data) {
        $('#files-list-cont').append(data.page);
        currentPageList++;
    });
}
```

## 🎨 User Interface

### 📱 **Responsive Layout**
- **Desktop**: 4-column grid with large thumbnails
- **Tablet**: 3-column grid with medium thumbnails  
- **Mobile**: Vertical list with small thumbnails

### 🔽 **Upload Area (Drag & Drop)**
```html
<div class="upload-zone">
    <div class="dz-message">
        <i class="cloud upload icon"></i>
        <p>Drag files here or click to select</p>
    </div>
</div>
```

### 🗃️ **File List**
```html
<div class="files-grid">
    <div class="file-card" data-file-id="123">
        <div class="thumbnail">
            <img src="files/2024/08/mini/image.jpg" alt="Image">
        </div>
        <div class="file-info">
            <h4>image.jpg</h4>
            <span class="file-date">31/08/2024 15:30</span>
            <span class="file-type">image/jpeg</span>
        </div>
        <div class="file-actions">
            <button class="btn-copy" data-url="files/2024/08/image.jpg">
                <i class="copy icon"></i> Copy URL
            </button>
            <button class="btn-select">
                <i class="check icon"></i> Select
            </button>
        </div>
    </div>
</div>
```

### 🎛️ **Advanced Filters**
```html
<div class="filters-panel">
    <div class="ui form">
        <div class="fields">
            <div class="field">
                <label>Period</label>
                <div class="ui calendar" id="rangestart">
                    <input type="text" placeholder="Start date">
                </div>
            </div>
            <div class="field">
                <div class="ui calendar" id="rangeend">
                    <input type="text" placeholder="End date">
                </div>
            </div>
            <div class="field">
                <label>Categories</label>
                <select id="categories" class="ui multiple dropdown">
                    <option value="">All categories</option>
                </select>
            </div>
            <div class="field">
                <label>Sort</label>
                <select id="order" class="ui dropdown">
                    <option value="alphabetical-asc">A-Z</option>
                    <option value="alphabetical-desc">Z-A</option>
                    <option value="order-date-asc">Oldest</option>
                    <option value="order-date-desc">Newest</option>
                </select>
            </div>
        </div>
        <div class="buttons">
            <button class="ui button filterButton">Filter</button>
            <button class="ui button clearButton">Clear</button>
        </div>
    </div>
</div>
```

## 🔌 Integration with Other Modules

### 📝 **CKEditor (Text Editor)**
The module integrates natively with CKEditor for image insertion:

```javascript
CKEDITOR.config.filebrowserBrowseUrl = 'admin-files/?pageIframe=yes';
CKEDITOR.config.filebrowserImageBrowseUrl = 'admin-files/?pageIframe=yes&type=image';
```

### 🏷️ **Categories Module**
Bidirectional integration for file categorization:

```php
// Fetch available categories
$categories = database_select_name(
    "name, id_categories",
    "categories",
    "WHERE id_modules='11' OR id_modules IS NULL"
);

// Associate file with categories
foreach($_POST['categories'] as $id_category) {
    database_insert_name([
        ['id_files', $id_file],
        ['id_categories', $id_category]
    ], 'files_categories');
}
```

### 👥 **Users System**
Ownership and permission control:

```php
// Check upload permission
if(!manager_user_permission('admin-files', 'upload')) {
    return manager_error('No permission to upload');
}

// Register file owner
$id_user = manager_user()['id_users'];
```

## ⚙️ Settings and Parameters

### 📐 **Limits and Validations**
```php
// Upload settings
define('MAX_FILE_SIZE', 10000000);           // 10MB
define('ALLOWED_TYPES', [
    'image/jpeg', 'image/png', 'image/gif',   // Images
    'video/mp4', 'video/webm',                // Videos
    'audio/mp3', 'audio/wav',                 // Audios
    'application/pdf',                        // Documents
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);
```

### 🗂️ **Directory Structure**
```php
// Automatic folder configuration
$basedir = 'files';
$thumbnail = 'mini';
$path = $basedir . '/' . date('Y') . '/' . date('m') . '/';
$path_mini = $path . $thumbnail . '/';
```

### 📊 **Pagination**
```php
// Listing configuration
$max_data_per_page = 100;
$total_pages = ceil($total_files / $max_data_per_page);
```

## 🛡️ Security

### 🔒 **Upload Validations**
- **MIME type verification**: Only allowed types
- **Extension validation**: Double check
- **Size limit**: Protection against large uploads
- **Name sanitization**: Removal of dangerous characters
- **Ownership verification**: Authenticated user

### 🗂️ **Directory Protection**
```php
// Secure directory creation
function admin_files_create_dir_inheriting_permission($dir) {
    if (!is_dir($dir)) {
        $parent = dirname($dir);
        $parent_permission = is_dir($parent) ? fileperms($parent) & 0777 : 0755;
        mkdir($dir, $parent_permission, true);
    }
}
```

### 🚫 **Attack Prevention**
- **SQL Injection**: Use of `database_escape_field()`
- **XSS**: Sanitization of input data
- **Path Traversal**: Path validation
- **Script Upload**: Strict type checking

## 📈 Performance and Optimization

### ⚡ **Performance Strategies**
- **Lazy Loading**: On-demand loading of thumbnails
- **AJAX Pagination**: Avoids full page reload
- **Thumbnail Caching**: Single generation and reuse
- **Database Indexes**: Query optimization
- **Image Compression**: Automatic quality reduction

### 🗃️ **Cache and Storage**
```php
// Thumbnail caching system
if (!file_exists($path_file_mini)) {
    $resized_image = imagescale($original_image, 300, 300);
    imagejpeg($resized_image, $path_file_mini, 80);
}
```

## 🔧 APIs and Endpoints

### 📡 **AJAX Endpoints**
| Endpoint | Method | Function |
|----------|--------|--------|
| `?ajax=upload_file` | POST | Upload single file |
| `?ajax=list_more_results` | POST | File pagination |
| `?ajax=delete_file` | POST | File deletion |
| `?ajax=update_categories` | POST | Update categories |

### 📥 **API Usage Example**
```javascript
// Upload via AJAX
var formData = new FormData();
formData.append('files[]', file);

$.ajax({
    url: 'admin-files/?ajax=upload_file',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(response) {
        if(response.status === 'Ok') {
            console.log('Upload successful:', response.file);
        }
    }
});
```

## 🧪 Tests and Validation

### ✅ **Test Cases**
- **Basic upload**: 2MB JPG image
- **Multiple uploads**: 5 files of different types
- **Size validation**: 15MB file (should fail)
- **Filters**: Search by specific category
- **Pagination**: Navigation between pages
- **Deletion**: File removal and cleanup

### 🐛 **Known Issues**
- **Timeout on large uploads**: Configure `max_execution_time`
- **Directory permissions**: Check www-data ownership
- **Thumbnail cache**: Manual cleaning occasionally necessary

## 📊 Metrics and Monitoring

### 📈 **Module KPIs**
- **Total files**: Number of files in the system
- **Uploads per day**: Repository growth rate
- **Most used types**: Format statistics
- **Upload errors**: Failure rate
- **Storage usage**: Disk space used

### 📋 **Important Logs**
```php
// Upload log
error_log("Upload successful: {$name} by user {$id_user}");

// Error log
error_log("Upload error: {$error} - file: {$name}");
```

## 🚀 Roadmap and Improvements

### ✅ **Implemented (v1.1.0)**
- Basic upload system
- Multiple categorization
- Advanced filters
- Responsive interface
- CKEditor integration

### 🚧 **In Development (v1.2.0)**
- Integrated image editor
- HTML5 video support
- Cloud storage synchronization
- Complete REST API
- Automatic compression

### 🔮 **Planned (v2.0.0)**
- Automatic content recognition (AI)
- File versioning
- Real-time collaboration
- Integrated CDN
- Automatic backup

## 🔗 Dependencies

### 📚 **JavaScript Libraries**
- **jQuery File Upload 10.31.0**: Upload system
- **Semantic UI**: User interface
- **Calendar JS**: Date selectors
- **Modal Manager**: Modal system

### 🔧 **PHP Libraries**
- **GD Extension**: Image processing
- **cURL**: HTTP communication
- **ZIP Extension**: File compression
- **OpenSSL**: Security

### 🗄️ **Database**
- **MySQL 5.7+**: Main database
- **Optimized indexes**: Query performance
- **Foreign Keys**: Referential integrity

## 📖 Conclusion

The **admin-files** module is a fundamental piece of Conn2Flow, offering a robust and complete system for managing digital files. With a modern interface, advanced features, and deep integration with the system, it represents one of the most sophisticated modules on the platform.

**Main features:**
- ✅ **Intuitive interface** with drag & drop
- ✅ **Optimized performance** with lazy loading and caching
- ✅ **Robust security** with multiple validations
- ✅ **Native integration** with editors and other modules
- ✅ **Scalability** prepared for large volumes

**Status**: ✅ **Production - Mature and Stable**  
**Maintainers**: Conn2Flow Core Team  
**Last update**: August 31, 2025

````