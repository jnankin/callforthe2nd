<?php

namespace Hackhouse\FilestoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Hackhouse\Abstracts\Entity;

/**
 * Hackhouse\FilestoreBundle\Entity\FilestoreFile
 *
 * @ORM\Table(name="filestore_file")
 * @ORM\Entity(repositoryClass="Hackhouse\FilestoreBundle\Entity\FilestoreFileRepository")
 * @UniqueEntity({"path"})
 */
class FilestoreFile extends Entity
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $path
     *
     * @ORM\Column(name="path", type="string", length=255)
     */
    protected $path;

    public function getDirectory(){
        return dirname($this->getPath());
    }

}