<?php

require_once "JournalScript.php";

use Ramsey\Uuid\Uuid;

class NewFieldUuidUpgradeUserTable extends JournalScript
{
    public const TABLE = 'USER';
    public const NEW_COLUMN = 'uuid';
    public const DEFAULT_SIZE = 500; // Number of uid(s) to update at the same time

    public function __construct()
    {
        $this->displayInfo(sprintf("*** Updating the %s column in the %s table ***", self::NEW_COLUMN, self::TABLE), true);

        $this->setArgs(
            array_merge($this->getArgs(), [
                'uid|id=i' => "User identifier [Optional: all Users will be processed if the script is run without this parameter.]",
                'buffer|b=i' => "Number of Users to update at the same time [default: buffer = 500]",
                'delUidPath|dop' => "Delete old photo path name create already based on UID | In all cases, a new path is created, this time based on the uuid"
            ]));

        parent::__construct();

    }

    public function run()
    {
        $t0 = time();

        if ($this->isVerbose()) {
            $this->displayInfo("Verbose mode: 1");
            $this->displayInfo("Debug mode: " . $this->isDebug());
        }

        $this->initApp();
        defineSQLTableConstants();
        $this->initDb();

        if (!$this->existColumn(self::NEW_COLUMN, self::TABLE)) {
            $this->displayCritical(sprintf("Unknown column '%s'%s", self::NEW_COLUMN, PHP_EOL));
            $alter = "ALTER TABLE `USER` ADD `uuid` CHAR(36) NULL DEFAULT NULL AFTER `UID`;";
            $alter .= PHP_EOL;
            $alter .= "";
            $this->displayInfo(sprintf("TO DO BEFORE => %s%s", PHP_EOL, $alter), true);
            exit(1);
        }


        if (!$this->getDb()) {
            $this->displayInfo("Null pointer exception");
            exit(1);
        }


        $params = $this->getParams();
        $buffer = $params['buffer'] ?? self::DEFAULT_SIZE;
        $uidParamMsg = '';


        $dataQuery = $this->getDb()
            ->select()
            ->from(self::TABLE)->order('UID DESC');


        if ($this->hasParam('uid')) {
            $dataQuery->where('UID = ?', $params['uid']);
            $uidParamMsg = sprintf(' for User #%s', $params['uid']);
        }


        $data = $this->getDb()->fetchAssoc($dataQuery);
        $uidData = array_keys($data);


        if (empty($data)) {
            $this->displayInfo(sprintf('No data to process%s', $uidParamMsg), true);
            exit(0);
        }


        $count = count($data);

        $totalPages = ceil($count / $buffer);

        $cpt = 1;


        if ($this->isVerbose()) {

            $this->displayTrace('** Preparing the update...', true);
            $this->displayTrace(sprintf('Buffer: %s', $buffer), true);
            $this->displayTrace(sprintf('Total pages : %s', $totalPages), true);
        }

        $dump = '';

        for ($page = 1; $page <= $totalPages; $page++) {

            if ($this->isVerbose()) {
                $this->displayTrace(sprintf('Page #%s', $page), true);
            }

            $toUpdate = '';
            $users = [];
            $offset = ($page - 1) * $buffer;
            $cData = array_slice($uidData, $offset, $buffer);
            $this->getProgressBar()->start();

            foreach ($cData as $uid) {

                $uid = (int)$uid;

                if ($this->isVerbose()) {
                    $this->displayTrace(sprintf('[UID #%s]', $uid), true);
                }

                $progress = round(($cpt * 100) / $count);

                if ($this->isVerbose()) {

                    $uuid = Uuid::uuid4()->toString();

                    $values = $data[$uid];

                    $user = new Episciences_User($values);
                    $user->setUuid($uuid);

                    $users[$user->getUid()]['current'] = $user;

                    if (isset($values['uuid'])) {
                        $users[$user->getUid()]['oldUuid'] = $values['uuid'];
                    }

                    $this->displaySuccess(sprintf('** Current UUID [#%s] ...', $uuid), true);
                    $toUpdate .= sprintf('%sUPDATE %s set `uuid` = %s  WHERE UID = %s;', PHP_EOL, self::TABLE, $this->getDb()->quote($uuid), $uid);
                }

                $this->getProgressBar()->setProgress($progress);

                ++$cpt;
            }

            if ($this->isVerbose()) {
                $this->displayDebug(sprintf('Applying Update... %s %s', PHP_EOL, $toUpdate), true);
            }

            $result = 0;

            if (!$this->isDebug()) {
                $statement = $this->getDb()->query($toUpdate);

                $this->checkAndSavePhoto($users, $this->hasParam('delUidPath'));

                try {
                    $result = $statement->rowCount();
                    $statement->closeCursor();
                } catch (Zend_Db_Statement_Exception $e) {
                    $this->displayCritical($e->getMessage());
                }

            }

            if ($this->isVerbose()) {

                if (!$this->isDebug()) {
                    $message = sprintf("Affected rows: %s", $result);
                    $this->displaySuccess(sprintf('Page #%s processed: %s', $page, $message), true);

                } else {
                    $this->displayDebug(sprintf('Page #%s processed: %s', $page, 'successfully updated'), true);
                }
            }

            $dump .= $toUpdate;
        }

        $t1 = time();
        $time = $t1 - $t0;

        $this->displayTrace("The script took $time seconds to run.", true);

        $this->displaySuccess(sprintf("([DUMP] %s", $dump), true);

        $this->displayWarning(sprintf("If you haven't already done so, %s Please modify the %s table structure to make the %s unique", PHP_EOL, self::TABLE, self::NEW_COLUMN), true);
        $alter = "ALTER TABLE `USER` CHANGE `uuid` `uuid` CHAR(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Storing As a String';";
        $alter .= PHP_EOL;
        $alter .= "ALTER TABLE `USER` ADD UNIQUE (`uuid`);";
        $this->displayInfo(sprintf("TODO => %s%s", PHP_EOL, $alter), true);


    }


