````markdown
# Global Data Structure - Conn2Flow Development

## 🎯 Initial Context
1. We will migrate the data structure to JSON files. This way you can create and modify data using PHP scripts with higher quality, without the need to keep using complicated regex for nothing.
2. The main objective is to pass the data structure that changes during development such as HTML and CSS files, variables, categories, and any other type that may arise in the system.
3. The first task will be to pass all global scope variables, which are stored in the file `gestor\resources\resources.map.pt-br.php` in the **$resources** variable of type Array, which is inside this file, to 3 .JSON files with destination in the folder `gestor\resources\pt-br`. Each file will have the data of the 3 types with the following names: `layouts.json`, `pages.json` and `components.json`. The origin of each type is an index of the **$resources** variable. Example: **$resources['layouts']**.
4. In the end you will delete the file `gestor\resources\resources.map.pt-br.php` and update the file path reference of `gestor\resources\resources.map.php`. Currently this file uses the `path` reference to the file `gestor\resources\resources.map.pt-br.php`. But, we will update and reference now the path of each of the 3 new files: `layouts.json`, `pages.json` and `components.json`.

## 📋 Project Information
- Always use this file as a reference for the actions that need to be taken.
- You can change this file and update the information freely.
- Always verify if all tasks described here have been completed.
- If you have doubts, please question all of them before any implementation.
- Err on the side of asking too many questions. No need to rush to implement, we can interact a few times before.
- No need to do all tasks in a single interaction, you can divide it into stages.

## 🤔 Doubts and 📝 Suggestions Answers

# ✅ IMPLEMENTATION PROGRESS
- [x] Current structure analysis completed
- [x] Migration script created and tested
- [x] Global data migration execution (100% completed)
- [x] Creation of the 3 JSON files (layouts.json, pages.json, components.json)
- [x] Update of resources.map.php file with new structure
- [x] Removal of resources.map.pt-br.php file
- [x] Complete validation (6/6 tests passed)

# 🎉 FINAL RESULT
**GLOBAL DATA MIGRATION 100% COMPLETED SUCCESSFULLY!**
- ✅ 12 layouts migrated to JSON
- ✅ 40 pages migrated to JSON
- ✅ 79 components migrated to JSON
- ✅ Mapping structure updated
- ✅ Old file removed
- ✅ System validated and ready for production

# 📁 Created/Modified Files
## Scripts:
- `ai-workspace/scripts/arquitetura/migrate-global-data-to-json.php` - Migration script
- `ai-workspace/scripts/arquitetura/validate-global-migration.php` - Validation script

## JSON Structure:
- `gestor/resources/pt-br/layouts.json` - 12 layouts (3.850 bytes)
- `gestor/resources/pt-br/pages.json` - 40 pages (18.203 bytes)
- `gestor/resources/pt-br/components.json` - 79 components (22.014 bytes)

## Updated Files:
- `gestor/resources/resources.map.php` - New structure with JSON references

## Removed Files:
- `gestor/resources/resources.map.pt-br.php` - Old file removed

# Doubts
If you have doubts put them here.

# Suggestions
If you have suggestions put them here.

## 🔧 Useful Commands


## 📝 Task Development
1. You will create a PHP script that will do the entire task. You will copy the content of the Array type variable **$resources** and store it in a temporary variable. Example of the code:
```php
$resources = array (
  'layouts' => 
  array (
    0 => 
    array (
      'name' => 'Layout Administrativo do Gestor',
      'id' => 'layout-administrativo-do-gestor',
      'version' => '1.0',
      'checksum' => 
      array (
        'html' => '47b4e8434e35ea44407008a3c1b02ff7',
        'css' => 'f10fa90d6577c2fdae5f56b13589179a',
        'combined' => 'f94ef140d592752cdc8e4d4d0e1c8e18',
      ),
    ),
```
2. You will take the indices of this variable `layouts`, `pages` and `components`. Apply the `json_encode()` function on each of them. And store the result of each index in a .JSON file inside the folder `gestor\resources\pt-br`. The 3 new files will be: `layouts.json`, `pages.json` and `components.json`.
3. You will update the file `gestor\resources\resources.map.php` and modify it to a new mapping structure:

# From:
```php
$resources = [
	'languages' => [
        'pt-br' => [
            'name' => 'Português (Brasil)',
            'path' => 'resources.map.pt-br.php',
            'version' => '1',
        ],
    ],
];
```

# To:
```php
$resources = [
	'languages' => [
        'pt-br' => [
            'name' => 'Português (Brasil)',
            'data': [
                'layouts' => 'layouts.json',
                'pages' => 'pages.json',
                'components' => 'components.json',
            ],
            'version' => '1',
        ],
    ],
];
```

4. In the end you will delete the file `gestor\resources\resources.map.pt-br.php`

## 📁 Relevant Files Expected
1. Whenever creating any operation script, mandatorily use the folder to store them: `ai-workspace\scripts\arquitetura\`.

---
**Date:** $(date)
**Developer:** Otavio Serra
**Project:** Conn2Flow v1.11.0

````