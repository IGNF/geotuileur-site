<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class PlageUploadsCleanupCommand extends Command
{
    protected static $defaultName = 'geotuileur:uploads:cleanup';
    protected static $defaultDescription = 'Supprime les fichiers de livraison (les fichiers téléversés restés sur le serveur) plus';

    private $directory;

    public function __construct(ParameterBagInterface $parameters)
    {
        $this->directory = $parameters->get('oneup_uploader_gallery_path');
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $fs = new Filesystem();
        $finder = new Finder();

        if (!$fs->exists($this->directory)) {
            $io->note("Il n'y a aucun fichier à supprimer, le répertoire [$this->directory] n'existe pas encore");

            return 0;
        }

        $finder->date('since 2 days ago')->in($this->directory); // files and directories

        $filesArray = iterator_to_array($finder, true);
        $deleteFileCount = 0;

        if (!$finder->hasResults()) {
            $io->note("Il n'y a aucun fichier à supprimer (datant de plus de 2 jours) dans le répertoire : $this->directory");

            return 0;
        }

        foreach ($filesArray as $file) {
            $absoluteFilePath = $file->getRealPath();
            // $fileNameWithExtension = $file->getRelativePathname();

            try {
                $fs->remove($absoluteFilePath);
                $io->note(sprintf('%s supprimé', $absoluteFilePath));
                ++$deleteFileCount;
            } catch (\Exception $ex) {
                $io->warning(sprintf("%s n'a pu être supprimé", $absoluteFilePath));
            }
        }

        $io->success(sprintf('%d fichiers ont été supprimés avec succès', $deleteFileCount));

        return 0;
    }
}
