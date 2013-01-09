<?php

namespace Hackhouse\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Hackhouse\UserBundle\Entity\User;

class RemoveRoleCommand extends AbstractUserCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('user:removeRole')
            ->setDescription('Removes a role from a user.')
            ->setDefinition(array(
            new InputArgument('username', InputArgument::REQUIRED, 'The username'),
            new InputArgument('role', InputArgument::REQUIRED, 'The role')
        ))
            ->setHelp(<<<EOT
The <info>user:removeRole</info> command removes a role from a user:

<info>php app/console user:removeRole jnankin@gmail.com ROLE_ADMIN</info>
EOT
        );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $role = $input->getArgument('role');

        $userManager = $this->getContainer()->get('user_manager');
        $user = $this->lookupUserWithUsernameString($username);

        $userManager->removeRole($user, $role);
        $output->writeln("Successfully removed $role from $username");
    }

    /**
     * @see Command
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('username')) {
            $username = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a username:',
                function($username) {
                    if (empty($username)) {
                        throw new \Exception('Username can not be empty');
                    }

                    return $username;
                }
            );
            $input->setArgument('username', $username);
        }

        if (!$input->getArgument('role')) {
            $role = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please enter a role name:',
                function($role) {
                    if (empty($role)) {
                        throw new \Exception('Role can not be empty');
                    }

                    return $role;
                }
            );
            $input->setArgument('role', $role);
        }
    }
}