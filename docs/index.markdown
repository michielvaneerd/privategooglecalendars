---
# Feel free to add content and custom Front Matter to this file.
# To modify the layout, see https://jekyllrb.com/docs/themes/#overriding-theme-defaults

layout: home
---

Functionality:

- Display multiple public and private Google calendars.
- Available as widget, shortcode and Gutenberg block.
- As it uses <a href="https://fullcalendar.io/" target="blank">FullCalendar</a> for displaying the calendars, almost all of the FullCalendar configuration options can be used for this plugin as well.

Below you find the documentation.

# Installation and configuration

In short these are the steps to get started:

1. Install the plugin.
2. Configure a Google project in the Google API Console and download the client secret JSON file.
3. Upload this client secret JSON file in the Private Google Calendars settings page.
4. Select the calendars you want to allow to be used on your Wordpress site.
5. Use the widget, shortcode and Gutenberg block to display one or more of the selected calendars on your site or add some public calendars.

## Configure a Google project

1. Create a project in the Google Cloud console. See <a href="https://developers.google.com/workspace/guides/create-project" target="_blank">this Google page</a> for information.
2. Enable the _Google Calendar API_. See <a href="https://developers.google.com/workspace/guides/enable-apis" target="_blank">this Google page</a> for information.
3. Create a new _OAuth client ID_<sup><a href="#note1">1</a></sup> credential in the credentials setting. See <a href="https://developers.google.com/workspace/guides/create-credentials" target="_blank">this Google page</a> for information.
4. Choose _Webapplication_ for the type.
5. Add the _authorised redirect URI_. Make sure to copy and paste the redirect URI you see in the Private Google Calendars settings page in the orange box __exactly__ as displayed, otherwise _it will not work_.
6. Click on _Create_ and download the client secret JSON file, because this file must be uploaded to the Wordpress plugin.

<em id="note1">Note 1</em>: This is the preferred way of configuring access to Google calendars and the only <a href="https://developers.google.com/calendar/auth#AboutAuthorization" target="_blank">officially supported</a> way. However up until now Google seems to support the use of and API key for access to _public_ Google calendars. But the use of an _OAuth client ID_ credential over and _API key_ is strongly recommended even for public calendars.

## Setting up the plugin

After you have successfully configured your Google project, proceed to the Private Google Calendars settings page that can be found under the _Settings_ menu item in the Wordpress admin area.

1. Under _Setting up private calendar access_, upload the client secret JSON file and save it<sup><a href="#note2">2</a></sup>.
2. Click the the _Authorize_ button that appears.
3. You are redirected to Google.
4. If you have more than one Google account, you will be presented a list of Google accounts. Make sure to select the same account as your Google project.
5. It's possible that you will now see a warning screen with a message saying this app isn't verified. Continue by clicking the link in the _Advanced_ section<sup><a href="#note3">3</a></sup>.
6. Allow access to the Google calendars.
7. You are redirected back to the Private Google Calendars settings page in the Wordpress admin area.
8. You'll see all calendars that are linked to the Google account.
9. Select the calendars you want to allow to be displayed on your website. This is the time to disallow calendars you never want to be displayed on your website, for example your personal Google Calendar.

<em id="note2">Note 2</em>: If you want to use an API key, you can skip all of these steps and just enter the API key. But note that this is not officially supported by Google, only allows access to public calendars and can be discontinued by Google at anytime. Use it at your own risk.

<em id="note3">Note 3</em>: This warning screen is only displayed when you authorize the project to access the Google calendars. Users of your site won't be affected by this. However you can get rid of this screen by submitting your Google project to the verify process.

# Basic usage

This plugin provides a widget, shortcode and a Gutenberg block. All of them have the same functionality.

## Plugin configuration vs FullCalendar configuration

There are two kinds of configuration you can make:

1. Plugin configuration, like which calendars you want to display, position of filter and event popup configuration.
2. FullCalendar configuration like available views (like dayGridMonth or listWeek) and location of buttons (like next, previous, today). These configuration options are mapped directly to the FullCalendar configuration options.
For examples see the <a href="/examples">examples page</a>.

## Widget

The widget is called _Private Google Calendars_ and can be configured by a user interface and a textfield where you can specify FullCalendar configuration options, like the available views, the placement of the buttons, the locale, the timezone, etc.

