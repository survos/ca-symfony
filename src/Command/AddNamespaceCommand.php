<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

class AddNamespaceCommand extends Command
{
    protected static $defaultName = 'app:add-namespace';

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('dir', InputArgument::OPTIONAL, 'Directory to process', "vendor/collectiveaccess/providence/app")
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'overwrite')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dir = $input->getArgument('dir');

        $finder = new Finder();
        $finder->files()->in($dir)->files()->name('*.php');

// check if there are any search results
        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $absoluteFilePath = $file->getRealPath();
                
                if ($php = $this->addNamespace($file))
                {
                    if (file_put_contents($absoluteFilePath, $php)) {
                        $io->success("File $absoluteFilePath written.");
                    }
                }
                
                $fileNameWithExtension = $file->getRelativePathname();

                // ...
            }
            // ...
        }



        if ($input->getOption('overwrite')) {
            // ...
        }

        $io->success('Finished ' . __CLASS__);

        return Command::SUCCESS;
    }
    
    private function addNamespace(\SplFileInfo $file): ?string
    {
        $absoluteFilePath = $file->getRealPath();
        
        $php = file_get_contents($absoluteFilePath);
        if ( preg_match('|app|', $file->getPath()) ) {
            // hack! 
            $namespace = str_replace('vendor/collectiveaccess/providence/', '', $file->getPath());
            $namespace = str_replace('app/lib','CA/lib', $namespace);
            $namespace = str_replace('/','\\', $namespace);
            
            $autoloadMap = [
                '__CA_LIB_DIR__' => 'CA\lib',
                '__CA_APP_DIR__' => 'CA',
                '__CA_MODELS_DIR__' => 'CA\models',
                '__CA_BASE_DIR__' => 'CA\Base',
                '__CA_THEME_DIR__' => 'CA\Themes',
                '$vs_app_plugin_dir' => 'CA\Plugins',
                '$vs_base_widget_dir' => 'CA\Widgets',
                '$vs_base_refinery_dir' => 'CA\Refinery',
                '$this->ops_theme_plugins_path' => 'CA\Themes\Plugins',
                "\$this->opo_config->get('application_plugins')" => 'CA\Plugins',
                '$this->ops_application_plugins_path' => 'CA\Plugins',
                '$this->ops_controller_path' => 'CA\Controller',
                'self::$opo_config->get("views_directory")' => 'CA\Views'
            ];
            
            // really we just want the part between the namespace and the start of the class
            if (preg_match('/<\?php.*?(class|function)/ms', $php, $mm)) {

                $oldHeader = $mm[0];

//                dd($header);
                $header = preg_replace_callback("/(include|require)_once\((.*?)\.[\"']\/(.*?).php[\"']\)/",
                    function($m) use ($autoloadMap) {
                        $prefix=$m[2];
                        if (array_key_exists($prefix, $autoloadMap)) {
                            return sprintf("use %s\%s", $autoloadMap[$prefix], str_replace('/', '\\', $m[3])); 
                        } else {
                            return $m[0]; // dont replace
                        }
                    }, $oldHeader);
//                dd($header);
                // get rid of the old namespace
                $header = preg_replace('/namespace [^ ]+;/', '', $header);
                // add the new one
                $header = str_replace("<?php", "<?php\n\nnamespace $namespace;\n", $header);

                // replace the header
                $php = str_replace($oldHeader, $header, $php);
            }
//            foreach ($autoloadMap as $constant=>$prefix) {
//            }

//            $php = preg_replace("|require_once\(__CA_LIB_DIR__.[\"']/(.*?).php|", "use CA\Lib\$1", $php);
//            dd(substr($php, 0, 1512));

            return $php;
            if (file_put_contents($absoluteFilePath, $php)) {
                
            }
            dd($php);

            dd($namespace);
            // missing the namespace.  Add it 
            $fileNameWithExtension = $file->getRelativePathname();
            dd($file->getPath(), $file->getPathname(), $file->getPathInfo());
            
        } else {
            return null;
        }


    }
}
