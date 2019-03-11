<?php

namespace Spam;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class Unlinker extends Command
{
    public function configure()
    {
        $this
            ->setName('unlink')
            ->setDescription('Stop serving the current site with SPAM')
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

        // remove from hosts
        $hosts = preg_replace("/127\.0\.0\.1\s+$sitename/", '', $hosts);
        file_put_contents(HOSTS_FILE, $hosts);

        // remove from vhosts
        $count = 0;
        $vhosts = preg_replace("/\#begin-spam-$sitename.+\#end-spam-$sitename\n/s", '', $vhosts, -1, $count);

        if ($count !== 0) {
            file_put_contents(VHOSTS_FILE, $vhosts);
            `sudo apachectl -k restart`;
            $output->writeln("<fg=green>$sitename successfully unlinked.</>");
        } else {
            $output->writeln("<fg=red>$sitename not found in vhosts.</>");
        }
    }
}