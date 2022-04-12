<?php

namespace App\Controller;

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
     * @Route("/nous-ecrire", name="contact", methods={"GET","POST"})
     */
    public function contact(Request $request, MailerService $mailerService, LoggerInterface $mailerLogger)
    {
        $user = $this->getUser();
        $defaultData = [
            'userEmail' => $user ? $user->getEmail() : '',
        ];

        /** @var Form */
        $form = $this->createFormBuilder($defaultData)
            ->add('userEmail', EmailType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'contact.form.userEmail',
                'attr' => ['readonly' => $user ? true : false],
                'required' => true,
                'constraints' => [
                    new Assert\Email(),
                    new Assert\NotBlank(),
                ],
            ])
            ->add('message', TextareaType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'contact.form.message',
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

            // sending mail to support address
            $mailerService->sendMail($supportAddress, '[Geotuileur] Demande de contact', 'bundles/Mailer/contact.html.twig', $supportMailParams);

            // sending acknowledgement mail to user
            $mailerService->sendMail($userEmail, '[Geotuileur] Accusé de réception de votre demande', 'bundles/Mailer/contact_acknowledgement.html.twig', [
                'message' => $message,
                'sendDate' => $now,
            ]);

            $mailerLogger->info('User ({userEmail}) : {message}', [
                'userEmail' => $userEmail,
                'message' => $message,
            ]);

            return $this->redirectToRoute('plage_contact_thanks', ['error' => false]);
        }

        return $this->render('pages/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
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
}
