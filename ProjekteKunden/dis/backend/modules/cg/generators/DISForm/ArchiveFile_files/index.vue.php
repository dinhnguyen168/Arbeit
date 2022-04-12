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
                    <v-layout wrap>
                        <v-flex lg9 md6 sm12 pr-2 pl-2>
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
<?= ($field['formInput']['type'] == 'select' && isset($field['formInput']['allowFreeInput']) && $field['formInput']['allowFreeInput'] == true) ? '                                            :allowFreeInput="true"' . "\n" : ''?>
<?= (!empty($field['description']))  ? '                                        hint="' . addslashes($field['description']) . '"' . "\n" : ''?>
<?= ($field['formInput']['type'] == 'select' && isset($field['formInput']['multiple']) && $field['formInput']['multiple'] == true) ? '                                        multiple="true"' . "\n" : '' ?>
                                        <?= $field['isNumeric'] ? 'v-model.number="formModel[\''. $field['name'] .'\']"' . "\n" : 'v-model="formModel[\''. $field['name'] .'\']"' . "\n"?>
                                        <?= (isset($field['readOnly']) && $field['readOnly'] == true)  ? ':readonly="true"' : ':readonly="formScenario === \'view\'"'?>/>
                                </v-flex>
<?php endforeach; ?>
                            </v-layout>
                        </v-flex>
                        <v-flex lg3 md6 sm12 pr-2 pl-2>
                            <p v-if="selectedItem">
                              Original: <a :href="baseUrl + 'files/original/' + selectedItem.id" target="_blank">{{selectedItem.filename}}</a>
                              <a v-if="selectedItem.mime_type.startsWith('image/')" :href="baseUrl + 'files/' + selectedItem.id" target="_blank"><v-img :src="baseUrl + `files/${selectedItem.id}`" /></a>
                            </p>
                        </v-flex>
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
                <template v-slot:form-actions="{ onEditClick, onCreateNewClick, onDeleteClick, onDuplicateClick, isEditButtonDisabled, userCanEditForm, isNewButtonDisabled, isFetchingDefaults, isFetchingDuplicate, isDeleteButtonDisabled, shortcuts }">
                  <!-- edit button -->
                  <v-btn @click="onEditClick" v-mousetrap="{keys: shortcuts.edit, disabled: isEditButtonDisabled || !userCanEditForm}" @mousetrap.prevent="onEditClick"  class="c-dis-form__btn-edit" :disabled="isEditButtonDisabled || !userCanEditForm" title="Edit">
                    <v-icon>edit</v-icon> Edit
                  </v-btn>
                  <v-btn @click="onUnassignClick" class="c-dis-form__btn-duplicate" title="Un-assign" :loading="isUnassigning" :disabled="isEditButtonDisabled">
                    <v-icon>link_off</v-icon> Un-assign
                  </v-btn>
                  <!-- delete button -->
                  <v-btn @click="onDeleteClick" v-mousetrap="{keys: shortcuts.delete, disabled: isDeleteButtonDisabled || !userCanEditForm}" @mousetrap.prevent="onDeleteClick"  class="c-dis-form__btn-delete" :disabled="isDeleteButtonDisabled || !userCanEditForm" title="Delete">
                    <v-icon>delete</v-icon> Delete
                  </v-btn>
                </template>
            </dis-form>
        </v-flex>
    </v-container>
</template>

<script>
import FileService from '../services/FileService'

export default {
  name: '<?= Inflector::id2camel($name)?>Form',
  data () {
    return {
      isUnassigning: false,
      baseUrl: window.baseUrl
    }
  },
  created () {
    this.fileService = new FileService()
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
  },
  methods: {
    async onUnassignClick () {
      let confirmed = await this.$dialog.confirm({
        title: 'Unassign File',
        text: 'This will remove the linkage of this file to this record, and move the file back to the "Files Upload" area.'
      })
      if (confirmed) {
        this.isUnassigning = true
        try {
          let response = await this.fileService.unassign(this.$refs.disForm.formModel.id)
          if (response.status === 200) {
            this.$refs.disForm.clearForm()
            this.$refs.disForm.formScenario = 'view'
            this.$refs.disForm.selectedItem = null
            this.$dialog.message.success('un-assigned successfully')
            await this.$refs.disForm.$refs.dataTable.refreshItems(false)
          }
        } catch (error) {
          this.$dialog.notify.warning('cannot un-assign - ' + error.message, { timeout: 30000 })
          console.log(error)
        } finally {
          this.isUnassigning = false
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
