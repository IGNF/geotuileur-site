<?php

namespace App\Form\GeneratePyramidForm;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PyramidTableAttributesSelectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // l'attribut geometrie est un champ qui est conserve par defaut dans les couches
        $attributes = $options['attributes'];

        $attributesSample = [];
        if ($options['attributes_sample']) {
            $attributesSample = explode(',', str_replace(' ', '', $options['attributes_sample']));
        }

        foreach ($attributes as $attributeName => $type) {
            if (preg_match('/^geometry/', $type)) {
                continue;
            }

            $builder->add($attributeName, CheckboxType::class, [
                'required' => false,
                'label_attr' => ['class' => 'checkbox-custom'],
                'data' => in_array($attributeName, $attributesSample, true),
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attributes' => [],
            'attributes_sample' => [],
        ]);
    }
}
