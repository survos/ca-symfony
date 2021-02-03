<?php

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

class FixCommand extends Command
{
    protected static $defaultName = 'app:fix';
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, string $name = null)
    {
        parent::__construct($name);
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('dir', InputArgument::OPTIONAL, 'Directory to process', "vendor/collectiveaccess/providence/app")
            ->addOption('expand-classes', null, InputOption::VALUE_NONE, 'expand files that have multiple classes')
            ->addOption('add-namespaces', null, InputOption::VALUE_NONE, 'add namespace to top of file')
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
                
                $output->writeln(
                    $absoluteFilePath,
                    OutputInterface::VERBOSITY_VERBOSE
                );
//                $this->logger->warning($absoluteFilePath);
                
                
                if ($php = file_get_contents($absoluteFilePath))
                {
                    $newPhp = $php;
                    // check to make sure that the filename matches the class name.  If not, create new files. 
                    if (preg_match_all('/\n(class ([^ ]+)[^\n]*\n{(.*?)\n})/', $php, $mm, PREG_SET_ORDER)) {
                        foreach ($mm as $idx => $m) 
                        {
                            $className = $m[2];
                            if ($className <> $file->getFilenameWithoutExtension()) {
                                // if exactly one, rename php.  Otherwise, create classes and rename to .txt or .orig
                                if ($idx === 0) {
//                                    $this->logger->alert(sprintf("Renaming %s to %s", $file->getFilename(), $className.'.php'));
                                    
                                } else {
                                    $newFilename = $file->getPath() . sprintf('/%s.php', $className);
                                    $this->logger->alert(sprintf("creating $newFilename"));
                                    
                                    file_put_contents($newFilename, '<?php' . "\n\n" . $m[1]);
                                    $newPhp = str_replace($m[1], "// see $newFilename for class $className\n", $newPhp);
//                                    $this->addNamespace($file);
                                    // probably a related exception class
                                    $this->logger->alert(sprintf("Extra class in %s (%s)", $absoluteFilePath, $className));
                                    
                                    if ($newPhp <> $php) {
                                        file_put_contents($absoluteFilePath, $newPhp);
                                    }
                                    dd($m);
                                    if (count($mm) > 2) {
                                        dd($mm);
                                    }
                                    
                                    dd($className, $file->getFilename());
                                }
                            }

                        }
//                        if (count($mm) == 1) {
//                            // check that class name matches
//                            $className = $mm[1];
//                            if ($className <> $file->getPathname()) {
//                                dd($className, $file->getPathname());
//                            }
//                            dd($mm);
//                        } else {
//                            dd($mm);
//                            
//                        }
                    } else {
                        if ($output->isVerbose()) {
                            $this->logger->warning("No classes in ", [$absoluteFilePath]);
                        }

                    }
                    if ($input->getOption('add-namespaces')) {
                        if (file_put_contents($absoluteFilePath, $php)) {
                            $io->success("File $absoluteFilePath written.");
                        }
                    }

                    
                    
                }
                
                $fileNameWithExtension = $file->getRelativePathname();

                // ...
            }
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
            
            // there are some files that have multiple classes.  For these, we need to create new files that match the class name.
            // we may also need to add use statements to any file that reference these classes.
            
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
            }  else {
                // probably a template or some definitions.  OK to keep as include
//                array_push($keepAsIncludes, $file->getFilename());
//                $this->logger->error("no class or function found", [$absoluteFilePath]);
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
