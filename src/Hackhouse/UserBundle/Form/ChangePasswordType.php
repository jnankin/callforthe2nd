<?php

namespace Hackhouse\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Constraints;

class ChangePasswordType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver){
        $collectionConstraint = new Constraints\Collection(array(
            'password' => array(
                new Constraints\MinLength(array('limit' => \Hackhouse\UserBundle\Entity\User::PASSWORD_MIN_LENGTH, 'message' => \Hackhouse\UserBundle\Entity\User::PASSWORD_MIN_LENGTH_MESSAGE)),
                new Constraints\Regex(array('pattern' => \Hackhouse\UserBundle\Entity\User::PASSWORD_REGEX, 'message' => \Hackhouse\UserBundle\Entity\User::PASSWORD_REGEX_MESSAGE)),
                new Constraints\NotBlank(array('message' => 'Please enter a password.'))
            )
        ));

        return $resolver->setDefaults(array(
            'csrf_protection' => false,
            'validation_constraint' => $collectionConstraint
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('password', 'repeated', array(
                'type' => 'password',
                'invalid_message' => 'The password fields must match.',
                'options' => array('required' => true)
            ))
        ;
    }

    public function getName()
    {
        return 'change_password';
    }
}
