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

    /**
     * ClassStructure constructor.
     * @param string $filename
     */
    public function __construct(\SplFileInfo $file, string $partToRemove)
    {
        $this->filename = $file->getRealPath();
        $this->originalPhp = file_get_contents($this->filename);
        $this->ns = str_replace($partToRemove, '', $file->getRealPath());
        $this->ns = str_replace('.php', '', $this->ns);
        $this->ns = str_replace('/', '\\', $this->ns);
        $this->ns = ltrim($this->ns,  '\\');
        $this->extractHeader();
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
        // really we just want the part between the opening <?php and the start of the class
        if (preg_match('/(<\?php(.*?)\n)(abstract |final )?(class |function )/ms', $this->originalPhp, $headerMatch)) {
            $autoloadMap = $this->getMap();
            $header = $headerMatch[1];
            // get all the includes, so we have them later when we need to dump them
            if (preg_match_all("/(include|require)_once\((.*?)\.[\"']\/(.*?).php[\"']\)/", $header, $mm, PREG_SET_ORDER)) {
                foreach ($mm as $m) {
                    list($orig, $incType, $path, $incFile) = $m;
                    $this->path = $path;
                    array_push($this->includes, $orig); // $incFile);
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
            $header = preg_replace_callback("/(include|require)_once\((.*?)\.[\"']\/(.*?).php[\"']\)/",
                function($m) use ($autoloadMap) {
                    $prefix=$m[2];
                    if (array_key_exists($prefix, $autoloadMap)) {
                        $use = sprintf("%s\%s", $autoloadMap[$prefix], str_replace('/', '\\', $m[3]));
                        $this->uses[$use] = $m[0];
                        return sprintf("// use %s; // %s", $use, $m[0]);
                    } else {
                        return $m[0]; // dont replace
                    }
                }, $oldHeader);

            $header  .=  sprintf("// all uses!\n%s\n//ENDOFUSES\n\n", $this->usesString);
//                dd($header);
            // get rid of the old namespace
            $header = preg_replace('/namespace [^ ]+;/', '', $header);
            // add the new one
            $header = str_replace("<?php", "<?php\n\nnamespace $namespace;\n", $header);
//                dd($header);

            // replace the header
            $php = str_replace($oldHeader, $header, $php);
        }  else {
            // probably a template or some definitions.  OK to keep as include
//                array_push($keepAsIncludes, $file->getFilename());
//                $this->logger->error("no class or function found", [$absoluteFilePath]);
        }

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
