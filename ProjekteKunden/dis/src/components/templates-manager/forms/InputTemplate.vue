<template>
  <div class="c-form-inputs-input">
    <div>
      <v-select
              label="Input Type"
              v-model="formInput.type"
              :items="formInputTypeList"
              :disabled="columnDataType === 'many_to_many' || columnDataType === 'one_to_many'"
      >
      </v-select>
      <v-checkbox
          :disabled="isPseudoColumn"
          label="Disabled"
          v-model="formInput.disabled"/>
      <v-text-field label="Calculate" :disabled="isPseudoColumn" v-model="formInput.calculate" hint="Calculate a value, use 'this.' to access column values."/>
    </div>
    <div class="c-form-inputs-input__select-options" v-if="formInput.type === 'select'">
      <!-- <v-text-field label="List Name" v-model="formInput.selectInputOptions.selectSource.listName"/> -->
      <v-radio-group label="List Source" v-model="formInput.selectInputOptions.selectSource.type" @change="onListSourceChange" :disabled="columnDataType === 'many_to_many' || columnDataType === 'one_to_many'">
        <v-radio label="Value list" value="list"></v-radio>
        <v-radio label="Linked data model" value="api"></v-radio>
        <v-radio label="Related data model" v-if="columnDataType === 'many_to_many'" value="many_relation"></v-radio>
        <v-radio label="Related data model" v-if="columnDataType === 'one_to_many'" value="one_relation"></v-radio>
      </v-radio-group>
      <v-combobox
          v-if="formInput.selectInputOptions.selectSource.type === 'list'"
          label="List Name"
          v-model="formInput.selectInputOptions.selectSource.listName"
          :items="$store.state.listValues.listNames"
          @change="onListNameChange"
          clearable>
        <template v-slot:no-data>
            <v-list-tile>
                <v-list-tile-content>
                    <v-list-tile-title>
                        This list name does not exist. Press <kbd>tab</kbd> to enter it anyway
                    </v-list-tile-title>
                </v-list-tile-content>
            </v-list-tile>
        </template>
      </v-combobox>
      <v-select clearable
          label="Linked data model"
          v-if="formInput.selectInputOptions.selectSource.type === 'api'"
          v-model="formInput.selectInputOptions.selectSource.model"
          :items="availableModels"
      ></v-select>
      <v-select clearable
                label="Related data model"
                v-if="formInput.selectInputOptions.selectSource.type === 'many_relation' || formInput.selectInputOptions.selectSource.type === 'one_relation'"
                v-model="formInput.selectInputOptions.selectSource.model"
                :items="availableModels"
                :disabled="columnDataType === 'many_to_many' || columnDataType === 'one_to_many'"
      ></v-select>
      <v-select clearable
              label="Value Column"
              v-if="formInput.selectInputOptions.selectSource.type === 'api'"
              v-model="formInput.selectInputOptions.selectSource.valueField"
              :items="availableColumnsWithoutPseudoColumn"
      ></v-select>
      <v-select clearable
              label="Display column of the related or linked data model"
              v-if="formInput.selectInputOptions.selectSource.type === 'api' || formInput.selectInputOptions.selectSource.type === 'many_relation' || formInput.selectInputOptions.selectSource.type === 'one_relation'"
              v-model="formInput.selectInputOptions.selectSource.textField"
              :items="availableColumns"
      ></v-select>
      <v-checkbox
          v-if="formInput.selectInputOptions.selectSource.type === 'list'"
          hide-details
          label="Allow Free Text input"
          v-model="formInput.selectInputOptions.allowFreeInput"/>
      <v-text-field
          label="Extra Filter"
          hint="Filter the records"
          v-model="formInput.selectInputOptions.selectSource.extraFilter"/>
      <v-checkbox
          hide-details
          label="Multiple"
          v-if="columnDataType !== 'many_to_many' && columnDataType !== 'one_to_many'"
          v-model="formInput.selectInputOptions.multiple"/>
    </div>
  </div>
</template>

