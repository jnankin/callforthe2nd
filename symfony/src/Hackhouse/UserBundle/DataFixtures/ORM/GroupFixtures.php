<?php


namespace Hackhouse\BlogBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Hackhouse\UserBundle\Entity\Group;


class GroupFixtures extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
    	
    	$group = new Group();
    	$all_groups = array(
          Group::ROLE_ADMIN,
          Group::ROLE_USER
        );
    	foreach($all_groups as $g){
    		$new_g = new Group();
    		$new_g->setId(null);
    		$new_g->setName($g);
    		$new_g->setRole($g);
    		$manager->persist($new_g);
    		$manager->flush();
    		unset($new_g);
    	}
        
    }

    public function getOrder()
    {
        return 2;
    }
}