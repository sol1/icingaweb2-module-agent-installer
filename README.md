# The Agent Installer module for icingaweb2
Gives the user the ability to generate a new client configuration, SSL keys and installer with a single click

## Installation
 - Drop the agentinstaller folder in your icingaweb2 module folder (Ensure that the folder is name `agentinstaller`).
 - Add `www-data ALL=(ALL) NOPASSWD: /usr/sbin/icinga2, /usr/bin/makensis` to /etc/sudoers.
 - `# mkdir /var/www/icingaclient`
 - `# mkdir /var/www/icingaclient/builds`
 - `# mkdir /var/www/icingaclient/working-dir`
 - `# mkdir /var/www/icingaclient/server-configs`
 - `# chown www-data /var/www/icingaclient`
 - `# chown nagios:nagios /var/www/icingaclient/builds`
 - `# chown www-data:www-data /var/www/icingaclient/working-dir`
 - `# chown www-data:www-data /var/www/icingaclient/server-configs`
 - move `icinga2.conf` and `icinga2-setup-windows-child.nsis` to `/var/www/icingaclient/working-dir/` and chown them to `www-data:www-data`
 - Add `include_recursive "/var/www/icingaclient/server-configs"` to your `/etc/icinga2/icinga2.conf`
 - restart icinga2 `systemctl restart icinga2.service`
 - Enable in `Configuration > Modules > agentinstaller`

## Todo
 - Fix those ugly installation instructions.
 - Use another method to generate keys so we don't need to sudo anything and everything.
 - Add some configuration options.
 - Show a tree of clients, parents, zones and have the ability to redownload the installer.
 - Manage generated configurations.
