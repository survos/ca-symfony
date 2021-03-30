<?php

namespace App\Entity;

use App\Repository\PhpFileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass=PhpFileRepository::class)
 * @ORM\Table(
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="realpath",
 *            columns={"real_path"})
 *    }
 * )
 */
class PhpFile
{
    public const IS_INC = 'inc'; // e.g. setup or a function list

    public const IS_CLASS = 'class'; // one or more classes defined

    public const IS_SCRIPT = 'script'; // like index.php

    public const IS_VIEW = 'view'; // template

    public const IS_INTERFACE = 'interface';

    public const IS_TRAIT = 'trait'; // one or more classes defined

    public const IS_IGNORED = 'ignored'; // one or more classes defined

    public const IS_CLI = 'cli'; // one or more classes defined

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $realPath;

    /**
     * @ORM\Column(type="text")
     */
    private $rawPhp;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $filename;

    /**
     * @ORM\OneToMany(targetEntity=PhpClass::class, mappedBy="phpFile", orphanRemoval=true)
     */
    private $phpClasses;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $relativeFilename;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $relativePath;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $headerPhp;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $includes = [];

    /**
     * @ORM\Column(type="array")
     */
    private $initialIncludes = [];

    public function __construct($realPath, array $dirsToRemove)
    {
        $cleanerDir = $realPath;
        foreach ($dirsToRemove as $dirToRemove)
        {
            $cleanerDir = str_replace($dirToRemove . '/', '', $cleanerDir);
        }
        $this
            ->setRealPath($realPath)
            ->setRelativeFilename($cleanerDir)
            ->setRelativePath(pathinfo($this->getRelativeFilename(), PATHINFO_DIRNAME))
            ->setRawPhp(trim(file_get_contents($realPath)))
        ;
        $this->phpClasses = new ArrayCollection();
    }

    public static function applyNamespaceHacks($path)
    {
        // hack for keyword problem.
        $path = preg_replace('|/lib/Print|', '/lib/PrintUtilities', $path);
        return $path;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRealPath(): ?string
    {
        return $this->realPath;
    }

    public function setRealPath(string $realPath): self
    {
        $this->realPath = $realPath;

        return $this;
    }

    public function getRawPhp(): ?string
    {
        return $this->rawPhp;
    }

    public function setRawPhp(string $rawPhp): self
    {
        // look for 'class' in the first column, if not, look for functions
        $this->rawPhp = $rawPhp;
        if (preg_match('/^(abstract |final |public )?(class |interface |trait )([A-Za-z_0-9]+)\s/im', $rawPhp, $m))
        {
            $this->setStatus(self::IS_CLASS);
        }
        elseif (preg_match('/interface ([A-Za-z_0-9]+) /i', $rawPhp, $m))
        {
            $this->setStatus(self::IS_INTERFACE);
        }
        elseif (preg_match('/trait ([A-Za-z_0-9]+) /i', $rawPhp, $m))
        {
            $this->setStatus(self::IS_TRAIT);
        }
        elseif (preg_match('/function ([A-Za-z_0-9]+)[\s|(]/i', $rawPhp, $m))
        {
            $this->setStatus(self::IS_INC);
        }
        elseif (preg_match('|^#!/usr/|', $rawPhp, $m))
        {
            $this->setStatus(self::IS_CLI);
        }
        elseif (preg_match('|setup\.php|', $rawPhp, $m))
        {
            $this->setStatus(self::IS_SCRIPT);
        }
        elseif (preg_match('{/views|printTemplates/}', $this->getRealPath(), $m))
        {
            $this->setStatus(self::IS_VIEW);
        }
        elseif (preg_match('{/Flickr|demos|/old/}', $this->getRealPath(), $m))
        {
            $this->setStatus(self::IS_IGNORED);
        }
        elseif (preg_match('|/mkstopwords|', $this->getRealPath(), $m))
        {
            $this->setStatus(self::IS_CLI);
        }
        elseif (preg_match('|/tools/merge|', $this->getRealPath(), $m))
        {
            $this->setStatus(self::IS_CLI);
        }
        elseif (preg_match('{/app/lib|(preload|version)\.php}', $this->getRealPath(), $m))
        {
            $this->setStatus(self::IS_INC);
        }
        else
        {
            if ($this->getPhpClasses()->count())
            {
                $this->setStatus(self::IS_CLASS);
            }
            else
            {
                dd($this->getRealPath(), $rawPhp);
            }
        }
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getFilenameWithoutExtension()
    {
        return pathinfo($this->getRealPath(), PATHINFO_BASENAME);
    }

    /**
     * @return Collection|PhpClass[]
     */
    public function getPhpClasses(): Collection
    {
        return $this->phpClasses;
    }

    public function addPhpClass(PhpClass $phpClass): self
    {
        if (!$this->phpClasses->contains($phpClass))
        {
            $this->phpClasses[] = $phpClass;
            $phpClass->setPhpFile($this);
        }

        return $this;
    }

    public function removePhpClass(PhpClass $phpClass): self
    {
        if ($this->phpClasses->removeElement($phpClass))
        {
            // set the owning side to null (unless already changed)
            if ($phpClass->getPhpFile() === $this)
            {
                $phpClass->setPhpFile(null);
            }
        }

        return $this;
    }

    public function getRelativeFilename(): ?string
    {
        return $this->relativeFilename;
    }

    public function setRelativeFilename(string $relativeFilename): self
    {
        $this->relativeFilename = $relativeFilename;

        return $this;
    }

    public function getRelativePath(): ?string
    {
        return $this->relativePath;
    }

    public function setRelativePath(string $relativePath): self
    {
        $this->relativePath = $relativePath;

        return $this;
    }

    public function getHeaderPhp(): ?string
    {
        return $this->headerPhp;
    }

    public function setHeaderPhp(?string $headerPhp): self
    {
        $this->headerPhp = $headerPhp;

        return $this;
    }

    public function getIncludes(): ?array
    {
        return $this->includes;
    }

    public function setIncludes(?array $includes): self
    {
        $this->includes = $includes;

        return $this;
    }

    // return true if realpath is added to list
    public function addInclude($realpath, $info): bool
    {
        if (array_key_exists($realpath, $this->includes))
        {
            return false;
        }
        $this->includes[$realpath] = $info;
        return true;
    }

    public function getInitialIncludes(): ?array
    {
        return $this->initialIncludes;
    }

    public function setInitialIncludes(array $initialIncludes): self
    {
        $this->initialIncludes = $initialIncludes;

        return $this;
    }
}
