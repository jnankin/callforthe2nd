<?
namespace Hackhouse\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Hackhouse\Abstracts\Entity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Hackhouse\UserBundle\Entity\ThirdPartyAccount
 *
 * @ORM\Table(name="third_party_account")
 * @ORM\Entity()
 * @UniqueEntity({"type", "uuid"})
 */
class ThirdPartyAccount extends Entity
{
    const GOOGLE = 'google';
    const FACEBOOK = 'facebook';
    const TWITTER = 'twitter';

    public static $accountTypes = array(
        self::GOOGLE,
        self::FACEBOOK,
        self::TWITTER
    );

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=10)
     */
    protected $type;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $uuid;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="thirdPartyAccounts")
     */
    protected $user;

}