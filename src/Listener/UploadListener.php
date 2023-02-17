<?php

namespace App\Listener;

use Oneup\UploaderBundle\Event\PostUploadEvent;
use Oneup\UploaderBundle\Uploader\Response\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @author pprevautel
 */
class UploadListener
{
    private const VALID_FILE_EXTENSIONS = ['csv', 'gpkg'];

    public function onUpload(PostUploadEvent $event)
    {
        /** @var UploadedFile */
        $uploadedFile = $event->getRequest()->files->get('upload')['file'];
        $originalName = $uploadedFile->getClientOriginalName();

        /** @var ResponseInterface */
        $response = $event->getResponse();
        $file = $event->getFile();

        try {
            $filename = $file->getFilename();
            $extension = strtolower($file->getExtension());

            $validFileExtensionsAndZip = array_merge(self::VALID_FILE_EXTENSIONS, ['zip']);
            if (!in_array($extension, $validFileExtensionsAndZip)) {
                throw new \Exception("L'extension du fichier $filename n'est pas correcte");
            }

            if ('zip' == $extension) {
                $this->cleanArchive($file);
                $this->validateArchive($file);
            }

            if (!$file->getSize()) {
                throw new \Exception("Le fichier $filename ne doit pas être vide");
            }

            $srids = $this->getSrids($file);    // seulement les gpkg et archives gpkg
            $unicity = array_unique($srids);
            if (!empty($unicity) && 1 != count($unicity)) {
                throw new \Exception('Ce fichier contient des données dans des systèmes de projection différents');
            }

            // Verification des srid (le srid doit être unique pour toutes couches gpkg et zip avec gpkg)
            $response['status'] = 'OK';
            $response['srid'] = (1 == count($unicity)) ? $unicity[0] : '';

            // Si c'est un fichier csv, on le zippe pour conserver le nom
            if ('csv' == $extension) {
                $response['filename'] = $this->zip($file, $originalName);
            } else {
                $response['filename'] = $file->getFilename();
            }
        } catch (\Exception $e) {
            $response['status'] = 'ERROR';
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    /**
     * Supprime tous les fichiers qui ne sont pas un gpkg ou CSV du zip.
     *
     * @return void
     *
     * @throws \Exception
     */
    private function cleanArchive(\SplFileInfo $file)
    {
        $filename = $file->getFilename();

        $zip = new \ZipArchive();
        if (!$zip->open($file->getPathname())) {
            throw new \Exception("L'ouverture de l'archive $filename a echoué");
        }

        $numDeletedFiles = 0;
        $numFiles = $zip->numFiles;

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $filename = $zip->getNameIndex($i);
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($extension, self::VALID_FILE_EXTENSIONS)) {
                $zip->deleteName($filename);
                ++$numDeletedFiles;
            }
        }
        $zip->close();

        if ($numDeletedFiles == $numFiles) {
            throw new \Exception("L'archive ne contient aucun fichier acceptable");
        }
    }

