<?
namespace Hackhouse\DynamicMenuBundle\Extensions;

abstract class MenuRenderer
{
    private $routeHelper;
    abstract public function render($menuItems);
    
    public function setRouteHelper($routeHelper){
        $this->routeHelper = $routeHelper;
    }
    
    public function getRouteHelper(){
        return $this->routeHelper;
    }
}
