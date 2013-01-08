<?
namespace Hackhouse\Abstracts;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as SymfonyController;
use Symfony\Component\Form\Form;
use Symfony\Bridge\Twig\TwigEngine;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\ParameterBag;
use Hackhouse\Utils\Utils;
use TMD\UserBundle\Entity\Group;

abstract class Controller extends SymfonyController {

    public function persistEntityAndFlush($entity){
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function getParameter($name, $default = null){
        if ($this->getRequest()->request->has($name)){
            return $this->getRequest()->request->get($name);
        }
        else if ($this->getRequest()->query->has($name)){
            return $this->getRequest()->query->get($name);
        }
        return $default;
    }

    /**
     * @return \TMD\UserBundle\Entity\User
     */
    public function getUser($refreshFromDb = false){
        $user = $this->get('security.context')->getToken()->getUser();

        if ($refreshFromDb){
            $user = $this->getRepository('TMDUserBundle:User')->find($user->getId());
        }

        return $user;
    }

    public function getUserRoles(){
        return $this->get('security.context')->getToken()->getRoles();
    }

    public function formPostedAndValid(Form $form){
        return \Hackhouse\Utils\FormUtils::formPostedAndValid($this->getRequest(), $form);
    }

    public function isLoggedIn(){
        return $this->isGranted(Group::ROLE_USER);
    }

    public function isGranted($role){
        return $this->getService('security.context')->isGranted($role);
    }

    public function setFlash($name, $message){
        $this->get('session')->setFlash($name, $message);
    }

    /**
     * @return TwigEngine;
     */
    public function getTemplating(){
        return $this->getContainer()->get('templating');
    }

    /**
     * @return \Swift_Mailer
     */
    public function getMailer(){
        return $this->getContainer()->get('mailer');
    }

    /**
     * @return EntityRepository;
     */
    public function getRepository($name){
        return $this->getContainer()->get('doctrine')->getRepository($name);
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(){
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }


    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }


    public function getService($name){
        return $this->getContainer()->get($name);
    }

    public function generateExternallyAccessibleUrl($route, $parameters = array(), $absolute = false){
       return $this->getContainer()->getParameter('externally_accessible_host') . $this->generateUrl($route, $parameters);
    }

    public function hasEntityPermissions($object, $permissions){
        if (!$this->isLoggedIn()) return false;
        return $this->getService('user_manager')->
            checkEntityPermissions($this->getUser(), $permissions, $object);
    }

    /**
     * @param $to
     * @param $subject
     * @param $txtMessage
     * @param $htmlMessage
     * @return \Swift_Message
     */
    public function createEmailMessage($to, $subject, $txtMessage, $htmlMessage, $twigParams = array()){
        $twigParams['subject'] = $subject;
        return $this->createRawEmailMessage($to, $subject,
            $txtMessage ? $this->getTemplating()->render($txtMessage, $twigParams) : null,
            $htmlMessage ? $this->getTemplating()->render($htmlMessage, $twigParams) : null
        );
    }

    public function createRawEmailMessage($to, $subject, $txtMessage, $htmlMessage){
        $message = $this->getMailer()->createMessage()
            ->setSubject($subject)
            ->setFrom($this->getContainer()->getParameter('mailer_from'))
            ->setTo($to)
            ->setBody($txtMessage);

        if ($htmlMessage) $message->addPart($htmlMessage, 'text/html');

        return $message;
    }



    public function requirePostParam($param, $default = null, $validFormat = 'string'){
        return $this->requireParam($this->getRequest()->request, $param, $default, $validFormat);
    }

    public function requireGetParam($param, $default = null, $validFormat = 'string'){
        return $this->requireParam($this->getRequest()->query, $param, $default, $validFormat);
    }

    private function requireParam(ParameterBag $collection, $param, $default, $validFormat = 'string') {
        if (!$collection->has($param)) {
            if ($default) return $default;
            throw new \Exception("Parameter '$param' is required for this action.");
        } else if (!Utils::simpleValidate($collection->get($param), $validFormat)) {
            throw new \Exception("Parameter '$param' is invalid for this action.");
        }

        return $collection->get($param);
    }

    public function requireGetParams($params) {
        foreach ($params as $param)
            $this->requireGetParam($param);
    }

    public function requirePostParams($params) {
        foreach ($params as $param)
            $this->requirePostParam($param);
    }


    public function logDebug($message){
        $this->log($message, 'debug');
    }

    public function logError($message){
        $this->log($message, 'error');
    }

    public function logInfo($message){
        $this->log($message, 'info');
    }

    public function logNotice($message){
        $this->log($message, 'notice');
    }

    public function logWarn($message){
        $this->log($message, 'notice');
    }

    public function log($message, $level){
        if ($message instanceof \Exception){
            $message = $message->getMessage() . ' ' . $message->getTraceAsString();
        }

        switch($level){
            case 'debug':
                $this->getService('logger')->debug($message);
            break;
            case 'error':
                $this->getService('logger')->err($message);
                break;
            case 'info':
                $this->getService('logger')->info($message);
                break;
            case 'notice':
                $this->getService('logger')->notice($message);
                break;
            case 'warn':
                $this->getService('logger')->warn($message);
                break;
        }
    }

    public function extractDomainObjectFromRequest($bundle, $entity, $param = null, $method = 'get',
                                                   $required = true, $secure = false) {
        if ($param == null) {
            $param = lcfirst($entity) . "Id";
            $param{0} = strtolower($param{0});
        }

        if (!$required && !$this->getParameter($param)){
            return null;
        }

        if ($method == 'get'){
            $id = $this->requireGetParam($param);
        }
        else if ($method == 'post'){
            $id = $this->requirePostParam($param);
        }
        else {
            $id = $this->getParameter($param);
        }

        if (!is_numeric($id)) {
            throw $this->createNotFoundException("This $table does not exist.");
        }

        $obj = $this->getRepository("$bundle:$entity")->find($id);

        if ($obj) {
            if ($secure && !$obj->isAccessibleForUser($this->getUser())){
                throw $this->createNotFoundException('User attempted to access entity without proper credentials');
            }

            return $obj;
        }
        else {
            throw $this->createNotFoundException("Could not find $table with id=$id");
        }
    }

    /**
     * @param $entityName
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createQuery($fullEntityName){
        $entityName = explode(':', $fullEntityName);
        $entityName = end($entityName);

        $alias = '';
        for ($i = 0; $i < strlen($entityName); $i++){
            if ($entityName{$i} === strtoupper($entityName{$i})){
                $alias .= $entityName{$i};
            }
        }

        $alias = strtolower($alias);
        return $this->getRepository($fullEntityName)->createQueryBuilder($alias);
    }

    public function persistAndFlush($entity){
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

}