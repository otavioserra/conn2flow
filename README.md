# Conn2Flow - The Open Source CMS System

**Welcome to Conn2Flow CMS!**

Conn2Flow is a lightweight and flexible open-source Content Management System (CMS) built using LAMP technology (Linux, Apache, MySQL, and PHP). Originally developed as a proprietary CMS named B2make, Conn2Flow is now being released to the open-source community to foster collaboration and innovation.

**Online Demos**

You will be able to experience Conn2Flow in two demo versions (**not yet published**):

* Latest Version (app.conn2flow.com):  [app.conn2flow.com](http://app.conn2flow.com) (not yet online) - This is the current version of Conn2Flow, developed with Git and representing the latest project state.
* Legacy Version (v1.conn2flow.com): [v1.conn2flow.com](http://v1.conn2flow.com) (not yet online) - This is the legacy version of Conn2Flow, based on the original B2make developed with Tortoise CVS. It is provided for demonstration and historical reference purposes.

## Repository Info

This repository has 3 branches with the 3 big versions delivered throughout time. 2 legacy versions and the actual version.
* **v0-legacy:** The first version was delivered in 2012.
* **v1-legacy:** The second version that delivered in 2015.
* **main:** The actual version with the last big modification was in 2023, but the first major delivery was in 2019.

The actual version is a bundle of a couple of sub-projects stored in these folders explained below.
* **b2make-app:**  React Native app project made to interact with the main server system.
* **b2make-cpanel:**  all libraries implementation which integrates with cPanel using cPanel API 2 and WHM API 1
* **b2make-gestor:**  the main server system project folder. 
* **b2make-gestor-cliente:**  the distributed system project folder. 
* **b2make-gestor-cliente-update:**  the distributed updater system project folder. 
* **b2make-gestor-plugins:**  the plugins root project folder which stores all plugins project folders. 
* **b2make-public-access:**  folder with all files needed for a new installation be configurated on Apache public_html folder. 

## Getting Started

To run Conn2Flow locally, you will need a configured LAMP (Linux, Apache, MySQL, PHP) environment. And an installation of a WHM/cPanel server (we working to remove this dependence from cPanel, but this current version is mandatory).

**Basic Steps:**

1.  **Clone the repository:**
    * The default installation needs to be located on the same level as the public_html folder from Apache.
    * It can be changed by modify relative/absolute paths inside the public access script in ``b2make-public-access/index.php``:
      ```php
	    $_INDEX['sistemas-dir-root'] = '../'; // Change this relative/absolute path to your custom installation path.
      ```
    * Clone the repository inside the installation path defined above.
      ```bash
      git clone git@github.com:otavioserra/conn2flow.git
      ```
2.  **Setup Configurations:**
    * Remove b2make-app from your installation because it will not be used by the system.
    * Move all files from b2make-public-access to public_html folder from Apache. After you can remove ``b2make-public-access`` if you want it because it is not to be used anymore.
    * Change all general definitions for the main server at ``b2make-gestor/config.php`` as you want.
    * Change all project definitions inside the ``b2make-gestor/autenticacoes`` folder. Each sub-folder stored inside it is named with the domain name the system uses (useful for 2 or 3 environments like alpha/beta testing environment and production one. Or websites with lots of parked domains pointing to the same app). Inside each folder you need to change 4 files:
        * ``chaves/gestor/privada.key``: is the OPENSSL private key, it is needed to be defined (RSA / sha512 / 2048 bits).
        * ``chaves/gestor/publica.key``: is the OPENSSL public key, it is needed to be defined (RSA / sha512 / 2048 bits).
        * ``banco.php``: MySQL database settings with credentials to system work.
        * ``config.php``: The major configurations with lots of credentials to external APIs, email sender config, etc. Change the 'SECRET' value with the password defined on OPENSSL private key creation at ``$_CONFIG['openssl-password'] = 'SECRET';``.
    * Change credentials and paths inside ``b2make-cpanel/cpanel-config.php``. You can get cPanel credentials defining a new one at: [cPanel API Tokens How-To](https://docs.cpanel.net/knowledge-base/security/how-to-use-cpanel-api-tokens/)
    * **IMPORTANT:** store one clone of the configuration files in a security local to not overwrite these when you will update the whole system to a new version.
3.  **Configure the Database:**
    * **Create a MySQL Database:**
        * Log in to your MySQL server (e.g., using a MySQL client or phpMyAdmin).
        * Create a new database for Conn2Flow. You can choose any name, for example, `conn2flow_db`.
        * Example SQL command (using MySQL command line):
          ```sql
          CREATE DATABASE conn2flow_db;
          ```
        * **REMEMBER:** Change all variables from all ``banco.php`` explained in the before section with this new db_name.
    * **Import Database Schema:**
        *   Get the database scheme at ``b2make-gestor/banco/gestor.mwb`` using [MySQL Workbench Tool](https://www.mysql.com/products/workbench/). Export to SQL file or connect to the database inside it and use the option to sync your database.
        *   Example SQL command (using MySQL command line, assuming schema file exported is `database.sql` in the project root):
            ```sql
            USE conn2flow_db;
            SOURCE database.sql;
            ```
    *   **Configure Database Credentials:**
        * Change all variables from all ``banco.php`` explained in the before section with these new credentials like database name, user, password, and host.
4.  **Configure the Web Server (Apache):**
      * Configure a Virtual Host in Apache to point to the Conn2Flow project root folder.
      * Ensure Apache has the correct permissions to access the project files.
5.  **Access via Browser:**
      * Open your browser and access the address configured in the Virtual Host (e.g., `http://localhost/conn2flow` or the domain you configured).
      * Follow the installation/configuration instructions that appear on the screen (if any).

## Community

**License**

Conn2Flow will be distributed under an open-source license to ensure freedom of use, modification, and distribution. Candidate licenses include [MIT License](https://www.google.com/url?sa=E&source=gmail&q=https://opensource.org/licenses/MIT) or [GNU GPL v3](https://www.google.com/url?sa=E&source=gmail&q=https://www.gnu.org/licenses/gpl-3.0.en.html). The final license will be defined soon.

**Contributing**

Contributions are welcome! If you want to contribute to Conn2Flow, please:

* Report bugs and suggestions for improvements through the repository's Issues system.
* Submit Pull Requests with bug fixes, new features, or code improvements.
* Help with documentation and translations.
* Participate in the community (communication channels to be defined).

**Communication**

Communication channels for the Conn2Flow community, such as [forum/mailing list/chat - to be defined], will be created soon. Stay tuned for more information! But you can find the founder's personal contact inside their LinkedIn: [https://www.linkedin.com/in/otaviocserra/](https://www.linkedin.com/in/otaviocserra/) 

-----

**Conn2Flow - Transforming B2make into Open Source!**