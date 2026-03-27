## Novem Linguae's Wikipedia bots

### Tasks

https://en.wikipedia.org/wiki/User:NovemBot

- Task 1 - Promote featured and good topic candidates. Summon the bot to a FGTC voting page using {{User:NovemBot/Promote}} ~~~~. Person adding the template must be on the allowlist.
- Task 7 - Update User:Amalthea/RfX/RfA count every 30 minutes with an updated open RFA count.
- Task A - Edit own userspace to keep accurate lists of users who have permissions, for use with User:Novem Linguae/Scripts/UserHighlighterSimple.
- Task B - Generate list of images that are possible copyright violations and publish it to User:NovemBot/files.

I have some AWB, one time run bot tasks as well, but those are not documented here.

### PHP + Toolforge bot tutorial

I found the learning curve for Toolforge pretty hard. I've written a tutorial at

https://en.wikipedia.org/wiki/User:Novem_Linguae/Essays/Toolforge_bot_tutorial

Nowadays I use WSL/Ubuntu to SSH into the command line: `ssh login.toolforge.org`. Then `become novem-bot`.

### Toolforge jobs framework setup commands

```
toolforge jobs run task-1 --command ./task-1.sh --image php8.4 --schedule "5 * * * *" --emails onfailure

toolforge jobs run task-7 --command ./task-7.sh --image php8.4 --schedule "22,52 * * * *" --emails onfailure

toolforge jobs run task-a --command ./task-a.sh --image php8.4 --schedule "0 13 * * *" --emails onfailure
```

- Task 1 (FGTC) runs hourly at :05
- Task 7 (RFA count) runs twice an hour at :22 and :52
- Task A (userlist.js) runs daily at 5AM

If you want to make sure all of these are programmed in, run `toolforge jobs list`.

If you want to delete and readd these (for example, to upgrade the PHP version), here's the delete commands:

```
toolforge jobs delete task-1

toolforge jobs delete task-7

toolforge jobs delete task-a
```

### Webservice start

The webservice (which is helpful for manually running the bot / debugging) needs to be started one time, then keeps running until you turn it off. Start it with the below command. Keep in mind that the version of PHP it uses can get out of sync with what you're using for your cron jobs above. They are completely separate. So every once in awhile, you will want to update the version of PHP it uses to match your cron jobs.

```
become novem-bot
toolforge webservice status
toolforge webservice stop
toolforge webservice php8.4 start
```

### Files omitted from this repo

If you're reconstructing this repo from scratch, note the following config files are omitted since they contain passwords. You'll need to create them yourself:

- src/public_html/Task1FGTC/config.php
- src/public_html/Task7RFACount/config.php
- src/public_html/TaskAPerms/logininfo.php
- src/public_html/TaskBSuspiciousFiles/logininfo.php
- src/replica.my.cnf
- src/task-1.sh
- src/task-7.sh
- src/task-a.sh

### Some bash commands

- `docker compose up -d` to start the Docker container (https://localhost:8083 to visit on the web)
- `docker exec -it novembot-php-1 /bin/bash` to open a shell
- `composer update`
- `composer exec phpunit tests` to run all test suites
