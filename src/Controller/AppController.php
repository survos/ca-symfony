<?php

namespace App\Controller;

use App\Command\ClassStructure;
use App\Entity\PhpFile;
use App\Repository\CaObjectsRepository;
use App\Repository\ProfileRepository;
use App\Services\FixNamespaceService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class AppController extends AbstractController
{
    private ParameterBagInterface $bag;

    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;

    private string $namespacedDir;

    /**
     * AppController constructor.
     */
    public function __construct(ParameterBagInterface $bag, LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->bag = $bag;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->namespacedDir=  $this->bag->get('namespaced_dir');
    }

    /**
     * @Route("/providence/old/{oldRoute}", name="app_legacy_index", requirements={"oldRoute"=".+"})
//     * @Route("/providence", name="app_legacy_route")
     */
    public function legacyIndex(Environment $twig, RouterInterface $router, ParameterBagInterface $bag, Request $request, $oldRoute=null): Response
    {
        $root = $bag->get('kernel.project_dir') . '/public/providence';
        $result =  require_once $root . '/index.php';

        dd($result);
        dd($root, $oldRoute);
    }

    /**
     * @Route("/providence", name="app_ca_index")
     */
    public function ca_app(Request $request): Response
    {
//        return $this->redirectToRoute('app_legacy_index');// ) . '/index.php');

        return new RedirectResponse($request->getRequestUri() . '/index.php');
    }

    /**
     * @Route("/", name="app_homepage")
     */
    public function index(Request $request, FixNamespaceService $fixNamespaceService): Response
    {
        return $this->render('app/homepage.html.twig', [
        ]);
    }

    private function removeDir($str)
    {
        return str_replace($this->bag->get('kernel.project_dir'), '', $str);
    }

    /**
     * @Route("/reflect", name="app_reflection")
     */
    public function reflect(FixNamespaceService $fixNamespaceService, ?Request $request=null, array $options = []): Response
    {

        if ($request) {
            $options = [
                'write' => $request->get('write', false),
                'process' => $request->get('process', true),
                'filter' => $request->get('filter')
            ];
        } else {

        }

        $dir = $this->bag->get('kernel.project_dir');
        require_once($dir . '/config/setup.php');
        define('$vs_app_plugin_dir', __CA_APP_DIR__ . '/plugins');
        define('$this->ops_controller_path', __CA_APP_DIR__ . '/service/controllers');
        define('$this->ops_theme_plugins_path', __CA_BASE_DIR__ . '/themes/plugins');
        define('$this->ops_application_plugins_path', __CA_BASE_DIR__ . '/app/plugins-maybe');// ???
        define('$vs_base_widget_dir', __CA_BASE_DIR__ . '/app/widgets');// ???

        /** @var PhpFile[] $files */
        $files = [];
        // initialize the files, we'll eventually use these for namespaces.
        $finder = new Finder();

        $finder
            ->in($dirToRemove = ($dir . '/vendor/collectiveaccess/providence'))
            ->filter(static function (\SplFileInfo $file) {
//                return $file->isFile() && !preg_match('/((Office|ImageMagick|SparqlEndpoint|BagIt|SimpleZip|sFTP|phpFlickr|vimeo|S3)\.php)|providence\/(app\/tmp|vendor|tests)/', $file->getRealPath()) && !preg_match('/(xxxphpqrcode)/', $file->getRealPath()) && preg_match('//', $file->getRealPath()) && preg_match('/\.(php)$/i', $file->getFilename());
                return $file->isFile() && !preg_match('/providence\/(app\/tmp|vendor|tests)/', $file->getRealPath()) && !preg_match('/(xxxphpqrcode)/', $file->getRealPath()) && preg_match('//', $file->getRealPath()) && preg_match('/\.(php)$/i', $file->getFilename());
            });

        // if this is a link, resolve it.
        $dirsToRemove = [$dirToRemove];
        // remove from realPath for relative file names.
        if (is_link($dirToRemove) && ($realPath = readlink($dirToRemove)))
        {
            array_push($dirsToRemove, $realPath);
        }

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            $this->logger->warning($errstr, [$errno, $errfile, $errline]);
        });

        // load ALL files, we need them to then resolve the inclues.
        // so that we can convert the include/requires to use statements.
        foreach ($finder as $file)
        {
            $absolutePath = $file->getRealPath();
            $phpFile = (new PhpFile($absolutePath, $dirsToRemove))
                ->setFilename($file->getFilename());
            $fixNamespaceService->createClasses($phpFile);
            $files[$file->getRealPath()] = $phpFile;
        }
        //  /home/tac/tacman/providence/app/helpers/utilityHelpers.php
        //  /home/tac/tacman/providence/app/helpers/utililityHelpers.php
        // /home/tac/tacman/providence/app/lib/Search/SitePageMediaSearchResult.php

