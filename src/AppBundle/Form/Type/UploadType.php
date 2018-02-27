<?php

namespace AppBundle\Form\Type;

use AppBundle\Entity\Uploader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class UploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ...
            ->add('excel', FileType::class, array(
                'label' => false, 
                'attr' => array('class' => 'btn btn-lg btn-block btn-primary'))
            )
            ->add('pdf', FileType::class, array(
                'label' => false, 
                'attr' => array('class' => 'btn btn-lg btn-block btn-primary'),
                'multiple' => true
            ))
            ->add('message', TextareaType::class, array(
                'label' => false,
                'attr' => ['class' => 'message', 'placeholder' => 'Mensaje']
            ))
            // ...
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Uploader::class,
        ));
    }
}