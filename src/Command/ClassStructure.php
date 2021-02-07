<?php
// Simple class to handle classes during transition to psr-4 namespace


namespace App\Command;

class ClassStructure
{
    private string $filename;
    private string $ns;
    private string $php;
    private ?string $originalPhp;
    private string $classname;
    private string $path = '~'; // root
    private array $includes = [];
    private int $lineCount = 0;
    private $classList = [];
    private ?string $header = null;
    private string $originalheader;
    private string $status;

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return ClassStructure
     */
    public function setStatus(string $status): ClassStructure
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return false|string|null
     */
    public function getOriginalPhp()
    {
        return $this->originalPhp;
    }

    /**
     * @param false|string|null $originalPhp
     * @return ClassStructure
     */
    public function setOriginalPhp($originalPhp)
    {
        $this->originalPhp = $originalPhp;
        return $this;
    }

    /**
     * @return string
     */
    public function getHeader(): ?string
    {
        return $this->header;
    }

    /**
     * @param string $header
     * @return ClassStructure
     */
    public function setHeader(string $header): ClassStructure
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalheader(): string
    {
        return $this->originalheader;
    }

    /**
     * @param string $originalheader
     * @return ClassStructure
     */
    public function setOriginalheader(string $originalheader): ClassStructure
    {
        $this->originalheader = $originalheader;
        return $this;
    }

    /**
     * @return array
     */
    public function getClassList(): array
    {
        return $this->classList;
    }

    /**
     * @param array $classList
     */
    public function setClassList(array $classList): void
    {
        $this->classList = $classList;
    }

    /**
     * @return int
     */
    public function getLineCount(): int
    {
        return $this->lineCount;
    }

    /**
     * @param int $lineCount
     */
    public function setLineCount(int $lineCount): void
    {
        $this->lineCount = $lineCount;
    }

    /**
     * ClassStructure constructor.
     * @param string $filename
     */
    public function __construct(\SplFileInfo $file, string $dirPathToRemove)
    {
        $this->status = 'loading';
        // total hack!
        if (!defined('__CA_BASE_DIR__')) {
            // hack for installing via composer
            define('__CA_BASE_DIR__', realpath(__DIR__ . '/../../vendor/collectiveaccess/providence'));
            define('__CA_APP_NAME__', 'ca');
            if (!is_dir(__CA_BASE_DIR__)) {
                throw new \Exception(__CA_MODELS_DIR__ . ' is not a valid directory');
            }
            include __CA_BASE_DIR__ . '/app/helpers/post-setup.php';
        }
        $this->filename = $file->getRealPath();
        $this->originalPhp = file_get_contents($this->filename);
        $this->lineCount = count(file($this->filename)) - 1;;


        $this->path = str_replace($dirPathToRemove, '', $file->getRealPath());

        $this->ns = str_replace('.php', '', $this->path);
        $this->ns = str_replace('/', '\\', $this->ns);
        $this->ns = ltrim($this->ns, '\\');
        //
//        $this->setIncludes($this->processHeader());
//        dd($this->getIncludes());
//        $this->extractHeader();
    }


    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     * @return ClassStructure
     */
    public function setFilename(string $filename): ClassStructure
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @return string
     */
    public function getNs(): string
    {
        return $this->ns;
    }

    /**
     * @return array
     */
    public function getIncludes(): array
    {
        return $this->includes;
    }

    /**
     * @param array $includes
     * @return ClassStructure
     */
    public function setIncludes(array $includes): ClassStructure
    {
        $this->includes = $includes;
        return $this;
    }

