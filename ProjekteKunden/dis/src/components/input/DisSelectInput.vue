<template>
    <v-flex>
        <v-combobox
                v-if="allowFreeInput"
                :value="selected"
                :search-input.sync="searchWord"
                v-bind="$attrs"
                v-on="listeners"
                :items="listItems"
                item-text="text"
                item-value="value"
                :rules="getRules()"
                :label="inputLabel"
                :error-messages="fieldServerValidationErrors"
                :loading="loading"
                :append-outer-icon="selectSource.type === 'list' ? 'list_alt' : undefined"
                @click:append-outer="editList"
                :return-object="false"
                :multiple="multiple"
                :chips="multiple"
                :deletable-chips="multiple"
                :small-chips="multiple"
                @keyup.prevent.stop.enter=""
                :clearable="!multiple"
        >
            <template v-slot:no-data>
                <v-list-tile>
                    <v-list-tile-content>
                        <v-list-tile-title>
                            "<strong>{{ searchWord }}</strong>" is not in the list. Press <kbd>tab</kbd> to enter it anyway
                        </v-list-tile-title>
                    </v-list-tile-content>
                </v-list-tile>
            </template>
            <template v-slot:selection="{ item, parent, selected }">
                <v-chip :selected="selected" small :disabled="parent.disabled || parent.readonly || parent.getDisabled(item)">
                  <span class="pr-2">{{items.findIndex(s => s.value === item) > -1 ? items.find(s => s.value === item).text : item}}</span>
                  <v-icon small @click="!parent.disabled && !parent.readonly && !parent.getDisabled(item) && parent.selectItem(item)">close</v-icon>
                </v-chip>
            </template>
        </v-combobox>
        <v-autocomplete
                v-else
                :value="selected"
                v-bind="$attrs"
                v-on="listeners"
                :items="listItems"
                item-text="text"
                item-value="value"
                :rules="getRules()"
                :label="inputLabel"
                :error-messages="fieldServerValidationErrors"
                :loading="loading"
                :append-outer-icon="selectSource.type === 'list' ? 'list_alt' : undefined"
                @click:append-outer="editList"
                :multiple="multiple"
                :return-object="false"
                :chips="multiple"
                :deletable-chips="multiple"
                :small-chips="multiple"
                @keyup.prevent.stop.enter=""
                :search-input.sync="searchWord"
                :no-filter="selectSource.type!=='list'"
                @change="clearSearchInput"
                :clearable="!multiple"
                @input.native="updateListItems"
        >
          <template v-slot:prepend-item v-if="totalCount && listItems.length > 0">
            <v-list-tile>
              <v-list-tile-content>
                <v-list-tile-title>
                  {{ listItems.length}} of {{ totalCount }} records.
                </v-list-tile-title>
              </v-list-tile-content>
            </v-list-tile>
            <v-divider></v-divider>
          </template>
        </v-autocomplete>
        <v-dialog v-if="selectSource.type === 'list'" v-model="listFormDialog" fullscreen hide-overlay transition="dialog-bottom-transition">
            <v-card>
                <v-toolbar dark color="primary">
                    <v-btn icon dark @click.native="closeDialog">
                        <v-icon>close</v-icon>
                    </v-btn>
                    <v-toolbar-title>List Values [{{this.selectSource.listName}}]</v-toolbar-title>
                    <v-spacer></v-spacer>
                </v-toolbar>
                <v-card-text>
                    <dis-list-values-form v-if="listFormDialog" :listName="this.selectSource.listName"></dis-list-values-form>
                </v-card-text>
            </v-card>
        </v-dialog>
    </v-flex>
</template>

<script>
import InputMixin from '../../mixins/dis-input-mixin'
import ListValuesService from '@/services/ListValuesService'
import CrudService from '@/services/CrudService'
import debounce from '../../util/debounce'
import deepCompareObjects from '@/util/deepCompareObjects'

