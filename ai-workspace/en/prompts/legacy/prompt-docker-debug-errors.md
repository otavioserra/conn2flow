````markdown
# Docker Manager Debug - Conn2Flow

## 🎯 Initial Context
- You are working on the Conn2Flow project, controlling errors in the test environment using Docker. Other AI agents are running other tasks, and yours is solely focused on Docker tasks.
- **Installation errors:** The installer log file is located at: `docker\data\public_html\installer\installer.log`

## 📋 Command Sequence
- **Docker Folder:** Make sure you are in the correct folder: `docker/data`.
```
pwd
```
If not, navigate to the folder:
```
cd docker/data
```
- **Check for installation errors:**
```
docker compose exec app bash -c "cat /var/www/html/installer/installer.log"
```
- **Check for Apache errors:**
```
docker compose logs app --tail=100
```
- **Check for PHP errors:**
```
docker compose exec app bash -c "tail -n 100 /var/log/php_errors.log"
```

## 🌐 Result:
- Analyze the logs and give me correction options.

## 🔧 If you have questions
If you have any questions, you can ask me, and we can do it in more than one request.


````