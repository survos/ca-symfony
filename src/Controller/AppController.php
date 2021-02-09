<?php

namespace App\Controller;

use App\Command\ClassStructure;
use App\Repository\CaObjectsRepository;
use App\Repository\ProfileRepository;
use App\Services\FixNamespaceService;
use CA\lib\Db;
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
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * AppController constructor.
     */
    public function __construct(ParameterBagInterface $bag, LoggerInterface $logger)
    {
        $this->bag = $bag;
        $this->logger = $logger;
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
//        $dir = $this->bag->get('kernel.project_dir');
        // test php extraction.  Needs to be a a service, since the relative path changes if on the command line.
        return $this->render('app/homepage.html.twig', [
//            'profiles' => $profileRepository->getBasicData()
        ]);

//        $object = $caObjectsRepository->findOneBy([]);
//        return $this->render('app/index.html.twig', [
//            'controller_name' => 'AppController',
//        ]);
    }

    /**
     * @Route("/reflect", name="app_reflection")
     */
    public function reflect(Request $request, FixNamespaceService $fixNamespaceService): Response
    {
        $dir = $this->bag->get('kernel.project_dir') ;
        require_once($dir . '/config/setup.php');
//        require_once('./app/helpers/post-setup.php');
//        $includedFiles = array_filter(get_included_files(), fn(string $filename) => preg_match('{/vendor/collectiveaccess/}', $filename) && !preg_match('{/providence/vendor|app/tmp}', str_replace($dir, '', $filename)));

        // for testing
//        $includedFiles = array_filter($includedFiles, fn(string $filename) => preg_match('/MemoryCache/', $filename));
//        dd($includedFiles);

        $files = [];
        // initialize the files, we'll eventually use these for namespaces.
        $finder = new Finder();

        $finder
            ->in($dir . '/vendor/collectiveaccess/providence/')
            ->filter(static function (\SplFileInfo $file) {
                return $file->isFile() && !preg_match('/providence\/(vendor|tests)/', $file->getRealPath()) && preg_match('/\.(php)$/i', $file->getFilename());
            });

        $finder->exclude('providence/vendor')->files()->name('*.php')->in([])
            ->filter(
            fn(\SplFileInfo $file) =>  preg_match('/lib/', ($file->getRealPath() )) // && dump($file->getRealPath(), preg_match('/lib/', $file->getRealPath()) ))
//            dump($file->getRealPath(), preg_match('{/(ca/vendor|ca/var/|cache|views|tmp|templates|ca/bin/|providence/vendor|printTemplates)/}', $file->getRealPath())) &&
//            !preg_match('{/(ca/vendor|ca/var/|cache|views|tmp|templates|ca/bin/|providence/vendor|printTemplates)/}', $file->getRealPath())
        )
        ;
//        dump($finder->count(), array_slice(iterator_to_array($finder), 0, 20));
//        dd($finder);

        // check that it's not already included first
        $includedFiles = array_filter(get_included_files(), fn(string $filename) => preg_match('{/vendor/collectiveaccess/}', $filename) && !preg_match('{/providence/vendor|app/tmp}', str_replace($dir, '', $filename)));
//        dd($includedFiles);

        set_error_handler(function ($errno, $errstr, $errfile, $errline ) {
            $this->logger->warning($errstr);
        });


        // was get_included_files()
        foreach ($finder as $file) {
            $absolutePath =  $file->getRealPath();

            if (preg_match('/class ([a-z_0-9]+)/i', file_get_contents($absolutePath), $m)) {
                $foundClass = $m[1];
                $this->logger->warning($file->getRealPath(), );
                    // the problem is that things like SetController exist in multiple directories (thus the need for namespaces)
                    if (class_exists($foundClass)) {
                        //
                        $this->logger->warning('class already exists ' . $foundClass, [$foundClass] );
                    } else {
                        if (!in_array($absolutePath, $includedFiles)) {
                            if (file_exists($absolutePath)) {
                                // we need to ignore if the requires are missing, like https://github.com/collectiveaccess/providence/blob/develop/app/controllers/find/SearchObjectsBuilderController.php#L28
                                if (!in_array($file->getFilenameWithoutExtension(), ['HTTPHeaders', 'get-events', '_autoload', 'Shibboleth', 'ShibbolethAuthAdapter', 'AuthController', 'BagIt', 'SearchObjectsBuilderController']))
                                {
                                    $this->logger->warning("trying " . $absolutePath);
                                    require_once($absolutePath); // to load the declared classes
                                }
                            }
                        }
                    }

                try {
                } catch (\Exception $exception) {
                    $this->logger->warning($exception->getMessage(), [$file->getRealPath()]);
                }
            }
            $absolutePath = $file->getRealPath();
            $shortFilename = str_replace($dir, '', $absolutePath);
            $files[$absolutePath] = [
                'phpLines' => file($absolutePath),
                'shortName' => $shortFilename,
                'absoluteFilename' => $absolutePath,
                'classes' => []
            ];
//            if ($file->getFilenameWithoutExtension() == 'ModelController') {
//                dd($files[$absolutePath], $absolutePath);
//            }
        }

//        $includedFiles = array_filter(get_included_files(), fn(string $filename) => preg_match('{/vendor/collectiveaccess/}', $filename) && !preg_match('{/providence/vendor|app/tmp}', str_replace($dir, '', $filename)));


        $fileToClass = [];
        foreach (get_declared_classes() as $class) {
            $r = (new \ReflectionClass($class));

            // if this is a file with multiple classes, break them apart
            if (!$r->isInternal()) {
                if (!preg_match('{/providence/}', $r->getFileName()) ) {
                    continue;
                }
                if (preg_match('{/providence/vendor/}', $r->getFileName()) ) {
                    continue;
                }

//                if (!preg_match('/MemoryCache/', $r->getFileName()) ) {
//                    continue;
//                }
                $classFilename = $r->getFileName();
                if ($class === 'ModelController') {
//                dd($classFilename, $r);
                }
                // get a reference to the file
                $f = $files[$classFilename] ?? null;
                if (empty($f)) {
                    dd($classFilename);
                    continue;
                }

                if ($r->getName() === 'ModelController') {
//                    dd($f);
                }


                $isUserDefined = $r->isUserDefined();

                $cs = (new ClassStructure(new \SplFileInfo($classFilename), $dir))
                    ->setClassname($class)
                    ->setStartLine($r->getStartLine())
                    ->setEndLine($r->getEndLine())
//                    ->setDocComment($r->getDocComment())
//                    ->setPhp(join("\n", array_slice($f['phpLines'], $startLine, $endLine)))
                ;

                $newHeader = [];
                foreach ($cs->getClassContent(0, $r->getStartLine()-2) as $line) {
                    $pattern = "/(include|require)_once\((.*?)\.[\"']\/(.*?).php[\"']\)/";
                    // walk through the includes and create namespaces from them.  Might not be any.
                    if (preg_match($pattern, $line, $m)) {

                        // if we get the full filename, we can look up the new class and replace it.
                        $ca_constant = $m[2];
                        if (!defined($ca_constant)) {
                            throw new \Exception($ca_constant . " not defined, " . $line . ' in ' . $r->getFileName());
                        }

                        $val = constant($ca_constant);
                        $includedFile = $val . '/' . $m[3] . '.php';

                        // hack!
                        $includedFile = str_replace('{$format}', 'SimpleZip', $includedFile);
                        $includedFile = str_replace('{$transport}', 'sFTP', $includedFile);

                        array_push($newHeader, " // ($m[0], $includedFile");
                        // files should have all the files
                        $rClass = $files[$includedFile] ?? null;


                        if (empty($rClass)) {
                            if (!in_array($m[3], ['vendor/autoload'])) {
                                dd($m[0], $m, $includedFile, $line);
                            } else {
                                continue;
                            }
                        }
//                        assert(is_array($rClass['classes']), dump($rClass));

                        foreach ($rClass['classes'] as $shortName => $c) {
                            $cs->addInclude($useNs = $c->getNs() . '\\' . $shortName, $includedFile);
                            array_push($newHeader, "use $useNs; // from $includedFile");
                        }

                        if ($cs->getClassname() == 'ca_users') {
                            // dd($includedFile, $r->getFileName(), $m, $rClass, $rClass['classes']);
                        }
                    }
                    $cs->setHeader(join("\n", $newHeader));
                }
                if (count($cs->getIncludes())) {
//                    dd($cs->getIncludes());
                }
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
        foreach ($files as $filename => $f) {
            /** @var ClassStructure  $cs */
            foreach ($f['classes'] as $className=>$cs) {
                $ns = $cs->getNs();

                // one class, and it matches, this is what we hope for
//                if ($className == basename($classFilename, '.php'))
                {
                    // just add the names

                    $newFilename = $this->bag->get('namespaced_dir') .   $cs->getFilenameToWrite();
                    $newDir = dirname($newFilename);
                    if (!is_dir($newDir)) {
                        try {
                            mkdir($newDir, 0777, true);
                        } catch (\Exception $e) {
                            dd($newDir, $e->getMessage());
                        }
                    }
                    // the new php replaces the required_once with the use statements, recursively...
                    // we can also figure out what use statements are necessary by searching the classes for their methods.
                    $newPhp = sprintf("<?php\n\nnamespace %s;\n%s\n\n%s", $cs->getNs(), $cs->getHeader(), $cs->getClassPhpText());
                    array_push($newFiles, $newFilename);
                    file_put_contents($newFilename, $newPhp);
                }
            }
//            dd($f);
        }


        return $this->render('app/reflection.html.twig', [
            'files' => $newFiles,
            'classes' => [] // get_declared_classes()
        ]);

        // find the CA classes
        foreach (get_declared_classes() as $class) {
            $r = (new \ReflectionClass($class));
            if ($r->isInternal()) {
                continue;
            }
            if (array_key_exists($r->getFileName(), $files) ) {
                // we have a winner!  Idea: create new namespaced files in a new directory.
                // we only want the part before the class starts.
                $headerLines = array_slice($phpLines, 0, $r->getStartLine());

                foreach ($headerLines as $line) {
                    $pattern = "/(include|require)_once\((.*?)\.[\"']\/(.*?).php[\"']\)/";
                    // walk through the includes and create namespaces from them.  Might not be any.
                    if (preg_match($pattern, $line, $m)) {
                        // if we get the full filename, we can look up the new class and replace it.
                        $ca_constant = $m[2];
                        if (!defined($ca_constant)) {
                            throw new \Exception($ca_constant . " not defined, " . $line . ' in ' . $r->getFileName());
                        }

                        $val = constant($ca_constant);
                        $includedFile = $val . '/' . $m[3] . '.php';
                        // files should have all the files
                        $rClass = $fileToClass[$includedFile];


                        dd($m, $rClass);

                    }
                }
                if ($r->getName() == 'Db') {
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
//        $dir = $bag->get('kernel.project_dir') . '/vendor/collectiveaccess/providence/app';

        $dir = $bag->get('kernel.project_dir') . '/../pr/app';

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
