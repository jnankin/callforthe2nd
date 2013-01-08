<?
namespace Hackhouse\UserBundle\Entity;
use Hackhouse\Abstracts\Entity;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\Common\Collections\ArrayCollection;

/** @MappedSuperclass */
abstract class AccessControlledEntity extends Entity
{
    protected $aces;

    public function __construct(){
        parent::__construct();
        $this->aces = new ArrayCollection();
    }

    public function addAce(User $user){
        $className = '\\' . get_class($this) . "ACE";

        $ace = new $className;
        $ace->setUser($user);
        $ace->setTargetEntity($this);

        $this->aces->add($ace);

        return $ace;
    }
}