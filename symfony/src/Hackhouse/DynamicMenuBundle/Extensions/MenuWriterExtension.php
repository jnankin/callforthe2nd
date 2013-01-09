<?

namespace Hackhouse\DynamicMenuBundle\Extensions;

use Symfony\Component\DependencyInjection\ContainerAware;


class MenuWriterExtension extends \Twig_Extension
{
    public function __construct($menus) {
        $this->menus = $menus;
    }
    
    public function getFunctions() 
    {
        return array(
            'write_menu' => new \Twig_Function_Method($this, 'writeMenu'),
        );

    }
    
    public function writeMenu($menuName, $selected = ''){
        $menu = $this->menus[$menuName];
        
        if (!$menu){
            throw new \InvalidArgumentException("The menu $menuName does not exist");
        }
        
        $items = null;
        
        //get the correct menu based on permissions
        foreach($menu['versions'] as $name => $params){
            if (!$params['roles'] || count($params['roles']) == 0){
                $items = $params['items'];
            }
            else {
                $roles = $params['roles'];
                if (!is_array($roles)) $roles = array($roles);
                
                foreach($params['roles'] as $role){
                    if (!$this->container->get('security.context')->isGranted($role)){
                        continue;
                    }
                }
                
                $items = $params['items'];
            }
        }
        
        if (!$items) {
            throw Exception("No menu found for this authorization level.");
        }
        
        if ($menu['renderer']){
            $renderer = new $menu['renderer'];
            $renderer->setRouteHelper($this->container->get('templating.helper.router'));
            return $renderer->render($items, $selected);
        }
        else {
            
        }
    }
    
    // for a service we need a name
    public function getName()
    {
        return 'menu_writer';
    }
    
    public function setContainer($container){
        $this->container = $container;
    }
    


}
