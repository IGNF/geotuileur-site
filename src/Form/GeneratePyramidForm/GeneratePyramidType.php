<?php

namespace App\Form\GeneratePyramidForm;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GeneratePyramidType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $procCreatPyramidSample = $options['proc_creat_pyramid_sample'];

        $builder
            ->add('pyramid_name', TextType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'pyramid.form_add.name',
                'required' => true,
                'data' => $procCreatPyramidSample ? $procCreatPyramidSample['output']['stored_data']['name'] : $options['stream_name'],
            ])
            ->add('levels', HiddenType::class)
            ->add('sample', CheckboxType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'pyramid.form_add.generate_sample',
                'required' => false,
                'data' => false,
                'attr' => [
                    'checked' => false,
                ],
                'label_attr' => ['class' => 'checkbox-custom'],
            ])
            ->add('bbox', HiddenType::class, [
                'required' => false,
            ])
            ->add('composition', PyramidCompositionType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'pyramid.form_add.composition',
                'type_infos' => $options['type_infos'],
                'composition_sample' => $procCreatPyramidSample ? $procCreatPyramidSample['parameters']['composition'] : null,
            ])
            ->add('tippecanoe', HiddenType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'pyramid.form_add.tippecanoe.options',
                'required' => false,
            ])
            ->add('datastoreId', HiddenType::class, [
                'data' => $options['datastoreId'],
            ])
            ->add('submit', SubmitType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'pyramid.form_add.submit',
                'attr' => [
                    'class' => 'float-right btn btn--plain btn--primary',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'datastoreId' => null,
            'stream_name' => null,
            'proc_creat_pyramid_sample' => null,
            'type_infos' => [],
            'allow_extra_fields' => true,
        ]);
    }
}
