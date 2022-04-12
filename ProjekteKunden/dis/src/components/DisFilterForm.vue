<template>
  <v-layout row wrap align-center>
    <v-flex md12 lg7 xl8>
      <v-form ref="form">
        <v-layout row wrap pl-1>
          <v-flex
              v-for="(item, key) in dataModels"
              :key="key"
              md2 lg2 sm4 xs12 pa-1>
            <v-autocomplete
                ref="filter"
                :rules = "fieldRules"
                :loading="loading"
                :clearable="true"
                v-model="selected[key]"
                :items="cascadeListItems[key] ? localStorage.ascFilterLists.includes(key) ? cascadeListItems[key].slice().reverse() : cascadeListItems[key] : []"
                :label="key"
                :item-text="['expedition', 'site', 'hole', 'core', 'section'].includes(key) ? item => `${item.text}` : item => `${item.value} | ${item.text}`"
                :disabled="isCodeScanEnabled"
                hide-details
                :filter="(item, queryText) => String(item.text).toLowerCase().startsWith(queryText.toLowerCase())"
                :prepend-inner-icon="localStorage.ascFilterLists.includes(key) ? 'arrow_upward' : 'arrow_downward'"
                @click:prepend-inner="() => switchListSortDirection(key)"
                placeholder="Show All"
                @change="refreshCascadeListItems"
                @focus="handleValidationAfterFocus"
            ></v-autocomplete>
          </v-flex>
          <v-flex shrink align-self-end>
            <v-btn icon flat color="primary" @click="getFilterListItems" title="refresh all filter lists">
              <v-icon>refresh</v-icon>
            </v-btn>
          </v-flex>
        </v-layout>
      </v-form>
    </v-flex>
    <v-flex md12 lg5 xl4 pr-1 >
      <v-layout align-center justify-end wrap>
        <v-flex shrink>
          <v-layout row align-center>
            <v-switch
                label="Toggle Filter"
                v-model="filterByValues"
                class="c-dis-form__btn-switchfilter"
                :disabled="isCodeScanEnabled"
                v-mousetrap="{keys: shortcuts.toggleFilterByValue, disabled: false}"
                @mousetrap.prevent="filterByValues = !filterByValues">
            </v-switch>
            <v-dialog
                v-model="filterModelDialog"
                v-mousetrap="{keys: shortcuts.openFilterByValueForm, disabled: false}"
                @mousetrap.prevent="filterModelDialog = !filterModelDialog">
              <template v-slot:activator="data">
                <v-btn v-on="data.on" class="c-dis-form__btn-editfilter" title="Create or change table-filter" :disabled="isCodeScanEnabled">
                  <v-icon class="pr-1">filter_list</v-icon><span class="filtertext">Create Filter</span>
                </v-btn>
              </template>
              <v-card>
                <v-card-title>
                  <h2>Create Filter</h2>
                  <v-spacer></v-spacer>
                  <v-flex class="text-xs-right">
                    <span>
                      <v-tooltip v-model="showMiniHelp" left close-delay="1200">
                        <template v-slot:activator="{ on }">
                            <div class="text-xs-right mt-2">
                              <span v-on="on">MiniHelp
                              </span>
                            </div>
                        </template>
                        <v-flex class="text-xs-left">
                          For filtering <b>numerical</b> data, use math operators such as <code>&gt; 400</code> or <code>= 3.141</code>.<br/>
                          For searching <b>text</b> columns, use regular expressions such as <code>.*Smith.*</code><br/>
                          &nbsp;&nbsp;&nbsp;to find any text matching "Smith" (Here, "Joe Smith" and "Smithsonian").<br/>
                        </v-flex>
                        <v-flex class="text-xs-right">
                          <a target="_blank" href="https://data.icdp-online.org/mdis-docs/guide/viewer-operator.html#filtering">More information</a>
                        </v-flex>
                      </v-tooltip>
                      <v-tooltip bottom>
                        <template v-slot:activator="{ close }">
                          <v-icon @click="filterModelDialog = false" color="primary" dark v-on="close">close</v-icon>
                        </template>
                        <span>Close filters</span>
                        </v-tooltip>
                      </span>
                  </v-flex>
                </v-card-title>
                <v-card-text>
                  <v-form @keyup.native.enter="applyFilterModelValues" ref="filterByValueForm" class="c-dis-form__filter-form">
                    <v-container fluid grid-list-md pa-0>
                      <v-layout row wrap v-for="(group, index) in fieldsGroups" :key="index">
                        <v-flex v-if="!group.startsWith('-')" xs12 class="title">
                          {{group}}
                        </v-flex>
                        <v-flex v-for="field in fields.filter(item => item.group === group && item.searchable)" :key="field.name" lg2 md3 sm6 xs12 >
                          <v-text-field :disabled="field.showAsAdditionalFilter" v-model="filterModel[field.name]" :label="field.label" :append-outer-icon="getOuterIcon(field)" @click:append-outer="() => onFieldOuterIconClick(field)" />
                        </v-flex>
                      </v-layout>
                    </v-container>
                  </v-form>
                </v-card-text>
                <v-card-actions>
                  <v-btn @click="applyFilterModelValues" color="green darken-2"> Apply </v-btn>
                  <v-btn @click="$refs.filterByValueForm.reset()" color="red lighten-2"> Clear </v-btn>
                  <v-spacer></v-spacer>
                  <v-btn @click="filterModelDialog = false" color="red darken-2"> Close </v-btn>
                </v-card-actions>
              </v-card>
            </v-dialog>
          </v-layout>
        </v-flex>
        <v-flex shrink mr-2 class="scan-input-container">
          <v-progress-circular indeterminate v-if="isSearchingForBarcode"></v-progress-circular>
          <v-checkbox dense :loading="isSearchingForBarcode" v-model="isCodeScanEnabled" label="scanner"  dark v-else-if="fields.find(item => item.name === 'igsn')"></v-checkbox>
        </v-flex>
      </v-layout>
    </v-flex>
    <v-flex md12 lg12 xl12>
      <v-layout align-center wrap>
        <v-form @keyup.native.enter="applyFilterModelValues" ref="filterByValueForm" class="c-dis-form__filter-form">
          <v-container fluid grid-list-md pa-2>
            <v-layout align-center row wrap>
              <v-flex v-for="field in fields.filter(item => item.hasOwnProperty('showAsAdditionalFilter') && item.showAsAdditionalFilter)" :key="field.name" lg2 md3 sm6 xs12>
                <v-text-field
                  v-if="!(field.formInput.hasOwnProperty('selectSource') && (field.formInput.selectSource.type === 'many_relation' || field.formInput.selectSource.type === 'one_relation'))"
                  v-model="filterModel[field.name]"
                  :label="field.label"
                  :append-outer-icon="getOuterIcon(field)"
                  @click:append-outer="() => onFieldOuterIconClick(field)" />
                <dis-select-input
                  v-if="field.formInput.type === 'select' && (field.formInput.selectSource.type === 'many_relation' || field.formInput.selectSource.type === 'one_relation')"
                  :name="field.name"
                  :label="field.label"
                  :serverValidationErrors="[]"
                  :selectSource="field.formInput.selectSource"
                  :allowFreeInput="field.formInput.allowFreeInput"
                  :multiple="field.formInput.multiple || false"
                  :hint="field.description"
                  v-model="filterModel[field.name]"
                  @change="applyFilterModelValues">
                </dis-select-input>
              </v-flex>
            </v-layout>
          </v-container>
        </v-form>
      </v-layout>
    </v-flex>
    <input ref="scanInput" v-model="scannedString" class="scan-input" @blur="onScanInputBlur" @keyup.enter="searchScannedString" />
  </v-layout>
