<?php

namespace App\Command;

use DOMDocument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/*
 * Commande pour mettre à jour le fichier public/data/followers.json
 * à partir du site institutionnel
 * A exécuter régulièrement
 */
class UpdateEditorialContentCommand extends Command
{
    protected static $defaultName = 'geotuileur:update-editorial-content';

    private $kernel;

    private $output;

    private $proxy;

    public function __construct(KernelInterface $kernel, $proxy)
    {
        $this->kernel = $kernel;
        $this->proxy = $proxy;
        parent::__construct();
    }

    public function configure()
    {
        $this
            ->setDescription('Met à jour le fichiers public/data/followers.json et templates/Components/megamenu.html')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $date = new \DateTime();
        $io->note("Date d'exécution : ".$date->format('Y-m-d H:i:s'));

        $this->output = $output;

        $followersUrl = 'https://www.ign.fr/publications-de-l-ign/followers.json';

        $this->output->writeln('Récupération du fichier des followers sur le site institutionnel');

        $followersContent = $this->curl($followersUrl);

        // Sauvegarde du fichier des followers
        $fileSystem = new Filesystem();
        $filepath = $this->kernel->getProjectDir().'/public/data';
        $this->output->writeln($filepath);
        if (!$fileSystem->exists($filepath)) {
            $fileSystem->mkdir($filepath);
        }

        $filepath .= '/followers.json';
        $handle = fopen($filepath, 'w');
        if (!$handle) {
            throw new \Exception('Impossible de creer le fichier followers.json');
        }
        fputs($handle, $followersContent);
        fclose($handle);

        // Récupération du Mega Menu
        $this->output->writeln('Récupération du megamenu sur le site institutionnel');

        $megamenuUrl = 'https://www.ign.fr/institut';
        $ignHtml = $this->curl($megamenuUrl);

        $dom = new DOMDocument();
        @$dom->loadHTML($ignHtml);

        $megamenuElement = $dom->getElementById('megamenuAccordion');

        if ($megamenuElement) {
            // Remplace tous les liens relatifs par des liens absolus vers ign.fr
            $this->makeUrlsAbsolute($megamenuElement);
            // Déplie le volet portail pro (univers de l'espace co)
            $this->expandPortailPro($megamenuElement);

            $megaMenuHtml = $this->innerHTML($megamenuElement);

            $megaMenuPath = $this->kernel->getProjectDir()
                .DIRECTORY_SEPARATOR.'templates/components/megamenu.html';

            if (false === file_put_contents($megaMenuPath, $megaMenuHtml)) {
                $this->output->writeln('L\'écriture du fichier '.$megaMenuPath.' a échoué');
            } else {
                $this->output->writeln('Megamenu html copié dans '.$megaMenuPath);
            }
        } else {
            $this->output->writeln('Pas d\'élément #megamenuAccordion trouvé dans le contenu ign.fr');
        }

        $this->output->writeln('Les contenus éditoriaux ont été copiés avec succès');

        return 0;
    }

    /**
     * curl.
     *
     * Renvoie le contenu de l'url cible
     *
     * @param string $url : url à télécharger
     */
    private function curl($url)
    {
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        if (null !== $this->proxy) {
            $options[CURLOPT_PROXY] = $this->proxy;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        $content = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (0 == $info['http_code']) {
            $this->output->writeln("can't connect to ".$url);

            return '';
        } elseif (200 != $info['http_code']) {
            $this->output->writeln('['.$info['http_code'].'] '.$url);

            return '';
        }

        return $content;
    }

    /**
     * innerHTML.
     *
     * Renvoie le contenu html d'un élément DOM
     */
    private function innerHTML(\DOMElement $element)
    {
        $doc = $element->ownerDocument;

        $html = '';

        foreach ($element->childNodes as $node) {
            $html .= $doc->saveHTML($node);
        }

        return $html;
    }

    /**
     * makeUrlsAbsolute.
     *
     * Remplace les URLs relatives des liens et des images par des URLs absolues
     * du site www.ign.fr
     */
    private function makeUrlsAbsolute(\DOMElement $megamenuElement)
    {
        // Remplace les href des liens
        foreach ($megamenuElement->getElementsByTagName('a') as $item) {
            $href = $item->getAttribute('href');
            if ('http' != substr($href, 0, 4)) {
                $item->setAttribute('href', 'https://www.ign.fr'.$href);
                $item->setAttribute('target', '_blank');
                $item->setAttribute('class', $item->getAttribute('class').' external-link');
            }
        }

        // Remplace les src des images
        foreach ($megamenuElement->getElementsByTagName('img') as $item) {
            $src = $item->getAttribute('src');
            if ('http' != substr($src, 0, 4)) {
                $item->setAttribute('src', 'https://www.ign.fr'.$src);
            }
        }
    }

    /**
     * expandPortailPro.
     *
     * Ouvre par défaut le portail pro (univers de l'espace co)
     */
    private function expandPortailPro(\DOMElement $megamenuElement)
    {
        foreach ($megamenuElement->getElementsByTagName('div') as $item) {
            $id = $item->getAttribute('id');
            if ('megamenu__collapse-1' === $id) {
                $item->setAttribute('class', 'megamenu__collapse collapse show');
            }
        }
        foreach ($megamenuElement->getElementsByTagName('button') as $item) {
            $dataTarget = $item->getAttribute('data-target');
            if ('#megamenu__collapse-1' === $dataTarget) {
                $item->setAttribute('aria-expanded', 'true');
            }
        }
    }
}
