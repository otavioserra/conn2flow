# Conn2Flow CMS - Open Source

**Welcome to Conn2Flow CMS!**

Conn2Flow is a lightweight and flexible open-source Content Management System (CMS) built using LAMP technology (Linux, Apache, MySQL, and PHP). Originally developed as a proprietary CMS named B2make, Conn2Flow is now being released to the open-source community to foster collaboration and innovation.

*Online Demos*

You will be able to experience Conn2Flow in two demo versions:

* Latest Version (app.conn2flow.com):  [app.conn2flow.com](http://app.conn2flow.com) (not yet online) - This is the current version of Conn2Flow, developed with Git and representing the latest project state.
* Legacy Version (v1.conn2flow.com): [v1.conn2flow.com](http://v1.conn2flow.com) (not yet online) - This is the legacy version of Conn2Flow, based on the original B2make developed with Tortoise CVS. It is provided for demonstration and historical reference purposes.

**Getting Started**

To run Conn2Flow locally, you will need a configured LAMP (Linux, Apache, MySQL, PHP) environment.

**Basic Steps:**

1.  **Clone the repository:** (Once the public Git repository is published, the link will be inserted here)
    ```bash
    git clone [GIT_REPOSITORY_URL_HERE]
    ```
2.  **Configure the Database:**
      *   **Create a MySQL Database:**
        *   Log in to your MySQL server (e.g., using a MySQL client or phpMyAdmin).
        *   Create a new database for Conn2Flow. You can choose any name, for example, `conn2flow_db`.
        *   Example SQL command (using MySQL command line):
            ```sql
            CREATE DATABASE conn2flow_db;
            ```
    *   **Import Database Schema (if available):**
        *   Check if there is a database schema file provided in the repository (e.g., a file named `database.sql` or similar in the project root or a `sql` folder).
        *   If a schema file exists, import it into the `conn2flow_db` database you created.
        *   Example SQL command (using MySQL command line, assuming schema file is `database.sql` in the project root):
            ```sql
            USE conn2flow_db;
            SOURCE database.sql;
            ```
    *   **Configure Database Credentials:**
        *   Locate the Conn2Flow configuration file where database settings are defined. This is often named `config.php`, `db_config.php`, or something similar and might be located in a `config` or `includes` folder within the project.
        *   Open the configuration file and find the database settings (host, username, password, database name).
        *   Update these settings to match your MySQL database credentials (the username and password you use to access your MySQL server, and the database name you created: `conn2flow_db` in the example above).
        *   **Example (Illustrative - Configuration file and variable names may vary):**
            ```php
            <?php
            // Database settings
            define('DB_HOST', 'localhost'); // Or your MySQL host address
            define('DB_USER', 'your_mysql_username'); // Your MySQL username
            define('DB_PASSWORD', 'your_mysql_password'); // Your MySQL password
            define('DB_NAME', 'conn2flow_db'); // The database name you created
            ?>
            ```
3.  **Configure the Web Server (Apache):**
      * Configure a Virtual Host in Apache to point to the Conn2Flow project root folder.
      * Ensure Apache has the correct permissions to access the project files.
4.  **Access via Browser:**
      * Open your browser and access the address configured in the Virtual Host (e.g., `http://localhost/conn2flow` or the domain you configured).
      * Follow the installation/configuration instructions that appear on the screen (if any).

*Versioning*

This Git repository contains the following main branches:

* `main` (or `master`): Main branch with the latest and active version of Conn2Flow. Continuous development will occur in this branch.
* `v1-legacy`: Branch containing the old version of Conn2Flow (based on the original B2make with Tortoise CVS). This branch is provided for reference and demonstration of the legacy version.

*License*

[**LICENSE TO BE DEFINED.***] Conn2Flow will be distributed under an open-source license to ensure freedom of use, modification, and distribution. Candidate licenses include [MIT License](https://www.google.com/url?sa=E&source=gmail&q=https://opensource.org/licenses/MIT) or [GNU GPL v3](https://www.google.com/url?sa=E&source=gmail&q=https://www.gnu.org/licenses/gpl-3.0.en.html). The final license will be defined soon.

*Contributing*

Contributions are welcome! If you want to contribute to Conn2Flow, please:

* Report bugs and suggestions for improvements through the repository's Issues system.
* Submit Pull Requests with bug fixes, new features, or code improvements.
* Help with documentation and translations.
* Participate in the community (communication channels to be defined).

*Community*

Communication channels for the Conn2Flow community, such as [forum/mailing list/chat - to be defined], will be created soon. Stay tuned for more information!

-----

**Conn2Flow - Transforming B2make into Open Source!**

```