    /**
     * Effectue des contrôles sur l'archive zip.
     *
     * Critères de validation :
     * - doit contenir au moins un fichier gpkg ou CSV
     * - ne peut contenir qu'un seul type de fichiers
     * - ne peut contenir plus de 10000 fichiers
     * - taille max du zip : 1 Go
     * - ratio de compression max : 20%
     *
     * @return void
     *
     * @throws \Exception
     */
    private function validateArchive(\SplFileInfo $file)
    {
        $maxFiles = 10000;
        $maxSize = 1000000000; // 1 GB
        $oneGiga = 1000000000;
        $maxRatio = 20; // initialement on avait testé 10% mais c'était trop restrictif (https://github.com/IGNF/geotuileur-site/issues/47)

        $filename = $file->getFilename();

        $zip = new \ZipArchive();
        if (!$zip->open($file->getPathname())) {
            throw new \Exception("L'ouverture de l'archive $filename a echoué");
        }

        $numFiles = 0;
        $extensions = [];

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $filename = $zip->getNameIndex($i);
            $stats = $zip->statIndex($i);

            // Prevent ZipSlip path traversal (S6096)
            if (false !== strpos($filename, '../') || '/' === substr($filename, 0, 1)) {
                throw new \Exception();
            }

            // C'est un dossier
            if ('/' === substr($filename, -1)) {
                continue;
            }

            ++$numFiles;
            if ($numFiles > $maxFiles) {
                throw new \Exception("Le nombre de fichiers excède $maxFiles");
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $extensions[] = $extension;
            // il n'y a plus besoin de vérifier l'extension parce qu'on a déjà supprimé tous les fichiers qui ne sont pas csv ou gpkg (voir cleanArchive)

            $size = $stats['size'];
            if ($size > $maxSize) {
                throw new \Exception(sprintf("La taille du fichier $filename excède %s GB", $maxSize / $oneGiga));
            }

            if ($stats['comp_size']) {
                $ratio = $stats['size'] / $stats['comp_size'];
                if ($ratio > $maxRatio) {
                    throw new \Exception("Le taux de compression excède $maxRatio");
                }
            }
        }

        $zip->close();
        $unicity = array_unique($extensions);
        if (1 != count($unicity)) {
            throw new \Exception(sprintf("L'archive ne doit contenir qu'un seul type de fichier (%s)", implode(' ou ', self::VALID_FILE_EXTENSIONS)));
        }
    }

    /**
     * Cree un fichier archive qui va contenir ce fichier $file (csv uniquement)
     * Le fichier zip est de la forme <basename>.zip.
     *
     * @param File   $file
     * @param string $originalName
     *
     * @return string
     */
    private function zip($file, $originalName)
    {
        $fs = new Filesystem();

        $folder = realpath($file->getPath());

        // On renomme le fichier
        $filepath = realpath($file->getPathName());
        $outfile = join(DIRECTORY_SEPARATOR, [$folder, "$originalName"]);
        $fs->rename($filepath, $outfile, true);

        // Creation de l'archive
        $extension = $file->getExtension();
        $uuid = $file->getBasename(".$extension");
        $zipFile = join(DIRECTORY_SEPARATOR, [$folder, "$uuid.zip"]);

        $zip = new \ZipArchive();
        $res = $zip->open($zipFile, \ZipArchive::CREATE);
        if (true === $res) {
            $zip->addFile($outfile, "$originalName");
            $zip->close();
        }

        $fs->remove($outfile);
        if (true === $res) {
            return "$uuid.zip";
        }

        throw new \Exception("La création de l'archive a échoué.");
    }

    private function getSrids($file)
    {
        $extension = strtolower($file->getExtension());
        if ('csv' == $extension) {
            return [];
        }    // difficile de connaitre le srid d'un fichier CSV

        $srids = [];
        if ('gpkg' == $extension) {
            $this->getSridsFromFile($file, $srids);
        } else {
            $this->getSridsFromArchive($file, $srids);
        }

        return $srids;
    }

    private function getSridsFromArchive($file, &$srids)
    {
        $fs = new Filesystem();

        $infos = pathinfo($file->getPathname());
        $dirname = $infos['dirname'];
        $filename = $infos['filename'].'_tmp';

        // Extracting zip file
        $zip = new \ZipArchive();
        if (!$zip->open($file->getPathname())) {
            throw new \Exception("l'ouverture du fichier ZIP échouée");
        }

        $folder = "$dirname/$filename";
        if (!$zip->extractTo($folder)) {
            return -1;
        }
        $zip->close();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $entry) {
            $extension = strtolower($entry->getExtension());
            if ('gpkg' != $extension) {
                continue;
            }

            $this->getSridsFromFile($entry, $srids);
        }

        $fs->remove($folder);
    }

    private function getSridsFromFile($file, &$srids)
    {
        $filepath = $file->getPathname();

        $db = new \SQLite3($filepath);
        $res = $db->query('SELECT table_name, srs_id FROM gpkg_geometry_columns');
        while ($row = $res->fetchArray()) {
            $srids[] = 'EPSG:'.$row['srs_id'];
        }
    }
}
