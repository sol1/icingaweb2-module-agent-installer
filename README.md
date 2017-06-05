# The Agent Installer module for icingaweb2
Make a complete Icinga2 agent package: client configuration, SSL keys and
Windows .exe installer in a single click

## Dependencies

For now, this module is a front end for a shell script,
[icingaclient](https://github.com/sol1/icingaclient)
.

Follow the instructions in the icingaclient(1) README for installation.

Other prerequisite packages on Debian-based distributions:

 - php-curl
 - nsis


## Installation
Build and install the package with `make`:

`# make`  
`# make install`

Finally we have a security sensitive step: we have to give www-data some su
privileges. Allow www-data to run Icinga2-specific binaries as root in
/etc/sudoers:

`
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/icingaclient, /usr/sbin/icinga2,
/usr/bin/makensis
`

## Todo
 - Add functionality similar to tree(1) to visualise Icinga2 cluster
   architecture.
 - Dropdown list of zones instead of text field.
