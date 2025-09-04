# Plugin Skeleton

This folder contains the recommended structure for developing, packaging, and releasing plugins for the Conn2Flow system. Below is a detailed breakdown of each directory and file, based on the official plugin architecture and release workflow.

## plugin-templates

This folder (`plugin-templates`) contains all the base files and example structures used to build a new plugin. It serves as a comprehensive template repository, providing ready-to-use examples for every part of a plugin. When creating a new plugin, you can copy files and folders from `plugin-templates` to the actual `plugin` directory, customizing as needed.

**Purpose:**
- Acts as a reference and starting point for plugin development.
- Includes example modules, controllers, resources, and configuration files.
- Ensures consistency and best practices across all plugins.

**Typical contents:**
- Example module structure (`modules/module-id/` with resources, PHP, JS, JSON)
- Example controllers
- Example resource folders and files (layouts, pages, components, variables)
- Example manifest and mapping files

> Use `plugin-templates` as your source for boilerplate code and recommended file organization when starting a new plugin project.

## Structure Overview

```
.github/                                            (GitHub Actions configuration)
    workflows/                                      (GitHub Actions workflows)
        release-gestor-plugin.yml                   (workflow for plugin release)
ai-workspace/                                       (AI workspace environment)
    git/                                            (AI-generated Git data)
        scripts/                                    (automation scripts for plugin Git)
            release.sh                              (plugin release script)
            commit.sh                               (plugin commit script)
            version.php                             (script to update plugin version)
    scripts/                                        (AI-generated automation scripts)
        build/                                      (local build artifacts)
        updates/                                    (update scripts)
            build-local-gestor-plugin.sh            (local release simulation script)
utils/                                              (plugin creation utilities)
    controllers/                                    (plugin controllers)
        agents/                                     (plugin agent controllers)
            update-data-resources-plugin.php        (generates Data.json for plugin layouts, pages, components, variables)
plugin/                                             (plugin root)
    manifest.json                                   (required plugin manifest)
    controllers/                                    (plugin controllers)
        controller-id/                              (specific controller, 0-n per plugin)
            controller-id.php                       (controller PHP file)
    modules/                                        (all plugin modules)
        module-id/                                  (module, always follows this pattern for system auto-connection)
            resources/                              (module resources, similar to main system)
                pt-br/                              (module resources in pt-br, can have en, es, etc.)
                    pages/                          (module pages)
                        page-id/                    (HTML/CSS for page-id, 0-n pages)
                            page-id.css             (optional CSS for page)
                            page-id.html            (optional HTML for page)
                    layouts/                        (module layouts)
                        layout-id/                  (HTML/CSS for layout-id, 0-n layouts)
                            layout-id.css           (optional CSS for layout)
                            layout-id.html          (optional HTML for layout)
                    components/                     (module components)
                        component-id/               (HTML/CSS for component-id, 0-n components)
                            component-id.css        (optional CSS for component)
                            component-id.html       (optional HTML for component)
            modulo-id.json                          (mapping of module pages, layouts, components, variables)
            modulo-id.js                            (module-specific JavaScript)
            modulo-id.php                           (module-specific PHP, referenced in gestor.php)
    resources/                                      (global plugin resources, similar to main system)
        pt-br/                                      (global resources in pt-br, can have en, es, etc.)
            pages/                                  (global pages, same pattern as modules)
            layouts/                                (global layouts, same pattern as modules)
            components/                             (global components, same pattern as modules)
            components.json                         (global plugin components)
            layouts.json                            (global plugin layouts)
            pages.json                              (global plugin pages)
            variables.json                          (global plugin variables)
        resources.map.php                           (global resource mapping)
    db/                                             (plugin database)
        data/                                       (plugin Data.json, generated by update-data-resources-plugin.php)
        migrations/                                 (plugin-specific migrations)
    assets/                                         (css/js/images)
    vendor/                                         (if isolated – policy to be evaluated)
```

## Key Files & Directories
- **manifest.json**: Required metadata for the plugin.
- **modules/**: Each module must follow the naming and structure for automatic system connection.
- **resources/**: Global resources, with language subfolders and JSON mapping files.
- **db/data/**: Contains Data.json, generated during plugin development.
- **utils/controllers/agents/update-data-resources-plugin.php**: Script to generate Data.json from source files.
- **ai-workspace/scripts/updates/build-local-gestor-plugin.sh**: Script to build and package the plugin for release/testing.
- **.github/workflows/release-gestor-plugin.yml**: GitHub Actions workflow for plugin release automation.

## Usage Notes
- All resource folders (pages, layouts, components) must follow the described structure for compatibility.
- Language folders (e.g., pt-br, en) allow for multi-language resource support.
- Module and global resources are mapped via their respective JSON files and `resources.map.php`.
- The skeleton is designed to support both manual and automated (CI/CD) plugin releases.

## References
- For more details, see the main system documentation and the scripts referenced above.
- This structure is based on the official Conn2Flow plugin architecture and workflow requirements.
