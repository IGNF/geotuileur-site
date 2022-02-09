<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;

class PlageGetThemesCommand extends Command
{
    protected static $defaultName = 'geotuileur:get-themes';
    protected static $defaultDescription = 'Recuperation des themes et mots clefs INSPIRE';

    private $parameters;

    public function __construct(ParameterBagInterface $parameters)
    {
        parent::__construct();
        $this->parameters = $parameters;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $keywords = [
                'Sans thème' => [],
            ];

            $client = HttpClient::create([
                'proxy' => $this->parameters->get('http_proxy'),
            ]);

            $response = $client->request('GET', 'https://inspire.ec.europa.eu/featureconcept/featureconcept.fr.json');

            $statusCode = $response->getStatusCode();
            if (Response::HTTP_OK != $statusCode) {
                throw new \Exception("L'accès à l'url d'INSPIRE s'est mal passé.");
            }

            $content = $response->toArray();
            if (!isset($content['register'])) {
                throw new \Exception("La clef register n'existe pas.");
            }
            if (!isset($content['register']['containeditems'])) {
                throw new \Exception("La clef containeditems n'existe pas dans register.");
            }

            foreach ($content['register']['containeditems'] as $item) {
                if (!isset($item['featureconcept'])) {
                    throw new \Exception("La clef featureconcept n'existe pas dans register/containeditems.");
                }
                $featureConcept = $item['featureconcept'];

                $keyword = null;
                $label = $featureConcept['label'];
                if ('fr' == $label['lang']) {
                    $keyword = $label['text'];
                }

                if (!isset($featureConcept['themes'])) {
                    $themes = [];
                } else {
                    $themes = $featureConcept['themes'];
                }
                if (!count($themes)) {
                    if ($keyword) {
                        $keywords['Sans thème'][] = $keyword;
                    }
                    continue;
                }

                foreach ($themes as $theme) {
                    $label = $theme['theme']['label'];

                    $themeName = null;
                    if ('fr' == $label['lang']) {
                        $themeName = $label['text'];
                    }
                    if ($themeName && !isset($keywords[$themeName])) {
                        $keywords[$themeName] = [];
                    }
                    if ($keyword) {
                        $keywords[$themeName][] = $keyword;
                    }
                }
            }

            // sort
            ksort($keywords);
            foreach ($keywords as $theme => &$words) {
                asort($words);
                $words = array_values($words);
            }

            $filepath = dirname(__FILE__).'/../../data/thematic-inspire.json';
            file_put_contents($filepath, json_encode($keywords, JSON_UNESCAPED_UNICODE));

            return 1;
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }

        return 0;
    }
}
