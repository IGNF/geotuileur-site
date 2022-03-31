<?php

namespace App\Form\GeneratePyramidForm;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PyramidCompositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $typeInfos = $options['type_infos'];
        $tables = $typeInfos['relations'];

        $compositionSample = [];
        if ($options['composition_sample']) {
            foreach ($options['composition_sample'] as $tableSample) {
                $compositionSample[$tableSample['table']] = $tableSample;
            }
        }

        foreach ($tables as $table) {
            ksort($table['attributes']);
            $builder
                ->add($table['name'], PyramidCompositionTableType::class, [
                    'table_infos' => $table,
                    'label' => false,
                    'table_infos_sample' => $options['composition_sample'] ? $compositionSample[$table['name']] : null,
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'type_infos' => [],
            'composition_sample' => null,
        ]);
    }
}
