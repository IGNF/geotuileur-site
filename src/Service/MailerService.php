<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment as TwigEnvironment;

class MailerService
{
    private $parameters;
    private $twig;
    private $mailer;
    private $logger;

    public function __construct(ParameterBagInterface $parameters, TwigEnvironment $twig, MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->parameters = $parameters;
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $templateName
     * @param array  $params
     *
     * @return void
     */
    public function sendMail($to, $subject, $templateName, $params = [])
    {
        $body = $this->twig->render($templateName, $params);

        $email = (new TemplatedEmail())
            ->from(new Address($this->parameters->get('mailer_sender_address'), 'Géotuileur'))
            ->to(new Address($to))
            ->subject($subject)
            ->html($body)
        ;

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $ex) {
            $this->logger->error('Sending mail failed, email address: {email_address}, email subject: {subject}', [
                'subject' => $subject,
                'email_address' => $to,
                'debug' => $ex->getDebug(),
            ]);
            throw $ex;
        }
    }

    public function containsBannedWords($text)
    {
        $bannedWords = Yaml::parseFile(__DIR__.'/../../config/app/banned_words.yml');

        foreach ($bannedWords as $bannedWord) {
            if (str_contains(strtolower($text), $bannedWord)) {
                return true;
            }
        }

        return false;
    }
}
