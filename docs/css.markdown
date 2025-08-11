---
layout: page
title: CSS
permalink: /css/
---

Here are some ways to customize the style of the calendars.

## Change color of events per calendar

<a href="https://wordpress.org/support/topic/how-to-add-custom-css-on-calendar-by-calendar-basis/" target="_blank">Forum example</a>

For example add a hover effect to events for calendar CALENDARID:

    .pgc-calendar-wrapper a.fc-event[data-calendarid="CALENDARID"]:hover {
        color: yellow;
        background-color: black !important;
    }

## Control width and height of event popup

By default the popup will be as heigh as the content. This can cause the popup to be off the screen. You can control the height and width of the popup with the following CSS:

    .tippy-box {
        max-height:200px;
        overflow-y: auto;
        max-width: 500px !important;
    }

## Change the checkmark in the filter

By default the ✔ is used. You can change it with the following CSS:

    .pgc-calendar-filter input[type=checkbox] + label span:before {
        content: "✗"
    }

## Change button colors

    .fc-button.fc-button-primary {
        background-color:green;
        color:yellow;
    }