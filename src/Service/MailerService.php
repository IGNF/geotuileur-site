<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment as TwigEnvironment;

class MailerService
{
    private $parameters;
    private $twig;
    private $mailer;

    public function __construct(ParameterBagInterface $parameters, TwigEnvironment $twig, \Swift_Mailer $mailer)
    {
        $this->parameters = $parameters;
        $this->twig = $twig;
        $this->mailer = $mailer;
    }

    public function sendMail($to, $subject, $templateName, $params = [])
    {
        $body = $this->twig->render($templateName, $params);

        $message = (new \Swift_Message($subject))
            ->setFrom($this->parameters->get('mailer_sender_address'))
            ->setTo($to)
            ->setBody($body, 'text/html')
        ;

        $this->mailer->send($message);
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
