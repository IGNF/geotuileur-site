<?php

namespace App\Form;

use App\Constants\StoredDataStatuses;
use App\Constants\StoredDataTypes;
use App\Service\PlageApiService;
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

class UpdatePyramidType extends AbstractType
{
    /** @var PlageApiService */
    private $plageApi;

    public function __construct(PlageApiService $plageApi)
    {
        $this->plageApi = $plageApi;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $pyramidChoices = [];
        $pyramidIdOptions = [];

        if (!$options['pyramid']) { // pyramid n'a pas été fourni en query params
            $pyramidChoices = $this->getPyramidChoices($options['datastoreId']);
        } else {
            $pyramidChoices[$options['pyramid']['name']] = $options['pyramid']['_id'];

            $pyramidIdOptions = array_merge($pyramidIdOptions, [
                'data' => $options['pyramid']['_id'],
                'empty_data' => $options['pyramid']['_id'],
                'attr' => [
                    'disabled' => true,
                ],
            ]);
        }

        $pyramidIdOptions = array_merge($pyramidIdOptions, [
            'label' => 'Tuiles vectorielles à remplacer',
            'placeholder' => 'Choisissez la pyramide de tuiles vectorielles à remplacer',
            'label_attr' => [
                'class' => 'control-label',
            ],
            'choices' => $pyramidChoices,
        ]);

        $projections = Yaml::parseFile(__DIR__.'/../../config/app/projections.yml');
        $projections = array_flip($projections);

        $builder
            ->add('pyramid_id', ChoiceType::class, $pyramidIdOptions)
            ->add('name', TextType::class)
            ->add('srs', ChoiceType::class, [
                'label' => 'upload.form.srs',
                'translation_domain' => 'PlageWebClient',
                'choices' => $projections,
                'constraints' => [new NotBlank(['message' => 'La projection doit être renseignée.'])],
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'error_bubbling' => true,
            ])
            ->add('file', FileType::class, [
                'label' => 'upload.form.file',
                'translation_domain' => 'PlageWebClient',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'application/zip,.csv,.gpkg',
                    'placeholder' => 'pyramid.upload_data.browse_files',
                ],
            ])
            ->add('file_data', HiddenType::class)
            ->add('datastoreId', HiddenType::class, [
                'data' => $options['datastoreId'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'upload.form.submit',
                'translation_domain' => 'PlageWebClient',
                'attr' => [
                    'class' => 'btn btn--plain btn--primary btn-width--lg',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'datastoreId' => null,
            'pyramid' => null,
        ]);
    }

    private function getPyramidChoices($datastoreId)
    {
        $pyramids = $this->plageApi->storedData->getAll($datastoreId, [
            'type' => StoredDataTypes::ROK4_PYRAMID_VECTOR,
            'status' => StoredDataStatuses::GENERATED,
        ]);

        $choices = [];
        foreach ($pyramids as $pyramid) {
            $offerings = $this->plageApi->configuration->getAllOfferings($datastoreId, [
                'stored_data' => $pyramid['_id'],
            ]);

            if (count($offerings) > 0) {
                $choices[$pyramid['name']] = $pyramid['_id'];
            }
        }

        return $choices;
    }
}