//        /home/tac/tacman/providence/app/lib/Search/SitePageMediaSearchResults.php"
//  523 => "/home/tac/tacman/providence/app/lib/Search/SitePageMediaSearch.php"
  
//        dd(array_keys($files));
        $fixNamespaceService->setFileMap($files);


        // old system autoloaded these, we'll manually load all of them, then cs-fixer will removed the unused ones.
        // get ALL the classes in the
        $paths = [
            __CA_MODELS_DIR__,
            __CA_LIB_DIR__,
            __CA_LIB_DIR__ . '/Utils',
//            __CA_LIB_DIR__ . '/Attributes/Values',
            __CA_LIB_DIR__ . '/Parsers',
            __CA_LIB_DIR__ . '/Media',
            __CA_LIB_DIR__ . '/Exceptions',
            __CA_LIB_DIR__ . '/Search',
            __CA_LIB_DIR__ . '/Plugins',
            __CA_LIB_DIR__ . '/Browse'
        ];
        // extract the files that are simply functions and add them to composer, and pass the HUGE function list into the generator.
        $functionFiles = $functionUses = $commonUses =  [];
        foreach ($files as $file) {
            foreach ($file->getPhpClasses() as $phpClass) {

                if ($phpClass->isRawInclude()) {
                    if (count($phpClass->getFunctionList())) {
                        array_push($functionFiles, "ca/" . $phpClass->getRelativePath());
                        foreach ($phpClass->getFunctionList() as $function) {
                            array_push($functionUses, "use function " . $phpClass->getNamespace() . "\\" . $function . ';');
                        }
                    }
                } else {
                    // push every class on the list, then let cs-fixer remove it.  Seems terrible, but it's a one-time thing!
                    $classDir = pathinfo($file->getRealPath(), PATHINFO_DIRNAME);
                    if ($phpClass->getName() === 'BaseObject') {
//                        dd($phpClass);
//                        dd($classDir, $paths, $phpClass->getRealPath());
                    }
                    assert(empty($seen[$phpClass->getName()]), "Duplicate class name: " . $phpClass->getName() . ' ' . $phpClass);
                    if (in_array($classDir, $paths)) {
                        $commonUses[$phpClass->getName()] = $phpClass->getUse();
                        $seen[$phpClass->getName()] = $phpClass->getRealPath();
//                        array_push($comm, sprintf("use %s;", $phpClass->getUse()));
                    }
                }
            }
        }
        $functionUses = array_unique($functionUses);
        sort($functionUses);
        ksort($commonUses);

//        dd($functionUses);

        $composer = json_decode(file_get_contents($fn = $this->namespacedDir . '/../composer.json'));
        $composer->autoload->files = $functionFiles;
        file_put_contents($fn, json_encode($composer, JSON_PRETTY_PRINT));
