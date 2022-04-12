<?php
namespace app\commands;

use app\components\templates\ModelTemplate;
use app\migrations\Migration;
use app\models\core\Base;
use app\models\CoreSection;
use app\models\CurationSectionSplit;
use app\modules\api\common\controllers\FormController;
use Da\User\Factory\MailFactory;
use Da\User\Service\UserCreateService;
use Symfony\Component\Console\Exception\RuntimeException;
use Yii;
use yii\base\Exception;
use yii\console\Controller;
use yii\helpers\Console;
use yii\web\ServerErrorHttpException;

/**
 * This commands are used to automatically fill database with data.
 */
class SeedController extends Controller
{

    /**
     * Seed users accounts into database.
     * @param boolean $onlyAdministrator wheather to create only administrator account or further ones.
     * @return int Exit code
     */
    public function actionUsers($onlyAdministrator = false)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $auth = Yii::$app->authManager;
            $sa = $auth->getRole('sa');
            $developer = $auth->getRole('developer');
            $operator = $auth->getRole('operator');
            $viewer = $auth->getRole('viewer');

            $users = [
                ['role' => 'sa', 'email' => 'k.behrends@icdp-online.org', 'username' => 'administrator', 'password' => 'neun51']
            ];
            if (!$onlyAdministrator) {
                $users = array_merge($users, [
                    ['role' => 'sa', 'email' => 'kunkelc@gfz-potsdam.de', 'username' => 'kunkelc', 'password' => 'cindyk'],
                    ['role' => 'sa', 'email' => 'knb@gfz-potsdam.de', 'username' => 'knb', 'password' => 'knbpassword'],
                    ['role' => 'developer', 'email' => 'katjah@gfz-potsdam.de', 'username' => 'katjah', 'password' => 'katjahpassword'],
                    ['role' => 'developer', 'email' => 'knut.behrends@gfz-potsdam.de', 'username' => 'dev1', 'password' => 'dev1pw'],
                    ['role' => 'operator', 'email' => 'operator1@domain.com', 'username' => 'operator1', 'password' => 'operator1password'],
                    ['role' => 'operator', 'email' => 'operator2@domain.com', 'username' => 'operator2', 'password' => 'operator2password'],
                    ['role' => 'viewer', 'email' => 'user1@domain.com', 'username' => 'user1', 'password' => 'user1password'],
                    ['role' => 'viewer', 'email' => 'user2@domain.com', 'username' => 'user2', 'password' => 'user2password']
                ]);
            }
            // create users

