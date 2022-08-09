## Novem Linguae's Wikipedia bots

### Tasks

https://en.wikipedia.org/wiki/User:NovemBot

- Task A - Edit own userspace to keep accurate lists of users who have permissions, for use with User:Novem Linguae/Scripts/UserHighlighterSimple.
- Task B - Generate list of images that are possible copyright violations and publish it to User:Minorax/files.
- Task 1 - Promote featured and good topic candidates. Summon the bot to a FGTC voting page using {{User:NovemBot/Promote}} ~~~~. Person adding the template must be on the whitelist.

I have some AWB, one time run bot tasks as well, but those are not documented here.

### logininfo.php

Most of these bots require a file logininfo.php with the following format:

    <?php
    
    $wiki_username = '';
    $wiki_password = '';
    
    $http_get_password = '';