    /**
     * @param string $ns
     * @return ClassStructure
     */
    public function setNs(string $ns): ClassStructure
    {
        $this->ns = $ns;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhp(): string
    {
        return $this->php;
    }

    /**
     * @param string $php
     * @return ClassStructure
     */
    public function setPhp(string $php): ClassStructure
    {
        $this->php = $php;
        return $this;
    }

    /**
     * @return string
     */
    public function getClassname(): string
    {
        return $this->classname;
    }

    /**
     * @param string $classname
     * @return ClassStructure
     */
    public function setClassname(string $classname): ClassStructure
    {
        $this->classname = $classname;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return ClassStructure
     */
    public function setPath(string $path): ClassStructure
    {
        $this->path = $path;
        return $this;
    }



    //
    private function extractHeader()
    {

        dd("old...");
        // really we just want the part between the opening <?php and the start of the class
        if (preg_match('/(<\?php(.*?)\n)(abstract |final )?(class |function )/ms', $this->originalPhp, $headerMatch)) {
            $autoloadMap = $this->getMap();
            $header = $headerMatch[1];
            // get all the includes, so we have them later when we need to dump them
            if (preg_match_all("/(include|require)_once\(((.*?)\.[\"']+((.*?).php)[\"']+)\)/", $header, $mm, PREG_SET_ORDER)) {
                foreach ($mm as $m) {
                    list($orig, $incType, $include, $path, $phpFile, $incFile) = $m;
                    // map the path to the real root
                    if (defined($path)) {
                        $actualPath = constant($path) . $phpFile;
                    } else {
                        dd($path);
                    }
                    $this->path = $path;
//                    dump($include, $actualPath);
//                    $actualPath = eval(" echo $include;");
                    array_push($this->includes, $actualPath); // $include); // $incFile);
//                    dd($this->includes);
//                    $this->setNs(str_replace('/', '\\', $incFile));
//                    if (array_key_exists($path, $autoloadMap)) {
////                        $use = sprintf("%s\%s", $autoloadMap[$path], $ns);
//                    }
//                    dd($this->getFilename(), $m, $this->getNs(), $this->getPath());
                }
            }
            
            return;
            

//                dd($header);
            // we need to add a Use for every class that's in the
            $autoloadMap = $this->getMap();
            $header = preg_replace_callback(
                "/(include|require)_once\((.*?)\.[\"']\/(.*?).php[\"']\)/",
                function ($m) use ($autoloadMap) {
                    $prefix=$m[2];
                    if (array_key_exists($prefix, $autoloadMap)) {

                        $use = sprintf("%s\%s", $autoloadMap[$prefix], str_replace('/', '\\', $m[3]));
                        $this->uses[$use] = $m[0];
                        return sprintf("// use %s; // %s", $use, $m[0]);
                    } else {
                        return $m[0]; // dont replace
                    }
                },
                $oldHeader
            );

            $header  .=  sprintf("// all uses!\n%s\n//ENDOFUSES\n\n", $this->usesString);
//                dd($header);
            // get rid of the old namespace
            $header = preg_replace('/namespace [^ ]+;/', '', $header);
            // add the new one
            $header = str_replace("<?php", "<?php\n\nnamespace $namespace;\n", $header);
//                dd($header);

            // replace the header
            $php = str_replace($oldHeader, $header, $php);
        } else {
            // probably a template or some definitions.  OK to keep as include
//                array_push($keepAsIncludes, $file->getFilename());
//                $this->logger->error("no class or function found", [$absoluteFilePath]);
        }
    }

    public function getNamespaceFromFilename($filename): ?string
    {
        $namespace = null;
        if (preg_match('|app|', $filename)) {
            // hack!
//            $namespace = str_replace('vendor/collectiveaccess/providence/', '', $filename);
            // use the map?  Or just make everything CA?
//            $namespace = str_replace('app/lib', 'CA/lib', $namespace);

            $namespace = preg_replace('|^.*?/app/|', 'CA\\', $filename);
            $namespace = str_replace('/', '\\', $namespace);
            $namespace = str_replace('.php', '', $namespace);
        }
        return $namespace;
    }


    public function top($n=1800)
    {
        return substr($this->originalPhp, 0, $n);
    }

    public function createNewHeader()
    {
        // replace the requires with use, etc.
        dd($this->includes);
            //
//            dd($mm[0]);
            // we need to add a Use for every class that's in the
        if (preg_match($pattern = '/(<\?php.*?\n)(abstract |final |public )?(class |function |interface |trait )/ms', $php, $mm)) {

            $header = preg_replace_callback(
                $pattern,
                function ($m) use ($uses) {
                    try {
                        $prefix=$m[2];
                        if (array_key_exists($prefix, $this->getMap())) {
                            $use = sprintf("%s\%s", $this->getMap()[$prefix], str_replace('/', '\\', $m[3]));
                            $uses[$use] = $m[0];
//                            dd($uses, $prefix, $this->getMap());
                            return sprintf("// use %s; // %s", $use, $m[0]);
                        } else {
                            return $m[0]; // dont replace
                        }
                    } catch (\Exception $exception) {
                        dd($m, $exception->getMessage());
                    }
                },
                $oldHeader
            );

            // do this later
//            $header  .=  sprintf("// all uses!\n%s\n//ENDOFUSES\n\n", $this->usesString);
//                dd($header);
            // get rid of the old namespace
//            $header = preg_replace('/namespace [^ ]+;/', '', $header);
            // add the new one
//            $header = str_replace("<?php", "<?php\n\nnamespace $namespace;\n", $header);
//                dd($header);

            // replace the header
            $php = str_replace($oldHeader, $header, $php);
        } else {
            dump(substr($php, 0, 1024));
//            throw new \Exception($this->filename . " Pattern $pattern not found in  " . substr($php, 0, 2300));

//            return [];
            // probably a template or some definitions.  OK to keep as include
//                array_push($keepAsIncludes, $file->getFilename());
//                $this->logger->error("no class or function found", [$absoluteFilePath]);
        }
        return $this->includes;
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
}
