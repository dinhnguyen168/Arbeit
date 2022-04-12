<?php
/**
 *
 * Parameters for the view:
 * @var $header
 * @var $batchEditForm
 * @var $columns
 * @var $modelTemplate
 * @var $formTemplate
 *
 **/
$this->registerAssetBundle(yii\web\JqueryAsset::className(), \yii\web\View::POS_HEAD);
?>
<?= $header ?>
<?php
$step = $batchEditForm->step;
?>
<div id="report" class="report batch-edit-report">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">1. Selected records of "<?= $formTemplate->dataModel?>" to batch edit</h3>
        </div>
        <div class="panel-body">
            <?php $form0 = $batchEditForm->startForm(0); ?>
            <input type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>" />
            <div class="table-container">
                <table class="table sample-table">
                    <thead>
                    <tr>
                        <?php foreach ($columns as $column => $label): ?>
                            <th><?= $label ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($batchEditForm->getSelectedModels() as $model): ?>
                        <tr>
                            <?php foreach ($columns as $column => $label): ?>
                                <td><?= $model->{$column} ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>

            <?php
            echo \yii\widgets\LinkPager::widget([
                // 'id' => 'selected-models-pagination',
                'options' => [
                    'class' => 'pagination selected-models-pagination',
                ],
                'pagination'=> $batchEditForm->dataProvider->pagination,
            ]);
            ?>

            <table class="table set-edit-inputs">
                <thead>
                <tr>
                    <th>Column</th>
                    <th>Options</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($columns as $column => $label): ?>
                    <tr>
                        <?php foreach ($modelTemplate->columns as $modelColumn): ?>
                        <?php if ($modelColumn->name !== 'id' && $column == $modelColumn->name && $modelColumn->type !== 'pseudo'): ?>
                        <?php
                            $inputType = null;
                            foreach ($formTemplate['fields'] as $fieldKey => $field) {
                                if( $field->name == $modelColumn->name && $field->formInput["type"] == 'select') {
                                    $inputType = $field->formInput["type"];
                                    $selectedField = $field;
                                }
                            }
                            $type = $modelColumn->type;

                        ?>

                        <td><?= $label ?></td>
                        <?php  ?>
                        <?php switch (true):
                            case ($inputType !== 'select' && ($type == "string" || $type == "integer" || $type == "double" || $type == "text" || $type == "string_multiple")): ?>
                            <td>
                                <select <?= $batchEditForm->isFinished ? 'disabled': '' ?> name="<?= 'BatchEditForm['.$column.'_operation'.']' ?>" @change="setFieldOptions($event, '<?= $column ?>', '<?= $type ?>', '<?= $label?>')">
                                    <option :selected="'<?= $batchEditForm->{$column.'_operation'} ?>' === val" v-for="(val, key) in getOptions('freeTextOptions', '<?=$modelColumn['required']?>')" :key="key" :value="val">{{ key }}</option>
                                </select>
                            </td>
                            <?php break; ?>
                            <?php case ($inputType !== 'select' && $type == "boolean"): ?>
                                <td>
                                    <select <?= $batchEditForm->isFinished ? 'disabled': '' ?> name="<?= 'BatchEditForm['.$column.'_operation'.']' ?>" @change="setFieldOptions($event, '<?= $column ?>', '<?= $type ?>', '<?= $label?>')">
                                        <option :selected="'<?= $batchEditForm->{$column.'_operation'} ?>' === val" v-for="(val, key) in getOptions('booleanOptions', '<?=$modelColumn['required']?>')" :key="key" :value="val">{{ key }}</option>
                                    </select>
                                </td>
                                <?php break; ?>
                            <?php case ($inputType !== 'select' && ($type == "dateTime" || $type == "date" || $type == "time")): ?>
                            <td>
                                <select <?= $batchEditForm->isFinished ? 'disabled': '' ?> name="<?= 'BatchEditForm['.$column.'_operation'.']' ?>" @change="setFieldOptions($event, '<?= $column ?>', '<?= $type ?>', '<?= $label?>')">
                                    <option :selected="'<?= $batchEditForm->{$column.'_operation'} ?>' === val" v-for="(val, key) in getOptions('dateOptions', '<?=$modelColumn['required']?>')" :key="key" :value="val">{{ key }}</option>
                                </select>
                            </td>
                            <?php break; ?>
                            <?php case ($inputType == "select"): ?>
                            <?php
                                $selectSource = json_encode($selectedField->formInput["selectSource"]);
                                $sourceType = $selectedField->formInput["selectSource"]["type"]
                            ?>
                            <td>
                            <?php if($sourceType === 'api'): ?>
                                <select <?= $batchEditForm->isFinished ? 'disabled': '' ?> name="<?= 'BatchEditForm['.$column.'_operation'.']' ?>" @change='setFieldOptions($event, "<?= $column ?>", "<?= $inputType ?>", "<?= $label?>", "<?= $selectedField->formInput["multiple"] ?>", <?= $selectSource ?>, "<?= $selectedField->formInput['allowFreeInput']?>")'>
                                    <option :selected="'<?= $batchEditForm->{$column.'_operation'} ?>' === val" v-for="(val, key) in getOptions('selectSingelOptions', '<?=$modelColumn['required']?>')" :key="key" :value="val">{{ key }}</option>
                                </select>
                            <?php elseif($sourceType === 'many_relation'): ?>
                                <select <?= $batchEditForm->isFinished ? 'disabled': '' ?> name="<?= 'BatchEditForm['.$column.'_operation'.']' ?>" @change='setFieldOptions($event, "<?= $column ?>", "<?= $inputType ?>", "<?= $label?>", "<?= $selectedField->formInput["multiple"] ?>", <?= $selectSource ?>, "<?= $selectedField->formInput['allowFreeInput']?>")'>
                                    <option :selected="'<?= $batchEditForm->{$column.'_operation'} ?>' === val" v-for="(val, key) in getOptions('selectMultiOptions', '<?=$modelColumn['required']?>')" :key="key" :value="val">{{ key }}</option>
                                </select>
                            <?php elseif($sourceType === 'list' && !$selectedField->formInput["multiple"]): ?>
                                <select <?= $batchEditForm->isFinished ? 'disabled': '' ?> name="<?= 'BatchEditForm['.$column.'_operation'.']' ?>" @change='setFieldOptions($event, "<?= $column ?>", "<?= $inputType ?>", "<?= $label?>", "<?= $selectedField->formInput["multiple"] ?>", <?= $selectSource ?>, "<?= $selectedField->formInput['allowFreeInput']?>")'>
                                    <option :selected="'<?= $batchEditForm->{$column.'_operation'} ?>' === val" v-for="(val, key) in getOptions('listValueSingleOptions', '<?=$modelColumn['required']?>')" :key="key" :value="val">{{ key }}</option>
                                </select>
                            <?php elseif($sourceType === 'list' && $selectedField->formInput["multiple"]): ?>
                                <select <?= $batchEditForm->isFinished ? 'disabled': '' ?> name="<?= 'BatchEditForm['.$column.'_operation'.']' ?>" @change='setFieldOptions($event, "<?= $column ?>", "<?= $inputType ?>", "<?= $label?>", "<?= $selectedField->formInput["multiple"] ?>", <?= $selectSource ?>, "<?= $selectedField->formInput['allowFreeInput']?>")'>
                                    <option :selected="'<?= $batchEditForm->{$column.'_operation'} ?>' === val" v-for="(val, key) in getOptions('listValueMultiOptions', '<?=$modelColumn['required']?>')" :key="key" :value="val">{{ key }}</option>
                                </select>
                            <?php endif;?>
                            <?php break; ?>
                            </td>
                            <?php endswitch; ?>
                            <div v-if="fieldsOptions.length">
                                <tr class="inputs-options" v-for="(val, key) in fieldsOptions" :key="'<?= $column?>'+key">
                                    <!-- first col in table -->
                                    <td v-if="val.columnName === '<?= $column?>'">
                                        <div v-if="val.operation === 'S+R'">
                                            <div class="group-input" v-if="val.type === 'text' || val.type === 'textarea' || val.type === 'datetime' || val.type === 'date'  || val.type === 'time' || (val.type === 'select' && val.selectSource.type === 'list')">
                                                <label>Search for</label>
                                                <input class="form-control" <?= $batchEditForm->isFinished ? 'disabled': '' ?> :type="val.type === 'datetime' ? val.type+'-local' : val.type" v-model="fieldsOptions[key].oldVal">
                                            </div>
                                        </div>

                                        <div v-if="val.operation === 'S+R_REGEX'">
                                            <div class="group-input" v-if="val.type === 'text' || val.type === 'textarea' || val.type === 'datetime' || val.type === 'date'  || val.type === 'time' || (val.type === 'select' && val.selectSource.type === 'list' && !val.selectSource.multiple)">
                                                <label>Search for</label>
                                                <input class="form-control" <?= $batchEditForm->isFinished ? 'disabled': '' ?> type="text" v-model="fieldsOptions[key].oldVal">
                                            </div>
                                        </div>

                                        <div v-if="val.operation === 'S+R_S'">
                                            <div class="group-input" v-if="val.type === 'select'">
                                                <label>Search for</label>
                                                <select class="form-control" <?= $batchEditForm->isFinished ? 'disabled': '' ?> :id="val.columnName+'-search'"><option></option></select>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- second col in table -->
                                    <td v-if="val.columnName === '<?= $column?>'">
                                        <div v-if="val.operation === 'B'">
                                            <div class="group-input-switch" v-if="val.type === 'boolean'">
                                                <label class="switch">
                                                    <input <?= $batchEditForm->isFinished ? 'disabled': '' ?> :id="fieldsOptions[key].columnName + '-' + key" type="checkbox" v-model="fieldsOptions[key].newVal">
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>

                                        <div v-if="val.operation === 'D'">
                                            <div class="group-input-delete" v-if="val.type === 'text' || val.type === 'textarea' || (val.type === 'select' && !val.multiple)">
                                                <input <?= $batchEditForm->isFinished ? 'disabled': '' ?> :id="fieldsOptions[key].columnName + '-' + key" type="checkbox" v-model="fieldsOptions[key].delete">
                                                <label :for="fieldsOptions[key].columnName + '-' + key">Delete content?</label>
                                            </div>
                                        </div>

                                        <div v-if="val.operation === 'DA'">
                                            <div class="group-input-delete" v-if="(val.type === 'select' && val.multiple)">
                                                <input <?= $batchEditForm->isFinished ? 'disabled': '' ?> :id="fieldsOptions[key].columnName + '-' + key" type="checkbox" v-model="fieldsOptions[key].deleteAll">
                                                <label :for="fieldsOptions[key].columnName + '-' + key">Delete all values?</label>
                                            </div>
                                        </div>
                                        <div v-if="val.operation === 'D_S'">
                                            <div class="group-input" v-if="val.type === 'select' && !val.multiple">
                                                <label>Delete one value</label>
                                                <select class="form-control" <?= $batchEditForm->isFinished ? 'disabled': '' ?> :id="val.columnName+'-select-delete'"><option></option></select>
                                            </div>
                                        </div>
                                        <div v-if="val.operation === 'A'">
                                            <div class="group-input" v-if="val.type === 'select' && val.multiple">
                                                <label>Add a value</label>
                                                <select class="form-control" <?= $batchEditForm->isFinished ? 'disabled': '' ?> :id="val.columnName+'-add'"></select>
                                            </div>
                                        </div>

                                        <div v-if="val.operation === 'R'">
                                            <div class="group-input" v-if="val.type === 'text' || val.type === 'textarea'">
                                                <label>Assign new value</label>
                                                <input class="form-control" <?= $batchEditForm->isFinished ? 'disabled': '' ?> :type="val.type" v-model="fieldsOptions[key].newVal">
                                            </div>
                                            <div class="group-input" v-if="val.type === 'datetime' || val.type === 'date' || val.type === 'time'">
                                                <label>Assign new value</label>
                                                <input class="form-control" <?= $batchEditForm->isFinished ? 'disabled': '' ?> :type="val.type === 'datetime' ? val.type+'-local' : val.type" v-model="fieldsOptions[key].newVal">
                                            </div>
                                        </div>

                                        <div v-if="val.operation === 'R_S'">
                                            <div class="group-input" v-if="val.type === 'select'">
                                                <label>Assign new value</label>
                                                <select class="form-control" <?= $batchEditForm->isFinished ? 'disabled': '' ?> :id="val.columnName+'-replace'"><option v-if="!val.multiple"></option></select>
                                            </div>
                                        </div>

                                        <div v-if="val.operation === 'S+R'">
                                            <div class="group-input" v-if="val.type === 'text' || val.type === 'textarea' || val.type === 'datetime' || val.type === 'date'  || val.type === 'time' || (val.type === 'select' && val.selectSource.type === 'list' && !val.selectSource.multiple)">
                                                <label>Replace with</label>
                                                <input class="form-control" <?= $batchEditForm->isFinished ? 'disabled': '' ?> :type="val.type === 'datetime' ? val.type+'-local' : val.type" v-model="fieldsOptions[key].newVal">
                                            </div>
                                        </div>

                                        <div v-if="val.operation === 'S+R_S'">
                                            <div class="group-input" v-if="val.type === 'select'">
                                                <label>Replace with</label>
                                                <select class="form-control" <?= $batchEditForm->isFinished ? 'disabled': '' ?> :id="val.columnName+'-replace'"><option v-if="!val.multiple"></option></select>
                                            </div>
                                        </div>

                                        <div v-if="val.operation === 'S+R_REGEX'">
                                            <div class="group-input" v-if="val.type === 'text' || val.type === 'textarea' || val.type === 'datetime' || val.type === 'date'  || val.type === 'time' || (val.type === 'select' && val.selectSource.type === 'list' && !val.selectSource.multiple)">
                                                <label>Replace with</label>
                                                <input class="form-control" <?= $batchEditForm->isFinished ? 'disabled': '' ?> type="text" v-model="fieldsOptions[key].newVal">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </div>
                        <?php endif;?>
                        <?php endforeach;?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?= $form0->field($batchEditForm, 'fieldsOptions')->hiddenInput(['id' => 'fields-options-input'])->label(false); ?>

            <?php $batchEditForm->showErrors(); ?>
            <?= \yii\helpers\Html::submitButton('Preview changes', array_merge(['class' => 'btn btn-warning'], ($batchEditForm->isFinished ? ['disabled' => 'disabled'] : []))) ?>
            <?php $batchEditForm->endForm(); ?>
        </div>
    </div>

    <?php if ($step > 1):?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">2. Preview changes</h3>
            </div>
            <div class="panel-body">
                <?php if (sizeof($batchEditForm->getOperationsErrors()) > 0): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($batchEditForm->getOperationsErrors() as $key => $error): ?>
                            <p><?= $key ?> : <?= $error ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php $form1 = $batchEditForm->startForm(1); ?>
                <div class="table-container">
                    <table class="table preview-sample-table">
                        <thead>
                        <tr>
                            <?php foreach ($columns as $column => $label): ?>
                                <th><?= $label ?></th>
                            <?php endforeach; ?>
                            <?php foreach ($modelTemplate->columns as $modelColumn): ?>
                                <?php if ($modelColumn->name !== 'id' && $column == $modelColumn->name && $modelColumn->calculate == ''): ?>
                                    <?= $form1->field($batchEditForm, $column.'_operation')->hiddenInput(['id' => $column.'_operation'])->label(false); ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($batchEditForm->getModelsToReview() as $model): ?>
                            <tr <?php if (sizeof($model->errors)):?>class="error" <?php endif;?>>
                                <?php foreach ($columns as $column => $label): ?>
                                    <td <?= $batchEditForm->isDirtyAttribute($model, $column) ? 'class="changed"' : ''?>><?= is_array($model->{$column}) ? implode(',', $model->{$column}) : $model->{$column}?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php if (sizeof($model->errors)):?>
                            <tr class="error">
                                <td colspan="<?= sizeof($columns) ?>"><?= implode ( "<br />" , \yii\helpers\ArrayHelper::getColumn ( $model->errors , 0 , false ) )?></td>
                            </tr>
                        <?php endif; ?>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>

                <?php
                echo \yii\widgets\LinkPager::widget([
                    // 'id' => 'selected-models-pagination',
                    'options' => [
                        'class' => 'pagination selected-models-pagination-2',
                    ],
                    'pagination'=> $batchEditForm->dataProvider->pagination,
                ]);
                ?>

                <?= $form1->field($batchEditForm, 'fieldsOptions')->hiddenInput(['id' => 'fields-options-input-2'])->label(false); ?>
                <?php $batchEditForm->showErrors(); ?>
                <?= \yii\helpers\Html::submitButton('Apply changes', array_merge(['id' => 'apply-changes-btn', 'class' => 'btn btn-warning'], ($batchEditForm->isFinished || $batchEditForm->getModelsToReviewError() > 0 ? ['disabled' => 'disabled'] : []))) ?>
                <div class="alert alert-warning" role="alert">
                    This cannot be undone.
                </div>
                <?php $batchEditForm->endForm(); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
  const App = new Vue({
    data: {
      fieldsOptions: [],
    },
    created() {
      this.fieldsOptions = <?= $batchEditForm->fieldsOptions ? $batchEditForm->fieldsOptions : '[]' ?>;
    },
    mounted() {
      for(let item of this.fieldsOptions) {
        if(item.hasOwnProperty('selectSource') && item.selectSource) {
          // $('select[name="BatchEditForm[' + item.columnName+ '_operation]"]').val(item.operation)
          if(item.selectSource.type === 'list') {
            this.createSelectListInputs (item.operation, item.columnName, item.selectSource, item.multiple, this, 0, item.oldVal, item.newVal)
          }
          if(item.selectSource.type === 'api' || item.selectSource.type === 'many_relation') {
            this.createSelectApiManyRelationInputs(item.operation, item.columnName, item.selectSource, item.multiple, this, 0, item.oldVal, item.newVal)
          }
        }
      }
    },
    methods: {
      getOptions(optionType, isRequired) {
        isRequired = !!isRequired
        var options = {
          'No action': 'N',
        }
          if(optionType === 'booleanOptions') {
              // if(!isRequired) Object.assign(options, {'Delete content': 'D'})
              Object.assign(options, {
                  'Assign new value': 'B'
              })
              return options
          }
        if(optionType === 'freeTextOptions') {
          if(!isRequired) Object.assign(options, {'Delete content': 'D'})
          Object.assign(options, {
            'Assign new value': 'R',
            'Search + Replace': 'S+R',
            'Search + Replace (Regular expression)': 'S+R_REGEX'
          })
          return options
        }
        if(optionType === 'selectSingelOptions') {
          if(!isRequired) Object.assign(options, {'Delete content': 'D_S'})
          Object.assign(options, {
            'Assign new value': 'R_S',
            'Search + Replace Value': 'S+R_S',
          })
          return options
        }
        if(optionType === 'selectMultiOptions') {
          if(!isRequired) Object.assign(options, {'Delete all values': 'DA'})
          Object.assign(options, {
            'Delete one value': 'D_S',
            'Add a value': 'A',
            'Search + Replace Value': 'S+R_S',
          })
          return options
        }
        if(optionType === 'listValueSingleOptions') {
          if(!isRequired) Object.assign(options, {'Delete content': 'D'})
          Object.assign(options, {
            'Assign new value': 'R_S',
            'Search + Replace Value': 'S+R_S',
            'Search + Replace': 'S+R',
            'Search + Replace (Regular expression)': 'S+R_REGEX'
          })
          return options
        }
        if(optionType === 'listValueMultiOptions') {
          if(!isRequired) Object.assign(options, {'Delete all values': 'DA'})
          Object.assign(options, {
            'Delete one value': 'D_S',
            'Add a value': 'A',
            'Search + Replace Value': 'S+R_S',
            'Search + Replace': 'S+R',
            'Search + Replace (Regular expression)': 'S+R_REGEX'
          })
          return options
        }
        if(optionType === 'dateOptions') {
          if(!isRequired) Object.assign(options, {'Delete content': 'D'})
          Object.assign(options, {
            'Assign new value': 'R',
            'Search + Replace': 'S+R',
            'Search + Replace (Regular expression)': 'S+R_REGEX'
          })
          return options
        }

      },
      setFieldOptions(e, columnName, inputType, label, multiple= null, selectSource = null, allowFreeInput = null) {
        if(inputType === 'integer' || inputType === 'string' || inputType === 'double' || inputType === 'string_multiple') inputType = 'text'
        if(inputType === 'dateTime') inputType = 'datetime'
        multiple = !!multiple;
        allowFreeInput = !!allowFreeInput;
        let item = this.fieldsOptions.find((obj) => obj.columnName === columnName)
        if (typeof item !== 'undefined') {
          if(e.target.value === 'N') {
            const index = this.fieldsOptions.findIndex((obj) => obj.columnName === columnName);
            if (index > -1) {
              this.fieldsOptions.splice(index, 1);
            }
          } else {
            item.operation = e.target.value
            // set to default
            item.delete = false
            item.deleteAll = false
            item.oldVal = null
            item.newVal = null
            item.selectSource = selectSource
            item.allowFreeInput = allowFreeInput
            item.multiple = multiple
          }
        } else {
          let newItem = {
            columnName: columnName,
            label: label,
            type: inputType,
            selectSource: selectSource,
            multiple: multiple,
            operation: e.target.value,
            oldVal: null,
            newVal: null,
            delete: false,
            deleteAll: false,
            allowFreeInput: allowFreeInput
          }
          this.fieldsOptions.push(newItem)
        }
        if(document.querySelector('#'+columnName+'_operation')) {
          document.querySelector('#'+columnName+'_operation').value = e.target.value
        }
        let thisVue = this
        if(selectSource) {
          if(selectSource.type === 'list' && (e.target.value === 'R_S' || e.target.value === 'S+R_S' || e.target.value === 'D_S' || e.target.value === 'A')) {
            this.createSelectListInputs (e.target.value, columnName, selectSource, multiple, thisVue, 100, [], [])
          } else {
            thisVue.destroySelectInput(columnName+'-search')
            thisVue.destroySelectInput(columnName+'-replace')
            thisVue.destroySelectInput(columnName+'-add')
            thisVue.destroySelectInput(columnName+'-select-delete')
          }

          if(selectSource.type === 'api' || selectSource.type === 'many_relation' && (e.target.value === 'R_S' || e.target.value === 'S+R_S' || e.target.value === 'D_S' || e.target.value === 'A')) {
            this.createSelectApiManyRelationInputs(e.target.value, columnName, selectSource, multiple, thisVue, 100, [], [])
          } else {
            thisVue.destroySelectInput(columnName+'-search')
            thisVue.destroySelectInput(columnName+'-replace')
            thisVue.destroySelectInput(columnName+'-add')
            thisVue.destroySelectInput(columnName+'-select-delete'  )
          }
        }
      },
      createSelectListInputs (operation, columnName, selectSource, multiple, thisVue, timeout, oldVal, newVal) {
        $.ajax({
          headers: {
            'Authorization' : 'Bearer <?= Yii::$app->user->identity->api_token ?>',
          },
          url : '/api/v1/list-values?sort=sort&filter[listname]=' + selectSource.listName,
          methods : 'GET',
          dataType : 'json',
        }).done(function(data) {
          data = data.items.map(function(item) {
            return {
              id : item.display,
              text : item.id + ' | ' + item.display
            };
          })
          setTimeout(() => {
            if(operation === 'R_S') {
              thisVue.createSelectListInput(columnName+'-replace', data, multiple, columnName, thisVue, false, newVal)
            }
            if(operation === 'D_S') {
              thisVue.createSelectListInput(columnName+'-select-delete', data, false, columnName, thisVue, false, newVal)
            }
            if(operation === 'A') {
              thisVue.createSelectListInput(columnName+'-add', data, multiple, columnName, thisVue, false, newVal)
            }
            if(operation === 'S+R_S') {
              thisVue.createSelectListInput(columnName+'-search', data, false, columnName, thisVue, false, oldVal)
              thisVue.createSelectListInput(columnName+'-replace', data, multiple, columnName, thisVue, false, newVal)
            }
          }, timeout)
        });
      },
      createSelectApiManyRelationInputs(operation, columnName, selectSource, multiple, thisVue, timeout, oldVal, newVal) {
        if(operation === 'R_S') {
          setTimeout(() => {
            thisVue.createSelectApiManyRelationInput(columnName+'-replace', selectSource, multiple, columnName, thisVue, false, newVal)
          }, timeout)
        }
        if(operation === 'D_S') {
          setTimeout(() => {
            thisVue.createSelectApiManyRelationInput(columnName+'-select-delete', selectSource, false, columnName, thisVue, false, newVal)
          }, timeout)
        }
        if(operation === 'A') {
          setTimeout(() => {
            thisVue.createSelectApiManyRelationInput(columnName+'-add', selectSource, multiple, columnName, thisVue, false, newVal)
          }, timeout)
        }
        if(operation === 'S+R_S') {
          setTimeout(() => {
            thisVue.createSelectApiManyRelationInput(columnName+'-search', selectSource, multiple, columnName, thisVue, true, oldVal)
            thisVue.createSelectApiManyRelationInput(columnName+'-replace', selectSource, multiple, columnName, thisVue, false, newVal)
          }, timeout)
        }
      },
      createSelectListInput(selectorID, data, multiple, columnName, thisVue, isSearch = false, value) {
        var val = isSearch ? 'oldVal' : 'newVal'
        $('#'+selectorID).select2({
          data: data,
          placeholder : 'Select ..',
          allowClear: true,
          multiple: multiple,
          closeOnSelect: !multiple,
          width: '100%'
        }).on('change', function (evnet) {
          for (let [key, item] of Object.entries(thisVue.fieldsOptions)) {
            if(item.columnName === columnName) {
              // Object.assign(thisVue.fieldsOptions[key][val], $(this).val())
              thisVue.fieldsOptions[key][val] = $(this).val()
            }
          }
        }).val(value).trigger('change');
      },
      createSelectApiManyRelationInput(selectorID, selectSource, multiple, columnName, thisVue, isSearch = false, value) {
        var val = isSearch ? 'oldVal' : 'newVal'
        console.log($('meta[name="csrf-token"]').attr('content'))
        $('#'+selectorID).select2({
          placeholder : 'Select ..',
          allowClear: true,
          multiple: multiple,
          closeOnSelect: !multiple,
          width: '100%',
          ajax : {
            headers: {
              'Authorization' : 'Bearer <?= Yii::$app->user->identity->api_token ?>',
            },
            url : '/api/v1/global/async-lists?name='+selectSource.model+'&sort='+selectSource.textField+'&fields='+ selectSource.textField + ','+selectSource.valueField +'&q=null&value=',
            methods : 'GET',
            dataType : 'json',
            data : function(params) {
              return  {
                q:params.term
              };
            },
            processResults : function(data, params) {
              return {
                results :
                  data.items.map(function(item) {
                      return {
                        id : item[selectSource.valueField],
                        text : item[selectSource.textField]
                      };
                    }
                  )};
            }
          }
        }).on('change', function (evnet) {
          for (let [key, item] of Object.entries(thisVue.fieldsOptions)) {
            if(item.columnName === columnName) {
              // Object.assign(thisVue.fieldsOptions[key][val], $(this).val())
              thisVue.fieldsOptions[key][val] = $(this).val()
            }
          }
        }).val(value).trigger('change');
      },
      destroySelectInput(SelectorID) {
        var el = $('#'+SelectorID)
        if(el.length > 0) {
          if (el.hasClass('select2-hidden-accessible')) {
            el.select2('destroy')
          }
        }
      }
    },
    watch: {
      'fieldsOptions': {
        deep: true,
        handler: function (val, oldVal) {
          document.querySelector('#fields-options-input').value = JSON.stringify(this.fieldsOptions)
          if(document.querySelector('#fields-options-input-2')) {
            document.querySelector('#fields-options-input-2').value = JSON.stringify(this.fieldsOptions)
          }
          /* console.log('watch', document.querySelector('#fields-options-input').value)
          if(document.querySelector('#fields-options-input-2')) {
            console.log('watch', document.querySelector('#fields-options-input-2').value)
          } */
        }
      }
    }
  });
  App.$mount('#report')
