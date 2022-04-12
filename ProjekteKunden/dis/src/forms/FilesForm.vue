<template>
    <v-container fluid>
        <v-flex align-start>
            <h2>Files</h2>
            <dis-form ref="disForm" formName="files" dataModel="ArchiveFile" :fields="simpleFields" :requiredFilters="requiredFilters" :filterDataModels="filterDataModels" :calculatedFields="calculatedFields">
                <template v-slot:form-fields="{fields, hasNumberValidator, getInputComponent, formScenario, selectedItem, formModel, serverValidationErrors}">
                    <v-layout wrap>
                        <v-flex lg9 md6 sm12 pr-2 pl-2>
                            <v-layout wrap mb-3>
                                <v-flex v-if="!'-group1'.startsWith('-')" xs12 pl-2 pt-2 class="title">
                                    -group1
                                </v-flex>
                                <v-flex lg2 md3 sm6 xs12 pr-2 pl-2>
                                    <DisTextInput
                                        :class="{'c-dis-form__input': true, 'c-dis-form__input--modified': formScenario === 'edit' && selectedItem['parent_combined_id'] !== formModel['parent_combined_id']}"
                                        :disabled="true"
                                        :validators="validators['parent_combined_id']"
                                        name="parent_combined_id"
                                        label="Parent ID"
                                        :serverValidationErrors="serverValidationErrors"
                                        hint="CombinedKey of parent: expedition, site, hole, core, section"
                                        v-model="formModel['parent_combined_id']"
                                        :readonly="formScenario === 'view'"/>
                                </v-flex>
                                <v-flex lg2 md3 sm6 xs12 pr-2 pl-2>
                                    <DisTextInput
                                        :class="{'c-dis-form__input': true, 'c-dis-form__input--modified': formScenario === 'edit' && selectedItem['type'] !== formModel['type']}"
                                        :disabled="true"
                                        :validators="validators['type']"
                                        name="type"
                                        label="File Type"
                                        :serverValidationErrors="serverValidationErrors"
                                        hint="File type"
                                        v-model="formModel['type']"
                                        :readonly="formScenario === 'view'"/>
                                </v-flex>
                                <v-flex lg2 md3 sm6 xs12 pr-2 pl-2>
                                    <DisTextInput
                                        :class="{'c-dis-form__input': true, 'c-dis-form__input--modified': formScenario === 'edit' && selectedItem['filename'] !== formModel['filename']}"
                                        :disabled="true"
                                        :validators="validators['filename']"
                                        name="filename"
                                        label="File Name"
                                        :serverValidationErrors="serverValidationErrors"
                                        hint="Name of the renamed file"
                                        v-model="formModel['filename']"
                                        :readonly="formScenario === 'view'"/>
                                </v-flex>
                                <v-flex lg2 md3 sm6 xs12 pr-2 pl-2>
                                    <DisTextInput
                                        :class="{'c-dis-form__input': true, 'c-dis-form__input--modified': formScenario === 'edit' && selectedItem['original_filename'] !== formModel['original_filename']}"
                                        :disabled="true"
                                        :validators="validators['original_filename']"
                                        name="original_filename"
                                        label="Original File Name"
                                        :serverValidationErrors="serverValidationErrors"
                                        hint="Name of the originally uploaded file"
                                        v-model="formModel['original_filename']"
                                        :readonly="formScenario === 'view'"/>
                                </v-flex>
                                <v-flex lg2 md3 sm6 xs12 pr-2 pl-2>
                                    <DisDateTimeInput
                                        :class="{'c-dis-form__input': true, 'c-dis-form__input--modified': formScenario === 'edit' && selectedItem['upload_date'] !== formModel['upload_date']}"
                                        :disabled="false"
                                        :validators="validators['upload_date']"
                                        name="upload_date"
                                        label="Upload Date"
                                        :serverValidationErrors="serverValidationErrors"
                                        hint="Date of file upload"
                                        v-model="formModel['upload_date']"
                                        :readonly="formScenario === 'view'"/>
                                </v-flex>
                                <v-flex lg2 md3 sm6 xs12 pr-2 pl-2>
                                    <DisTextInput
                                        :class="{'c-dis-form__input': true, 'c-dis-form__input--modified': formScenario === 'edit' && selectedItem['filesize'] !== formModel['filesize']}"
                                        :disabled="false"
                                        :validators="validators['filesize']"
                                        name="filesize"
                                        label="File Size"
                                        :serverValidationErrors="serverValidationErrors"
                                        hint="File size"
                                        v-model.number="formModel['filesize']"
                                        :readonly="formScenario === 'view'"/>
                                </v-flex>
                                <v-flex lg2 md3 sm6 xs12 pr-2 pl-2>
                                    <DisTextInput
                                        :class="{'c-dis-form__input': true, 'c-dis-form__input--modified': formScenario === 'edit' && selectedItem['comment'] !== formModel['comment']}"
                                        :disabled="false"
                                        :validators="validators['comment']"
                                        name="comment"
                                        label="Additional Information"
                                        :serverValidationErrors="serverValidationErrors"
                                        hint="Additional information on the file"
                                        v-model="formModel['comment']"
                                        :readonly="formScenario === 'view'"/>
                                </v-flex>
                                <v-flex lg2 md3 sm6 xs12 pr-2 pl-2>
                                    <DisSelectInput
                                        :class="{'c-dis-form__input': true, 'c-dis-form__input--modified': formScenario === 'edit' && selectedItem['curator_contact_person_id'] !== formModel['curator_contact_person_id']}"
                                        :disabled="false"
                                        :validators="validators['curator_contact_person_id']"
                                        name="curator_contact_person_id"
                                        label="Curator"
                                        :serverValidationErrors="serverValidationErrors"
                                        :selectSource="selectSources['curator_contact_person_id']"
                                        hint="The analyst/curator who created the data record"
                                        v-model="formModel['curator_contact_person_id']"
                                        :readonly="formScenario === 'view'"/>
                                </v-flex>
                            </v-layout>
                        </v-flex>
                        <v-flex lg3 md6 sm12 pr-2 pl-2>
                            <p v-if="selectedItem">
                              Original: <a :href="baseUrl + 'files/original/' + selectedItem.id" target="_blank">{{selectedItem.filename}}</a>
                              <a v-if="selectedItem.mime_type.startsWith('image/')" :href="baseUrl + 'files/' + selectedItem.id" target="_blank"><v-img :src="baseUrl + `files/${selectedItem.id}`" /></a>
                            </p>
                        </v-flex>
                    </v-layout>
                </template>
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
  name: 'FilesForm',
  data () {
    return {
      isUnassigning: false,
      baseUrl: window.baseUrl
    }
  },
  created () {
    this.fileService = new FileService()
    this.validators = {}
    this.validators['parent_combined_id'] = JSON.parse('[{"type":"string","min":null,"max":null}]')
    this.validators['type'] = JSON.parse('[{"type":"required"},{"type":"string","min":null,"max":null}]')
    this.validators['filename'] = JSON.parse('[{"type":"required"},{"type":"string","min":null,"max":null}]')
    this.validators['original_filename'] = JSON.parse('[{"type":"required"},{"type":"string","min":null,"max":null}]')
    this.validators['upload_date'] = JSON.parse('[{"type":"required"}]')
    this.validators['filesize'] = JSON.parse('[{"type":"number"}]')
    this.validators['comment'] = JSON.parse('[]')
    this.validators['curator_contact_person_id'] = JSON.parse('[]')
    this.selectSources = {}
    this.selectSources['curator_contact_person_id'] = JSON.parse('{"type":"one_relation","listName":"","textField":"person_acronym","valueField":"id","model":"ContactPerson"}')
    this.calculatedFields = {}
    this.simpleFields = JSON.parse('[{"name":"parent_combined_id","label":"Parent ID","description":"CombinedKey of parent: expedition, site, hole, core, section","group":"-group1","order":0,"inputType":"text","searchable":true},{"name":"type","label":"File Type","description":"File type","group":"-group1","order":1,"inputType":"text","searchable":true},{"name":"filename","label":"File Name","description":"Name of the renamed file","group":"-group1","order":2,"inputType":"text","searchable":true},{"name":"original_filename","label":"Original File Name","description":"Name of the originally uploaded file","group":"-group1","order":3,"inputType":"text","searchable":true},{"name":"upload_date","label":"Upload Date","description":"Date of file upload","group":"-group1","order":4,"inputType":"datetime","searchable":true},{"name":"filesize","label":"File Size","description":"File size","group":"-group1","order":5,"inputType":"text","searchable":true},{"name":"comment","label":"Additional Information","description":"Additional information on the file","group":"-group1","order":6,"inputType":"text","searchable":true},{"name":"curator_contact_person_id","label":"Curator","description":"The analyst/curator who created the data record","group":"-group1","order":7,"inputType":"select","searchable":true}]')
    this.requiredFilters = JSON.parse('[{"value":"person","as":"curator_contact_person_id","skipOnEmpty":false}]')
    this.subForms = JSON.parse('[]')
    this.supForms = JSON.parse('[]')
    this.filterDataModels = JSON.parse('{"expedition":{"model":"ProjectExpedition","value":"id","text":"exp_acronym","ref":"expedition_id"},"site":{"model":"ProjectSite","value":"id","text":"site","ref":"site_id","require":{"value":"expedition","as":"expedition_id"}},"hole":{"model":"ProjectHole","value":"id","text":"hole","ref":"hole_id","require":{"value":"site","as":"site_id"}},"core":{"model":"CoreCore","value":"id","text":"core","ref":"core_id","require":{"value":"hole","as":"hole_id"}},"section":{"model":"CoreSection","value":"id","text":"section","ref":"section_id","require":{"value":"core","as":"core_id"}},"sectionSplit":{"model":"CurationSectionSplit","value":"id","text":"type","ref":"section_split_id","require":{"value":"section","as":"section_id"}},"sample":{"model":"CurationSample","value":"id","text":"id","ref":"sample_id","require":{"value":"sectionSplit","as":"section_split_id"}},"sampleRequest":{"model":"CurationSampleRequest","value":"id","text":"id","ref":"sample_request_id","require":{"value":"expedition","as":"expedition_id"}},"person":{"model":"ContactPerson","value":"id","text":"person_acronym","ref":"curator_contact_person_id"}}')
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
