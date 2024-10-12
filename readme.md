## Novem Linguae's Wikipedia bots

### Tasks

https://en.wikipedia.org/wiki/User:NovemBot

- Task A - Edit own userspace to keep accurate lists of users who have permissions, for use with User:Novem Linguae/Scripts/UserHighlighterSimple.
- Task B - Generate list of images that are possible copyright violations and publish it to User:NovemBot/files.
- Task 1 - Promote featured and good topic candidates. Summon the bot to a FGTC voting page using {{User:NovemBot/Promote}} ~~~~. Person adding the template must be on the allowlist.
- Task 7 - Update User:Amalthea/RfX/RfA count every 30 minutes with an updated open RFA count.

I have some AWB, one time run bot tasks as well, but those are not documented here.

### PHP + Toolforge bot tutorial

I found the learning curve for Toolforge pretty hard. I've written a tutorial at

https://en.wikipedia.org/wiki/User:Novem_Linguae/Essays/Toolforge_bot_tutorial

### Toolforge jobs framework setup commands

```
toolforge jobs run task-a --command ./task-a.sh --image php8.2 --schedule "0 13 * * *" --emails onfailure

toolforge jobs run task-1 --command ./task-1.sh --image php8.2 --schedule "5 * * * *" --emails onfailure

toolforge jobs run task-7 --command ./task-7.sh --image php8.2 --schedule "22,52 * * * *" --emails onfailure
```

- Task A (userlist.js) runs daily at 5AM
- Task 1 (FGTC) runs hourly at :05
- Task 7 (RFA count) runs twice an hour at :22 and :52

### Webservice start

The webservice (which is helpful for manually running the bot / debugging) needs to be started one time, then keeps running until you turn it off. Start it with the below command. Keep in mind that the version of PHP it uses can get out of sync with what you're using for your cron jobs above. They are completely separate. So every once in awhile, you will want to update the version of PHP it uses to match your cron jobs.

```
become novem-bot
toolforge webservice status
toolforge webservice stop
toolforge webservice php8.2 start
```