<script>
import snakeCase from 'lodash/snakeCase'
export default {
  name: 'InputTemplate',
  props: {
    value: {
      type: Object
    },
    dataModelFullName: {
      type: String,
      required: true
    },
    dataModelColumnName: {
      type: String,
      required: true
    }
  },
  data () {
    return {
      formInput: {
        type: null,
        calculate: '',
        disabled: false,
        selectInputOptions: {
          selectSource: {
            type: 'list',
            listName: '',
            model: '',
            textField: 'remark',
            valueField: 'display',
            extraFilter: ''
          },
          allowFreeInput: false,
          multiple: false
        }
      }
    }
  },
  mounted () {
    this.formInput.type = this.columnDataType === 'many_to_many' || this.columnDataType === 'one_to_many' ? 'select' : this.value.type
    this.formInput.calculate = this.value.calculate
    this.formInput.disabled = this.value.disabled || this.isPseudoColumn
    this.updateSelectSource()
  },
  computed: {
    availableModels () {
      return this.$store.state.templates.summary.models.filter(item => item.isTableCreated).map(item => item.fullName)
    },
    availableColumns () {
      const selectedModel = this.$store.state.templates.summary.models.find(item => item.fullName === this.formInput.selectInputOptions.selectSource.model)
      const selectedTable = selectedModel ? selectedModel.table : null
      return selectedTable ? Object.entries(this.$store.state.templates.summary.models.find(item => item.table === selectedTable).columns).map((item) => item[0]) : []
    },
    availableColumnsWithoutPseudoColumn () {
      const selectedModel = this.$store.state.templates.summary.models.find(item => item.fullName === this.formInput.selectInputOptions.selectSource.model)
      const selectedTable = selectedModel ? selectedModel.table : null
      return selectedTable ? Object.entries(this.$store.state.templates.summary.models.find(item => item.table === selectedTable).columns).filter((item) => item[1].type !== 'pseudo').map((item) => item[0]) : []
    },
    dataModelTemplate () {
      return this.$store.state.templates.models.find(item => item.fullName === this.dataModelFullName)
    },
    columnDataType () {
      return this.dataModelTemplate.columns[this.dataModelColumnName].type
    },
    columnDisplayColumn () {
      return this.dataModelTemplate.columns[this.dataModelColumnName].displayColumn
    },
    columnModel () {
      console.log(this.dataModelTemplate.columns[this.dataModelColumnName])
      let retaledModel = this.$store.state.templates.summary.models.find(item => item.table === this.dataModelTemplate.columns[this.dataModelColumnName].relatedTable)
      return retaledModel.fullName
    },
    formInputTypeList () {
      // ['integer', 'double', 'string', 'string_multiple', 'dateTime', 'date', 'time']
      // return ['text', 'select', 'switch', 'datetime', 'date', 'time', 'textarea']
      switch (this.columnDataType) {
        case 'integer':
          return ['text', 'select', 'switch']
        case 'double':
          return ['text', 'select']
        case 'boolean':
          return ['switch']
        case 'string':
          return ['text', 'select', 'textarea']
        case 'text':
          return ['textarea']
        case 'string_multiple':
          return ['select']
        case 'dateTime':
          return ['datetime']
        case 'date':
          return ['date']
        case 'time':
          return ['time']
        case 'pseudo':
          return ['text', 'textarea', 'select']
        case 'many_to_many':
          return ['select']
        case 'one_to_many':
          return ['select']
        default:
          throw new Error(`Unknown column data type [${this.columnDataType}] for column ${this.dataModelColumnName}`)
      }
    },
    isPseudoColumn () {
      return this.dataModelTemplate.columns[this.dataModelColumnName].type === 'pseudo'
    },
    selectSourceType () {
      return this.formInput.selectInputOptions.selectSource.type
    }
  },
  methods: {
    updateSelectSource () {
      if (this.value.type === 'select') {
        this.formInput.selectInputOptions.selectSource = this.value.selectSource
        this.formInput.selectInputOptions.allowFreeInput = this.value.allowFreeInput
        // this.value is always true when the column type is `string_multiple`
        this.formInput.selectInputOptions.selectSource.type = this.columnDataType === 'many_to_many' ? 'many_relation' : this.columnDataType === 'one_to_many' ? 'one_relation' : this.selectSourceType
        if (this.formInput.selectInputOptions.selectSource.type === 'many_relation' || this.formInput.selectInputOptions.selectSource.type === 'one_relation') {
          this.formInput.selectInputOptions.selectSource.model = this.columnModel
          this.formInput.selectInputOptions.selectSource.textField = this.columnDisplayColumn
          this.formInput.selectInputOptions.selectSource.valueField = 'id'
        }
        this.formInput.selectInputOptions.multiple = this.columnDataType === 'string_multiple' || this.columnDataType === 'many_to_many' ? true : this.value.multiple
      } else {
        this.formInput.selectInputOptions.selectSource = {
          type: 'list',
          listName: '',
          textField: 'remark',
          valueField: 'display'
        }
      }
    },
    updateValue () {
      let value = {
        type: this.formInput.type,
        disabled: this.formInput.disabled || this.isPseudoColumn,
        calculate: this.formInput.calculate
      }
      if (this.formInput.type === 'select') {
        value['selectSource'] = this.formInput.selectInputOptions.selectSource
        value['allowFreeInput'] = this.formInput.selectInputOptions.allowFreeInput
        value['multiple'] = this.formInput.selectInputOptions.multiple // this.columnDataType === 'string_multiple' ? true : this.value.multiple
      }
      this.$emit('input', value)
    },
    onListNameChange () {
      if (!this.$store.state.listValues.listNames.includes(this.formInput.selectInputOptions.selectSource.listName)) {
        this.formInput.selectInputOptions.selectSource.listName = snakeCase(this.formInput.selectInputOptions.selectSource.listName).toUpperCase()
      }
    },
    onListSourceChange () {
      if (this.formInput.selectInputOptions.selectSource.type === 'list') {
        this.formInput.selectInputOptions.selectSource.textField = 'remark'
        this.formInput.selectInputOptions.selectSource.valueField = 'display'
      } else if (this.formInput.selectInputOptions.selectSource.type === 'many_relation' || this.formInput.selectInputOptions.selectSource.type === 'one_relation') {
        this.formInput.selectInputOptions.selectSource.model = this.columnModel
        this.formInput.selectInputOptions.selectSource.textField = this.columnDisplayColumn
        this.formInput.selectInputOptions.selectSource.valueField = 'id'
      } else {
        this.formInput.selectInputOptions.selectSource.textField = ''
        this.formInput.selectInputOptions.selectSource.valueField = ''
      }
    }
  },
  watch: {
    formInput: {
      deep: true,
      handler () {
        this.updateValue()
      }
    },
    'value.type': {
      handler () {
        this.updateSelectSource()
      }
    },
    'formInput.calculate': {
      handler () {
        if (this.formInput.calculate > '') {
          this.formInput.disabled = true
        }
      }
    }
  }
}
</script>

<style scoped>
    .c-form-inputs-input input,
    .c-form-inputs-input select {
        border: solid 1px;
    }
    .c-form-inputs-input select {
        -moz-appearance: menulist;
        -webkit-appearance: menulist;
    }
    .c-form-inputs-input select option {
        background: #333;
    }
</style>
