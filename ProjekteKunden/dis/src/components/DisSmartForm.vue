<template>
    <v-container fluid pt-0>
        <dis-form v-if="ready"
        :formName="formName"
        :dataModel="dataModel"
        :fields="formTemplate.fields"
        :requiredFilters="requiredFilters"
        :filterDataModels="Array.isArray(filterDataModels) ? {} : filterDataModels"
        :calculatedFields="calculatedFields">
        <template v-slot:form-fields="{fields, formScenario, selectedItem, formModel, serverValidationErrors, compactUI}">
          <v-layout wrap :class="{'c-dis-form__layout': true, 'compact': compactUI}" v-for="(group, index) in Object.keys(fieldsGroups)" :key="index">
            <v-flex v-if="!group.startsWith('-') && !compactUI" xs12 pl-2 pt-2 class="title">
                {{ group }}
            </v-flex>
            <v-flex
              v-for="field in fieldsGroups[group]"
              :key="field.name"
              :class="{'c-dis-form__layout-item': true, 'compact': compactUI}"
              md3 lg2 sm6 xs12>
              <component
                v-if="getInputComponent(field.formInput.type) === 'DisSelectInput'"
                :class="{'c-dis-form__input': true, 'c-dis-form__input--modified': formScenario === 'edit' && selectedItem[field.name] !== formModel[field.name]}"
                :disabled="!!field.formInput.calculate || field.formInput.disabled || formScenario === 'view'"
                :validators="field.validators"
                :is="getInputComponent(field.formInput.type)"
                :name="field.name"
                :label="field.label"
                :serverValidationErrors="serverValidationErrors"
                :selectSource="selectSources[field.name]"
                :formModel="formModel"
                :allowFreeInput="field.formInput.allowFreeInput"
                :multiple="field.formInput.multiple || false"
                :hint="field.description"
                v-model="formModel[field.name]"
                :readonly="field.readOnly || formScenario === 'view'"/>
              <component
                v-else-if="field.validators.findIndex(element => element.type === 'number') > -1"
                :class="{'c-dis-form__input': true, 'c-dis-form__input--modified': formScenario === 'edit' && selectedItem[field.name] !== formModel[field.name]}"
                :disabled="!!field.formInput.calculate || field.formInput.disabled"
                :validators="field.validators"
                :is="getInputComponent(field.formInput.type)"
                :name="field.name"
                :label="field.label"
                :serverValidationErrors="serverValidationErrors"
                :hint="field.description"
                v-model.number="formModel[field.name]"
                :readonly="field.readOnly || formScenario === 'view'"/>
              <component
                v-else
                :class="{'c-dis-form__input': true, 'c-dis-form__input--modified': formScenario === 'edit' && selectedItem[field.name] !== formModel[field.name]}"
                :disabled="!!field.formInput.calculate || field.formInput.disabled"
                :validators="field.validators"
                :is="getInputComponent(field.formInput.type)"
                :name="field.name"
                :label="field.label"
                :serverValidationErrors="serverValidationErrors"
                :hint="field.description"
                v-model="formModel[field.name]"
                :readonly="field.readOnly || formScenario === 'view'"/>
            </v-flex>
          </v-layout>

        </template>
        <template v-slot:extra-form-actions="{ selectedItem, onSubFormClick, onSupFormClick }">
            <v-btn
                v-for="subForm in subFormsWithPermission"
                :key="subForm"
                round
                class="c-dis-form__btn-sub-form"
                @click="onSubFormClick(subForm, formTemplate.subForms[subForm])"
                color="indigo darken-4"
                dark
                :disabled="!selectedItem">
                {{ formTemplate.subForms[subForm].buttonLabel }} <v-icon>arrow_downward</v-icon>
            </v-btn>
            <v-btn
                v-for="supForm in supFormsWithPermission"
                :key="supForm"
                round
                class="c-dis-form__btn-sup-form"
                @click="onSupFormClick(supForm, formTemplate.supForms[supForm])"
                color="indigo darken-1"
                dark
                :disabled="!selectedItem">
                {{ formTemplate.supForms[supForm].buttonLabel }} <v-icon>arrow_upward</v-icon>
            </v-btn>
        </template>
    </dis-form>
    </v-container>
</template>

<script>
/* eslint no-eval: 0 */
export default {
  name: 'DisSmartForm',
  data () {
    return {
      ready: false
    }
  },
  watch: {
    '$route.params.formName': {
      immediate: true,
      handler: async function (val) {
        this.ready = false
        await this.$nextTick()
        try {
          await this.$store.dispatch('templates/getFormTemplate', this.$route.params.formName)
          await this.$nextTick()
          this.ready = true
        } catch (e) {
          this.$dialog.notify.warning(e.message)
        }
      }
    }
  },
  computed: {
    formTemplate () {
      return this.$store.state.templates.forms.find(item => item.name === this.$route.params.formName)
    },
    subFormsWithPermission () {
      let subForms = []
      let appForms = this.$store.state.appForms
      for (const aF in appForms) {
        subForms = [...subForms, ...appForms[aF].map(item => item['key'])] // all Forms that can be visible
      }
      return subForms.filter(item => Object.keys(this.formTemplate.subForms).includes(item))
    },
    supFormsWithPermission () {
      let supForms = []
      let appForms = this.$store.state.appForms
      for (const aF in appForms) {
        supForms = [...supForms, ...appForms[aF].map(item => item['key'])] // all Forms that can be visible
      }
      return supForms.filter(item => Object.keys(this.formTemplate.supForms).includes(item))
    },
    formName () {
      return this.formTemplate.name
    },
    dataModel () {
      return this.formTemplate.dataModel
    },
    selectSources () {
      return this.formTemplate.fields.reduce((acc, cur, i) => {
        if (cur.formInput.type === 'select') {
          acc[cur.name] = cur.formInput.selectSource
        }
        return acc
      }, {})
    },
    requiredFilters () {
      return this.formTemplate.requiredFilters
    },
    filterDataModels () {
      return this.formTemplate.filterDataModels
    },
    calculatedFields () {
      let calculated = {}
      this.formTemplate.fields.filter(item => item.formInput.jsCalculate !== '').map(item => {
        calculated[item.name] = function () {
          return eval(item.formInput.jsCalculate)
        }
      })
      return calculated
    },
    fieldsGroups () {
      let fieldGroups = this.formTemplate.fields.reduce((acc, cur, i) => {
        if (!acc[cur.group]) {
          acc[cur.group] = []
        }
        acc[cur.group].push(cur)
        return acc
      }, {})
      let allInOneArr = []
      Object.values(fieldGroups).forEach(arr => {
        allInOneArr = [...allInOneArr, ...arr]
      })
      return this.$store.state.compactUI ? { 'allInOneGroup': allInOneArr } : fieldGroups
    }
  },
  methods: {
    getInputComponent (type) {
      switch (type) {
        case 'text':
          return 'DisTextInput'
        case 'autoIncrement':
          return 'DisAutoIncrementInput'
        case 'select':
          return 'DisSelectInput'
        case 'switch':
          return 'DisSwitchInput'
        case 'datetime':
          return 'DisDateTimeInput'
        case 'date':
          return 'DisDateInput'
        case 'time':
          return 'DisTimeInput'
        case 'textarea':
          return 'DisTextareaInput'
        default:
          throw new Error(`Dis: Unsupported input type '${type}'`)
      }
    }
  }
}
</script>

<style>

</style>
