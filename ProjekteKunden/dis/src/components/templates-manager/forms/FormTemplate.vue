<template>
  <v-container fluid grid-list-md :class="Object.assign({'c-form-template': true}, themeClasses)">
    <v-progress-linear v-if="loading" indeterminate />
    <v-layout>
      <v-flex sm12>
        <v-card>
          <v-form ref="form" lazy-validation>
            <v-card-text>
              <v-text-field ref="formNameInput" @input="fixFormNameCase" label="Form Name" :disabled="scenario === 'update'" :rules="[v => !!v || `Name is required`]" hint="should be unique kebab-case string" v-model="exportedTemplate.name" />
              <v-text-field label="Data Model" :disabled="true" hint="Against which data model should this be generated" v-model="exportedTemplate.dataModel" />
              <v-layout row>
                <v-flex md3 :class="Object.assign({'c-form-template__left-col': true}, themeClasses)">
                  <div :class="Object.assign({'c-form-template__left-col-head': true}, themeClasses)">
                    <h3>Fields</h3>
                  </div>
                  <div :class="Object.assign({ 'c-form-template__left-col-content': true }, themeClasses)">
                    <draggable :force-fallback="true" v-model="fields" group="fields" :sort="false" ghostClass="u-drag-ghost" tag="ul" :class="Object.assign({ 'c-form-template__fields-to-select-bag': true, 'js-fields-to-select': true}, themeClasses)">
                      <!--<transition-group tag="">-->
                      <li v-for="field in fields" :key="field.name">
                        <field-template
                            :isDbRequired="dbRequiredColumns.includes(field.name)"
                            v-model="fieldsTemplates[field.name]"
                            :dataModelFullName="exportedTemplate.dataModel"
                            :dataModelColumnName="field.name"
                                                        :enabled="false"
                                                        :newField="true"
                                                />
                      </li>
                      <!--</transition-group>-->
                    </draggable>
                  </div>
                </v-flex>
                <v-flex md9 :class="Object.assign({'c-form-template__selected-fields': true}, themeClasses)">
                  <draggable :force-fallback="true" v-model="selectedFieldsGroups" group="groups" handle=".drag-handle" tag="ul" class="groups-drag-bag">
                    <li v-for="group in selectedFieldsGroups" :key="group.id">
                      <div :class="Object.assign({'c-form-template__group': true}, themeClasses)">
                        <div :class="Object.assign({'c-form-template__group-label': true}, themeClasses)">
                          <v-icon class="drag-handle mr-3">drag_indicator</v-icon>
                          <h2>{{group.label}}</h2>
                          <v-spacer></v-spacer>
                          <v-btn
                              icon
                              title="remove"
                              @click="() => removeFieldsGroup(group)"
                              v-if="group.id !== 0"
                              :disabled="group.fields.length > 0">
                            <v-icon>
                              remove
                            </v-icon>
                          </v-btn>
                          <v-dialog v-model="group.dialog" max-width="600px">
                            <template v-slot:activator="data">
                              <v-btn v-on="data.on" icon @click="group['dialog'] = !group['dialog']">
                                <v-icon>edit</v-icon>
                              </v-btn>
                            </template>
                            <v-card>
                              <v-card-text>
                                <v-text-field v-model="group.label" label="Group Name" hint="if it starts with `-`, it will not be shown in form"></v-text-field>
                              </v-card-text>
                            </v-card>
                          </v-dialog>
                          <v-btn small icon @click="group.expanded = !group.expanded">
                            <v-icon v-if="!group.expanded">arrow_drop_down</v-icon>
                            <v-icon v-if="group.expanded">arrow_drop_up</v-icon>
                          </v-btn>
                        </div>
                        <div :class="Object.assign({'c-form-template__group-fields': true}, themeClasses)" v-show="group.expanded">
                          <v-alert
                              v-if="group.fields.length === 0"
                              v-model="hintGroupFieldEmpty"
                              dismissible
                              transition="scroll-y-transition"
                              color="info"
                              dense
                              style="display: flex"
                          >
                            Add fields to this group by dragging them below this message. Empty field-groups will be deleted on save.
                          </v-alert>
                          <ul>
                            <draggable
                                :force-fallback="true"
                                v-model="group.fields"
                                :group="{ name: 'fields', pull: onSelectedFieldPull }"
                                ghostClass="u-drag-ghost"
                                handle=".field-drag-handle">
                              <li v-for="field in group.fields" :key="field.name" :class="{ 'list-complete-item': true, 'js-is-db-required':  dbRequiredColumns.includes(field.name)}" >
                                <field-template
                                    ref="formFieldTemplate"
                                    :isDbRequired="dbRequiredColumns.includes(field.name)"
                                    v-model="fieldsTemplates[field.name]"
                                    :dataModelFullName="exportedTemplate.dataModel"
                                    :dataModelColumnName="field.name"
                                    :enabled="true"/>
                              </li>
                            </draggable>
                        </ul>
                        </div>
                      </div>
                    </li>
                  </draggable>
                  <v-dialog v-model="addGroupDialog" persistent max-width="600px">
                    <template v-slot:activator="data">
                      <v-btn ref="addGroupButton" v-on="data.on" color="green darken-4">Add a Group</v-btn>
                    </template>
                    <v-card>
                      <v-card-text>
                        <v-text-field ref="newGroupName" v-model="newGroupName" label="Group Name"></v-text-field>
                      </v-card-text>
                      <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="warning"  @click="addGroupDialog = false">Cancel</v-btn>
                        <v-btn color="teal" @click="addFieldsGroup">ADD</v-btn>
                      </v-card-actions>
                    </v-card>
                  </v-dialog>
                  <v-layout row wrap>
                    <v-flex xs12 md6>
                      <v-select :items="availableSupForms" multiple label="Parent Forms Buttons" v-model="selectedSupForms"></v-select>
                    </v-flex>
                    <v-flex xs12 md6>
                      <v-select :items="availableSubForms" multiple label="Child Forms Buttons" v-model="selectedSubForms"></v-select>
                    </v-flex>
                  </v-layout>
                  <v-alert v-model="showErrorSummary" dismissible>
                    <ul>
                      <li v-for="error in serverValidationErrors" :key="error.field">
                        <strong>{{error.field}}</strong>: {{error.message}}
                      </li>
                    </ul>
                  </v-alert>
                </v-flex>
              </v-layout>
            </v-card-text>
          </v-form>
        </v-card>
        <v-card class="mt-2">
          <v-card-text>
            <v-btn @click="() => saveTemplate(false)" color="success" :loading="loading">Save</v-btn>
            <v-btn @click="() => saveTemplate(true)" color="blue" :loading="loading" :disabled="scenario === 'create'">Save & Generate</v-btn>
            <v-alert v-model="showGenerationLog" dismissible color="info">
              <ul>
                <li v-for="(log, index) in generationLog" :key="index">
                  {{log}}
                </li>
              </ul>
            </v-alert>
            <auto-code-generator v-if="generatorAttributes" ref="autoCodeGenerator" v-model="generationDialog" :generatorAttributes="generatorAttributes" generatorId="dis-form"></auto-code-generator>
          </v-card-text>
        </v-card>
      </v-flex>
    </v-layout>
  </v-container>
