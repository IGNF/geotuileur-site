<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

class UploadType extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $projections = Yaml::parseFile(__DIR__.'/../../config/app/projections.yml');
        $projections = array_flip($projections);

        $builder->add('file', FileType::class, [
            'label' => 'upload.form.file',
            'translation_domain' => 'PlageWebClient',
            'mapped' => false,
            'required' => false,
            'attr' => [
                'accept' => 'application/zip,.csv,.gpkg',
                'placeholder' => 'pyramid.upload_data.browse_files',
            ],
        ])->add('pyramid_name', TextType::class, [
            'label' => 'upload.form.pyramid_name',
            'translation_domain' => 'PlageWebClient',
            'constraints' => [
                new NotBlank(),
            ],
            'error_bubbling' => true,
        ])->add('srs', ChoiceType::class, [
            'label' => 'upload.form.srs',
            'translation_domain' => 'PlageWebClient',
            'choices' => $projections,
            'data' => 'EPSG:4326',
            'required' => true,
            'constraints' => [
                new NotBlank(['message' => $this->translator->trans('upload.form.error_msg_srs_blank', [], 'PlageWebClient')]),
            ],
            'label_attr' => [
                'class' => 'control-label',
            ],
            'error_bubbling' => true,
        ])->add('file_data', HiddenType::class)
        ->add('datastoreId', HiddenType::class, [
            'data' => $options['datastoreId'],
        ])->add('submit', SubmitType::class, [
            'label' => 'upload.form.submit',
            'translation_domain' => 'PlageWebClient',
            'attr' => [
                'class' => 'btn btn--plain btn--primary btn-width--lg',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'datastoreId' => null,
            'storedDataChoices' => null,
        ]);
    }
}
