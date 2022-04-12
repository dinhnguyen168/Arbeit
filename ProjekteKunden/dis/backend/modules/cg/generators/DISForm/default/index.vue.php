<?php
use yii\helpers\Inflector;
use yii\helpers\Json;
?>
<template>
    <v-container fluid>
        <v-flex align-start>
            <h2><?= Inflector::titleize($name)?></h2>
            <dis-form ref="disForm" formName="<?= $name?>" dataModel="<?= $dataModel?>" :fields="simpleFields" :requiredFilters="requiredFilters" :filterDataModels="filterDataModels" :calculatedFields="calculatedFields">
                <template v-slot:form-fields="{fields, hasNumberValidator, getInputComponent, formScenario, selectedItem, formModel, serverValidationErrors}">
<?php foreach ($fieldsGroups as $groupName => $fieldsGroup): ?>
                        <v-layout wrap mb-3>
                            <v-flex v-if="!'<?= $groupName?>'.startsWith('-')" xs12 pl-2 pt-2 class="title">
                                <?= $groupName . "\n"?>
                            </v-flex>
<?php foreach ($fieldsGroup as $field): ?>
                            <v-flex lg2 md3 sm6 xs12 pr-2 pl-2>
                                <<?= $field['componentName'] . "\n" ?>
                                        :class="{'c-dis-form__input': true, 'c-dis-form__input--modified': formScenario === 'edit' && selectedItem['<?= $field['name'] ?>'] !== formModel['<?= $field['name'] ?>']}"
                                        :disabled="<?= ((isset($field['formInput']['calculate']) && !empty($field['formInput']['calculate'])) || (isset($field['formInput']['disabled']) && $field['formInput']['disabled'] == true)) ? 'true' : 'false' ?>"
                                        :validators="validators['<?= $field['name'] ?>']"
                                        name="<?= $field['name'] ?>"
                                        label="<?= addslashes($field['label']) ?>"
                                        :serverValidationErrors="serverValidationErrors"
<?= ($field['formInput']['type'] == 'select') ? '                                        :selectSource="selectSources[\''. $field['name'] .'\']"' . "\n" : '' ?>
<?= ($field['formInput']['type'] == 'select' && isset($field['formInput']['allowFreeInput']) && $field['formInput']['allowFreeInput'] == true) ? '                                        :allowFreeInput="true"' . "\n" : ''?>
<?= (!empty($field['description']))  ? '                                        hint="' . addslashes($field['description']) . '"' . "\n" : ''?>
<?= ($field['formInput']['type'] == 'select' && isset($field['formInput']['multiple']) && $field['formInput']['multiple'] == true) ? '                                        multiple="true"' . "\n" : '' ?>
                                        <?= $field['isNumeric'] ? 'v-model.number="formModel[\''. $field['name'] .'\']"' . "\n" : 'v-model="formModel[\''. $field['name'] .'\']"' . "\n"?>
                                        <?= (isset($field['readOnly']) && $field['readOnly'] == true)  ? ':readonly="true"' : ':readonly="formScenario === \'view\'"'?>/>
                            </v-flex>
<?php endforeach; ?>
                        </v-layout>
<?php endforeach; ?>
                </template>
<?php if (count($subForms) > 0 || count($supForms) > 0): ?>
                <template v-slot:extra-form-actions="{ selectedItem<?= (count($subForms) > 0 ) ? ", onSubFormClick" : "" ?><?= (count($supForms) > 0 ) ? ", onSupFormClick" : "" ?> }">
<?php foreach ($subForms as $key => $subForm): ?>
                    <v-btn round class="c-dis-form__btn-sub-form" @click="onSubFormClick('<?= $key ?>', subForms['<?= $key ?>'])" color="indigo darken-4" dark :disabled="!selectedItem">
                        <?= $subForm['buttonLabel'] ?> <v-icon>arrow_downward</v-icon>
                    </v-btn>
<?php endforeach; ?>
<?php foreach ($supForms as $key => $supForm): ?>
                    <v-btn round class="c-dis-form__btn-sup-form" @click="onSupFormClick('<?= $key ?>', supForms['<?= $key ?>'])" color="indigo darken-1" dark :disabled="!selectedItem">
                        <?= $supForm['buttonLabel'] ?> <v-icon>arrow_upward</v-icon>
                    </v-btn>
<?php endforeach; ?>
                </template>
<?php endif; ?>
            </dis-form>
        </v-flex>
    </v-container>
</template>

<script>
export default {
  name: '<?= Inflector::id2camel($name)?>Form',
  created () {
    this.validators = {}
<?php foreach ($validators as $fieldName => $validator): ?>
    this.validators['<?= $fieldName ?>'] = JSON.parse('<?= Json::encode($validator, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>')
<?php endforeach; ?>
    this.selectSources = {}
<?php foreach ($selectInputSources as $fieldName => $selectInputSource): ?>
    this.selectSources['<?= $fieldName ?>'] = JSON.parse('<?= Json::encode($selectInputSource, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>')
<?php endforeach; ?>
    this.calculatedFields = {}
<?php foreach ($calculatedFields as $fieldName => $calculatedField): ?>
    this.calculatedFields['<?= $fieldName ?>'] = function () {
      return <?=$calculatedField . "\n" ?>
    }
<?php endforeach; ?>
    this.simpleFields = JSON.parse('<?= Json::encode($simpleFields, JSON_UNESCAPED_UNICODE) ?>')
    this.requiredFilters = JSON.parse('<?= Json::encode($requiredFilters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>')
    this.subForms = JSON.parse('<?= Json::encode($subForms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>')
    this.supForms = JSON.parse('<?= Json::encode($supForms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>')
    this.filterDataModels = JSON.parse('<?= Json::encode($filterDataModels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT) ?>')
  }
}
</script>

<style scoped>

</style>
