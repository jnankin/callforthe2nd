<?php

namespace Hackhouse\UserBundle\Tests;

use Hackhouse\UserBundle\Entity\UserManager;
use Hackhouse\UserBundle\Entity\User;
use Hackhouse\Utils\Utils;
use Symfony\Component\Validator\Constraints\DateTime;

class UserManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetZonedTimestamp()
    {
        $userManager = new UserManager();

        $user = new User();
        $user->setTimezone('America/Chicago');
        $this->assertEquals('2012-12-31 22:41:01', $userManager->getZonedTimestamp($user, 1357015261, Utils::DOCTRINE_TIMESTAMP_FMT));

        $user->setTimezone('America/New_York');
        $this->assertEquals('2012-12-31 23:41:01', $userManager->getZonedTimestamp($user, 1357015261, Utils::DOCTRINE_TIMESTAMP_FMT));


        $user->setTimezone('America/Chicago');
        $date = new \DateTime("1970-01-01 00:00:00", new \DateTimeZone("UTC"));
        $this->assertEquals('1970-01-01 06:00:00', $userManager->convertFakeUTCToRealUTC($user, $date, Utils::DOCTRINE_TIMESTAMP_FMT));


    }
}