            foreach ($users as $user) {
                /* @var $newUser \app\models\core\User */
                $newUser = Yii::createObject([
                    'class' => \app\models\core\User::class,
                    'scenario' => 'create',
                    'email' => $user['email'],
                    'username' => $user['username'],
                    'password' => $user['password']
                ]);

                $mailService = MailFactory::makeWelcomeMailerService($newUser);
                if (\Yii::$container->get(UserCreateService::class, [$newUser, $mailService])->run()) {
                    echo "User has been created!\n";
                    switch ($user['role']) {
                        case 'sa':
                            $auth->assign($sa, $newUser->id);
                            break;
                        case 'developer':
                            $auth->assign($developer, $newUser->id);
                            break;
                        case 'operator':
                            $auth->assign($operator, $newUser->id);
                            break;
                        case 'viewer':
                            $auth->assign($viewer, $newUser->id);
                            break;
                        default:

                    }
                } else {
                    echo sprintf("Something is wrong with user %s:\n", $user['email']);
                    if( isset($user->errors)){
                        foreach ($user->errors as $errors) {
                            foreach ($errors as $error) {
                                echo ' - ' . $error . "\n";
                            }
                        }
                    } else {
                        echo "Maybe user already existed in DB.\n\n";
                    }
                }
            }
            $transaction->commit();
        } catch (Exception $e) {
            echo $e->getMessage().'\n';
            $transaction->rollBack();
        }
    }

    /**
     * Create default tables and records (i.e. "ProjectExpedition")
     */
    public function actionExampleDump() {
        $sql = file_get_contents(__DIR__ . '/dis-example-dump.sql');
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $commands = preg_split('/;$/m', $sql);
            foreach($commands as $command) {
                $this->stdout(substr($command, 0, 64) . "...\n");
                $rows = Yii::$app->db->createCommand(trim($command))->execute();
                $this->stdout("Affected rows = $rows\n", Console::FG_GREEN);
            }
//            $rows = Yii::$app->db->createCommand($sql)->execute();
//            echo "done importing DB example-dump! Affected rows = $rows\n";
            $transaction->commit();
        } catch (\Exception $e) {
            echo $e->getMessage()."\n";
            $transaction->rollBack();
        } catch (\Throwable $e) {
            echo $e->getMessage()."\n";
            $transaction->rollBack();
        }
    }

    /**
     * imports some real-world core-section data  into a temp database tables
     */
    public function actionSectionWork() {
        $sql = file_get_contents(__DIR__ . '/dis-GRIND-core_section_work.sql');
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $commands = preg_split('/;/', $sql);
            $command_extra =<<<ENDINSERT
insert into core_section (
  `id`,
  `core_id`,
  `section`,
  `combined_id`,
  `top_depth`,
  `section_length`,
  `bottom_depth`,
  `analyst`,
  `igsn`,
  `section_state`,
  `curated_length`,
  `box`,
  `slot`,
  `position`)

select 
  `id`,
  `core_id`,
  `section`,
  `combined_id`,
  `top_depth`,
  `section_length`,
  `bottom_depth`,
  `analyst`,
  `igsn`,
  NULL as `section_state`,
  `curated_length`,
  `box`,
  `slot`,
  `position`
from core_section_work
ENDINSERT;
            array_push($commands, $command_extra);
            foreach($commands as $command) {
                $this->stdout(substr($command, 0, 64) . "...\n");
                $rows = Yii::$app->db->createCommand(trim($command))->execute();
                $this->stdout("Affected rows = $rows\n", Console::FG_GREEN);
            }
            $transaction->commit();

            echo "done importing GRIND core_section tables!\n";
        } catch (\Exception $e) {
            echo $e->getMessage().'\n';
            $transaction->rollBack();
        } catch (\Throwable $e) {
            echo $e->getMessage().'\n';
            $transaction->rollBack();
        }
    }

    /**
     * Load mDIS list_values table with more or less useful data
     */
    public function actionListValues() {
        $sql = file_get_contents(__DIR__ . '/list-values.sql');
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand($sql)->execute();
            echo "done importing list-values!\n";
            $update_sql = <<<ENDSQL
            update list_values set sort = 10 where sort IS NULL;
            
ENDSQL;
// update list_values set sort = sort * 10 where sort IS NOT NULL;";
            echo Yii::$app->db->createCommand($update_sql)->execute();
            $transaction->commit();
        } catch (\Exception $e) {
            echo $e->getMessage() . '\n';
            $transaction->rollBack();
        }
    }

    /**
     * Load mDIS list_values table with more or less useful data
     */
    public function actionWidgets() {
        $sql = file_get_contents(__DIR__ . '/mdis-widgets-default.sql');
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand($sql)->execute();
            echo "done importing widgets default values!\n";
            $transaction->commit();
        } catch (\Exception $e) {
            echo $e->getMessage() . '\n';
            $transaction->rollBack();
        }
    }

    /**
     * Updates the access rights based on the existing forms.
     * Should be called if forms are added outside of the template manager.
     */
    public function actionFormPermissions () {
        FormController::updateAccessRights();
    }

    /**
     * try to put the password for the "mdis" mysql user on the post-widget
     * insert it into the table
     */
    public function actionPostPass(){
        $dotfile =  sprintf("%s%s%s", getenv("HOME"), DIRECTORY_SEPARATOR, ".my.mdis_user.cnf");
        $credentials = file_get_contents($dotfile);
        $time = time();
        $uid = 1;  # assume user 1 is administrator
        $sql = "
INSERT INTO `post` ( `text`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(	'$credentials',	$uid,	$uid,	$time, $time);
";
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand($sql)->execute();
            echo "added mysql-user 'mdis' credentials to Dashboard!\n";
            $transaction->commit();
        } catch (\Exception $e) {
            echo $e->getMessage() . '\n';
            $transaction->rollBack();
        }
    }

    public function actionCreateWrSplits () {
        $query = CoreSection::find();
        $sectionsCount = $query->count();
        $sections = $query->all();
        $this->stdout("section count = $sectionsCount\n");
        $counter = 1;
        $invalidSplits = [];
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($sections as $section) {
                $this->stdout("$counter / $sectionsCount\r");
                $newSplit = new CurationSectionSplit();
                $newSplit->section_id = $section->id;
                $newSplit->type = 'WR';
                $newSplit->still_exists = 1;
                $newSplit->sampleable = 0;
                $attributesToCopy = $section->attributes;
                unset($attributesToCopy['combined_id']);
                unset($attributesToCopy['id']);
                $newSplit->trigger(Base::EVENT_DEFAULTS);
                $newSplit->setAttributes($attributesToCopy);
                if ($newSplit->validate()) {
                    $newSplit->save();
                } else {
                    $invalidSplits[] = $newSplit;
                }
                $counter += 1;
            }
            $transaction->commit();
        } catch (\Exception $e) {
            echo $this->stdout($e->getMessage() . "\n");
            $transaction->rollBack();
        }
        if (count($invalidSplits)) {
            $this->stdout("\nWas not able to save " . count($invalidSplits) . " splits \n", Console::FG_RED);
            foreach ($invalidSplits as $invalidSplit) {
                $this->stdout("- " . $invalidSplit->getErrorSummary(false)[0] . "\n", Console::FG_YELLOW);
            }
        }
    }

    private $modelsToGenerate = [
        'ContactOperator',
        'ContactOrganisation',
        'ContactPerson',
        'ContactRepository',
        'ProjectProgram',
        'ProjectExpedition',
        'CurationSampleRequest',
        'ProjectSite',
        'ProjectHole',
        'CoreCore',
        'CoreSection',
        'CurationStorage',
        'CurationCorebox',
        'CurationSectionSplit',
        'CurationSample',
        'CurationCuttings',
        'GeologyLithology',
        'GeologyStructure',
        'GeologyLithologicalUnits',
        'AuxiliaryTablesSurveyTools',
        'ArchiveFile',
    ];
    public function actionTemplatesTables () {
        // generating tables
        foreach ($this->modelsToGenerate as $modelName) {
            $model = Yii::$app->templates->getModelTemplate($modelName);
            /* @var $model ModelTemplate */
            if (!$model->getIsTableCreated()) {
                $migration = new Migration();
                $transaction = $migration->db->beginTransaction();
                try {
                    if ($model->generateTable($migration, false)) {
                        $transaction->commit();
                    } else {
                        throw new ServerErrorHttpException('Unable to create the table.');
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                    $model->restoreBackupVersion();
                    throw $e;
                }
                $model->deleteBackupVersion();
                echo "$modelName Created! \n";
            } else {
                echo "$modelName Already exists! \n";
            }
        }

        // generating relations
        foreach ($this->modelsToGenerate as $modelName) {
            $model = Yii::$app->templates->getModelTemplate($modelName);
            $warnings = [];
            /* @var $model ModelTemplate */
            if ($model->getIsTableCreated()) {
                $migration = new Migration();
                // TODO check why when using transaction, the relation not created in sql server.
                // $transaction = $migration->db->beginTransaction();
                try {
                    $model->validateDatabaseStructure($warnings, $migration);
                    // $transaction->commit();
                } catch (Exception $e) {
                    // $transaction->rollBack();
                    $model->restoreBackupVersion();
                    throw $e;
                }
                $model->deleteBackupVersion();
                echo "$modelName relations Created! \n";
            }
        }

        // generating connection tables
        foreach ($this->modelsToGenerate as $modelName) {
            $model = Yii::$app->templates->getModelTemplate($modelName);
            $warnings = [];
            /* @var $model ModelTemplate */
            if ($model->getIsTableCreated()) {
                if(isset($model->relations)) {
                    foreach ($model->relations as $relation) {
                        if (isset($relation->relationType) && $relation->relationType == 'nm') {
                            if(!$relation->oppositionRelation) {
                                $connectionTemplateData = $model->generateConnectionModelTemplateData($relation);
                                $connectionModel = \Yii::createObject(ModelTemplate::className());
                                $connectionModel->load($connectionTemplateData,'');
                                /* @var $connectionModel ModelTemplate */
                                if ($connectionModel && (!$connectionModel->getIsTableCreated())) {
                                    $warnings = [];
                                    $migration = new Migration();
                                    $transaction = $migration->db->beginTransaction();
                                    try {
                                        $connectionModel->validateDatabaseStructure($warnings, $migration);
                                        $transaction->commit();
                                        if (sizeof($warnings)) {
                                            \Yii::warning("The following modifications of table ". $connectionModel->table . " have been corrected:\n" . implode("\n", $warnings));
                                        }
                                    } catch (\Exception $e) {
                                        throw $e;
                                    }
                                }
                            }
                        }
                    }
                }
                echo "$modelName related connection table Created! \n";
            }
        }
    }
    public function actionTemplatesFiles() {
        foreach ($this->modelsToGenerate as $modelName) {
            $this->run('cg/data-model', [
                'templateName' => $modelName,
                'interactive' => 0,
                'overwrite' => 0
            ]);
        }
    }

    public function actionCopyListValuesToNewTables()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $db = Yii::$app->db;
            $db->createCommand('INSERT INTO dis_list (list_name) SELECT DISTINCT listname FROM list_values')->execute();
            $lists = $db->createCommand('SELECT DISTINCT * FROM dis_list')->queryAll();
            foreach ($lists as $list) {
                $listId = $list['id'];
                $listName = $list['list_name'];
                $db->createCommand("INSERT INTO dis_list_item (list_id, display, remark, sort) SELECT $listId, display, remark, sort FROM list_values WHERE listname='$listName'")->execute();
            }
            $this->stdout("\nList Values were copied to new structure successfully\n", Console::FG_GREEN);
            $transaction->commit();
            $db->createCommand('DROP TABLE list_values')->execute();
        } catch (\Exception $e) {
            $this->stderr("\nUnable to copy list values to new tables: \n" . $e->getMessage() . "\n\n", Console::FG_RED);
            $transaction->rollBack();
        }
    }

    public function actionRandomStorageLocations() {
        $locations = [
            "Site" => ["Marum Bremen", "BGR Berlin", "BGR Hannover", "LGRB Freiburg"],
            "Building" => ["A", "B", "C", "E", "F", "G", "H", "Lager", "Außenlager"],
            "Floor" => ["Basement", "E1", "E2", "E3", "1st Floor", "2nd Floor", "3rd Floor", "Hochebene"],
            "Room" => ["A002", "A003", "A004", "A005", "114", "116", "117", "118", "119", "120", "Abstellraum"],
            "Shelf" => ["S001", "S002", "S003", "S004", "S005", "S006", "S007", "S008", "S009", "S010"],
            "Shelf Level" => ["L1", "L2", "L3", "L4", "L5", "L6", "L7", "L8"]
        ];

        echo "Create storage locations: ...\n";
        $this->createLocations($locations);
    }

    protected function createLocations ($locations, $parentId = null) {
        $type = array_key_first($locations);
        $values = $locations[$type];

        // Remove some random elements from the list
        $removeItems = rand(1, sizeof($values) - 2);
        while ($removeItems > 0) {
            $i = rand(0, sizeof($values)-1);
            array_splice($values, $i, 1);
            $removeItems--;
        }
        unset($locations[$type]);
        foreach ($values as $value) {
            $storage = new \app\models\CurationStorage();
            $storage->type = $type;
            $storage->storage = $value;
            $storage->parent_id = $parentId;
            if ($storage->save()) {
                echo "- " . $storage->combined_id . "\n";
                if (sizeof($locations)) {
                    $this->createLocations($locations, $storage->id);
                }
            }
        }
    }


}
