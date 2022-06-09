<?php

namespace App\Service\PlageApi;

use App\Constants\UploadStatuses;
use App\Exception\AppException;
use App\Exception\PlageApiException;

class UploadApiService extends AbstractPlageApiService
{
    /**
     * @param string $datastoreId
     * @param array  $query
     *
     * @return array
     */
    public function getAll($datastoreId, $query = [])
    {
        $query['sort'] = 'date:desc'; // sort by creation date in descending order

        return $this->requestAll("datastores/$datastoreId/uploads", $query);
    }

    /**
     * @param string $datastoreId
     * @param string $uploadId
     *
     * @return array
     */
    public function get($datastoreId, $uploadId)
    {
        return $this->request('GET', "datastores/$datastoreId/uploads/$uploadId");
    }

    /**
     * @param string $datastoreId
     * @param string $uploadId
     *
     * @return array
     */
    public function getFileTree($datastoreId, $uploadId)
    {
        $upload = $this->get($datastoreId, $uploadId);
        if (UploadStatuses::DELETED == $upload['status'] || UploadStatuses::OPEN == $upload['status']) {
            if (array_key_exists('file_tree', $upload['tags'])) {
                return json_decode($upload['tags']['file_tree'], true);
            }

            return [];
        }

        return $this->request('GET', "datastores/$datastoreId/uploads/$uploadId/tree");
    }

    /**
     * @param string $datastoreId
     * @param string $uploadId
     *
     * @return array
     */
    public function getComments($datastoreId, $uploadId)
    {
        return $this->request('GET', "datastores/$datastoreId/uploads/$uploadId/comments");
    }

    /**
     * Creates (and opens) a new upload entry.
     *
     * @param string $datastoreId
     * @param array  $uploadData
     *
     * @return array API response
     *
     * @throws PlageApiException when the operation fails on the API side
     */
    public function add($datastoreId, $uploadData)
    {
        try {
            return $this->request('POST', "datastores/$datastoreId/uploads", [
                'name' => $uploadData['name'],
                'description' => $uploadData['description'],
                'type' => $uploadData['type'],
                'srs' => $uploadData['srs'],
            ]);
        } catch (PlageApiException $ex) {
            throw new PlageApiException('Création de la livraison échouée');
        }
    }

    /**
     * @param string $datastoreId
     * @param string $uploadId
     * @param string $filename
     *
     * @return void
     */
    public function addFile($datastoreId, $uploadId, $filename)
    {
        $filepath = realpath($this->parameters->get('oneup_uploader_gallery_path')."/$filename");
        $infos = pathinfo($filepath);

        $extension = $infos['extension'];

        $files = [];
        try {
            if ('zip' != $extension) {
                $files[] = $filepath;
            } else {
                // extracting zip file
                $zip = new \ZipArchive();
                if (!$zip->open($filepath)) {
                    throw new AppException('Ouverture du fichier ZIP échouée');
                }

                $folder = join([$infos['dirname'], DIRECTORY_SEPARATOR, $infos['filename']]);
                if (!$zip->extractTo($folder)) {
                    throw new AppException('Décompression du fichier zip échouée');
                }
                $zip->close();

                // add files to the upload
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS)
                );

                $filename = null;
                foreach ($iterator as $entry) {
                    $files[] = $entry->getPathname();
                }
            }

            foreach ($files as $filepath) {
                $filename = basename($filepath);
                $this->uploadFile($datastoreId, $uploadId, $filepath, $filename);
            }

            // close the upload
            $this->close($datastoreId, $uploadId);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Adds a file and its md5 checksum to an existing and OPEN upload.
     *
     * @param string $datastoreId
     * @param string $uploadId
     * @param string $pathname
     * @param string $filename
     *
     * @return void
     */
    public function uploadFile($datastoreId, $uploadId, $pathname, $filename)
    {
        // posting the file itself
        $this->postFile("datastores/$datastoreId/uploads/$uploadId/data", $pathname);

        // calculating and posting the file's md5 checksum
        $md5 = \md5_file($pathname);
        $md5filePath = "$pathname.md5";
        file_put_contents($md5filePath, "$md5 data/$filename");

        $this->postFile("datastores/$datastoreId/uploads/$uploadId/md5", $md5filePath);

        $this->fs->remove($pathname);
        $this->fs->remove($md5filePath);
    }

    /**
     * Opens an existing upload only if it isn't already OPEN.
     *
     * @param string $datastoreId
     * @param string $uploadId
     *
     * @return void
     */
    public function open($datastoreId, $uploadId)
    {
        if (UploadStatuses::OPEN != $this->get($datastoreId, $uploadId)['status']) {
            $this->request('POST', "datastores/$datastoreId/uploads/$uploadId/open");
        }
    }

    /**
     * Closes an existing upload only if it isn't already CLOSED.
     *
     * @param string $datastoreId
     * @param string $uploadId
     *
     * @return void
     */
    public function close($datastoreId, $uploadId)
    {
        if (UploadStatuses::CLOSED != $this->get($datastoreId, $uploadId)['status']) {
            $this->request('POST', "datastores/$datastoreId/uploads/$uploadId/close");
        }
    }

    /**
     * @param string $datastoreId
     * @param string $uploadId
     * @param array  $tags
     *
     * @return void
     */
    public function addTags($datastoreId, $uploadId, $tags)
    {
        $this->request('POST', "datastores/$datastoreId/uploads/$uploadId/tags", $tags);
    }

    /**
     * @param string $datastoreId
     * @param string $uploadId
     * @param array  $tags
     *
     * @return void
     */
    public function removeTags($datastoreId, $uploadId, $tags)
    {
        $this->request('DELETE', "datastores/$datastoreId/uploads/$uploadId/tags", [], [
            'tags' => $tags,
        ]);
    }

    public function remove($datastoreId, $uploadId)
    {
        $upload = $this->get($datastoreId, $uploadId);
        if (UploadStatuses::OPEN == $upload['status']) {
            $this->close($datastoreId, $uploadId);
        }

        // sauvegarde dans les tags de l'aborescence de fichiers de la livraison avant de la supprimer, parce qu'une fois supprimée elle ne sera plus récupérable
        try {
            $fileTree = $this->getFileTree($datastoreId, $uploadId);
            $this->addTags($datastoreId, $uploadId, [
                'file_tree' => json_encode($fileTree),
            ]);
        } catch (PlageApiException $ex) {
            // ne rien faire, tant pis si la récupération de l'arborescence a échoué
        }

        return $this->request('DELETE', "datastores/$datastoreId/uploads/$uploadId");
    }

    public function getEvents($datastoreId, $uploadId)
    {
        return $this->request('GET', "datastores/$datastoreId/uploads/$uploadId/events");
    }

    public function getCheckExecutions($datastoreId, $uploadId)
    {
        return $this->request('GET', "datastores/$datastoreId/uploads/$uploadId/checks");
    }

    public function getChecks($datastoreId)
    {
        return $this->request('GET', "datastores/$datastoreId/checks");
    }

    public function getCheck($datastoreId, $checkId)
    {
        return $this->request('GET', "datastores/$datastoreId/checks/$checkId");
    }

    public function getCheckExecution($datastoreId, $checkExecutionId)
    {
        return $this->request('GET', "datastores/$datastoreId/checks/executions/$checkExecutionId");
    }

    public function getCheckExecutionLogs($datastoreId, $checkExecutionId)
    {
        return $this->request('GET', "datastores/$datastoreId/checks/executions/$checkExecutionId/logs", [], [], [], false, false);
    }
}
