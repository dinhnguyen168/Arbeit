<?php namespace models;

use app\modules\api\common\models\FilesUploadedFormModel;

class FilesUploadedFormTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'expeditions' => [
                'class' => \app\tests\fixtures\ProjectExpeditionFixture::class,
                'dataFile' => codecept_data_dir() . 'project_expedition.php'
            ],
            'sites' => [
                'class' => \app\tests\fixtures\ProjectSiteFixture::class,
                'dataFile' => codecept_data_dir() . 'project_site.php'
            ],
            'holes' => [
                'class' => \app\tests\fixtures\ProjectHoleFixture::class,
                'dataFile' => codecept_data_dir() . 'project_hole.php'
            ],
            'cores' => [
                'class' => \app\tests\fixtures\CoreCoreFixture::class,
                'dataFile' => codecept_data_dir() . 'core_core.php'
            ],
            'sections' => [
                'class' => \app\tests\fixtures\CoreSectionFixture::class,
                'dataFile' => codecept_data_dir() . 'core_section.php'
            ],
            'section_splits' => [
                'class' => \app\tests\fixtures\CurationSectionSplitFixture::class,
                'dataFile' => codecept_data_dir() . 'curation_section_split.php'
            ],
            'samples' => [
                'class' => \app\tests\fixtures\CurationSampleFixture::class,
                'dataFile' => codecept_data_dir() . 'curation_sample.php'
            ],
        ];
    }

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testGetsExpeditionsListWhenNoValuesSet()
    {
        $model = new FilesUploadedFormModel();
        expect($model->getSelectListValues())->equals([
            'expedition' => [
                [
                    'text' => 'GRIND',
                    'value' => 1,
                ],
                [
                    'text' => 'GFZ',
                    'value' => 2,
                ]
            ],
            'person' => []
        ]);
    }

    public function testGetsDependantListBasedOnValue()
    {
        $model = new FilesUploadedFormModel();
        $model->assignIds = [
            'expedition' => 1
        ];
        expect($model->getSelectListValues('expedition'))->equals([
            'site' =>[
                [
                    'text' => '1',
                    'value' => 1,
                ],
                [
                    'text' => '2',
                    'value' => 2,
                ],
            ],
            'hole' => [],
            'core' => [],
            'section' => [],
            'sectionSplit' => [],
            'sample' => [],
            'sampleRequest' => []
        ]);
    }

    public function testGetsOnlyDependantListBasedOnValueStartingFromSpecificField()
    {
        $model = new FilesUploadedFormModel();
        $model->assignIds = [
            'hole' => 1
        ];
        expect($model->getSelectListValues('hole'))->equals([
            'core' => [
                [
                    'text' => 1,
                    'value' => 1,
                ],
                [
                    'text' => 2,
                    'value' => 2,
                ]
            ],
            'section' => [],
            'sectionSplit' => [],
            'sample' => [],
        ]);
    }

    public function testDoesNotReturnSiblingsLists() {
        $model = new FilesUploadedFormModel();
        $model->assignIds = [
            'request' => 1
        ];
        expect($model->getSelectListValues('request'))->equals([]);
    }
}
