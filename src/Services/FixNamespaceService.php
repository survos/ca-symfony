<?php

// load all php files, check ns, multiple classes.  Collect require's.

namespace App\Services;


use App\Command\ClassStructure;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FixNamespaceService
{


    private $includes = [];
    private $uses = [];
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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

            if ($php = file_get_contents($absoluteFilePath)) {
                $newPhp = $php;
                // check to make sure that the filename matches the class name.  If not, create new files.
                if (preg_match_all('/\n((final |abstract )?class ([^ ]+)[^\n]*\n{(.*?)\n})/sm', $php, $mm, PREG_SET_ORDER)) {
                    foreach ($mm as $idx => $m) {
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
                } else {
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

            $fileNameWithExtension = $file->getRelativePathname();

            // ...
        }
        return $files; // class structure indexed by pathname

        // ...
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
    public function processHeader(ClassStructure $cs, array $files): bool
    {
        // if it's a file with just functions, we might have requires.  But we don't want the includes that are inside of functions.

        $php = $cs->getOriginalPhp();
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
