<?php

namespace App\Controller;

use App\Exception\PlageApiException;
use App\Security\User;
use App\Service\MailerService;
use App\Service\PlageApiService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("", name="plage_")
 */
class ContactController extends AbstractController
{
    /** @var PlageApiService */
    private $plageApi;

    public function __construct(PlageApiService $plageApi)
    {
        $this->plageApi = $plageApi;
    }

    /**
     * Formulaire de contact.
     *
     * @Route("/nous-ecrire", name="contact", methods={"GET","POST"})
     */
    public function contact(Request $request, MailerService $mailerService, LoggerInterface $mailerLogger)
    {
        /** @var User */
        $user = $this->getUser();

        $informations = $this->getInformations($request->query->all());
        if ($informations) {
            $text = $this->getTextFromInformations($informations);
        }

        $defaultData = [
            'userEmail' => null != $user ? $user->getEmail() : '',
        ];

        /** @var Form */
        $form = $this->createFormBuilder($defaultData)
            ->add('userEmail', EmailType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'contact.form.userEmail',
                'attr' => ['readonly' => null != $user ? true : false],
                'required' => true,
                'constraints' => [
                    new Assert\Email(),
                    new Assert\NotBlank(),
                ],
            ])
            ->add('message', TextareaType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'contact.form.message',
                'data' => isset($text) ? $text : null,
                'attr' => ['rows' => 12],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 10, 'minMessage' => 'Le corps du message doit faire au moins 10 caractères.']),
                ],
            ])
            // anti-spam hidden field
            // if the field is filled-in with something other than the default value, it's a bot
            ->add('importance', ChoiceType::class, [
                'choices' => [null, 1, 2, 3],
                'required' => false,
                'mapped' => false,
            ])
            ->add('submit', SubmitType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'contact.form.submit',
                'attr' => [
                    'class' => 'btn btn--plain btn--primary btn-width--lg',
                ],
            ])
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $importance = $form->get('importance')->getData();
            if ($importance) {
                return $this->redirectToRoute('plage_contact');
            }

            $message = $form->get('message')->getData();
            if ($mailerService->containsBannedWords($message)) {
                return $this->redirectToRoute('plage_contact');
            }

            $supportAddress = $this->getParameter('support_contact_mail');
            $userEmail = $form->get('userEmail')->getData();
            $now = new \DateTime();

            $userApi = null;
            $supportMailParams = [
                'userEmail' => $userEmail,
                'sendDate' => $now,
                'message' => $message,
            ];

            if ($user instanceof User) {
                $userApi = $this->plageApi->user->getMe();
                $supportMailParams['userId'] = $userApi['_id'];
            }

            $mailerLogger->info('User ({userEmail}) : {message}', [
                'userEmail' => $userEmail,
                'message' => $message,
            ]);

            // sending mail to support address
            $mailerService->sendMail($supportAddress, '[Geotuileur] Demande de contact', 'bundles/Mailer/contact.html.twig', $supportMailParams);

            // sending acknowledgement mail to user
            $mailerService->sendMail($userEmail, '[Geotuileur] Accusé de réception de votre demande', 'bundles/Mailer/contact_acknowledgement.html.twig', [
                'message' => $message,
                'sendDate' => $now,
            ]);

            return $this->redirectToRoute('plage_contact_thanks', ['error' => false]);
        }

        return $this->render('pages/contact.html.twig', [
            'form' => $form->createView(),
            'subject' => $informations ? $informations['subject'] : null,
        ]);
    }

    /**
     * Page de redirection après contact.
     *
     * @Route("/nous-ecrire/merci", name="contact_thanks", methods={"GET"})
     */
    public function contactThanks(Request $request)
    {
        $error = $request->query->get('error');
        $failures = $request->query->get('failures');

        return $this->render('pages/contact_thanks.html.twig', [
            'error' => $error,
            'failures' => $failures,
        ]);
    }

    /**
     * Récupère les informations a partir des paramètres de la requête.
     *
     * @param array $datas
     *
     * @return array|null
     */
    private function getInformations($datas)
    {
        if (!count($datas)) {
            return null;
        }
        if (!isset($datas['subject'])) {
            return null;
        }
        if ('add_datastore' != $datas['subject'] && 'processing_failed' != $datas['subject']) {
            return null;
        }

        try {
            $user = $this->getUser();
            $me = $this->plageApi->user->getMe();

            if ('add_datastore' == $datas['subject']) {
                return [
                    'subject' => $datas['subject'],
                    'user_id' => $me['_id'],
                    'username' => $user->getUsername(),
                ];
            }

            // Recuperation de l'execution de traitement qui a echoue
            foreach (['datastoreId', 'storedDataId'] as $param) {
                if (!isset($datas[$param])) {
                    return null;
                }
            }

            $datastore = $this->plageApi->datastore->get($datas['datastoreId']);

            $executions = $this->plageApi->processing->getAllExecutions($datas['datastoreId'], ['output_stored_data' => $datas['storedDataId']]);
            $execution = $this->plageApi->processing->getExecution($datas['datastoreId'], $executions[0]['_id']);
            if ('FAILURE' == $execution['status']) {
                return [
                    'subject' => $datas['subject'],
                    'user_id' => $me['_id'],
                    'username' => $user->getUsername(),
                    'datastore' => $datastore,
                    'processing' => $execution['processing'],
                ];
            }

            return null;
        } catch (PlageApiException $e) {
            return null;
        }
    }

    /**
     * Retourne un corps de message prérempli pour certains sujets de demande.
     *
     * @param array $informations
     *
     * @return string
     */
    private function getTextFromInformations($informations)
    {
        $text = "Bonjour, \n";
        if ('processing_failed' == $informations['subject']) {
            $text .= "J'ai rencontré une erreur dans la création ou la mise à jour d'un flux de tuiles vectorielles\n\n";
            $text .= '- Etape en échec : '.$informations['processing']['name']."\n";
            $text .= '- Espace de travail : '.$informations['datastore']['_id'].' ('.$informations['datastore']['name'].')'."\n";
            $text .= "- Identifiant de l'exécution de traitement : ".$informations['processing']['_id']."\n";
            $text .= '- Mon identifiant utilisateur : '.$informations['user_id'].' ('.$informations['username'].')';
        } else {
            $text .= "Je souhaiterais un nouvel espace de travail sur le Géotuileur.\n\n";
            $text .= "- Nom souhaité pour cet espace de travail : ...\n";
            $text .= "- Volume d'espace de stockage souhaité : ... Go\n";
            $text .= '- Mon identifiant utilisateur : '.$informations['user_id'].' ('.$informations['username'].")\n\n";
            $text .= 'Ajoutez toute autre information qui vous semble pertinente, notamment des informations sur la nature des données que vous souhaitez diffuser.';
        }

        return $text;
    }
}
