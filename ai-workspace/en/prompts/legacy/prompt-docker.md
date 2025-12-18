````markdown
# Docker Manager - Conn2Flow

## 🎯 Initial Context
- You are working on the Conn2Flow project, controlling the test environment using Docker. Other AI agents are running other tasks, and yours is solely focused on Docker tasks.

## 📋 Command Sequence
- **Complete Reset of the Test Environment:** All update operations are defined in the following script: `docker\utils\update-installer.sh`
```
# Example of use:
bash ./docker/utils/update-installer.sh
```

## 🌐 ENVIRONMENT:
- Access URL: http://localhost/installer/

## 📋 TEST DATA:
- Host Database: mysql
- Database Name: conn2flow
- Database User: conn2flow_user
- Database Password: conn2flow_pass
- Secure Folder: /home/conn2flow/

## 📁 FOLDER CONFIGURATION:
- Installation Folder (Secure): `/home/conn2flow/manager` (suggestion)
- Apache Folder: `/var/www/html/installer/`
- Domain: `localhost`

## 👨‍💼 ADMINISTRATOR USER:
- Full Name: Administrator
- Email: admin@localhost
- Password: 123456789

## 🔧 If you have questions
If you have any questions, you can ask me, and we can do it in more than one request.


````