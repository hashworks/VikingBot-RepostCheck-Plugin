VikingBot-RepostCheck-Plugin
============================

A VikingBot plugin which checks for reposts of urls and credits the original poster.


You just need to set the hosts to check for in your config, f.e.:
```
$config['plugins']['repostCheck']['hostsToCheck'] = array(
	"img.pr0gramm.com",
	"full.pr0gramm.com",
	"imgur.com",
	"gfycat.com"
);
```

That's it!
Here is an example of the plugin in action:
```
<%hashworks> http://i.imgur.com/2kFWmiR.jpg
<+goodguy> <%hashworks> http://i.imgur.com/2kFWmiR.jpg <-- Nice!
<+badguy> Hey guys, check this out: http://i.imgur.com/2kFWmiR.jpg
<+VikingBot> I smell repost! This was posted by hashworks 7 minutes ago!
```
As you can see the one giving credit doesn't trigger VikingBot.
