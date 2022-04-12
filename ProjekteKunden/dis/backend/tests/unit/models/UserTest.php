<?php

namespace tests\unit\models;

use app\models\core\User;
use app\tests\fixtures\UserFixture;
use yii\base\Security;

class UserTest extends \Codeception\Test\Unit
{
    public function _fixtures()
    {
        return [
            'users' => [
                'class' => UserFixture::class,
                // fixture data located in tests/_data/user.php
                'dataFile' => codecept_data_dir() . 'user.php'
            ],
        ];
    }

    public function testFindUserById()
    {
        expect_that($user = User::findIdentity(1));
        expect($user->username)->equals('sa');

        expect_not(User::findIdentity(999));
    }

    public function testFindUserByAccessToken()
    {
        expect_that($user = User::findIdentityByAccessToken('100-token'));
        expect($user->username)->equals('sa');

        expect_not(User::findIdentityByAccessToken('non-existing'));
    }

    public function testFindUserByUsername()
    {
        expect_that($user = User::findOne(['username' => 'sa']));
        expect_not(User::findOne(['username' => 'not-admin']));
    }

    /**
     * @depends testFindUserByUsername
     */
    public function testValidateUser($user)
    {
        $user = User::findOne(['username' => 'sa']);
        expect_that($user->validateAuthKey('test100key'));
        expect_not($user->validateAuthKey('test102key'));
        $securityModel = new Security();
        expect_that($securityModel->validatePassword('neun51', $user->password_hash));
        expect_not($securityModel->validatePassword('notvalid', $user->password_hash));
    }

}