## Shortcode

The shortcode is `[pgc]` and can be configured by specifying attributes. The FullCalendar configuration options can be specified with attributes as well but have some specific formatting:

### FullCalendar configuration options for the shortcode

To _translate_ FullCalendar options to shortcode attributes, follow the 2 rules:

1. Nested properties should be written with a dash “-“.
2. The camelCase properties should be written with an underscore “_”.

For example see the below FullCalendar configuration:

    {
        "eventColor": "black",
        "eventTextColor": "white",
        "header": {
            "center": "title",
            "right": "today prev,next",
            "left": "dayGridMonth,timeGridWeek,listWeek"
        }
    }

The shortcode equivalent is this:

    [pgc event_color="black" event_text_color="white" header-center="title" header-right="today prev,next" header-left="dayGridMonth,timeGridWeek,listWeek"]

## Gutenberg block

The Gutenberg block is called _Private Google Calendars_ and can be configured by an user interface and a textfield where you can specify FullCalendar configuration options, like the available views, the placement of the buttons, the locale, the timezone, etc.

# Private Google Calendars settings page

Explanation of configuration options on the Private Google Calendar settings page which can be found under the _Settings_ menu item.

## General settings

### Cache time in minutes

Each time this plugin displays a Google calendar a request to Google is made. You can specify the number of minutes the Google response should be cached. This can greatly improve the performance.

## Public calendar settings

### API key

You can enter your Google project API key in this field. Note that this _not_ officially supported by Google and the recommendation is to setup _Client-ID OAuth_ credentials instead.

### Public calendars

If you display public calendars that you haven't added to your Google account a default color is used and the calendar ID is used as the title. You can specify the color and title for each public calendar you are using.

## Private calendar settings

### Select calendars	

These calendars are attached to your Google account. Select the calendars that you want to allow to be used on your site.

## Tools

### Update calendars

This will update your calendarlist displayed under _Private calendar settings / Select calendars_. Use this if you add or remove a calendar to or from your account.

### Get colorlist

Download the default Google colorlist. This will be used when you have custom colors for calendar events. You only have to download this list once.

### Verify

Does some verification if your _Client-ID OAuth_ credential in your Google project is still OK.

### Remove cache

Removes the cache. Use this if you cache your events but you added new events to the calendar and want them to be displayed immediately.

### Revoke access

Revokes access to your Google account.

### Remove plugin data

Removes all plugin data. Use _Revoke access_ first before using this option. Otherwise you have to manually revoke access in the Google <a href="https://myaccount.google.com/permissions" target="_blank">Permissions</a> page.

# Options for wp-config.php

You can set the following options in _wp-config.php_:

- `PGC_EVENTS_MAX_RESULTS` (default 250) – Maximum number of events that Google returns.
- `PGC_EVENTS_DEFAULT_TITLE` (default "") – If the title of an event is empty, which can happen when the status is _busy_, the value of this option is displayed instead.

# FAQ

## W3 Total Cache

If you use W3 Total Cache and have minify JS enabled, make sure that you do one of the following:

Choose Combine only in the Minify settings.

OR

Enter the below files in the Never minify the following JS files textbox. Make sure you add the full path to these files from the root of your installation, so if your WordPress website is located in the wordpress directory, this will be:

    wordpress/wp-content/plugins/private-google-calendars/lib/fullcalendar4/core/main.min.js
    wordpress/wp-content/plugins/private-google-calendars/lib/fullcalendar4/core/locales-all.min.js
    wordpress/wp-content/plugins/private-google-calendars/lib/fullcalendar4/list/main.min.js
    wordpress/wp-content/plugins/private-google-calendars/lib/fullcalendar4/timegrid/main.min.js

## No refresh token error

Only the first time you allow this plugin to access your Google calendars a refresh token is given to you. If you see an error like 'Your refresh token is missing!', this ususally means that you used the client secret JSON file on multiple websites.
To fix this error, you have to go to the Google <a href="https://myaccount.google.com/permissions" target="_blank">Permissions</a> page to revoke the access to the Google calendars and remove the plugin. After that you can install the plugin and upload the client secret JSON file again.

## Rate limit exceeded error

This usually means you have a wrong API key.