    public function checkAndSavePhoto(array $users = [], bool $deleteOldPath = false): void
    {
        /** @var Episciences_User $user */

        foreach ($users as $uid => $values) {

            $user = $values['current'];

            if (isset($values['oldUuid'])) {

                $oldPhotoPathName = $user->getPhotoPathName($values['oldUuid']);

                if ($oldPhotoPathName) {
                    $oldPhotoPath = $user->getPhotoPath($values['oldUuid']);
                    $user->deletePhoto($values['oldUuid']);

                    if ($this->isVerbose()) {
                        $this->displayTrace(sprintf("Profile photo [%s] deleted successfully form path [%s)", $oldPhotoPathName, $oldPhotoPath), true);
                    }

                    try {
                        $user->savePhotoWithUuid($user->getPhotoPathName($user->getUuid()));
                        if ($this->isVerbose()) {
                            $this->displayTrace(sprintf("Profile photo [%s] saved successfully in path [%s)", $oldPhotoPathName, $user->getPhotoPathWithUuid($user->getUuid())), true);
                        }
                    } catch (Exception $e) {
                        $this->displayCritical($e->getMessage());
                    }

                }

            }

            if ($user::hasPhoto($user->getUid())) { // Ancienne photo

                try {
                    $uidPhotoPathName = $user->getPhotoPathName();
                    $uidPhotoPath = $user->getPhotoPath();
                    $user->savePhotoWithUuid($uidPhotoPathName);

                    if ($this->isVerbose()) {
                        $this->displayTrace(sprintf("Profile photo [%s] saved successfully in path [%s)", $uidPhotoPathName, $user->getPhotoPathWithUuid()), true);
                    }

                    if ($deleteOldPath) {

                        $user->deletePhoto();

                        if ($this->isVerbose()) {
                            $this->displayTrace(sprintf("Profile photo [%s] deleted successfully form path [%s)", $uidPhotoPathName, $uidPhotoPath), true);
                        }
                    }

                } catch (Exception $e) {
                    $this->displayCritical($e->getMessage());
                }

            }


        }

    }

}

$script = new NewFieldUuidUpgradeUserTable();
$script->run();