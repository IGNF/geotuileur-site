<?php

namespace App\Listener;

use Oneup\UploaderBundle\Event\PostUploadEvent;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author pprevautel
 */
class UploadListener
{
    public function onUpload(PostUploadEvent $event)
    {
        $response = $event->getResponse();
        $file = $event->getFile();

        try {
            $filename = $file->getFilename();

            $extension = strtolower($file->getExtension());
            if (! in_array($extension, ['zip','csv','gpkg'])) {
                throw new \Exception("L'extension du fichier $filename n'est pas correcte");
            }

            if (in_array($extension, ['csv','gpkg'])) {
                if (! $file->getSize()) {
                    throw new \Exception("Le fichier $filename ne doit pas être vide");   
                }
            } else $this->checkArchive($file);

            $srids = $this->getSrids($file);    // seulement les gpkg et archives gpkg
            $unicity = array_unique($srids);
            if (! empty($unicity) && count($unicity) != 1) {
                throw new \Exception("Ce fichier contient des données dans des systèmes de projections différents");
            }

            // Verification des srid (le srid doit être unique pour toutes couches gpkg et zip avec gpkg)
            $response['status'] = "OK";
            $response['srid'] = (count($unicity) == 1) ? $unicity[0] : "";
            $response['filename'] = $file->getFilename();
        } catch (\Exception $e) {
            $response['status'] = "ERROR";
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    /**
     * @param \SplFileInfo $file
     * On autorise pour l'instant que des fichiers zip ne contenant qu'un seul type de fichiers (gpk ou CSV)
     * @throws \Exception
     */
    private function checkArchive($file)
    {
        $maxFiles   = 10000;
        $maxSize    = 1000000000; // 1 GB
        $maxRatio   = 10;
        $readLength = 1024;

        $filename = $file->getFilename();

        $zip = new \ZipArchive();
        if (! $zip->open($file->getPathname())) {
            throw new \Exception("L'ouverture de l'archive $filename a echoué");
        }

        $numFiles = 0;
        $extensions = [];

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $filename = $zip->getNameIndex($i);
            $stats = $zip->statIndex($i);

            // Prevent ZipSlip path traversal (S6096)
            if (strpos($filename, '../') !== false || substr($filename, 0, 1) === '/') {
                throw new \Exception();
            }
            
            // C'est un dossier
            if (substr($filename, -1) === '/') { continue; }

            $numFiles++;
            if ($numFiles > $maxFiles) {
                throw new \Exception("Le nombre de fichiers excède $maxFiles");
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $extensions[] = $extension;
            if (! in_array($extension, ['csv','gpkg'])) {
                throw new \Exception("L'extension du fichier $filename n'est pas correcte.");
            }

            $size = $stats["size"];
            if ($size > $maxSize) {
                throw new \Exception("La taille du fichier $filename excède $maxSize");
            }

            if ($stats['comp_size']) {
                $ratio = $stats["size"] / $stats['comp_size'];
                if ($ratio > $maxRatio) {
                    throw new \Exception("Le taux de compression excède $maxRatio");
                }
            }
        }

        $zip->close();
        $unicity = array_unique($extensions);
        if (count($unicity) != 1) {
            throw new \Exception("L'archive ne doit contenir qu'un seul type de fichier (gpkg ou CSV)");
        }
    }

    /**
     *
     */
    private function getSrids($file) {
        $extension = strtolower($file->getExtension());
        if ($extension == 'csv')  return [];    // difficile de connaitre le srd d'un fichier CSV
        
        $srids = [];
        if ($extension == 'gpkg') {
            $this->getSridsFromFile($file, $srids);
        } else {
            $this->getSridsFromArchive($file, $srids);
        }
        return $srids;
    }

    /**
     * 
     */
    private function getSridsFromArchive($file, &$srids) {
        $fs = new Filesystem();

        $infos = pathinfo($file->getPathname());
        $dirname    = $infos['dirname'];
        $filename   = $infos['filename'] . '_tmp';

        // Extracting zip file
        $zip = new \ZipArchive();
        if (! $zip->open($file->getPathname())) {
            throw new \Exception("l'ouverture du fichier ZIP échouée");
        }

        $folder = "$dirname/$filename";
        if (!$zip->extractTo($folder)) {
            return -1;
        }
        $zip->close();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder,\RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $entry) {
            $extension = strtolower($entry->getExtension());
            if ($extension != 'gpkg') continue;
            
            $this->getSridsFromFile($entry, $srids);
        } 

        $fs->remove($folder);
    }

    private function getSridsFromFile($file, &$srids) {
        $filepath = $file->getPathname();

        $db = new \SQLite3($filepath); 
        $res = $db->query('SELECT table_name, srs_id FROM gpkg_geometry_columns');
        while ($row = $res->fetchArray()) {
            $srids[] = 'EPSG:' . $row['srs_id'];
        } 
    }
}