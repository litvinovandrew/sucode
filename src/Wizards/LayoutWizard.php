<?php

namespace App\Wizards;

use App\Templates\Template;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LayoutWizard
{
    public static function run(InputInterface $input, OutputInterface $output)
    {
        $customPath = Helper::getCustomPath();
        $manifestPath = Helper::getManifestPath();

        $io = new SymfonyStyle($input, $output);
        $file = new File($io);


        if (!$customPath) {
            $io->writeln('manifest.php not found. Please run the command from the src directory');
            exit;
        }

        //:template-layout-name
        $layoutName = $io->ask('Enter layout name ', 'my-test-layout-' . Helper::generateRandomString(4));

        $layoutName = str_replace('_', '-', $layoutName);
        $layoutName = str_replace(' ', '-', $layoutName);
        $nameParts = explode('-', $layoutName);

        $nameParts = array_map(function ($item) {
            return ucfirst($item);
        }, $nameParts);

        $lbl_layout_name = implode(' ', $nameParts);

        $nameParts = array_map(function ($item) {
            return strtoupper($item);
        }, $nameParts);

        $LBL_LAYOUT_NAME = 'LBL_' . implode('_', $nameParts) . '_NAME';
        $LBL_LAYOUT_DESC = 'LBL_' . implode('_', $nameParts) . '_DESC';

        //:template-layout-desc
        $lbl_layout_desc = $io->ask('Enter layout description ', 'My Sample Layout');

        //1 prepare layout files
        $file->mkdir("$customPath/clients/base/layouts/$layoutName");
        $content = Template::renderLayoutDef([
            ':template-layout-name' => $layoutName,
        ]);
        $file->put_content("$customPath/clients/base/layouts/$layoutName/$layoutName-def.php", $content);

        //2 prepare views files
        $file->mkdir("$customPath/clients/base/views/$layoutName-view");
        $content = Template::renderLayoutViewJs([
            ':template-layout-name' => $layoutName,
        ]);
        $file->put_content("$customPath/clients/base/views/$layoutName-view/$layoutName-view.js", $content);

        $content = Template::renderLayoutViewHbs([
            ':template-layout-desc' => $lbl_layout_desc,
        ]);
        $file->put_content("$customPath/clients/base/views/$layoutName-view/$layoutName-view.hbs", $content);

        $processAdmin = $io->ask('Do you want to create Admin link for this layout (y/n)?', 'n');
        if ('y' != $processAdmin) {
            return;
        }

        //3 Administration folder
        $file->mkdir("$customPath/Extension/modules/Administration/");
        $file->mkdir("$customPath/Extension/modules/Administration/Ext");
        $file->mkdir("$customPath/Extension/modules/Administration/Ext/Administration");
        $file->mkdir("$customPath/Extension/modules/Administration/Ext/Language");

        $content = Template::renderAdminLayoutDef([
            ':template-layout-name' => $layoutName,
            ':LBL_LAYOUT_NAME' => $LBL_LAYOUT_NAME,
            ':LBL_LAYOUT_DESC' => $LBL_LAYOUT_DESC,
        ]);
        $file->put_content("$customPath/Extension/modules/Administration/Ext/Administration/$layoutName.php", $content);

        $content = Template::renderAdminLayoutLang([
            ':lbl_layout_name' => $lbl_layout_name,
            ':lbl_layout_desc' => $lbl_layout_desc,
            ':LBL_LAYOUT_NAME' => $LBL_LAYOUT_NAME,
            ':LBL_LAYOUT_DESC' => $LBL_LAYOUT_DESC,
        ]);
        $file->put_content("$customPath/Extension/modules/Administration/Ext/Language/en_us.$layoutName.php", $content);
        $file->printSummary();
    }
}
