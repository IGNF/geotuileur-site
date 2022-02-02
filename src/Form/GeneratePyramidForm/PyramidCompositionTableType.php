<?php

namespace App\Form\GeneratePyramidForm;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PyramidCompositionTableType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('attributes', PyramidTableAttributesSelectionType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'pyramid.form_add.attributes',
                'help' => 'pyramid.form_add.attributes_help',
                'attributes' => $options['table_infos']['attributes'],
                'attributes_sample' => $options['table_infos_sample'] ? $options['table_infos_sample']['attributes'] : null,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'table_infos' => [],
            'table_infos_sample' => [],
        ]);
    }
}
