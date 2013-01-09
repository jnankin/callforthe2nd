<?
namespace Hackhouse\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Hackhouse\Abstracts\Entity;
use Hackhouse\Utils\Utils;

/**
 * Hackhouse\UserBundle\Entity\User
 *
 * @ORM\Table(name="hackhouse_user")
 * @ORM\Entity(repositoryClass="Hackhouse\UserBundle\Entity\UserRepository")
 * @ORM\HasLifecycleCallbacks
 */
class User extends Entity implements AdvancedUserInterface
{

    const PASSWORD_MIN_LENGTH = 6;
    const PASSWORD_MIN_LENGTH_MESSAGE = 'Password must be at least 6 characters.';
    const PASSWORD_REGEX = '/^\w*(?=\w*\d)\w*$/';
    const PASSWORD_REGEX_MESSAGE = 'Password must contain at least one number.';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=50, unique=true)
     */
    protected $username;

    /**
     * @Assert\Email(message="The email '{{ value }}' is not a valid email.")
     * @ORM\Column(type="string", length=50)
     */
    protected $email;

    /**
     * @ORM\Column(name="first_name", type="string", length=100, nullable=true)
     */
    protected $firstName;

    /**
     * @ORM\Column(name="last_name", type="string", length=100, nullable=true)
     */
    protected $lastName;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $timezone = "America/Chicago";

    /**
     * @ORM\Column(type="string", length=32)
     */
    protected $salt;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    protected $password;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    protected $isActive;

    /**
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="users", fetch="EAGER")
     */
    protected $groups;

    /**
     * @ORM\Column(name="phone_number", type="string", length=15, nullable=true)
     */
    protected $phoneNumber;


    /**
     * @ORM\OneToOne(targetEntity="ActivationToken", mappedBy="user")
     */
    protected $activationToken;

    /**
     * @ORM\OneToOne(targetEntity="Hackhouse\SharedBundle\Entity\Speaker", mappedBy="user")
     */
    protected $speaker;


    /**
     * @ORM\OneToMany(targetEntity="ThirdPartyAccount", mappedBy="user")
     */
    protected $thirdPartyAccounts;

    /**
     * @ORM\OneToOne(targetEntity="\Hackhouse\FilestoreBundle\Entity\ThumbnailedFile", fetch="EAGER")
     */
    protected $profilePic;

    /**
     * @ORM\OneToMany(targetEntity="\Hackhouse\SharedBundle\Entity\PortalUser", mappedBy="user")
     */
    protected $portalUsers;


    public function __construct()
    {
        parent::__construct();

        $this->groups = new ArrayCollection();
        $this->thirdPartyAccounts = new ArrayCollection();
        $this->portalUsers = new ArrayCollection();

        $this->isActive = false;
        $this->salt = md5(uniqid(null, true));
    }

    public function eraseCredentials()
    {
    }

    public function equals($user)
    {
        return $this->username === $user->getUsername();
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->isActive;
    }

    public function getRoles(){
        $roles = array();

        foreach($this->groups as $group){
            $roles[] = $group->getRole();
        }
        $roles[] = Group::ROLE_USER;
        return $roles;
    }

    public function setEncryptedPassword($encryptedPassword)
    {
        $this->password = $encryptedPassword;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string The salt
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->username;
    }

    public function getFullName(){
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    public function getGravatarUrl(){
        return "http://www.gravatar.com/avatar/" . md5($this->getEmail()) . "?d=";
    }

    public function onlyHasThirdPartyLogin(){
        if (!Utils::validEmail($this->getUsername())){
            foreach(ThirdPartyAccount::$accountTypes as $type){
                if (Utils::startsWith($this->getUsername(), $type)) return $type;
            }
        }

        return false;
    }

    public function hasThirdPartyAccountType($type){
        foreach ($this->getThirdPartyAccounts() as $account){
            if ($account->getType() == $type) return true;
        }

        return false;
    }

    public function getThirdPartyAccountByType($type){
        foreach ($this->getThirdPartyAccounts() as $account){
            if ($account->getType() == $type) return $account;
        }

        return null;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preSave(){
        $this->setPhoneNumber(Utils::cleanPhoneNumber($this->getPhoneNumber()));
    }

    public function serialize(){
        return array(
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'id' => $this->getId()
        );
    }

    public function getUtcOffset(){
        $date = new \DateTime("now", new \DateTimeZone($this->getTimezone()));
        return $date->getOffset();
    }

}