<?php

namespace App\Command;

use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DiffCommand extends Command
{
    public static $i = 0;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'diff';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Shows difference list')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Shows difference list');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $rootPath2 = '';
        if (is_file('manifest.php')) {
            $rootPath = 'custom';

            if (is_dir('SugarModules')) {
                $rootPath2 = 'SugarModules';
            } elseif (is_dir('modules')) {
                $rootPath2 = 'modules';
            }
        } elseif (is_file('src/manifest.php')) {
            $rootPath = 'src/custom';

            if (is_dir('src/SugarModules')) {
                $rootPath2 = 'src/SugarModules';
            } elseif (is_dir('src/modules')) {
                $rootPath2 = 'src/modules';
            }
        }

        $deploymentPath = null;
        if (is_file('.sucode')) {
            $cache = file_get_contents('.sucode');
            $config = json_decode($cache, true);
            $deploymentPath = $config['deploymentPath'];
        }

        if ('src/modules' == $rootPath2) {
            $deploymentPath2 = $deploymentPath . '/modules';
        }
        if ('src/SugarModules' == $rootPath2) {
            $deploymentPath2 = $deploymentPath . '';
        }

        $deploymentPath = $io->ask('Where is you deployment folder?', $deploymentPath);

        $config['deploymentPath'] = $deploymentPath;
        file_put_contents('.sucode', json_encode($config));
        $deploymentPath2 = $deploymentPath . '/modules';
        $deploymentPath = $deploymentPath . '/custom';

        // Will exclude everything under these directories
        $exclude = ['.git', 'otherDirToExclude', 'scripts', 'manifest.php'];

        self::specificRun($rootPath, $deploymentPath, $exclude);
        if ($rootPath2) {
            self::specificRun($rootPath2, $deploymentPath2, $exclude);
        }

        return Command::SUCCESS;
    }

    public static function specificRun($rootPath, $deploymentPath, $exclude)
    {
        $filter = function ($file, $key, $iterator) use ($exclude) {
            if ($iterator->hasChildren() && !in_array($file->getFilename(), $exclude)) {
                return true;
            }

            return $file->isFile();
        };

        $innerIterator = new RecursiveDirectoryIterator(
            $rootPath,
            RecursiveDirectoryIterator::SKIP_DOTS
        );

        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator($innerIterator, $filter)
        );

        foreach ($iterator as $pathname => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();

                $relativePath = substr($pathname, strlen($rootPath) + 1);

                $fileOnDeploySide = $deploymentPath . '/' . $relativePath;

                $output = [];
                $status = 0;

                $command = 'diff --brief -N ' . $filePath . ' ' . $fileOnDeploySide;

                exec($command, $output, $status);
                if ($status) {
                    echo self::$i . ') ' . $relativePath . ' does not match' . PHP_EOL;
                    echo $command . PHP_EOL . PHP_EOL;
                    ++self::$i;
                }
            }
        }
    }
}
