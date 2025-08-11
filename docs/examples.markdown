---
layout: page
title: Examples
permalink: /examples/
---

The Private Google Calendars plugin provides a widget, shortcode and Gutenberg block. Each one has the same functionality but has to be configured differently. The configurations of the widget and Gutenberg block are almost the same as this can be done with a user interface and speaks mostly for itself. The shortcode configuration differs a lot because it has to be specified as attributes of the shortcode.


Many of the below examples are really FullCalendar options. Currently this plugin uses the v4 version of FullCalendar. If you can't find your answer here, you can have a look at <a href="https://fullcalendar.io/docs/v4#toc" target="_blank">FullCalendar documentation</a> or send a message to the <a href="https://wordpress.org/support/plugin/private-google-calendars/" target="_blank">Wordpress forum</a>.

## Show all private calendars

Simply using `[pgc]` will display all of the private calendars you've selected in the settings page.

## Show some specific calendars

Enter the ID of the calendars you wish to display. You can see the calendar ID for your private calendars on the settings page. The ID of public calendars can be looked up in the Google calendar interface.

    [pgc calendarids="calendar1,calendar2"]

## Hide past or future events

Use the `hidepassed` and `hidefuture` attributes and specify the number of days.
For example to hide all passed events and show events for the next 10 days:

    [pgc hidepassed="0" hidefuture="10"]

## Calendar view, title and buttons

The following views are available:
- `dayGridMonth` - a monthly calendar
- `timeGridWeek` - a weekly calendar with a vertical time grid
- `listWeek` - a weekly list of events
- `listDay` - a daily list of events

For example if you only want to display the `dayGridMonth` and `listWeek` views and make the `listWeek` the default:

    [pgc default_view="listWeek" header-left="dayGridMonth,listWeek" header-right="prev,next today" header-center="title"]

If you use the widget or Gutenberg block, you add this to the _FullCalendar config_ textfield:

    {
        "header": {
            "left": "prev,next today",
            "center": "title",
            "right": "dayGridMonth,listWeek"
        },
        "defaultView": "listWeek"
    }

## Configure the event popup

The popup is disabled by default. Below you find all the properties you can set:

    [pgc eventpopup="true" eventlink="true" eventdescription="true" eventattachments="true" eventattendees="true" eventlocation="true" eventcreator="true" eventcalendarname="true"]


## Limit the events

To display all events instead of showing the "+2 more" text:

    [pgc event_limit="false"]

If you use the widget or Gutenberg block, you add this to the _FullCalendar config_ textfield:

    {
        "eventLimit": false
    }

## Change locale

The locale is used for things like translation of texts, starting day of the week and the time format. You can set it explicitly:

    [pgc locale="nl-nl"]

If you use the widget or Gutenberg block, you add this to the _FullCalendar config_ textfield:

    {
        "locale": "nl-nl"
    }

## Change timezone

If you see that your events have strange times, you may set the timezone:

    [pgc time_zone="America/New_York"]

If you use the widget or Gutenberg block, you add this to the _FullCalendar config_ textfield:

    {
        "timeZone": "America/New_York"
    }

## Change the first of the week

Normally you won't have to set this, as you better can use the `locale` option. But if you want you can set it.
The counr goes from Sunday = 0 until Saturday = 6.

So to start on Monday:

    [pgc first_day="1"]

If you use the widget or Gutenberg block, you add this to the _FullCalendar config_ textfield:

    {
        "firstDay": 1
    }

It's also possible to specify today as the first day:

    [pgc first_day="+0"]

Or yesterday:

    [pgc first_day="-1"]

Or tomorrow:

    [pgc first_day="+1"]

And so on.

## Format dates

You can format the following properties: `columnHeaderFormat` (default day names displayed for month and week views), `eventTimeFormat` (date displayed in the events) and `titleFormat` (title displayed in the header).
FullCalendar uses Moment.js to format the date. See the <a href="https://momentjs.com/docs/#/displaying/format/" target="_blank">Moment.js website</a> for information about the formatting options.
For example the below configuration will make the event date displayed as: “1st Jan”

    [pgc event_time_format="Do MMM"]

If you use the widget or Gutenberg block, you add this to the _FullCalendar config_ textfield:

    {
        "eventTimeFormat": "Do MMM"
    }

## Format date on titlebar

See <a href="https://fullcalendar.io/docs/v4/titleFormat" target="_blank">https://fullcalendar.io/docs/v4/titleFormat</a>.

For example if you want to display only the title as Tuesday, 12th November:

If you use the widget or Gutenberg block, you add this to the _FullCalendar config_ textfield:

    {
        "header": {
            "left": "title",
            "center": "",
            "right": ""
        },
        "titleFormat": "D MMMM YYYY dddd"
    }

Shortcode:

    [pgc header-left="title" header-center="" header-right="" title_format="D MMMM YYYY dddd"]

Instead of explicitly specifying the date format `D MMMM YYYY dddd` you can also use localized versions, for example: `LL`.

## Format date in weeklist

See <a href="https://fullcalendar.io/docs/v4/listDayFormat" target="_blank">https://fullcalendar.io/docs/v4/listDayFormat</a> and <a href="https://fullcalendar.io/docs/v4/listDayAltFormat" target="_blank">https://fullcalendar.io/docs/v4/listDayAltFormat</a>.

If you use the widget or Gutenberg block, you add this to the _FullCalendar config_ textfield:

    {
        "listDayAltFormat": false,
        "list_day_format": "D MMMM YYYY dddd"
    }

Shortcode:

    [pgc list_day_alt_format="false" list_day_format="D MMMM YYYY dddd"]

## Uncheck calendars in filter

By default all calendars you want to show are checked in the filter. If you want some or no calendars to be checked by default, you can add them to the _Unchecked calendar IDs_ option. Just enter the calendar IDs separated by a comma. The calendar ID is displayed in the Private Google Calendars settings page (just beneath the calendar title) if you use private calendars. If you display public calendars, you already know the calendar ID.

In the shortcode you use:

    [pgc uncheckedcalendarids="calendar1,calendar2"]

## Custom colors for events

In the Google calendar it’s possible to specify custom colors for events. By default all events have the same color as the calendar they’re in. If you specify custom colors for events, this plugin will display them but first you have to download the Google colors. This can be done by clicking the _Update colorlist_ button in the settings page. This will download the Google colors so this plugin knows what colors to use. You only have to do this one.

## Show weeknumbers

For shortcode:

    [pgc week_numbers="true"]

If you use the widget or Gutenberg block, you add this to the _FullCalendar config_ textfield:

    {
        "weekNumbers": true
    }

## Specify the valid period range for events

If you want to restrict the period range, you can do:

    [pgc valid_range-start="2020-08-01" valid_range-end="2020-08-31"]

If you use the widget or Gutenberg block, you add this to the _FullCalendar config_ textfield:

    {
        "validRange": {
            "start": "2020-08-01",
            "end": "2020-08-31"
        }
    }

## Hide filter or change position

For shortcode:

    [pgc filter="false"]

If you use the Gutenberg block or widget you can select the preferred option.