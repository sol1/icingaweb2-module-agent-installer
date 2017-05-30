# The Agent Installer module for icingaweb2
Make a complete Icinga2 agent package: client configuration, SSL keys and
Windows .exe installer in a single click

## Dependencies
deb packages:
 - php-curl
 - nsis

## Installation
Build and install the package with `make`:

` # make
  # make install `

Finally we have a security sensitive step: we have to give www-data some su
privileges:

`
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/icingaclient, /usr/sbin/icinga2,
/usr/bin/makensis
`

## Todo
 - Add functionality similar to tree(1) to visualise Icinga2 cluster
   architecture.
 - Dropdown list of zones instead of text field.
