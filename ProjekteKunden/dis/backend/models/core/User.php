<?php

namespace app\models\core;

use ancor\relatedKvStorage\RelatedConfig;
use Da\User\Model\User as BaseUser;

class User extends BaseUser
{
    const TOKEN_VALID_TIME = 86400; // 24 * 3600
    const TOKEN_EXTEND_INTERVAL = 300; // 5 * 60

    private $_config = null;

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        // add field to scenarios
        $scenarios['create'][]   = 'is_ldap_user';
        $scenarios['update'][]   = 'is_ldap_user';
        $scenarios['register'][] = 'is_ldap_user';
        return $scenarios;
    }

    public function rules()
    {
        $rules = parent::rules();
        // add some rules
        if ($this->hasProperty('is_ldap_user')) {
            $rules['fieldLength']   = ['is_ldap_user', 'integer'];
        }

        return $rules;
    }

    /** @inheritdoc */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::find()
            ->andWhere(['not', ['api_token' => null]])
            ->andWhere(['not', ['token_expire' => null]])
            ->andWhere(['api_token' => $token])
            ->andWhere(['>=', 'token_expire', time()])
            ->one();
    }

    /** @inheritdoc */
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->setAttribute('api_token', \Yii::$app->security->generateRandomString());
        }
        return parent::beforeSave($insert);
    }

    /** @inheritdoc */
    public function block()
    {
        if (parent::block()) {
            return (bool)$this->updateAttributes([
                'api_token'   => null,
            ]);
        }
        return false;
    }

    public function extendTokenLifetime () {
        if ($this->token_expire > time() + self::TOKEN_EXTEND_INTERVAL) {
            $this->updateAttributes([
                'token_expire' => time() + User::TOKEN_VALID_TIME
            ]);
        }
    }

    /**
     * Get user configuration
     * @return RelatedConfig
     * @throws \yii\base\InvalidConfigException
     */
    public function getConfig() {
        if ($this->_config == null) {
            $this->_config = \Yii::createObject([
                'class'      => RelatedConfig::class,
                'relationId' => $this->id,
                // Default settings
                'tableName' => '{{user_config}}',
                'relationIdField' => 'user_id',
                'configComponentName' => 'config',
                'useCommonConfig' => true,
            ]);
        }
        return $this->_config;
    }
}
