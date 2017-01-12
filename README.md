# OpsGenie module for icingaweb2
Gives the user the ability to go on call for their OpsGenie schedules directly from the icinga web interface.

## Features
 - One click to clock on an opsgenie schedule directly from the icinga web interface.
 - Uses current icinga account name and appends a customisable email domain.
 - Define custom clock on duration.

## Installation
Drop the opsgenie folder in your icingaweb2 module folder and enable in `Configuration > Modules > opsgenie`

## Todo
 - Set custom rotation name.
 - Adjust rotation duration directly from opsgenie tab.
 - Better handling of API errors.
