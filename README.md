# Yet Another Wordpress Plugin Template (ya-wordpress-plugin-template) or (YAWPT)

Version 1.0.8

I used to hate WordPress but I have found a way that I can deliver value through WordPress Plugin ShortCodes.

I have scoured the internet for all of the tidbits of information and put them together in such a way that I
can stamp out plugins quite easily. I use this to deliver value to my clients without having to be responsible
for the whole site, content and the look and feel.

The usual conversation goes like this:

- Client: I would like a small website and it only has to do this (Insert Feature List)...
- Me: Let me stop you there. When you say small, does that mean that the budget is small?
- Client: Well yeah.
- Me: Are you guys comfortable with WordPress?
- Client: Yeah, that is what we have now.
- Me: Well I can just give you a WordPress Plugin that exposes a short-code. Then I can connect
though API to your main system so I can deliver (Insert Feature List). Then your web developer
can style the page however you like and just put the short code in the page where you want it.
- Client: Sounds great. What about the look and feel of what you develop?
- Me: I will create the initial templates, and there is a template editor built into the plugin
so your web developer can style the templates as well. It will be fast to develop, and I will provide
test environment for you to play before I install on your site.
- Client: Wow, Great. What a great programmer you are! (ok, I just added the last part for my ego)

As you know the time suck with any project is the UI/UX and the styling. If you can deliver a short-code
then you can drop as much as 70% of this from the budget. (This is a guess, but UI takes time)

Delivery on WordPress is easier IT as well. Sometime I would have to provide the IT, Servers, VM or
whatever the customer needs, but there are meny vendor's providing WordPress hoisting at
a reasonable price with backup options as well.

BTW, I do not claim that this is the best, it might even be the worst, but it is working well for me.
I am happy to get your input suggestions feedback etc.

## Techical Implementation Guide

### TL;DR

* run zip.sh
* install and activate plugin into wordpress
* Get an API Key from https://app.ipgeolocation.io/
* Go into settings and add API Key and API URL: https://api.ipgeolocation.io
* Create a page in your wordpress
* Add the following shortcode to the page
* `[yawpt-geoip][/yawpt-geoip]`
* Save and you are all done.

The above is an example of how you can use this plugin

TODO: Discuss the parts of the implementation and relate it back to the wordpress system.

This plugin includes a vendor directory from jackbooted.com that contains a https://laravel.com type
system (much more light weight). You could easily substitute your own framework to make things easier.
I like Jack because I wrote it (oh course).

Contact me if you need help. My email is somewhere in the code.


## Whats in the code

TODO: Put some explanations next to the different directories and what they do.
Created this with tree command

```
tree .
.
├── LICENSE
├── README.md
├── _private
│   ├── base_database.sql
│   └── jackbooted.sqlite
├── ajax.php
├── app
│   ├── App.php
│   ├── api
│   │   └── APIResult.php
│   ├── commands
│   │   ├── BaseCLI.php
│   │   ├── CLI.php
│   │   ├── CLIResult.php
│   │   ├── CronCLI.php
│   │   ├── CurrencyCLI.php
│   │   ├── InstallationCLI.php
│   │   ├── JobsCLI.php
│   │   └── MaintenanceCLI.php
│   ├── controllers
│   │   ├── BaseController.php
│   │   ├── CommonAjaxEndpoints.php
│   │   ├── CronController.php
│   │   ├── DebugController.php
│   │   ├── GeoIPController.php
│   │   ├── PartialDisplayController.php
│   │   ├── PartialEditController.php
│   │   ├── UpdateRatesController.php
│   │   ├── YAWPTController.php
│   │   └── YAWPTSettingsController.php
│   ├── libraries
│   │   ├── IPGeolocationAPI.php
│   │   ├── RestAPI.php
│   │   └── Validate.php
│   └── models
│       ├── JobLog.php
│       ├── MailData.php
│       ├── Mutex.php
│       ├── Options.php
│       ├── RegistrarLog.php
│       └── TblCurrencies.php
├── assets
│   ├── custom.js
│   ├── fontawesome-all.min.css
│   ├── overlay-spinner.svg
│   └── style.css
├── build.sh
├── config.env.php
├── config.php
├── doc
│   └── README.md
├── incver.php
├── jack.php
├── job.sh
├── languages
│   └── index.php
├── partials
│   ├── ErrMsg.php
│   ├── message_error.html
│   ├── message_success.html
│   ├── signup_index.html
│   ├── spinner.html
│   └── syserr.html
├── vendor
│   ├── defuse
│   │   └── 3rdparty-encryption-master
│   └── jackbooted
│       ├── 3rdparty
│       ├── admin
│       ├── config
│       ├── cron
│       ├── db
│       ├── forms
│       ├── html
│       ├── security
│       ├── time
│       └── util
├── webfonts
├── ya-wordpress-plugin-template.php
└── zip.sh
```
