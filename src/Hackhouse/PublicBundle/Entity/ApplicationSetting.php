<?
namespace Hackhouse\PublicBundle\Entity;

use Symfony\Component\Security\Core\Role\RoleInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Hackhouse\Abstracts\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="application_setting")
 * @ORM\Entity()
 */
class ApplicationSetting extends Entity
{

    /**
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    protected $name;

    /**
     * @ORM\Column(name="value", type="string", length=255)
     */
    protected $value;

}