export default {
  name: 'DisSelectInput',
  mixins: [
    InputMixin
  ],
  props: {
    'value': {
      type: [Number, String, Array]
    },
    'selectSource': {
      type: Object,
      required: true
    },
    'allowFreeInput': {
      type: Boolean
    },
    'multiple': {
      type: Boolean
    },
    'formModel': {
      type: Object
    }
  },
  data () {
    return {
      selected: null,
      items: [],
      loading: false,
      listFormDialog: false,
      searchWord: null,
      totalCount: null
    }
  },
  computed: {
    filterParams () {
      const extraFilter = {}
      if (this.selectSource.extraFilter) {
        const regex = /^([a-zA-Z_]+) *(!=|==|=|<=|<|>=|>) *(.+)$/
        for (const matchedQuery of this.selectSource.extraFilter.split('&')) {
          const parts = regex.exec(matchedQuery.trim())
          if (parts && parts.length === 4) {
            const filterKey = parts[1]
            const comparison = parts[2]
            let filterValue = parts[3]
            // console.log('filterKey', filterKey, 'comparison', comparison, 'filterValue', filterValue)
            // replace formModel values
            for (const evalMatch of filterValue.matchAll(/{([^}]+)}/g)) {
              const value = evalMatch[1]
              let self = { formModel: this.formModel, ...this.formModel }
              let cb = () => {
                // eslint-disable-next-line no-eval
                return eval(value)
              }
              let v = ''
              try {
                v = (cb.bind(self))()
                if (!v) v = ''
                filterValue = filterValue.replace(evalMatch[0], v)
              } catch (e) {
                v = 'ERROR in extraFilter'
                console.error('ERROR in extraFilter value', value, ': ', e.message)
                filterValue = null
              }
            }
            if (filterValue !== null) {
              filterValue = filterValue.replace(/^'(.+)'$/, '$1')
              extraFilter[filterKey] = filterValue === 'null' ? '' : comparison + filterValue
            }
          } else {
            console.error('ERROR in part of extraFilter:', matchedQuery)
          }
        }
        // console.log('extraFilter:', extraFilter)
      }
      switch (this.selectSource.type) {
        case 'api':
          return Object.assign({ }, extraFilter)
        case 'list':
          return Object.assign({ 'listname': this.selectSource.listName }, extraFilter)
        default:
          return {}
      }
    },
    listItems () {
      let itemList = [...this.items]
      if (this.selected) {
        switch (typeof (this.selected)) {
          case 'string':
            if (!this.items.find(item => item.value === this.selected)) { itemList.unshift(this.selected) }
            break
          case 'object':
            this.correctValue()
            if (Object.keys(this.selected).length !== 0) {
              let noAvailableItems = this.selected.filter(item => !this.items.map(item => item.value).includes(item))
              noAvailableItems.forEach(item => itemList.unshift({ 'text': item, 'value': item }))
            }
        }
      }
      return itemList
    }
  },
  created () {
    this.selected = this.value
    this.textField = this.selectSource.textField
    this.valueField = this.selectSource.valueField
    this.updateListItems = debounce(this.updateListItems, 500)
    switch (this.selectSource.type) {
      case 'api':
      case 'one_relation':
      case 'many_relation':
        if (this.selected) {
          this.correctValue()
        }
        this.service = new CrudService(this.selectSource.model)
        let fields = this.selectSource.textField ? [this.selectSource.textField, this.selectSource.valueField] : [this.selectSource.valueField]
        // filter distinct
        fields = fields.filter((value, index, self) => self.indexOf(value) === index)
        this.queryParams = { 'sort': this.selectSource.textField || this.selectSource.valueField, fields: fields.join(','), q: null, value: this.selected }
        this.getAsyncListItems()
        break
      case 'list':
        this.service = new ListValuesService('ListValues')
        this.queryParams = { 'sort': 'sort' }
        this.getListItems()
        break
    }
  },
  methods: {
    clearSearchInput () {
      this.searchWord = null
    },
    updateListItems (q) {
      if (this.selectSource.type === 'many_relation' || this.selectSource.type === 'one_relation' || this.selectSource.type === 'api') {
        let fields = this.selectSource.textField ? [this.selectSource.textField, this.selectSource.valueField] : [this.selectSource.valueField]
        fields = fields.filter((value, index, self) => self.indexOf(value) === index)
        this.queryParams = { 'sort': this.selectSource.textField || this.selectSource.valueField, fields: fields.join(','), q: q ? q.srcElement.value : null, value: this.selected }
        this.getAsyncListItems()
      }
    },
    updateSelectedListItems (q) {
      if (this.selectSource.type === 'many_relation' || this.selectSource.type === 'one_relation' || this.selectSource.type === 'api') {
        let fields = this.selectSource.textField ? [this.selectSource.textField, this.selectSource.valueField] : [this.selectSource.valueField]
        fields = fields.filter((value, index, self) => self.indexOf(value) === index)
        this.queryParams = { 'sort': this.selectSource.textField || this.selectSource.valueField, fields: fields.join(','), q: q ? q.srcElement.value : null, value: this.selected }
        this.getAsyncListItems()
      }
    },
    getAsyncListItems () {
      this.loading = true
      this.service.getAsyncList(this.queryParams)
        .then(data => {
          this.items = data.items.map(item => {
            return {
              text: this.selectSource.type === 'many_relation' && this.selectSource.type === 'one_relation'
                ? `${item[this.textField]}`
                : item[this.valueField] + ((this.textField && item[this.textField] && this.textField !== this.valueField) ? ` | ${item[this.textField]}` : ``),
              // cast value to string when the input accepts multiple values
              value: this.multiple ? item[this.valueField] + '' : item[this.valueField]
            }
          }).flat().filter((v, i, a) => a.findIndex(t => (t.text === v.text && t.value === v.value)) === i) // remove duplicate in array of object
          this.totalCount = data.totalCount
        })
        .finally(() => {
          this.loading = false
        })
    },
    getListItems () {
      this.loading = true
      this.service.getList(this.queryParams, this.filterParams)
        .then(data => {
          this.items = data.items.map(item => {
            if (this.multiple && typeof item[this.valueField] === 'string' && item[this.valueField].includes(';')) {
              let listItems = []
              let splitString = item[this.valueField].split(';')
              for (const it of splitString) {
                listItems.unshift({
                  'text': it + ((this.textField && item[this.textField] && this.textField !== this.valueField) ? ` | ${item[this.textField]}` : ``),
                  'value': it
                })
              }
              return listItems
            } else {
              return {
                text: item[this.valueField] + ((this.textField && item[this.textField] && this.textField !== this.valueField) ? ` | ${item[this.textField]}` : ``),
                // cast value to string when the input accepts multiple values
                value: this.multiple ? item[this.valueField] + '' : item[this.valueField]
              }
            }
          }).flat().filter(
            (v, i, a) => a.findIndex(t => (t.text === v.text && t.value === v.value)) === i) // remove duplicate in array of object
          this.totalCount = data.totalCount
        })
        .finally(() => {
          this.loading = false
        })
    },
    editList () {
      if (this.selectSource.type !== 'list') {
        return
      }
      this.listFormDialog = true
    },
    closeDialog () {
      this.listFormDialog = false
      this.getListItems()
    },
    correctValue () {
      let validValue = []
      if (Array.isArray(this.selected)) {
        this.selected.forEach(item => {
          if (item.hasOwnProperty(this.valueField)) {
            validValue.push(item[this.valueField].toString())
          }
        })
      } else {
        this.selected = this.selected[this.valueField]
      }
      this.selected = validValue.length ? validValue : this.selected
    }
  },
  watch: {
    filterParams (newVal, oldVal) {
      if (!deepCompareObjects(newVal, oldVal)) {
        this.getListItems()
      }
    },
    value () {
      this.selected = this.value
      this.updateListItems(null)
    }
  }
}
</script>

<style scoped>
</style>
