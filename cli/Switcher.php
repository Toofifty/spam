<?php

namespace Spam;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class Switcher extends Command
{
    public function configure()
    {
        $this
            ->setName('use')
            ->setDescription('Set PHP version')
            ->addArgument('version', InputArgument::REQUIRED, 'Tagged PHP version');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $ex = !$input->getOption('verbose') ? 'exec' : function ($cmd) use ($output) {
            $output->writeln("<fg=yellow>$ $cmd</>");
            return system($cmd);
        };

        $version = $input->getArgument('version');
        if (strpos($version, '@') !== false) {
            $version = explode('@', $version)[1];
        }

        if ($version === 'latest') {
            $version = LATEST_PHP;
        }

        if (!$version || (intval($version) !== 5 && intval($version) !== 7)) {
            $output->writeln("<fg=red>Version must be specified as major.minor: '7.3', or as a tag: 'php@7.3'</>");
            return;
        }

        $preexisting = true;

        $matches = [];
        preg_match("/PHP (\d.\d+)/", `php -v`, $matches);
        $previous = $matches[1];

        if ($previous === $version) {
            $output->writeln("<fg=red>Already using php@$version</>");
            return;
        }

        // get it if it doesn't already exist
        if (!file_exists("/usr/local/bin/php$version")) {
            $preexisting = false;
            $output->writeln('<fg=green>Fetching from homebrew...</>');

            if (floatval($version) <= 7.0) {
                $ex('brew tap exolnet/homebrew-deprecated');
            }

            $ex("brew install php@$version");

            if (!$ex("brew list | grep php@$version")) {
                $output->writeln("<fg=red>Could not find php@$version</>");
                return;
            }
        }

        $major = intval($version);
        $httpdconf = file_get_contents(CONFIG_DIR . '/httpd.conf');

        // switch php in httpd.conf
        $httpdconf = preg_replace(
            "/LoadModule php\d_module \/usr\/local\/opt\/php@\d\.\d\/lib\/httpd\/modules\/libphp\d\.so/",
            "LoadModule php{$major}_module /usr/local/opt/php@$version/lib/httpd/modules/libphp$major.so",
            $httpdconf
        );

        file_put_contents(CONFIG_DIR . '/httpd.conf', $httpdconf);

        // switch php in path
        $ex("brew unlink php@$previous");
        $ex("brew link --force --overwrite php@$version");
        if (!$preexisting) {
            // hardlink
            $ex("sudo ln -s $(readlink $(which php)) /usr/local/bin/php$version");
        }

        $ex('sudo apachectl -k restart');
        if ($ex('which php')) {
            $output->writeln("<fg=green>Now using php@$version</>");
        } else {
            $output->writeln("<fg=red>Unknown error when switching PHP versions.</>");
            $output->writeln("<fg=red>Reverting to latest PHP</>");
            $ex("brew unlink php@$version");
            $ex("brew link --force --overwrite php");
        }
    }
}