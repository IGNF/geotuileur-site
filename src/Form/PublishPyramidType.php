<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints\Url;

class PublishPyramidType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'pyramid.form_publish.name',
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('title', TextType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'pyramid.form_publish.title',
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('address_preview', TextType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'pyramid.form_publish.address_preview',
                'required' => false,
                'attr' => [
                    'class' => 'text-primary',
                    'readonly' => true
                ]  
            ])
            ->add('description', TextAreaType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'pyramid.form_publish.description',
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('legal_notices', TextType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'pyramid.form_publish.legal_notices',
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('attribution_url', UrlType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'pyramid.form_publish.attribution_url',
                'default_protocol' => null,
                'constraints' => [
                    new NotBlank(), new Url()
                ]
            ])
            ->add('keywords', HiddenType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'pyramid.form_publish.keywords',
                'required' => false
            ])
            ->add('submit', SubmitType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'valid',
                'attr' => [
                    'class' => 'btn btn--plain btn--primary btn-width--lg'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'datastoreId' => '',
            'pyramidId' => '',
        ]);
    }
}