</template>

<script>
import FieldTemplate from './FieldTemplate'
import draggable from 'vuedraggable'
import AutoCodeGenerator from '../AutoCodeGenerator'
import kebabCase from 'lodash/kebabCase'
import themeable from 'vuetify/lib/mixins/themeable'
import FormLocalStorage from '../../../util/FormLocalStorage'

export default {
  name: 'FormTemplate',
  mixins: [themeable],
  components: { AutoCodeGenerator, FieldTemplate, draggable },
  props: {
    availableFieldsTemplates: {
      type: Object
    },
    scenario: {
      type: String,
      required: true
    },
    initialTemplate: {
      type: Object,
      required: true
    }
  },
  data () {
    return {
      fieldsTemplates: {},
      fields: [],
      selectedFieldsGroups: [],
      exportedTemplate: {
        name: '',
        dataModel: '',
        fields: []
      },
      generatedForms: [],
      loading: false,
      addGroupDialog: false,
      newGroupName: '',
      serverValidationErrors: [],
      selectedSupForms: [],
      selectedSubForms: [],
      generationLog: [],
      generationDialog: false,
      hintGroupFieldEmpty: true
    }
  },
  mounted () {
    this.reset()
    this.exportedTemplate.dataModel = this.initialTemplate.dataModel
    // get model template to ensure it's loaded
    // because columns info will be used to evaluate inputs and validators
    this.$store.dispatch('templates/getModelTemplate', this.exportedTemplate.dataModel)
    this.exportedTemplate.name = this.initialTemplate.name
    this.selectedSupForms = this.initialTemplate.supForms ? Object.keys(this.initialTemplate.supForms) : []
    this.selectedSubForms = this.initialTemplate.subForms ? Object.keys(this.initialTemplate.subForms) : []
    let availableFieldsTemplates = JSON.parse(JSON.stringify(this.availableFieldsTemplates))
    for (let i in this.initialTemplate.fields) {
      const fieldName = this.initialTemplate.fields[i].name
      this.$set(this.fieldsTemplates, fieldName, this.initialTemplate.fields[i])
      let groupName = this.fieldsTemplates[fieldName].group
      let groupIndex = this.selectedFieldsGroups.findIndex(item => item.label === groupName)
      if (groupIndex > -1) {
        this.selectedFieldsGroups[groupIndex].fields.push(this.fieldsTemplates[fieldName])
        this.selectedFieldsGroups[groupIndex].fields.sort((field1, field2) => field1.order - field2.order)
      } else {
        this.selectedFieldsGroups.push({
          id: this.selectedFieldsGroups.length,
          label: groupName,
          expanded: true,
          fields: [
            this.fieldsTemplates[fieldName]
          ]
        })
      }
    }

    for (let fieldName in availableFieldsTemplates) {
      if (!this.fieldsTemplates[fieldName]) {
        this.$set(this.fieldsTemplates, fieldName, availableFieldsTemplates[fieldName])
        this.fields.push(this.fieldsTemplates[fieldName])
      }
    }
  },
  computed: {
    dbRequiredColumns () {
      const dataModel = this.initialTemplate.dataModel
      const modelTemplate = this.$store.state.templates.models.find(item => item.fullName === dataModel)
      return modelTemplate ? Object.keys(this.fieldsTemplates).filter(item =>
        modelTemplate.columns[item] ? modelTemplate.columns[item].required : false) : []
    },
    draggableOptions () {
      return {
        group: 'fields',
        ghostClass: 'u-drag-ghost'
      }
    },
    showErrorSummary: {
      get: function () {
        return this.serverValidationErrors.length > 0
      },
      set: function () {
        this.serverValidationErrors = []
      }
    },
    availableSupForms () {
      let dataModel = this.$store.state.templates.summary.models.find(item => item.fullName === this.initialTemplate.dataModel)
      if (dataModel && dataModel.parentModel) {
        let forms = this.$store.state.templates.summary.forms.filter(item => item.dataModel === dataModel.parentModel)
        if (forms.length > 0) {
          return forms.map(item => item.name)
        }
      }
      return []
    },
    availableSubForms () {
      let dataModels = this.$store.state.templates.summary.models.filter(item => item.parentModel === this.initialTemplate.dataModel)
      if (dataModels.length > 0) {
        dataModels = dataModels.map(item => item.fullName)
        // console.log('dataModels', dataModels)
        let forms = this.$store.state.templates.summary.forms.filter(item => dataModels.includes(item.dataModel))
        if (forms.length > 0) {
          return forms.map(item => item.name)
        }
      }
      return []
    },
    showGenerationLog: {
      get: function () {
        return this.generationLog.length > 0
      },
      set: function () {
        this.generationLog = []
      }
    },
    generatorAttributes () {
      return { templateName: this.exportedTemplate.name }
    }
  },
  created () {
    this.$store.dispatch('listValues/refreshListNames')
  },
  methods: {
    fixFormNameCase () {
      this.exportedTemplate.name = kebabCase(this.exportedTemplate.name)
    },
    reset () {
      this.fields = []
      this.selectedFieldsGroups = []
      this.exportedTemplate.name = ''
      this.exportedTemplate.dataModel = ''
      this.exportedTemplate.fields = []
    },
    removeFieldsGroup (group) {
      if (group.fields.length === 0) {
        let index = this.selectedFieldsGroups.indexOf(group)
        if (index > -1) {
          this.selectedFieldsGroups.splice(index, 1)
        }
      }
    },
    removeFieldsEmptyGroup () {
      for (const grp of this.selectedFieldsGroups) {
        this.removeFieldsGroup(grp)
      }
    },
    addFieldsGroup () {
      if (this.newGroupName && this.newGroupName.length > 0) {
        this.selectedFieldsGroups.push({
          id: this.selectedFieldsGroups.length,
          label: this.newGroupName,
          expanded: true,
          fields: []
        })
      }
      this.newGroupName = null
      this.addGroupDialog = false
    },
    onSelectedFieldPull (to, from, element) {
      if (element && to && element.classList.contains('js-is-db-required') && to.el.classList.contains('js-fields-to-select')) {
        return false
      }
      return true
    },
    saveTemplate (generate = false) {
      this.serverValidationErrors = []
      this.loading = true
      this.exportedTemplate.fields = []
      this.selectedFieldsGroups.map(group => {
        group.fields.map((field, order) => {
          this.exportedTemplate.fields.push({
            ...field,
            group: group.label,
            order: order
          })
        })
      })
      if (this.$refs.form.validate()) {
        if (!generate) {
          this.save()
        } else {
          this.saveAndGenerate()
        }
      } else {
        this.loading = false
        this.$refs.form.inputs.forEach(e => {
          if (e.hasError) {
            this.serverValidationErrors.push({
              field: e.label,
              message: e.errorBucket[0]
            })
          }
        })
      }
    },
    async save () {
      try {
        this.loading = true
        if (this.scenario === 'create') {
          const result = await this.$store.dispatch('templates/createFormTemplate', { template: this.exportedTemplate, subForms: this.selectedSubForms, supForms: this.selectedSupForms })
          this.$dialog.message.success('created successfully', {
            position: 'bottom'
          })
          this.$router.push(`/settings/templates-manager/forms/${this.exportedTemplate.dataModel}/update/${result.name}`)
        } else if (this.scenario === 'update') {
          await this.$store.dispatch('templates/updateFormTemplate', { template: this.exportedTemplate, subForms: this.selectedSubForms, supForms: this.selectedSupForms })
          this.$dialog.message.success('saved successfully', {
            position: 'bottom'
          })
        }
        this.loading = false
        return true
      } catch (e) {
        if (e.response && e.response.status === 422) {
          this.serverValidationErrors = e.response.data
        } else {
          this.$dialog.notify.warning(e.message)
        }
      } finally {
        this.loading = false
        this.removeFieldsEmptyGroup()
      }
    },
    async saveAndGenerate () {
      this.generationLog.push(`Step 1/2: saving the template`)
      const saved = await this.save()
      if (saved) {
        this.loading = true
        try {
          this.generationLog.push(`template was saved successfully`)
          this.generationLog.push(`Step 2/2: generating form classes and component`)
          this.generationDialog = true
          await this.$nextTick()
          const result = await this.$refs.autoCodeGenerator.generate()
          if (result === 'generated') {
            this.generationLog.push(`classes and component were generated successfully`)
          } else {
            this.generationLog.push(`some files will be overwritten. choose how you want to continue.`)
          }
          const formLocalStorage = new FormLocalStorage(this.exportedTemplate.name)
          formLocalStorage.clear()
          await this.$store.dispatch('refreshUser', null, { root: true })
          await this.$store.dispatch('getForms')
        } finally {
          this.loading = false
        }
      }
    }
  },
  watch: {
    hintGroupFieldEmpty () {
      this.hintGroupFieldEmpty = true
    }
  }
}
</script>