</template>

<script>
import FormService from '../services/FormService'
import CrudService from '../services/CrudService'
import FormLocalStorage from '../util/FormLocalStorage'
import urlFilterMixin from '../mixins/urlFilter'
import FORM_SHORTCUTS from '../util/shotrcuts'
import debounce from '../util/debounce'

export default {
  name: 'DisFilterForm',
  mixins: [
    urlFilterMixin
  ],
  props: {
    dataModels: {
      type: Object,
      required: true
    },
    value: {
      type: Object
    },
    formName: {
      type: String
    },
    dataModel: {
      type: String,
      required: true
    },
    fields: {
      type: Array,
      required: true
    },
    filterByValuesModel: {
      type: Object
    },
    filterValidation: {
      type: Array
    }
  },
  data () {
    return {
      selected: {},
      listItems: {},
      cascadeListItems: {},
      loading: false,
      filterModel: {},
      filterByValues: false,
      filterModelDialog: false,
      shortcuts: FORM_SHORTCUTS,
      isCodeScanEnabled: false,
      scannedString: '',
      isSearchingForBarcode: false,
      showMiniHelp: false,
      oldDependentFilterValues: [],
      fieldRules: []
    }
  },
  mounted () {
    this.$watch('filterValidation', function () {
      this.fieldRules = [...this.filterValidation]
    })
  },
  computed: {
    filterString: function () {
      let filterString = ''
      for (let key in this.dataModels) {
        if (this.selected[key] && Array.isArray(this.cascadeListItems[key]) && this.cascadeListItems[key].length > 0) {
          let item = this.cascadeListItems[key].find(item => item.value === this.selected[key])
          if (item) {
            filterString += `${item.text} `
          }
        }
      }
      return filterString
    },
    fieldsGroups () {
      return Array.from(new Set(this.fields.map(item => item.group)))
    },
    filterModelWithoutEmptyProps () {
      let obj = {}
      Object.keys(this.filterModel).forEach(key => {
        if (this.filterModel[key] !== null && typeof this.filterModel[key] !== 'undefined' && this.filterModel[key] !== '') {
          obj[key] = this.filterModel[key]
        }
      })
      return obj
    }
  },
  async created () {
    this.fields.map(field => {
      field.searchable && this.$set(this.filterModel, field.name, null)
    })
    // Set filter component reactive data
    Object.keys(this.dataModels).forEach(key => this.$set(this.selected, key, null))
    if (this.formName) {
      this.localStorage = new FormLocalStorage(this.formName)
      this.service = new FormService(this.formName)
    } else {
      this.localStorage = new FormLocalStorage(this.dataModel)
      this.service = new CrudService(this.dataModel)
    }
    if (this.localStorage.filterModel) {
      this.filterModel = Object.assign(this.filterModel, this.localStorage.filterModel)
    }
    // @todo if the selected Id is not set load the filter value in the local storage
    this.onSelectedChange = async () => {
      console.log('filter selected watcher')
      this.localStorage.filter = this.selected
      this.urlFilter = this.selected
      await this.getFilterListItems()
      this.refreshCascadeListItems()
      this.$emit('change', this.selected)
    }
    this.onSelectedChange = debounce(this.onSelectedChange, 500)
    if (this.urlFilter) {
      this.selected = Object.assign(this.selected, this.urlFilter)
    }
    if (!this.urlFilter && this.localStorage.filter) {
      this.selected = Object.assign(this.selected, this.localStorage.filter)
    }
    await this.getFilterListItems()
    this.refreshCascadeListItems()
    this.unwatchSelected = this.$watch('selected', {
      immediate: true,
      deep: true,
      handler: function (val) {
        this.onSelectedChange()
      }
    })
  },
  beforeDestroy () {
    this.unwatchSelected && this.unwatchSelected()
  },
  methods: {
    handleValidationAfterFocus () {
      this.fieldRules = []
      this.$emit('handleFilterValidation', this.fieldRules)
    },
    applyFilterModelValues () {
      this.localStorage.filterModel = this.filterModel
      if (this.filterByValues) {
        this.$emit('update:filterByValuesModel', this.filterModelWithoutEmptyProps)
      }
      this.filterByValues = true
      this.filterModelDialog = false
    },
    async getFilterListItems () {
      let filtersToBring = []
      for (let key in this.dataModels) {
        let definition = this.dataModels[key]
        let dependentValue = definition.hasOwnProperty('require') ? this.selected[definition.require.value] : null
        if (dependentValue !== null && typeof dependentValue !== 'undefined') {
          if (!definition.hasOwnProperty('require') && !this.listItems[key]) {
            filtersToBring.push({
              model: key,
              require: null
            })
          } else {
            if (Object.entries(this.oldDependentFilterValues).length > 0) {
              let shouldReload = true
              for (let [model, value] of Object.entries(this.oldDependentFilterValues)) {
                if (this.selected[definition.require.value] !== undefined && model === definition.require.value && value === this.selected[definition.require.value]) {
                  shouldReload = false
                }
              }
              if (shouldReload) {
                let require = definition.require
                filtersToBring.push({
                  model: key,
                  require: {
                    as: require.as,
                    value: this.selected[require.value]
                  }
                })
              }
            } else {
              let require = definition.require
              filtersToBring.push({
                model: key,
                require: {
                  as: require.as,
                  value: this.selected[require.value]
                }
              })
            }
          }
        } else {
          if (!definition.hasOwnProperty('require') && !this.listItems[key]) {
            if (!this.cascadeListItems[key]) {
              filtersToBring.push({
                model: key,
                // value: this.selected[key],
                require: null
              })
            }
          }
        }
      }
      if (filtersToBring.length > 0) {
        this.loading = true
        // console.log(this.dataModels, 'ss')
        let result = await this.service.getFilterLists({ models: filtersToBring })
        for (let key in this.dataModels) {
          if (result.data[key]) {
            this.listItems[key] = result.data[key]
          }
        }
        this.loading = false
      }
      for (let key in this.dataModels) {
        let oldDependentFilterName = this.dataModels[key].hasOwnProperty('require') ? this.dataModels[key].require.value : null
        let oldDependentFilterValue = this.dataModels[key].hasOwnProperty('require') ? this.selected[this.dataModels[key].require.value] : null
        if (oldDependentFilterName && oldDependentFilterValue) {
          this.oldDependentFilterValues[oldDependentFilterName] = oldDependentFilterValue
        }
      }
      this.refreshCascadeListItems()
    },
    refreshCascadeListItems () {
      for (let key in this.dataModels) {
        let definition = this.dataModels[key]
        if (!definition.require) {
          this.cascadeListItems[key] = this.listItems[key]
        } else {
          if (this.selected[definition.require.value] && this.listItems[key]) {
            let filteredList = this.listItems[key].filter(item => item[definition.require.as] === this.selected[definition.require.value])
            let selectedInList = filteredList.findIndex(item => item.value === this.selected[key]) > -1
            this.cascadeListItems[key] = filteredList
            if (selectedInList === false) {
              this.selected[key] = null
            }
          } else {
            this.selected[key] = null
            this.cascadeListItems[key] = []
          }
        }
      }
    },
    async startBarcodeScanning () {
      await this.$nextTick()
      this.$refs.scanInput.focus()
    },
    onScanInputBlur () {
      setTimeout(() => {
        this.isCodeScanEnabled && (this.isCodeScanEnabled = false)
      }, 300)
    },
    async searchScannedString () {
      const completeScannedString = this.scannedString
      this.scannedString = ''
      try {
        this.isSearchingForBarcode = true
        const data = await this.service.getList({ }, { 'igsn': completeScannedString })
        if (data.items.length > 0) {
          let filterParams = {}
          for (const key in this.dataModels) {
            if (Object.keys(data.items[0]).includes(this.dataModels[key].ref)) {
              filterParams[key] = data.items[0][this.dataModels[key].ref]
            }
          }
          let newRoute = { path: `/forms/${this.formName}-form/${data.items[0].id}`, query: { filter: this.filterToString(filterParams) } }
          console.log(newRoute)
          this.$router.replace(newRoute)
          // console.log(data.items[0], filterParams)
        }
      } catch (error) {
        console.log(error)
      } finally {
        this.isSearchingForBarcode = false
      }
    },
    switchListSortDirection (listKey) {
      console.log(`switchListSortDirection (${listKey})`, this.localStorage.ascFilterLists)
      if (!this.localStorage.ascFilterLists.includes(listKey)) {
        this.localStorage.ascFilterLists = [...this.localStorage.ascFilterLists, listKey]
      } else {
        this.localStorage.ascFilterLists = this.localStorage.ascFilterLists.filter(item => item !== listKey)
      }
    },
    // setFilterFromSelectedItem (item) {
    //   const newSelected = {}
    //   for (const key in this.dataModels) {
    //     const definition = this.dataModels[key]
    //     if (Object.keys(item).includes(definition.ref)) {
    //       newSelected[key] = item[definition.ref]
    //     } else {
    //       throw new Error(`Selected item does not have the property '${definition.ref}', please add it in the table class in 'fields()' method `)
    //     }
    //   }
    //   this.urlFilter = newSelected
    //   this.selected = newSelected
    //   this.refreshCascadeListItems()
    // },
    blurInputs () {
      for (let key in this.$refs.form.inputs) {
        this.$refs.form.inputs[key].blur()
      }
    },
    focusInputs () {
      let form = this.$refs.form
      for (let key in form.inputs) {
        if (!form.inputs[key].disabled) {
          form.inputs[key].focus()
          break
        }
      }
    },
    getOuterIcon (field) {
      if (typeof field.formInput === 'undefined') {
        return null
      }
      switch (field.formInput.type) {
        case 'date':
        case 'time':
        case 'datetime':
          return 'schedule'
        default:
          return null
      }
    },
    onFieldOuterIconClick (field) {
      const currentDateTime = this.$dateTime(new Date()).toUtc().formatForDB()
      if (!field || !field.formInput) { return null }
      switch (field.formInput.type) {
        case 'datetime':
          this.filterModel[field.name] = currentDateTime
          break
        case 'date':
          this.filterModel[field.name] = currentDateTime.split(' ')[0]
          break
        case 'time':
          this.filterModel[field.name] = currentDateTime.split(' ')[1]
          break
        default:
          return null
      }
    }
  },
  watch: {
    isCodeScanEnabled (v) {
      if (v) {
        this.startBarcodeScanning()
      }
    },
    filterString: {
      handler: function (v) {
        if (this.formName) {
          this.$setTitle(this.formName, v)
        }
      }
    },
    filterByValues: {
      handler: function (val) {
        if (val === true) {
          this.$emit('update:filterByValuesModel', this.filterModelWithoutEmptyProps)
        } else {
          this.$emit('update:filterByValuesModel', {})
        }
      }
    }
  }
}
</script>

<style scoped lang="scss">
.scan-input-container {
  position: relative
}
.scan-input {
  // position: absolute;
  // top: 0;
  // right: 0;
  width: 0;
  height: 0;
  // z-index: 99999;
  // border: dashed 1px red;
  // transform: translate(0, -100%);
}
.filtertext {
  text-transform: none;
  font-size: large;
}
.c-dis-form__filter-form {
  width: 100%;
}
</style>
