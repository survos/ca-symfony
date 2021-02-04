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

/*
 * Get all project classes.  
 */
class FixCommand extends Command
{
    private $uses = [];
    private $includes = []; // track each include/require, so we can map to use statement
    private string $usesString;
    private $autoloadMap = [];
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
            ->addArgument('dir', InputArgument::OPTIONAL, 'Directory to process', "vendor/collectiUveaccess/providence/app/")
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
            // crazy hack
            foreach ($finder as $file) {
                $classStructure = (new ClassStructure($file));
                
                $this->includes[$classStructure->getFilename()] = $classStructure;
                
                $absoluteFilePath = $file->getRealPath();
                $this->includes[$absoluteFilePath] = $this->getIncludedClasses($file);
                $this->uses[$this->getNamespace($file)] = $absoluteFilePath;
            }
            
            // hack --
            $classes = array_map(fn($class) => "use $class;", array_keys($this->uses) );
            $this->usesString = join("\n", $classes);
            
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
                    if (preg_match_all('/\n((final |abstract )?class ([^ ]+)[^\n]*\n{(.*?)\n})/sm', $php, $mm, PREG_SET_ORDER)) {
                        foreach ($mm as $idx => $m) 
                        {
                            $className = $m[3];
                            if ($className <> $file->getFilenameWithoutExtension()) {
                                // if exactly one, rename php.  Otherwise, create classes and rename to .txt or .orig
                                if ($idx === 0) {

//                                    $this->logger->alert(sprintf("Renaming %s to %s", $file->getFilename(), $className.'.php'));
                                    
                                } else {
                                    $newFilename = $file->getPath() . sprintf('/%s.php', $className);
                                    $this->logger->alert(sprintf("creating $newFilename"));

                                    // We shouldn't need a user, since its in the same namespace
                                    $newClassPhp = '<?php' . "\n\n" . $m[1];
                                    file_put_contents($newFilename, $this->addNamespace($file, $newClassPhp));

                                    $newPhp = str_replace($m[1], "// see $newFilename for class $className\n", $newPhp);
//                                    $this->addNamespace($file);
                                    // probably a related exception class
                                    $this->logger->alert(sprintf("Extra class in %s (%s)", $absoluteFilePath, $className));
                                    
//                                    dd($m);
//                                    if (count($mm) > 2) {
//                                        dd($mm);
//                                    }
//                                    
//                                    dd($className, $file->getFilename());
                                }
                            } else {
                                file_put_contents($absoluteFilePath, $this->addNamespace($file, $php));
                                $output->writeln(
                                    "Class and filename match " . $absoluteFilePath,
                                    OutputInterface::VERBOSITY_VERBOSE
                                );

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

                    // likely classes have been removed.
                    if ($newPhp <> $php) {
                        file_put_contents($absoluteFilePath, $this->addNamespace($file, $newPhp));
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
    
    private function getNamespace(\SplFileInfo $file): ?string
    {
        $namespace = null;
        if ( preg_match('|app|', $file->getPath()) ) {
            // hack! 
            $namespace = str_replace('vendor/collectiveaccess/providence/', '', $file->getPath());
            $namespace = str_replace('app/lib', 'CA/lib', $namespace);
            $namespace = str_replace('/', '\\', $namespace);
        }
        return $namespace;
    }
    
    
    private function addNamespace(\SplFileInfo $file, string $php): ?string
    {
        $absoluteFilePath = $file->getRealPath();
        
//        $php = file_get_contents($absoluteFilePath);
        if ( preg_match('|app|', $file->getPath()) ) {
            // hack! 
            $namespace = $this->getNamespace($file);
            
            // there are some files that have multiple classes.  For these, we need to create new files that match the class name.
            // we may also need to add use statements to any file that reference these classes.
            
            
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
    
    private function getIncludedClasses($php): array
    {
        if (preg_match('/(<\?php.*?\n)(abstract |final )?(class |function )/ms', $php, $mm)) {

            $oldHeader = $mm[1];
            // 

//                dd($header);
            // we need to add a Use for every class that's in the 
            $header = preg_replace_callback("/(include|require)_once\((.*?)\.[\"']\/(.*?).php[\"']\)/",
                function($m)  {
                    $prefix=$m[2];
                    if (array_key_exists($prefix, $this->autoloadMap)) {
                        $use = sprintf("%s\%s", $this->autoloadMap[$prefix], str_replace('/', '\\', $m[3]));
                        $this->uses[$use] = $m[0];
                        return sprintf("// use %s; // %s", $use, $m[0]);
                    } else {
                        return $m[0]; // dont replace
                    }
                }, $oldHeader);
            

            $header  .=  sprintf("// all uses!\n%s\n//ENDOFUSES\n\n", $this->usesString);
//                dd($header);
            // get rid of the old namespace
//            $header = preg_replace('/namespace [^ ]+;/', '', $header);
            // add the new one
//            $header = str_replace("<?php", "<?php\n\nnamespace $namespace;\n", $header);
//                dd($header);

            // replace the header
            $php = str_replace($oldHeader, $header, $php);
        }  else {
            // probably a template or some definitions.  OK to keep as include
//                array_push($keepAsIncludes, $file->getFilename());
//                $this->logger->error("no class or function found", [$absoluteFilePath]);
        }

    }
}
