<?php

class Episciences_Paper_FilesManager
{
    /**
     * @param int $docId
     * @return array [Episciences_Paper_File]
     */
    public static function findByDocId(int $docId)
    {

        $oResult = [];
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(T_PAPER_FILES)
            ->where('doc_id = ?', $docId);

        $rows = $db->fetchAssoc($sql);

        foreach ($rows as $value) {
            $oResult[] = new Episciences_Paper_File($value);
        }

        return $oResult;
    }

    /**
     * @param int $docId
     * @return bool
     */
    public static function deleteByDocId(int $docId): bool
    {
        if ($docId < 1) {
            return false;
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        return ($db->delete(T_PAPER_FILES, ['doc_id = ?' => $docId]) > 0);

    }

    /**
     * @param int $id
     * @return bool
     */
    public static function deleteById(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        return ($db->delete(T_PAPER_FILES, ['id = ?' => $id]) > 0);

    }


    /**
     * @param array $files
     * @return bool
     */

    public static function insert(array $files): bool
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $success = false;
        $values = [];

        foreach ($files as $file) {

            if (!($files instanceof Episciences_Paper_File)) {
                $file = new Episciences_Paper_File($file);
            }

            $values[] = '(' . $db->quote($file->getDocId()) . ',' . $db->quote($file->getFileName()) . ',' . $db->quote($file->getChecksum()) . ',' . $db->quote($file->getChecksumType()) . ',' . $db->quote($file->getSelfLink()) . ',' . $db->quote($file->getFileSize()) . ',' . $db->quote($file->getFileType()) . ')';
        }

        $sql = 'INSERT INTO ' . $db->quoteIdentifier(T_PAPER_FILES) . ' (`doc_id`, `file_name`, `checksum`, `checksum_type`, `self_link`, `file_size`, `file_type`) VALUES ';

        if (!empty($values)) {
            try {
                //Prepares and executes an SQL
                $db->query($sql . implode(', ', $values));
                $success = true;
            } catch (Exception $e) {
                trigger_error($e->getMessage(), E_USER_ERROR);
            }
        }

        return $success;

    }

    /**
     * @param Episciences_Paper_File $file
     * @return int
     */
    public static function update(Episciences_Paper_File $file): int
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $where['id = ?'] = $file->getId();

        $values = [
            'docId' => $file->getDocId(),
            'fileName' => $file->getFileName(),
            'checksum' => $file->getChecksum(),
            'checksumType' => $file->getChecksumType(),
            'selfLink' => $file->getSelfLink(),
            'fileSize' => $file->getFileSize(),
            'fileType' => $file->getFileSize()
        ];

        try {
            $resUpdate = $db->update(T_PAPER_FILES, $values, $where);
        } catch (Zend_Db_Adapter_Exception $exception) {
            error_log($exception->getMessage());
            $resUpdate = 0;
        }
        return $resUpdate;
    }

}