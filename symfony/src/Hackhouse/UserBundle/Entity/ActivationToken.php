<?php

namespace Hackhouse\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToOne;
use Hackhouse\Abstracts\Entity;

/**
 * Hackhouse\UserBundle\Entity\ActivationToken
 *
 * @ORM\Table(name="activation_token")
 * @ORM\Entity(repositoryClass="Hackhouse\UserBundle\Entity\ActivationTokenRepository")
 */
class ActivationToken extends Entity
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
     * @var text $token
     *
     * @ORM\Column(name="token", type="text")
     */
    protected $token;


    /**
     * @OneToOne(targetEntity="User", inversedBy="activationToken"))
     */
    protected $user;

    public function __toString(){
        return $this->getToken();
    }
}