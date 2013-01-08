<?
namespace Hackhouse\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Hackhouse\Abstracts\Entity;

/**
 * @ORM\MappedSuperclass
 */
abstract class EntityACE extends Entity
{
    const EDIT = 'edit';
    const DELETE = 'delete';

    /**
     * @ORM\ManyToOne(targetEntity="\Hackhouse\UserBundle\Entity\User")
     */
    protected $user;

    /**
     * @ORM\Column(name="can_edit", type="boolean")
     */
    protected $edit = false;

    /**
     * @ORM\Column(name="can_delete", type="boolean")
     */
    protected $delete = false;

    abstract public function getTargetEntity();
    abstract public function setTargetEntity(AccessControlledEntity $entity);

    public function can($permission){
        return $this->$permission;
    }

    public function addPermission($permission){
        $this->$permission = true;
    }

    public function revokePermission($permission){
        $this->$permission = false;
    }

}