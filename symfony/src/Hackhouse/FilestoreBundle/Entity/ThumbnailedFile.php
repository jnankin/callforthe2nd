<?php

namespace Hackhouse\FilestoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hackhouse\Abstracts\Entity;

/**
 * Hackhouse\FilestoreBundle\Entity\ThumbnailedFile
 *
 * @ORM\Table(name="thumbnailed_file")
 * @ORM\Entity
 */
class ThumbnailedFile extends Entity
{
    const PROFILE_LARGE = 128;
    const PROFILE_MEDIUM = 50;
    const PROFILE_SMALL = 23;

    const PHOTO_LARGE = 500;
    const PHOTO_MEDIUM = 320;
    const PHOTO_SMALL = 150;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="FilestoreFile"))
     */
    protected $target;


    /**
     * @ORM\ManyToOne(targetEntity="FilestoreFile"))
     * 128x128
     */
    protected $large;


    /**
     * @ORM\ManyToOne(targetEntity="FilestoreFile"))
     * 50x50
     */
    protected $medium;


    /**
     * @ORM\ManyToOne(targetEntity="FilestoreFile"))
     * 23x23
     */
    protected $small;

    public function serialize(){
        return array(
            'target' => $this->getTarget()->getPath(),
            'small' => $this->getSmall()->getPath(),
            'medium' => $this->getMedium()->getPath(),
            'large' => $this->getLarge()->getPath()
        );
    }

}