<?php
/**
 * Created by PhpStorm.
 * User: alali
 * Date: 31.01.2019
 * Time: 10:51
 */

namespace app\modules\api\common\controllers;

use app\components\templates\FormTemplate;
use app\components\templates\ModelTemplate;
use app\modules\api\common\controllers\base\TemplatedClassActiveController;
use app\modules\api\common\controllers\interfaces\ITemplatedFormActiveController;
use app\rbac\LimitedAccessRule;
use yii\helpers\Inflector;
use Yii;

class FormController extends TemplatedClassActiveController implements ITemplatedFormActiveController
{
    public function getDataFormClassName(): string
    {
        $name = $this->getName();
        return Inflector::camelize($name . '-form');
    }

    public function getDataFormNameSpace(): string
    {
        return Yii::$app->params['formsClassesNs'];
    }

    public function getDataFormTemplate(): FormTemplate
    {
        return Yii::$app->templates->getFormTemplate($this->getName());
    }

    public function getFormDataModelTemplate(): ModelTemplate
    {
        return Yii::$app->templates->getModelTemplate($this->getDataFormTemplate()->dataModel);
    }

    public static function updateAccessRights()
    {
        $createAccessModes = ['edit', 'view'];
        $authManager = \Yii::$app->authManager;
        $viewerRole = $authManager->getRole('viewer');
        $operatorRole = $authManager->getRole('operator');
        /**
         * form-ALL:view and form-ALL:edit permissions are required
         * to allow administrator to add a limited access permission
         * with limited access rule because rule can be applied
         * only on a permission not role;
         */
        // ensure that view all permission exists
        $viewAllFormPermission = $authManager->getPermission('form-ALL:view');
        if ($viewAllFormPermission == null) {
            $viewAllFormPermission = $authManager->createPermission('form-ALL:view');
            $authManager->add($viewAllFormPermission);
            $authManager->addChild($viewerRole, $viewAllFormPermission);
        }
        // ensure that operate all permissions exists
        $editAllFormPermission = $authManager->getPermission('form-ALL:edit');
        if ($editAllFormPermission == null) {
            $editAllFormPermission = $authManager->createPermission('form-ALL:edit');
            $authManager->add($editAllFormPermission);
            $authManager->addChild($operatorRole, $editAllFormPermission);
        }
        // ensure that limited access rule exists and up to date
        $authManager->removeAllRules();
        $hasLimitedAccessRule = new LimitedAccessRule;
        $authManager->add($hasLimitedAccessRule);
        $formPermissions = [];
        $formNames = array_merge(\app\components\templates\FormTemplate::getGeneratedFormNames(), ['ArchiveFile']);
        foreach ($formNames as $formName) {
            foreach ($createAccessModes as $access) {
                // deal numbers as words (to work as _.kebabCase)
                $formName = Inflector::camel2id(trim(preg_replace('/[0-9]+/', '-$0-', $formName)), '-');
                $formName = str_replace('--', '-', $formName);
                $formPermissions["form-" . $formName . ":" . $access] = ["formName" => Inflector::camel2id($formName), "access" => $access];
            }
        }

        // remove non-existing forms' permissions except view/edit all forms
        foreach ($authManager->getPermissions() as $permission) {
            if (!isset($formPermissions[$permission->name]) && !str_starts_with($permission->name, 'form-ALL')) {
                $authManager->remove($permission);
            }
        }

        foreach ($formPermissions as $formPermissionName => $formPermission) {
            if ($formPermission['access'] === 'edit') {
                $editPermission = $authManager->getPermission($formPermissionName);
                if ($editPermission == null) {
                    $editPermission = $authManager->createPermission($formPermissionName);
                    $authManager->add($editPermission);
                }
                // reorder permission under form-ALL:edit
                if ($authManager->hasChild($operatorRole, $editPermission)) {
                    $authManager->removeChild($operatorRole, $editPermission);
                }
                if (!$authManager->hasChild($editAllFormPermission, $editPermission)) {
                    $authManager->addChild($editAllFormPermission, $editPermission);
                }

                $viewPermission = $authManager->getPermission("form-" . $formPermission['formName'] . ':' . 'view');
                if ($viewPermission == null) {
                    $viewPermission = $authManager->createPermission("form-" . $formPermission['formName'] . ':' . 'view');
                    $authManager->add($viewPermission);
                }
                // reorder permission under form-ALL:view
                if ($authManager->hasChild($viewerRole, $viewPermission)) {
                    $authManager->removeChild($viewerRole, $viewPermission);
                }
                if (!$authManager->hasChild($viewAllFormPermission, $viewPermission)) {
                    $authManager->addChild($viewAllFormPermission, $viewPermission);
                }

                // adding a view permission to an edit permission
                if(!$authManager->hasChild($editPermission, $viewPermission)) {
                    $authManager->addChild($editPermission, $viewPermission);
                }
            }
        }
    }
}