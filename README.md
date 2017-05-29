# The Agent Installer module for icingaweb2
Make a complete Icinga2 agent package: client configuration, SSL keys and
Windows .exe installer in a single click

## Dependencies
deb packages:
 - php-curl
 - nsis

## Installation
Build and install the package with `make`:

`
 # make
 # make install
`

## Todo
 - Add functionality similar to tree(1) to visualise Icinga2 cluster
   architecture.
 - Dropdown list of zones instead of text field.
