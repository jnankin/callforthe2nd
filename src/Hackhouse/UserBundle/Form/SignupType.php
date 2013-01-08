<?php

namespace Hackhouse\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Constraints;

class SignupType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver){
        $collectionConstraint = new Constraints\Collection(array(
            'firstName' => array(
                new Constraints\MinLength(array('limit' => 2, 'message' => 'First name must be longer than 2 characters')),
                new Constraints\NotBlank(array('message' => 'Please enter your first name'))
            ),
            'lastName' => array(
                new Constraints\MinLength(array('limit' => 2, 'message' => 'Last name must be longer than 2 characters')),
                new Constraints\NotBlank(array('message' => 'Please enter your last name'))
            ),
            'password' => array(
                new Constraints\MinLength(array('limit' => \Hackhouse\UserBundle\Entity\User::PASSWORD_MIN_LENGTH, 'message' => \Hackhouse\UserBundle\Entity\User::PASSWORD_MIN_LENGTH_MESSAGE)),
                new Constraints\Regex(array('pattern' => \Hackhouse\UserBundle\Entity\User::PASSWORD_REGEX, 'message' => \Hackhouse\UserBundle\Entity\User::PASSWORD_REGEX_MESSAGE)),
                new Constraints\NotBlank(array('message' => 'Please enter a password.'))
            ),
            'email' => array(
                new Constraints\Email(array('message' => 'Please enter a valid email address')),
                new Constraints\NotBlank(array('message' => 'Please enter your email address'))
            )
        ));

        return $resolver->setDefaults(array(
            'validation_constraint' => $collectionConstraint
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('password', 'repeated', array(
                'type' => 'password',
                'invalid_message' => 'The password fields must match.',
                'options' => array('required' => true)
            ))
            ->add('email', 'email')
        ;
    }

    public function getName()
    {
        return 'Hackhouse_publicbundle_signuptype';
    }
}