</script>
<?php
$this->registerJs("
// to update tables per ajax without refreshing the page
$(document).on('click','.selected-models-pagination a',function(e){
    e.preventDefault();
    var BatchEditForm = {}
    var url=$(this).attr('href');
    var loader = $('<div class=selected-loader></div>');
    loader.insertBefore('.selected-models-pagination')
    if($step > 1) {
        var loader2 = $('<div class=selected-loader-2></div>');
        loader2.insertBefore('.selected-models-pagination-2')
        var formData = $('#batch-edit-form0').serializeArray();
        var csrf = null
        for(let item of formData) {
            if(item.name !== '_csrf') {
                var matches = item.name.match(/\[(.*?)\]/);
                if (matches) {
                    var name = matches[1];
                    if(name === 'modelIdsToEdit') {
                        if(BatchEditForm.hasOwnProperty(name)){
                            BatchEditForm[name].push(item.value)
                        }
                        else {
                            BatchEditForm[name] = [item.value]
                        }
                    } else {
                        Object.assign(BatchEditForm, {[name]: item.value})
                    } 
                }   
            }
            
            if(item.name === '_csrf') {
                csrf = item.value
            }
        }
    }
    
    $.post(url, {'csrf': csrf, BatchEditForm}, function(data){
        $('.selected-loader').remove()
        $('.sample-table').replaceWith($(data).find('.sample-table'));
        $('.selected-models-pagination').replaceWith($(data).find('.selected-models-pagination'));
        if($step > 1){
            $('.selected-loader-2').remove()
            $('.preview-sample-table').replaceWith($(data).find('.preview-sample-table'));
            $('.selected-models-pagination-2').replaceWith($(data).find('.selected-models-pagination-2'));
        }
        $([document.documentElement, document.body]).animate({
            scrollTop: $('.sample-table').offset().top
        }, 1000);
    });
});
$(document).on('click','.selected-models-pagination-2 a',function(e){
    e.preventDefault();
    var BatchEditForm = {}
    var url=$(this).attr('href');
    var loader = $('<div class=selected-loader></div>');
    loader.insertBefore('.selected-models-pagination')
    var loader2 = $('<div class=selected-loader-2></div>');
    loader2.insertBefore('.selected-models-pagination-2')
    var formData = $('#batch-edit-form0').serializeArray();
    var csrf = null
    for(let item of formData) {
        if(item.name !== '_csrf') {
            var matches = item.name.match(/\[(.*?)\]/);
            if (matches) {
                var name = matches[1];
                if(name === 'modelIdsToEdit') {
                    if(BatchEditForm.hasOwnProperty(name)){
                        BatchEditForm[name].push(item.value)
                    }
                    else {
                        BatchEditForm[name] = [item.value]
                    }
                } else {
                    Object.assign(BatchEditForm, {[name]: item.value})
                } 
            }   
        }
        
        if(item.name === '_csrf') {
            csrf = item.value
        }
    }
    
    $.post(url, {'csrf': csrf, BatchEditForm}, function(data){
        $('.selected-loader').remove()
        $('.sample-table').replaceWith($(data).find('.sample-table'));
        $('.selected-models-pagination').replaceWith($(data).find('.selected-models-pagination'));
        $('.selected-loader-2').remove()
        $('.preview-sample-table').replaceWith($(data).find('.preview-sample-table'));
        $('.selected-models-pagination-2').replaceWith($(data).find('.selected-models-pagination-2'));
        $([document.documentElement, document.body]).animate({
            scrollTop: $('.preview-sample-table').offset().top
        }, 1000);
    });
});
", \yii\web\View::POS_END);
?>

