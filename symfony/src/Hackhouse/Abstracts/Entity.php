<?
namespace Hackhouse\Abstracts;
use Doctrine\ORM\Mapping as ORM;

/** @ORM\MappedSuperclass */
abstract class Entity {

    public function __construct(){

    }

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;


    public function isNew(){
        return !$this->getId();
    }


    public function getId(){
        return $this->id;
    }

    /**
     * Provides getter and setter methods.
     *
     * @param  string $method    The method name
     * @param  array  $arguments The method arguments
     *
     * @return mixed The returned value of the called method
     */
    public function __call($method, $arguments)
    {
        if (in_array($verb = substr($method, 0, 3), array('set', 'get')))
        {
            $name = substr($method, 3);
            $name = lcfirst($name);


            if (property_exists(get_class($this), $name)){
                if ($verb == 'set'){
                    $this->$name = $arguments[0];
                }
                else if ($verb == 'get'){
                    return $this->$name;
                }
            }
        }
    }

    public function __set($name, $value)
    {
        $name = lcfirst($name);

        if (property_exists(get_class($this), $name)){
            $this->$name = $value;
        }
    }

    public function __isset($name){
        $name = lcfirst($name);
        return property_exists(get_class($this), $name);
    }

    public function __get($name)
    {
        $name = lcfirst($name);
        return $this->$name;
    }

    public function equals($entity){
        if ($entity == null) return false;
        return get_class($this) == get_class($entity) && $this->getId() == $entity->getId();
    }

    public function isAccessibleForUser(\TMD\UserBundle\Entity\User $user){
        return $user->equals($this->getUser());
    }

    public function exists(){
        return $this->getId() != null;
    }

    public function __toString(){
    	
    	$name = get_class($this);
    	$string = join('', array_slice(explode('\\', $name), -1));
    	if(property_exists($this,"id"))$string .= ":".$this->id;
    	if(property_exists($this,"name"))$string .= ":".$this->name;
        return $string;
    }
}