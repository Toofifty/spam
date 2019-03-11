<?php

namespace Spam;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class Linker extends Command
{
    public function configure()
    {
        $this
            ->setName('link')
            ->setDescription('Serve the current site with SPAM')
            ->addArgument('sitename', InputArgument::OPTIONAL, 'Site name to use');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $hosts = file_get_contents(HOSTS_FILE);
        $vhosts = file_get_contents(VHOSTS_FILE);

        // backups
        if (!file_exists(HOSTS_FILE . '.bk')) {
            file_put_contents(HOSTS_FILE . '.bk', $hosts);
            $output->writeln('<fg=green>Hosts file backed up to ' . HOSTS_FILE . '.bk');
        }
        // also clears existing vhosts (dummies)
        if (!file_exists(VHOSTS_FILE)) {
            file_put_contents(VHOSTS_FILE . '.bk', $vhosts);
            file_put_contents(VHOSTS_FILE, '');
            $output->writeln('<fg=green>VHOSTS file backed up to ' . VHOSTS_FILE . '.bk');
            $vhosts = '';
        }

        $sitename = ($input->getArgument('sitename') ?? trim(`basename $(pwd)`)) . TLD;
        $cwd = trim(`pwd`);

        // add to hosts file
        if (!preg_match("/127\.0\.0\.1\s+$sitename/", $hosts)) {
            file_put_contents(HOSTS_FILE, $hosts . "\n127.0.0.1\t$sitename\n127.0.0.1\twww.$sitename");
        }

        if (!preg_match("/ServerName\s+$sitename/", $vhosts)) {
            $entry = <<<EOT
#begin-spam-$sitename
<VirtualHost *:80>
    DocumentRoot "$cwd"
    ServerName $sitename
    ServerAlias www.$sitename
    <Directory "$cwd">
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
#end-spam-$sitename

EOT;
            file_put_contents(VHOSTS_FILE, $vhosts . "\n\n" . $entry);
            `sudo apachectl -k restart`;
            $output->writeln("<fg=green>$sitename successfully linked.</>");
        } else {
            $output->writeln("<fg=red>$sitename already linked.</>");
        }
    }
}