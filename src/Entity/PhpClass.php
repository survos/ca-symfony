<?php

namespace App\Entity;

use App\Repository\PhpClassRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PhpClassRepository::class)
 */
class PhpClass
{
    public const RAW_INCLUDE = 'raw_include';

    public const NORMAL_CLASS = 'class';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=PhpFile::class, inversedBy="phpClasses")
     * @ORM\JoinColumn(nullable=false)
     */
    private $phpFile;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $originalPhp;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $realPath;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $namespace;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $functionList = [];

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $header;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $includes = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $relativePath;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $extends;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $implements = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPhpFile(): ?PhpFile
    {
        return $this->phpFile;
    }

    public function setPhpFile(?PhpFile $phpFile): self
    {
        $this->phpFile = $phpFile;

        return $this;
    }

    public function getOriginalPhp(): ?string
    {
        return $this->originalPhp;
    }

    public function setOriginalPhp(?string $originalPhp): self
    {
        $this->originalPhp = $originalPhp;

        return $this;
    }

    public function guessNamespace()
    {
        $path = PhpFile::applyNamespaceHacks($this->getPhpFile()->getRelativePath());
        return str_replace('/', '\\', 'CA/' . $path);
    }

    public function getUse()
    {
            return $this->guessNamespace() . '\\' . $this->getName();
//        return $this->isClass() ? $this->guessNamespace() . '\\' . $this->getName() : '';
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = trim($type);

        return $this;
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

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function setNamespace(?string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function getFunctionList(): ?array
    {
        return $this->functionList;
    }

    public function setFunctionList(?array $functionList): self
    {
        $this->functionList = $functionList;

        return $this;
    }

    public function isRawInclude(): bool
    {
        return $this->getType() === self::RAW_INCLUDE;
    }

    public function isClass(): bool
    {
        return $this->getType() === self::NORMAL_CLASS;
    }


    public function getHeader(): ?string
    {
        return $this->header;
    }

    public function setHeader(?string $header): self
    {
        $this->header = $header;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

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

    public function __toString()
    {
        return $this->getRealPath();
    }

    public function getExtends(): ?string
    {
        return $this->extends;
    }

    public function setExtends(?string $extends): self
    {
        $this->extends = $extends;
        if ($extends) {
//            $this->getPhpFile()->addUse($extends);
        }
        return $this;
    }

    public function getImplements(): ?array
    {
        return $this->implements;
    }

    public function setImplements(?array $implements): self
    {
        $this->implements = $implements;
        if ($implements) {
//            array_walk($implements, fn($useStr) => $this->getPhpFile()->addUse($useStr));
        }

        return $this;
    }
}
