<?php

// load all php files, check ns, multiple classes.  Collect require's.

namespace App\Services;


use _HumbugBox5d215ba2066e\Roave\BetterReflection\Reflection\Adapter\ReflectionFunction;
use App\Command\ClassStructure;
use App\Entity\PhpClass;
use App\Entity\PhpFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FixNamespaceService
{


    private $includes = [];
    private $uses = [];
    private LoggerInterface $logger;
    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $bag;
    private string $namespacedDir;

    public function __construct(LoggerInterface $logger, ParameterBagInterface $bag, string $namespacedDir)
    {
        $this->logger = $logger;
        $this->bag = $bag;
        $this->namespacedDir = $namespacedDir;
    }


    public function fix($dir, array $options=[]): array
    {
        $options = (new OptionsResolver())
            ->setDefault('add-namespaces', false)
            ->resolve($options);

        $finder = new Finder();
        $finder->files()->in($dir)->files()->name('*.php')->filter(

            fn(\SplFileInfo $file) =>
//                dd($file) &&
            !preg_match('{/(views|tmp|templates|providence/vendor|printTemplates)/}', $file->getRealPath())
        );

        // check if there are any search results
        if (!$finder->hasResults()) {
            throw new \Exception("No matching php files");
        }

        // load up all the original files, keyed by absolute filename
        $files = [];
        $this->logger->info("Count", [$finder->count()]);
        foreach ($finder as $file) {
            $this->logger->info("Loading", [$file->getPath(), $file->getFilename()]);
            // ignore views, maybe some other files
            $classStructure = (new ClassStructure($file, $dir));

            $files[$classStructure->getFilename()] = $classStructure;

//            $absoluteFilePath = $file->getRealPath();
//            $classStructure->setIncludes($this->getIncludedClasses($classStructure->getPhp()));
//            $this->includes[$absoluteFilePath] = ;
//            $this->uses[$this->getNamespace($file)] = $absoluteFilePath;
        }

        // go through each file, and if the class name doesn't match, fix it.  If there are multiple classes, create new files.
        // now that the files are loaded, we can fix the headers because we have references to everything.  We can also create new files if necessary, in memory

        reset($files);
        foreach ($files as $filename=>$classStructure) {
                if ($this->processHeader($classStructure, $files))
                {
                    $absoluteFilePath = $filename;
                    if ($classStructure->getHeader()) {
                        $newPhp = str_replace($classStructure->getOriginalheader(), $classStructure->getHeader(), $classStructure->getOriginalPhp());
                        $newPhp = trim($newPhp); // get rid of spaces.
                        $newPhp = preg_replace('|\?>$|', '', $newPhp);
//                        dd($newPhp);
//                        file_put_contents($absoluteFilePath, $this->addNamespace($file, $newPhp));
                        $this->logger->info("Adding namespace and header", [$absoluteFilePath]);
                    } else {
                        $this->logger->warning("Empty Header", [$absoluteFilePath]);
                    }
                } else {
                    $this->logger->warning("Unable to process header", [$absoluteFilePath]);

                }
            try {
            } catch (\Exception $e) {
                $classStructure->setStatus($e->getMessage());
//                $this->logger->error($e->getMessage(), [$classStructure->getFilename()]);
            }

        }


        return $files;



        // hack --
        $classes = array_map(fn ($class) => "use $class;", array_keys($this->uses));
        $this->usesString = join("\n", $classes);

        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();


            $fileNameWithExtension = $file->getRelativePathname();

            // ...
        }
        return $files; // class structure indexed by pathname

        // ...
    }

    // given a php file, extract the classes, traits and interfaces defined in it.  Assumes these key words are in the first column, and that their ending brace is as well.  use cs-fixer.
    public function createClasses(PhpFile $phpFile)
    {
        if ($php = $phpFile->getRawPhp())
        {
            $php = preg_replace('/^<\?php/', '', $php);
            $php = preg_replace('/<\?php>$/', '', $php);

            // if there's still a <?php tag in here, then it's a template, never create a namespace or class
            if (preg_match('/<\?php.*?\?>/ms', $php, $m)) {
                $phpFile->setStatus(PhpFile::IS_VIEW);
                return;
            }

            // we could simply include this

            // check to make sure that the filename matches the class name.  If not, create new files.
            if (preg_match_all('/\n((final |abstract )?(class |interface |trait )([^ ]+)[^\n]*\n{(.*?)\n})/sm', $php, $mm, PREG_SET_ORDER)) {
                foreach ($mm as $idx => $m) {
                    $type = $m[3];
                    $className = trim($m[4]);
                    $classPhp = $m[0];
                    $phpClass = (new PhpClass())
                        ->setName($className)
                        ->setType($type)
                        ->setOriginalPhp(trim($classPhp));
                    $phpFile->addPhpClass($phpClass);
                    $php = str_replace($classPhp, '', $php);
                }
                // get everything to the first function and squeeze in a class
            } elseif (preg_match('/(.*?)\n(private |public )?function ([a-zA-Z0-9_]+)\s*\(.*?\n}\n/sm', $php, $mm)) {
//                $path = $phpFile->getRealPath();

                // @todo: fix this so docBlocks are correct
                $functionsPhp = str_replace($mm[1], '', $php); // the php content
                // tidy this up?  Add public?
//                exec($functionsPhp); // this should define the function


//                dd($phpFile->getRealPath(), $functionsPhp);
//                dd($functionsPhp, $php, $mm);
                $className = str_replace('.php', '', pathinfo($phpFile->getRealPath(), PATHINFO_BASENAME));
                // remove the functions from php file, so we have an accurate header

                $phpClass = (new PhpClass())
                    ->setName($className)
                    ->setType('class')
//                    ->setOriginalPhp("class $className\n{\n\n" . $functionsPhp . "\n}" )
                ;

                // get the list of functions so we can import them.
                // use function My\Full\functionName as func;
                if (preg_match_all('/\n(private |public )?function ([a-zA-Z\d_]+)\s*\(.*?\n}\n/ism', $php . "\n", $mmm, PREG_SET_ORDER)) {
//                    $php = str_replace($mmm[1], '', $php);
                    $functionList = [];
                    $functionPhp = '';
                    foreach ($mmm as $m) {
                        array_push($functionList, $m[2]);
                        $functionPhp .= $m[0] . "\n";
                        $php = str_replace($m[0], "// $m[2]() removed\n", $php . "\n");
                    }
                    $phpClass
                        ->setOriginalPhp($functionPhp) // namespaced, but NOT in a class.  "class $className\n{\n\n" . $functionPhp . "\n}" )
                        ->setFunctionList($functionList);
                }
//                if ($phpFile->getFilenameWithoutExtension() == 'accessHelpers.php') {
//                    dd($functionList, $functionPhp, $php);
//                    dd($functionList, $phpFile->getRealPath());
//                }


                $phpFile->addPhpClass($phpClass);

//                $phpFile->setRawPhp(str_replace($functionsPhp, '', $php));
                $phpFile
                    ->setStatus(PhpFile::IS_CLASS) // we're forcing it into a class
                    ->setHeaderPhp($php); // the original PHP, minus the functions.  The class has to go below this,because sometimes globals are set., e.g. accessHelpers.php

                // if there's any PHP besides the first one, it's a template.  if there's a $this, it's an include for a class (and should be manually included when the time comes).
                if (preg_match('/<?/', $php) || preg_match('/\$this/', $php) ) {

                    // maybe this is a trait, or included directly in a php class, but it's something that probably shouldn't be.

                } else {
                    dd($phpClass);
//                require_once($path);


 //                    dd($mm[1], $phpFile->getRelativeFilename(), $php);

                }

//
//                dd($mm);
//                $functions = get_defined_functions();
//                dd($functions);

            } else {
                //
                if ($phpFile->getStatus() === PhpFile::IS_CLASS)
                {
                    dd($phpFile->getStatus(), $phpFile->getRelativeFilename(), $php);
                }

                // hack on a class, even if it's just a bunch of includes or constants
                $className = str_replace('.php', '', pathinfo($phpFile->getRealPath(), PATHINFO_BASENAME));
                $phpClass = (new PhpClass())
                    ->setName($className)
                    ->setType('class')
                    ->setOriginalPhp( "\nclass $className\n{\n\n"  . "}" );
                $phpFile->setHeaderPhp("// class created from name\n\n" . $php)
                    ->addPhpClass($phpClass);

            }
        }

        if (!empty($phpClass)) {
            $newFilename = $this->namespacedDir . '/' . ($relativeFilename = PhpFile::applyNamespaceHacks($phpClass->getPhpFile()->getRelativePath()) . sprintf('/%s.php', $phpClass->getName()));
            $phpClass->setRealPath($newFilename);
            $ns = $this->getNamespaceFromPath($phpClass->getPhpFile()->getRelativePath());
            if (count($phpClass->getFunctionList())) {
                $ns .= "\\" . $className; // functions aren't in a class, but instead namespaced.
            }
            $phpClass->setNamespace($ns);
        }





        // get a list of the files we're going to need
        $include = [];
        foreach (explode("\n", trim($php)) as $line) {
            // maybe exit when we see function() or class
            if ($fileToInclude = $this->extractIncludedFilename($line)) {
                $include[$fileToInclude] = $line;
            }
        }

        $phpFile->setInitialIncludes($include);
        $phpFile->setHeaderPhp($php); // php without the classes, still raw, but now we have the include list
        return $phpFile;
    }

    public function insertIncludes($includedFile, $line)
    {
        $includedPhpFile = $this->getFile($includedFile);
        if ($includedPhpFile->getStatus() == PhpFile::IS_CLASS) {
            array_push($newLines, "// $line $includedFile");
            dd($includedPhpFile->getStatus(), $includedPhpFile->getPhpClasses()->count());

            foreach ($includedPhpFile->getPhpClasses() as $phpClass) {
                $use = $phpClass->getUse();
                dd($uses, $use);
//                if (!array_key_exists($uses)) {
//                    array_push($newLines, sprintf("use %s;", $phpClass->getUse()));
//                    $uses[$phpClass->getUse()] = $includedFile;
//                }
            }
        } else {
            array_push($newLines, "$line // ". $includedPhpFile->getStatus());
        }


}

    public function extractIncludedFilename($line): ?string
    {
//        $vs_app_plugin_dir = __CA_APP_DIR__ . '/plugins';

        $pattern = "/(include|require)_once\((.*?) ?\. ?[\"']\/(.*?).php[\"']\)/";
        if (preg_match($pattern, $line, $m)) { // }, PREG_SET_ORDER)) {
            $ca_constant = trim($m[2]);
            $path = $m[3];
            // if we're requiring it from vendor, we should be using autoload.
            if (preg_match('/vendor/', $path)) {
                return null;
            }

            if (!defined($ca_constant)) {
                switch ($ca_constant) {
                    case "\$this->opo_config->get('application_plugins')": $ca_constant = 'xx'; break;
                    case 'self::$opo_config->get("views_directory"': $ca_constant = 'xx'; break;
                }
            } else {
                $val = constant($ca_constant);
            }
            $includedFile = $val . '/' . $m[3] . '.php';
            return $includedFile;
        }
        return null;

    }

    private function getMap()
    {
        return [
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
    }



    public function extractIncludes()
    {}

    private $files;
    public function setFileMap(array $files)
    {
        $this->files = $files;
    }
    public function getFileMap(): array
    {
        return $this->files;
    }
    public function getFile($fn): ?PhpFile
    {
        // hacks for variable classes
        $fn = str_replace('{$ps_plugin_code}', 'domPDF', $fn);
//        assert(array_key_exists($fn, $this->files), $fn);
        return $this->files[$fn] ?? null;
    }

    public function writeFile($filename, $contents)
    {
        $newDir = dirname($filename);
        if (!is_dir($newDir)) {
            try {
                mkdir($newDir, 0777, true);
            } catch (\Exception $e) {
                dd($newDir, $e->getMessage());
            }
        }

//        dd($filename, $contents);
        file_put_contents($filename, $contents);
    }

    public function loadIncludes(?PhpFile $originalPhpFile, array &$includesSoFar): array
    {
        if (empty($originalPhpFile)) {
            return [];
        }
        foreach ($originalPhpFile->getInitialIncludes() as $include => $info) {
            if (!array_key_exists($include, $includesSoFar)) {
                if (file_exists($include)) {
                    $includesSoFar[$include] = $info;
                    $this->loadIncludes($this->getFile($include), $includesSoFar);
                }

                // if we haven't seen this, recursively call it.
//                dd($includesSoFar, $newIncludes);
//                if ($originalPhpFile->addInclude($include, $info)) {
//                }
            }
        }
        return $includesSoFar;
    }


    public function oldInclude(PhpFile $originalPhpFile)
    {
        foreach (explode("\n", trim($originalPhpFile->getHeaderPhp())) as $line) {
            // for now, just the filename, we'll loop through them later
            if ($fn  = $this->extractIncludedFilename($line))
            {
                if ($originalPhpFile->addInclude($fn, $line)) {

                }
            }
        }
        $uses = [];
        $newLines = [];

        // walk through the includes and create namespaces from them.  Might not be any.
        $seen = [];
        $includes = [];
//        dd(__CA_LIB_DIR__);

//        dd($includes, $originalPhpFile->getRelativeFilename());
//
//        dd($originalPhpFile->getRelativeFilename(), $uses, $newLines);


        return $header;

    }

    public function writeClass(PhpClass $phpClass)
        {

            $phpFile = $phpClass->getPhpFile();

            $newFilename = $phpClass->getRealPath();
            $ns = $phpClass->getNamespace();
//            dd($newFilename);
            $this->logger->alert(sprintf("creating $newFilename"));

        // We shouldn't need a user, since its in the same namespace

//        dd($ns, $relativeFilename);
        $newClassPhp = <<<END
<?php

namespace $ns;


END;
//        dd($phpFile->getInitialIncludes(), $phpFile->getHeaderPhp());
        // now we have to recurse through all the includes to get a master list of uses.

        $allIncludes = [];

        $this->loadIncludes($phpFile, $allIncludes);
        // hacking..., never include the namespace itself
        unset($allIncludes[$phpFile->getRealPath()]);
        $uses = $this->getUsesFromIncludes($allIncludes);



        $newHeader = [];
        foreach ($uses as $include=>$info) {
            array_push($newHeader, sprintf("%s; // %s", $include, $info));
        }

//        $newClassPhp = '<?php' . "\n\n" . $relativeFilename;

        $this->writeFile($newFilename,  $newClassPhp . join("\n", $newHeader) . "\n\n" . $phpClass->getOriginalPhp());
        return;


//        $newPhp = str_replace($m[1], "// see $newFilename for class $className\n", $newPhp);

            {
                {
//                    $phpFile->add

                    // get rid of the classes, and keeps what's left (header, etc.)
                    if ($className <> $phpFile->getFilenameWithoutExtension()) {

                        // if exactly one, rename php.  Otherwise, create classes and rename to .txt or .orig
                        if ($idx === 0) {

//                                    $this->logger->alert(sprintf("Renaming %s to %s", $file->getFilename(), $className.'.php'));
                        } else {
//                            $this->logger->alert(sprintf("creating $newFilename"));

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
                        $this->logger->info("Class and filename match", [$absoluteFilePath]);
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
            }

            // likely classes have been removed.
            if ($newPhp <> $php) {
                file_put_contents($absoluteFilePath, $this->addNamespace($file, $newPhp));
            }

            if ($options['add-namespaces']) {
                if (file_put_contents($absoluteFilePath, $php)) {
                    $this->logger->info("File $absoluteFilePath written.");
                }
            }
    }


    private function getNamespace(\SplFileInfo $file): ?string
    {
        $namespace = null;
            // hack!
        if (preg_match('|app|', $file->getPath())) {
            $namespace = str_replace('vendor/collectiveaccess/providence/', '', $file->getPath());
            $namespace = str_replace('app/lib', 'CA/lib', $namespace);
            $namespace = str_replace('/', '\\', $namespace);
        }
        return $namespace;
    }

    private function getNamespaceFromPath($path): ?string
    {
        $path = PhpFile::applyNamespaceHacks($path);

        $namespace = $path;
        // hack!
//            $namespace = str_replace('vendor/collectiveaccess/providence/', '', $path);
//            $namespace = str_replace('app/lib', 'CA/lib', $namespace);
            $namespace = 'CA\\' . str_replace('/', '\\', $namespace);
        // hacks for namespace issues.  Maybe sometime capitialization, etc.

        return $namespace;
    }

    private function getUsesFromIncludes($includes)
    {
        $uses = [];
        // we include files, but we use classes
        foreach ($includes as $include=>$info) {
            if ($originalIncludedFile = $this->getFile($include))
            {
                if ($originalIncludedFile->getPhpClasses()->count()) {
                    foreach ($originalIncludedFile->getPhpClasses() as $phpClass) {


                        $uses["use " . $phpClass->getUse()] = $info;
                        // add the functions, which are now inside a class
                        foreach ($phpClass->getFunctionList() as $functionName) {
                            $uses["use function " . $phpClass->getUse()."\\$functionName"] = $info;
                        }
                    }
                } else {
                    $filename = pathinfo($include, PATHINFO_BASENAME);
                    if (!$filename == 'configuration_error_html.php') {
                        dd($include, $info, $originalIncludedFile);
                        $uses[$info] = $include; // should be the include or require
                    }

                }
            }
        }

        return $uses;
        // if no classes, then include this file raw.

    }

    private function addNamespace(\SplFileInfo $file, string $php): ?string
    {
        $absoluteFilePath = $file->getRealPath();

//        $php = file_get_contents($absoluteFilePath);
        if (preg_match('|app|', $file->getPath())) {
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

    /**
     * @description Reads the header and returns an array of the ..???
     * @param $php
     * @return array
     */
    public function processHeader(PhpFile $phpFile): bool
    {
        // if it's a file with just functions, we might have requires.  But we don't want the includes that are inside of functions.

        // hackish -- this is what's left after the classes have been removed
        $php = $phpFile->getRawPhp();
//        $uses = [];
        $includes = [];
        if (preg_match($pattern = '/(<\?php.*?\n)(abstract |final |public )?(class |interface |trait |function )/ms', $php, $mm)) {
            $header = $oldHeader = $mm[1];
            $cs->setStatus(trim($mm[2]));
        } else {
            // so it'll get replaced.
            // @todo: look for functions and include them in use, then we can make functions namespaced too.
            $header = $oldHeader = $php;
            $cs->setStatus('raw-include');
//            return false;
            // set a status so that if we see this somewhere, we do a raw include.  It could be constants being defined, or a console script.
//            throw new \Exception(sprintf("%s  %s %s", $cs->filename, $pattern , $cs->top()));
        }
        if ($cs->getStatus() === 'function') {
            // for now, eventually get all the functions and namespace them.
            $cs->setStatus('raw-include');
        }


        $cs->setOriginalheader($oldHeader); // use to replace php later
        $namespace = $cs->getNamespaceFromFilename(dirname($cs->getFilename()));
        $pattern = "/(include|require)_once\((.*?)\.[\"']\/(.*?).php[\"']\)/";
        // walk through the includes and create namespaces from them.  Might not be any.
        if (preg_match_all($pattern, $oldHeader, $mm, PREG_SET_ORDER)) {
            $seen = [];
            $includes = [];
            foreach ($mm as $m) {
                $ca_constant = $m[2];
                if (!defined($ca_constant)) {
                    throw new \Exception($ca_constant . " not defined, " . $cs->getFilename());
                }

                $val = constant($ca_constant);
                $includedFile = $val . '/' . $m[3] . '.php';
                // files should have all the files 
                /** @var ClassStructure $csInc */
                $csInc = $files[$includedFile];
                // if it's missing, then treat it as a raw include

//                assert($csInc, $cs->getFilename() . " Missing " . $includedFile );
//                dd($csInc->getStatus(), $includedFile);
                
                $ns = $cs->getNamespaceFromFilename($includedFile);
                // don't include yourself
                if ($includedFile ===  $cs->getFilename()) {
                    $header = str_replace($m[0], "// THIS FILE, SO DONT USE NAMESPACE; // $m[0]", $header);
                } else {
                    if (empty($csInc) || $csInc->isRawInclude()) {
                        // 
                    } else {
//                    dump($includedFile, $cs->getFilename(), $ns, $cs->getNs());
                        // need to use the ns + classname!
                        if (!array_key_exists($includedFile, $includes)) {
                            $header = str_replace($m[0], "use $ns; // $m[3]", $header);
//                            $seen[$ns] = $includedFile;
                        } else {
                            $header = str_replace($m[0], "// DUPLICATE $m[3]", $header);
                        }
                    }
//                    $includes[$includedFile] = $ns;
                }
            }
            // hack.  While we're in here, fix the header.
            // make sure not to include the use for THIS namespace.

//                    dd($m, $cs->includes, $val);
//            $cs->setIncludes($includes);
//            dd($includes, $header);
        }

//            dd($namespace, $cs->path);
        // we might not want the namespace for everything.

        if ($cs->getStatus() <> 'raw-include') {
            $header = preg_replace('/namespace [^ ]+;/', '', $header);
            $header = str_replace("<?php", sprintf("<?php\n\n// %s\n\nnamespace %s;\n\n", $cs->getPath(), $namespace), $header);
        }
        $cs->setHeader($header);
//                dd($mm);
        return true;

    }



}
