<?

namespace Hackhouse\TwigExtensionsBundle\Extensions;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Form\FormView;

class HackhouseTwigExtension extends \Twig_Extension
{
    public function __construct($menus) {
        $this->menus = $menus;
    }
    
    public function getFunctions() 
    {
        return array(
            'app_parameter' => new \Twig_Function_Method($this, 'appParameter'),
            'has_flash' => new \Twig_Function_Method($this, 'hasFlash'),
            'write_flash_block' => new \Twig_Function_Method($this, 'writeFlashBlock', array('is_safe' => array('html'))),
            'write_form_errors' => new \Twig_Function_Method($this, 'writeFormErrors', array('is_safe' => array('html'))),
            'write_error_class' => new \Twig_Function_Method($this, 'writeErrorClass', array('is_safe' => array('html')))
        );

    }

    public function appParameter($name){
        return $this->container->getParameter($name);
    }

    public function writeErrorClass(FormView $element){
        return (count($element->vars['errors']) ? ' error' : '');
    }

    public function writeFormErrors(FormView $element){
        $errors = $element->vars['errors'];

        $ret = '<ul rel="'. $element->vars['id'] . '" class="errors">';
        foreach($errors as $error){
            $ret .= '<li>' . $error->getMessage() . '</li>';
        }
        $ret .= '</ul>';
        return $ret;
    }

    public function hasFlash($name){
        return $this->container->get('session')->hasFlash($name);
    }

    public function getFlash($name){
        return $this->container->get('session')->getFlash($name);
    }

    public function writeFlashBlock($name, $withDismiss = false){
        return $this->writeSuccessFlash($name . "Success", $withDismiss) . $this->writeErrorFlash($name . "Error", $withDismiss);
    }

    public function writeSuccessFlash($flashName, $withDismiss) {
        $ret = "";
        if ($this->hasFlash($flashName)) {
            $ret = "<div id='$flashName' class='alert alert-success'>";
            $ret .= $this->getFlash($flashName);
            if ($withDismiss) $ret .= '<a href="#" class="close" data-dismiss="alert">×</a>';
            $ret .= "</div>";
        }
        return $ret;
    }

    public function writeErrorFlash($flashName, $withDismiss) {
        $ret = "";
        if ($this->hasFlash($flashName)) {
            $ret = "<div id='$flashName' class='alert alert-error'>";
            $ret .= $this->getFlash($flashName);
            if ($withDismiss) $ret .= '<a href="#" class="close" data-dismiss="alert">×</a>';
            $ret .= "</div>";
        }
        return $ret;
    }

        // for a service we need a name
    public function getName()
    {
        return 'hh_twig_extension';
    }
    
    public function setContainer($container){
        $this->container = $container;
    }
}
