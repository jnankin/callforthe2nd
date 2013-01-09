<?php

namespace Hackhouse\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Constraints;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

class ResetPasswordType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver){
        $collectionConstraint = new Constraints\Collection(array(
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
        $builder->add('email', 'email');
    }

    public function getName()
    {
        return 'resetpassword';
    }
}
