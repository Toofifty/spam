<?php

namespace Spam;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * Initial setup installer
 */
class Installer extends Command
{
    public function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install the SPAM environment');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $ex = !$input->getOption('verbose') ? 'exec' : function ($cmd) use ($output) {
            $output->writeln("<fg=yellow>$ $cmd</>");
            return system($cmd);
        };

        // update homebrew
        $output->writeln('<fg=cyan>Updating homebrew...</>');
        $ex('brew update');

        // install extra libs
        $output->writeln('<fg=cyan>Installing required libraries...</>');
        $ex('brew install openldap libiconv');

        // remove system apache
        $output->writeln('<fg=cyan>Disabling system Apache...</>');
        $ex('sudo apachectl stop');
        $ex('sudo launchctl unload -w /System/Library/LaunchDaemons/org.apache.httpd.plist 2>/dev/null');

        // check and create sbin
        if (!file_exists('/usr/local/sbin')) {
            $output->writeln('<fg=cyan>Creating /usr/local/sbin for homebrew</>');
            $ex('sudo mkdir /usr/local/sbin');
            $ex('sudo chown -R $(whoami):admin /usr/local/sbin');
        }

        // install brew apache
        $output->writeln('<fg=cyan>Getting Apache from homebrew...</>');
        $ex('brew install httpd');

        $output->writeln('<fg=cyan>Configuring Apache...</>');

        $httpdconf = file_get_contents(CONFIG_DIR . '/httpd.conf');
        // backup
        file_put_contents(CONFIG_DIR . '/httpd.conf.bk', $httpdconf);

        // set to port 80
        $httpdconf = str_replace('Listen 8080', 'Listen 80', $httpdconf);

        // enable vhosts
        $httpdconf = str_replace(
            '#Include /usr/local/etc/httpd/extra/httpd-vhosts.conf',
            'Include /usr/local/etc/httpd/extra/httpd-vhosts.conf',
            $httpdconf
        );
        // enable mod_rewrite and enable php
        $httpdconf = str_replace(
            '#LoadModule rewrite_module lib/httpd/modules/mod_rewrite.so',
            "LoadModule rewrite_module lib/httpd/modules/mod_rewrite.so\n" .
            'LoadModule php7_module /usr/local/opt/php@7.3/lib/httpd/modules/libphp7.so',
            $httpdconf
        );

        // set server name
        $httpdconf = str_replace(
            '#ServerName www.example.com:8080',
            'ServerName localhost',
            $httpdconf
        );

        // serve php files
        $httpdconf = str_replace(
            "<IfModule dir_module>\n    DirectoryIndex index.html\n</IfModule>",
            "<IfModule dir_module>\n    DirectoryIndex index.php index.html\n</IfModule>\n\n" .
            "<FilesMatch \.php$>\n    SetHandler application/x-httpd-php\n</FilesMatch>",
            $httpdconf
        );

        // htaccess allow override
        $httpdconf = str_replace(
            "    #\nAllowOverride None",
            "    #\nAllowOverride All",
            $httpdconf
        );

        $user = trim(`whoami`);
        // user & group
        $httpdconf = str_replace(
            "User _www\nGroup _www",
            "User $user\nGroup staff",
            $httpdconf
        );

        file_put_contents('/usr/local/etc/httpd/httpd.conf', $httpdconf);

        // disable system php
        $ex('sudo mv /usr/bin/php /usr/bin/php.sys');

        // install php, mysql
        $output->writeln('<fg=cyan>Installing PHP and MySQL</>');
        $ex('brew install php@' . LATEST_PHP . ' mysql@' . LATEST_MYSQL);
        $ex('brew link mysql@' . LATEST_MYSQL . ' --force');

        // hard link php7.3
        $ex('sudo ln -s $(readlink $(which php)) /usr/local/bin/php' . LATEST_PHP);

        // start httpd and mysql server
        $output->writeln('<fg=cyan>Starting services...</>');
        $ex('sudo apachectl -k restart');
        $ex('sudo mysql.server start');

        // check for errors
        if (strpos(`php --ini`, '/usr/local/etc/php/') === false) {
            $output->writeln('<error>PHP may not have been installed correctly.</error>');
        }

        if (strpos(`mysql -V`, LATEST_MYSQL . '') === false) {
            $output->writeln('<error>MySQL may not have been installed correctly.</error>');
        }

        // create default site at localhost
        unlink(SITE_HOME . '/index.html');
        file_put_contents(SITE_HOME . '/index.php', '// TODO: usage guide<?php phpinfo();');

        $output->writeln('<fg=green>Spam install complete.');
    }
}