//        dd($composer, $fn, $functionFiles, $composer->autoload, $functionUses);

        foreach ($files as $realPath => $phpFile)
            {
                // initial includes happen on the file level.  This is used when writing the file.
                foreach ($phpFile->getIncludes() as $fileToInclude) {
                    if (!array_key_exists($fileToInclude, $this->$files)) {
                        $phpFile->setErrorText($phpFile->getErrorText() . "Missing $fileToInclude\n");
                    } else {
                        $phpFile->addRequiredPhpFile($this->$files[$fileToInclude]);
                    }
                }

                if (!str_contains($phpFile->getRealPath(), 'BaseVersionUpdater')) {
//                    continue;
                }
                switch ($phpFile->getStatus()) {
                    case PhpFile::IS_INTERFACE:
                    case PhpFile::IS_CLASS:
                    case PhpFile::IS_INC:
//                    $allIncludes = []; // $phpFile->getInitialIncludes();
//                    $fixNamespaceService->loadIncludes($phpFile, $allIncludes);
//                    $phpFile->setIncludes($allIncludes);
                        // write the new classes
                        foreach ($phpFile->getPhpClasses() as $phpClass)
                        {
                            if ($fixNamespaceService->processHeader($phpClass))
                            {
                                $fixNamespaceService->createClassPhp($phpClass, $functionUses, $commonUses, $options);
                                if ($options['process']) {
                                }
                            }
                        }
                        // extract the classes
                        break;
                    case PhpFile::IS_INC:
//                    dd($phpFile);
//                        $fixNamespaceService->createClassPhp($phpClass, $functionUses, $options);

                        // someday namespace this
                        // count the functions so we can use them when this file is included
                        break;
                    case PhpFile::IS_CLI:
                        // move to bin/?
                        break;
                }
        }


            if ($filter = $options['filter']) {
                $files = array_filter($files, fn(PhpFile $key) => preg_match("|$filter|i", $key->getRealPath()));
            }

        return $this->render('app/reflection.html.twig', [
            'files' => $files,
            'classes' => [] // get_declared_classes()
        ]);
    }

    private function oldWay()
    {
        assert(false);



        // now go through each file and decide what to do with it.
//        dd($files['/home/tac/survos/ca/vendor/collectiveaccess/providence/app/helpers/utilityHelpers.php']);

//        $includedFiles = array_filter(get_included_files(), fn(string $filename) => preg_match('{/vendor/collectiveaccess/}', $filename) && !preg_match('{/providence/vendor|app/tmp}', str_replace($dir, '', $filename)));

        $fileToClass = [];
        $classesToWrite = array_filter(get_declared_classes(), fn($className) => preg_match('/Db/', $className));
        foreach ($classesToWrite as $class)
        {
            $r = (new \ReflectionClass($class));

            // if this is a file with multiple classes, break them apart
            if ($r->isUserDefined())
            {
                if (!preg_match('{/providence/}', $r->getFileName()))
                {
                    continue;
                }
                if (preg_match('{/providence/vendor/}', $r->getFileName()))
                {
                    continue;
                }

//                if (!preg_match('/MemoryCache/', $r->getFileName()) ) {
//                    continue;
//                }
                $classFilename = $r->getFileName();
                if ($class === 'ModelController')
                {
//                dd($classFilename, $r);
                }
                // get a reference to the file
                $f = $files[$classFilename] ?? null;
                if (empty($f))
                {
                    dd($classFilename);
                    continue;
                }

                if ($r->getName() === 'ModelController')
                {
//                    dd($f);
                }


                $isUserDefined = $r->isUserDefined();

                $cs = (new ClassStructure(new \SplFileInfo($classFilename), $dir))
                    ->setClassname($class)
                    ->setStartLine($r->getStartLine())
                    ->setEndLine($r->getEndLine())
                    ->setExtends($r->getParentClass()?:null)
                    ;

//                if ($class == 'ModelController') {
//                    dd($r->getExtension(), $r, $r->getProperties(), $r->getParentClass());
//                    dd($cs, $r);
//                }

//                    ->setDocComment($r->getDocComment())
//                    ->setPhp(join("\n", array_slice($f['phpLines'], $startLine, $endLine)))
                

                $newHeader = [];
                if ($class  == 'BaseServiceController')
                {
//                    dd($r, $r->getFileName(), $includedFileInfo, $includedFileInfo['classes']);
                }

                if ($parent = $r->getParentClass())
                {
                    if (!$parent->isInternal())
                    {
                        // to get the classes that may have been included from this!
                        $parentFile = $files[$parent->getFileName()];
                        $parentNs = ClassStructure::getNamespaceFromPath(pathinfo($parent->getFileName(), PATHINFO_DIRNAME)) . '/' . $parent->getName();
                        $cs->addInclude($parentNs, $parent->getFileName());
//                        $useNs = $parentNs . '\\' . $parent->getName() . ' // ' . $parent->getFileName(), $parent->getFileName());
//                        array_push($newHeader, sprintf('use %s\\%s; // extends %s %s', $parentNs, $parent->getName(),  $parent->getName(), $parent->getFileName() ));
                        // dd($parentFile, $parent, $parent->getFileName(), $parentNs);
//                        dd($newHeader);
                    }
                }
//                dd($r, $files[$r->getFileName()], $cs);

                $this->extractIncludes();

                if (count($cs->getIncludes()))
                {
                    foreach ($cs->getIncludes() as $useNs=>$fn)
                    {
                        // skip if it it's also the name of the namespace
                        if ($useNs <> $cs->getNs())
                        {
                            array_push($newHeader, "use $useNs; // from ".$this->removeDir($fn));
                        }
                    }
//                    dd($cs->getIncludes());
                }
                $cs->setHeader(join("\n", $newHeader));
//                dd($cs->getClassPhp());

                // we somehow lose 'extra' after assignment
                $files[$classFilename]['classes'][$class] = $cs;

//                assert($f, $r->getFileName());
                continue;

                assert(is_string($r->getFileName()), $r->name);
                dump($f);
                array_push($f['classes'], $r);
                dump($files, $f);


                if (array_key_exists($r->getFileName(), $fileToClass))
                {
                    // create a new file in the array, we'll write it later
                    $originalFile = (new \SplFileInfo($r->getFileName()));
                    // strip the $dir, we're going to rewrite these somewhere else.
                    $newFilename = $originalFile->getPath() . '/' . $r->getName();
                    dd($f, $files[$r->getFileName()], $newFilename, $r, $r->getFileName(), $fileToClass[$r->getFileName()]);
                }
                $fileToClass[$r->getFileName()] = $r;
            }
        }

        // go through all the files, check the classes and rename, add namespace, etc.
        $newFiles=[];
        foreach ($files as $filename => $f)
        {
            /** @var ClassStructure  $cs */
            foreach ($f['classes'] as $className=>$cs)
            {
                $ns = $cs->getNs();

                // one class, and it matches, this is what we hope for
//                if ($className == basename($classFilename, '.php'))
                {
                    // just add the names

                    $newFilename = $this->bag->get('namespaced_dir') .   $cs->getFilenameToWrite();
                    dd($newFilename);
                    $newDir = dirname($newFilename);
                    if (!is_dir($newDir))
                    {
                        try
                        {
                            mkdir($newDir, 0777, true);
                        }
                        catch (\Exception $e)
                        {
                            dd($newDir, $e->getMessage());
                        }
                    }

                    if ($cs->getExtends())
                    {
//                        dd($cs->getExtends());
                    }
                    // the new php replaces the required_once with the use statements, recursively...
                    // we can also figure out what use statements are necessary by searching the classes for their methods.
                    $newPhp = sprintf("<?php // %s\n\nnamespace %s;\n%s\n\n%s", $newFilename, $cs->getNs(), $cs->getHeader(), $cs->getClassPhpText());
                    array_push($newFiles, $newFilename);
                    file_put_contents($newFilename, $newPhp);
//                    dd($newFilename, $newPhp);
                }
            }
//            dd($f);
        }



        // find the CA classes
        foreach (get_declared_classes() as $class)
        {
            $r = (new \ReflectionClass($class));
            if ($r->isInternal())
            {
                continue;
            }
            if (array_key_exists($r->getFileName(), $files))
            {
                // we have a winner!  Idea: create new namespaced files in a new directory.
                // we only want the part before the class starts.
                $headerLines = array_slice($phpLines, 0, $r->getStartLine());

                foreach ($headerLines as $line)
                {
                    $pattern = "/(include|require)_once\\((.*?)\\.[\"']\\/(.*?).php[\"']\\)/";
                    // walk through the includes and create namespaces from them.  Might not be any.
                    if (preg_match($pattern, $line, $m))
                    {
                        // if we get the full filename, we can look up the new class and replace it.
                        $ca_constant = $m[2];
                        if (!defined($ca_constant))
                        {
                            throw new \Exception($ca_constant . " not defined, " . $line . ' in ' . $r->getFileName());
                        }

                        $val = constant($ca_constant);
                        $includedFile = $val . '/' . $m[3] . '.php';
                        // files should have all the files
                        $includedFileInfo = $fileToClass[$includedFile];


                        dd($m, $includedFileInfo);
                    }
                }
                if ($r->getName() == 'Db')
                {
                    dd($headerLines, $r);
                    dd($r);
                }
            }
        }



        dd($includedFiles, get_declared_classes());

        $reflect = (new \ReflectionClass(Db::class));
        dd($reflect);
    }

    /**
     * @Route("/load", name="app_load_files")
     */
    public function loadFiles(Request $request, ParameterBagInterface $bag, FixNamespaceService $fixNamespaceService): Response
    {
        // test php extraction.  Needs to be a a service, since the relative path changes if on the command line.
        $finder = new Finder();
        $dir = $bag->get('kernel.project_dir') . '/vendor/collectiveaccess/providence/app';

//        $dir = $bag->get('kernel.project_dir') . '/../pr/app';

        $files = $fixNamespaceService->fix($dir);


        return $this->render('app/ca_files.html.twig', [
            'files' => $files
//            'profiles' => $profileRepository->getBasicData()
        ]);

//        $object = $caObjectsRepository->findOneBy([]);
//        return $this->render('app/index.html.twig', [
//            'controller_name' => 'AppController',
//        ]);
    }
}
